<?php

namespace app\admin\controller\lottery;

use app\common\model\LotteryDraw as LotteryDrawModel;
use app\common\model\LotteryType;
use app\common\controller\Backend;
use app\service\LotteryService;
use think\facade\Log;
use think\facade\Validate;
use Exception;

/**
 * 开奖结果管理控制器
 */
class LotteryDraw extends Backend
{
    /**
     * LotteryDraw模型对象
     * @var LotteryDrawModel
     */
    protected object $model;
    
    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];
    
    protected array $withJoinTable = ['lotteryType'];
    
    protected string|array $quickSearchField = ['lottery_code', 'period_no'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new LotteryDrawModel();
    }

    /**
     * 手动开奖
     */
    public function manualDraw(): void
    {
        $id = $this->request->param('id');
        $drawNumbers = $this->request->param('draw_numbers');
        $drawResult = $this->request->param('draw_result');
        $remark = $this->request->param('remark', '管理员手动开奖');
        
        // 参数验证
        $validate = Validate::rule([
            'id' => 'require|integer|gt:0',
            'draw_numbers' => 'require|length:1,200',
            'remark' => 'max:500'
        ]);
        
        $params = [
            'id' => $id,
            'draw_numbers' => $drawNumbers,
            'remark' => $remark
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        try {
            // 检查开奖记录是否存在
            $draw = $this->model->find($id);
            if (!$draw) {
                $this->error('开奖记录不存在');
            }
            
            // 检查状态
            if ($draw->status !== 'PENDING') {
                $this->error('只能对待开奖状态的记录进行手动开奖');
            }
            
            // 验证开奖号码格式
            $this->validateDrawNumbers($drawNumbers, null);
            
            $this->model->startTrans();
            
            // 更新开奖信息
            $updateData = [
                'draw_numbers' => $drawNumbers,
                'draw_time' => time(),
                'status' => 'DRAWN',
                'remark' => $remark,
                'is_official' => 0  // 手动开奖标记为非官方
            ];
            
            if ($drawResult) {
                $updateData['draw_result'] = json_encode($drawResult);
            }
            
            // 统计该期投注数据
            $betStats = $this->calculateBetStatistics($draw->lottery_code, $draw->period_no);
            $updateData['bet_count'] = $betStats['bet_count'];
            $updateData['total_bet_amount'] = $betStats['total_bet_amount'];
            
            $result = $draw->save($updateData);
            
            if ($result) {
                $this->model->commit();
                
                Log::info('管理员手动开奖', [
                    'admin_id' => $this->auth->id,
                    'draw_id' => $id,
                    'lottery_code' => $draw->lottery_code,
                    'period_no' => $draw->period_no,
                    'draw_numbers' => $drawNumbers,
                    'remark' => $remark,
                    'bet_count' => $betStats['bet_count'],
                    'total_bet_amount' => $betStats['total_bet_amount']
                ]);
                
                $this->success('开奖成功');
            } else {
                $this->model->rollback();
                $this->error('开奖失败');
            }
        } catch (Exception $e) {
            $this->model->rollback();
            Log::error('手动开奖失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 批量手动开奖（原方法保留用于其他场景）
     */
    public function batchManualDraw(): void
    {
        $lotteryCode = $this->request->param('lottery_code');
        $periodNo = $this->request->param('period_no');
        $drawNumbers = $this->request->param('draw_numbers');
        $drawTime = $this->request->param('draw_time');
        $remark = $this->request->param('remark', '管理员批量手动开奖');
        
        // 参数验证
        $validate = Validate::rule([
            'lottery_code' => 'require|alphaNum|length:2,20',
            'period_no' => 'require|alphaNum|length:1,50',
            'draw_numbers' => 'require|length:1,200',
            'draw_time' => 'date',
            'remark' => 'max:500'
        ]);
        
        $params = [
            'lottery_code' => $lotteryCode,
            'period_no' => $periodNo,
            'draw_numbers' => $drawNumbers,
            'draw_time' => $drawTime,
            'remark' => $remark
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        try {
            // 检查彩种是否存在
            $lotteryType = LotteryType::where('type_code', $lotteryCode)->where('status', 1)->find();
            if (!$lotteryType) {
                $this->error('彩种不存在或已禁用');
            }
            
            // 检查期号是否已存在
            $exists = $this->model
                ->where('lottery_code', $lotteryCode)
                ->where('period_no', $periodNo)
                ->find();
            if ($exists) {
                $this->error('该期号已存在开奖结果');
            }
            
            $lotteryService = new LotteryService();
            $result = $lotteryService->manualDraw($lotteryCode, $periodNo, $drawNumbers, [
                'draw_time' => $drawTime ? strtotime($drawTime) : time(),
                'remark' => $remark,
                'created_by' => $this->auth->id
            ]);
            
            if ($result) {
                Log::info('管理员批量手动开奖', [
                    'admin_id' => $this->auth->id,
                    'lottery_code' => $lotteryCode,
                    'period_no' => $periodNo,
                    'draw_numbers' => $drawNumbers,
                    'remark' => $remark
                ]);
                $this->success('开奖成功');
            } else {
                $this->error('开奖失败');
            }
        } catch (Exception $e) {
            Log::error('批量手动开奖失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取开奖统计
     */
    public function statistics(): void
    {
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $lotteryCode = $this->request->param('lottery_code');
        $status = $this->request->param('status');
        $groupBy = $this->request->param('group_by', 'day');

        // 参数验证
        $validate = Validate::rule([
            'start_time' => 'date',
            'end_time' => 'date',
            'lottery_code' => 'alphaNum|length:2,20',
            'status' => 'in:PENDING,DRAWN,SETTLED',
            'group_by' => 'in:day,hour,month'
        ]);
        
        $params = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'lottery_code' => $lotteryCode,
            'status' => $status,
            'group_by' => $groupBy
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        try {
            $lotteryService = new LotteryService();
            $stats = $lotteryService->getDrawStatistics($params);
            
            // 记录统计查询日志
            Log::info('管理员查询开奖统计', [
                'admin_id' => $this->auth->id,
                'params' => $params
            ]);
            
            $this->success('', $stats);
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
     * 获取最新开奖结果
     */
    public function getLatest(): void
    {
        $lotteryCode = $this->request->param('lottery_code');
        $limit = $this->request->param('limit', 10);

        try {
            $query = $this->model->withJoin(['lotteryType']);
            
            if ($lotteryCode) {
                $query->where('lottery_draw.lottery_code', $lotteryCode);
            }
            
            $results = $query
                ->where('lottery_draw.status', 'DRAWN')
                ->order('lottery_draw.draw_time', 'desc')
                ->limit($limit)
                ->select();
            
            $this->success('', $results);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 重写index方法，优化查询性能
     */
    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        // 格式化数据
        foreach ($res->items() as $item) {
            // 格式化金额字段（从分转换为元）
            $item->total_sales = $item->total_sales / 100;
            $item->prize_pool = $item->prize_pool / 100;
            $item->total_bet_amount = $item->total_bet_amount / 100;
            $item->total_win_amount = $item->total_win_amount;
            
            // 解析开奖详情JSON
            if ($item->draw_result && is_string($item->draw_result)) {
                $item->draw_result = json_decode($item->draw_result, true);
            }
        }

        $this->success('', [
            'list' => $res->items(),
            'total' => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 获取开奖数据概览
     */
    public function overview(): void
    {
        try {
            $today = strtotime(date('Y-m-d'));
            $yesterday = $today - 86400;
            $thisWeek = strtotime(date('Y-m-d', strtotime('this week')));
            $thisMonth = strtotime(date('Y-m-01'));
            
            // 今日统计
            $todayStats = LotteryDrawModel::where('draw_time', '>=', $today)
                ->where('draw_time', '<', $today + 86400)
                ->field('COUNT(*) as count, SUM(total_bet_amount) as total_bet, SUM(total_win_amount) as total_win, SUM(bet_count) as bet_count, SUM(win_count) as win_count')
                ->find();
            
            // 昨日统计
            $yesterdayStats = LotteryDrawModel::where('draw_time', '>=', $yesterday)
                ->where('draw_time', '<', $today)
                ->field('COUNT(*) as count, SUM(total_bet_amount) as total_bet, SUM(total_win_amount) as total_win, SUM(bet_count) as bet_count, SUM(win_count) as win_count')
                ->find();
            
            // 本周统计
            $weekStats = LotteryDrawModel::where('draw_time', '>=', $thisWeek)
                ->field('COUNT(*) as count, SUM(total_bet_amount) as total_bet, SUM(total_win_amount) as total_win, SUM(bet_count) as bet_count, SUM(win_count) as win_count')
                ->find();
            
            // 本月统计
            $monthStats = LotteryDrawModel::where('draw_time', '>=', $thisMonth)
                ->field('COUNT(*) as count, SUM(total_bet_amount) as total_bet, SUM(total_win_amount) as total_win, SUM(bet_count) as bet_count, SUM(win_count) as win_count')
                ->find();
            
            // 状态统计
            $statusStats = LotteryDrawModel::field('status, COUNT(*) as count')
                ->group('status')
                ->select();
            
            // 格式化统计数据
            $todayData = $this->formatStatsData($todayStats);
            $yesterdayData = $this->formatStatsData($yesterdayStats);
            $weekData = $this->formatStatsData($weekStats);
            $monthData = $this->formatStatsData($monthStats);
            
            // 格式化状态统计
            $statusData = [];
            if ($statusStats) {
                foreach ($statusStats as $item) {
                    $statusData[] = [
                        'status' => $item->status,
                        'count' => (int)$item->count
                    ];
                }
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
            
        $this->success('', [
            'today' => $todayData,
            'yesterday' => $yesterdayData,
            'week' => $weekData,
            'month' => $monthData,
            'status' => $statusData
        ]);
    }
    
    /**
     * 计算投注统计数据
     * @param string $lotteryCode
     * @param string $periodNo
     * @return array
     */
    private function calculateBetStatistics(string $lotteryCode, string $periodNo): array
    {
        $betOrderModel = new \app\common\model\BetOrder();
        
        // 统计该期投注数据
        $stats = $betOrderModel
            ->where('lottery_code', $lotteryCode)
            ->where('period_no', $periodNo)
            ->where('status', 'in', ['PENDING', 'WINNING', 'LOSING', 'PAID'])
            ->field([
                'COUNT(*) as bet_count',
                'SUM(bet_amount) as total_bet_amount'
            ])
            ->find();
        
        return [
            'bet_count' => (int)($stats['bet_count'] ?? 0),
            'total_bet_amount' => (int)($stats['total_bet_amount'] ?? 0)
        ];
    }
    
    /**
     * 验证开奖数据
     */
    private function validateDrawData(array &$data): void
    {
        // 基础验证
        $validate = Validate::rule([
            'lottery_code' => 'require|alphaNum|length:2,20',
            'period_no' => 'require|alphaNum|length:1,50',
            'draw_numbers' => 'require|length:1,200',
            'draw_time' => 'integer|gt:0',
            'remark' => 'max:500'
        ]);
        
        if (!$validate->check($data)) {
            throw new Exception($validate->getError());
        }
        
        // 验证彩种是否存在且启用
        $lotteryType = LotteryType::where('type_code', $data['lottery_code'])
            ->where('status', 1)
            ->find();
        if (!$lotteryType) {
            throw new Exception('彩种不存在或已禁用');
        }
        
        $data['lottery_type_id'] = $lotteryType->id;
        
        // 验证开奖号码格式（根据彩种规则）
        $this->validateDrawNumbers($data['draw_numbers'], $lotteryType);
    }
    
    /**
     * 验证开奖号码格式
     */
    private function validateDrawNumbers(string $drawNumbers, $lotteryType): void
    {
        // 基础格式验证：数字和逗号分隔
        if (!preg_match('/^[0-9,]+$/', $drawNumbers)) {
            throw new Exception('开奖号码格式错误，只能包含数字和逗号');
        }
        
        $numbers = explode(',', $drawNumbers);
        
        // 检查号码数量（可根据彩种配置进行更详细的验证）
        if (count($numbers) < 1 || count($numbers) > 20) {
            throw new Exception('开奖号码数量不正确');
        }
        
        // 检查每个号码是否为有效数字
        foreach ($numbers as $number) {
            if (!is_numeric($number) || $number < 0 || $number > 99) {
                throw new Exception('开奖号码范围错误，应为0-99之间的数字');
            }
        }
    }

    /**
     * 格式化统计数据
     */
    private function formatStatsData($stats): array
    {
        if (!$stats) {
            return [
                'count' => 0,
                'total_bet' => 0,
                'total_win' => 0,
                'bet_count' => 0,
                'win_count' => 0
            ];
        }
        
        // 转换为数组并处理空值
        $data = [
            'count' => (int)($stats->count ?? 0),
            'total_bet' => (float)($stats->total_bet ?? 0), // 从分转换为元
            'total_win' => (float)($stats->total_win ?? 0), // 从分转换为元
            'bet_count' => (int)($stats->bet_count ?? 0),
            'win_count' => (int)($stats->win_count ?? 0)
        ];
        
        return $data;
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}