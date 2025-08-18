<?php

namespace app\admin\controller\user;

use Throwable;
use think\facade\Db;
use app\common\controller\Backend;
use app\common\model\AgentRebateConfig;
use app\common\model\AgentRebateRecord;
use app\common\model\AgentRebateSettlement;
use app\common\model\BetOrder;
use app\common\model\User;
use app\service\FinanceService;
use app\service\AgentRebateService;

/**
 * 代理商返水管理控制器
 */
class AgentRebate extends Backend
{
    /**
     * @var object
     * @phpstan-var AgentRebateConfig
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['agent_id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new AgentRebateConfig();
    }

    /**
     * 获取代理商返水配置
     * @throws Throwable
     */
    public function getConfig(): void
    {
        $agentId = $this->request->param('agent_id/d');
        if (!$agentId) {
            $this->error(__('Parameter error'));
        }

        // 验证代理商是否存在
        $agent = User::where('id', $agentId)->where('is_agent', 1)->find();
        if (!$agent) {
            $this->error('代理商不存在');
        }

        $config = AgentRebateConfig::where('agent_id', $agentId)->find();
        if (!$config) {
            // 创建默认配置
            $config = new AgentRebateConfig();
            $config->agent_id = $agentId;
            $config->sports_no_win_rate = 0;
            $config->sports_bet_rate = 0;
            $config->welfare_no_win_rate = 0;
            $config->welfare_bet_rate = 0;
            $config->sports_single_no_win_rate = 0;
            $config->sports_single_bet_rate = 0;
            $config->quick_no_win_rate = 0;
            $config->quick_bet_rate = 0;
            $config->rebate_type = AgentRebateConfig::REBATE_TYPE_PROFIT;
            $config->settlement_cycle = AgentRebateConfig::SETTLEMENT_CYCLE_7;
            $config->settlement_time = '23:45';
            $config->is_enabled = 1;
            $config->save();
        }

        $this->success('', [
            'config' => $config,
            'agent' => $agent,
        ]);
    }

    /**
     * 保存代理商返水配置
     * @throws Throwable
     */
    public function saveConfig(): void
    {
        $data = $this->request->post();
        $agentId = $data['agent_id'] ?? 0;
        
        if (!$agentId) {
            $this->error(__('Parameter error'));
        }

        // 验证代理商是否存在
        $agent = User::where('id', $agentId)->where('is_agent', 1)->find();
        if (!$agent) {
            $this->error('代理商不存在');
        }

        try {
            $config = AgentRebateConfig::where('agent_id', $agentId)->find();
            if ($config) {
                $config->save($data);
            } else {
                $data['agent_id'] = $agentId;
                AgentRebateConfig::create($data);
            }
        } catch (Throwable $e) {
            $this->error('配置保存失败：' . $e->getMessage());
        }
            
        $this->success('配置保存成功');
    }

    /**
     * 获取代理商返水记录
     * @throws Throwable
     */
    public function getRecords(): void
    {
        $agentId = $this->request->param('agent_id/d');
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        $lotteryCategory = $this->request->param('lottery_category', '');
        $recordStatus = $this->request->param('record_status', '');
        $settlementDate = $this->request->param('settlement_date', []);
        
        if (!$agentId) {
            $this->error(__('Parameter error'));
        }

        $query = AgentRebateRecord::where('agent_id', $agentId);
        
        // 添加搜索条件
        if ($lotteryCategory) {
            $query->where('category', $lotteryCategory);
        }
        
        if ($recordStatus) {
            $query->where('record_status', $recordStatus);
        }
        
        if (!empty($settlementDate) && is_array($settlementDate) && count($settlementDate) === 2) {
            $query->where('settlement_date', '>=', $settlementDate[0])
                  ->where('settlement_date', '<=', $settlementDate[1]);
        }
        
        $query->order('create_time', 'desc');

        $total = $query->count();
        $records = $query->page($page, $limit)->select();
        
        // 格式化返回数据
        $formattedRecords = [];
        foreach ($records as $record) {
            $formattedRecords[] = [
                'id' => $record->id,
                'category' => $record->category,
                'category_text' => $record->category_text,
                'bet_amount' => $record->bet_amount / 100, // 转换为元
                'win_amount' => $record->win_amount / 100,
                'no_win_amount' => $record->no_win_amount / 100,
                'profit_loss' => $record->profit_loss / 100,
                'rebate_amount' => $record->rebate_amount / 100,
                'commission_amount' => $record->commission_amount / 100,
                'rebate_rate' => $record->rebate_rate,
                'rebate_type' => $record->rebate_type,
                'no_win_rebate_amount' => $record->no_win_rebate_amount / 100,
                'no_win_rate' => $record->no_win_rate,
                'bet_rebate_amount' => $record->bet_rebate_amount / 100,
                'bet_rate' => $record->bet_rate,
                'rebate_type_text' => $record->rebate_type_text,
                'settlement_date' => $record->settlement_date,
                'record_status' => $record->record_status,
                'record_status_text' => $record->record_status_text,
                'create_time' => $record->create_time,
            ];
        }

        $this->success('', [
            'list' => $formattedRecords,
            'total' => $total,
        ]);
    }

