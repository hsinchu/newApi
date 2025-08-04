<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * RechargeOrder 模型
 * @property int    $id                充值订单ID
 * @property int    $user_id           用户ID
 * @property string $order_no          订单号
 * @property string $third_order_no    第三方订单号
 * @property int    $payment_channel_id 支付通道ID
 * @property int    $amount            充值金额(分)
 * @property int    $actual_amount     实际到账金额(分)
 * @property int    $fee               手续费(分)
 * @property int    $status            订单状态
 * @property string $payment_method    支付方式
 * @property string $channel_name      通道名称
 * @property string $notify_data       通知数据
 * @property string $callback_data     回调数据
 * @property int    $notify_time       通知时间
 * @property int    $success_time      成功时间
 * @property string $fail_reason       失败原因
 * @property string $client_ip         客户端IP
 * @property string $user_agent        用户代理
 * @property string $remark            备注
 * @property int    $create_time       创建时间
 * @property int    $update_time       更新时间
 */
class RechargeOrder extends Model
{
    /**
     * 表名
     */
    protected $name = 'recharge_order';

    /**
     * 状态常量
     */
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_SUCCESS = 'SUCCESS';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_TIMEOUT = 'TIMEOUT';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'                 => 'int',
        'user_id'            => 'int',
        'order_no'           => 'string',
        'trade_no'           => 'string',
        'amount'             => 'decimal',
        'actual_amount'      => 'decimal',
        'fee_amount'         => 'decimal',
        'status'             => 'string',
        'payment_method'     => 'string',
        'payment_channel'    => 'string',
        'payment_code'       => 'string',
        'method_id'          => 'int',
        'channel_id'         => 'int',
        'payment_url'        => 'string',
        'qr_code'            => 'string',
        'client_ip'          => 'string',
        'user_agent'         => 'string',
        'notify_count'       => 'int',
        'last_notify_time'   => 'int',
        'notify_content'     => 'string',
        'success_time'       => 'int',
        'expire_time'        => 'int',
        'remark'             => 'string',
        'admin_remark'       => 'string',
        'create_time'        => 'int',
        'update_time'        => 'int',
    ];

    /**
     * JSON字段
     */
    protected $json = ['notify_content'];

    /**
     * 追加属性
     */
    protected $append = [
        'status_text',
        'amount_yuan',
        'actual_amount_yuan',
        'fee_amount_yuan',
    ];

    protected $autoWriteTimestamp = true;

    public function getAmountYuanAttr($value, $row): string
    {
        return $row['amount'] ?? '0.00';
    }

    public function getActualAmountYuanAttr($value, $row): string
    {
        return $row['actual_amount'] ?? '0.00';
    }

    public function getFeeAmountYuanAttr($value, $row): string
    {
        return $row['fee_amount'] ?? '0.00';
    }

    public function getStatusTextAttr($value, $row): string
    {
        $statusMap = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '支付成功',
            self::STATUS_FAILED => '支付失败',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_TIMEOUT => '已超时',
        ];
        return $statusMap[$row['status']] ?? '未知';
    }

    public function getNotifyContentAttr($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }

    public function setNotifyContentAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联支付通道
     */
    public function paymentChannel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_channel_id');
    }

    /**
     * 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待支付',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '支付成功',
            self::STATUS_FAILED => '支付失败',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_TIMEOUT => '已超时',
        ];
    }

    /**
     * 生成订单号
     * @return string
     */
    public static function generateOrderNo(): string
    {
        return 'R' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * 根据订单号查找订单
     * @param string $orderNo
     * @return RechargeOrder|null
     */
    public static function findByOrderNo(string $orderNo): ?RechargeOrder
    {
        return self::where('order_no', $orderNo)->find();
    }

    /**
     * 获取用户充值统计
     * @param int $userId
     * @return array
     */
    public static function getUserRechargeStats(int $userId): array
    {
        $stats = self::where('user_id', $userId)
            ->where('status', 1)
            ->field('COUNT(*) as total_count, SUM(actual_amount) as total_amount')
            ->find();
        
        return [
            'total_count' => $stats['total_count'] ?? 0,
            'total_amount' => bcdiv($stats['total_amount'] ?? 0, 100, 2),
        ];
    }
}