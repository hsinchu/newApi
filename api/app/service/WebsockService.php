<?php

namespace app\service;

use think\facade\Log;
use Exception;

/**
 * WebSocket服务类
 * 统一处理WebSocket消息推送
 */
class WebsockService
{
    /**
     * WebSocket服务器地址
     */
    private static $wsServerUrl = 'http://localhost:8080/api/push-prize-pool';
    
    /**
     * 推送奖池更新
     * @param string $lotteryCode 彩种代码
     * @param float $betAmount 投注金额（分为单位）
     * @param int $userId 用户ID（可选，用于生成飘动效果）
     * @return bool
     */
    public static function pushPrizePoolUpdate(string $lotteryCode, float $betAmount, int $userId = 0): bool
    {
        try {
            // 计算加入奖池的金额（70%）
            $prizePoolAmount = $betAmount * 0.7;
           
            $betAmountYuan = $betAmount;
            $prizePoolAmountYuan = $prizePoolAmount;
            
            // 构造推送消息
            $message = [
                'type' => 'prize_pool_update',
                'lottery_code' => $lotteryCode,
                'bet_amount' => $betAmountYuan,
                'prize_pool_amount' => $prizePoolAmountYuan,
                'timestamp' => time(),
                // 飘动效果数据
                'floating_tip' => [
                    'user_id' => $userId,
                    'amount' => $betAmountYuan,
                    'tip_type' => self::getTipType($betAmountYuan),
                    'user_name' => self::generateRandomUserName()
                ]
            ];
            
            // 发送WebSocket消息
            return self::sendWebSocketMessage($message);
            
        } catch (Exception $e) {
            Log::error('推送奖池更新失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 发送WebSocket消息
     * @param array $message
     * @return bool
     */
    private static function sendWebSocketMessage(array $message): bool
    {
        try {
            $postData = json_encode($message, JSON_UNESCAPED_UNICODE);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$wsServerUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData)
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3秒超时
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 2秒连接超时
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                Log::warning('WebSocket推送cURL错误: ' . $error);
                return false;
            }
            
            if ($httpCode !== 200) {
                Log::warning('WebSocket推送失败，HTTP状态码: ' . $httpCode . ', 响应: ' . $response);
                return false;
            }
            
            Log::info('WebSocket推送成功', ['message' => $message, 'response' => $response]);
            return true;
            
        } catch (Exception $e) {
            Log::error('发送WebSocket消息失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 根据金额获取提示类型
     * @param float $amount 金额（元）
     * @return string
     */
    private static function getTipType(float $amount): string
    {
        if ($amount >= 1000) {
            return 'vip';
        } elseif ($amount >= 500) {
            return 'premium';
        } elseif ($amount >= 100) {
            return 'gold';
        } else {
            return 'normal';
        }
    }
    
    /**
     * 生成随机用户名（x***x格式）
     * @return string
     */
    private static function generateRandomUserName(): string
    {
        $randomNum1 = mt_rand(0, 9);
        $randomNum2 = mt_rand(0, 9);
        return $randomNum1 . '***' . $randomNum2;
    }
    
    /**
     * 设置WebSocket服务器地址
     * @param string $url
     */
    public static function setServerUrl(string $url): void
    {
        self::$wsServerUrl = $url;
    }
    
    /**
     * 获取WebSocket服务器地址
     * @return string
     */
    public static function getServerUrl(): string
    {
        return self::$wsServerUrl;
    }
    
    /**
     * 推送自定义消息
     * @param array $message
     * @return bool
     */
    public static function pushCustomMessage(array $message): bool
    {
        try {
            return self::sendWebSocketMessage($message);
        } catch (Exception $e) {
            Log::error('推送自定义消息失败: ' . $e->getMessage());
            return false;
        }
    }
}