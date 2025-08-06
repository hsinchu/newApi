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
use app\service\ApiService;
use app\service\LotteryBetService;
use app\common\model\BetOrder;
use app\common\model\LotteryDraw;
use app\common\model\LotteryType;
use Exception;

class AutoDraw extends Command
{
    protected function configure()
    {
        $this->setName('autodraw')
            ->setDescription('自动开奖 - 把待派奖的订单加入redis队列，把未中奖的订单执行未中奖佣金处理')
            ->addArgument('lottery_code', \think\console\input\Argument::OPTIONAL, '彩种代码，如：ff3d')
            ->addOption('force', 'f', \think\console\input\Option::VALUE_NONE, '强制执行，忽略锁定检查')
            ->addOption('current', 'c', \think\console\input\Option::VALUE_NONE, '处理当前期订单')
            ->addOption('period', 'p', \think\console\input\Option::VALUE_REQUIRED, '指定期号');
    }

    protected function execute(Input $input, Output $output)
    {
        $lotteryCode = $input->getArgument('lottery_code') ?: 'ff3d';
        $force = $input->getOption('force');
        
        $output->writeln("开始执行自动开奖任务 - 彩种: {$lotteryCode}");
        
        // 防重复执行锁
        $lockKey = "autodraw_lock_{$lotteryCode}";
        
        if (!$force && Cache::get($lockKey)) {
            $output->writeln('自动开奖任务正在执行中，跳过本次执行');
            return;
        }
        
        // 设置锁，有效期5分钟
        Cache::set($lockKey, time(), 300);
      
        $lotteryType = $this->getLotteryTypeInfo($lotteryCode);
        
        try {
            $lotteryService = new LotteryService();
            
            // 根据选项获取期号信息
            if ($input->getOption('period')) {
                // 使用指定的期号
                $periodNo = $input->getOption('period');
            } elseif ($input->getOption('current')) {
                // 获取当前期期号信息
                $currentPeriodResult = $lotteryService->getCurrentPeriod($lotteryCode);
                
                if ($currentPeriodResult['code'] != 1) {
                    $output->writeln('获取当前期期号失败: ' . $currentPeriodResult['msg']);
                    return;
                }
                
                $periodNo = $currentPeriodResult['data']['period_number'];
            } else {
                
                if ($lotteryType && $lotteryType['category'] === 'QUICK') {
                    // 快彩：使用本地逻辑获取上一期
                    $output->writeln('快彩彩种，使用本地逻辑获取期号');
                    $previousPeriodResult = $lotteryService->getPreviousPeriod($lotteryCode);
                    
                    if ($previousPeriodResult['code'] != 1) {
                        $output->writeln('获取上一期期号失败: ' . $previousPeriodResult['msg']);
                        return;
                    }
                    
                    $periodNo = $previousPeriodResult['data']['period_number'];
                } else {
                    // 官方彩种：支持从官方API获取真实开奖数据
                    $output->writeln('官方彩种，尝试从API获取真实数据');
                    $periodNo = $this->getLastPeriodWithRealData($lotteryCode, $lotteryService, $output);
                    
                    if (!$periodNo) {
                        $output->writeln('获取期号信息失败');
                        return;
                    }
                }
            }
            $output->writeln("处理期号: {$periodNo}");
            
            // 检查是否已经开奖并获取开奖号码
            $existingDraw = LotteryDraw::where([
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo
            ])->find();
            
            $drawNumbers = null;
            if ($existingDraw) {
                $drawNumbers = $existingDraw->draw_numbers;
                $output->writeln("期号 {$periodNo} 已经开奖，开奖号码: {$drawNumbers}");
            } else {
                // 根据彩种类型决定开奖号码获取方式（复用之前获取的彩种信息）
                if ($lotteryType && $lotteryType['category'] === 'QUICK') {
                    // 快彩：使用智能开奖号码生成
                    $drawNumbers = $this->generateDrawNumbers($lotteryCode, $periodNo);
                    $output->writeln("快彩生成智能开奖号码: {$drawNumbers}");
                } else {
                    // 官方彩种：尝试获取真实开奖号码
                    $drawNumbers = $this->getRealDrawNumbers($lotteryCode, $periodNo, $output);
                    if ($drawNumbers) {
                        $output->writeln("官方彩种获取真实开奖号码: {$drawNumbers}");
                    }
                }
                
                // 保存开奖记录
                LotteryDraw::create([
                    'lottery_type_id' => $lotteryType['id'],
                    'lottery_code' => $lotteryCode,
                    'period_no' => $periodNo,
                    'draw_numbers' => $drawNumbers,
                    'draw_time' => time(),
                    'status' => 'DRAWN',
                    'create_time' => time(),
                    'update_time' => time()
                ]);
                
                $output->writeln("期号 {$periodNo} 开奖完成");
            }
            
            // 获取该期所有订单
            $orders = BetOrder::where([
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo,
                'status' => BetOrder::STATUS_CONFIRMED
            ])->select();
            
            if ($orders->isEmpty()) {
                $output->writeln("期号 {$periodNo} 没有待处理的订单");
                return;
            }
            
            $winningOrders = 0;
            $losingOrders = 0;
            $queuedOrders = 0;
            $currentTime = time();
            
            // 批量更新订单的开奖结果
            BetOrder::where([
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo,
                'status' => BetOrder::STATUS_CONFIRMED
            ])->update([
                'draw_result' => $drawNumbers,
                'draw_time' => $currentTime
            ]);
            
            foreach ($orders as $order) {
                try {
                    // 判断是否中奖
                    $isWinning = $this->checkWinning($order, $drawNumbers);
                    
                    if ($isWinning) {
                        // 中奖订单加入派奖队列
                        $this->addToPayoutQueue($order);
                        $winningOrders++;
                        $queuedOrders++;
                        
                        $output->writeln("订单 {$order->order_no} 中奖，已加入派奖队列");
                    } else {
                        // 未中奖订单处理返佣
                        $this->processLosingOrder($order);
                        $losingOrders++;
                        
                        $output->writeln("订单 {$order->order_no} 未中奖，已处理返佣");
                    }
                    
                } catch (Exception $e) {
                    Log::error("处理订单 {$order->order_no} 失败: " . $e->getMessage());
                    $output->writeln("处理订单 {$order->order_no} 失败: " . $e->getMessage());
                }
            }
            
            $output->writeln("开奖任务完成 - 中奖订单: {$winningOrders}笔, 未中奖订单: {$losingOrders}笔, 加入队列: {$queuedOrders}笔");
            
            // 记录日志
            Log::info("自动开奖任务完成", [
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo,
                'winning_orders' => $winningOrders,
                'losing_orders' => $losingOrders,
                'queued_orders' => $queuedOrders
            ]);
            
        } catch (Exception $e) {
            Log::error('自动开奖任务失败: ' . $e->getMessage());
            $output->writeln('自动开奖任务失败: ' . $e->getMessage());
        } finally {
            // 释放锁
            Cache::delete($lockKey);
        }
    }
    
