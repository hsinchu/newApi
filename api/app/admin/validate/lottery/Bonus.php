<?php

namespace app\admin\validate\lottery;

use think\Validate;

class Bonus extends Validate
{
    protected $rule = [
        'type_id'    => 'require|integer|>:0',
        'name'       => 'require|max:200',
        'key'        => 'require|max:200|unique:lottery_bonus',
        'min_price'  => 'require|float|>=:0',
        'max_price'  => 'require|float|>=:0',
    ];

    protected $message = [
        'type_id.require'    => '所属彩种不能为空',
        'type_id.integer'    => '所属彩种必须是整数',
        'type_id.>'          => '所属彩种ID必须大于0',
        'name.require'       => '名称不能为空',
        'name.max'           => '名称长度不能超过200个字符',
        'key.require'        => '键值不能为空',
        'key.max'            => '键值长度不能超过200个字符',
        'key.unique'         => '键值已存在',
        'min_price.require'  => '最低购买金额不能为空',
        'min_price.float'    => '最低购买金额必须是数字',
        'min_price.>='       => '最低购买金额不能小于0',
        'max_price.require'  => '最高购买金额不能为空',
        'max_price.float'    => '最高购买金额必须是数字',
        'max_price.>='       => '最高购买金额不能小于0',
    ];

    protected $scene = [
        'add'  => ['type_id', 'name', 'key', 'min_price', 'max_price', 'bonus_json'],
        'edit' => ['type_id', 'name', 'key', 'min_price', 'max_price', 'bonus_json'],
    ];

    /**
     * 编辑场景下的键值唯一性验证
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function checkKeyUnique($value, $rule, $data)
    {
        $model = new \app\admin\model\lottery\Bonus();
        $where = [['key', '=', $value]];
        
        // 编辑时排除当前记录
        if (isset($data['id'])) {
            $where[] = ['id', '<>', $data['id']];
        }
        
        $exists = $model->where($where)->find();
        if ($exists) {
            return '键值已存在';
        }
        
        return true;
    }
}