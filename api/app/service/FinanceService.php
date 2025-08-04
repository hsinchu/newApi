<?php

namespace app\service;

use app\common\model\UserMoneyLog;
use app\common\model\UserScoreLog;
use app\common\model\User;
use think\exception\ValidateException;

/**
 * 财务服务类
 * 处理用户余额和积分变动相关的业务逻辑
 */
class FinanceService
{
    /**
     * 调整用户余额
     * @param int $userId 用户ID
     * @param float $amount 调整金额（正数为增加，负数为减少）
     * @param string $remark 备注
     * @param string $type 变动类型
     * @param bool $updateGiftMoney 是否同时更新赠送金额
     * @return bool
     * @throws \Exception
     */
    public function adjustUserBalance(int $userId, float $amount, string $remark = '', string $type = 'ADMIN_ADD', bool $updateGiftMoney = false): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 检查余额是否足够（如果是减少操作）
        if ($amount < 0 && $user->money < abs($amount)) {
            throw new ValidateException('用户余额不足');
        }

        // 根据操作类型确定是否需要调整冻结金额
        $frozenAmount = 0;
        
        // 增加冻结余额的操作类型
        $freezeIncreaseTypes = [
            'COMMISSION_ADD',        // 佣金收入
            'ADMIN_ADD',             // 管理员充值
            'RECHARGE_ADD',          // 用户充值
            'RECHARGE_GIFT_ADD',     // 充值赠送
            'BET_REFUND_ADD',        // 用户投注退款
            'ACTIVITY_REWARD_ADD',   // 活动奖励
            'RED_PACKET_CANCEL',     // 代理商撤销红包
            'RED_PACKET_RECEIVE'     // 用户领取红包
        ];
        
        // 减少冻结余额的操作类型
        $freezeDecreaseTypes = [
            'ADMIN_DEDUCT',          // 管理员扣款
            'BET_DEDUCT',            // 用户投注
            'RED_PACKET_SEND',       // 代理商发放红包
            'RECHARGE_GIFT_DEDUCT'   // 充值赠送扣款
        ];
        
        // 不影响冻结余额的操作类型（返佣等直接可用余额）
        $noFreezeTypes = [
            'PRIZE_ADD',             // 中奖奖金
            'AGENT_ADD_TO_USER',     // 代理商给用户加款
            'AGENT_DEDUCT_FROM_USER' // 代理商给用户扣款
        ];
        
        // 计算冻结金额变动
        if (in_array($type, $freezeIncreaseTypes)) {
            $frozenAmount = abs($amount); // 冻结金额增加
        } elseif (in_array($type, $freezeDecreaseTypes)) {
            $frozenAmount = -abs($amount); // 冻结金额减少
        } elseif (in_array($type, $noFreezeTypes)) {
            $frozenAmount = 0; // 不影响冻结金额，直接可用
        }
        // 其他类型不影响冻结金额
        
        // 如果需要更新gift_money字段
        if ($updateGiftMoney && $amount != 0) {
            if ($type === 'RECHARGE_GIFT_ADD') {
                // 充值赠送增加gift_money
                $user->gift_money += $amount;
                $user->save();
            }
        }
        
        $moneyLog = new UserMoneyLog();
            $saveData = [
                'user_id' => $userId,
                'money' => $amount,
                'memo' => $remark ?: '管理员调整余额',
                'type' => $type,
                'operator_type' => 'system'
            ];
            // 只有当冻结金额有变动时才设置frozen_money字段
            if ($frozenAmount != 0) {
                $saveData['frozen_change'] = $frozenAmount;
            }
            
            $moneyLog->save($saveData);
            
