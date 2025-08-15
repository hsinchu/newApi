<?php

namespace app\service\pay;

use Exception;
use think\facade\Log;
use app\common\model\PaymentChannel;

/**
 * 天天支付服务类
 */
class TtianService
{
    private string $merchantNo;
    private string $secretKey;
    private string $gatewayUrl;
    
    public function __construct()
    {
        // 从数据库获取天天支付配置
        $channel = PaymentChannel::where('channel_code', 'ttian')
            ->where('is_enabled', 1)
            ->find();
            
        if (!$channel) {
            throw new Exception('天天支付通道未配置或已禁用');
        }
        
        $this->merchantNo = $channel->merchant_id;
        $this->secretKey = $channel->secret_key;
        $this->gatewayUrl = 'http://域名/api/v2/gateway'; // 根据实际情况修改
    }
    
    /**
     * 创建支付订单
     * @param array $orderData
     * @return array
     * @throws Exception
     */
    public function createPayOrder(array $orderData): array
    {
        try {
            // 获取通道配置中的channel_code
            $channel = PaymentChannel::where('channel_code', 'ttian')
                ->where('is_enabled', 1)
                ->find();
                
            if (!$channel) {
                throw new Exception('天天支付通道未配置或已禁用');
            }
            
            $channelParams = $channel->channel_params;
            $channelCode = '8055'; // 默认通道编码
            
            if (is_array($channelParams) && !empty($channelParams)) {
                $channelCode = $channelParams[0]['channel_code'] ?? '8055';
            }
            
            // 第一步：组装业务数据
            $businessData = [
                'order_no' => $orderData['order_no'],
                'order_money' => intval($orderData['amount'] * 100), // 转换为分
                'channel' => $channelCode,
                'sync_url' => $orderData['return_url'],
                'async_url' => $orderData['notify_url'],
                'extend' => $orderData['extend'] ?? '扩展字段'
            ];
            
            // 如果是USDT通道，添加付款地址
            if (isset($orderData['pay_addr'])) {
                $businessData['payAddr'] = $orderData['pay_addr'];
            }
            
            // 第二步：将业务数据转为JSON并进行base64编码
            $jsonData = json_encode($businessData, JSON_UNESCAPED_UNICODE);
            $base64Data = base64_encode($jsonData);
            
            // 第三步：组装公共参数
            $commonData = [
                'businessType' => 'order',
                'data' => $base64Data,
                'ipAddr' => $orderData['client_ip'] ?? '127.0.0.1',
                'merchantNo' => $this->merchantNo,
                'timeStamp' => time() * 1000 // 13位时间戳
            ];
            
            // 第四步：生成签名
            $sign = $this->generateSign($commonData);
            $commonData['sign'] = $sign;
            
            // 发送请求
            $response = $this->sendRequest($commonData);
            
            return $this->parseResponse($response);
            
        } catch (Exception $e) {
            Log::error('TtianService createOrder error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 生成签名
     * @param array $data
     * @return string
     */
    private function generateSign(array $data): string
    {
        // 按ASCII编码升序排列
        ksort($data);
        
        // 拼接成key=value形式
        $signStr = '';
        foreach ($data as $key => $value) {
            $signStr .= $key . '=' . $value . '&';
        }
        
        // 添加商户密钥
        $signStr .= 'key=' . $this->secretKey;
        
        // MD5签名并转大写
        return strtoupper(md5($signStr));
    }
    
    /**
     * 发送HTTP请求
     * @param array $data
     * @return string
     * @throws Exception
     */
    private function sendRequest(array $data): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->gatewayUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * 解析响应数据
     * @param string $response
     * @return array
     * @throws Exception
     */
    private function parseResponse(string $response): array
    {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . $response);
        }
        
        if (!$data['success'] || $data['code'] !== 20000) {
            throw new Exception('API Error: ' . ($data['msg'] ?? 'Unknown error'));
        }
        
        // 解析业务数据
        if (empty($data['data'])) {
            throw new Exception('Empty business data');
        }
        
        $businessData = json_decode(base64_decode($data['data']), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid business data format');
        }
        
        return [
            'success' => true,
            'pay_url' => $businessData['payUrl'] ?? '',
            'qr_code' => $businessData['payUrl'] ?? '', // 天天支付返回的是支付链接
            'trade_no' => $businessData['orderNo'] ?? '',
            'is_popup' => true, // 天天支付需要跳转页面
            'expire_time' => '', // 天天支付未提供过期时间
            'receipt_addr' => $businessData['receiptAddr'] ?? '',
            'extend' => $businessData['extparam'] ?? ''
        ];
    }
    
    /**
     * 验证回调签名
     * @param array $data
     * @return array
     */
    public function verifyNotify(array $data): array
    {
        try {
            $sign = $data['sign'] ?? '';
            $verifyData = $data;
            unset($verifyData['sign'], $verifyData['extend']); // extend不参与签名
            
            // 按ASCII编码升序排列
            ksort($verifyData);
            
            // 拼接成key=value形式
            $signStr = '';
            foreach ($verifyData as $key => $value) {
                if ($value !== '' && $value !== null) {
                    $signStr .= $key . '=' . $value . '&';
                }
            }
            
            // 添加商户密钥
            $signStr .= 'key=' . $this->secretKey;
            
            // MD5签名并转大写
            $calculatedSign = strtoupper(md5($signStr));
            
            if ($calculatedSign !== $sign) {
                return [
                    'success' => false,
                    'message' => '签名验证失败'
                ];
            }
            
            // 检查订单状态
            if (($data['order_state'] ?? '') !== '82002') {
                return [
                    'success' => false,
                    'message' => '订单状态异常'
                ];
            }
            
            return [
                'success' => true,
                'trade_no' => $data['platform_order_no'] ?? '',
                'amount' => floatval(($data['pay_money'] ?? 0) / 100), // 分转元
                'order_no' => $data['order_no'] ?? ''
            ];
            
        } catch (Exception $e) {
            Log::error('TtianService verifyNotify error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
}