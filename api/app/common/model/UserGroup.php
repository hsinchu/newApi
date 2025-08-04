<?php

namespace app\common\model;

use think\Model;

/**
 * UserGroup 模型
 */
class UserGroup extends Model
{
    protected $name = 'user_group';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'rules'       => 'string',
        'status'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $status = [
            0 => '禁用',
            1 => '启用'
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 关联用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'group_id');
    }
    
    /**
     * 获取启用的用户组
     */
    public static function getEnabledGroups(): array
    {
        return self::where('status', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取用户组选项
     */
    public static function getOptions(): array
    {
        return self::where('status', 1)
            ->order('id', 'asc')
            ->column('name', 'id');
    }
}