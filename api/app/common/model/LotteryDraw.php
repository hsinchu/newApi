<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * LotteryDraw 开奖结果模型
 */
class LotteryDraw extends Model
{
    protected $name = 'lottery_draw';
    protected $autoWriteTimestamp = true;
    
    protected $append = ['status_text', 'lottery_type_name', 'draw_time_text', 'settle_time_text'];
    
    // 开奖状态
    const STATUS_PENDING = 'PENDING';       // 待开奖
    const STATUS_DRAWN = 'DRAWN';           // 已开奖
    const STATUS_SETTLED = 'SETTLED';       // 已结算
    const STATUS_CANCELLED = 'CANCELLED';   // 已取消
    
    /**
     * 开奖结果访问器 - JSON解码
     */
    public function getDrawResultAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 开奖结果修改器 - JSON编码
     */
    public function setDrawResultAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    
    /**
     * 奖池金额访问器 - 分转元
     */
    public function getPrizePoolAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 奖池金额修改器 - 元转分
     */
    public function setPrizePoolAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 总投注金额访问器 - 分转元
     */
    public function getTotalBetAmountAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 总投注金额修改器 - 元转分
     */
    public function setTotalBetAmountAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 总中奖金额访问器 - 分转元
     */
    public function getTotalWinAmountAttr($value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
    
    /**
     * 总中奖金额修改器 - 元转分
     */
    public function setTotalWinAmountAttr($value): int
    {
        return (int) bcmul($value, 100, 0);
    }
    
    /**
     * 开奖时间访问器
     */
    public function getDrawTimeAttr($value): string
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
     * 彩种名称访问器
     */
    public function getLotteryTypeNameAttr($value, $data): string
    {
        return $this->lotteryType ? $this->lotteryType->type_name : '';
    }
    
    /**
     * 关联彩种
     */
    public function lotteryType(): BelongsTo
    {
        return $this->belongsTo(LotteryType::class, 'lottery_type_id');
    }
    
    /**
     * 关联投注订单
     */
    public function betOrders(): HasMany
    {
        return $this->hasMany(BetOrder::class, 'draw_no', 'draw_no');
    }
    
    /**
     * 生成开奖期号
     */
    public static function generateDrawNo(int $lotteryTypeId): string
    {
        $today = date('Ymd');
        $count = self::where('lottery_type_id', $lotteryTypeId)
            ->where('draw_no', 'like', $today . '%')
            ->count();
        return $today . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * 检查是否可以开奖
     */
    public function canDraw(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->draw_time <= time();
    }
    
    /**
     * 检查是否可以结算
     */
    public function canSettle(): bool
    {
        return $this->status === self::STATUS_DRAWN;
    }
    
    /**
     * 检查是否已开奖
     */
    public function isDrawn(): bool
    {
        return in_array($this->status, [self::STATUS_DRAWN, self::STATUS_SETTLED]);
    }
    
    /**
     * 检查是否已结算
     */
    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }
    
    /**
     * 检查指定期号是否已开奖
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @return bool
     */
    public static function isAlreadyDrawn(string $lotteryCode, string $periodNo): bool
    {
        $count = self::where('lottery_code', $lotteryCode)
            ->where('period_no', $periodNo)
            ->where('status', 'in', [self::STATUS_DRAWN, self::STATUS_SETTLED])
            ->count();
        
        return $count > 0;
    }
    
    /**
     * 获取状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待开奖',
            self::STATUS_DRAWN => '已开奖',
            self::STATUS_SETTLED => '已结算',
            self::STATUS_CANCELLED => '已取消'
        ];
    }
    
    /**
     * 获取最新开奖结果
     */
    public static function getLatestDraw(int $lotteryTypeId, int $limit = 10): array
    {
        return self::where('lottery_type_id', $lotteryTypeId)
            ->where('status', 'in', [self::STATUS_DRAWN, self::STATUS_SETTLED])
            ->order('draw_time desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取开奖统计
     */
    public static function getDrawStats(int $lotteryTypeId, string $startDate = '', string $endDate = ''): array
    {
        $where = [['lottery_type_id', '=', $lotteryTypeId]];
        
        if ($startDate) {
            $where[] = ['draw_time', '>=', strtotime($startDate)];
        }
        if ($endDate) {
            $where[] = ['draw_time', '<=', strtotime($endDate . ' 23:59:59')];
        }
        
        $stats = self::where($where)
            ->field([
                'COUNT(*) as total_count',
                'SUM(total_bet_amount) as total_bet_amount',
                'SUM(total_win_amount) as total_win_amount',
                'SUM(bet_count) as total_bet_count',
                'SUM(win_count) as total_win_count'
            ])
            ->find();
            
        return [
            'total_count' => $stats->total_count ?: 0,
            'total_bet_amount' => number_format(($stats->total_bet_amount ?: 0) / 100, 2),
            'total_win_amount' => number_format(($stats->total_win_amount ?: 0) / 100, 2),
            'total_bet_count' => $stats->total_bet_count ?: 0,
            'total_win_count' => $stats->total_win_count ?: 0,
            'profit_amount' => number_format((($stats->total_bet_amount ?: 0) - ($stats->total_win_amount ?: 0)) / 100, 2),
            'win_rate' => $stats->total_bet_count > 0 ? round(($stats->total_win_count / $stats->total_bet_count) * 100, 2) : 0
        ];
    }
    
    /**
     * 更新开奖统计数据
     */
    public function updateStats(): void
    {
        $orders = BetOrder::where('draw_no', $this->draw_no)
            ->where('lottery_type_id', $this->lottery_type_id)
            ->select();
            
        $totalBetAmount = 0;
        $totalWinAmount = 0;
        $betCount = $orders->count();
        $winCount = 0;
        
        foreach ($orders as $order) {
            $totalBetAmount += $order->getData('total_amount');
            if ($order->status === BetOrder::STATUS_WINNING) {
                $totalWinAmount += $order->getData('win_amount');
                $winCount++;
            }
        }
        
        $this->save([
            'total_bet_amount' => $totalBetAmount,
            'total_win_amount' => $totalWinAmount,
            'bet_count' => $betCount,
            'win_count' => $winCount
        ]);
    }
    
    /**
     * 更新开奖统计信息
     * @param int $totalBetAmount 总投注金额
     * @param int $totalWinAmount 总中奖金额
     * @param int $betCount 投注数量
     * @param int $winCount 中奖数量
     */
    public function updateStatistics(int $totalBetAmount, int $totalWinAmount, int $betCount, int $winCount): bool
    {
        return $this->save([
            'total_bet_amount' => $totalBetAmount,
            'total_win_amount' => $totalWinAmount,
            'bet_count' => $betCount,
            'win_count' => $winCount,
            'update_time' => time() * 1000
        ]);
    }
    
    /**
     * 根据期号查找开奖记录
     */
    public static function findByDrawNo(string $drawNo): ?self
    {
        return self::where('draw_no', $drawNo)->find();
    }
    
    /**
     * 获取待开奖记录
     */
    public static function getPendingDraws(int $lotteryTypeId = null): array
    {
        $query = self::where('status', self::STATUS_PENDING)
            ->where('draw_time', '<=', time());
            
        if ($lotteryTypeId) {
            $query->where('lottery_type_id', $lotteryTypeId);
        }
        
        return $query->order('draw_time asc')->select()->toArray();
    }
    
    /**
     * 执行开奖
     */
    public function executeDraw(array $drawResult): bool
    {
        if (!$this->canDraw()) {
            return false;
        }
        
        return $this->save([
            'status' => self::STATUS_DRAWN,
            'draw_result' => $drawResult,
            'draw_time' => time()
        ]);
    }
    
    /**
     * 执行结算
     */
    public function executeSettle(): bool
    {
        if (!$this->canSettle()) {
            return false;
        }
        
        // 更新统计数据
        $this->updateStats();
        
        // 更新状态
        return $this->save([
            'status' => self::STATUS_SETTLED,
            'settle_time' => time()
        ]);
    }
    
    /**
     * 取消开奖
     */
    public function cancelDraw(string $reason = ''): bool
    {
        return $this->save([
            'status' => self::STATUS_CANCELLED,
            'remark' => $reason
        ]);
    }
}