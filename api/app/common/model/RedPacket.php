<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * RedPacket 红包模型
 */
class RedPacket extends Model
{
    protected $name = 'red_packet';
    protected $autoWriteTimestamp = true;
    
    protected $append = ['type_text', 'status_text', 'condition_type_text', 'remaining_count', 'remaining_amount', 'expire_time_text'];
    
    // 红包类型
    const TYPE_RANDOM = 'RANDOM';
    const TYPE_FIXED = 'FIXED';
    
    // 红包状态
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_FINISHED = 'FINISHED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_EXPIRED = 'EXPIRED';
    
    // 领取条件类型
    const CONDITION_NONE = 'NONE';
    const CONDITION_MIN_BET = 'MIN_BET';
    const CONDITION_USER_LEVEL = 'USER_LEVEL';



    /**
     * 金额分配访问器 - JSON转数组
     */
    public function getAmountListAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 金额分配修改器 - 数组转JSON
     */
    public function setAmountListAttr($value): string
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 过期时间访问器
     */
    public function getExpireTimeAttr($value): string
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 过期时间修改器
     */
    public function setExpireTimeAttr($value): int
    {
        return $value ? strtotime($value) : 0;
    }

    /**
     * 关联代理商
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * 关联领取记录
     */
    public function records(): HasMany
    {
        return $this->hasMany(RedPacketRecord::class, 'red_packet_id');
    }

    /**
     * 获取红包类型选项
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_RANDOM => '随机红包',
            self::TYPE_FIXED => '固定红包'
        ];
    }

    /**
     * 获取红包状态选项
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => '进行中',
            self::STATUS_FINISHED => '已完成',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_EXPIRED => '已过期'
        ];
    }

    /**
     * 获取领取条件类型选项
     */
    public static function getConditionTypeOptions(): array
    {
        return [
            self::CONDITION_NONE => '无条件',
            self::CONDITION_MIN_BET => '最低投注额',
            self::CONDITION_USER_LEVEL => '用户等级'
        ];
    }

    /**
     * 检查红包是否可领取
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ACTIVE 
            && $this->received_count < $this->total_count
            && ($this->expire_time === null || $this->expire_time > time());
    }

    /**
     * 获取剩余红包数量
     */
    public function getRemainingCount(): int
    {
        return $this->total_count - $this->received_count;
    }

    /**
     * 获取剩余金额
     */
    public function getRemainingAmount(): string
    {
        return bcsub($this->total_amount, $this->received_amount, 2);
    }
    
    /**
     * 红包类型文本访问器
     */
    public function getTypeTextAttr($value, $data): string
    {
        $types = self::getTypeOptions();
        return $types[$data['type']] ?? $data['type'];
    }
    
    /**
     * 红包状态文本访问器
     */
    public function getStatusTextAttr($value, $data): string
    {
        $statuses = self::getStatusOptions();
        return $statuses[$data['status']] ?? $data['status'];
    }
    
    /**
     * 领取条件类型文本访问器
     */
    public function getConditionTypeTextAttr($value, $data): string
    {
        $conditions = self::getConditionTypeOptions();
        return $conditions[$data['condition_type']] ?? $data['condition_type'];
    }
    
    /**
     * 剩余数量访问器
     */
    public function getRemainingCountAttr($value, $data): int
    {
        return $data['total_count'] - $data['received_count'];
    }
    
    /**
     * 剩余金额访问器
     */
    public function getRemainingAmountAttr($value, $data): string
    {
        return bcsub($data['total_amount'], $data['received_amount'], 2);
    }
    
    /**
     * 过期时间文本访问器
     */
    public function getExpireTimeTextAttr($value, $data): string
    {
        return $data['expire_time'] ? date('Y-m-d H:i:s', $data['expire_time']) : '永不过期';
    }
    
