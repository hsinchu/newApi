<?php

namespace app\common\model;

use Throwable;
use think\model;
use think\Exception;
use think\model\relation\BelongsTo;

/**
 * UserScoreLog 模型
 * 1. 创建积分日志自动完成会员积分的添加
 * 2. 创建积分日志时，请开启事务
 */
class UserScoreLog extends model
{
    /**
     * 表名
     */
    protected $name = 'user_score_log';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'           => 'int',
        'user_id'      => 'int',
        'type'         => 'string',
        'score'        => 'int',
        'before'       => 'int',
        'after'        => 'int',
        'related_id'   => 'int',
        'operator_id'  => 'int',
        'operator_type'=> 'string',
        'memo'         => 'string',
        'create_time'  => 'int',
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
        $model->before = $user->score;

        $user->score += $model->score;
        $user->save();

        $model->after = $user->score;
    }

    public static function onBeforeDelete(): bool
    {
        return false;
    }

    public function getTypeTextAttr($value, $row): string
    {
        $typeMap = [
            'ADMIN_ADD'         => '管理员增加',
            'ADMIN_DEDUCT'      => '管理员扣除',
            'SIGN_IN'           => '签到奖励',
            'TASK_REWARD'       => '任务奖励',
            'ACTIVITY_REWARD'   => '活动奖励',
            'EXCHANGE_DEDUCT'   => '积分兑换',
            'PROMOTION_REWARD'  => '推广奖励',
            'LOTTERY_REWARD'    => '抽奖奖励',
            'SYSTEM_ADJUST'     => '系统调整',
            'REFUND_ADD'        => '退款返还',
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
            'ADMIN_ADD'         => '管理员增加',
            'ADMIN_DEDUCT'      => '管理员扣除',
            'SIGN_IN'           => '签到奖励',
            'TASK_REWARD'       => '任务奖励',
            'ACTIVITY_REWARD'   => '活动奖励',
            'EXCHANGE_DEDUCT'   => '积分兑换',
            'PROMOTION_REWARD'  => '推广奖励',
            'LOTTERY_REWARD'    => '抽奖奖励',
            'SYSTEM_ADJUST'     => '系统调整',
            'REFUND_ADD'        => '退款返还',
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
     * 获取用户积分统计
     * @param int $userId
     * @return array
     */
    public static function getUserScoreStats(int $userId): array
    {
        $stats = [
            'total_income' => 0,
            'total_expense' => 0,
            'sign_in_total' => 0,
            'task_total' => 0,
            'exchange_total' => 0,
        ];

        // 总收入（正数）
        $income = self::where('user_id', $userId)
            ->where('score', '>', 0)
            ->sum('score');
        $stats['total_income'] = $income;

        // 总支出（负数）
        $expense = self::where('user_id', $userId)
            ->where('score', '<', 0)
            ->sum('score');
        $stats['total_expense'] = abs($expense);

        // 签到总积分
        $signIn = self::where('user_id', $userId)
            ->where('type', 'SIGN_IN')
            ->sum('score');
        $stats['sign_in_total'] = $signIn;

        // 任务总积分
        $task = self::where('user_id', $userId)
            ->where('type', 'TASK_REWARD')
            ->sum('score');
        $stats['task_total'] = $task;

        // 兑换总积分
        $exchange = self::where('user_id', $userId)
            ->where('type', 'EXCHANGE_DEDUCT')
            ->sum('score');
        $stats['exchange_total'] = abs($exchange);

        return $stats;
    }
}