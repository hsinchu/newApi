<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\common\model\AgentRebateConfig;
use app\common\model\AgentRebateRecord;
use app\common\model\AgentRebateSettlement;
use app\common\model\BetOrder;
use app\common\model\User;
use app\service\FinanceService;
use app\service\AgentRebateService;
use Exception;
use Throwable;

/**
 * 代理商返水定时任务
 */
class Rebate extends Command
{
    protected function configure()
    {
        $this->setName('rebate')
            ->setDescription('代理商返水定时任务 - 根据配置自动发放返水')
            ->addOption('agent_id', 'a', \think\console\input\Option::VALUE_REQUIRED, '指定代理商ID（可选）')
            ->addOption('force', 'f', \think\console\input\Option::VALUE_NONE, '强制执行（忽略时间检查）')
            ->addOption('dry_run', 'd', \think\console\input\Option::VALUE_NONE, '试运行模式（不实际发放）');
    }

    protected function execute(Input $input, Output $output)
    {
        $agentId = $input->getOption('agent_id');
        $force = $input->hasOption('force');
        $dryRun = $input->hasOption('dry_run');
        
        $output->writeln('开始执行代理商返水定时任务...');
        
        if ($dryRun) {
            $output->writeln('【试运行模式】不会实际发放返水');
        }
        
        try {
            if ($agentId) {
                // 处理指定代理商
                $this->processAgent($agentId, $force, $dryRun, $output);
            } else {
                // 处理所有符合条件的代理商
                $this->processAllAgents($force, $dryRun, $output);
            }
            
            $output->writeln('代理商返水任务执行完成');
            
        } catch (Throwable $e) {
            $output->writeln('任务执行失败: ' . $e->getMessage());
            Log::error('代理商返水任务执行失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * 处理所有符合条件的代理商
     */
    private function processAllAgents($force, $dryRun, Output $output)
    {
        // 获取所有启用的代理商返水配置
        $configs = AgentRebateConfig::where('is_enabled', 1)->select();
        
        if ($configs->isEmpty()) {
            $output->writeln('没有找到启用的代理商返水配置');
            return;
        }
        
        $output->writeln('找到 ' . count($configs) . ' 个启用的代理商配置');
        
        foreach ($configs as $config) {
            try {
                $this->processAgent($config->agent_id, $force, $dryRun, $output);
            } catch (Exception $e) {
                $output->writeln("处理代理商 {$config->agent_id} 失败: " . $e->getMessage());
                Log::error('处理代理商返水失败', [
                    'agent_id' => $config->agent_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 处理单个代理商
     */
    private function processAgent($agentId, $force, $dryRun, Output $output)
    {
        $output->writeln("处理代理商 ID: {$agentId}");
        
        // 验证代理商是否存在
        $agent = User::where('id', $agentId)->where('is_agent', 1)->find();
        if (!$agent) {
            $output->writeln("代理商 {$agentId} 不存在或不是代理商");
            return;
        }
        
        // 获取代理商配置
        $config = AgentRebateConfig::getAgentConfig($agentId);
        if (!$config) {
            $output->writeln("代理商 {$agentId} 返水配置不存在或未启用");
            return;
        }
        
        // 检查是否到了结算时间
        if (!$force && !$this->shouldSettle($config)) {
            $output->writeln("代理商 {$agentId} 未到结算时间");
            return;
        }
        
        // 获取待结算数据
        $pendingData = $this->getPendingRebateData($agentId, $config);
        
        if (empty($pendingData['categoryStats'])) {
            $output->writeln("代理商 {$agentId} 没有待结算的返水数据");
            return;
        }
        
        $totalRebate = $pendingData['totalRebate'];
        $output->writeln("代理商 {$agentId} 待发放返水: ¥" . number_format($totalRebate / 100, 2));
        
        if ($dryRun) {
            $output->writeln("【试运行】跳过实际发放");
            return;
        }
        
        // 执行返水发放
        $this->distributeRebate($agentId, $config, $pendingData, $output);
    }

    /**
     * 检查是否应该结算
     */
    private function shouldSettle(AgentRebateConfig $config)
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
        $lastSettlement = AgentRebateSettlement::where('agent_id', $config->agent_id)
            ->where('settlement_status', AgentRebateSettlement::STATUS_COMPLETED)
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
     */
    private function getPendingRebateData($agentId, AgentRebateConfig $config)
    {
        // 获取该代理商最后一次发放时间
        $lastSettlement = AgentRebateSettlement::where('agent_id', $agentId)
            ->where('settlement_status', AgentRebateSettlement::STATUS_COMPLETED)
            ->order('settlement_time', 'desc')
            ->find();
        
        // 确定统计开始时间
        $startTime = $lastSettlement ? $lastSettlement->settlement_time : 0;
        
        // 获取该代理商下级用户的投注数据
        $categoryStats = AgentRebateService::getBetStatsByCategory($agentId, $startTime, $config);
        
        // 获取该代理商已发放的佣金金额
        $totalCommissionAmount = AgentRebateService::getCommissionAmount($agentId, $startTime);
        
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



    /**
     * 发放返水
     */
    private function distributeRebate($agentId, AgentRebateConfig $config, $pendingData, Output $output)
    {
        $categoryStats = $pendingData['categoryStats'];
        $totalRebateAmount = $pendingData['totalRebate'];
        $totalBetAmount = $pendingData['totalBetAmount'];
        $totalWinAmount = $pendingData['totalWinAmount'];
        $totalCommissionAmount = $pendingData['totalCommissionAmount'];
        
        Db::startTrans();
        try {
            $currentTime = time();
            $settlementDate = date('Y-m-d');
            
            // 计算各彩种返水金额
            $categoryRebates = [
                'sports_rebate_amount' => 0,
                'welfare_rebate_amount' => 0,
                'sports_single_rebate_amount' => 0,
                'quick_rebate_amount' => 0,
            ];
            
            // 创建返水记录
            foreach ($categoryStats as $stat) {
                // 计算该分类的佣金金额（从已发放佣金中按比例分配）
                $categoryCommissionAmount = 0;
                if ($totalRebateAmount > 0) {
                    $categoryCommissionAmount = round(($stat['rebate_amount'] / $totalRebateAmount) * $totalCommissionAmount);
                }
                
                $rebateRecord = new AgentRebateRecord();
                $rebateRecord->agent_id = $agentId;
                $rebateRecord->category = $stat['category'];
                $rebateRecord->bet_amount = $stat['bet_amount'];
                $rebateRecord->win_amount = $stat['win_amount'];
                $rebateRecord->no_win_amount = $stat['no_win_amount'];
                $rebateRecord->profit_loss = $stat['profit_loss'];
                $rebateRecord->rebate_amount = $stat['rebate_amount'];
                $rebateRecord->commission_amount = $categoryCommissionAmount;
                $rebateRecord->no_win_rebate_amount = $stat['no_win_rebate_amount'];
                $rebateRecord->no_win_rate = $stat['no_win_rate'];
                $rebateRecord->bet_rebate_amount = $stat['bet_rebate_amount'];
                $rebateRecord->bet_rate = $stat['bet_rate'];
                $rebateRecord->rebate_type = $config->rebate_type;
                $rebateRecord->settlement_date = $settlementDate;
                $rebateRecord->record_status = AgentRebateRecord::STATUS_SETTLED;
                $rebateRecord->settlement_time = $currentTime;
                $rebateRecord->save();
                
                // 累计各彩种返水金额
                switch ($stat['category']) {
                    case 'SPORTS':
                        $categoryRebates['sports_rebate_amount'] += $stat['rebate_amount'];
                        break;
                    case 'WELFARE':
                        $categoryRebates['welfare_rebate_amount'] += $stat['rebate_amount'];
                        break;
                    case 'SPORTS_SINGLE':
                        $categoryRebates['sports_single_rebate_amount'] += $stat['rebate_amount'];
                        break;
                    case 'QUICK':
                        $categoryRebates['quick_rebate_amount'] += $stat['rebate_amount'];
                        break;
                }
            }
            
            // 创建结算记录
            $settlement = new AgentRebateSettlement();
            $settlement->agent_id = $agentId;
            $settlement->settlement_date = $settlementDate;
            $settlement->settlement_cycle = $config->settlement_cycle;
            $settlement->total_bet_amount = $totalBetAmount;
            $settlement->total_win_amount = $totalWinAmount;
            $settlement->total_profit_loss = $totalBetAmount - $totalWinAmount;
            $settlement->total_rebate_amount = $totalRebateAmount;
            $settlement->commission_amount = $totalCommissionAmount;
            $settlement->sports_rebate = $categoryRebates['sports_rebate_amount'];
            $settlement->welfare_rebate = $categoryRebates['welfare_rebate_amount'];
            $settlement->sports_single_rebate = $categoryRebates['sports_single_rebate_amount'];
            $settlement->quick_rebate = $categoryRebates['quick_rebate_amount'];
            $settlement->settlement_status = AgentRebateSettlement::STATUS_COMPLETED;
            $settlement->settlement_time = $currentTime;
            $settlement->operator_id = 0; // 系统自动发放
            $settlement->remark = '系统定时自动发放';
            $settlement->save();
            
            // 更新代理商余额
            $financeService = new FinanceService();
            $financeService->adjustUserBalance(
                $agentId,
                $totalRebateAmount / 100, // 转换为元
                '代理返水发放 - 系统自动',
                'COMMISSION_ADD'
            );
            
            Db::commit();
            
            $output->writeln("代理商 {$agentId} 返水发放成功: ¥" . number_format($totalRebateAmount / 100, 2));
            
            // 记录日志
            Log::info('代理商返水自动发放成功', [
                'agent_id' => $agentId,
                'total_rebate_amount' => $totalRebateAmount / 100,
                'settlement_id' => $settlement->id,
                'category_count' => count($categoryStats)
            ]);
            
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 获取代理商已发放的佣金金额
     * @param int $agentId 代理商ID
     * @param int $startTime 开始时间
     * @return int 佣金金额（分）
     */

}