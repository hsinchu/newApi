<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;
use app\common\model\LotteryType;
use app\common\model\BetOrder;
use app\common\model\LotteryDraw;
use app\service\FinanceService;
use app\service\fc3d\Fc3dCalculationService;
use Exception;

class AutoPaid extends Command
{
    protected function configure()
    {
        // 设置内存限制，防止段错误
        ini_set('memory_limit', '512M');
        
        $this->setName('autopaid')
            ->setDescription('自动派奖 - 从redis队列中取出待派奖订单，查询订单状态，更新订单状态为已派奖')
            ->addOption('max-jobs', 'm', \think\console\input\Option::VALUE_OPTIONAL, '最大处理任务数', 100)
            ->addOption('timeout', 't', \think\console\input\Option::VALUE_OPTIONAL, '超时时间（秒）', 300)
            ->addOption('force', 'f', \think\console\input\Option::VALUE_NONE, '强制执行，忽略锁定检查');
    }

    protected function execute(Input $input, Output $output)
    {
        $maxJobs = (int)$input->getOption('max-jobs');
        $timeout = (int)$input->getOption('timeout');
        $force = $input->getOption('force');
        
        $output->writeln("开始执行自动派奖任务 - 最大处理数: {$maxJobs}, 超时: {$timeout}秒");
        
        // 防重复执行锁
        $lockKey = 'autopaid_lock';
        
        if (!$force && Cache::get($lockKey)) {
            $output->writeln('自动派奖任务正在执行中，跳过本次执行');
            return;
        }
        
        // 设置锁，有效期为超时时间+60秒
        Cache::set($lockKey, time(), $timeout + 60);
        
        $startTime = time();
        $processedCount = 0;
        $successCount = 0;
        $failCount = 0;
                
        try {
            // 检查Redis连接
            try {
                // Cache::store('redis')->get('payout_queue');
                $output->writeln('使用数据库队列模式');
                $this->processFromDatabase($output, $maxJobs, $timeout);
            } catch (Exception $e) {
                // $output->writeln('Redis连接失败，使用数据库队列模式');
                $this->processFromDatabase($output, $maxJobs, $timeout);
                return;
            }
            
            while ($processedCount < $maxJobs && (time() - $startTime) < $timeout) {
                // 从Redis队列中取出任务
                $queueData = Cache::store('redis')->rPop('payout_queue');
                
                if (!$queueData) {
                    $output->writeln('队列为空，等待新任务...');
                    sleep(1);
                    continue;
                }
                
                $taskData = json_decode($queueData, true);
                if (!$taskData || !isset($taskData['order_id'])) {
                    $output->writeln('无效的队列数据，跳过');
                    $failCount++;
                    continue;
                }
                
                $processedCount++;
                
                try {
                    // 处理派奖任务
                    $result = $this->processPayoutTask($taskData, $output);
                    
                    if ($result) {
                        $successCount++;
                        $output->writeln("订单 {$taskData['order_no']} 派奖成功");
                    } else {
                        $failCount++;
                        $output->writeln("订单 {$taskData['order_no']} 派奖失败");
                    }
                    
                } catch (Exception $e) {
                    $failCount++;
                    Log::error("处理派奖任务失败: " . $e->getMessage(), $taskData);
                    $output->writeln("处理订单 {$taskData['order_no']} 时发生异常: " . $e->getMessage());
                }
            }
            
            $output->writeln("派奖任务完成 - 处理总数: {$processedCount}, 成功: {$successCount}, 失败: {$failCount}");
            
            // 记录日志
            Log::info("自动派奖任务完成 - 处理总数: {$processedCount}, 成功: {$successCount}, 失败: {$failCount}, 执行时间: " . (time() - $startTime) . "秒");
            
        } catch (Exception $e) {
            Log::error('自动派奖任务失败: ' . $e->getMessage());
            $output->writeln('自动派奖任务失败: ' . $e->getMessage());
        } finally {
            // 释放锁
            Cache::delete($lockKey);
        }
    }
    
