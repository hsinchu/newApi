<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use app\common\model\RechargeOrder as RechargeOrderModel;
use app\common\model\User;
use app\common\model\PaymentChannel;
use app\service\PaymentService;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 充值订单管理
 */
class RechargeOrder extends Backend
{
    /**
     * RechargeOrder模型对象
     * @var RechargeOrderModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['order_no', 'third_order_no'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new RechargeOrderModel();
    }

    /**
     * 查看
     */
    public function index(): void
    {
        // 如果是select返回
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        
        // 获取主表别名
        $modelTable = strtolower($this->model->getTable());
        $mainAlias = $alias[$modelTable];
        
        // 添加关联查询
        $res = $this->model
            ->withoutGlobalScope()
            ->alias($mainAlias)
            ->leftJoin('fa_user u', 'u.id = ' . $mainAlias . '.user_id')
            ->leftJoin('fa_payment_channel pc', 'pc.id = ' . $mainAlias . '.channel_id')
            ->field($mainAlias . '.*, u.username, u.nickname, u.avatar, pc.internal_name as channel_name, pc.channel_code as payment_channel')
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $list = [];
        foreach ($res->items() as $item) {
            $list[] = [
                'id' => $item['id'],
                'order_no' => $item['order_no'],
                'trade_no' => $item['trade_no'],
                'user_id' => $item['user_id'],
                'username' => $item['username'],
                'nickname' => $item['nickname'],
                'avatar' => $item['avatar'],
                'amount' => $item['amount'],
                'actual_amount' => $item['actual_amount'],
                'fee_amount' => $item['fee_amount'],
                'gift_amount' => $item['gift_amount'],
                'status' => $item['status'],
                'status_text' => $this->getStatusText($item['status']),
                'payment_method' => $item['payment_method'],
                'payment_channel' => $item['payment_channel'],
                'channel_name' => $item['channel_name'],
                'payment_url' => $item['payment_url'],
                'qr_code' => $item['qr_code'],
                'client_ip' => $item['client_ip'],
                'notify_count' => $item['notify_count'],
                'last_notify_time' => $item['last_notify_time'],
                'success_time' => $item['success_time'],
                'expire_time' => $item['expire_time'],
                'remark' => $item['remark'],
                'admin_remark' => $item['admin_remark'],
                'create_time' => $item['create_time'],
                'update_time' => $item['update_time'],
            ];
        }

        $this->success('', [
            'list'   => $list,
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 添加
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);

            $result = false;
            Db::startTrans();
            try {
                // 验证用户
                $user = User::find($data['user_id']);
                if (!$user) {
                    throw new ValidateException('用户不存在');
                }

                // 验证支付通道
                $channel = PaymentChannel::find($data['payment_channel_id']);
                if (!$channel) {
                    throw new ValidateException('支付通道不存在');
                }

                // 创建充值订单
                $orderData = [
                    'user_id' => $data['user_id'],
                    'payment_channel_id' => $data['payment_channel_id'],
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'] ?? '',
                    'client_ip' => $this->request->ip(),
                    'user_agent' => $this->request->header('user-agent'),
                    'remark' => $data['remark'] ?? '',
                ];

                $order = PaymentService::createRechargeOrder($orderData);
                $result = true;
                Db::commit();
            } catch (ValidateException|\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->error(__('Parameter error'));
    }

    /**
     * 编辑
     */
    public function edit(): void
    {
        $id  = $this->request->param($this->model->getPk());
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);

            $result = false;
            Db::startTrans();
            try {
                // 只允许修改部分字段
                $allowedFields = ['remark', 'fail_reason'];
                $updateData = [];
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        $updateData[$field] = $data[$field];
                    }
                }

                if ($updateData) {
                    $result = $row->save($updateData);
                }
                Db::commit();
            } catch (ValidateException|\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        // 加载关联数据
        $row->load(['user', 'paymentChannel']);

        $this->success('', [
            'row' => $row
        ]);
    }

