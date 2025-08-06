<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use app\common\model\WithdrawRecord;
use app\common\model\WithdrawAccount;
use app\common\model\User;
use app\common\model\UserMoneyLog;
use think\facade\Db;
use think\facade\Request;
use think\facade\Cache;

/**
 * 提现管理
 */
class Withdraw extends Backend
{
    /**
     * WithdrawRecord模型对象
     * @var WithdrawRecord
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['order_no', 'user.username'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new WithdrawRecord();
    }
    
    /**
     * 重写查询参数构建器，处理状态字段的字符串到数字转换
     */
    public function queryBuilder(): array
    {
        list($where, $alias, $limit, $order) = parent::queryBuilder();
        
        // 处理状态字段的转换
        foreach ($where as &$condition) {
            if (is_array($condition) && count($condition) >= 3) {
                $field = $condition[0];
                $operator = $condition[1];
                $value = $condition[2];
                
                // 检查是否为状态字段查询
                if (str_contains($field, '.status') || $field === 'status') {
                    if ($operator === '=' && is_string($value)) {
                        $numericStatus = $this->convertStatusToNumber($value);
                        if ($numericStatus !== null) {
                            $condition[2] = $numericStatus;
                        }
                    } elseif (in_array($operator, ['IN', 'NOT IN']) && is_array($value)) {
                        $convertedValues = [];
                        foreach ($value as $val) {
                            $numericStatus = $this->convertStatusToNumber($val);
                            if ($numericStatus !== null) {
                                $convertedValues[] = $numericStatus;
                            }
                        }
                        if (!empty($convertedValues)) {
                            $condition[2] = $convertedValues;
                        }
                    }
                }
            }
        }
        
        return [$where, $alias, $limit, $order];
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
            ->leftJoin('fa_withdraw_account wa', 'wa.id = ' . $mainAlias . '.account_id')
            ->leftJoin('fa_admin a', 'a.id = ' . $mainAlias . '.admin_id')
            ->field($mainAlias . '.*, u.username, u.nickname, wa.type as account_type, wa.account_name, wa.account_number, wa.bank_name, a.username as process_admin')
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $list = [];
        foreach ($res->items() as $item) {
            $list[] = [
                'id' => $item['id'],
                'order_no' => $item['order_no'],
                'user' => [
                    'username' => $item['username'],
                    'nickname' => $item['nickname']
                ],
                'account_type_name' => $this->getAccountTypeName($item['account_type']),
                'account_info' => [
                    'account_name' => $item['account_name'],
                    'account_number' => $item['account_number'],
                    'bank_name' => $item['bank_name']
                ],
                'amount' => $item['amount'],
                'fee' => $item['fee'],
                'actual_amount' => $item['actual_amount'],
                'status' => $this->convertStatusToString($item['status']),
                'create_time' => $item['create_time'],
                'audit_time' => $item['audit_time'],
                'process_time' => $item['audit_time'] ?: $item['complete_time'],
                'process_admin' => $item['process_admin'],
                'admin_remark' => $item['admin_remark']
            ];
        }

        $this->success('', [
            'list'   => $list,
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 获取提现记录列表（兼容旧接口）
     */
    public function list()
    {
        $page = Request::param('page', 1);
        $limit = Request::param('limit', 20);
        $orderNo = Request::param('orderNo', '');
        $username = Request::param('username', '');
        $status = Request::param('status', '');
        $startTime = Request::param('startTime', '');
        $endTime = Request::param('endTime', '');

        $where = [];
        
        if (!empty($orderNo)) {
            $where[] = ['order_no', 'like', '%' . $orderNo . '%'];
        }
        
        if (!empty($status)) {
            $where[] = ['status', '=', $status];
        }
        
        if (!empty($startTime)) {
            $where[] = ['create_time', '>=', $startTime];
        }
        
        if (!empty($endTime)) {
            $where[] = ['create_time', '<=', $endTime];
        }
        
        // 如果有用户名筛选，需要关联用户表
        $query = WithdrawRecord::with(['user', 'withdrawAccount', 'admin'])
            ->where($where)
            ->order('create_time', 'desc');
            
        if (!empty($username)) {
            $query->whereHas('user', function($query) use ($username) {
                $query->where('username', 'like', '%' . $username . '%');
            });
        }
        
        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page
        ]);
        
        $data = [];
        foreach ($list->items() as $item) {
            $data[] = [
                'id' => $item->id,
                'orderNo' => $item->order_no,
                'username' => $item->user->username ?? '',
                'accountType' => $item->withdrawAccount->type ?? '',
                'accountTypeName' => $item->withdrawAccount->type_name ?? '',
                'accountNumber' => $item->withdrawAccount->account_number ?? '',
                'maskedAccountNumber' => $item->withdrawAccount->masked_account ?? '',
                'accountName' => $item->withdrawAccount->account_name ?? '',
                'bankName' => $item->withdrawAccount->bank_name ?? '',
                'amount' => $item->amount,
                'fee' => $item->fee,
                'actualAmount' => $item->actual_amount,
                'status' => $item->status,
                'createTime' => $item->create_time,
                'auditTime' => $item->audit_time,
                'processAdmin' => $item->admin->username ?? '',
                'remark' => $item->remark
            ];
        }
        
        return $this->success('操作成功', [
            'list' => $data,
            'total' => $list->total(),
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * 获取账户类型名称
     */
    private function getAccountTypeName($type): string
    {
        $typeMap = [
            'bank' => '银行卡',
            'alipay' => '支付宝',
            'wechat' => '微信',
            'usdt' => 'USDT'
        ];
        return $typeMap[$type] ?? '未知类型';
    }
    
    /**
     * 将数字状态转换为字符串状态
     */
    private function convertStatusToString($status)
    {
        $statusMap = [
            0 => 'pending',   // 待审核
            1 => 'approved',  // 审核通过
            2 => 'completed', // 已完成
            3 => 'rejected',  // 已拒绝
            4 => 'cancelled'  // 已取消
        ];
        return $statusMap[$status] ?? 'pending';
    }
    
    /**
     * 将字符串状态转换为数字状态
     */
    private function convertStatusToNumber($status)
    {
        $statusMap = [
            'pending' => 0,   // 待审核
            'approved' => 1,  // 审核通过
            'completed' => 2, // 已完成
            'rejected' => 3,  // 已拒绝
            'cancelled' => 4  // 已取消
        ];
        return $statusMap[$status] ?? null;
    }

    /**
     * 通过提现申请
     */
    public function approve(): void
    {
        $id = Request::param('id');
        $remark = Request::param('remark', '审核通过');
        
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        $record = WithdrawRecord::find($id);
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record->status !== WithdrawRecord::STATUS_PENDING) {
            $this->error('当前状态不允许此操作');
        }
        
        Db::startTrans();
        try {
            // 更新记录状态
            $record->status = WithdrawRecord::STATUS_APPROVED;
            $record->audit_time = time();
            $record->admin_id = $this->auth->id;
            $record->admin_remark = $remark;
            $record->save();
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
        $this->success('操作成功');
    }
    
    /**
     * 拒绝提现申请
     */
    public function reject(): void
    {
        $id = Request::param('id');
        $remark = Request::param('remark', '');
        
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        $record = WithdrawRecord::find($id);
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record->status !== WithdrawRecord::STATUS_PENDING) {
            $this->error('当前状态不允许此操作');
        }

        Db::startTrans();
        try {
            // 更新记录状态
            $record->status = WithdrawRecord::STATUS_REJECTED;
            $record->audit_time = time();
            $record->admin_id = $this->auth->id;
            $record->admin_remark = $remark;
            $record->save();
            
            // 退还用户资金
            $financeService = new \app\service\FinanceService();
            $financeService->adjustUserBalance(
                $record->user_id,
                $record->amount,
                '提现被拒绝，退还资金',
                'WITHDRAW_REFUND_ADD',
            );
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
        $this->success('操作成功');
    }
    
    /**
     * 完成提现
     */
    public function complete(): void
    {
        $id = Request::param('id');
        $remark = Request::param('remark', '');
        
        if (empty($id)) {
            $this->error('参数错误');
        }
        
        $record = WithdrawRecord::find($id);
        if (!$record) {
            $this->error('记录不存在');
        }
        
        if ($record->status !== WithdrawRecord::STATUS_APPROVED) {
            $this->error('当前状态不允许此操作');
        }

        Db::startTrans();
        try {
            // 更新记录状态
            $record->status = WithdrawRecord::STATUS_COMPLETED;
            $record->complete_time = time();
            $record->admin_id = $this->auth->id;
            if ($remark) {
                $record->admin_remark = $remark;
            }
            $record->save();
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
        $this->success('操作成功');
    }
    
    /**
     * 获取提现详情
     */
    public function detail(): void
    {
        $id = $this->request->param('id');
        $record = WithdrawRecord::with(['user', 'withdrawAccount', 'admin'])->find($id);
        if (!$record) {
            $this->error('记录不存在');
        }
        
        $data = [
            'id' => $record->id,
            'order_no' => $record->order_no,
            'user' => [
                'username' => $record->user->username ?? ''
            ],
            'account_type_name' => $this->getAccountTypeName($record->withdrawAccount->type ?? ''),
            'account_info' => [
                'account_name' => $record->withdrawAccount->account_name ?? '',
                'account_number' => $record->withdrawAccount->account_number ?? '',
                'bank_name' => $record->withdrawAccount->bank_name ?? ''
            ],
            'amount' => $record->amount,
            'fee' => $record->fee,
            'actual_amount' => $record->actual_amount,
            'status' => $this->convertStatusToString($record->status),
            'create_time' => $record->create_time,
            'process_time' => $record->audit_time ?: $record->complete_time,
            'process_admin' => $record->admin->username ?? '',
            'admin_remark' => $record->admin_remark
        ];
        
        $this->success('获取成功', $data);
    }
    
    /**
     * 获取提现统计
     */
    public function stats(): void
    {
        $startTime = Request::param('startTime', date('Y-m-d', strtotime('-30 days')));
        $endTime = Request::param('endTime', date('Y-m-d'));
        
        $where = [
            ['create_time', '>=', $startTime . ' 00:00:00'],
            ['create_time', '<=', $endTime . ' 23:59:59']
        ];
        
        // 总申请数量和金额
        $totalCount = WithdrawRecord::where($where)->count();
        $totalAmount = WithdrawRecord::where($where)->sum('amount');
        
        // 各状态统计
        $statusStats = WithdrawRecord::where($where)
            ->field('status, count(*) as count, sum(amount) as amount')
            ->group('status')
            ->select()
            ->toArray();
        
        // 每日统计
        $dailyStats = WithdrawRecord::where($where)
            ->field('DATE(create_time) as date, count(*) as count, sum(amount) as amount')
            ->group('DATE(create_time)')
            ->order('date', 'asc')
            ->select()
            ->toArray();
        
        $this->success('获取成功', [
            'totalCount' => $totalCount,
            'totalAmount' => $totalAmount,
            'statusStats' => $statusStats,
            'dailyStats' => $dailyStats
        ]);
    }
    
    /**
     * 获取提现配置
     */
    public function getConfig(): void
    {
        $config = [
            'minAmount' => Cache::get('withdraw_min_amount', 100),
            'maxAmount' => Cache::get('withdraw_max_amount', 50000),
            'feeRate' => Cache::get('withdraw_fee_rate', 0.01),
            'workingDays' => Cache::get('withdraw_working_days', '1-5'),
            'notice' => Cache::get('withdraw_notice', '提现将在1-3个工作日内到账')
        ];
        
        $this->success('获取成功', $config);
    }
    
    /**
     * 更新提现配置
     */
    public function updateConfig(): void
    {
        $minAmount = Request::param('minAmount');
        $maxAmount = Request::param('maxAmount');
        $feeRate = Request::param('feeRate');
        $workingDays = Request::param('workingDays');
        $notice = Request::param('notice');
        
        if ($minAmount !== null) {
            Cache::set('withdraw_min_amount', $minAmount);
        }
        
        if ($maxAmount !== null) {
            Cache::set('withdraw_max_amount', $maxAmount);
        }
        
        if ($feeRate !== null) {
            Cache::set('withdraw_fee_rate', $feeRate);
        }
        
        if ($workingDays !== null) {
            Cache::set('withdraw_working_days', $workingDays);
        }
        
        if ($notice !== null) {
            Cache::set('withdraw_notice', $notice);
        }
        
        $this->success('更新成功');
    }
}