    /**
     * 生成红包金额分配
     */
    public function generateAmountList(): array
    {
        $totalAmount = bcmul($this->total_amount, 100, 0); // 转为分进行计算
        $totalCount = $this->total_count;
        $amountList = [];
        
        if ($this->type === self::TYPE_FIXED) {
            // 固定红包：平均分配
            $avgAmount = intval($totalAmount / $totalCount);
            for ($i = 0; $i < $totalCount; $i++) {
                $amountList[] = $avgAmount;
            }
            // 处理余数
            $remainder = $totalAmount - ($avgAmount * $totalCount);
            if ($remainder > 0) {
                $amountList[0] += $remainder;
            }
        } else {
            // 随机红包：随机分配
            $remaining = $totalAmount;
            for ($i = 0; $i < $totalCount - 1; $i++) {
                $max = intval($remaining / ($totalCount - $i) * 2);
                $amount = mt_rand(1, $max);
                $amountList[] = $amount;
                $remaining -= $amount;
            }
            $amountList[] = $remaining; // 最后一个红包是剩余金额
        }
        
        // 打乱顺序
        shuffle($amountList);
        
        return $amountList;
    }
    
    /**
     * 领取红包
     */
    public function receiveRedPacket(int $userId): array
    {
        if (!$this->isAvailable()) {
            return ['success' => false, 'message' => '红包不可领取'];
        }
        
        // 检查用户是否已领取
        $existRecord = RedPacketRecord::where('red_packet_id', $this->id)
            ->where('user_id', $userId)
            ->find();
        if ($existRecord) {
            return ['success' => false, 'message' => '您已领取过此红包'];
        }
        
        // 获取红包金额
        $amountList = $this->amount_list;
        if (empty($amountList) || $this->received_count >= count($amountList)) {
            return ['success' => false, 'message' => '红包已被抢完'];
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
            return ['success' => false, 'message' => '红包已被抢完'];
        }
        
        // 创建领取记录
        $record = RedPacketRecord::create([
            'red_packet_id' => $this->id,
            'user_id' => $userId,
            'amount' => $amount,
            'receive_time' => time()
        ]);
        
        // 更新amount_list，标记红包已被使用
        $amountList[$usedIndex] = [$amount => $userId];
        $this->amount_list = $amountList;
        
        // 更新红包统计
        $this->received_count += 1;
        $this->received_amount = bcadd($this->received_amount, bcdiv($amount, 100, 2), 2);
        
        // 检查是否已完成
        if ($this->received_count >= $this->total_count) {
            $this->status = self::STATUS_FINISHED;
        }
        
        $this->save();
        
        return [
            'success' => true,
            'amount' => bcdiv($amount, 100, 2),
            'record_id' => $record->id
        ];
    }
    
    /**
     * 取消红包
     */
    public function cancelRedPacket(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }
    
    /**
     * 检查红包是否过期
     */
    public function checkExpired(): bool
    {
        if ($this->expire_time && $this->expire_time <= time() && $this->status === self::STATUS_ACTIVE) {
            $this->status = self::STATUS_EXPIRED;
            $this->save();
            return true;
        }
        return false;
    }
    
    /**
     * 获取红包统计
     */
    public static function getStatistics(int $agentId = null): array
    {
        $query = self::query();
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }
        
        $stats = $query->field([
            'COUNT(*) as total_count',
            'SUM(total_amount) as total_amount',
            'SUM(received_amount) as received_amount',
            'SUM(total_count) as total_packets',
            'SUM(received_count) as received_packets'
        ])->find();
        
        return [
            'total_count' => $stats['total_count'] ?? 0,
            'total_amount' => $stats['total_amount'] ?? 0,
            'received_amount' => $stats['received_amount'] ?? 0,
            'total_packets' => $stats['total_packets'] ?? 0,
            'received_packets' => $stats['received_packets'] ?? 0,
            'remaining_amount' => bcsub($stats['total_amount'] ?? 0, $stats['received_amount'] ?? 0, 2)
        ];
    }
}