<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

class LotteryBonus extends Model
{
    protected $name = 'lottery_bonus';

    protected $updateTime = 'update_time';

    /**
     * 读取器：格式化赔率JSON显示
     */
    public function getBonusJsonAttr($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $value;
    }
    
    /**
     * 关联彩种
     */
    public function lotteryType(): BelongsTo
    {
        return $this->belongsTo(LotteryType::class, 'lottery_id');
    }

    /**
     * 修改器：处理赔率JSON存储
     */
    public function setBonusJsonAttr($value)
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        return $value;
    }
}