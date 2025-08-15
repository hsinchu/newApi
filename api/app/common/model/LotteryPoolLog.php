<?php

namespace app\common\model;

use think\Model;

/**
 * 服务费记录表模型
 * @controllerUrl 'admin/lottery.LotteryPoolLog'
 */
class LotteryPoolLog extends Model
{
    protected $name = 'lottery_pool_log';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'type_code'   => 'string',
        'period_no'   => 'string', 
        'bonus_system'=> 'decimal',
        'update_time' => 'bigint',
    ];
    
    // JSON字段
    protected $json = [];
    
    // 追加属性
    protected $append = [];
    
    // 自动时间戳
    protected $autoWriteTimestamp = false;
    
    /**
     * 关联彩种表
     */
    public function lotteryType()
    {
        return $this->belongsTo(LotteryType::class, 'type_code', 'type_code');
    }
    
    /**
     * 格式化服务费金额
     */
    public function getBonusSystemAttr($value)
    {
        return number_format($value, 2);
    }
    
    /**
     * 格式化更新时间
     */
    public function getUpdateTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value / 1000) : '';
    }
    
    /**
     * 设置更新时间
     */
    public function setUpdateTimeAttr($value)
    {
        return is_numeric($value) ? $value : strtotime($value) * 1000;
    }
    
    /**
     * 记录服务费日志
     * @param string $typeCode 彩种代码
     * @param string $periodNo 期号
     * @param float $bonusSystem 服务费金额
     * @return bool
     */
    public static function recordBonusSystem($typeCode, $periodNo, $bonusSystem)
    {
        $data = [
            'type_code' => $typeCode,
            'period_no' => $periodNo,
            'bonus_system' => $bonusSystem,
            'update_time' => time() * 1000, // 毫秒时间戳
        ];
        
        return self::create($data);
    }
}