    /**
     * 删除
     */
    public function del(): void
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        if (is_array($ids)) {
            $ids = array_filter($ids);
        } else {
            $ids = array_filter(explode(',', $ids));
        }

        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $count = 0;
        Db::startTrans();
        try {
            foreach ($ids as $id) {
                $row = $this->model->find($id);
                if ($row && $row->status == 'PENDING') { // 只能删除待支付的订单
                    $count += $row->delete();
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(__('Deleted successfully'));
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status): string
    {
        $statusMap = [
            'PENDING' => '待支付',
            'PROCESSING' => '处理中',
            'SUCCESS' => '支付成功',
            'FAILED' => '支付失败',
            'CANCELLED' => '已取消',
            'TIMEOUT' => '已超时',
        ];
        return $statusMap[$status] ?? '未知状态';
    }

    /**
     * 手动处理支付成功
     */
    public function handleSuccess(): void
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        $order = $this->model->find($id);
        if (!$order) {
            $this->error('订单不存在');
        }
        
        if ($order->status != 'PENDING') {
            $this->error('只能处理待支付的订单');
        }
        
        try {
            $order->status = 'SUCCESS';
            $order->success_time = time();
            $order->admin_remark = '管理员手动处理成功';
            $order->save();
            $this->success('处理成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 手动处理支付失败
     */
    public function handleFailed(): void
    {
        $id = $this->request->param('id');
        $reason = $this->request->param('reason', '管理员手动处理');
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $order = $this->model->find($id);
        if (!$order) {
            $this->error('订单不存在');
        }
        
        if ($order->status != 'PENDING') {
            $this->error('只能处理待支付的订单');
        }
        
        try {
            $order->status = 'FAILED';
            $order->admin_remark = $reason;
            $order->save();
            $this->success('处理成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 取消订单
     */
    public function cancelOrder(): void
    {
        $id = $this->request->param('id');
        $reason = $this->request->param('reason', '管理员取消');
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $order = $this->model->find($id);
        if (!$order) {
            $this->error('订单不存在');
        }
        
        try {
            $order->status = 'CANCELLED';
            $order->admin_remark = $reason;
            $order->save();
            $this->success('取消成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取订单统计
     */
    public function getStats(): void
    {
        $startDate = $this->request->param('start_date', date('Y-m-d', strtotime('-7 days')));
        $endDate = $this->request->param('end_date', date('Y-m-d'));
        
        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime = strtotime($endDate . ' 23:59:59');
        
        // 总订单数
        $totalCount = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->count();
        
        // 成功订单数和金额
        $successStats = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->where('status', 'SUCCESS')
            ->field('COUNT(*) as count, SUM(actual_amount) as amount')
            ->find();
        
        // 失败订单数
        $failedCount = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->where('status', 'FAILED')
            ->count();
        
        // 待支付订单数
        $pendingCount = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->where('status', 'PENDING')
            ->count();
        
        // 处理中订单数
        $processingCount = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->where('status', 'PROCESSING')
            ->count();
        
        // 已取消订单数
        $cancelledCount = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->where('status', 'CANCELLED')
            ->count();
        
        // 超时订单数
        $timeoutCount = $this->model
            ->where('create_time', 'between', [$startTime, $endTime])
            ->where('status', 'TIMEOUT')
            ->count();
        
        $stats = [
            'total_count' => $totalCount,
            'success_count' => $successStats['count'] ?? 0,
            'success_amount' => number_format($successStats['amount'] ?? 0, 2),
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'processing_count' => $processingCount,
            'cancelled_count' => $cancelledCount,
            'timeout_count' => $timeoutCount,
            'success_rate' => $totalCount > 0 ? round(($successStats['count'] ?? 0) / $totalCount * 100, 2) : 0,
        ];
        
        $this->success('', $stats);
    }

    /**
     * 导出订单
     */
    public function export(): void
    {
        $startDate = $this->request->param('start_date');
        $endDate = $this->request->param('end_date');
        $status = $this->request->param('status');
        
        $where = [];
        if ($startDate && $endDate) {
            $startTime = strtotime($startDate . ' 00:00:00');
            $endTime = strtotime($endDate . ' 23:59:59');
            $where[] = ['create_time', 'between', [$startTime, $endTime]];
        }
        
        if ($status !== '') {
            $where[] = ['status', '=', $status];
        }
        
        $orders = $this->model
            ->leftJoin('user u', 'u.id = recharge_order.user_id')
            ->leftJoin('payment_channel pc', 'pc.id = recharge_order.channel_id')
            ->field('recharge_order.*, u.username, u.nickname, pc.internal_name as channel_name, pc.channel_code as payment_channel')
            ->where($where)
            ->order('id', 'desc')
            ->limit(5000) // 限制导出数量
            ->select();
        
        $data = [];
        foreach ($orders as $order) {
            $data[] = [
                '订单号' => $order['order_no'],
                '第三方交易号' => $order['trade_no'],
                '用户名' => $order['username'],
                '用户昵称' => $order['nickname'],
                '充值金额' => $order['amount'],
                '实际到账' => $order['actual_amount'],
                '手续费' => $order['fee_amount'],
                '赠送金额' => $order['gift_amount'],
                '支付方式' => $order['payment_method'],
                '支付通道' => $order['payment_channel'],
                '通道名称' => $order['channel_name'],
                '订单状态' => $this->getStatusText($order['status']),
                '客户端IP' => $order['client_ip'],
                '通知次数' => $order['notify_count'],
                '创建时间' => $order['create_time'] ? date('Y-m-d H:i:s', $order['create_time']) : '',
                '成功时间' => $order['success_time'] ? date('Y-m-d H:i:s', $order['success_time']) : '',
                '过期时间' => $order['expire_time'] ? date('Y-m-d H:i:s', $order['expire_time']) : '',
                '备注' => $order['remark'],
                '管理员备注' => $order['admin_remark'],
            ];
        }
        
        $this->success('', [
            'data' => $data,
            'filename' => '充值订单_' . date('YmdHis') . '.xlsx'
        ]);
    }
}