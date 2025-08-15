<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * AgentRebateConfig 代理商返水配置模型
 * @property int    $id                        主键ID
 * @property int    $agent_id                  代理商ID
 * @property float  $sports_no_win_rate        竞彩不中奖返水比例(%)
 * @property float  $sports_bet_rate           竞彩投注返水比例(%)
 * @property float  $welfare_no_win_rate       福彩不中奖返水比例(%)
 * @property float  $welfare_bet_rate          福彩投注返水比例(%)
 * @property float  $sports_single_no_win_rate 单场不中奖返水比例(%)
 * @property float  $sports_single_bet_rate    单场投注返水比例(%)
 * @property float  $quick_no_win_rate         快彩不中奖返水比例(%)
 * @property float  $quick_bet_rate            快彩投注返水比例(%)
 * @property string $rebate_type               返水方式
 * @property string $settlement_cycle          结算周期
 * @property string $settlement_time           结算时间
 * @property int    $is_enabled                是否启用
 * @property int    $create_time               创建时间
 * @property int    $update_time               更新时间
 */
class AgentRebateConfig extends Model
{
    /**
     * 表名
     */
    protected $name = 'agent_rebate_config';

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
        'sports_no_win_rate' => 'float',
        'sports_bet_rate' => 'float',
        'welfare_no_win_rate' => 'float',
        'welfare_bet_rate' => 'float',
        'sports_single_no_win_rate' => 'float',
        'sports_single_bet_rate' => 'float',
        'quick_no_win_rate' => 'float',
        'quick_bet_rate' => 'float',
        'is_enabled' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * 追加属性
     */
    protected $append = [
        'rebate_type_text',
        'settlement_cycle_text',
        'is_enabled_text',
    ];

    // 返水方式常量
    const REBATE_TYPE_PROFIT = 'profit';  // 盈利返水
    const REBATE_TYPE_BET = 'bet';        // 投注返水

    // 结算周期常量
    const SETTLEMENT_CYCLE_1 = '1';       // 1天
    const SETTLEMENT_CYCLE_7 = '7';       // 7天
    const SETTLEMENT_CYCLE_30 = '30';     // 30天
    const SETTLEMENT_CYCLE_90 = '90';     // 90天

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
     * 启用状态获取器
     */
    public function getIsEnabledTextAttr($value, $data)
    {
        $status = [
            0 => '禁用',
            1 => '启用',
        ];
        return $status[$data['is_enabled']] ?? '';
    }

    /**
     * 关联代理商用户
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * 获取代理商返水配置
     */
    public static function getAgentConfig($agentId)
    {
        return self::where('agent_id', $agentId)
            ->where('is_enabled', 1)
            ->find();
    }

    /**
     * 获取返水比例
     */
    public function getRebateRate($category, $isWin = false)
    {
        $rateField = '';
        switch ($category) {
            case 'SPORTS':
                $rateField = $isWin ? 'sports_bet_rate' : 'sports_no_win_rate';
                break;
            case 'WELFARE':
                $rateField = $isWin ? 'welfare_bet_rate' : 'welfare_no_win_rate';
                break;
            case 'SPORTS_SINGLE':
                $rateField = $isWin ? 'sports_single_bet_rate' : 'sports_single_no_win_rate';
                break;
            case 'QUICK':
                $rateField = $isWin ? 'quick_bet_rate' : 'quick_no_win_rate';
                break;
        }
        
        return $rateField ? $this->$rateField : 0;
    }
}