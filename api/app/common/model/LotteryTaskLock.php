<?php

namespace app\common\model;

use think\Model;

/**
 * 彩票任务锁模型
 * Class LotteryTaskLock
 * @package app\common\model
 */
class LotteryTaskLock extends Model
{
    // 设置表名
    protected $name = 'fa_lottery_task_lock';
    
    // 设置主键
    protected $pk = 'id';
    
    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'task_type' => 'string',
        'lottery_code' => 'string',
        'period_no' => 'string',
        'lock_key' => 'string',
        'lock_time' => 'bigint',
        'expire_time' => 'bigint',
        'process_id' => 'string',
        'status' => 'string',
        'create_time' => 'bigint',
        'update_time' => 'bigint'
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段取整
    protected $dateFormat = false;
    
    // 创建时间字段
    protected $createTime = 'create_time';
    
    // 更新时间字段
    protected $updateTime = 'update_time';
    
    // 状态常量
    const STATUS_LOCKED = 'LOCKED';      // 已锁定
    const STATUS_RELEASED = 'RELEASED';  // 已释放
    const STATUS_EXPIRED = 'EXPIRED';    // 已过期
    
    // 任务类型常量
    const TASK_TYPE_AUTODRAW = 'autodraw';  // 自动开奖
    const TASK_TYPE_SETTLE = 'settle';      // 结算
    
    /**
     * 获取锁
     * @param string $taskType 任务类型
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @param int $expireSeconds 过期时间（秒）
     * @return bool
     */
    public static function acquireLock($taskType, $lotteryCode, $periodNo = '', $expireSeconds = 300)
    {
        $lockKey = self::generateLockKey($taskType, $lotteryCode, $periodNo);
        $currentTime = time() * 1000;
        $expireTime = $currentTime + ($expireSeconds * 1000);
        
        try {
            // 清理过期锁
            self::cleanExpiredLocks();
            
            // 检查是否已存在有效锁
            $existingLock = self::where('lock_key', $lockKey)
                ->where('status', self::STATUS_LOCKED)
                ->where('expire_time', '>', $currentTime)
                ->find();
            
            if ($existingLock) {
                return false; // 锁已存在
            }
            
            // 创建新锁
            $lockData = [
                'task_type' => $taskType,
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo,
                'lock_key' => $lockKey,
                'lock_time' => $currentTime,
                'expire_time' => $expireTime,
                'process_id' => getmypid(),
                'status' => self::STATUS_LOCKED
            ];
            
            $result = self::create($lockData);
            return $result !== false;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 释放锁
     * @param string $taskType 任务类型
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @return bool
     */
    public static function releaseLock($taskType, $lotteryCode, $periodNo = '')
    {
        $lockKey = self::generateLockKey($taskType, $lotteryCode, $periodNo);
        
        try {
            $result = self::where('lock_key', $lockKey)
                ->where('status', self::STATUS_LOCKED)
                ->update([
                    'status' => self::STATUS_RELEASED,
                    'update_time' => time() * 1000
                ]);
            
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 检查锁是否存在
     * @param string $taskType 任务类型
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @return bool
     */
    public static function isLocked($taskType, $lotteryCode, $periodNo = '')
    {
        $lockKey = self::generateLockKey($taskType, $lotteryCode, $periodNo);
        $currentTime = time() * 1000;
        
        $count = self::where('lock_key', $lockKey)
            ->where('status', self::STATUS_LOCKED)
            ->where('expire_time', '>', $currentTime)
            ->count();
        
        return $count > 0;
    }
    
    /**
     * 清理过期锁
     * @return int 清理数量
     */
    public static function cleanExpiredLocks()
    {
        $currentTime = time() * 1000;
        
        try {
            $result = self::where('status', self::STATUS_LOCKED)
                ->where('expire_time', '<=', $currentTime)
                ->update([
                    'status' => self::STATUS_EXPIRED,
                    'update_time' => $currentTime
                ]);
            
            return $result;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * 生成锁键
     * @param string $taskType 任务类型
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @return string
     */
    private static function generateLockKey($taskType, $lotteryCode, $periodNo = '')
    {
        if (empty($periodNo)) {
            return "lock:{$taskType}:{$lotteryCode}";
        }
        return "lock:{$taskType}:{$lotteryCode}:{$periodNo}";
    }
    
    /**
     * 获取锁信息
     * @param string $taskType 任务类型
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @return LotteryTaskLock|null
     */
    public static function getLockInfo($taskType, $lotteryCode, $periodNo = '')
    {
        $lockKey = self::generateLockKey($taskType, $lotteryCode, $periodNo);
        
        return self::where('lock_key', $lockKey)
            ->where('status', self::STATUS_LOCKED)
            ->order('create_time', 'desc')
            ->find();
    }
    
    /**
     * 强制释放锁（管理员操作）
     * @param int $lockId 锁ID
     * @return bool
     */
    public static function forceRelease($lockId)
    {
        try {
            $result = self::where('id', $lockId)
                ->update([
                    'status' => self::STATUS_RELEASED,
                    'update_time' => time() * 1000
                ]);
            
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取活跃锁列表
     * @return \think\Collection
     */
    public static function getActiveLocks()
    {
        $currentTime = time() * 1000;
        
        return self::where('status', self::STATUS_LOCKED)
            ->where('expire_time', '>', $currentTime)
            ->order('create_time', 'desc')
            ->select();
    }
    
    /**
     * 获取状态文本
     * @return string
     */
    public function getStatusTextAttr()
    {
        $statusMap = [
            self::STATUS_LOCKED => '已锁定',
            self::STATUS_RELEASED => '已释放',
            self::STATUS_EXPIRED => '已过期'
        ];
        
        return $statusMap[$this->status] ?? '未知状态';
    }
    
    /**
     * 获取任务类型文本
     * @return string
     */
    public function getTaskTypeTextAttr()
    {
        $typeMap = [
            self::TASK_TYPE_AUTODRAW => '自动开奖',
            self::TASK_TYPE_SETTLE => '结算'
        ];
        
        return $typeMap[$this->task_type] ?? '未知类型';
    }
    
    /**
     * 格式化锁定时间
     * @param $value
     * @return string
     */
    public function getLockTimeTextAttr($value)
    {
        return $this->lock_time ? date('Y-m-d H:i:s', $this->lock_time / 1000) : '';
    }
    
    /**
     * 格式化过期时间
     * @param $value
     * @return string
     */
    public function getExpireTimeTextAttr($value)
    {
        return $this->expire_time ? date('Y-m-d H:i:s', $this->expire_time / 1000) : '';
    }
    
    /**
     * 检查是否已过期
     * @return bool
     */
    public function isExpired()
    {
        return $this->expire_time <= (time() * 1000);
    }
}