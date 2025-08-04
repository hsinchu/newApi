<?php

namespace app\api\validate;

use think\Validate;

class RedPacket extends Validate
{
    protected $rule = [
        'title' => 'require|max:50',
        'blessing' => 'require|max:100',
        'type' => 'require|in:RANDOM,FIXED',
        'total_amount' => 'require|float|gt:0',
        'total_count' => 'require|integer|gt:0',
        'target_type' => 'require|in:0,1,2',
        'condition_type' => 'require|in:NONE,MIN_BET,USER_LEVEL',
        'condition_value' => 'requireIf:condition_type,MIN_BET,USER_LEVEL',
        'expire_time' => 'require|integer|checkExpireTime'
    ];

    protected $message = [
        'title.require' => '红包标题不能为空',
        'title.max' => '红包标题不能超过50个字符',
        'blessing.require' => '祝福语不能为空',
        'blessing.max' => '祝福语不能超过100个字符',
        'type.require' => '红包类型不能为空',
        'type.in' => '红包类型只能是RANDOM或FIXED',
        'total_amount.require' => '总金额不能为空',
        'total_amount.float' => '总金额必须是数字',
        'total_amount.gt' => '总金额必须大于0',
        'total_count.require' => '红包个数不能为空',
        'total_count.integer' => '红包个数必须是整数',
        'total_count.gt' => '红包个数必须大于0',
        'target_type.require' => '发送对象不能为空',
        'target_type.in' => '发送对象类型错误',
        'condition_type.require' => '领取条件类型不能为空',
        'condition_type.in' => '领取条件类型错误',
        'condition_value.requireIf' => '请输入领取条件值',
        'expire_time.require' => '过期时间不能为空',
        'expire_time.integer' => '过期时间格式错误',
        'expire_time.gt' => '过期时间不能早于当前时间'
    ];

    protected $scene = [
        'create' => ['title', 'blessing', 'type', 'total_amount', 'total_count', 'target_type', 'condition_type', 'condition_value', 'expire_time']
    ];

    /**
     * 自定义验证规则
     */
    protected function checkAmount($value, $rule, $data = [])
    {
        // 检查总金额是否足够分配
        if (isset($data['total_count']) && $data['total_count'] > 0) {
            $minAmount = $data['total_count'] * 0.01; // 每个红包至少1分钱
            if ($value < $minAmount) {
                return '总金额不能少于红包个数的1分钱';
            }
        }
        return true;
    }

    /**
     * 验证过期时间
     */
    protected function checkExpireTime($value, $rule, $data = [])
    {
        if ($value <= time()) {
            return '过期时间不能早于当前时间';
        }
        return true;
    }

    /**
     * 验证条件值
     */
    protected function checkConditionValue($value, $rule, $data = [])
    {
        if (!isset($data['condition_type'])) {
            return true;
        }

        switch ($data['condition_type']) {
            case 'MIN_BET':
                if (!is_numeric($value) || $value <= 0) {
                    return '最低投注金额必须是大于0的数字';
                }
                break;
            case 'USER_LEVEL':
                if (!is_numeric($value) || $value <= 0 || $value != intval($value)) {
                    return '用户等级必须是大于0的整数';
                }
                break;
        }
        return true;
    }
}