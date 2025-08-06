<?php

namespace app\common\model;

use Throwable;
use think\model;
use think\Exception;
use think\model\relation\BelongsTo;

/**
 * UserMoneyLog 模型
 * 1. 创建余额日志自动完成会员余额的添加
 * 2. 创建余额日志时，请开启事务
 */
class UserMoneyLog extends model
{
    /**
     * 表名
     */
    protected $name = 'user_money_log';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'             => 'int',
        'user_id'        => 'int',
        'type'           => 'string',
        'money'          => 'int',
        'before'         => 'int',
        'after'          => 'int',
        'frozen_change'  => 'int',
        'frozen_before'  => 'int',
        'frozen_after'   => 'int',
        'related_id'     => 'int',
        'operator_id'    => 'int',
        'operator_type'  => 'string',
        'memo'           => 'string',
        'create_time'    => 'int',
    ];

    /**
     * 追加属性
     */
    protected $append = [
        'type_text',
        'operator_type_text',
    ];

    protected $autoWriteTimestamp = true;
    protected $updateTime         = false;

    /**
     * 入库前
     * @throws Throwable
     */
    public static function onBeforeInsert($model): void
    {
        $user = User::where('id', $model->user_id)->lock(true)->find();
        if (!$user) {
            throw new Exception("The user can't find it");
        }
        if (!$model->memo) {
            throw new Exception("Change note cannot be blank");
        }
        
        // 记录变动前的余额和不可提现金额
        $model->before = $user->money;
        $model->frozen_before = $user->unwith_money;
        
        // 更新用户余额
        $user->money += $model->money;
        
        // 更新不可提现金额（如果有变动）
        if (isset($model->frozen_change) && $model->frozen_change != 0) {
            $user->unwith_money = max(0, $user->unwith_money + $model->frozen_change);
        } else {
            $model->frozen_change = 0;
        }
        
        $user->save();
        
        // 记录变动后的余额和不可提现金额
        $model->after = $user->money;
        $model->frozen_after = $user->unwith_money;
    }

    public static function onBeforeDelete(): bool
    {
        return false;
    }

    public function getMoneyAttr($value): string
    {
        return bcdiv($value ?: 0, 100, 2);
    }

    public function setMoneyAttr($value): string
    {
        return bcmul($value ?: 0, 100, 2);
    }

    public function getBeforeAttr($value): string
    {
        return bcdiv($value ?: 0, 100, 2);
    }

    public function setBeforeAttr($value): string
    {
        return bcmul($value ?: 0, 100, 2);
    }

    public function getAfterAttr($value): string
    {
        return bcdiv($value ?: 0, 100, 2);
    }

    public function setAfterAttr($value): string
    {
        return bcmul($value ?: 0, 100, 2);
    }

    public function getFrozenChangeAttr($value): string
    {
        return bcdiv($value ?: 0, 100, 2);
    }

    public function setFrozenChangeAttr($value): string
    {
        return bcmul($value ?: 0, 100, 2);
    }

    public function getFrozenBeforeAttr($value): string
    {
        return bcdiv($value ?: 0, 100, 2);
    }

    public function setFrozenBeforeAttr($value): string
    {
        return bcmul($value ?: 0, 100, 2);
    }

    public function getFrozenAfterAttr($value): string
    {
        return bcdiv($value ?: 0, 100, 2);
    }

    public function setFrozenAfterAttr($value): string
    {
        return bcmul($value ?: 0, 100, 2);
    }

    public function getTypeTextAttr($value, $row): string
    {
        $typeMap = [
            'ADMIN_ADD'              => '管理员增加',
            'ADMIN_DEDUCT'           => '管理员扣除',
            'BET_DEDUCT'             => '投注扣除',
            'PRIZE_ADD'              => '中奖奖金',
            'WITHDRAW_DEDUCT'        => '提现扣除',
            'WITHDRAW_REFUND_ADD'    => '提现退款',
            'BET_REFUND_ADD'         => '投注退款',
            'COMMISSION_ADD'         => '佣金收入',
            'ACTIVITY_REWARD_ADD'    => '活动奖励',
            'RED_PACKET_SEND'        => '发送红包',
            'RED_PACKET_CANCEL'      => '取消红包',
            'RED_PACKET_RECEIVE'     => '领取红包',
            'AGENT_ADD_TO_USER'      => '代理商给用户加款',
            'AGENT_DEDUCT_FROM_USER' => '代理商给用户扣款',
            'RECHARGE_ADD'           => '充值到账',
            'RECHARGE_GIFT_ADD'      => '充值赠送',
            'RECHARGE_GIFT_DEDUCT'   => '充值赠送扣款',
            'BONUS_ADD'              => '奖金发放',
            'PROMOTION_INCOME'       => '推广收益',
        ];
        return $typeMap[$row['type']] ?? $row['type'];
    }

    public function getOperatorTypeTextAttr($value, $row): string
    {
        $operatorTypeMap = [
            'admin'  => '管理员',
            'agent'  => '代理商',
            'system' => '系统',
        ];
        return $operatorTypeMap[$row['operator_type']] ?? $row['operator_type'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 获取变动类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            'ADMIN_ADD'              => '管理员增加',
            'ADMIN_DEDUCT'           => '管理员扣除',
            'BET_DEDUCT'             => '投注扣除',
            'PRIZE_ADD'              => '中奖奖金',
            'WITHDRAW_DEDUCT'        => '提现扣除',
            'WITHDRAW_REFUND_ADD'    => '提现退款',
            'BET_REFUND_ADD'         => '投注退款',
            'COMMISSION_ADD'         => '佣金收入',
            'ACTIVITY_REWARD_ADD'    => '活动奖励',
            'RED_PACKET_SEND'        => '发送红包',
            'RED_PACKET_CANCEL'      => '取消红包',
            'RED_PACKET_RECEIVE'     => '领取红包',
            'AGENT_ADD_TO_USER'      => '代理商给用户加款',
            'AGENT_DEDUCT_FROM_USER' => '代理商给用户扣款',
            'RECHARGE_ADD'           => '充值到账',
            'RECHARGE_GIFT_ADD'      => '充值赠送',
            'RECHARGE_GIFT_DEDUCT'   => '充值赠送扣款',
            'BONUS_ADD'              => '奖金发放',
            'PROMOTION_INCOME'       => '推广收益',
        ];
    }

    /**
     * 获取操作员类型选项
     * @return array
     */
    public static function getOperatorTypeOptions(): array
    {
        return [
            'admin'  => '管理员',
            'agent'  => '代理商',
            'system' => '系统',
        ];
    }

    /**
     * 获取用户资金统计
     * @param int $userId
     * @return array
     */
    public static function getUserMoneyStats(int $userId): array
    {
        $stats = [
            'total_income' => 0,
            'total_expense' => 0,
            'recharge_total' => 0,
            'withdraw_total' => 0,
            'bet_total' => 0,
            'prize_total' => 0,
        ];

        // 总收入（正数）
        $income = self::where('user_id', $userId)
            ->where('money', '>', 0)
            ->sum('money');
        $stats['total_income'] = bcdiv($income, 100, 2);

        // 总支出（负数）
        $expense = self::where('user_id', $userId)
            ->where('money', '<', 0)
            ->sum('money');
        $stats['total_expense'] = bcdiv(abs($expense), 100, 2);

        // 充值总额
        $recharge = self::where('user_id', $userId)
            ->where('type', 'RECHARGE_ADD')
            ->sum('money');
        $stats['recharge_total'] = bcdiv($recharge, 100, 2);

        // 提现总额
        $withdraw = self::where('user_id', $userId)
            ->where('type', 'WITHDRAW_DEDUCT')
            ->sum('money');
        $stats['withdraw_total'] = bcdiv(abs($withdraw), 100, 2);

        // 投注总额
        $bet = self::where('user_id', $userId)
            ->where('type', 'BET_DEDUCT')
            ->sum('money');
        $stats['bet_total'] = bcdiv(abs($bet), 100, 2);

        // 中奖总额
        $prize = self::where('user_id', $userId)
            ->where('type', 'PRIZE_ADD')
            ->sum('money');
        $stats['prize_total'] = bcdiv($prize, 100, 2);

        return $stats;
    }
    
    /**
     * 创建资金变动记录
     * @param int $userId 用户ID
     * @param string $type 变动类型
     * @param float $money 变动金额（元）
     * @param string $memo 备注
     * @param int $relatedId 关联ID
     * @param int $operatorId 操作员ID
     * @param string $operatorType 操作员类型
     * @return self
     */
    public static function createLog(
        int $userId,
        string $type,
        float $money,
        string $memo,
        int $relatedId = 0,
        int $operatorId = 0,
        string $operatorType = 'system'
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'money' => $money,
            'memo' => $memo,
            'related_id' => $relatedId,
            'operator_id' => $operatorId,
            'operator_type' => $operatorType,
        ]);
    }
    
    /**
     * 获取用户最近的资金记录
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public static function getUserRecentLogs(int $userId, int $limit = 20): array
    {
        return self::where('user_id', $userId)
            ->order('create_time desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取指定时间范围内的资金统计
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getDateRangeStats(int $userId, string $startDate, string $endDate): array
    {
        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $query = self::where('user_id', $userId)
            ->where('create_time', 'between', [$startTime, $endTime]);
            
        $stats = [
            'total_income' => 0,
            'total_expense' => 0,
            'count' => 0,
        ];
        
        $logs = $query->select();
        $stats['count'] = $logs->count();
        
        foreach ($logs as $log) {
            $money = $log->getData('money');
            if ($money > 0) {
                $stats['total_income'] += $money;
            } else {
                $stats['total_expense'] += abs($money);
            }
        }
        
        $stats['total_income'] = bcdiv($stats['total_income'], 100, 2);
        $stats['total_expense'] = bcdiv($stats['total_expense'], 100, 2);
        
        return $stats;
    }
    
    /**
     * 获取按类型分组的统计
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getTypeGroupStats(int $userId, string $startDate = '', string $endDate = ''): array
    {
        $query = self::where('user_id', $userId);
        
        if ($startDate) {
            $query->where('create_time', '>=', strtotime($startDate . ' 00:00:00'));
        }
        if ($endDate) {
            $query->where('create_time', '<=', strtotime($endDate . ' 23:59:59'));
        }
        
        $results = $query->field('type, COUNT(*) as count, SUM(money) as total_money')
            ->group('type')
            ->select()
            ->toArray();
            
        $stats = [];
        $typeOptions = self::getTypeOptions();
        
        foreach ($results as $result) {
            $stats[] = [
                'type' => $result['type'],
                'type_text' => $typeOptions[$result['type']] ?? $result['type'],
                'count' => $result['count'],
                'total_money' => bcdiv($result['total_money'], 100, 2),
            ];
        }
        
        return $stats;
    }
    
    /**
     * 验证用户余额是否足够
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public static function checkUserBalance(int $userId, float $amount): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        
        $amountCent = bcmul($amount, 100, 0);
        return $user->getData('money') >= $amountCent;
    }
}