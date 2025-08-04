<?php

namespace app\service;

use think\facade\Cache;
use think\facade\Log;

/**
 * 红包缓存服务类
 * 统一管理红包相关的缓存操作
 */
class RedPacketCacheService
{
    // 缓存键前缀
    const CACHE_PREFIX = 'redpacket:';
    
    // 缓存时间常量（秒）
    const CACHE_TIME_USER_BET = 300;        // 用户投注金额缓存5分钟
    const CACHE_TIME_AVAILABLE = 60;        // 可用红包缓存1分钟
    const CACHE_TIME_USER_CLAIMED = 300;    // 用户已领取红包缓存5分钟
    const CACHE_TIME_REDPACKET_INFO = 600;  // 红包信息缓存10分钟
    
    /**
     * 获取用户今日投注金额缓存键
     * @param int $userId
     * @return string
     */
    public static function getUserBetCacheKey($userId)
    {
        $today = date('Y-m-d');
        return self::CACHE_PREFIX . "user_bet:{$userId}:{$today}";
    }
    
    /**
     * 获取用户可用红包缓存键
     * @param int $userId
     * @return string
     */
    public static function getAvailableCacheKey($userId)
    {
        return self::CACHE_PREFIX . "available:{$userId}";
    }
    
    /**
     * 获取用户已领取红包缓存键
     * @param int $userId
     * @return string
     */
    public static function getClaimedCacheKey($userId)
    {
        return self::CACHE_PREFIX . "claimed:{$userId}";
    }
    
    /**
     * 获取红包信息缓存键
     * @param int $redPacketId
     * @return string
     */
    public static function getRedPacketInfoCacheKey($redPacketId)
    {
        return self::CACHE_PREFIX . "info:{$redPacketId}";
    }
    
    /**
     * 缓存用户今日投注金额
     * @param int $userId
     * @param float $amount
     * @return bool
     */
    public static function cacheUserTodayBetAmount($userId, $amount)
    {
        $key = self::getUserBetCacheKey($userId);
        return Cache::set($key, $amount, self::CACHE_TIME_USER_BET);
    }
    
    /**
     * 获取用户今日投注金额缓存
     * @param int $userId
     * @return float|null
     */
    public static function getUserTodayBetAmount($userId)
    {
        $key = self::getUserBetCacheKey($userId);
        return Cache::get($key);
    }
    
    /**
     * 缓存用户可用红包列表
     * @param int $userId
     * @param array $redPackets
     * @return bool
     */
    public static function cacheAvailableRedPackets($userId, $redPackets)
    {
        $key = self::getAvailableCacheKey($userId);
        return Cache::set($key, $redPackets, self::CACHE_TIME_AVAILABLE);
    }
    
    /**
     * 获取用户可用红包列表缓存
     * @param int $userId
     * @return array|null
     */
    public static function getAvailableRedPackets($userId)
    {
        $key = self::getAvailableCacheKey($userId);
        return Cache::get($key);
    }
    
    /**
     * 缓存用户已领取红包ID列表
     * @param int $userId
     * @param array $claimedIds
     * @return bool
     */
    public static function cacheClaimedRedPackets($userId, $claimedIds)
    {
        $key = self::getClaimedCacheKey($userId);
        return Cache::set($key, $claimedIds, self::CACHE_TIME_USER_CLAIMED);
    }
    
    /**
     * 获取用户已领取红包ID列表缓存
     * @param int $userId
     * @return array|null
     */
    public static function getClaimedRedPackets($userId)
    {
        $key = self::getClaimedCacheKey($userId);
        return Cache::get($key);
    }
    
    /**
     * 缓存红包信息
     * @param int $redPacketId
     * @param array $redPacketInfo
     * @return bool
     */
    public static function cacheRedPacketInfo($redPacketId, $redPacketInfo)
    {
        $key = self::getRedPacketInfoCacheKey($redPacketId);
        return Cache::set($key, $redPacketInfo, self::CACHE_TIME_REDPACKET_INFO);
    }
    
    /**
     * 获取红包信息缓存
     * @param int $redPacketId
     * @return array|null
     */
    public static function getRedPacketInfo($redPacketId)
    {
        $key = self::getRedPacketInfoCacheKey($redPacketId);
        return Cache::get($key);
    }
    
    /**
     * 清除用户相关的所有红包缓存
     * @param int $userId
     * @return bool
     */
    public static function clearUserRedPacketCache($userId)
    {
        $keys = [
            self::getUserBetCacheKey($userId),
            self::getAvailableCacheKey($userId),
            self::getClaimedCacheKey($userId)
        ];
        
        $success = true;
        foreach ($keys as $key) {
            if (!Cache::delete($key)) {
                $success = false;
                Log::error("Failed to clear cache: {$key}");
            }
        }
        
        return $success;
    }
    
    /**
     * 清除红包信息缓存
     * @param int $redPacketId
     * @return bool
     */
    public static function clearRedPacketInfoCache($redPacketId)
    {
        $key = self::getRedPacketInfoCacheKey($redPacketId);
        return Cache::delete($key);
    }
    
    /**
     * 清除所有红包相关缓存
     * @return bool
     */
    public static function clearAllRedPacketCache()
    {
        try {
            // 获取所有红包相关的缓存键
            $pattern = self::CACHE_PREFIX . '*';
            
            // 注意：这个方法的实现取决于你使用的缓存驱动
            // Redis驱动可以使用keys命令，但在生产环境中要谨慎使用
            if (Cache::getConfig('default') === 'redis') {
                $redis = Cache::store('redis')->handler();
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    return $redis->del($keys) > 0;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Clear all red packet cache failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取缓存统计信息
     * @return array
     */
    public static function getCacheStats()
    {
        $stats = [
            'cache_driver' => Cache::getConfig('default'),
            'cache_prefix' => self::CACHE_PREFIX,
            'cache_times' => [
                'user_bet' => self::CACHE_TIME_USER_BET,
                'available' => self::CACHE_TIME_AVAILABLE,
                'user_claimed' => self::CACHE_TIME_USER_CLAIMED,
                'redpacket_info' => self::CACHE_TIME_REDPACKET_INFO
            ]
        ];
        
        // 如果是Redis驱动，获取更多统计信息
        if (Cache::getConfig('default') === 'redis') {
            try {
                $redis = Cache::store('redis')->handler();
                $info = $redis->info('memory');
                $stats['memory_usage'] = $info['used_memory_human'] ?? 'unknown';
                $stats['connected_clients'] = $redis->info('clients')['connected_clients'] ?? 'unknown';
            } catch (\Exception $e) {
                Log::warning('Failed to get Redis stats: ' . $e->getMessage());
            }
        }
        
        return $stats;
    }
    
    /**
     * 预热缓存
     * 在系统启动或低峰期预先加载常用数据到缓存
     * @param array $userIds 需要预热的用户ID列表
     * @return bool
     */
    public static function warmUpCache($userIds = [])
    {
        try {
            // 如果没有指定用户ID，获取活跃用户
            if (empty($userIds)) {
                // 这里可以根据业务需求获取活跃用户列表
                // 例如：最近登录的用户、VIP用户等
                $userIds = [];
            }
            
            foreach ($userIds as $userId) {
                // 预热用户投注金额缓存
                // 这里需要调用相应的服务方法来计算并缓存数据
                // 具体实现可以根据业务需求调整
            }
            
            Log::info('Red packet cache warm up completed for ' . count($userIds) . ' users');
            return true;
        } catch (\Exception $e) {
            Log::error('Cache warm up failed: ' . $e->getMessage());
            return false;
        }
    }
}