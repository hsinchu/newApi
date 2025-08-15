<?php

namespace app\service\pay;

use think\Exception;
use think\facade\Log;

/**
 * 模拟支付服务类
 */
class MoniService
{
    /**
     * 创建支付订单
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function createPayOrder(array $params): array
    {
        try {
            $orderNo = $params['order_no'] ?? '';
            $amount = $params['amount'] ?? 0;
            $userId = $params['user_id'] ?? 0;
            
            if (empty($orderNo)) {
                throw new Exception('订单号不能为空');
            }
            
            if ($amount <= 0) {
                throw new Exception('支付金额必须大于0');
            }
            
            // 模拟支付，直接返回成功
            Log::info('模拟支付订单创建', [
                'order_no' => $orderNo,
                'amount' => $amount,
                'user_id' => $userId
            ]);
            
            return [
                'success' => true,
                'pay_url' => '',
                'qr_code' => '',
                'trade_no' => 'MONI_' . $orderNo,
                'is_popup' => false,
                'expire_time' => date('Y-m-d H:i:s', time() + 1800) // 30分钟后过期
            ];
            
        } catch (Exception $e) {
            Log::error('模拟支付订单创建失败', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 验证支付回调
     * @param array $notifyData
     * @return array
     */
    public function verifyNotify(array $notifyData): array
    {
        try {
            // 模拟回调验证，直接返回成功
            $orderNo = $notifyData['order_no'] ?? $notifyData['out_trade_no'] ?? '';
            $amount = floatval($notifyData['amount'] ?? 0);
            $tradeNo = $notifyData['trade_no'] ?? 'MONI_' . $orderNo;
            
            if (empty($orderNo)) {
                throw new Exception('订单号不能为空');
            }
            
            if ($amount <= 0) {
                throw new Exception('支付金额无效');
            }
            
            Log::info('模拟支付回调验证', [
                'order_no' => $orderNo,
                'amount' => $amount,
                'trade_no' => $tradeNo
            ]);
            
            return [
                'success' => true,
                'trade_no' => $tradeNo,
                'amount' => $amount,
                'order_no' => $orderNo
            ];
            
        } catch (Exception $e) {
            Log::error('模拟支付回调验证失败', [
                'notify_data' => $notifyData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 获取商户密钥
     * @param string $merchantId
     * @return string
     */
    public function getMerchantKey(string $merchantId): string
    {
        // 模拟通道的固定密钥
        return '2';
    }
}