    /**
     * 处理派奖任务
     * @param array $taskData
     * @param Output $output
     * @return bool
     */
    private function processPayoutTask(array $taskData, Output $output): bool
    {
        $orderId = $taskData['order_id'];
        $orderNo = $taskData['order_no'];
        
        // 查询订单信息
        $order = BetOrder::find($orderId);
        
        if (!$order) {
            $output->writeln("订单 {$orderNo} 不存在");
            return false;
        }
        
        return $this->executePayoutProcess($order, $output);
    }
    
    /**
     * 执行派奖核心处理逻辑
     * @param BetOrder $order
     * @param Output $output
     * @return bool
     */
    private function executePayoutProcess(BetOrder $order, Output $output): bool
    {
        // 检查订单状态
        if ($order->status !== BetOrder::STATUS_WINNING) {
            $output->writeln("订单 {$order->order_no} 状态不正确，当前状态: {$order->status}");
            return false;
        }
        
        // 防止重复派奖
        if ($order->settle_time > 0) {
            $output->writeln("订单 {$order->order_no} 已经派奖过了");
            return true; // 已派奖视为成功  reset
        }
        
        // 获取开奖信息
        $lotteryDraw = LotteryDraw::where([
            'lottery_code' => $order->lottery_code,
            'period_no' => $order->period_no
        ])->find();
        
        if (!$lotteryDraw) {
            $output->writeln("订单 {$order->order_no} 对应的开奖记录不存在");
            return false;
        }
        
        // 开启事务
        Db::startTrans();
        
        try {
            // 计算中奖金额（元）
            $winAmountInYuan = $this->calculateWinAmount($order, $lotteryDraw->draw_numbers);
            
            if ($winAmountInYuan <= 0) {
                $output->writeln("订单 {$order->lottery_code}  {$order->order_no} 中奖金额计算错误: {$winAmountInYuan}");
                Db::rollback();
                return false;
            }
            
            // 更新订单状态和中奖金额
            $order->win_amount = $winAmountInYuan;
            $order->status = BetOrder::STATUS_PAID;  //reset
            $order->settle_time = time();
            $order->draw_result = $lotteryDraw->draw_numbers;
            $order->save();
            
            // 调整用户余额（传入元）
            $financeService = new FinanceService();
            $financeService->adjustUserBalance(
                $order->user_id,
                $winAmountInYuan,
                "派奖奖金，订单号：" . $order->order_no,
                'PRIZE_ADD'
            );
            
            // 更新开奖表的中奖统计数据
            $winAmountInCents = (int)round($winAmountInYuan * 100); // 转换为分并避免精度问题
            $this->updateDrawWinStatistics($order->lottery_code, $order->period_no, $winAmountInCents);
            
            // 提交事务
            Db::commit();
            
            $output->writeln("订单 {$order->order_no} 派奖成功，中奖金额: " . number_format($winAmountInYuan, 2) . "元");
            
            return true;
            
        } catch (Exception $e) {
            Db::rollback();
            Log::error("派奖处理失败: " . $e->getMessage(), [
                'order_id' => $order->id,
                'order_no' => $order->order_no
            ]);
            throw $e;
        }
    }
    
