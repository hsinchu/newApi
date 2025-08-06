<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * 提现记录模型
 * @property int    $id              记录ID
 * @property string $order_no        提现订单号
 * @property int    $user_id         用户ID
 * @property int    $account_id      提现账号ID
 * @property string $account_type    账号类型
 * @property string $account_name    账号名称
 * @property string $account_number  账号（脱敏）
 * @property string $bank_name       银行名称
 * @property float  $amount          提现金额
 * @property float  $fee             手续费
 * @property float  $actual_amount   实际到账金额
 * @property int    $status          状态：0-待审核，1-审核通过，2-处理中，3-已完成，4-已拒绝，5-已取消
 * @property string $remark          备注
 * @property string $admin_remark    管理员备注
 * @property int    $admin_id        处理管理员ID
 * @property int    $audit_time      审核时间
 * @property int    $complete_time   完成时间
 * @property int    $create_time     创建时间
 * @property int    $update_time     更新时间
 */
class WithdrawRecord extends Model
{
    protected $name = 'withdraw_record';
    
    protected $schema = [
        'id'             => 'int',
        'order_no'       => 'string',
        'user_id'        => 'int',
        'account_id'     => 'int',
        'account_type'   => 'string',
        'account_name'   => 'string',
        'account_number' => 'string',
        'bank_name'      => 'string',
        'amount'         => 'float',
        'fee'            => 'float',
        'actual_amount'  => 'float',
        'status'         => 'int',
        'remark'         => 'string',
        'admin_remark'   => 'string',
        'admin_id'       => 'int',
        'audit_time'     => 'int',
        'complete_time'  => 'int',
        'create_time'    => 'int',
        'update_time'    => 'int',
    ];
    
    protected $type = [
        'amount'        => 'float',
        'fee'           => 'float',
        'actual_amount' => 'float',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 状态常量
    const STATUS_PENDING = 0;    // 待审核
    const STATUS_APPROVED = 1;   // 审核通过
    const STATUS_COMPLETED = 2;  // 已完成
    const STATUS_REJECTED = 3;   // 已拒绝
    const STATUS_CANCELLED = 4;  // 已取消
    
    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 关联提现账号
     */
    public function withdrawAccount(): BelongsTo
    {
        return $this->belongsTo(WithdrawAccount::class, 'account_id');
    }
    
    /**
     * 关联管理员
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
    
    /**
     * 获取状态名称
     */
    public function getStatusNameAttr($value, $data)
    {
        $statuses = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '审核通过',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $statuses[$data['status']] ?? '未知';
    }
    
    /**
     * 获取状态颜色
     */
    public function getStatusColorAttr($value, $data)
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
        ];
        return $colors[$data['status']] ?? 'secondary';
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo()
    {
        return 'WD' . date('YmdHis') . rand(1000, 9999);
    }
    
    /**
     * 创建提现记录
     */
    public static function createRecord($userId, $accountId, $amount, $fee = 0, $remark = '')
    {
        // 获取账号信息
        $account = WithdrawAccount::find($accountId);
        if (!$account || $account->user_id != $userId) {
            throw new \Exception('提现账号不存在或不属于当前用户');
        }
        
        $actualAmount = $amount - $fee;
        
        $data = [
            'order_no'       => self::generateOrderNo(),
            'user_id'        => $userId,
            'account_id'     => $accountId,
            'account_type'   => $account->type,
            'account_name'   => $account->account_name,
            'account_number' => $account->masked_account,
            'bank_name'      => $account->bank_name ?: '',
            'amount'         => $amount,
            'fee'            => $fee,
            'actual_amount'  => $actualAmount,
            'status'         => self::STATUS_PENDING,
            'remark'         => $remark,
        ];
        
        return self::create($data);
    }
    
    /**
     * 审核通过
     */
    public function approve($adminId, $adminRemark = '')
    {
        $this->status = self::STATUS_APPROVED;
        $this->admin_id = $adminId;
        $this->admin_remark = $adminRemark;
        $this->audit_time = time();
        return $this->save();
    }
    
    /**
     * 拒绝提现
     */
    public function reject($adminId, $adminRemark = '')
    {
        $this->status = self::STATUS_REJECTED;
        $this->admin_id = $adminId;
        $this->admin_remark = $adminRemark;
        $this->audit_time = time();
        return $this->save();
    }
    
    /**
     * 完成提现
     */
    public function complete($adminId, $adminRemark = '')
    {
        $this->status = self::STATUS_COMPLETED;
        $this->admin_id = $adminId;
        $this->admin_remark = $adminRemark;
        $this->complete_time = time();
        return $this->save();
    }
    
    /**
     * 取消提现
     */
    public function cancel($adminRemark = '')
    {
        $this->status = self::STATUS_CANCELLED;
        $this->admin_remark = $adminRemark;
        return $this->save();
    }
}