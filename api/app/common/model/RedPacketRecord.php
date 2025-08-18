<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * RedPacketRecord 红包领取记录模型
 */
class RedPacketRecord extends Model
{
    protected $name = 'red_packet_record';
    
    protected $autoWriteTimestamp = 'create_time';
    
    protected $updateTime = false;
    
    protected $append = [
        'amount_text',
        'receive_time_text',
        'red_packet_title',
        'user_nickname'
    ];



    /**
     * 关联红包
     */
    public function redPacket(): BelongsTo
    {
        return $this->belongsTo(RedPacket::class, 'red_packet_id');
    }

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 获取领取时间格式化
     */
    public function getCreateTimeTextAttr(): string
    {
        return date('Y-m-d H:i:s', $this->create_time);
    }

    /**
     * 根据红包ID统计领取情况
     */
    public static function getStatsByRedPacketId(int $redPacketId): array
    {
        $records = self::where('red_packet_id', $redPacketId)->select();
        
        $totalAmount = 0;
        $totalCount = count($records);
        
        foreach ($records as $record) {
            $totalAmount = bcadd($totalAmount, $record->amount, 2);
        }
        
        return [
            'total_count' => $totalCount,
            'total_amount' => $totalAmount
        ];
    }

    /**
     * 检查用户是否已领取指定红包
     */
    public static function hasReceived(int $redPacketId, int $userId): bool
    {
        return self::where('red_packet_id', $redPacketId)
            ->where('user_id', $userId)
            ->count() > 0;
    }
    
    /**
     * 金额文本访问器
     */
    public function getAmountTextAttr($value, $data): string
    {
        return $data['amount'] . '元';
    }
    
    /**
     * 领取时间文本访问器
     */
    public function getReceiveTimeTextAttr($value, $data): string
    {
        return $data['receive_time'] ? date('Y-m-d H:i:s', $data['receive_time']) : '';
    }
    
    /**
     * 红包标题访问器
     */
    public function getRedPacketTitleAttr($value, $data): string
    {
        return $this->redPacket ? $this->redPacket->title : '';
    }
    
    /**
     * 用户昵称访问器
     */
    public function getUserNicknameAttr($value, $data): string
    {
        return $this->user ? $this->user->nickname : '';
    }
    
    /**
     * 获取用户领取记录
     */
    public static function getUserRecords(int $userId, int $page = 1, int $limit = 20): array
    {
        $query = self::where('user_id', $userId)
            ->with(['redPacket', 'user'])
            ->order('create_time', 'desc');
            
        $total = $query->count();
        $records = $query->page($page, $limit)->select();
        
        return [
            'total' => $total,
            'records' => $records,
            'page' => $page,
            'limit' => $limit
        ];
    }
    
    /**
     * 获取红包领取排行榜
     */
    public static function getTopReceivers(int $redPacketId, int $limit = 10): array
    {
        return self::where('red_packet_id', $redPacketId)
            ->with('user')
            ->order('amount', 'desc')
            ->order('create_time', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取日期范围内的领取统计
     */
    public static function getDateRangeStats(string $startDate, string $endDate, int $userId = null): array
    {
        $startTime = strtotime($startDate . ' 00:00:00');
        $endTime = strtotime($endDate . ' 23:59:59');
        
        $query = self::where('receive_time', '>=', $startTime)
            ->where('receive_time', '<=', $endTime);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $stats = $query->field([
            'COUNT(*) as total_count',
            'SUM(amount) as total_amount',
            'AVG(amount) as avg_amount',
            'MAX(amount) as max_amount',
            'MIN(amount) as min_amount'
        ])->find();
        
        return [
            'total_count' => $stats['total_count'] ?? 0,
            'total_amount' => $stats['total_amount'] ?? 0,
            'avg_amount' => $stats['avg_amount'] ?? 0,
            'max_amount' => $stats['max_amount'] ?? 0,
            'min_amount' => $stats['min_amount'] ?? 0
        ];
    }
    
    /**
     * 获取最近领取记录
     */
    public static function getRecentRecords(int $limit = 50): array
    {
        return self::with(['redPacket', 'user'])
            ->order('create_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}