    /**
     * 计算中奖金额
     * @param BetOrder $order
     * @param string $drawNumbers
     * @return float 中奖金额（元）
     */
    private function calculateWinAmount(BetOrder $order, string $drawNumbers): float
    {
        try {
            $lotteryTypeCategory = LotteryType::where('type_code', $order->lottery_code)->value('category');
        
            if($lotteryTypeCategory == 'QUICK'){
                return $this->calculateQuickLotteryWinAmount($order, $drawNumbers);
            }else{
                return $this->calculateComplexLotteryWinAmount($order, $drawNumbers);
            }
            
        } catch (Exception $e) {
            Log::error("计算中奖金额失败 - 订单号: {$order->order_no}, 错误: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * 计算快彩中奖金额
     * @param BetOrder $order
     * @param string $drawNumbers
     * @return float 中奖金额（元）
     */
    private function calculateQuickLotteryWinAmount(BetOrder $order): float
    {
        try {
            // 快彩的bet_content是简单字符串，直接使用赔率计算
            // 中奖金额 = 投注金额 * 赔率 * 倍数
            $winAmount = ($order->bet_amount) * $order->odds * ($order->multiple ?? 1);
            
            return round($winAmount, 2);
            
        } catch (Exception $e) {
            Log::error("计算快彩中奖金额失败 - 订单号: {$order->order_no}, 错误: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * 计算复杂彩种中奖金额
     * @param BetOrder $order
     * @param string $drawNumbers
     * @return float 中奖金额（元）
     */
    private function calculateComplexLotteryWinAmount(BetOrder $order, string $drawNumbers): float
    {
        try {
            $calculationService = new Fc3dCalculationService();
            
            // 解析投注内容
            $betContent = is_array($order->bet_content) ? $order->bet_content : json_decode($order->bet_content, true);
            if (!$betContent) {
                Log::error("订单 {$order->order_no} 的bet_content解析失败");
                return 0.0;
            }
            
            $result = $calculationService->calculateWinAmount(
                $betContent['type_key'] ?? '',
                $betContent,
                $drawNumbers,
                $order->bet_amount ?? 2,
                $order->multiple ?? 1,
                $order
            );
            
            if ($result['success'] && isset($result['data']['win_amount'])) {
                // 返回值已经是分，需要转换为元
                return round($result['data']['win_amount'], 2);
            }
            
            return 0.0;
            
        } catch (Exception $e) {
            Log::error("计算复杂彩种中奖金额失败 - 订单号: {$order->order_no}, 错误: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * 获取队列长度
     * @return int
     */
    public function getQueueLength(): int
    {
        return Cache::store('redis')->lLen('payout_queue');
    }
    
    /**
     * 清空队列
     */
    public function clearQueue(): void
    {
        Cache::store('redis')->del('payout_queue');
    }
    
    /**
     * 更新开奖表的中奖统计数据
     * @param string $lotteryCode
     * @param string $periodNo
     * @param int $winAmountInCents 中奖金额（分）
     */
    private function updateDrawWinStatistics(string $lotteryCode, string $periodNo, int $winAmountInCents): void
    {
        try {
            // 使用原生SQL更新，避免并发问题
            Db::execute(
                "UPDATE fa_lottery_draw SET 
                    total_win_amount = total_win_amount + ?, 
                    win_count = win_count + 1,
                    settle_time = ?
                WHERE lottery_code = ? AND period_no = ?",
                [$winAmountInCents, time(), $lotteryCode, $periodNo]
            );
            
            Log::info("更新开奖表中奖统计 - 彩种: {$lotteryCode}, 期号: {$periodNo}, 中奖金额: {$winAmountInCents}");
        } catch (Exception $e) {
            Log::error('更新开奖表中奖统计失败: ' . $e->getMessage(), [
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo,
                'win_amount' => $winAmountInCents
            ]);
        }
    }
    
    /**
     * 从数据库处理派奖任务
     * @param Output $output
     * @param int $maxJobs
     * @param int $timeout
     */
    private function processFromDatabase(Output $output, int $maxJobs, int $timeout): void{
        $processedCount = 0;
        $startTime = time();
        
        while ($processedCount < $maxJobs && (time() - $startTime) < $timeout) {
            // 直接从数据库查询待派奖订单
            $orders = BetOrder::where('status', BetOrder::STATUS_WINNING)
                ->limit(10)
                ->select();
                
            if ($orders->isEmpty()) {
                $output->writeln('没有待派奖订单，等待...');
                sleep(1);
                continue;
            }
            
            foreach ($orders as $order) {
                if ($processedCount >= $maxJobs || (time() - $startTime) >= $timeout) {
                    break 2;
                }
                
                try {
                    $this->processPayoutOrder($order, $output);
                    $processedCount++;
                } catch (Exception $e) {
                    Log::error("处理订单 {$order->order_no} 派奖失败: " . $e->getMessage());
                    $output->writeln("处理订单 {$order->order_no} 派奖失败: " . $e->getMessage());
                }
            }
        }
        
        $output->writeln("数据库模式派奖任务完成 - 处理订单数: {$processedCount}笔");
    }
    
    /**
     * 处理单个订单的派奖
     * @param BetOrder $order
     * @param Output $output
     * @return bool
     */
    private function processPayoutOrder(BetOrder $order, Output $output): bool
    {
        return $this->executePayoutProcess($order, $output);
    }
}