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
     * 添加开奖结果
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->auth->id;
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 验证数据
                $this->validateDrawData($data);
                
                // 检查期号是否重复
                $exists = $this->model
                    ->where('lottery_code', $data['lottery_code'])
                    ->where('period_no', $data['period_no'])
                    ->find();
                if ($exists) {
                    throw new Exception('该期号已存在开奖结果');
                }
                
                // 设置开奖时间
                if (empty($data['draw_time'])) {
                    $data['draw_time'] = time();
                } else {
                    $data['draw_time'] = strtotime($data['draw_time']);
                }
                
                // 设置状态为已开奖
                $data['status'] = 'DRAWN';
                
                // 设置创建者
                $data['created_by'] = $this->auth->id;
                
                $result = $this->model->save($data);
                $this->model->commit();
                
                // 记录操作日志
                Log::info('管理员添加开奖结果', [
                    'admin_id' => $this->auth->id,
                    'draw_id' => $this->model->id,
                    'lottery_code' => $data['lottery_code'],
                    'period_no' => $data['period_no'],
                    'draw_numbers' => $data['draw_numbers']
                ]);
                
                // 将派奖任务推送到Redis队列
                if ($result) {
                    $queueService = new \app\service\QueueService();
                    $queueData = [
                         'draw_id' => $this->model->id,
                         'draw_numbers' => $data['draw_numbers'],
                         'lottery_type' => $this->model->lottery_type,
                         'period_no' => $this->model->period_no
                     ];
                    
                    $queueResult = $queueService->push('settle', $queueData);
                    
                    if (!$queueResult) {
                        throw new Exception('派奖任务推送到队列失败');
                    }
                    
                    Log::info('派奖任务已推送到队列', [
                        'draw_id' => $this->model->id,
                        'period_no' => $data['period_no'],
                        'draw_numbers' => $data['draw_numbers']
                    ]);
                }
            } catch (Exception $e) {
                $this->model->rollback();
                Log::error('添加开奖结果失败', [
                    'admin_id' => $this->auth->id,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        // 获取彩种列表供选择
        $lotteryTypes = LotteryType::where('status', 1)->field('id,type_code,type_name')->select();
        
        $this->success('', [
            'lottery_types' => $lotteryTypes,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 编辑开奖结果
     */
    public function edit(): void
    {
        $id = $this->request->param($this->model->getPk());
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        // 已结算的开奖结果不允许修改
        if ($row->status === LotteryDrawModel::STATUS_SETTLED) {
            $this->error('已结算的开奖结果不允许修改');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);
            $result = false;
            $this->model->startTrans();
            try {
                // 验证数据
                $this->validateDrawData($data);
                
                // 检查期号是否重复（排除自己）
                $exists = $this->model
                    ->where('lottery_code', $data['lottery_code'])
                    ->where('period_no', $data['period_no'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new Exception('该期号已存在开奖结果');
                }
                
                $result = $row->save($data);
                $this->model->commit();
                
                // 如果修改了开奖号码，重新推送派奖任务到队列
                if ($result && isset($data['draw_numbers'])) {
                    $queueService = new \app\service\QueueService();
                    $queueData = [
                         'draw_id' => $id,
                         'draw_numbers' => $data['draw_numbers'],
                         'lottery_type' => $row->lottery_type,
                         'period_no' => $row->period_no
                     ];
                    
                    $queueResult = $queueService->push('settle', $queueData);
                    
                    if (!$queueResult) {
                        throw new Exception('派奖任务推送到队列失败');
                    }
                    
                    Log::info('修改开奖号码后派奖任务已推送到队列', [
                        'draw_id' => $id,
                        'draw_numbers' => $data['draw_numbers']
                    ]);
                }
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        $this->success('', [
            'row' => $row,
            'remark' => get_route_remark(),
        ]);
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
            
            $result = $draw->save($updateData);
            
            if ($result) {
                $this->model->commit();
                
                Log::info('管理员手动开奖', [
                    'admin_id' => $this->auth->id,
                    'draw_id' => $id,
                    'lottery_code' => $draw->lottery_code,
                    'period_no' => $draw->period_no,
                    'draw_numbers' => $drawNumbers,
                    'remark' => $remark
                ]);
                
                // 将派奖任务推送到Redis队列
                $queueService = new \app\service\QueueService();
                $queueData = [
                     'draw_id' => $id,
                     'draw_numbers' => $drawNumbers,
                     'lottery_type' => $draw->lottery_type,
                     'period_no' => $draw->period_no
                 ];
                
                $queueResult = $queueService->push('settle', $queueData);
                
                if (!$queueResult) {
                    throw new Exception('派奖任务推送到队列失败');
                }
                
                Log::info('手动开奖派奖任务已推送到队列', [
                    'draw_id' => $id,
                    'period_no' => $draw->period_no,
                    'draw_numbers' => $drawNumbers
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
     * 结算开奖结果
     */
    public function settle(): void
    {
        $id = $this->request->param('id');
        $forceSettle = $this->request->param('force_settle', false);
        
        // 参数验证
        $validate = Validate::rule([
            'id' => 'require|integer|gt:0',
            'force_settle' => 'boolean'
        ]);
        
        if (!$validate->check(['id' => $id, 'force_settle' => $forceSettle])) {
            $this->error($validate->getError());
        }

        try {
            // 检查开奖结果是否存在
            $draw = $this->model->find($id);
            if (!$draw) {
                $this->error('开奖结果不存在');
            }
            
            // 检查状态
            if ($draw->status === LotteryDrawModel::STATUS_SETTLED && !$forceSettle) {
                $this->error('该开奖结果已结算，如需重新结算请勾选强制结算');
            }
            
            if ($draw->status !== LotteryDrawModel::STATUS_DRAWN && $draw->status !== LotteryDrawModel::STATUS_SETTLED) {
                $this->error('只能结算已开奖的结果');
            }
            
            // 将派奖任务推送到Redis队列
            $queueService = new \app\service\QueueService();
            $queueData = [
                'draw_id' => $id,
                'draw_numbers' => $draw->draw_numbers,
                'lottery_type' => $draw->lottery_type,
                'period_no' => $draw->period_no,
                'force_settle' => $forceSettle,
                'operator_id' => $this->auth->id
            ];
            
            $queueResult = $queueService->push('settle', $queueData);
            
            if ($queueResult) {
                Log::info('管理员结算开奖结果任务已推送到队列', [
                    'admin_id' => $this->auth->id,
                    'draw_id' => $id,
                    'lottery_type' => $draw->lottery_type,
                    'period_no' => $draw->period_no,
                    'force_settle' => $forceSettle
                ]);
                $this->success('结算任务已提交，正在处理中');
            } else {
                $this->error('结算任务提交失败');
            }
        } catch (Exception $e) {
            Log::error('结算开奖结果失败', [
                'admin_id' => $this->auth->id,
                'draw_id' => $id,
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
            $item->total_win_amount = $item->total_win_amount / 100;
            
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