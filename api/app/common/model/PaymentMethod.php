<?php

namespace app\common\model;

use think\Model;

/**
 * PaymentMethod 模型
 * @property int    $id                支付方式ID
 * @property string $method_code       支付方式代码
 * @property string $method_name       支付方式名称
 * @property string $method_icon       支付方式图标
 * @property string $description       支付方式描述
 * @property int    $is_enabled        是否启用
 * @property int    $sort_order        排序
 * @property int    $created_by        创建人
 * @property int    $updated_by        更新人
 * @property int    $create_time       创建时间
 * @property int    $update_time       更新时间
 */
class PaymentMethod extends Model
{
    /**
     * 表名
     */
    protected $name = 'payment_method';

    /**
     * 追加属性
     */
    protected $append = [
        'is_enabled_text',
    ];

    protected $autoWriteTimestamp = true;

    public function getMethodIconAttr($value): string
    {
        return full_url($value, false, '');
    }

    public function setMethodIconAttr($value): string
    {
        return $value;
    }

    public function getIsEnabledTextAttr($value, $row): string
    {
        return $row['is_enabled'] ? '启用' : '禁用';
    }

    /**
     * 获取支付方式
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
     * 检查代码是否唯一
     * @param string $code
     * @param int $excludeId
     * @return bool
     */
    public static function isCodeUnique(string $code, int $excludeId = 0): bool
    {
        $query = self::where('method_code', $code);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return !$query->find();
    }
}