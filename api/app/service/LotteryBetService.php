<?php

namespace app\service;

use app\common\model\BetOrder;
use app\common\model\LotteryType;
use app\common\model\LotteryBonus;
use app\common\model\User;
use app\common\model\UserLevel;
use think\facade\Log;

/**
 * 彩票投注服务类
 * 实现新的投注限额计算逻辑
 */
class LotteryBetService
{
    
    // 平台服务费率将从lottery_type表的bonus_system_rate字段动态获取
    
    /**
     * 计算最大投注限额
     * @param string $lottery_code 彩种代码
     * @param string $period_no 期号
     * @param string $bet_type 投注类型（da/xiao/he）
     * @param float $odds 赔率
     * @param int $user_id 用户ID
     * @return array
     */
    public function calculateMaxBetAmount($lottery_code, $period_no, $bet_type, $odds, $user_id)
    {
        try {
            // 获取彩种信息
            $lotteryType = LotteryType::where('type_code', $lottery_code)->find();
            if (!$lotteryType) {
                return ['status' => 'error', 'message' => '彩种不存在'];
            }
            
            // 计算实际奖金池：jjc = default_pool + bonus_pool - bonus_system
            $defaultPool = floatval($lotteryType->default_pool ?? 10000); // 使用default_pool字段
            $bonusPool = floatval($lotteryType->bonus_pool ?? 0);
            $bonusSystem = floatval($lotteryType->bonus_system ?? 0);
            $actualBonusPool = $defaultPool + $bonusPool - $bonusSystem;
            
            // 调试信息
            Log::info('奖金池计算', [
                'default_pool' => $defaultPool,
                'bonus_pool' => $bonusPool, 
                'bonus_system' => $bonusSystem,
                'actual_bonus_pool' => $actualBonusPool
            ]);
            
            // 获取当期所有玩法的投注统计
            $currentPeriodStats = $this->getCurrentPeriodStats($lottery_code, $period_no);
            
            // 获取用户在当期的投注统计
            $userPeriodStats = $this->getUserPeriodStats($lottery_code, $period_no, $user_id, $bet_type);
            
            // 计算系统最大投注额（确保平台盈利）
             $systemMaxBet = $this->calculateSystemMaxBet($actualBonusPool, $currentPeriodStats, $bet_type, $odds, $lotteryType);
            
            // 计算用户最大投注额（确保用户中奖不超过其净投注额）
            $userMaxBet = $this->calculateUserMaxBet($userPeriodStats, $bet_type, $odds, $lotteryType);
            
            // 获取彩种配置的投注限制
            $lotteryMinBet = $lotteryType->min_bet_amount ?? 200; // 转换为元
            $lotteryMaxBet = $lotteryType->max_bet_amount ?? 1000000; // 转换为元
            $lotteryDailyLimit = $lotteryType->daily_limit ?? 0; // 转换为元
            
            // 获取玩法限额信息
            $playLimits = $this->getPlayTypeLimits($lotteryType->id, $bet_type);
            $playMinBet = $playLimits['min_price'] ?? 0;
            $playMaxBet = $playLimits['max_price'] ?? 0;
            
            // 获取用户今日已投注金额
            $userDailyBet = $this->getUserDailyBetAmount($lottery_code, $user_id);
            
            // 计算日限额剩余额度
            $dailyRemaining = $lotteryDailyLimit > 0 ? max(0, $lotteryDailyLimit - $userDailyBet) : PHP_FLOAT_MAX;
            
            // 应用层级限额规则：最低取高的，最高取低的
            $effectiveMinBet = $this->getEffectiveMinBetAmount($playMinBet, $lotteryMinBet);
            $effectiveMaxBet = $this->getEffectiveMaxBetAmount($playMaxBet, $lotteryMaxBet);
            
            // 最终最大投注额：取系统计算、用户限制、有效最大限额、日限额中的最小值
            $finalSystemMaxBet = min($systemMaxBet, $userMaxBet, $effectiveMaxBet, $dailyRemaining);
            
            // 确保最大投注额不小于最小投注额
            $finalMaxBet = max($effectiveMinBet, $finalSystemMaxBet);
            
            // 如果计算结果小于最小投注额，则返回0（不允许投注）
            if ($finalMaxBet < $effectiveMinBet) {
                $finalMaxBet = 0;
            }
            
            // 确保不为负数
            $finalMaxBet = max(0, $finalMaxBet);

            if($lotteryType['max_pool_rate'] > 0){
                $finalMaxBet = $finalMaxBet * $lotteryType['max_pool_rate'] / 100;
                $systemMaxBet = $systemMaxBet * $lotteryType['max_pool_rate'] / 100;
                $userMaxBet = $userMaxBet * $lotteryType['max_pool_rate'] / 100;
                $actualBonusPool = $actualBonusPool * $lotteryType['max_pool_rate'] / 100;
            }
            
            // 获取用户等级信息并应用bet_percentage
            if ($user_id > 0) {
                $user = User::find($user_id);
                if ($user && $user->level_id) {
                    $userLevel = UserLevel::find($user->level_id);
                    if ($userLevel && $userLevel->bet_percentage > 0) {
                        $betPercentage = floatval($userLevel->bet_percentage) / 100;
                        $finalMaxBet = $finalMaxBet * $betPercentage;
                        $systemMaxBet = $systemMaxBet * $betPercentage;
                        $userMaxBet = $userMaxBet * $betPercentage;
                        
                        // 记录等级限制应用日志
                        Log::info('应用用户等级投注限制', [
                            'user_id' => $user_id,
                            'level_id' => $user->level_id,
                            'level_name' => $userLevel->name,
                            'bet_percentage' => $userLevel->bet_percentage,
                            'original_max_bet' => round($finalMaxBet / $betPercentage, 2),
                            'final_max_bet' => round($finalMaxBet, 2)
                        ]);
                    }
                }
            }
            
            return [
                 'status' => 'success',
                 'max_bet_amount' => round($finalMaxBet, 2),
                 'system_max_bet' => round($systemMaxBet, 2),
                 'user_max_bet' => round($userMaxBet, 2),
                 'current_bonus_pool' => round($actualBonusPool, 2),
                 'user_total_bet' => round($userPeriodStats['total_bet'], 2),
                 'odds' => $odds,
                 'period_stats' => $currentPeriodStats,
                 'lottery_limits' => [
                     'min_bet_amount' => round($lotteryMinBet, 2),
                     'max_bet_amount' => round($lotteryMaxBet, 2),
                     'daily_limit' => round($lotteryDailyLimit, 2),
                     'user_daily_bet' => round($userDailyBet, 2),
                     'daily_remaining' => round($dailyRemaining == PHP_FLOAT_MAX ? 0 : $dailyRemaining, 2),
                     'effective_min_bet' => round($effectiveMinBet, 2),
                     'effective_max_bet' => round($effectiveMaxBet, 2)
                 ]
             ];
            
        } catch (\Exception $e) {
            Log::error('计算最大投注额失败: ' . $e->getMessage());
            return ['status' => 'error', 'message' => '计算失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 获取当期所有玩法的投注统计
     * @param string $lottery_code 彩种代码
     * @param string $period_no 期号
     * @return array
     */
    public function getCurrentPeriodStats($lottery_code, $period_no)
    {
        $stats = [
            'da' => ['total_bet' => 0, 'total_potential_win' => 0],
            'xiao' => ['total_bet' => 0, 'total_potential_win' => 0],
            'he' => ['total_bet' => 0, 'total_potential_win' => 0]
        ];
        
        // 查询当期所有投注订单
        $orders = BetOrder::where('lottery_code', $lottery_code)
            ->where('period_no', $period_no)
            ->where('status', 'in', ['PENDING', 'CONFIRMED']) // 待开奖和已中奖
            ->select();
            
        foreach ($orders as $order) {
            $playType = $order->bet_content;
            $betAmount = floatval($order->bet_amount); // 模型访问器已自动转换为元
            $odds = floatval($order->odds ?? 1);
            
            if (isset($stats[$playType])) {
                $stats[$playType]['total_bet'] += $betAmount;
                $stats[$playType]['total_potential_win'] += $betAmount * $odds;
            }
        }
        
        return $stats;
    }
    
    /**
     * 获取用户在当期的投注统计
     * @param string $lottery_code 彩种代码
     * @param string $period_no 期号
     * @param int $user_id 用户ID
     * @param string $bet_type 投注类型
     * @return array
     */
    protected function getUserPeriodStats($lottery_code, $period_no, $user_id, $bet_type)
    {
        $stats = [
            'da' => ['total_bet' => 0, 'total_potential_win' => 0],
            'xiao' => ['total_bet' => 0, 'total_potential_win' => 0],
            'he' => ['total_bet' => 0, 'total_potential_win' => 0],
            'current_bet_type_total' => 0 // 当前玩法的总投注
        ];
        
        // 查询用户当期所有投注订单
        $orders = BetOrder::where('lottery_code', $lottery_code)
            ->where('period_no', $period_no)
            ->where('user_id', $user_id)
            ->where('status', 'in', ['PENDING', 'CONFIRMED'])
            ->select();
            
        foreach ($orders as $order) {
            // 解析bet_content，处理JSON格式或直接字符串
            $playType = $order->bet_content;
            
            // 如果是JSON格式，尝试解析
            if (is_string($playType) && (strpos($playType, '{') === 0 || strpos($playType, '[') === 0)) {
                $decoded = json_decode($playType, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (isset($decoded['type_key'])) {
                        $playType = $decoded['type_key'];
                    } elseif (isset($decoded['numbers'])) {
                        $playType = $decoded['numbers'];
                    }
                }
            }
            
            // 处理特殊的bet_content值，如'daxiaohe'
            if (!in_array($playType, ['da', 'xiao', 'he'])) {
                // 根据bet_type_name或bet_type映射到标准玩法
                $betTypeName = $order->bet_type_name ?? $order->bet_type ?? '';
                if (strpos($betTypeName, '大') !== false || strpos($playType, 'da') !== false) {
                    $playType = 'da';
                } elseif (strpos($betTypeName, '小') !== false || strpos($playType, 'xiao') !== false) {
                    $playType = 'xiao';
                } elseif (strpos($betTypeName, '和') !== false || strpos($playType, 'he') !== false) {
                    $playType = 'he';
                }
            }
            
            $betAmount = floatval($order->bet_amount); // 模型访问器已自动转换为元
            $odds = floatval($order->odds ?? 1);
            
            if (isset($stats[$playType])) {
                $stats[$playType]['total_bet'] += $betAmount;
                $stats[$playType]['total_potential_win'] += $betAmount * $odds;
            }
            
            // 如果是当前投注类型，累加到current_bet_type_total
            if ($playType === $bet_type) {
                $stats['current_bet_type_total'] += $betAmount;
            }
        }
        
        // 为了兼容原有代码，保留total_bet字段
        $stats['total_bet'] = $stats['current_bet_type_total'];
        
        return $stats;
    }
    
    /**
     * 计算各玩法的潜在中奖金额（加入新投注后）
     * @param array $currentStats 当前统计
     * @param string $newBetType 新投注类型
     * @param float $newOdds 新投注赔率
     * @param float $newBetAmount 新投注金额
     * @return array
     */
    private function calculatePotentialWinnings($currentStats, $newBetType, $newOdds, $newBetAmount)
    {
        $winnings = [];
        
        foreach (['da', 'xiao', 'he'] as $playType) {
            $totalWin = $currentStats[$playType]['total_potential_win'];
            
            // 如果新投注是这个玩法，加上新的潜在中奖金额
            if ($playType === $newBetType) {
                $totalWin += $newBetAmount * $newOdds;
            }
            
            $winnings[$playType] = $totalWin;
        }
        
        return $winnings;
    }
    
    /**
     * 计算系统最大投注额（确保平台盈利）
     * @param float $actualBonusPool 实际奖金池
     * @param array $currentPeriodStats 当期投注统计
     * @param string $newBetType 新投注类型
     * @param float $odds 新投注赔率
     * @param object $lotteryType 彩种信息
     * @return float
     */
    protected function calculateSystemMaxBet($actualBonusPool, $currentPeriodStats, $newBetType, $odds, $lotteryType)
    {
        // 根据用户需求：先从奖金池扣除当前投注玩法的潜在赔付，基于剩余奖金池计算最大投注额
        
        // 计算当前可用奖金池（扣减当前投注玩法的潜在赔付）
        $availablePool = $actualBonusPool;
        
        // 扣减当前投注玩法的潜在赔付
        if (isset($currentPeriodStats[$newBetType])) {
            $availablePool -= floatval($currentPeriodStats[$newBetType]['total_potential_win']);
        }
        
        // 确保可用奖金池不为负数
        $availablePool = max(0, $availablePool);
        
        // 验证赔率有效性
        $odds = floatval($odds);
        if ($odds <= 0) {
            return 0; // 赔率无效，返回0
        }
        
        // 基于剩余奖金池计算基础最大投注额
        $basicMaxBet = $availablePool / $odds;
        
        // 计算当前各玩法的总投注额和潜在中奖总额
        $totalBetAmount = 0;
        $totalWinAmounts = [];
        
        foreach (['da', 'xiao', 'he'] as $playType) {
            $totalBetAmount += floatval($currentPeriodStats[$playType]['total_bet']);
            $totalWinAmounts[$playType] = floatval($currentPeriodStats[$playType]['total_potential_win']);
        }
        
        // 调试信息
        Log::info('系统最大投注额计算', [
            'actual_bonus_pool' => $actualBonusPool,
            'available_pool' => $availablePool,
            'new_bet_type' => $newBetType,
            'odds' => $odds,
            'basic_max_bet' => $basicMaxBet,
            'total_bet_amount' => $totalBetAmount,
            'total_win_amounts' => $totalWinAmounts
        ]);
        
        // 使用二分查找法找到满足条件的最大投注额
        $minBet = 0;
        $maxBet = $basicMaxBet;
        $tolerance = 0.01; // 精度容差
        
        while ($maxBet - $minBet > $tolerance) {
            $testBet = ($minBet + $maxBet) / 2;
            
            // 计算加入测试投注额后的情况
            $newTotalBetAmount = $totalBetAmount + $testBet;
            $newTotalWinAmounts = $totalWinAmounts;
            $newTotalWinAmounts[$newBetType] += $testBet * $odds;
            
            // 获取彩种的服务费率
            $serviceFeeRate = floatval($lotteryType->bonus_system_rate ?? 20) / 100; // 转换为小数
            
            // 检查是否至少有一个开奖结果能保证平台盈利
            $hasValidResult = false;
            
            foreach (['da', 'xiao', 'he'] as $resultType) {
                $platformIncome = $newTotalBetAmount;
                $platformPayout = $newTotalWinAmounts[$resultType];
                $platformProfit = $platformIncome - $platformPayout;
                $profitRate = $platformIncome > 0 ? $platformProfit / $platformIncome : 0;
                if ($profitRate >= $serviceFeeRate) {
                    $hasValidResult = true;
                    break; // 找到一个满足条件的结果就足够了
                }
            }
            
            if ($hasValidResult) {
                $minBet = $testBet; // 这个投注额可行，尝试更大的
            } else {
                $maxBet = $testBet; // 这个投注额不可行，尝试更小的
            }
        }
        
        return max(0, $minBet);
    }
    
    /**
     * 计算用户最大投注额
     * @param array $userPeriodStats 用户当期投注统计
     * @param string $newBetType 新投注类型
     * @param float $odds 投注赔率
     * @param object $lotteryType 彩种信息
     * @return float
     */
    protected function calculateUserMaxBet($userPeriodStats, $newBetType, $odds, $lotteryType)
    {
        // 计算用户在当期的总投注额
        $userTotalBet = 0;
        foreach (['da', 'xiao', 'he'] as $playType) {
            if (isset($userPeriodStats[$playType])) {
                $userTotalBet += $userPeriodStats[$playType]['total_bet'];
            }
        }
        // 如果用户没有投注历史，允许基础投注额
        if ($userTotalBet <= 0) {
            // 设置基础投注限额
            return 9999;
        }
        
        // 计算用户在新投注类型上的当前投注额
        $currentBetTypeAmount = $userPeriodStats[$newBetType]['total_bet'] ?? 0;
        
        // 用户中奖金额不能超过其总投注额扣除服务费后的金额
        // 考虑新投注后的情况：
        // 设新投注额为x，则总投注变为 userTotalBet + x
        // 如果新投注类型中奖，中奖金额为 (currentBetTypeAmount + x) * odds
        // 净收益为 (currentBetTypeAmount + x) * odds - (userTotalBet + x)
        // 要求净收益不超过总投注的(1-服务费率)倍：
        // (currentBetTypeAmount + x) * odds - (userTotalBet + x) <= (userTotalBet + x) * (1 - bonus_system_rate)
        
        // 获取彩种的服务费率
        $serviceFeeRate = floatval($lotteryType->bonus_system_rate ?? 20) / 100; // 转换为小数
        $netRate = 1 - $serviceFeeRate;
        
        // 整理公式：
        // (currentBetTypeAmount + x) * odds <= (userTotalBet + x) * (1 + netRate)
        // currentBetTypeAmount * odds + x * odds <= userTotalBet * (1 + netRate) + x * (1 + netRate)
        // x * (odds - 1 - netRate) <= userTotalBet * (1 + netRate) - currentBetTypeAmount * odds
        
        $coefficient = $odds - 1 - $netRate;
        $rightSide = $userTotalBet * (1 + $netRate) - $currentBetTypeAmount * $odds;
        
        if ($coefficient <= 0) {
            // 如果系数小于等于0，说明赔率过低或其他异常情况
            return 9999;
        }
        
        $maxBet = $rightSide / $coefficient;
        
        return max(0, $maxBet);
    }
    
    /**
     * 获取玩法类型的限额信息
     * @param int $lotteryId 彩种ID
     * @param string $betType 投注类型
     * @return array
     */
    private function getPlayTypeLimits(int $lotteryId, string $betType): array
    {
        try {
            $bonusRecord = LotteryBonus::where('lottery_id', $lotteryId)
                ->where('type_key', $betType)
                ->where('status', 1)
                ->find();
                
            if (!$bonusRecord) {
                return ['min_price' => 0, 'max_price' => 0];
            }
            
            return [
                'min_price' => (float)$bonusRecord->min_price,
                'max_price' => (float)$bonusRecord->max_price
            ];
        } catch (\Exception $e) {
            Log::error('获取玩法限额失败: ' . $e->getMessage());
            return ['min_price' => 0, 'max_price' => 0];
        }
    }
    
    /**
     * 获取有效的最小投注金额
     * 规则：取玩法限额和彩种限额中的较高值（更严格的最小限制）
     * 如果某个限额为0，则表示不限制，使用另一个限额
     * 
     * @param float $playMinBet 玩法最小限额
     * @param float $lotteryMinBet 彩种最小限额
     * @return float 有效的最小投注金额
     */
    private function getEffectiveMinBetAmount(float $playMinBet, float $lotteryMinBet): float
    {
        // 如果玩法限额为0，使用彩种限额
        if ($playMinBet <= 0) {
            return $lotteryMinBet > 0 ? $lotteryMinBet : 2.0;
        }
        
        // 如果彩种限额为0，使用玩法限额
        if ($lotteryMinBet <= 0) {
            return $playMinBet;
        }
        
        // 两个都有值时，取较高的（更严格的限制）
        return max($playMinBet, $lotteryMinBet);
    }
    
    /**
     * 获取有效的最大投注金额
     * 规则：取玩法限额和彩种限额中的较低值（更严格的最大限制）
     * 如果某个限额为0，则表示不限制，使用另一个限额
     * 
     * @param float $playMaxBet 玩法最大限额
     * @param float $lotteryMaxBet 彩种最大限额
     * @return float 有效的最大投注金额
     */
    private function getEffectiveMaxBetAmount(float $playMaxBet, float $lotteryMaxBet): float
    {
        // 如果玩法限额为0，使用彩种限额
        if ($playMaxBet <= 0) {
            return $lotteryMaxBet > 0 ? $lotteryMaxBet : 10000.0;
        }
        
        // 如果彩种限额为0，使用玩法限额
        if ($lotteryMaxBet <= 0) {
            return $playMaxBet;
        }
        
        // 两个都有值时，取较低的（更严格的限制）
        return min($playMaxBet, $lotteryMaxBet);
    }
    
    /**
     * 获取用户今日已投注金额
     * @param string $lottery_code 彩种代码
     * @param int $user_id 用户ID
     * @return float
     */
    protected function getUserDailyBetAmount($lottery_code, $user_id)
    {
        try {
            // 获取今日开始和结束时间戳
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayEnd = strtotime(date('Y-m-d 23:59:59'));
            
            // 获取彩种信息
            $lotteryType = LotteryType::where('type_code', $lottery_code)->find();
            if (!$lotteryType) {
                return 0;
            }
            
            // 查询用户今日在该彩种的投注总额
            $dailyBetAmount = BetOrder::where('user_id', $user_id)
                ->where('lottery_type_id', $lotteryType->id)
                ->where('create_time', 'between', [$todayStart, $todayEnd])
                ->where('status', 'in', ['pending', 'won', 'lost']) // 排除已取消的订单
                ->sum('total_amount');
            
            // 转换为元（数据库存储为分）
            return floatval($dailyBetAmount) / 100;
            
        } catch (\Exception $e) {
            Log::error('获取用户今日投注金额失败: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 更新奖金池（投注成功后调用）
     * @param string $lottery_code 彩种代码
     * @param float $bet_amount 投注金额（元）
     * @return bool
     */
    public function updateBonusPool($lottery_code, $bet_amount)
    {
        try {
            // 获取彩种信息
            $lotteryType = LotteryType::where('type_code', $lottery_code)->find();
            if (!$lotteryType) {
                throw new \Exception('彩种不存在');
            }
            
            $pool_amount = $bet_amount;
            $current_bonus_pool = floatval($lotteryType->bonus_pool ?? 0);
            $current_bonus_system = floatval($lotteryType->bonus_system ?? 0);
            $current_default_pool = floatval($lotteryType->default_pool ?? 0);
            $new_bonus_pool = $current_bonus_pool + $pool_amount;
            
            // 获取彩种的服务费率
            $serviceFeeRate = floatval($lotteryType->bonus_system_rate ?? 20) / 100; // 转换为小数
            
            // 更新彩种表的bonus_pool字段
            $lotteryType->bonus_pool = $new_bonus_pool;
            $lotteryType->bonus_system = $current_bonus_system + ($bet_amount * $serviceFeeRate);  // 累加投注额的服务费
            $lotteryType->save();
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('更新奖金池失败: ' . $e->getMessage());
            return false;
        }
    }

}

