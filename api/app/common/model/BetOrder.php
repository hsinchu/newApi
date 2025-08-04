<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * BetOrder 投注订单模型
 */
class BetOrder extends Model
{
    protected $name = 'bet_order';
    protected $autoWriteTimestamp = true;
    
    // 订单状态
    const STATUS_PENDING = 'PENDING';           // 待确认
    const STATUS_CONFIRMED = 'CONFIRMED';       // 已确认
    const STATUS_WINNING = 'WINNING';           // 待派奖
    const STATUS_PAID = 'PAID';                 // 已派奖
    const STATUS_LOSING = 'LOSING';             // 未中奖
    const STATUS_CANCELLED = 'CANCELLED';       // 已取消
    const STATUS_REFUNDED = 'REFUNDED';         // 已退款
    
    /**
     * 投注金额访问器 - 分转元
     */
    public function getBetAmountAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 总金额访问器 - 分转元
     */
    public function getTotalAmountAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 中奖金额访问器 - 分转元
     */
    public function getWinAmountAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 投注金额修改器 - 元转分
     */
    public function setBetAmountAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 总金额修改器 - 元转分
     */
    public function setTotalAmountAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 中奖金额修改器 - 元转分
     */
    public function setWinAmountAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 赠送金额访问器 - 分转元
     */
    public function getGiftMoneyAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 赠送金额修改器 - 元转分
     */
    public function setGiftMoneyAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 投注内容访问器 - JSON解码
     */
    public function getBetContentAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $value;
    }
    
    /**
     * 投注内容修改器 - JSON编码
     */
    public function setBetContentAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    
    /**
     * 开奖结果访问器 - 字符串转数组
     */
    public function getDrawResultAttr($value): array
    {
        return $value ? explode(',', $value) ?: [] : [];
    }
    
    /**
     * 开奖结果修改器 - 数组转字符串
     */
    public function setDrawResultAttr($value): string
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    
    /**
     * 状态文本访问器
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statuses = [
            self::STATUS_PENDING => '待确认',
            self::STATUS_CONFIRMED => '待开奖',
            self::STATUS_WINNING => '待派奖',
            self::STATUS_PAID => '已派奖',
            self::STATUS_LOSING => '未中奖',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_REFUNDED => '已退款'
        ];
        return $statuses[$data['status']] ?? $data['status'];
    }
    
    /**
     * 开奖时间访问器
     */
    public function getDrawTimeAttr($value): string
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }
    
    /**
     * 结算时间访问器
     */
    public function getSettleTimeAttr($value): string
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }
    
    /**
     * 开奖时间文本访问器
     */
    public function getDrawTimeTextAttr($value, $data): string
    {
        return $data['draw_time'] ? date('Y-m-d H:i:s', $data['draw_time']) : '';
    }
    
    /**
     * 结算时间文本访问器
     */
    public function getSettleTimeTextAttr($value, $data): string
    {
        return $data['settle_time'] ? date('Y-m-d H:i:s', $data['settle_time']) : '';
    }
    
    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 关联彩种
     */
    public function lotteryType(): BelongsTo
    {
        return $this->belongsTo(LotteryType::class, 'lottery_type_id');
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderNo(): string
    {
        return date('YmdHis') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * 检查订单是否可以取消
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
    
    /**
     * 检查订单是否可以结算
     */
    public function canSettle(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
    
    /**
     * 检查订单是否已结算
     */
    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_WINNING, self::STATUS_LOSING]);
    }
    
    /**
     * 检查订单是否中奖
     */
    public function isWinning(): bool
    {
        return $this->status === self::STATUS_WINNING && $this->getData('win_amount') > 0;
    }
    
    /**
     * 获取状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待确认',
            self::STATUS_CONFIRMED => '待开奖',
            self::STATUS_WINNING => '中奖',
            self::STATUS_LOSING => '未中奖',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_REFUNDED => '已退款'
        ];
    }
    
    /**
     * 根据用户ID获取订单统计
     */
    public static function getUserOrderStats(int $userId): array
    {
        $stats = self::where('user_id', $userId)
            ->field([
                'COUNT(*) as total_count',
                'SUM(total_amount) as total_amount',
                'SUM(CASE WHEN status = "WINNING" THEN win_amount ELSE 0 END) as total_win_amount',
                'COUNT(CASE WHEN status = "WINNING" THEN 1 END) as win_count'
            ])
            ->find();
            
        return [
            'total_count' => $stats->total_count ?: 0,
            'total_amount' => number_format(($stats->total_amount ?: 0) / 100, 2),
            'total_win_amount' => number_format(($stats->total_win_amount ?: 0) / 100, 2),
            'win_count' => $stats->win_count ?: 0,
            'win_rate' => $stats->total_count > 0 ? round(($stats->win_count / $stats->total_count) * 100, 2) : 0
        ];
    }
    
    /**
     * 根据订单号查找订单
     */
    public static function findByOrderNo(string $orderNo): ?self
    {
        return self::where('order_no', $orderNo)->find();
    }
    
    /**
     * 获取待结算订单
     */
    public static function getPendingSettleOrders(int $lotteryTypeId = null): array
    {
        $query = self::where('status', self::STATUS_CONFIRMED);
        
        if ($lotteryTypeId) {
            $query->where('lottery_type_id', $lotteryTypeId);
        }
        
        return $query->select()->toArray();
    }
    
    /**
     * 批量结算订单
     */
    public static function batchSettle(array $orderIds, array $drawResult): int
    {
        return self::where('id', 'in', $orderIds)
            ->where('status', self::STATUS_CONFIRMED)
            ->update([
                'draw_result' => json_encode($drawResult, JSON_UNESCAPED_UNICODE),
                'settle_time' => time(),
                'update_time' => time()
            ]);
    }
    
    /**
     * 获取订单统计数据
     */
    public static function getStatistics(string $startDate = null, string $endDate = null, array $conditions = []): array
    {
        $startDate = $startDate ?: date('Y-m-d', strtotime('-7 days'));
        $endDate = $endDate ?: date('Y-m-d');
        
        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $query = self::where('create_time', 'between', [$startTime, $endTime]);
        
        // 应用额外条件
        foreach ($conditions as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }
        
        // 总订单数
        $totalOrders = $query->count();
        
        // 各状态订单统计
        $statusStats = [];
        foreach (self::getStatusOptions() as $status => $statusText) {
            $count = (clone $query)->where('status', $status)->count();
            $amount = (clone $query)->where('status', $status)->sum('total_amount');
            $statusStats[$status] = [
                'count' => $count,
                'amount' => number_format($amount / 100, 2),
                'text' => $statusText
            ];
        }
        
        // 中奖订单统计
        $winData = (clone $query)->where('status', self::STATUS_WINNING)
            ->field('COUNT(*) as count, SUM(total_amount) as total_amount, SUM(win_amount) as total_win_amount')
            ->find();
        
        return [
            'total_orders' => $totalOrders,
            'win_orders' => $winData['count'] ?? 0,
            'total_bet_amount' => number_format((clone $query)->sum('total_amount') / 100, 2),
            'total_win_amount' => number_format(($winData['total_win_amount'] ?? 0) / 100, 2),
            'win_rate' => $totalOrders > 0 ? round(($winData['count'] ?? 0) / $totalOrders * 100, 2) : 0,
            'profit_amount' => number_format(((clone $query)->sum('total_amount') - ($winData['total_win_amount'] ?? 0)) / 100, 2),
            'status_stats' => $statusStats,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }
    
    /**
     * 取消订单
     */
    public function cancelOrder(string $reason = ''): bool
    {
        if (!$this->canCancel()) {
            return false;
        }
        
        return $this->save([
            'status' => self::STATUS_CANCELLED,
            'remark' => $reason
        ]);
    }
    
    /**
     * 结算为中奖
     */
    public function settleAsWinning(int $winAmount, array $drawResult = []): bool
    {
        if (!$this->canSettle()) {
            return false;
        }
        
        return $this->save([
            'status' => self::STATUS_WINNING,
            'win_amount' => $winAmount,
            'draw_result' => $drawResult,
            'settle_time' => time()
        ]);
    }
    
    /**
     * 结算为未中奖
     */
    public function settleAsLosing(array $drawResult = []): bool
    {
        if (!$this->canSettle()) {
            return false;
        }
        
        return $this->save([
            'status' => self::STATUS_LOSING,
            'win_amount' => 0,
            'draw_result' => $drawResult,
            'settle_time' => time()
        ]);
    }
}