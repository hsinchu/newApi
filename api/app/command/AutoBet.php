<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\common\model\BetOrder;
use app\common\model\User;
use app\service\LotteryService;
use app\service\FinanceService;
use app\service\BetOrderService;
use app\service\WebsockService;
use Exception;

class AutoBet extends Command
{
    protected function configure()
    {
        $this->setName('autobet')
            ->setDescription('自动投注脚本 - 为指定用户随机生成投注订单');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始执行自动投注...');
        $lotteryCode = 'ff3d';
        
        try {
            // 获取当前期号
            $lotteryService = new LotteryService();
            $currentPeriodResult = $lotteryService->getCurrentPeriod($lotteryCode);
            
            if ($currentPeriodResult['code'] != 1) {
                $output->writeln('获取当前期号失败: ' . $currentPeriodResult['msg']);
                return;
            }
            
            $periodNo = $currentPeriodResult['data']['period_number'];
            $output->writeln('当前期号: ' . $periodNo);
            
            // 期号验证
            $periodValidation = $lotteryService->validatePeriod($periodNo, $lotteryCode);
            if ($periodValidation['code'] != 1) {
                $output->writeln('期号验证失败: ' . $periodValidation['msg']);
                return;
            }
            
            // 检查是否还在投注时间内
            if ($currentPeriodResult['data']['remaining_minutes'] <= 0) {
                $output->writeln('当前期号已截止投注，跳过本次执行');
                return;
            }
            
            // 用户ID范围：2-7
            $userIds = range(2, 7);
            
            // 投注类型选项
            $betTypes = ['da'=>'大', 'xiao'=>'小', 'he'=>'和'];
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($userIds as $userId) {
                try {
                    // 检查用户是否存在且有效
                    $user = User::find($userId);
                    if (!$user || $user->status != 1) {
                        $output->writeln("用户ID {$userId} 不存在或已禁用，跳过");
                        continue;
                    }
                    
                    // 随机生成投注金额（2-100元）
                    $betAmount = rand(2, 100);
                    
                    // 随机选择投注类型
                    $betType = $betTypes[array_rand($betTypes)];
                    
                    // 构造投注数据
                    $betData = [
                        [
                            'type' => 'daxiaohe',
                            'type_name' => '大小和',
                            'numbers' => $betType,
                            'key' => array_search($betType, $betTypes),
                            'note' => 1,
                            'money' => $betAmount,
                            'bonus' => 0 // 将在后面获取
                        ]
                    ];
                    
                    // 计算总金额
                    $totalAmount = $betAmount;
                    
                    // 检查用户余额
                    if ($user->money < $totalAmount) {
                        $output->writeln("用户ID {$userId} 余额不足，当前余额: " . number_format($user->money, 2) . "元，需要: {$totalAmount}元");
                        $failCount++;
                        continue;
                    }
                    
                    // 开启事务
                    Db::startTrans();
                    
                    try {
                        // 计算赠送金额扣除（确保不超过用户实际拥有的gift_money）
                        $giftMoneyToDeduct = min($user->gift_money, $totalAmount);
                        $giftMoneyRatio = $totalAmount > 0 ? $giftMoneyToDeduct / $totalAmount : 0;
                        
                        // 生成订单号
                        $orderNo = BetOrder::generateOrderNo();
                        
                        // 计算当前订单的赠送金额
                        $orderGiftMoney = (int)($totalAmount * $giftMoneyRatio);

                        $min = 1.5;
                        $max = 3.5;
                        $range = $max - $min; // 计算范围差值
                        // 生成随机数
                        $randomNumber = $min + (mt_rand() / mt_getrandmax()) * $range;
                        // 可以使用number_format()控制小数位数，例如保留2位小数
                        $formattedNumber = number_format($randomNumber, 2);
                        
                        // 创建投注订单
                        BetOrder::create([
                            'order_no' => $orderNo,
                            'user_id' => $userId,
                            'lottery_type_id' => $lotteryService->getLotteryTypeId($lotteryCode),
                            'lottery_code' => $lotteryCode,
                            'period_no' => $periodNo,
                            'bet_content' => json_encode($betData[0], JSON_UNESCAPED_UNICODE),
                            'bet_amount' => $betAmount,
                            'gift_money' => $orderGiftMoney,
                            'gift_money_ratio' => $giftMoneyRatio,
                            'multiple' => 1,
                            'total_amount' => $totalAmount,
                            'win_amount' => 0,
                            'commission_amount' => 0,
                            'agent_id' => 0,
                            'odds' => $formattedNumber,
                            'bet_type' => 'daxiaohe',
                            'bet_type_name' => '大小和',
                            'status' => BetOrder::STATUS_CONFIRMED,
                            'draw_result' => '',
                            'draw_time' => 0,
                            'settle_time' => 0,
                            'ip' => '127.0.0.1',
                            'user_agent' => 'AutoBet Script',
                            'remark' => '自动投注',
                            'create_time' => time(),
                            'update_time' => time()
                        ]);
                        
                        // 扣除用户赠送金额（直接操作gift_money字段，不添加账变记录）
                        if ($giftMoneyToDeduct > 0) {
                            $user->gift_money -= $giftMoneyToDeduct;
                            $user->save();
                        }
                        
                        // 记录资金变动（扣除投注金额）
                        $financeService = new FinanceService();
                        $financeService->adjustUserBalance(
                            $userId, 
                            -$totalAmount, 
                            '自动投注扣款 - 订单号：' . $orderNo, 
                            'BET_DEDUCT'
                        );

                        

                        // 处理投注返佣
                        BetOrderService::processBetRebate($userId, $totalAmount, [$orderNo]);
                        
                        // 提交事务
                        Db::commit();
                        
                        // 自动投注成功后，推送奖池更新（70%的投注金额）
                        WebsockService::pushPrizePoolUpdate($lotteryCode, $totalAmount, $userId);
                        
                        $output->writeln("用户ID {$userId} 自动投注成功 - 订单号: {$orderNo}, 金额: {$totalAmount}元, 类型: {$betType}, 使用赠送金额: {$orderGiftMoney}元");
                        $successCount++;
                        
                    } catch (Exception $e) {
                        Db::rollback();
                        Log::error('自动投注失败: ' . $e->getMessage());
                        $output->writeln("用户ID {$userId} 投注失败: " . $e->getMessage());
                        $failCount++;
                    }
                    
                } catch (Exception $e) {
                    $output->writeln("处理用户ID {$userId} 时发生异常: " . $e->getMessage());
                    Log::error("处理用户ID {$userId} 时发生异常: " . $e->getMessage());
                    $failCount++;
                }
                
                // 添加随机延迟，避免并发问题
                usleep(rand(100000, 500000)); // 0.1-0.5秒随机延迟
            }
            
            $output->writeln("自动投注执行完成 - 成功: {$successCount}笔, 失败: {$failCount}笔");
            
            // 记录日志
            Log::info("自动投注执行完成", [
                'period_no' => $periodNo,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'execute_time' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $output->writeln('自动投注执行异常: ' . $e->getMessage());
            Log::error('自动投注执行异常: ' . $e->getMessage());
        }
    }
}