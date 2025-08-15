<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * AgentRebateRecord 代理商返水记录模型
 * @property int    $id                主键ID
 * @property int    $agent_id          代理商ID
 * @property string $category          彩种分类
 * @property float  $bet_amount        投注金额
 * @property float  $win_amount        中奖金额
 * @property float  $no_win_amount     未中奖金额
 * @property float  $profit_loss       盈亏金额
 * @property float  $rebate_amount     返水金额
 * @property float  $commission_amount 佣金金额
 * @property float  $rebate_rate       返水比例
 * @property float  $no_win_rebate_amount  未中奖返佣金额
 * @property float  $no_win_rate       未中奖返佣比例
 * @property float  $bet_rebate_amount 投注返佣金额
 * @property float  $bet_rate          投注返佣比例
 * @property string $rebate_type       返水方式
 * @property string $settlement_date   结算日期
 * @property string $record_status     记录状态
 * @property int    $create_time       创建时间
 * @property int    $update_time       更新时间
 */
class AgentRebateRecord extends Model
{
    /**
     * 表名
     */
    protected $name = 'agent_rebate_record';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 字段类型转换
     */
    protected $type = [
        'id' => 'integer',
        'agent_id' => 'integer',
        'bet_amount' => 'float',
        'win_amount' => 'float',
        'no_win_amount' => 'float',
        'profit_loss' => 'float',
        'rebate_amount' => 'float',
        'commission_amount' => 'float',
        'rebate_rate' => 'float',
        'no_win_rebate_amount' => 'float',
        'no_win_rate' => 'float',
        'bet_rebate_amount' => 'float',
        'bet_rate' => 'float',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * 追加属性
     */
    protected $append = [
        'category_text',
        'rebate_type_text',
        'record_status_text',
        'no_win_rebate_amount_yuan',
        'bet_rebate_amount_yuan',
        'commission_amount_yuan',
    ];

    // 彩种分类常量
    const CATEGORY_SPORTS = 'SPORTS';           // 竞彩
    const CATEGORY_WELFARE = 'WELFARE';         // 福彩
    const CATEGORY_SPORTS_SINGLE = 'SPORTS_SINGLE'; // 单场
    const CATEGORY_QUICK = 'QUICK';             // 快彩

    // 返水方式常量
    const REBATE_TYPE_PROFIT = 'profit';        // 盈利返水
    const REBATE_TYPE_BET = 'bet';              // 投注返水

    // 记录状态常量
    const STATUS_PENDING = 'pending';           // 待结算
    const STATUS_SETTLED = 'settled';           // 已结算
    const STATUS_CANCELLED = 'cancelled';       // 已取消

    /**
     * 彩种分类获取器
     */
    public function getCategoryTextAttr($value, $data)
    {
        $categories = [
            self::CATEGORY_SPORTS => '竞彩',
            self::CATEGORY_WELFARE => '福彩',
            self::CATEGORY_SPORTS_SINGLE => '单场',
            self::CATEGORY_QUICK => '快彩',
        ];
        return $categories[$data['category']] ?? '';
    }

    /**
     * 返水方式获取器
     */
    public function getRebateTypeTextAttr($value, $data)
    {
        $types = [
            self::REBATE_TYPE_PROFIT => '盈利返水',
            self::REBATE_TYPE_BET => '投注返水',
        ];
        return $types[$data['rebate_type']] ?? '';
    }

    /**
     * 记录状态获取器
     */
    public function getRecordStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_PENDING => '待结算',
            self::STATUS_SETTLED => '已结算',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $status[$data['record_status']] ?? '';
    }

    /**
     * 未中奖返佣金额（元）获取器
     */
    public function getNoWinRebateAmountYuanAttr($value, $data)
    {
        return round(($data['no_win_rebate_amount'] ?? 0) / 100, 2);
    }

    /**
     * 投注返佣金额（元）获取器
     */
    public function getBetRebateAmountYuanAttr($value, $data)
    {
        return round(($data['bet_rebate_amount'] ?? 0) / 100, 2);
    }

    /**
     * 佣金金额（元）获取器
     */
    public function getCommissionAmountYuanAttr($value, $data)
    {
        return round(($data['commission_amount'] ?? 0) / 100, 2);
    }

    /**
     * 关联代理商用户
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * 获取代理商未结算返水记录
     */
    public static function getUnsettledRecords($agentId, $category = null)
    {
        $query = self::where('agent_id', $agentId)
            ->where('record_status', self::STATUS_PENDING);
        
        if ($category) {
            $query->where('lottery_category', $category);
        }
        
        return $query->select();
    }

    /**
     * 获取代理商指定日期范围的返水记录
     */
    public static function getRecordsByDateRange($agentId, $startDate, $endDate)
    {
        return self::where('agent_id', $agentId)
            ->where('settlement_date', '>=', $startDate)
            ->where('settlement_date', '<=', $endDate)
            ->select();
    }

    /**
     * 计算返水金额
     */
    public static function calculateRebate($betAmount, $winAmount, $rebateRate, $rebateType)
    {
        if ($rebateType === self::REBATE_TYPE_PROFIT) {
            // 盈利返水：基于盈亏计算
            $profitLoss = $betAmount - $winAmount;
            return $profitLoss > 0 ? $profitLoss * ($rebateRate / 100) : 0;
        } else {
            // 投注返水：基于投注金额计算
            return $betAmount * ($rebateRate / 100);
        }
    }

    /**
     * 批量更新记录状态
     */
    public static function updateRecordsStatus($recordIds, $status)
    {
        return self::where('id', 'in', $recordIds)
            ->update(['record_status' => $status, 'update_time' => time()]);
    }
}