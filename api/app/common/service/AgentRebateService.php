<?php

namespace app\common\service;

use think\facade\Db;
use app\common\model\AgentRebateConfig;
use app\common\model\AgentRebateRecord;
use app\common\model\AgentRebateSettlement;
use app\common\model\BetOrder;
use app\common\model\LotteryType;
use app\common\model\User;
use Exception;

/**
 * 代理商返水服务类
 */
class AgentRebateService
{
    /**
     * 计算代理商返水
     * @param int $agentId 代理商ID
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     * @throws Exception
     */
    public static function calculateAgentRebate(int $agentId, string $startDate, string $endDate): array
    {
        // 获取代理商配置
        $config = AgentRebateConfig::getAgentConfig($agentId);
        if (!$config) {
            throw new Exception('代理商返水配置不存在或未启用');
        }

        // 验证代理商是否存在
        $agent = User::where('id', $agentId)->where('is_agent', 1)->find();
        if (!$agent) {
            throw new Exception('代理商不存在');
        }

        // 获取彩种分类数据
        $lotteryTypes = LotteryType::column('category', 'type_code');
        
        // 按彩种分类统计投注数据
        $categoryStats = self::getBetStatsByCategory($agentId, $startDate, $endDate, $lotteryTypes);
        
        $rebateRecords = [];
        $totalRebate = 0;
        
        foreach ($categoryStats as $category => $stats) {
            if ($stats['bet_amount'] <= 0) {
                continue;
            }
            
            // 计算返水金额
            $rebateAmount = self::calculateCategoryRebate($config, $category, $stats, $config->rebate_type);
            
            if ($rebateAmount > 0) {
                $rebateRate = self::getCategoryRebateRate($config, $category, $config->rebate_type);
                
                $rebateRecords[] = [
                    'agent_id' => $agentId,
                    'category' => $category,
                    'bet_amount' => $stats['bet_amount'],
                    'win_amount' => $stats['win_amount'],
                    'no_win_amount' => $stats['bet_amount'] - $stats['win_amount'],
                    'profit_loss' => $stats['bet_amount'] - $stats['win_amount'],
                    'rebate_amount' => $rebateAmount,
                    'rebate_rate' => $rebateRate,
                    'rebate_type' => $config->rebate_type,
                    'settlement_date' => $endDate,
                    'record_status' => AgentRebateRecord::STATUS_PENDING,
                ];
                
                $totalRebate += $rebateAmount;
            }
        }
        
        return [
            'config' => $config,
            'records' => $rebateRecords,
            'total_rebate' => $totalRebate,
            'category_stats' => $categoryStats,
        ];
    }
    
    /**
     * 按彩种分类统计投注数据
     * @param int $agentId
     * @param string $startDate
     * @param string $endDate
     * @param array $lotteryTypes
     * @return array
     */
    private static function getBetStatsByCategory(int $agentId, string $startDate, string $endDate, array $lotteryTypes): array
    {
        // 获取代理商下级用户的投注数据
        $betStats = Db::name('bet_order')
            ->alias('bo')
            ->join('user u', 'bo.user_id = u.id')
            ->join('lottery_type lt', 'bo.type_code = lt.type_code')
            ->where('u.agent_id', $agentId)
            ->where('bo.create_time', '>=', strtotime($startDate))
            ->where('bo.create_time', '<=', strtotime($endDate . ' 23:59:59'))
            ->where('bo.status', 'in', ['win', 'lose', 'draw']) // 只统计已开奖的订单
            ->field([
                'lt.category',
                'SUM(bo.bet_amount) as total_bet_amount',
                'SUM(CASE WHEN bo.status = "win" THEN bo.win_amount ELSE 0 END) as total_win_amount',
                'COUNT(*) as bet_count'
            ])
            ->group('lt.category')
            ->select()
            ->toArray();
            
        $categoryStats = [];
        foreach ($betStats as $stat) {
            $categoryStats[$stat['category']] = [
                'bet_amount' => floatval($stat['total_bet_amount']) / 100, // 转换为元
                'win_amount' => floatval($stat['total_win_amount']) / 100, // 转换为元
                'bet_count' => intval($stat['bet_count']),
            ];
        }
        
        return $categoryStats;
    }
    
    /**
     * 计算指定彩种的返水金额
     * @param AgentRebateConfig $config
     * @param string $category
     * @param array $stats
     * @param string $rebateType
     * @return float
     */
    private static function calculateCategoryRebate(AgentRebateConfig $config, string $category, array $stats, string $rebateType): float
    {
        $rebateRate = self::getCategoryRebateRate($config, $category, $rebateType);
        
        if ($rebateRate <= 0) {
            return 0;
        }
        
        if ($rebateType === AgentRebateConfig::REBATE_TYPE_PROFIT) {
            // 盈利返水：基于盈亏计算
            $profitLoss = $stats['bet_amount'] - $stats['win_amount'];
            return $profitLoss > 0 ? $profitLoss * ($rebateRate / 100) : 0;
        } else {
            // 投注返水：基于投注金额计算
            return $stats['bet_amount'] * ($rebateRate / 100);
        }
    }
    
