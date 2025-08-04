<?php

namespace app\common\model;

use ba\Random;
use think\Model;

/**
 * 公告模型
 * @controllerUrl 'admin/dano'
 */
class Dano extends Model
{
    // 表名
    protected $name = 'dano';
    
    // 自动时间戳字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'sort_num' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            0 => '已下架',
            1 => '正常显示'
        ];
        return $status[$data['status']] ?? '';
    }
    
    /**
     * 获取公告列表（前台使用）
     */
    public static function getPublicList($limit = 10)
    {
        return self::where('status', 1)
            ->order('sort_num desc, id desc')
            ->limit($limit)
            ->select();
    }
    
    /**
     * 获取公告详情（前台使用）
     */
    public static function getPublicDetail($id)
    {
        return self::where('id', $id)
            ->where('status', 1)
            ->find();
    }
}