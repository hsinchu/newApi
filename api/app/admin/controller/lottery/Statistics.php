<?php

namespace app\admin\controller\lottery;

use app\common\model\BetOrder;
use app\common\model\LotteryDraw;
use app\common\model\LotteryType;
use app\common\model\User;
use app\common\controller\Backend;
use think\facade\Db;
use think\facade\Log;
use think\facade\Validate;
use Exception;

/**
 * 彩票统计控制器
 */
class Statistics extends Backend
{
    /**
     * 无需登录的方法
     */
    protected array $noNeedLogin = [];

    /**
     * 无需权限的方法
     */
    protected array $noNeedPermission = [];

    /**
     * 获取总览统计
     */
    public function overview(): void
    {
        try {
            $stats = [
                'total_lottery_types' => LotteryType::where('is_enabled', 1)->count(),
                'total_bet_orders' => BetOrder::count(),
                'total_bet_amount' => BetOrder::sum('bet_amount') / 100, // 转换为元
                'total_win_amount' => BetOrder::whereIn('status', ['WINNING', 'LOSING'])->sum('win_amount') / 100
            ];
            
            Log::info('获取总览统计', [
                'admin_id' => $this->auth->id,
                'stats' => $stats
            ]);
            
            $this->jsonReturn('获取成功', $stats);
        } catch (Exception $e) {
            Log::error('获取总览统计失败', [
                'admin_id' => $this->auth->id,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取彩票类型列表
     */
    public function lotteryTypes(): void
    {
        try {
            $types = LotteryType::where('is_enabled', 1)
                ->field('id,type_name')
                ->order('sort_order', 'asc')
                ->select()
                ->toArray();
            
            $this->jsonReturn('获取成功', $types);
        } catch (Exception $e) {
            Log::error('获取彩票类型列表失败', [
                'admin_id' => $this->auth->id,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 用户统计
     */
    public function userStats(): void
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);
        $dateRange = $this->request->param('dateRange', []);
        $lotteryTypeId = $this->request->param('lottery_type_id', '');
        
        // 参数验证
        $validate = Validate::rule([
            'page' => 'integer|egt:1',
            'limit' => 'integer|between:1,100',
            'lottery_type_id' => 'integer|egt:0'
        ]);
        
        $params = [
            'page' => $page,
            'limit' => $limit,
            'lottery_type_id' => $lotteryTypeId
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }
        
        try {
            $query = Db::name('bet_order')
                ->alias('bo')
                ->leftJoin('user u', 'bo.user_id = u.id')
                ->leftJoin('lottery_type lt', 'bo.lottery_type_id = lt.id')
                ->field([
                    'bo.user_id',
                    'u.username',
                    'COUNT(bo.id) as total_bets',
                    'SUM(bo.bet_amount) as total_amount',
                    'SUM(CASE WHEN bo.status = "WINNING" THEN bo.win_amount ELSE 0 END) as win_amount',
                    'ROUND(COUNT(CASE WHEN bo.status = "WINNING" THEN 1 END) * 100.0 / COUNT(CASE WHEN bo.status IN ("WINNING", "LOSING") THEN 1 END), 2) as win_rate',
                    'SUM(CASE WHEN bo.status IN ("WINNING", "LOSING") THEN CAST(bo.win_amount AS SIGNED) - CAST(bo.bet_amount AS SIGNED) ELSE 0 END) as profit_loss',
                    'MAX(bo.create_time) as last_bet_time'
                ])
                ->group('bo.user_id');
            
            // 日期范围过滤
            if (!empty($dateRange) && count($dateRange) == 2) {
                $query->whereBetweenTime('bo.create_time', $dateRange[0], $dateRange[1]);
            }
            
            // 彩票类型过滤
            if ($lotteryTypeId) {
                $query->where('bo.lottery_type_id', $lotteryTypeId);
            }
            
            $total = $query->count();
            $list = $query->page($page, $limit)
                ->order('total_amount', 'desc')
                ->select()
                ->toArray();
            
            // 转换金额单位为元
            foreach ($list as &$item) {
                $item['total_amount'] = round($item['total_amount'] / 100, 2);
                $item['win_amount'] = round($item['win_amount'] / 100, 2);
                $item['profit_loss'] = round($item['profit_loss'] / 100, 2);
                $item['win_rate'] = $item['win_rate'] ?: 0;
            }
            
            Log::info('获取用户统计', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'total' => $total
            ]);
            
            $this->jsonReturn('获取成功', [
                'list' => $list,
                'total' => $total
            ]);
        } catch (Exception $e) {
            Log::error('获取用户统计失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 开奖统计
     */
    public function drawStats(): void
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);
        $dateRange = $this->request->param('dateRange', []);
        $lotteryTypeId = $this->request->param('lottery_type_id', '');
        
        // 参数验证
        $validate = Validate::rule([
            'page' => 'integer|egt:1',
            'limit' => 'integer|between:1,100',
            'lottery_type_id' => 'integer|egt:0'
        ]);
        
        $params = [
            'page' => $page,
            'limit' => $limit,
            'lottery_type_id' => $lotteryTypeId
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }
        
        try {
            $query = Db::name('lottery_draw')
                ->alias('ld')
                ->leftJoin('lottery_type lt', 'ld.lottery_type_id = lt.id')
                ->field([
                    'ld.id',
                    'ld.period_no as draw_no',
                    'ld.draw_time',
                    'ld.status',
                    'lt.type_name as lottery_type_name',
                    'COUNT(bo.id) as total_bets',
                    'SUM(bo.bet_amount) as total_amount',
                    'COUNT(CASE WHEN bo.status = "WINNING" THEN 1 END) as win_bets',
                    'SUM(CASE WHEN bo.status = "WINNING" THEN bo.win_amount ELSE 0 END) as win_amount',
                    'SUM(CASE WHEN bo.status IN ("WINNING", "LOSING") THEN CAST(bo.bet_amount AS SIGNED) - CAST(bo.win_amount AS SIGNED) ELSE 0 END) as profit_amount'
                ])
                ->leftJoin('bet_order bo', 'ld.id = bo.lottery_type_id')
                ->group('ld.id');
            
            // 日期范围过滤
            if (!empty($dateRange) && count($dateRange) == 2) {
                $query->whereBetweenTime('ld.draw_time', $dateRange[0], $dateRange[1]);
            }
            
            // 彩票类型过滤
            if ($lotteryTypeId) {
                $query->where('ld.lottery_type_id', $lotteryTypeId);
            }
            
            $total = $query->count();
            $list = $query->page($page, $limit)
                ->order('ld.draw_time', 'desc')
                ->select()
                ->toArray();
            
            // 处理数据
            $statusMap = [
                'CONFIRMED' => '待开奖',
                'DRAWN' => '已开奖',
                'COMPLETED' => '已结算',
                'CANCELLED' => '已取消'
            ];
            
            foreach ($list as &$item) {
                $item['total_amount'] = round($item['total_amount'] / 100, 2);
                $item['win_amount'] = round($item['win_amount'] / 100, 2);
                $item['profit_amount'] = round($item['profit_amount'] / 100, 2);
                $item['status_text'] = $statusMap[$item['status']] ?? $item['status'];
                $item['total_bets'] = $item['total_bets'] ?: 0;
                $item['win_bets'] = $item['win_bets'] ?: 0;
            }
            
            Log::info('获取开奖统计', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'total' => $total
            ]);
            
            $this->jsonReturn('获取成功', [
                'list' => $list,
                'total' => $total
            ]);
        } catch (Exception $e) {
            Log::error('获取开奖统计失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 投注统计
     */
    public function betStats(): void
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);
        $dateRange = $this->request->param('dateRange', []);
        $lotteryTypeId = $this->request->param('lottery_type_id', '');
        $status = $this->request->param('status', '');
        
        // 参数验证
        $validate = Validate::rule([
            'page' => 'integer|egt:1',
            'limit' => 'integer|between:1,100',
            'lottery_type_id' => 'integer|egt:0',
            'status' => 'in:,PENDING,CONFIRMED,WINNING,LOSING,CANCELLED,REFUNDED'
        ]);
        
        $params = [
            'page' => $page,
            'limit' => $limit,
            'lottery_type_id' => $lotteryTypeId,
            'status' => $status
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }
        
        try {
            $query = Db::name('bet_order')
                ->alias('bo')
                ->leftJoin('lottery_type lt', 'bo.lottery_type_id = lt.id')
                ->field([
                    'DATE(bo.create_time) as date',
                    'lt.type_name as lottery_type_name',
                    'COUNT(bo.id) as total_bets',
                    'SUM(bo.bet_amount) as total_amount',
                    'AVG(bo.bet_amount) as avg_amount',
                    'COUNT(CASE WHEN bo.status = "WINNING" THEN 1 END) as win_bets',
                    'SUM(CASE WHEN bo.status = "WINNING" THEN bo.win_amount ELSE 0 END) as win_amount',
                    'ROUND(COUNT(CASE WHEN bo.status = "WINNING" THEN 1 END) * 100.0 / COUNT(CASE WHEN bo.status IN ("WINNING", "LOSING") THEN 1 END), 2) as win_rate'
                ])
                ->group('DATE(bo.create_time), bo.lottery_type_id');
            
            // 日期范围过滤
            if (!empty($dateRange) && count($dateRange) == 2) {
                $query->whereBetweenTime('bo.create_time', $dateRange[0], $dateRange[1]);
            }
            
            // 彩票类型过滤
            if ($lotteryTypeId) {
                $query->where('bo.lottery_type_id', $lotteryTypeId);
            }
            
            // 状态过滤
            if ($status) {
                $query->where('bo.status', $status);
            }
            
            $total = $query->count();
            $list = $query->page($page, $limit)
                ->order('date', 'desc')
                ->select()
                ->toArray();
            
            // 转换金额单位为元
            foreach ($list as &$item) {
                $item['total_amount'] = round($item['total_amount'] / 100, 2);
                $item['avg_amount'] = round($item['avg_amount'] / 100, 2);
                $item['win_amount'] = round($item['win_amount'] / 100, 2);
                $item['win_rate'] = $item['win_rate'] ?: 0;
            }
            
            // 生成图表数据
            $chartData = $this->generateChartData($dateRange, $lotteryTypeId, $status);
            
            Log::info('获取投注统计', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'total' => $total
            ]);
            
            $this->jsonReturn('获取成功', [
                'list' => $list,
                'total' => $total,
                'charts' => $chartData
            ]);
        } catch (Exception $e) {
            Log::error('获取投注统计失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 生成图表数据
     */
    private function generateChartData($dateRange, $lotteryTypeId, $status): array
    {
        try {
            // 投注金额趋势数据
            $amountQuery = Db::table('bet_order')
                ->field([
                    'DATE(create_time) as date',
                    'SUM(bet_amount) as amount'
                ])
                ->group('DATE(create_time)')
                ->order('date', 'asc');
            
            if (!empty($dateRange) && count($dateRange) == 2) {
                $amountQuery->whereBetweenTime('create_time', $dateRange[0], $dateRange[1]);
            }
            
            if ($lotteryTypeId) {
                $amountQuery->where('lottery_type_id', $lotteryTypeId);
            }
            
            if ($status) {
                $amountQuery->where('status', $status);
            }
            
            $amountData = $amountQuery->select()->toArray();
            
            $betAmount = [
                'dates' => array_column($amountData, 'date'),
                'amounts' => array_map(function($item) {
                    return round($item['amount'] / 100, 2);
                }, $amountData)
            ];
            
            // 投注状态分布数据
            $statusQuery = Db::table('bet_order')
                ->field([
                    'status',
                    'COUNT(*) as count'
                ])
                ->group('status');
            
            if (!empty($dateRange) && count($dateRange) == 2) {
                $statusQuery->whereBetweenTime('create_time', $dateRange[0], $dateRange[1]);
            }
            
            if ($lotteryTypeId) {
                $statusQuery->where('lottery_type_id', $lotteryTypeId);
            }
            
            $statusData = $statusQuery->select()->toArray();
            
            $statusMap = [
                'PENDING' => '待确认',
                'CONFIRMED' => '已确认',
                'WINNING' => '中奖',
                'LOSING' => '未中奖',
                'CANCELLED' => '已取消',
                'REFUNDED' => '已退款'
            ];
            
            $betStatus = array_map(function($item) use ($statusMap) {
                return [
                    'name' => $statusMap[$item['status']] ?? $item['status'],
                    'value' => $item['count']
                ];
            }, $statusData);
            
            return [
                'betAmount' => $betAmount,
                'betStatus' => $betStatus
            ];
        } catch (Exception $e) {
            Log::error('生成图表数据失败', [
                'error' => $e->getMessage()
            ]);
            return [
                'betAmount' => ['dates' => [], 'amounts' => []],
                'betStatus' => []
            ];
        }
    }
}