    /**
     * 获取彩种类型信息
     */
    private function getLotteryTypeInfo($lotteryCode)
    {
        try {
            $lotteryType = LotteryType::where('type_code', $lotteryCode)->find();
            return $lotteryType ? $lotteryType->toArray() : null;
        } catch (\Exception $e) {
            \think\facade\Log::error('获取彩种类型信息失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 获取上一期期号并尝试从官方API获取真实数据
     * @param string $lotteryCode
     * @param LotteryService $lotteryService
     * @param Output $output
     * @return string|null
     */
    private function getLastPeriodWithRealData(string $lotteryCode, LotteryService $lotteryService, Output $output): ?string
    {
        try {
            // 首先尝试从官方API获取最新开奖数据
            $realData = $this->fetchRealLotteryData($lotteryCode, $output);
            
            if ($realData && isset($realData['result'])) {
                // 处理API返回的所有期号记录
                $apiResults = $realData['result'];
                $latestPeriodNo = null;
                
                foreach ($apiResults as $result) {
                    if (isset($result['code'])) {
                        $periodNo = $result['code'];
                        
                        // 检查该期号是否已在lottery_draw表中存在
                        $existingDraw = LotteryDraw::where('lottery_code', $lotteryCode)
                            ->where('period_no', $periodNo)
                            ->find();
                        
                        if (!$existingDraw) {
                            // 该期号没有开奖记录，需要添加
                            $output->writeln("发现未开奖期号: {$periodNo}");
                            
                            // 获取该期号的开奖号码
                            $drawNumbers = $this->formatDrawNumbers($result, $lotteryCode, $output);
                            if ($drawNumbers) {
                                // 获取彩种ID
                                $lotteryType = $this->getLotteryTypeInfo($lotteryCode);
                                $lotteryTypeId = $lotteryType ? $lotteryType['id'] : 0;
                                
                                // 保存开奖记录
                                LotteryDraw::create([
                                    'lottery_type_id' => $lotteryTypeId,
                                    'lottery_code' => $lotteryCode,
                                    'period_no' => $periodNo,
                                    'draw_numbers' => $drawNumbers,
                                    'draw_time' => time(),
                                    'status' => 'DRAWN',
                                    'is_official' => 1,
                                    'create_time' => time(),
                                    'update_time' => time()
                                ]);
                                
                                $output->writeln("已保存期号 {$periodNo} 的开奖记录: {$drawNumbers}");
                            }
                        }
                        
                        // 记录最新期号（用于返回）
                        if (!$latestPeriodNo || $periodNo > $latestPeriodNo) {
                            $latestPeriodNo = $periodNo;
                        }
                    }
                }
                
                if ($latestPeriodNo) {
                    $output->writeln("从官方API获取到最新期号: {$latestPeriodNo}");
                    return $latestPeriodNo;
                }
            }
            
            // 如果API获取失败，使用本地逻辑获取上一期
            $output->writeln('官方API获取失败，使用本地逻辑获取上一期');
            $previousPeriodResult = $lotteryService->getPreviousPeriod($lotteryCode);
            
            if ($previousPeriodResult['code'] != 1) {
                $output->writeln('获取上一期期号失败: ' . $previousPeriodResult['msg']);
                return null;
            }
            
            return $previousPeriodResult['data']['period_number'];
            
        } catch (Exception $e) {
            $output->writeln('获取期号信息异常: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 从官方API获取福利彩票数据
     * @param string $lotteryCode
     * @param Output $output
     * @return array|null
     */
    private function fetchRealLotteryData(string $lotteryCode, Output $output): ?array
    {
        try {
            // 映射彩种代码到官方API名称
            $lotteryNameMap = [
                '3d' => '3d',
                'pl3' => '35',
            ];
            
            $apiName = $lotteryNameMap[$lotteryCode] ?? '3d';
            $dayStart = date('Y-m-d', strtotime('-7 days')); // 查询最近7天的数据
            
            $apiService = new ApiService();
            switch($lotteryCode){
                case 'pl3':
                    $result = $apiService->getSportKj($apiName, 2);
                    // 验证返回数据格式并转换为标准格式
                    if (isset($result['errorMessage']) && $result['errorMessage'] == '处理成功' && isset($result['value']) && !empty($result['value']['lastPoolDraw'])) {
                        $number = str_replace(' ', ',', $result['value']['lastPoolDraw']['lotteryDrawResult']);
                        $code = $result['value']['lastPoolDraw']['lotteryDrawNum'];
                        $standardResult = [
                            'state' => 0,
                            'result' => [[
                                'red' => $number,
                                'code' => $code,
                                'date' => $result['value']['lastPoolDraw']['lotteryDrawTime'],
                            ]]
                        ];
                        $output->writeln("成功获取官方API【pl3】数据");
                        return $standardResult;
                    }
                    break;
                case '3d':
                    $result = $apiService->GetWelfareKJ($apiName, $dayStart);
                    // 验证返回数据格式
                    if (isset($result['state']) && $result['state'] == 0 && isset($result['result']) && !empty($result['result'])) {
                        $output->writeln("成功获取官方API【fc3d】数据，共 " . count($result['result']) . " 条记录");
                        return $result;
                    }
                    break;            
            }
            
            $output->writeln('官方API返回数据格式异常');
            return null;
            
        } catch (Exception $e) {
            $output->writeln('调用官方API异常: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 获取真实开奖号码
     * @param string $lotteryCode
     * @param string $periodNo
     * @param Output $output
     * @return string|null
     */
    private function getRealDrawNumbers(string $lotteryCode, string $periodNo, Output $output): ?string
    {
        try {
            $realData = $this->fetchRealLotteryData($lotteryCode, $output);
            print_r($realData);
            
            if (!$realData || !isset($realData['result'])) {
                return null;
            }
            
            // 查找匹配的期号
            foreach ($realData['result'] as $item) {
                if (isset($item['code']) && $item['code'] == $periodNo) {
                    // 处理开奖号码格式
                    $drawNumbers = $this->formatDrawNumbers($item, $lotteryCode, $output);
                    if ($drawNumbers) {
                        return $drawNumbers;
                    }
                }
            }
            
            $output->writeln("未找到期号 {$periodNo} 的开奖数据");
            return null;
            
        } catch (Exception $e) {
            $output->writeln('获取真实开奖号码异常: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 格式化开奖号码
     * @param array $item 官方API返回的单条数据
     * @param string $lotteryCode
     * @param Output $output
     * @return string|null
     */
    private function formatDrawNumbers(array $item, string $lotteryCode, Output $output): ?string
    {
        try {
            // 根据彩种处理不同的号码格式
            switch ($lotteryCode) {
                case 'xxx':
                    
                    break;
                default:
                    // 默认处理：优先使用red字段
                    if (isset($item['red']) && !empty($item['red'])) {
                        return $item['red'];
                    }
            }
            
            $output->writeln('开奖号码格式异常: ' . json_encode($item));
            return null;
            
        } catch (Exception $e) {
            $output->writeln('格式化开奖号码异常: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 生成开奖号码
     * @param string $lotteryCode
     * @param string $periodNo 期号
     * @return string
     */
    private function generateDrawNumbers(string $lotteryCode, string $periodNo = ''): string
    {
        $lotteryTypeCategory = LotteryType::where('type_code', $lotteryCode)->value('category');
        // 对于快彩（ff3d, 5f3d），使用智能开奖号码生成
        if ($lotteryTypeCategory == 'QUICK' && !empty($periodNo)) {
            $smartNumbers = $this->generateSmartDrawNumbers($lotteryCode, $periodNo);
            if ($smartNumbers) {
                return $smartNumbers;
            }
        }
        
        // 原有的随机生成逻辑作为备用
        switch ($lotteryCode) {
            case 'ff3d':
            case '3d':
            case 'fc3d':
                // 3D彩票：生成3个0-9的数字
                return sprintf('%d,%d,%d', rand(0, 9), rand(0, 9), rand(0, 9));
            case 'ssc':
                // 时时彩：生成5个0-9的数字
                return sprintf('%d,%d,%d,%d,%d', rand(0, 9), rand(0, 9), rand(0, 9), rand(0, 9), rand(0, 9));
            case 'ssq':
                // 双色球：6个红球(1-33) + 1个蓝球(1-16)
                $red = [];
                while (count($red) < 6) {
                    $num = rand(1, 33);
                    if (!in_array($num, $red)) {
                        $red[] = $num;
                    }
                }
                sort($red);
                $blue = rand(1, 16);
                return implode(',', $red) . '|' . $blue;
            case 'dlt':
                // 大乐透：5个前区(1-35) + 2个后区(1-12)
                $front = [];
                while (count($front) < 5) {
                    $num = rand(1, 35);
                    if (!in_array($num, $front)) {
                        $front[] = $num;
                    }
                }
                sort($front);
                $back = [];
                while (count($back) < 2) {
                    $num = rand(1, 12);
                    if (!in_array($num, $back)) {
                        $back[] = $num;
                    }
                }
                sort($back);
                return implode(',', $front) . '|' . implode(',', $back);
            default:
                // 默认3位数字
                return sprintf('%d,%d,%d', rand(0, 9), rand(0, 9), rand(0, 9));
        }
    }
    
    /**
     * 生成智能开奖号码（确保平台盈利）
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @return string|null
     */
    private function generateSmartDrawNumbers(string $lotteryCode, string $periodNo): ?string
    {
        try {
            // 使用LotteryBetService获取当期投注统计
            $lotteryBetService = new LotteryBetService();
            $currentPeriodStats = $lotteryBetService->getCurrentPeriodStats($lotteryCode, $periodNo);
            
            // 获取彩种信息计算实际奖金池
            $lotteryType = LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lotteryType) {
                Log::warning("彩种 {$lotteryCode} 不存在，使用随机开奖");
                return null;
            }
            
            $defaultPool = floatval($lotteryType->default_pool ?? 10000);
            $bonusPool = floatval($lotteryType->bonus_pool ?? 0);
            $bonusSystem = floatval($lotteryType->bonus_system ?? 0);
            
            // 计算各玩法的投注和潜在赔付
            $totalBetAmount = 0;
            $potentialPayouts = [];
            
            foreach (['da', 'xiao', 'he'] as $playType) {
                $totalBet = floatval($currentPeriodStats[$playType]['total_bet'] ?? 0);
                $totalPotentialWin = floatval($currentPeriodStats[$playType]['total_potential_win'] ?? 0);
                
                $totalBetAmount += $totalBet;
                $potentialPayouts[$playType] = $totalPotentialWin;
            }
            
            // 如果没有投注，使用随机开奖
            if ($totalBetAmount <= 0) {
                Log::info("期号 {$periodNo} 无投注订单，使用随机开奖");
                return null;
            }
            
            // 计算各开奖结果对奖金池的影响
            $poolAnalysis = [];
            $validResults = [];
            
            foreach (['da', 'xiao', 'he'] as $resultType) {
                $winningPayout = $potentialPayouts[$resultType];
                
                // 计算开奖后的奖金池余额（bonus_system保持不变）
                $remainingPool = $bonusPool - $winningPayout - $bonusSystem;
                
                $poolAnalysis[$resultType] = [
                    'winning_payout' => $winningPayout,
                    'remaining_pool' => $remainingPool,
                    'is_valid' => $remainingPool >= 0
                ];
                
                // 记录满足奖金池余额大于等于0的开奖结果
                if ($remainingPool >= 0) {
                    $validResults[] = $resultType;
                }
                
                Log::info("开奖结果奖金池分析", [
                    'lottery_code' => $lotteryCode,
                    'period_no' => $periodNo,
                    'result_type' => $resultType,
                    'bonus_pool' => $bonusPool,
                    'bonus_system' => $bonusSystem,
                    'winning_payout' => $winningPayout,
                    'remaining_pool' => $remainingPool,
                    'is_valid' => $remainingPool >= 0
                ]);
            }
            
            // 选择开奖结果
            $selectedResult = null;
            
            if (!empty($validResults)) {
                // 如果有满足奖金池余额要求的结果，随机选择一个
                $selectedResult = $validResults[array_rand($validResults)];
                Log::info("选择满足奖金池余额要求的开奖结果: {$selectedResult}", [
                    'remaining_pool' => $poolAnalysis[$selectedResult]['remaining_pool']
                ]);
            } else {
                // 如果没有满足奖金池余额要求的结果，选择余额最大的（可能为负数）
                $maxRemainingPool = max(array_column($poolAnalysis, 'remaining_pool'));
                foreach ($poolAnalysis as $resultType => $analysis) {
                    if ($analysis['remaining_pool'] == $maxRemainingPool) {
                        $selectedResult = $resultType;
                        break;
                    }
                }
                Log::warning("无满足奖金池余额要求的结果，选择余额最大的结果: {$selectedResult}", [
                    'remaining_pool' => $maxRemainingPool
                ]);
            }
            
            // 根据选择的结果生成对应的开奖号码
            return $this->generateNumbersForResult($selectedResult);
            
        } catch (Exception $e) {
            Log::error('智能开奖号码生成失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 根据开奖结果类型生成对应的号码
     * @param string $resultType 结果类型：da/xiao/he
     * @return string
     */
    private function generateNumbersForResult(string $resultType): string
    {
        switch ($resultType) {
            case 'da':
                // 大：和值19-27，生成符合条件的三位数
                return $this->generateNumbersWithSum(19, 27);
            case 'xiao':
                // 小：和值0-8，生成符合条件的三位数
                return $this->generateNumbersWithSum(0, 8);
            case 'he':
                // 和：和值9-18，生成符合条件的三位数
                return $this->generateNumbersWithSum(9, 18);
            default:
                // 默认随机生成
                return sprintf('%d,%d,%d', rand(0, 9), rand(0, 9), rand(0, 9));
        }
    }
    
    /**
     * 生成指定和值范围的三位数号码
     * @param int $minSum 最小和值
     * @param int $maxSum 最大和值
     * @return string
     */
    private function generateNumbersWithSum(int $minSum, int $maxSum): string
    {
        $attempts = 0;
        $maxAttempts = 1000; // 防止无限循环
        
        while ($attempts < $maxAttempts) {
            $num1 = rand(0, 9);
            $num2 = rand(0, 9);
            $num3 = rand(0, 9);
            $sum = $num1 + $num2 + $num3;
            
            if ($sum >= $minSum && $sum <= $maxSum) {
                return sprintf('%d,%d,%d', $num1, $num2, $num3);
            }
            
            $attempts++;
        }
        
        // 如果无法生成符合条件的号码，使用保底方案
        $targetSum = rand($minSum, $maxSum);
        return $this->generateNumbersForTargetSum($targetSum);
    }
    
    /**
     * 生成指定和值的三位数号码（保底方案）
     * @param int $targetSum 目标和值
     * @return string
     */
    private function generateNumbersForTargetSum(int $targetSum): string
    {
        // 确保和值在有效范围内
        $targetSum = max(0, min(27, $targetSum));
        
        if ($targetSum <= 9) {
            // 和值较小时，优先使用较小的数字
            $num1 = rand(0, min(9, $targetSum));
            $remaining = $targetSum - $num1;
            $num2 = rand(0, min(9, $remaining));
            $num3 = $remaining - $num2;
            $num3 = max(0, min(9, $num3));
        } else {
            // 和值较大时，平均分配
            $avg = intval($targetSum / 3);
            $num1 = rand(max(0, $avg - 2), min(9, $avg + 2));
            $remaining = $targetSum - $num1;
            $num2 = rand(max(0, intval($remaining / 2) - 2), min(9, intval($remaining / 2) + 2));
            $num3 = $remaining - $num2;
            $num3 = max(0, min(9, $num3));
        }
        
        return sprintf('%d,%d,%d', $num1, $num2, $num3);
    }
    
    /**
     * 检查订单是否中奖
     * @param BetOrder $order
     * @param string $drawNumbers
     * @return bool
     */
    private function checkWinning(BetOrder $order, string $drawNumbers)
    {
        try {
            // 处理bet_content可能为null或空的情况
            if (empty($order->bet_content)) {
                Log::warning("订单 {$order->order_no} 的bet_content为空");
                return false;
            }
            
            // 根据彩种使用专业的中奖判断服务
            if(in_array($order->lottery_code, ['3d', 'pl3'])){
                    // 3D需要解析JSON格式的bet_content
                    $betContent = is_array($order->bet_content) ? $order->bet_content : json_decode($order->bet_content, true);
                    if (!$betContent) {
                        Log::warning("订单 {$order->order_no} 的bet_content JSON解析失败: " . $order->bet_content);
                        return false;
                    }
                    return $this->checkFc3dWinning($order, $betContent, $drawNumbers);
            }else{
                    // 快彩使用简单字符串格式的bet_content
                    return $this->checkQuickLotteryWinning($order->bet_content, $drawNumbers);
            }
            
        } catch (Exception $e) {
            Log::error("中奖判断异常 - 订单号: {$order->order_no}, 错误: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 福彩3D专业中奖判断
     * @param BetOrder $order
     * @param array $betContent
     * @param string $drawNumbers
     * @return bool
     */
    private function checkFc3dWinning(BetOrder $order, array $betContent, string $drawNumbers)
    {
        try {
            // 引入福彩3D中奖验证服务
            $validationService = new \app\service\fc3d\Fc3dValidationService();
            $result = $validationService->checkWin($betContent['type_key'], $betContent, $drawNumbers);
            
            // 记录中奖详情到订单
            if ($result['is_win']) {
                $order->win_count = $result['win_count'] ?? 1;
            }
            
            return $result['is_win'] ?? false;
            
        } catch (Exception $e) {
            Log::error("福彩3D中奖判断异常 - 订单号: {$order->order_no}, 错误: " . $e->getMessage());
        }
    }
    
    /**
     * 快彩中奖判断逻辑
     * @param string $betContent 投注内容（如：'da', 'xiao', 'he'）
     * @param string $drawNumbers 开奖号码
     * @return bool
     */
    private function checkQuickLotteryWinning(string $betContent, string $drawNumbers): bool
    {
        try {
            $drawArray = explode(',', $drawNumbers);
            $sum = array_sum(array_map('intval', $drawArray));
            
            switch ($betContent) {
                case 'da':
                    // 大：和值 19-27
                    return $sum >= 19 && $sum <= 27;
                case 'xiao':
                    // 小：和值 0-8
                    return $sum >= 0 && $sum <= 8;
                case 'he':
                    // 和：和值 9-18
                    return $sum >= 9 && $sum <= 18;
                default:
                    Log::warning("未知的快彩投注类型: {$betContent}");
                    return false;
            }
            
        } catch (Exception $e) {
            Log::error("快彩中奖判断异常: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 默认简化中奖判断逻辑
     * @param array $betContent
     * @param string $drawNumbers
     * @return bool
     */
    private function checkDefaultWinning(array $betContent, string $drawNumbers): bool
    {
        try {
            $drawArray = explode(',', $drawNumbers);
            
            if (!isset($betContent['type_key'])) {
                return false;
            }
            
            switch ($betContent['type_key']) {
                case 'daxiaohe':
                    // 大小和玩法
                    $sum = array_sum(array_map('intval', $drawArray));
                    $betType = $betContent['numbers'] ?? '';
                    
                    if ($betType === '大' && $sum >= 19) return true;
                    if ($betType === '和' && $sum >= 9 && $sum <= 18) return true;
                    if ($betType === '小' && $sum <= 8) return true;
                    break;
                    
            }
            
            return false;
            
        } catch (Exception $e) {
            Log::error("默认中奖判断异常: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 将中奖订单加入派奖队列
     * @param BetOrder $order
     */
    private function addToPayoutQueue(BetOrder $order)
    {
        try {
            // 使用Redis队列
            $queueData = [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
                'lottery_code' => $order->lottery_code,
                'period_no' => $order->period_no,
                'bet_amount' => $order->bet_amount,
                'created_at' => time()
            ];
            
            // 推送到Redis队列
            Cache::store('redis')->lPush('payout_queue', json_encode($queueData));
            
        } catch (Exception $e) {
            // Redis连接失败时，直接更新订单状态，后续由autopaid处理
            Log::warning("Redis连接失败，订单 {$order->order_no} 将由autopaid直接处理: " . $e->getMessage());
        }
        
        // 减少奖池金额
        $this->reduceBonusPool($order);
        
        // 更新订单状态为待派奖
        $order->status = BetOrder::STATUS_WINNING;
        $order->save();
    }
    
    /**
     * 处理未中奖订单
     * @param BetOrder $order
     */
    private function processLosingOrder(BetOrder $order)
    {
        try {
            // 处理未中奖返佣
            BetOrderService::processBetRebate(
                $order->user_id,
                $order->total_amount,
                [$order->order_no],
                'nowin'
            );
        } catch (Exception $e) {
            // Redis连接失败时记录日志，但不影响订单状态更新
            Log::warning("处理订单 {$order->order_no} 返佣失败: " . $e->getMessage());
        }
        
        // 更新订单状态为未中奖
        $order->status = BetOrder::STATUS_LOSING; //reset
        $order->save();
    }
    
    /**
     * 减少奖池金额
     * @param BetOrder $order
     */
    private function reduceBonusPool(BetOrder $order)
    {
        try {
            // 获取彩种信息
            $lotteryType = LotteryType::where('type_code', $order->lottery_code)->find();
            if (!$lotteryType) {
                Log::warning("订单 {$order->order_no} 对应的彩种 {$order->lottery_code} 不存在");
                return;
            }
            
            // 计算中奖金额（投注金额 * 赔率 * 倍数）
            $winAmount = ($order->bet_amount) * $order->odds * ($order->multiple ?? 1);
            
            // 获取当前奖池金额
            $currentBonusPool = floatval($lotteryType->bonus_pool ?? 0);
            
            // 减少奖池金额
            $newBonusPool = max(0, $currentBonusPool - $winAmount);
            
            // 更新彩种表的bonus_pool字段
            $lotteryType->bonus_pool = $newBonusPool;
            $lotteryType->save();
            
            Log::info("订单 {$order->order_no} 中奖，奖池金额从 {$currentBonusPool} 减少到 {$newBonusPool}，减少金额: {$winAmount}");
            
        } catch (Exception $e) {
            Log::error("减少奖池金额失败，订单: {$order->order_no}，错误: " . $e->getMessage());
        }
    }
}