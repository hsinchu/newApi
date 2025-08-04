<?php

namespace app\common\model;

use think\Model;
use think\model\relation\HasMany;

/**
 * LotteryType 彩种管理模型
 */
class LotteryType extends Model
{
    protected $name = 'lottery_type';
    protected $autoWriteTimestamp = true;
    
    // 彩种分类
    const CATEGORY_SPORTS = 'SPORTS';           // 竞彩
    const CATEGORY_WELFARE = 'WELFARE';         // 福彩
    const CATEGORY_SPORTS_SINGLE = 'SPORTS_SINGLE'; // 单场
    
    /**
     * 分类文本访问器
     */
    public function getCategoryTextAttr($value, $data): string
    {
        if(isset($data['category'])){
            $categories = [
                self::CATEGORY_SPORTS => '竞彩',
                self::CATEGORY_WELFARE => '福彩',
                self::CATEGORY_SPORTS_SINGLE => '单场'
            ];
            return $categories[$data['category']] ?? $data['category'];
        }else{
            return '';
        }
        
    }
    
    /**
     * 状态文本访问器
     */
    public function getIsEnabledTextAttr($value, $data): string
    {
        if(isset($data['is_enabled'])){
            return $data['is_enabled'] ? '启用' : '禁用';    
        }else{
            return '';
        }
        
    }
    
    /**
     * 关联投注订单
     */
    public function betOrders(): HasMany
    {
        return $this->hasMany(BetOrder::class, 'lottery_type_id');
    }
    
    /**
     * 关联开奖结果
     */
    public function lotteryDraws(): HasMany
    {
        return $this->hasMany(LotteryDraw::class, 'lottery_type_id');
    }
    
    /**
     * 获取启用的彩种列表
     */
    public static function getEnabledList(): array
    {
        return self::where('is_enabled', 1)
            ->order('sort_order desc, id asc')
            ->column('type_name', 'type_code', 'id');
    }
    
    /**
     * 根据代码获取彩种
     */
    public static function getByCode(string $typeCode): ?self
    {
        return self::where('type_code', $typeCode)->find();
    }
    
    /**
     * 检查彩种是否启用
     */
    public function isEnabled(): bool
    {
        return (bool) $this->is_enabled;
    }
    
    /**
     * 验证投注金额
     */
    public function validateBetAmount(int $amount): bool
    {
        return $amount >= $this->getData('min_bet_amount') && $amount <= $this->getData('max_bet_amount');
    }
    
    /**
     * 获取分类选项
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_SPORTS => '竞彩',
            self::CATEGORY_WELFARE => '福彩',
            self::CATEGORY_SPORTS_SINGLE => '单场'
        ];
    }
    
    /**
     * 今日订单数访问器
     */
    public function getTodayOrdersCountAttr($value, $data): int
    {
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $todayEnd = strtotime(date('Y-m-d 23:59:59'));
        
        return BetOrder::where('lottery_type_id', $data['id'])
            ->where('create_time', 'between', [$todayStart, $todayEnd])
            ->count();
    }
    
    /**
     * 今日投注金额访问器
     */
    public function getTodayBetAmountAttr($value, $data): string
    {
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $todayEnd = strtotime(date('Y-m-d 23:59:59'));
        
        $amount = BetOrder::where('lottery_type_id', $data['id'])
            ->where('create_time', 'between', [$todayStart, $todayEnd])
            ->sum('total_amount');
            
        return number_format($amount / 100, 2);
    }
    
    /**
     * 获取彩种选项
     */
    public static function getOptions(): array
    {
        return self::where('is_enabled', 1)
            ->order('sort_order desc, id asc')
            ->column('type_name', 'id');
    }
    
    /**
     * 生成唯一彩种代码
     */
    public static function generateUniqueCode(string $baseName): string
    {
        $code = strtoupper(substr(md5($baseName), 0, 8));
        $counter = 1;
        
        while (self::where('type_code', $code)->find()) {
            $code = strtoupper(substr(md5($baseName . $counter), 0, 8));
            $counter++;
        }
        
        return $code;
    }
    
    /**
     * 获取彩种统计信息
     */
    public function getStats(): array
    {
        
        // 今日统计
        $todayStats = $this->betOrders()
            ->whereTime('create_time', 'today')
            ->field([
                'COUNT(*) as orders_count',
                'SUM(bet_amount) as bet_amount',
                'SUM(win_amount) as win_amount'
            ])
            ->find();
            
        // 本月统计
        $monthStats = $this->betOrders()
            ->whereTime('create_time', 'month')
            ->field([
                'COUNT(*) as orders_count',
                'SUM(bet_amount) as bet_amount',
                'SUM(win_amount) as win_amount'
            ])
            ->find();
            
        return [
            'today' => [
                'orders_count' => $todayStats['orders_count'] ?? 0,
                'bet_amount' => bcdiv($todayStats['bet_amount'] ?? 0, 100, 2),
                'win_amount' => bcdiv($todayStats['win_amount'] ?? 0, 100, 2)
            ],
            'month' => [
                'orders_count' => $monthStats['orders_count'] ?? 0,
                'bet_amount' => bcdiv($monthStats['bet_amount'] ?? 0, 100, 2),
                'win_amount' => bcdiv($monthStats['win_amount'] ?? 0, 100, 2)
            ]
        ];
    }
    
}