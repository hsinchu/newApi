<?php

namespace app\service;

use app\common\model\BetOrder;
use app\common\model\LotteryType;
use app\common\model\LotteryBonus;
use think\facade\Log;

/**
 * 彩票投注服务类
 * 实现新的投注限额计算逻辑
 */
class LotteryBetService
{
    
    // 平台服务费率
    private $service_fee_rate = 0.2; // 20%
    
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
            
            // 获取当期所有玩法的投注统计
            $currentPeriodStats = $this->getCurrentPeriodStats($lottery_code, $period_no);
            
            // 获取用户在当期的投注统计
            $userPeriodStats = $this->getUserPeriodStats($lottery_code, $period_no, $user_id, $bet_type);
            
            // 计算系统最大投注额（确保平台盈利20%）
             $systemMaxBet = $this->calculateSystemMaxBet($actualBonusPool, $currentPeriodStats, $bet_type, $odds);
            
            // 计算用户最大投注额（确保用户中奖不超过其净投注额）
            $userMaxBet = $this->calculateUserMaxBet($userPeriodStats, $bet_type, $odds);
            
            // 取较小值作为最终限制
            $finalMaxBet = min($systemMaxBet, $userMaxBet);
            
            // 确保不为负数
            $finalMaxBet = max(0, $finalMaxBet);
            
            return [
                 'status' => 'success',
                 'max_bet_amount' => round($finalMaxBet, 2),
                 'system_max_bet' => round($systemMaxBet, 2),
                 'user_max_bet' => round($userMaxBet, 2),
                 'current_bonus_pool' => round($actualBonusPool, 2),
                 'user_total_bet' => round($userPeriodStats['total_bet'], 2),
                 'odds' => $odds,
                 'period_stats' => $currentPeriodStats
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
     * @return float
     */
    protected function calculateSystemMaxBet($actualBonusPool, $currentPeriodStats, $newBetType, $odds)
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
            
            // 检查是否至少有一个开奖结果能保证平台20%盈利
            $hasValidResult = false;
            
            foreach (['da', 'xiao', 'he'] as $resultType) {
                $platformIncome = $newTotalBetAmount;
                $platformPayout = $newTotalWinAmounts[$resultType];
                $platformProfit = $platformIncome - $platformPayout;
                $profitRate = $platformIncome > 0 ? $platformProfit / $platformIncome : 0;
                
                if ($profitRate >= 0.2) {
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
     * @return float
     */
    protected function calculateUserMaxBet($userPeriodStats, $newBetType, $odds)
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
            return 999999;
        }
        
        // 计算用户在新投注类型上的当前投注额
        $currentBetTypeAmount = $userPeriodStats[$newBetType]['total_bet'] ?? 0;
        
        // 用户中奖金额不能超过其总投注额扣除服务费后的金额
        // 考虑新投注后的情况：
        // 设新投注额为x，则总投注变为 userTotalBet + x
        // 如果新投注类型中奖，中奖金额为 (currentBetTypeAmount + x) * odds
        // 净收益为 (currentBetTypeAmount + x) * odds - (userTotalBet + x)
        // 要求净收益不超过总投注的(1-服务费率)倍：
        // (currentBetTypeAmount + x) * odds - (userTotalBet + x) <= (userTotalBet + x) * (1 - service_fee_rate)
        
        $netRate = 1 - $this->service_fee_rate; // 0.8
        
        // 整理公式：
        // (currentBetTypeAmount + x) * odds <= (userTotalBet + x) * (1 + netRate)
        // currentBetTypeAmount * odds + x * odds <= userTotalBet * (1 + netRate) + x * (1 + netRate)
        // x * (odds - 1 - netRate) <= userTotalBet * (1 + netRate) - currentBetTypeAmount * odds
        
        $coefficient = $odds - 1 - $netRate;
        $rightSide = $userTotalBet * (1 + $netRate) - $currentBetTypeAmount * $odds;
        
        if ($coefficient <= 0) {
            // 如果系数小于等于0，说明赔率过低或其他异常情况
            return 999999;
        }
        
        $maxBet = $rightSide / $coefficient;
        
        return max(0, $maxBet);
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
            $new_bonus_pool = $current_bonus_pool + $pool_amount;
            
            // 更新彩种表的bonus_pool字段
            $lotteryType->bonus_pool = $new_bonus_pool;
            $lotteryType->bonus_system = $new_bonus_pool * $this->service_fee_rate;  // 计算平台服务费率
            $lotteryType->save();
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('更新奖金池失败: ' . $e->getMessage());
            return false;
        }
    }

}