            return true;
    }

    /**
     * 调整用户积分
     * @param int $userId 用户ID
     * @param int $score 调整积分（正数为增加，负数为减少）
     * @param string $remark 备注
     * @param string $type 变动类型
     * @return bool
     * @throws \Exception
     */
    public function adjustUserScore(int $userId, int $score, string $remark = '', string $type = 'admin_adjust'): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 检查积分是否足够（如果是减少操作）
        if ($score < 0 && $user->score < abs($score)) {
            throw new ValidateException('用户积分不足');
        }

        // 创建积分变动记录，模型的onBeforeInsert会自动处理用户积分更新
        $scoreLog = new UserScoreLog();
        return $scoreLog->save([
            'user_id' => $userId,
            'score' => $score,
            'memo' => $remark ?: '管理员调整积分',
            'type' => $type
        ]);
    }

    /**
     * 获取用户余额变动记录
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getUserMoneyLogs(int $userId, int $page = 1, int $limit = 15): array
    {
        $result = UserMoneyLog::where('user_id', $userId)
                             ->order('id', 'desc')
                             ->paginate($limit, false, ['page' => $page]);

        return [
            'list' => $result->items(),
            'total' => $result->total(),
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * 获取用户积分变动记录
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getUserScoreLogs(int $userId, int $page = 1, int $limit = 15): array
    {
        $result = UserScoreLog::where('user_id', $userId)
                              ->order('id', 'desc')
                              ->paginate($limit, false, ['page' => $page]);

        return [
            'list' => $result->items(),
            'total' => $result->total(),
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * 批量调整用户余额
     * @param array $userIds 用户ID数组
     * @param float $amount 调整金额
     * @param string $remark 备注
     * @param string $type 变动类型
     * @return bool
     * @throws \Exception
     */
    public function batchAdjustUserBalance(array $userIds, float $amount, string $remark = '', string $type = 'admin_adjust'): bool
    {
        foreach ($userIds as $userId) {
            $this->adjustUserBalance($userId, $amount, $remark, $type);
        }
        return true;
    }

    /**
     * 批量调整用户积分
     * @param array $userIds 用户ID数组
     * @param int $score 调整积分
     * @param string $remark 备注
     * @param string $type 变动类型
     * @return bool
     * @throws \Exception
     */
    public function batchAdjustUserScore(array $userIds, int $score, string $remark = '', string $type = 'admin_adjust'): bool
    {
        foreach ($userIds as $userId) {
            $this->adjustUserScore($userId, $score, $remark, $type);
        }
        return true;
    }

    /**
     * 获取用户当前余额和积分
     * @param int $userId 用户ID
     * @return array
     * @throws \Exception
     */
    public function getUserBalance(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        return [
            'money' => $user->money,
            'score' => $user->score
        ];
    }

    /**
     * 添加余额变动日志
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public static function addBalanceLog(array $data): bool
    {
        $moneyLog = new UserMoneyLog();
        return $moneyLog->save([
            'user_id' => $data['user_id'],
            'money' => $data['amount'],
            'memo' => $data['remark'],
            'type' => $data['type'] ?? 'recharge',
            'order_no' => $data['order_no'] ?? '',
            'before_money' => $data['before_balance'] ?? 0,
            'after_money' => $data['after_balance'] ?? 0,
        ]);
    }

    /**
     * 投注扣款
     * @param int $userId 用户ID
     * @param int $amount 扣款金额（分）
     * @param string $orderNo 订单号
     * @param string $remark 备注
     * @return bool
     * @throws \Exception
     */
    public function deductForBet(int $userId, int $amount, string $orderNo, string $remark = ''): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 将分转换为元进行比较
        $amountInYuan = $amount / 100;
        if ($user->money < $amountInYuan) {
            throw new ValidateException('用户余额不足');
        }

        // 开启事务
        User::startTrans();
        try {
            // 创建余额变动记录
            $moneyLog = new UserMoneyLog();
            $moneyLog->save([
                'user_id' => $userId,
                'money' => -$amountInYuan, // 负数表示扣款
                'memo' => $remark ?: '投注扣款',
                'type' => 'BET_DEDUCT',
                'order_no' => $orderNo,
                'frozen_money' => -$amountInYuan // 减少冻结金额
            ]);
            
            User::commit();
            return true;
        } catch (\Exception $e) {
            User::rollback();
            throw $e;
        }
    }

    /**
     * 投注中奖加款
     * @param int $userId 用户ID
     * @param int $amount 中奖金额（分）
     * @param string $orderNo 订单号
     * @param string $remark 备注
     * @return bool
     * @throws \Exception
     */
    public function addForWinning(int $userId, int $amount, string $orderNo, string $remark = ''): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 将分转换为元
        $amountInYuan = $amount / 100;

        // 开启事务
        User::startTrans();
        try {
            // 创建余额变动记录
            $moneyLog = new UserMoneyLog();
            $moneyLog->save([
                'user_id' => $userId,
                'money' => $amountInYuan, // 正数表示加款
                'memo' => $remark ?: '投注中奖',
                'type' => 'BET_WIN_ADD',
                'order_no' => $orderNo,
                'frozen_money' => $amountInYuan // 增加冻结金额
            ]);
            
            User::commit();
            return true;
        } catch (\Exception $e) {
            User::rollback();
            throw $e;
        }
    }

    /**
     * 投注退款
     * @param int $userId 用户ID
     * @param int $amount 退款金额（分）
     * @param string $orderNo 订单号
     * @param string $remark 备注
     * @return bool
     * @throws \Exception
     */
    public function refundForBet(int $userId, int $amount, string $orderNo, string $remark = ''): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 将分转换为元
        $amountInYuan = $amount / 100;

        // 开启事务
        User::startTrans();
        try {
            // 创建余额变动记录
            $moneyLog = new UserMoneyLog();
            $moneyLog->save([
                'user_id' => $userId,
                'money' => $amountInYuan, // 正数表示退款
                'memo' => $remark ?: '投注退款',
                'type' => 'BET_REFUND_ADD',
                'order_no' => $orderNo,
                'frozen_money' => $amountInYuan // 增加冻结金额
            ]);
            
            User::commit();
            return true;
        } catch (\Exception $e) {
            User::rollback();
            throw $e;
        }
    }

    /**
     * 检查用户余额是否足够
     * @param int $userId 用户ID
     * @param int $amount 需要的金额（分）
     * @return bool
     * @throws \Exception
     */
    public function checkBalance(int $userId, int $amount): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 将分转换为元进行比较
        $amountInYuan = $amount / 100;
        return $user->money >= $amountInYuan;
    }
}