    /**
     * 获取指定彩种的返水比例
     * @param AgentRebateConfig $config
     * @param string $category
     * @param string $rebateType
     * @return float
     */
    private static function getCategoryRebateRate(AgentRebateConfig $config, string $category, string $rebateType): float
    {
        $isWin = ($rebateType === AgentRebateConfig::REBATE_TYPE_BET);
        return $config->getRebateRate($category, $isWin);
    }
    
    /**
     * 保存返水记录
     * @param array $rebateRecords
     * @return bool
     * @throws Exception
     */
    public static function saveRebateRecords(array $rebateRecords): bool
    {
        if (empty($rebateRecords)) {
            return true;
        }
        
        Db::startTrans();
        try {
            foreach ($rebateRecords as $record) {
                AgentRebateRecord::create($record);
            }
            Db::commit();
            return true;
        } catch (Exception $e) {
            Db::rollback();
            throw new Exception('保存返水记录失败：' . $e->getMessage());
        }
    }
    
    /**
     * 自动结算代理商返水
     * @param int|null $agentId 指定代理商ID，为空则结算所有代理商
     * @return array
     */
    public static function autoSettleRebate(int $agentId = null): array
    {
        $results = [];
        
        // 获取需要结算的代理商配置
        $query = AgentRebateConfig::where('is_enabled', 1);
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }
        $configs = $query->select();
        
        foreach ($configs as $config) {
            try {
                $result = self::settleAgentRebate($config);
                $results[] = [
                    'agent_id' => $config->agent_id,
                    'success' => true,
                    'message' => $result['message'],
                    'rebate_amount' => $result['rebate_amount'] ?? 0,
                ];
            } catch (Exception $e) {
                $results[] = [
                    'agent_id' => $config->agent_id,
                    'success' => false,
                    'message' => $e->getMessage(),
                    'rebate_amount' => 0,
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * 结算单个代理商返水
     * @param AgentRebateConfig $config
     * @return array
     * @throws Exception
     */
    private static function settleAgentRebate(AgentRebateConfig $config): array
    {
        $agentId = $config->agent_id;
        $cycle = intval($config->settlement_cycle);
        
        // 计算结算日期范围
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$cycle} days"));
        
        // 检查是否已经结算过
        $existingSettlement = AgentRebateSettlement::getSettlementByDate($agentId, $endDate, $config->settlement_cycle);
        if ($existingSettlement) {
            throw new Exception('该代理商今日已结算过返水');
        }
        
        // 计算返水
        $rebateData = self::calculateAgentRebate($agentId, $startDate, $endDate);
        
        if (empty($rebateData['records'])) {
            return [
                'message' => '没有需要结算的返水记录',
                'rebate_amount' => 0,
            ];
        }
        
        // 保存返水记录
        self::saveRebateRecords($rebateData['records']);
        
        return [
            'message' => '返水计算完成，共生成 ' . count($rebateData['records']) . ' 条返水记录',
            'rebate_amount' => $rebateData['total_rebate'],
        ];
    }
    
    /**
     * 获取代理商返水统计
     * @param int $agentId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public static function getAgentRebateStats(int $agentId, string $startDate = null, string $endDate = null): array
    {
        $query = AgentRebateRecord::where('agent_id', $agentId);
        
        if ($startDate) {
            $query->where('settlement_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('settlement_date', '<=', $endDate);
        }
        
        // 总体统计
        $totalStats = $query->field([
            'COUNT(*) as total_records',
            'SUM(bet_amount) as total_bet_amount',
            'SUM(win_amount) as total_win_amount',
            'SUM(rebate_amount) as total_rebate_amount',
        ])->find();
        
        // 按彩种统计
        $categoryStats = $query->field([
            'lottery_category',
            'COUNT(*) as record_count',
            'SUM(bet_amount) as bet_amount',
            'SUM(win_amount) as win_amount',
            'SUM(rebate_amount) as rebate_amount',
        ])->group('lottery_category')->select();
        
        // 按状态统计
        $statusStats = $query->field([
            'record_status',
            'COUNT(*) as record_count',
            'SUM(rebate_amount) as rebate_amount',
        ])->group('record_status')->select();
        
        return [
            'total_stats' => $totalStats,
            'category_stats' => $categoryStats,
            'status_stats' => $statusStats,
        ];
    }
}