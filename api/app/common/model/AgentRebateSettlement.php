<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * AgentRebateSettlement 代理商返水发放记录模型
 * @property int    $id                        主键ID
 * @property int    $agent_id                  代理商ID
 * @property string $settlement_date           结算日期
 * @property string $settlement_cycle          结算周期
 * @property float  $total_bet_amount          总投注金额
 * @property float  $total_win_amount          总中奖金额
 * @property float  $total_profit_loss         总盈亏金额
 * @property float  $total_rebate_amount       总返水金额
 * @property float  $sports_rebate_amount      竞彩返水金额
 * @property float  $welfare_rebate_amount     福彩返水金额
 * @property float  $sports_single_rebate_amount 单场返水金额
 * @property float  $quick_rebate_amount       快彩返水金额
 * @property string $settlement_status         结算状态
 * @property int    $settlement_time           结算时间
 * @property int    $operator_id               操作员ID
 * @property string $remark                    备注
 * @property int    $create_time               创建时间
 * @property int    $update_time               更新时间
 */
class AgentRebateSettlement extends Model
{
    /**
     * 表名
     */
    protected $name = 'agent_rebate_settlement';

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
        'total_bet_amount' => 'float',
        'total_win_amount' => 'float',
        'total_profit_loss' => 'float',
        'total_rebate_amount' => 'float',
        'sports_rebate_amount' => 'float',
        'welfare_rebate_amount' => 'float',
        'sports_single_rebate_amount' => 'float',
        'quick_rebate_amount' => 'float',
        'settlement_time' => 'integer',
        'operator_id' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * 追加属性
     */
    protected $append = [
        'settlement_cycle_text',
        'settlement_status_text',
    ];

    // 结算周期常量
    const SETTLEMENT_CYCLE_1 = '1';       // 1天
    const SETTLEMENT_CYCLE_7 = '7';       // 7天
    const SETTLEMENT_CYCLE_30 = '30';     // 30天
    const SETTLEMENT_CYCLE_90 = '90';     // 90天

    // 结算状态常量
    const STATUS_PENDING = 'pending';     // 待发放
    const STATUS_PROCESSING = 'processing'; // 发放中
    const STATUS_COMPLETED = 'completed'; // 已发放
    const STATUS_FAILED = 'failed';       // 发放失败
    const STATUS_CANCELLED = 'cancelled'; // 已取消

    /**
     * 结算周期获取器
     */
    public function getSettlementCycleTextAttr($value, $data)
    {
        $cycles = [
            self::SETTLEMENT_CYCLE_1 => '1天',
            self::SETTLEMENT_CYCLE_7 => '7天',
            self::SETTLEMENT_CYCLE_30 => '30天',
            self::SETTLEMENT_CYCLE_90 => '90天',
        ];
        return $cycles[$data['settlement_cycle']] ?? '';
    }

    /**
     * 结算状态获取器
     */
    public function getSettlementStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_PENDING => '待发放',
            self::STATUS_PROCESSING => '发放中',
            self::STATUS_COMPLETED => '已发放',
            self::STATUS_FAILED => '发放失败',
            self::STATUS_CANCELLED => '已取消',
        ];
        return $status[$data['settlement_status']] ?? '';
    }

    /**
     * 关联代理商用户
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * 关联操作员
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * 获取代理商待发放的返水记录
     */
    public static function getPendingSettlements($agentId = null)
    {
        $query = self::where('settlement_status', self::STATUS_PENDING);
        
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }
        
        return $query->order('settlement_date', 'desc')->select();
    }

    /**
     * 获取代理商指定日期的结算记录
     */
    public static function getSettlementByDate($agentId, $settlementDate, $cycle)
    {
        return self::where('agent_id', $agentId)
            ->where('settlement_date', $settlementDate)
            ->where('settlement_cycle', $cycle)
            ->find();
    }

    /**
     * 创建结算记录
     */
    public static function createSettlement($data)
    {
        $settlement = new self();
        $settlement->save($data);
        return $settlement;
    }

    /**
     * 更新结算状态
     */
    public function updateStatus($status, $operatorId = null, $remark = '')
    {
        $updateData = [
            'settlement_status' => $status,
            'update_time' => time(),
        ];
        
        if ($status === self::STATUS_COMPLETED) {
            $updateData['settlement_time'] = time();
        }
        
        if ($operatorId) {
            $updateData['operator_id'] = $operatorId;
        }
        
        if ($remark) {
            $updateData['remark'] = $remark;
        }
        
        return $this->save($updateData);
    }

    /**
     * 获取结算统计信息
     */
    public static function getSettlementStats($agentId, $startDate = null, $endDate = null)
    {
        $query = self::where('agent_id', $agentId)
            ->where('settlement_status', self::STATUS_COMPLETED);
        
        if ($startDate) {
            $query->where('settlement_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('settlement_date', '<=', $endDate);
        }
        
        return $query->field([
            'COUNT(*) as total_count',
            'SUM(total_bet_amount) as total_bet',
            'SUM(total_win_amount) as total_win',
            'SUM(total_rebate_amount) as total_rebate',
        ])->find();
    }
}