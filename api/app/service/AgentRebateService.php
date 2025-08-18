<?php

namespace app\service;

use app\common\model\UserMoneyLog;
use app\common\model\AgentRebateConfig;
use app\common\model\BetOrder;
use app\common\model\User;

/**
 * 代理商返水服务类
 */
class AgentRebateService
{
    /**
     * 根据彩种分类统计投注数据
     * @param int $agentId 代理商ID
     * @param int $startTime 开始时间
     * @param AgentRebateConfig $config 代理商配置
     * @return array
     */
    public static function getBetStatsByCategory($agentId, $startTime, $config)
    {
        // 获取该代理商下级用户ID列表
        $userIds = User::where('parent_id', $agentId)->column('id');
        if (empty($userIds)) {
            return [];
        }
        
        // 查询投注订单，关联彩种表获取分类信息
        $query = BetOrder::alias('bo')
            ->leftJoin('lottery_type lt', 'bo.lottery_type_id = lt.id')
            ->where('bo.user_id', 'in', $userIds)
            ->where('bo.create_time', '>', $startTime)
            ->where('bo.status', 'in', ['PAID', 'LOSING']) // 只统计已结算的订单
            ->where('lt.category', '<>', '') // 排除category为空的记录
            ->whereNotNull('lt.category') // 排除category为NULL的记录
            ->field([
                'lt.category',
                'COUNT(*) as record_count',
                'SUM(bo.total_amount) as total_bet_amount',
                'SUM(bo.win_amount) as total_win_amount'
            ])
            ->group('lt.category');
        
        $results = $query->select()->toArray();
        
        // 彩种分类映射
        $categoryMap = [
            'SPORTS' => '竞彩',
            'WELFARE' => '福彩', 
            'SPORTS_SINGLE' => '单场',
            'QUICK' => '快彩'
        ];
        
        $categoryStats = [];
        
        foreach ($results as $result) {
            $category = $result['category'];
            
            // 跳过category为空的记录
            if (empty($category)) {
                continue;
            }
            
            $betAmount = $result['total_bet_amount'];
            $winAmount = $result['total_win_amount'];
            $noWinAmount = $betAmount - $winAmount;
            $profitLoss = $betAmount - $winAmount;
            
            // 计算各种返水比例和金额
            $noWinRate = 0;
            $betRate = 0;
            $noWinRebateAmount = 0;
            $betRebateAmount = 0;
            
            switch ($category) {
                case 'SPORTS':
                    $noWinRate = $config->sports_no_win_rate;
                    $betRate = $config->sports_bet_rate;
                    break;
                case 'WELFARE':
                    $noWinRate = $config->welfare_no_win_rate;
                    $betRate = $config->welfare_bet_rate;
                    break;
                case 'SPORTS_SINGLE':
                    $noWinRate = $config->sports_single_no_win_rate;
                    $betRate = $config->sports_single_bet_rate;
                    break;
                case 'QUICK':
                    $noWinRate = $config->quick_no_win_rate;
                    $betRate = $config->quick_bet_rate;
                    break;
            }
            
            // 根据返水类型计算返水金额
            if ($config->rebate_type === 'profit') {
                // 盈利模式：按盈利金额计算两种返水，只有盈利时才计算
                if ($profitLoss > 0) {
                    $noWinRebateAmount = $profitLoss * ($noWinRate / 100);
                    $betRebateAmount = $profitLoss * ($betRate / 100);
                }
            } elseif ($config->rebate_type === 'bet') {
                // 投注模式：按投注金额计算两种返水
                $noWinRebateAmount = $betAmount * ($noWinRate / 100);
                $betRebateAmount = $betAmount * ($betRate / 100);
            }
            
            // 总返水金额
            $rebateAmount = $noWinRebateAmount + $betRebateAmount;
            
            $categoryStats[] = [
                'category' => $category,
                'category_text' => $categoryMap[$category] ?? $category,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'no_win_amount' => $noWinAmount,
                'profit_loss' => $profitLoss,
                'rebate_amount' => $rebateAmount,
                'rebate_type' => $config->rebate_type,
                'no_win_rate' => $noWinRate,
                'bet_rate' => $betRate,
                'no_win_rebate_amount' => $noWinRebateAmount,
                'bet_rebate_amount' => $betRebateAmount,
                'record_count' => $result['record_count']
            ];
        }
        
        return $categoryStats;
    }

