<?php

namespace app\service;

use app\common\model\RedPacket;
use app\common\model\RedPacketRecord;
use app\common\model\User;
use app\service\FinanceService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

/**
 * 红包服务类
 */
class RedPacketService
{
    /**
     * 创建红包
     */
    public function createRedPacket(array $data): RedPacket
    {
        Db::startTrans();
        try {
            $data['agent_id'] = isset($data['agent_id']) ? $data['agent_id'] : 0;
            if($data['agent_id'] > 0){
                // 验证代理商是否存在
                $agent = User::find($data['agent_id']);
                if (!$agent) {
                    throw new Exception('代理商不存在');
                }
            }
            

            // 生成红包金额分配
            $amountList = $this->generateAmountList(
                $data['type'],
                $data['total_amount'],
                $data['total_count']
            );

            $redPacket = RedPacket::create([
                'target_type' => $data['target_type'],
                'agent_id' => $data['agent_id'],
                'title' => $data['title'],
                'blessing' => $data['blessing'] ?? '',
                'type' => $data['type'],
                'total_amount' => $data['total_amount'],
                'total_count' => $data['total_count'],
                'remaining_count' => $data['total_count'],
                'amount_list' => $amountList,
                'condition_type' => $data['condition_type'] ?? RedPacket::CONDITION_NONE,
                'condition_value' => $data['condition_value'] ?? '',
                'expire_time' => isset($data['expire_time']) ? $data['expire_time'] : 0,
                'status' => RedPacket::STATUS_ACTIVE
            ]);

            // 如果代理商给会员发放红包并从代理商扣除，
            if($data['agent_id'] > 0){
                $financeService = new FinanceService();
                $financeService->adjustUserBalance(
                    $data['agent_id'],
                    -$data['total_amount'],
                    '发放红包：' . $data['title'],
                    'RED_PACKET_SEND'
                );
            }

            Db::commit();
            return $redPacket;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 生成红包金额分配
     */
    private function generateAmountList(string $type, float $totalAmount, int $totalCount): array
    {
        $totalAmountCent = bcmul($totalAmount, 100, 0); // 转换为分
        $amountList = [];

        if ($type === RedPacket::TYPE_FIXED) {
            // 固定红包：平均分配（保持原有逻辑不变）
            $avgAmount = bcdiv($totalAmountCent, $totalCount, 0);
            $remainder = bcmod($totalAmountCent, $totalCount);
            
            for ($i = 0; $i < $totalCount; $i++) {
                $amount = $avgAmount;
                if ($i < $remainder) {
                    $amount = bcadd($amount, 1, 0);
                }
                $amountList[] = [intval($amount)=>0];  //0是金额未使用
            }
        } else {
            // 随机红包：使用正态分布算法
            $amountList = $this->generateNormalDistributionAmounts($totalAmountCent, $totalCount);
        }

        return $amountList;
    }

    /**
     * 使用正态分布生成随机红包金额
     * 特点：金额更均衡，大部分红包接近平均值
     */
    private function generateNormalDistributionAmounts(string $totalAmount, int $count): array
    {
        $amounts = [];
        $remaining = $totalAmount;
        $avgAmount = bcdiv($totalAmount, $count, 0);
        
        // 标准差系数，越小金额越集中，越大波动越大
        $stdDevFactor = 0.4;
        
        for ($i = 0; $i < $count - 1; $i++) {
            // 计算当前剩余红包数
            $remainingCount = $count - $i;
            
            // 计算当前剩余金额的平均值
            $currentAvg = bcdiv($remaining, $remainingCount, 0);
            
            // 生成正态分布的随机金额
            $amount = $this->getNormalDistributedAmount($currentAvg, $stdDevFactor);
            
            // 确保金额在合理范围内（至少1分，不超过剩余金额）
            $amount = max(1, min($amount, intval(bcsub($remaining, $remainingCount - 1, 0))));
            
            $amounts[] = [intval($amount) => 0];  // 0代表未使用红包
            $remaining = bcsub($remaining, $amount, 0);
        }
        
        // 最后一个红包获得剩余金额
        $amounts[] = [intval($remaining) => 0];  // 0代表未使用红包
        
        // 打乱顺序
        shuffle($amounts);
        
        return $amounts;
    }

    /**
     * 生成符合正态分布的随机金额
     */
    private function getNormalDistributedAmount(string $mean, float $stdDevFactor): int
    {
        // 使用Box-Muller变换生成标准正态分布随机数
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        
        // 标准正态分布随机数
        $z0 = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
        
        // 调整均值和标准差
        $stdDev = bcmul($mean, $stdDevFactor, 0);
        $amount = intval(bcadd($mean, bcmul($z0, $stdDev, 0), 0));
        
        return max(1, $amount); // 确保至少1分
    }

    /**
     * 领取红包
     */
    public function receiveRedPacket(int $redPacketId, int $userId, string $ip = '', string $userAgent = ''): array
    {
        Db::startTrans();
        try {
            // 获取红包信息
            $redPacket = RedPacket::find($redPacketId);
            if (!$redPacket) {
                throw new Exception('红包不存在');
            }

            // 检查红包状态
            if (!$redPacket->isAvailable()) {
                throw new Exception('红包已失效或已被领完');
            }

            // 检查用户是否已领取
            if (RedPacketRecord::hasReceived($redPacketId, $userId)) {
                throw new Exception('您已经领取过这个红包了');
            }

            // 检查领取条件
            $this->checkReceiveCondition($redPacket, $userId);

            // 获取红包金额
            $amountList = $redPacket->amount_list;
            if (empty($amountList) || $redPacket->received_count >= count($amountList)) {
                throw new Exception('红包已被领完');
            }

            // 查找未使用的红包金额（值为0的）
            $amount = null;
            $usedIndex = null;
            foreach ($amountList as $index => $amountData) {
                if (is_array($amountData)) {
                    foreach ($amountData as $amountValue => $userId) {
                        if ($userId == 0) { // 未使用的红包
                            $amount = $amountValue;
                            $usedIndex = $index;
                            break 2;
                        }
                    }
                }
            }
            
            if ($amount === null) {
                throw new Exception('红包已被领完');
            }

            // 创建领取记录
            $record = RedPacketRecord::create([
                'red_packet_id' => $redPacketId,
                'user_id' => $userId,
                'amount' => $amount,
                'ip' => $ip,
                'user_agent' => $userAgent
            ]);

            // 更新amount_list，标记红包已被使用
            $amountList[$usedIndex] = [$amount => $userId];
            $redPacket->amount_list = $amountList;
            
            // 更新红包统计
            $redPacket->received_count += 1;
            $redPacket->received_amount = bcadd($redPacket->getAttr('received_amount'), $amount, 0);
            
            // 检查是否领完
            if ($redPacket->received_count >= $redPacket->total_count) {
                $redPacket->status = RedPacket::STATUS_FINISHED;
            }
            
            $redPacket->save();

            // 给用户加钱
            $this->addUserMoney($userId, $amount);

            Db::commit();
            
            return [
                'amount' => bcdiv($amount, 100, 2),
                'blessing' => $redPacket->blessing,
                'title' => $redPacket->title
            ];
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 检查领取条件
     */
    private function checkReceiveCondition(RedPacket $redPacket, int $userId): void
    {
        if ($redPacket->condition_type === RedPacket::CONDITION_NONE) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            throw new Exception('用户不存在');
        }

        switch ($redPacket->condition_type) {
            case RedPacket::CONDITION_MIN_BET:
                // 这里需要根据实际业务逻辑检查用户投注额
                // $userBetAmount = $this->getUserTotalBet($userId);
                // if (bccomp($userBetAmount, $redPacket->condition_value, 2) < 0) {
                //     throw new Exception('您的投注额不满足领取条件');
                // }
                break;
                
            case RedPacket::CONDITION_USER_LEVEL:
                // 这里需要根据实际业务逻辑检查用户等级
                // if ($user->level < intval($redPacket->condition_value)) {
                //     throw new Exception('您的等级不满足领取条件');
                // }
                break;
        }
    }

    /**
     * 给用户加钱
     */
    private function addUserMoney(int $userId, int $amount): void
    {
        $financeService = new FinanceService();
        $amountInYuan = bcdiv($amount, 100, 2); // 转换为元
        $financeService->adjustUserBalance(
            $userId,
            $amountInYuan,
            '领取红包',
            'RED_PACKET_RECEIVE'
        );
    }

    /**
     * 取消红包
     */
    public function cancelRedPacket(int $redPacketId): bool
    {
        Db::startTrans();
        try {
            $redPacket = RedPacket::find($redPacketId);
            if (!$redPacket) {
                throw new Exception('红包不存在');
            }

            if ($redPacket->status !== RedPacket::STATUS_ACTIVE) {
                throw new Exception('只能取消进行中的红包');
            }

            // 如果有用户已经领取了红包，需要从这些用户账户中扣除相应金额
            $records = RedPacketRecord::where('red_packet_id', $redPacketId)->select();
            $financeService = new FinanceService();
            
            foreach ($records as $record) {
                $amountInYuan = bcdiv($record->amount, 100, 2);
                // 从用户账户扣除红包金额
                $financeService->adjustUserBalance(
                    $record->user_id,
                    -$amountInYuan,
                    '红包被取消，扣除已领取金额',
                    'RED_PACKET_CANCEL'
                );
            }

            // 更新红包状态
            $redPacket->status = RedPacket::STATUS_CANCELLED;
            $result = $redPacket->save();
            
            Db::commit();
            return $result;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 检查过期红包
     */
    public function checkExpiredRedPackets(): int
    {
        $count = RedPacket::where('status', RedPacket::STATUS_ACTIVE)
            ->where('expire_time', '<', time())
            ->where('expire_time', '>', 0)
            ->update(['status' => RedPacket::STATUS_EXPIRED]);
            
        return $count;
    }

    /**
     * 获取红包统计信息
     */
    public function getRedPacketStats(int $redPacketId): array
    {
        $redPacket = RedPacket::find($redPacketId);
        if (!$redPacket) {
            throw new Exception('红包不存在');
        }

        return [
            'total_amount' => $redPacket->total_amount,
            'total_count' => $redPacket->total_count,
            'received_amount' => $redPacket->received_amount,
            'received_count' => $redPacket->received_count,
            'remaining_amount' => $redPacket->getRemainingAmount(),
            'remaining_count' => $redPacket->getRemainingCount(),
            'status' => $redPacket->status
        ];
    }

    /**
     * 获取红包领取记录
     */
    public function getRedPacketRecords(int $redPacketId, int $page = 1, int $limit = 20): array
    {
        $redPacket = RedPacket::find($redPacketId);
        if (!$redPacket) {
            throw new Exception('红包不存在');
        }

        $records = RedPacketRecord::alias('r')
            ->leftJoin('user u', 'r.user_id = u.id')
            ->where('r.red_packet_id', $redPacketId)
            ->field('r.id,r.amount,r.create_time,u.username,u.nickname,u.avatar,r.ip,r.user_agent')
            ->order('r.create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);

        $list = [];
        foreach ($records->items() as $item) {
            $list[] = [
                'id' => $item['id'],
                'amount' => $item['amount'], // 转换为元
                'create_time' => $item['create_time'],
                'username' => $item['username'],
                'nickname' => $item['nickname'],
                'avatar' => $item['avatar'],
                'ip' => $item['ip'],
                'user_agent' => $item['user_agent'],
                'time_text' => date('Y-m-d H:i:s', $item['create_time'])
            ];
        }

        return [
            'data' => $list,
            'total' => $records->total(),
            'page' => $page,
            'limit' => $limit,
            'stats' => $this->getRedPacketStats($redPacketId)
        ];
    }
}