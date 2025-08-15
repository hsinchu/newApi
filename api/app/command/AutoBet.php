<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;
use app\service\LotteryService;
use app\service\BetOrderService;
use app\service\LotteryBetService;
use app\service\FinanceService;
use app\common\model\BetOrder;
use app\common\model\LotteryType;
use app\common\model\LotteryBonus;
use app\common\model\User;
use Exception;

class AutoBet extends Command
{
    protected function configure()
    {
        // 设置内存限制，防止段错误
        ini_set('memory_limit', '512M');
        
        $this->setName('autobet')
            ->setDescription('自动投注ff3d - 随机用户、玩法、赔率和金额')
            ->addArgument('count', \think\console\input\Argument::OPTIONAL, '投注次数', 10)
            ->addOption('lottery', 'l', \think\console\input\Option::VALUE_REQUIRED, '彩种代码', 'ff3d')
            ->addOption('interval', 'i', \think\console\input\Option::VALUE_REQUIRED, '投注间隔(秒)', 2);
    }

    protected function execute(Input $input, Output $output)
    {
        $count = (int)$input->getArgument('count');
        $lotteryCode = $input->getOption('lottery');
        $interval = (int)$input->getOption('interval');
        
        $output->writeln("开始执行自动投注任务 - 彩种: {$lotteryCode}, 次数: {$count}, 间隔: {$interval}秒");
        
        // 获取彩种信息
        $lotteryType = LotteryType::where('type_code', $lotteryCode)->find();
        if (!$lotteryType) {
            $output->writeln("错误: 彩种 {$lotteryCode} 不存在");
            return;
        }
        
        if (!$lotteryType->is_enabled) {
            $output->writeln("错误: 彩种 {$lotteryCode} 未启用");
            return;
        }
        
        // 获取玩法配置
        $playTypes = $this->getPlayTypes($lotteryType->id);
        if (empty($playTypes)) {
            $output->writeln("错误: 彩种 {$lotteryCode} 没有可用玩法");
            return;
        }
        
        $output->writeln("找到 " . count($playTypes) . " 个可用玩法");
        
        $successCount = 0;
        $failCount = 0;
        
        for ($i = 1; $i <= $count; $i++) {
            try {
                $output->writeln("\n执行第 {$i} 次投注...");
                
                // 随机选择用户ID (2-7)
                $userId = rand(2, 14);
                
                // 验证用户是否存在且有足够余额
                $user = User::find($userId);
                if (!$user) {
                    $output->writeln("用户ID {$userId} 不存在，跳过");
                    $failCount++;
                    continue;
                }
                
                // 随机选择玩法
                $playType = $playTypes[array_rand($playTypes)];
                
                // 随机选择赔率索引
                $bonusJson = $playType['bonus_json'];
                if (is_string($bonusJson)) {
                    $bonusArray = json_decode($bonusJson, true);
                } else {
                    $bonusArray = $bonusJson;
                }
                $bonusIndex = array_rand($bonusArray);
                $odds = (float)$bonusArray[$bonusIndex];
                

                
                // 获取当前期号信息
                 $lotteryService = new \app\service\LotteryService();
                 $currentPeriodResult = $lotteryService->getCurrentPeriod($lotteryCode);
                 
                 if ($currentPeriodResult['code'] != 1) {
                     throw new \Exception('获取当前期号失败: ' . $currentPeriodResult['msg']);
                 }
                 
                 $periodData = $currentPeriodResult['data'];
                 $periodNo = $periodData['period_number'];
                 
                 // 检查是否封盘
                 if ($periodData['status'] != 'normal') {
                     $this->output->writeln("当前期号{$periodNo}已封盘，跳过投注");
                     $failCount++;
                     sleep($interval);
                     continue;
                 }
                 
                 // 获取最大投注金额限制
                 $lotteryBetService = new \app\service\LotteryBetService();
                 $maxBetResult = $lotteryBetService->calculateMaxBetAmount(
                     $lotteryCode,
                     $periodNo,
                     $playType['type_key'],
                     $odds,
                     $userId
                 );
                
                if ($maxBetResult['status'] === 'error') {
                    throw new \Exception('获取最大投注额失败: ' . $maxBetResult['message']);
                }
                
                $systemMaxBet = floor($maxBetResult['system_max_bet']);
                
                // 随机选择投注金额（50-200元）
                $randomBetAmount = rand(30, 200);
                
                // 如果随机金额大于系统最大投注额，使用系统最大投注额
                if ($randomBetAmount > $systemMaxBet) {
                    $betAmountYuan = $systemMaxBet;
                } else {
                    $betAmountYuan = $randomBetAmount;
                }
                
                // 如果投注金额小于2元，跳过投注
                 if ($betAmountYuan < 2) {
                     $this->output->writeln("用户{$userId}投注金额{$betAmountYuan}元小于2元，跳过投注");
                     $failCount++;
                     continue;
                 }
                
                $betAmount = $betAmountYuan; // 直接使用元为单位
                
                // 检查用户余额
                if ($user->money < $betAmount) {
                    $output->writeln("用户ID {$userId} 余额不足 (余额: {$user->money}分, 需要: {$betAmount}分)，跳过");
                    $failCount++;
                    continue;
                }
                
                // 期号已在前面生成
                
                // 执行投注
                $result = $this->placeBet([
                    'user' => $user,
                    'lottery_type' => $lotteryType,
                    'play_type' => $playType,
                    'bet_amount' => $betAmount,
                    'odds' => $odds,
                    'bonus_index' => $bonusIndex,
                    'period_no' => $periodNo
                ]);
                
                if ($result['success']) {
                
                    $lotteryBetService = new LotteryBetService();
                    $lotteryBetService->updateBonusPool($lotteryCode, $betAmount);
                    $output->writeln("投注成功: 用户{$userId}, 玩法{$playType['type_name']}, 金额{$betAmountYuan}元, 赔率{$odds}, 订单号{$result['order_no']}");
                    $successCount++;
                } else {
                    $output->writeln("投注失败: {$result['message']}");
                    $failCount++;
                }
                
            } catch (Exception $e) {
                $output->writeln("投注异常: " . $e->getMessage());
                $output->writeln("异常文件: " . $e->getFile() . ":" . $e->getLine());
                $failCount++;
                sleep($interval);
            }
            
            // 间隔等待
            if ($i < $count && $interval > 0) {
                $output->writeln("等待 {$interval} 秒...");
                sleep($interval);
            }
        }
        
        $output->writeln("\n自动投注任务完成!");
        $output->writeln("成功: {$successCount} 次");
        $output->writeln("失败: {$failCount} 次");
    }
    