    /**
     * 获取代理商已发放的佣金金额
     * @param int $agentId 代理商ID
     * @param int $startTime 开始时间
     * @return int 佣金金额（分）
     */
    public static function getCommissionAmount($agentId, $startTime)
    {
        // 获取该代理商下级用户ID列表
        $userIds = User::where('parent_id', $agentId)->column('id');
        if (empty($userIds)) {
            return 0;
        }

        array_push($userIds, $agentId); //代理商自己也要算进去


        // 查询fa_user_money_log表中type=COMMISSION_ADD的记录
        $commissionAmount = UserMoneyLog::where('user_id', 'in', $userIds)
            ->where('type', 'COMMISSION_ADD')
            ->where('create_time', '>=', $startTime)
            ->sum('money');

        return $commissionAmount ?: 0;
    }

    /**
     * 检查是否应该结算
     * @param AgentRebateConfig $config 代理商配置
     * @return bool
     */
    public static function shouldSettle(AgentRebateConfig $config)
    {
        $now = time();
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        $settlementTime = $config->settlement_time;
        
        // 检查当前时间是否已过结算时间
        if ($currentTime < $settlementTime) {
            return false;
        }
        
        // 获取最后一次结算记录
        $lastSettlement = \app\common\model\AgentRebateSettlement::where('agent_id', $config->agent_id)
            ->where('settlement_status', \app\common\model\AgentRebateSettlement::STATUS_COMPLETED)
            ->order('settlement_time', 'desc')
            ->find();
        
        if (!$lastSettlement) {
            // 从未结算过，可以结算
            return true;
        }
        
        $lastSettlementDate = date('Y-m-d', $lastSettlement->settlement_time);
        $daysSinceLastSettlement = (strtotime($currentDate) - strtotime($lastSettlementDate)) / 86400;
        
        // 根据结算周期判断
        $settlementCycle = (int)$config->settlement_cycle;
        return $daysSinceLastSettlement >= $settlementCycle;
    }

    /**
     * 获取待结算返水数据
     * @param int $agentId 代理商ID
     * @param AgentRebateConfig $config 代理商配置
     * @return array
     */
    public static function getPendingRebateData($agentId, AgentRebateConfig $config)
    {
        // 获取该代理商最后一次发放时间
        $lastSettlement = \app\common\model\AgentRebateSettlement::where('agent_id', $agentId)
            ->where('settlement_status', \app\common\model\AgentRebateSettlement::STATUS_COMPLETED)
            ->order('settlement_time', 'desc')
            ->find();
        
        // 确定统计开始时间
        $startTime = $lastSettlement ? $lastSettlement->settlement_time : 0;
        
        // 获取该代理商下级用户的投注数据
        $categoryStats = self::getBetStatsByCategory($agentId, $startTime, $config);
        
        // 获取该代理商已发放的佣金金额
        $totalCommissionAmount = self::getCommissionAmount($agentId, $startTime);
        
        // 计算总返水金额
        $totalRebate = 0;
        $totalBetAmount = 0;
        $totalWinAmount = 0;
        
        foreach ($categoryStats as $stat) {
            $totalBetAmount += $stat['bet_amount'];
            $totalWinAmount += $stat['win_amount'];
            $totalRebate += $stat['rebate_amount'];
        }
        
        return [
            'categoryStats' => $categoryStats,
            'totalRebate' => $totalRebate,
            'totalBetAmount' => $totalBetAmount,
            'totalWinAmount' => $totalWinAmount,
            'totalCommissionAmount' => $totalCommissionAmount,
            'startTime' => $startTime
        ];
    }
}