    /**
     * 获取待发放返水信息
     * @throws Throwable
     */
    public function getPendingRebate(): void
    {
        $agentId = $this->request->param('agent_id/d');
        if (!$agentId) {
            $this->error(__('Parameter error'));
        }

        // 获取代理商配置
        $config = AgentRebateConfig::getAgentConfig($agentId);
        if (!$config) {
            $this->error('代理商返水配置不存在或未启用');
        }

        // 获取该代理商最后一次发放时间
        $lastSettlement = AgentRebateSettlement::where('agent_id', $agentId)
            ->where('settlement_status', AgentRebateSettlement::STATUS_COMPLETED)
            ->order('settlement_time', 'desc')
            ->find();
        
        // 确定统计开始时间
        $startTime = $lastSettlement ? $lastSettlement->settlement_time : 0;
        
        // 获取该代理商下级用户的投注数据
        $categoryStats = AgentRebateService::getBetStatsByCategory($agentId, $startTime, $config);
        
        // 删除已发放佣金相关逻辑
        
        // 计算总返水金额和分类返水金额
        $totalRebate = 0;
        $totalNoWinRebate = 0; // 未中奖返水总额
        $totalBetRebate = 0;   // 投注返水总额
        $totalBetAmount = 0;
        $totalWinAmount = 0;
        $totalRecordCount = 0;
        
        foreach ($categoryStats as &$stat) {
            $totalBetAmount += $stat['bet_amount'];
            $totalWinAmount += $stat['win_amount'];
            $totalRecordCount += $stat['record_count'];
            
            // 计算未中奖金额和盈亏
            $stat['no_win_amount'] = $stat['bet_amount'] - $stat['win_amount'];
            $stat['profit_loss'] = $stat['bet_amount'] - $stat['win_amount'];
            
            // 分别计算未中奖返水和投注返水
            $category = $stat['category'];
            $betAmount = $stat['bet_amount'];
            $noWinAmount = $stat['no_win_amount'];
            $profitLoss = $stat['profit_loss'];
            
            // 根据返水类型计算返水金额
            $noWinRebateAmount = 0;
            $betRebateAmount = 0;
            
            if ($config->rebate_type === 'profit') {
                // 盈利模式：按盈利金额计算两种返水，只有盈利时才计算
                if ($profitLoss > 0) {
                    switch ($category) {
                        case 'SPORTS':
                            $noWinRebateAmount = $profitLoss * ($config->sports_no_win_rate / 100);
                            $betRebateAmount = $profitLoss * ($config->sports_bet_rate / 100);
                            break;
                        case 'WELFARE':
                            $noWinRebateAmount = $profitLoss * ($config->welfare_no_win_rate / 100);
                            $betRebateAmount = $profitLoss * ($config->welfare_bet_rate / 100);
                            break;
                        case 'SPORTS_SINGLE':
                            $noWinRebateAmount = $profitLoss * ($config->sports_single_no_win_rate / 100);
                            $betRebateAmount = $profitLoss * ($config->sports_single_bet_rate / 100);
                            break;
                        case 'QUICK':
                            $noWinRebateAmount = $profitLoss * ($config->quick_no_win_rate / 100);
                            $betRebateAmount = $profitLoss * ($config->quick_bet_rate / 100);
                            break;
                    }
                }
            } elseif ($config->rebate_type === 'bet') {
                // 投注模式：按投注金额计算两种返水
                switch ($category) {
                    case 'SPORTS':
                        $noWinRebateAmount = $betAmount * ($config->sports_no_win_rate / 100);
                        $betRebateAmount = $betAmount * ($config->sports_bet_rate / 100);
                        break;
                    case 'WELFARE':
                        $noWinRebateAmount = $betAmount * ($config->welfare_no_win_rate / 100);
                        $betRebateAmount = $betAmount * ($config->welfare_bet_rate / 100);
                        break;
                    case 'SPORTS_SINGLE':
                        $noWinRebateAmount = $betAmount * ($config->sports_single_no_win_rate / 100);
                        $betRebateAmount = $betAmount * ($config->sports_single_bet_rate / 100);
                        break;
                    case 'QUICK':
                        $noWinRebateAmount = $betAmount * ($config->quick_no_win_rate / 100);
                        $betRebateAmount = $betAmount * ($config->quick_bet_rate / 100);
                        break;
                }
            }
            
            $totalNoWinRebate += $noWinRebateAmount;
            $totalBetRebate += $betRebateAmount;
            
            // 总返水金额
            $totalRebate += ($noWinRebateAmount + $betRebateAmount);
            
            // 添加到统计数据中
            $stat['no_win_rebate_amount'] = $noWinRebateAmount;
            $stat['bet_rebate_amount'] = $betRebateAmount;
            $stat['rebate_amount'] = $noWinRebateAmount + $betRebateAmount;
        }

        // 可结算金额即为总返水金额
        $settlableAmount = $totalRebate;
        
        $this->success('', [
            'config' => $config,
            'categoryStats' => array_values($categoryStats),
            'totalRebate' => round($totalRebate / 100, 2), // 转换为元
            'totalNoWinRebate' => round($totalNoWinRebate / 100, 2), // 未中奖返水总额
            'totalBetRebate' => round($totalBetRebate / 100, 2), // 投注返水总额
            'settlableAmount' => round($settlableAmount / 100, 2), // 可结算金额
            'totalBetAmount' => round($totalBetAmount / 100, 2),
            'totalWinAmount' => round($totalWinAmount / 100, 2),
            'recordCount' => $totalRecordCount,
            'lastSettlementTime' => $lastSettlement ? date('Y-m-d H:i:s', $lastSettlement->settlement_time) : '从未发放',
        ]);
    }



