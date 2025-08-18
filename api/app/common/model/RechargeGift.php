<?php

namespace app\common\model;

use think\Model;

class RechargeGift extends Model
{
    protected $name = 'recharge_gift';
    
    // 设置字段信息
    protected $schema = [
        'id'            => 'int',
        'agent_id'      => 'int',
        'charge_amount' => 'decimal',
        'bonus_amount'  => 'decimal',
        'status'        => 'int',
        'start_time'    => 'int',
        'end_time'      => 'int',
        'create_time'   => 'int',
        'update_time'   => 'int',
    ];
    
    // 类型转换
    protected $type = [
        'charge_amount' => 'float',
        'bonus_amount'  => 'float',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 状态常量
    const STATUS_DISABLED = 0; // 禁用
    const STATUS_ENABLED = 1;  // 启用
    
    /**
     * 状态文本访问器
     */
    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
        return $statusMap[$data['status']] ?? '未知';
    }
    
    /**
     * 关联代理商
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id', 'id')->where('is_agent', 1);
    }
    
    /**
     * 获取代理商的充值赠送配置
     */
    public static function getAgentConfigs($agentId)
    {
        return self::where('agent_id', $agentId)
            ->order('charge_amount', 'asc')
            ->select();
    }
    
    /**
     * 根据充值金额获取赠送配置
     */
    public static function getGiftByAmount($agentId, $chargeAmount)
    {
        $currentTime = time();
        
        return self::where('agent_id', $agentId)
            ->where('status', self::STATUS_ENABLED)
            ->where('charge_amount', '<=', $chargeAmount)
            ->where(function($query) use ($currentTime) {
                $query->where('start_time', 0)
                      ->whereOr('start_time', '<=', $currentTime);
            })
            ->where(function($query) use ($currentTime) {
                $query->where('end_time', 0)
                      ->whereOr('end_time', '>=', $currentTime);
            })
            ->order('charge_amount', 'desc')
            ->find();
    }
}