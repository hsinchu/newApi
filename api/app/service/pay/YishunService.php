<?php

namespace app\service\pay;

use think\Exception;
use think\facade\Log;

/**
 * 易顺支付服务类
 */
class YishunService
{
    /**
     * 创建支付订单
     * @param array $params
     * @return array
     */
    public function createPayOrder(array $params): array
    {
        try {
            $orderNo = $params['order_no'];
            $amount = $params['amount'];
            $notifyUrl = $params['notify_url'];
            $returnUrl = $params['return_url'];
            
            // 验证必要参数
            if (empty($orderNo) || $amount <= 0) {
                throw new Exception('订单号或金额无效');
            }
            
            // 获取易顺支付通道配置
            $channel = \app\common\model\PaymentChannel::where('channel_code', 'yishun')
                ->where('is_enabled', 1)
                ->find();
                
            if (!$channel) {
                throw new Exception('易顺支付通道未启用或不存在');
            }
            
            $merchantId = $channel['merchant_id'];
            $merchantKey = $channel['secret_key'];
            $apiUrl = 'https://tsapi-yishun.tszfbb.com/api/v1/payment/init'; // 易顺支付API地址
            
            if (empty($merchantId) || empty($merchantKey)) {
                throw new Exception('易顺支付通道配置不完整');
            }
            
            // 构建请求参数（参考PaymentService.php的格式）
            $requestData = [
                'mchKey' => $merchantId,
                'mchOrderNo' => $orderNo,
                'nonce' => $orderNo,
                'product' => $params['method'],
                'amount' => intval($amount * 100), // 转换为分
                'notifyUrl' => $notifyUrl,
                'timestamp' => time() * 1000
            ];
            
            // 生成签名
            $requestData['sign'] = $this->generateSign($requestData, $merchantKey);
            
            // 发送请求到支付网关
            $response = $this->sendRequest($apiUrl, $requestData);
            
            if (!$response) {
                throw new Exception('支付网关请求失败');
            }
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('支付网关返回数据格式错误');
            }
            
            // 检查返回状态
            if (!isset($result['code']) || $result['code'] != '200') {
                $message = $result['msg'] ?? '支付订单创建失败';
                throw new Exception($message);
            }
            
            // 返回支付信息
            return [
                'success' => true,
                'pay_url' => $result['data']['url']['payUrl'] ?? '',
                'qr_code' => $result['data']['url']['payUrl'] ?? '',
                'trade_no' => $orderNo,
                'is_popup' => false,
                'expire_time' => $result['data']['url']['expireTime'] ?? ''
            ];
            
        } catch (Exception $e) {
            Log::error('易顺支付创建订单失败', [
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
            // 验证必要字段
            $requiredFields = ['merchant_id', 'order_no', 'trade_no', 'amount', 'status', 'sign'];
            foreach ($requiredFields as $field) {
                if (!isset($notifyData[$field]) || empty($notifyData[$field])) {
                    throw new Exception('回调数据缺少必要字段: ' . $field);
                }
            }
            
            // 验证支付状态
            if ($notifyData['status'] !== 'SUCCESS') {
                throw new Exception('支付状态不是成功状态');
            }
            
            // 获取商户密钥（这里应该从数据库或配置中获取）
            $merchantKey = $this->getMerchantKey($notifyData['merchant_id']);
            if (!$merchantKey) {
                throw new Exception('无法获取商户密钥');
            }
            
            // 验证签名
            $sign = $notifyData['sign'];
            unset($notifyData['sign']);
            
            $expectedSign = $this->generateSign($notifyData, $merchantKey);
            if ($sign !== $expectedSign) {
                throw new Exception('签名验证失败');
            }
            
            return [
                'success' => true,
                'order_no' => $notifyData['order_no'],
                'trade_no' => $notifyData['trade_no'],
                'amount' => floatval($notifyData['amount'])
            ];
            
        } catch (Exception $e) {
            Log::error('易顺支付回调验证失败', [
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
     * 生成签名
     * @param array $data
     * @param string $key
     * @return string
     */
    private function generateSign(array $data, string $key): string
    {
        // 移除空值和sign字段
        $data = array_filter($data, function($value) {
            return $value !== '' && $value !== null;
        });
        unset($data['sign']);
        
        // 按键名排序
        ksort($data);
        
        // 构建签名字符串
        $signString = '';
        foreach ($data as $k => $v) {
            $signString .= $k . '=' . $v . '&';
        }
        $signString .= 'key=' . $key;
        
        // 生成MD5签名并转大写
        return strtoupper(md5($signString));
    }
    
    /**
     * 发送HTTP请求
     * @param string $url
     * @param array $data
     * @return string|false
     */
    private function sendRequest(string $url, array $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: YishunPayment/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error('易顺支付HTTP请求失败', [
                'url' => $url,
                'error' => $error
            ]);
            return false;
        }
        
        if ($httpCode !== 200) {
            Log::error('易顺支付HTTP状态码错误', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return false;
        }
        
        return $response;
    }
    
    /**
     * 获取商户密钥
     * @param string $merchantId
     * @return string|null
     */
    private function getMerchantKey(string $merchantId): ?string
    {
        // 从支付通道配置中获取商户密钥
        $channel = \app\common\model\PaymentChannel::where('channel_code', 'yishun')
            ->where('merchant_id', $merchantId)
            ->where('is_enabled', 1)
            ->find();
            
        return $channel ? $channel['secret_key'] : null;
    }
}