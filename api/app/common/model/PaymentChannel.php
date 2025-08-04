<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * PaymentChannel 模型
 * @property int    $id                支付通道ID
 * @property string $channel_code      通道代码
 * @property string $internal_name     渠道名称(内部)
 * @property string $external_name     通道名称(外部)
 * @property string $merchant_id       商户号
 * @property string $secret_key        商户密钥
 * @property string $callback_ip       回调IP
 * @property string $notify_url        异步通知地址
 * @property string $return_url        同步返回地址
 * @property int    $is_enabled        是否启用
 * @property int    $sort_order        排序
 * @property string $remark            备注
 * @property int    $created_by        创建人
 * @property int    $updated_by        更新人
 * @property int    $create_time       创建时间
 * @property int    $update_time       更新时间
 */
class PaymentChannel extends Model
{
    /**
     * 表名
     */
    protected $name = 'payment_channel';

    /**
     * 追加属性
     */
    protected $append = [
        'is_enabled_text',
        'payment_method_names',
    ];

    protected $autoWriteTimestamp = true;

    public function getIsEnabledTextAttr($value, $row): string
    {
        return $row['is_enabled'] ? '启用' : '禁用';
    }

    public function getPaymentMethodNamesAttr($value, $row): string
    {
        if (empty($row['payment_method_id'])) {
            return '';
        }
        
        $methodIds = explode(',', $row['payment_method_id']);
        $methods = PaymentMethod::whereIn('id', $methodIds)->column('method_name');
        return implode(', ', $methods);
    }

    public function getChannelParamsAttr($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }

    public function setChannelParamsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 获取支付通道
     * @return array
     */
    public static function getOptions(): array
    {
        return self::order('sort_order', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }

    /**
     * 检查通道代码是否唯一
     * @param string $code
     * @param int $excludeId
     * @return bool
     */
    public static function isChannelCodeUnique(string $code, int $excludeId = 0): bool
    {
        $query = self::where('channel_code', $code);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return !$query->find();
    }

    /**
     * 根据支付方式获取可用通道
     * @param int $methodId
     * @return array
     */
    public static function getChannelsByMethod(int $methodId): array
    {
        return self::where('is_enabled', 1)
            ->where('payment_method_id', 'like', '%' . $methodId . '%')
            ->order('sort_order', 'desc')
            ->select()
            ->toArray();
    }
}