    /**
     * 获取彩种玩法配置
     */
    private function getPlayTypes($lotteryId)
    {
        return LotteryBonus::where('lottery_id', $lotteryId)
            ->where('status', 1)
            ->whereIn('type_key', ['da', 'xiao', 'he']) // 大、小、和
            ->select()
            ->toArray();
    }
    
    /**
     * 执行投注
     */
    private function placeBet($params)
    {
        try {
            $user = $params['user'];
            $lotteryType = $params['lottery_type'];
            $playType = $params['play_type'];
            $betAmount = $params['bet_amount'];
            $odds = $params['odds'];
            $bonusIndex = $params['bonus_index'];
            $periodNo = $params['period_no'];
            
            // 开启事务
            Db::startTrans();
            
            try {
                // 生成订单号
                $orderNo = BetOrder::generateOrderNo();
                
                // 计算赠送金额扣除（确保不超过用户实际拥有的gift_money）
                $giftMoneyToDeduct = min($user->gift_money, $betAmount);
                $giftMoneyRatio = $betAmount > 0 ? $giftMoneyToDeduct / $betAmount : 0;
                
                // 构建bet_content
                $betContent = [
                    'numbers' => $playType['type_name'], // 玩法值（如'大'、'小'、'和'）
                    'type_key' => $playType['type_key'],
                    'type_name' => $playType['type_name'],
                    'odds' => $odds
                ];
                
                // 创建投注订单
                $betOrder = BetOrder::create([
                    'order_no' => $orderNo,
                    'user_id' => $user->id,
                    'lottery_type_id' => $lotteryType->id,
                    'lottery_code' => $lotteryType->type_code,
                    'period_no' => $periodNo,
                    'bet_content' => $lotteryType->category == 'QUICK' ? $playType['type_key'] : json_encode($betContent, JSON_UNESCAPED_UNICODE),
                    'bet_amount' => $betAmount,
                    'gift_money' => $giftMoneyToDeduct,
                    'gift_money_ratio' => $giftMoneyRatio,
                    'multiple' => 1,
                    'note' => 1,
                    'total_amount' => $betAmount,
                    'win_amount' => 0,
                    'commission_amount' => 0,
                    'agent_id' => 0, // 自动投注没有代理
                    'odds' => $odds,
                    'bet_type' => $playType['type_key'],
                    'bet_type_name' => $playType['type_name'],
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
                
                // 扣除用户赠送金额
                if ($giftMoneyToDeduct > 0) {
                    $user->gift_money -= $giftMoneyToDeduct;
                    $user->save();
                }
                
                // 记录资金变动（扣除投注金额）
                $financeService = new FinanceService();
                $financeService->adjustUserBalance($user->id, -$betAmount, '自动投注扣款', 'BET_DEDUCT');
                
                // 提交事务
                Db::commit();
                
                return [
                    'success' => true,
                    'order_no' => $orderNo,
                    'order_id' => $betOrder->id
                ];
                
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}