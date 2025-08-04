<?php

namespace app\admin\controller\lottery;

use app\common\model\BetOrder as BetOrderModel;
use app\common\model\LotteryType;
use app\common\model\User;
use app\common\controller\Backend;
use app\service\LotteryService;
use think\facade\Log;
use think\facade\Validate;
use Exception;

/**
 * 投注订单管理控制器
 */
class BetOrder extends Backend
{
    /**
     * BetOrder模型对象
     * @var BetOrderModel
     */
    protected object $model;
    
    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];
    
    protected array $withJoinTable = ['user', 'lotteryType'];
    
    protected string|array $quickSearchField = ['order_no', 'user.username', 'user.nickname', 'lottery_code', 'period_no'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new BetOrderModel();
    }

    /**
     * 添加 - 禁用
     */
    public function add(): void
    {
        $this->error('投注订单不支持手动添加');
    }

    /**
     * 编辑 - 禁用
     */
    public function edit(): void
    {
        $this->error('投注订单不支持编辑');
    }

    /**
     * 删除 - 禁用
     */
    public function del(): void
    {
        $this->error('投注订单不支持删除');
    }

    /**
     * 查看详情
     */
    public function info(): void
    {
        $id = $this->request->param('id');
        
        // 参数验证
        $validate = Validate::rule([
            'id' => 'require|integer|gt:0'
        ]);
        
        if (!$validate->check(['id' => $id])) {
            $this->error($validate->getError());
        }

        try {
            $order = $this->model
                ->withJoin(['user', 'lotteryType'])
                ->where('bet_order.id', $id)
                ->find();

            if (!$order) {
                $this->error('订单不存在');
            }

            // 记录查看日志
            Log::info('管理员查看订单详情', [
                'admin_id' => $this->auth->id,
                'order_id' => $id,
                'order_no' => $order->order_no
            ]);
        } catch (Exception $e) {
            Log::error('查看订单详情失败', [
                'admin_id' => $this->auth->id,
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->error('查看订单详情失败：' . $e->getMessage());
        }

        $this->success('', [
            'order' => $order,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 取消订单
     */
    public function cancel(): void
    {
        $id = $this->request->param('id');
        $reason = $this->request->param('reason', '管理员取消');
        
        // 参数验证
        $validate = Validate::rule([
            'id' => 'require|integer|gt:0',
            'reason' => 'max:200'
        ]);
        
        if (!$validate->check(['id' => $id, 'reason' => $reason])) {
            $this->error($validate->getError());
        }

        try {
            // 检查订单是否存在
            $order = $this->model->find($id);
            if (!$order) {
                $this->error('订单不存在');
            }
            
            // 检查订单状态
            if (!in_array($order->status, ['PENDING', 'CONFIRMED'])) {
                $this->error('当前状态的订单不能取消');
            }
            
            $lotteryService = new LotteryService();
            $result = $lotteryService->cancelBetOrder($id, $reason, $this->auth->id);
            
            if ($result) {
                Log::info('管理员取消订单', [
                    'admin_id' => $this->auth->id,
                    'order_id' => $id,
                    'order_no' => $order->order_no,
                    'reason' => $reason
                ]);
                $this->success('订单已取消');
            } else {
                $this->error('取消失败');
            }
        } catch (Exception $e) {
            Log::error('取消订单失败', [
                'admin_id' => $this->auth->id,
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 手动结算订单
     */
    public function settle(): void
    {
        $id = $this->request->param('id');
        $winAmount = $this->request->param('win_amount', 0);
        $reason = $this->request->param('reason', '管理员手动结算');
        
        // 参数验证
        $validate = Validate::rule([
            'id' => 'require|integer|gt:0',
            'win_amount' => 'require|number|egt:0',
            'reason' => 'max:200'
        ]);
        
        if (!$validate->check(['id' => $id, 'win_amount' => $winAmount, 'reason' => $reason])) {
            $this->error($validate->getError());
        }

        try {
            // 检查订单是否存在
            $order = $this->model->find($id);
            if (!$order) {
                $this->error('订单不存在');
            }
            
            // 检查订单状态
            if (!in_array($order->status, ['CONFIRMED', 'DRAWN'])) {
                $this->error('当前状态的订单不能手动结算');
            }
            
            // 转换金额为分
            $winAmountCent = intval($winAmount * 100);
            
            $lotteryService = new LotteryService();
            $result = $lotteryService->settleBetOrder($id, $winAmountCent, $reason, $this->auth->id);
            
            if ($result) {
                Log::info('管理员手动结算订单', [
                    'admin_id' => $this->auth->id,
                    'order_id' => $id,
                    'order_no' => $order->order_no,
                    'win_amount' => $winAmountCent,
                    'reason' => $reason
                ]);
                $this->success('订单已结算');
            } else {
                $this->error('结算失败');
            }
        } catch (Exception $e) {
            Log::error('手动结算订单失败', [
                'admin_id' => $this->auth->id,
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取订单统计
     */
    public function statistics(): void
    {
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $lotteryTypeId = $this->request->param('lottery_type_id');
        $userId = $this->request->param('user_id');
        $status = $this->request->param('status');
        $groupBy = $this->request->param('group_by', 'day'); // day, hour, month

        // 参数验证
        $validate = Validate::rule([
            'start_time' => 'date',
            'end_time' => 'date',
            'lottery_type_id' => 'integer|gt:0',
            'user_id' => 'integer|gt:0',
            'status' => 'in:PENDING,CONFIRMED,CANCELLED,WINNING,PAID,LOSING,REFUNDED',
            'group_by' => 'in:day,hour,month'
        ]);
        
        $params = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'lottery_type_id' => $lotteryTypeId,
            'user_id' => $userId,
            'status' => $status,
            'group_by' => $groupBy
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        try {
            $lotteryService = new LotteryService();
            $stats = $lotteryService->getBetOrderStatistics($params);
            
            // 记录统计查询日志
            Log::info('管理员查询订单统计', [
                'admin_id' => $this->auth->id,
                'params' => $params
            ]);
            
            $this->success('', $stats);
        } catch (Exception $e) {
            Log::error('获取订单统计失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 根据用户ID获取订单
     */
    public function getByUser(): void
    {
        $userId = $this->request->param('user_id');
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 15);
        $status = $this->request->param('status');
        
        // 参数验证
        $validate = Validate::rule([
            'user_id' => 'require|integer|gt:0',
            'page' => 'integer|gt:0',
            'limit' => 'integer|between:1,100',
            'status' => 'in:PENDING,CONFIRMED,CANCELLED,WINNING,PAID,LOSING,REFUNDED'
        ]);
        
        if (!$validate->check(['user_id' => $userId, 'page' => $page, 'limit' => $limit, 'status' => $status])) {
            $this->error($validate->getError());
        }

        try {
            // 检查用户是否存在
            $user = User::find($userId);
            if (!$user) {
                $this->error('用户不存在');
            }
            
            $query = $this->model
                ->withJoin(['lotteryType'])
                ->where('bet_order.user_id', $userId);
                
            if ($status) {
                $query->where('bet_order.status', $status);
            }

            $orders = $query
                ->order('bet_order.create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);

            $this->success('', [
                'list' => $orders->items(),
                'total' => $orders->total(),
                'user_info' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname
                ]
            ]);
        } catch (Exception $e) {
            Log::error('获取用户订单失败', [
                'admin_id' => $this->auth->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 根据彩种获取订单
     */
    public function getByLottery(): void
    {
        $lotteryTypeId = $this->request->param('lottery_type_id');
        $periodNo = $this->request->param('period_no');
        
        if (!$lotteryTypeId) {
            $this->error('彩种ID不能为空');
        }

        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 15);

        $query = $this->model
            ->withJoin(['user'])
            ->where('bet_order.lottery_type_id', $lotteryTypeId);
            
        if ($periodNo) {
            $query->where('bet_order.period_no', $periodNo);
        }

        $orders = $query
            ->order('bet_order.create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);

        $this->success('', [
            'list' => $orders->items(),
            'total' => $orders->total(),
        ]);
    }

    /**
     * 导出订单
     */
    public function export(): void
    {
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');
        $lotteryTypeId = $this->request->param('lottery_type_id');
        $userId = $this->request->param('user_id');
        $status = $this->request->param('status');
        $maxRows = $this->request->param('max_rows', 10000); // 限制导出数量

        // 参数验证
        $validate = Validate::rule([
            'start_time' => 'date',
            'end_time' => 'date',
            'lottery_type_id' => 'integer|gt:0',
            'user_id' => 'integer|gt:0',
            'status' => 'in:PENDING,CONFIRMED,CANCELLED,WINNING,PAID,LOSING,REFUNDED',
            'max_rows' => 'integer|between:1,50000'
        ]);
        
        $params = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'lottery_type_id' => $lotteryTypeId,
            'user_id' => $userId,
            'status' => $status,
            'max_rows' => $maxRows
        ];
        
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }

        try {
            $query = $this->model->withJoin(['user', 'lotteryType']);

            // 时间筛选
            if ($startTime) {
                $query->where('bet_order.create_time', '>=', strtotime($startTime));
            }
            if ($endTime) {
                $query->where('bet_order.create_time', '<=', strtotime($endTime . ' 23:59:59'));
            }

            // 彩种筛选
            if ($lotteryTypeId) {
                $query->where('bet_order.lottery_type_id', $lotteryTypeId);
            }

            // 用户筛选
            if ($userId) {
                $query->where('bet_order.user_id', $userId);
            }
            
            // 状态筛选
            if ($status) {
                $query->where('bet_order.status', $status);
            }

            // 限制导出数量
            $orders = $query->order('bet_order.create_time', 'desc')->limit($maxRows)->select();

            $exportData = [];
            $statusMap = [
                'PENDING' => '待确认',
                'CONFIRMED' => '待开奖',
                'CANCELLED' => '已取消',
                'WINNING' => '待派奖',
                'PAID' => '已派奖',
                'LOSING' => '未中奖',
                'REFUNDED' => '已退款'
            ];
            
            foreach ($orders as $order) {
                $exportData[] = [
                    'ID' => $order->id,
                    '订单号' => $order->order_no,
                    '用户名' => $order->user->username ?? '',
                    '用户昵称' => $order->user->nickname ?? '',
                    '彩种名称' => $order->lotteryType->type_name ?? '',
                    '彩种代码' => $order->lottery_code,
                    '期号' => $order->period_no,
                    '投注内容' => $order->bet_content,
                    '单注金额(元)' => number_format($order->bet_amount / 100, 2),
                    '倍数' => $order->multiple,
                    '总金额(元)' => number_format($order->total_amount / 100, 2),
                    '中奖金额(元)' => number_format($order->win_amount / 100, 2),
                    '状态' => $statusMap[$order->status] ?? $order->status,
                    'IP地址' => $order->ip,
                    '创建时间' => date('Y-m-d H:i:s', $order->create_time),
                    '更新时间' => date('Y-m-d H:i:s', $order->update_time)
                ];
            }

            // 记录导出日志
            Log::info('管理员导出订单数据', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'export_count' => count($exportData)
            ]);

            $this->success('', [
                'data' => $exportData,
                'filename' => '投注订单_' . date('YmdHis') . '.csv',
                'total' => count($exportData)
            ]);
        } catch (Exception $e) {
            Log::error('导出订单数据失败', [
                'admin_id' => $this->auth->id,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}