    /**
     * 发放返水
     * @throws Throwable
     */
    public function distributeRebate(): void
    {
        $agentId = $this->request->param('agent_id/d');
        $remark = $this->request->param('remark', '');
        
        if (!$agentId) {
            $this->error(__('Parameter error'));
        }

        // 验证代理商是否存在
        $agent = User::where('id', $agentId)->where('is_agent', 1)->find();
        if (!$agent) {
            $this->error('代理商不存在');
        }

        // 获取代理商配置
        $config = AgentRebateConfig::getAgentConfig($agentId);
        if (!$config) {
            $this->error('代理商返水配置不存在或未启用');
        }

        // 获取该代理商最后一次发放时间
        $lastSettlement = AgentRebateSettlement::where('agent_id', $agentId)
            ->where('settlement_status', AgentRebateSettlement::STATUS_COMPLETED)
            ->order('settlement_time', 'desc')
            ->find();
        
        // 确定统计开始时间
        $startTime = $lastSettlement ? $lastSettlement->settlement_time : 0;
        
        // 获取该代理商下级用户的投注数据
        $categoryStats = AgentRebateService::getBetStatsByCategory($agentId, $startTime, $config);
        
        // 删除已发放佣金相关逻辑
        
        if (empty($categoryStats)) {
            $this->error('没有待发放的返水记录');
        }

        Db::startTrans();
        try {
            // 计算总返水金额和各彩种返水金额
            $totalBetAmount = 0;
            $totalWinAmount = 0;
            $totalRebateAmount = 0;
            $categoryRebates = [
                'sports_rebate_amount' => 0,
                'welfare_rebate_amount' => 0,
                'sports_single_rebate_amount' => 0,
                'quick_rebate_amount' => 0,
            ];
            
            $rebateRecords = [];
            foreach ($categoryStats as $stat) {
                $totalBetAmount += $stat['bet_amount'];
                $totalWinAmount += $stat['win_amount'];
                $totalRebateAmount += $stat['rebate_amount'];
                
                // 按彩种累计返水金额
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
                
                // 使用从getBetStatsByCategory方法计算的正确值
                $noWinRebateAmount = $stat['no_win_rebate_amount'];
                $betRebateAmount = $stat['bet_rebate_amount'];
                $noWinRate = $stat['no_win_rate'];
                $betRate = $stat['bet_rate'];
                
                // 删除佣金计算逻辑
                
                // 创建返水记录
                $rebateRecords[] = [
                    'agent_id' => $agentId,
                    'category' => $stat['category'],
                    'bet_amount' => $stat['bet_amount'],
                    'win_amount' => $stat['win_amount'],
                    'no_win_amount' => $stat['no_win_amount'],
                    'profit_loss' => $stat['profit_loss'],
                    'rebate_amount' => $stat['rebate_amount'],

                    'no_win_rebate_amount' => $noWinRebateAmount,
                    'no_win_rate' => $noWinRate,
                    'bet_rebate_amount' => $betRebateAmount,
                    'bet_rate' => $betRate,
                    'rebate_type' => $config->rebate_type,
                    'settlement_date' => date('Y-m-d'),
                    'record_status' => AgentRebateRecord::STATUS_SETTLED,
                    'settlement_time' => time(),
                    'create_time' => time(),
                    'update_time' => time(),
                ];
            }
            
            // 检查是否有可发放的返水金额
            if ($totalRebateAmount <= 0) {
                $this->error('没有可发放的返水金额');
            }

            // 批量创建返水记录
            if (!empty($rebateRecords)) {
                AgentRebateRecord::insertAll($rebateRecords);
            }

            // 创建发放记录
            $settlementData = [
                'agent_id' => $agentId,
                'settlement_date' => date('Y-m-d'),
                'settlement_cycle' => $config->settlement_cycle,
                'total_bet_amount' => $totalBetAmount,
                'total_win_amount' => $totalWinAmount,
                'total_profit_loss' => $totalBetAmount - $totalWinAmount,
                'total_rebate_amount' => $totalRebateAmount,
                'settlement_status' => AgentRebateSettlement::STATUS_COMPLETED,
                'settlement_time' => time(),
                'operator_id' => $this->auth->id,
                'remark' => $remark,
            ];
            $settlementData = array_merge($settlementData, $categoryRebates);
            
            AgentRebateSettlement::create($settlementData);

            // 使用FinanceService调整代理商余额和记录账变
            $financeService = new FinanceService();
            $financeService->adjustUserBalance(
                $agentId,
                $totalRebateAmount / 100, // 转换为元
                '代理返水发放' . ($remark ? '：' . $remark : ''),
                'COMMISSION_ADD'
            );

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            
            // 检查是否为重复发放错误
            if (strpos($e->getMessage(), '1062') !== false && strpos($e->getMessage(), 'agent_date_cycle') !== false) {
                $this->error('今日已发放过返水，一天只能发放一次');
            }
            
            $this->error('返水发放失败：' . $e->getMessage());
        }
        $this->success('返水发放成功，共发放 ' . round($totalRebateAmount / 100, 2) . ' 元');
    }

    /**
     * 获取发放记录
     * @throws Throwable
     */
    public function getSettlements(): void
    {
        $agentId = $this->request->param('agent_id/d');
        $page = $this->request->param('page/d', 1);
        $limit = $this->request->param('limit/d', 10);
        
        if (!$agentId) {
            $this->error(__('Parameter error'));
        }

        $query = AgentRebateSettlement::where('agent_id', $agentId)
            ->with(['agent', 'operator'])
            ->order('create_time', 'desc');

        $total = $query->count();
        $settlements = $query->page($page, $limit)->select();

        $this->success('', [
            'list' => $settlements,
            'total' => $total,
        ]);
    }


}