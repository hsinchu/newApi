<?php

namespace app\service\pay;

use app\common\model\PaymentChannel;
use app\common\model\RechargeGift;
use app\common\model\RechargeOrder;
use app\common\model\User;
use app\service\FinanceService;
use app\common\library\Email;

use think\Exception;
use think\facade\Db;
use think\facade\Log;

/**
 * 支付服务类 - 公共支付服务调用类
 */
class PayService
{
    /**
     * 处理充值请求
     * @param array $params 充值参数
     * @return array
     * @throws Exception
     */
    public function processRecharge(array $params): array
    {
        try {
            // 验证参数
            $userId = $params['user_id'] ?? 0;
            $amount = $params['amount'] ?? 0;
            $paymentMethod = $params['payment_method'] ?? '';
            $paymentChannel = $params['payment_channel'] ?? '';
            $clientIp = $params['client_ip'] ?? '';
            $userAgent = $params['user_agent'] ?? '';
            
            if ($amount <= 0) {
                throw new Exception('充值金额必须大于0');
            }
            
            if ($userId <= 0) {
                throw new Exception('用户ID无效');
            }
            
            if (empty($paymentChannel)) {
                throw new Exception('请选择支付通道');
            }
            
            // 解析支付通道ID
            $channelInfo = explode('_', $paymentChannel);

            $channelCode = $channelInfo[0] ?? '';
            $methodId = $channelInfo[1] ?? 0;
            // 查找支付通道
            $channel = PaymentChannel::where('channel_code', $channelCode)
                ->where('is_enabled', 1)
                ->find();
                
            if (!$channel) {
                throw new Exception('支付通道不存在或已禁用');
            }
            
            // 验证金额限制和获取费率
            $channelParams = $channel['channel_params'];
            $minAmount = 1;
            $maxAmount = 50000;
            $feeRate = 0;
            
            if (is_array($channelParams)) {
                foreach ($channelParams as $param) {
                    if (isset($param['method_id']) && $param['method_id'] == $methodId) {
                        $minAmount = floatval($param['min_amount'] ?? 1);
                        $maxAmount = floatval($param['max_amount'] ?? 50000);
                        $feeRate = floatval($param['fee_rate'] ?? 0);
                        break;
                    }
                }
            }
            
            if ($amount < $minAmount) {
                throw new Exception('充值金额不能小于¥' . $minAmount);
            }

            if ($amount > $maxAmount) {
                throw new Exception('充值金额不能大于¥' . $maxAmount);
            }
            // 计算手续费
            $feeAmount = $amount * ($feeRate / 100);
            $actualAmount = $amount;
            
            // 计算赠送金额
            $giftAmount = 0;
            $user = User::find($userId);
            $agentId = $user->parent_id ?? 0;
            
            // 先检查代理商赠送
            if ($agentId) {
                $rechargeGift = RechargeGift::getGiftByAmount($agentId, $amount);
                if ($rechargeGift) {
                    $giftAmount = floatval($rechargeGift['bonus_amount']);
                }
            }
            
            // 不管代理商有无赠送，都检查系统赠送（agent_id=0）
            $systemGift = RechargeGift::getGiftByAmount(0, $amount);
            if ($systemGift) {
                $systemGiftAmount = floatval($systemGift['bonus_amount']);
                $giftAmount += $systemGiftAmount; // 累加系统赠送金额
            }
            
            // 生成订单号
            $orderNo = $this->generateOrderNo();
            
            // 创建充值订单
            $orderData = [
                'order_no' => $orderNo,
                'user_id' => $userId,
                'amount' => $amount,
                'actual_amount' => $actualAmount,
                'fee_amount' => $feeAmount,
                'gift_amount' => $giftAmount,
                'status' => 'PENDING',
                'payment_method' => $paymentMethod,
                'payment_channel' => $paymentChannel,
                'payment_code' => $channelCode,
                'method_id' => $methodId,
                'channel_id' => $channel['id'],
                'client_ip' => $clientIp,
                'user_agent' => $userAgent,
                'create_time' => time(),
                'update_time' => time()
            ];

            
            $order = RechargeOrder::create($orderData);

            if (!$order) {
                throw new Exception('创建充值订单失败');
            }
            
            // 根据支付通道类型调用对应的服务
            $paymentService = $this->getPaymentService($channelCode);
            
            // 调用具体支付服务创建支付订单
            $payResult = $paymentService->createPayOrder([
                'order_no' => $orderNo,
                'amount' => $amount,
                'method' => $paymentMethod,
                'user_id' => $userId,
                'notify_url' => $this->getNotifyUrl($channelCode),
                'return_url' => $this->getReturnUrl()
            ]);
            
            if (!$payResult['success']) {
                throw new Exception($payResult['message'] ?? '创建支付订单失败');
            }
            
            // 更新订单支付信息
            $order->payment_url = $payResult['pay_url'] ?? '';
            $order->qr_code = $payResult['qr_code'] ?? '';
            $order->trade_no = $payResult['trade_no'] ?? '';
            $order->save();
            
            return [
                'success' => true,
                'data' => [
                    'order_no' => $orderNo,
                    'pay_url' => $payResult['pay_url'] ?? '',
                    'qr_code' => $payResult['qr_code'] ?? '',
                    'is_popup' => $payResult['is_popup'] ?? false,
                    'amount' => $amount,
                    'actual_amount' => $actualAmount,
                    'fee_amount' => $feeAmount,
                    'gift_amount' => $giftAmount,
                    'trade_no' => $payResult['trade_no'] ?? '',
                    'expire_time' => $payResult['expire_time'] ?? ''
                ]
            ];
            
        } catch (Exception $e) {
            Log::error('支付处理失败', [
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
     * 处理支付成功回调
     * @param array $notifyData 回调数据
     * @return array
     */
    public function handlePaymentNotify(array $notifyData): array
    {
        try {
            // 从回调数据中获取订单号，先尝试通过订单号查找订单
            $orderNo = $notifyData['order_no'] ?? $notifyData['order_no'] ?? '';
            if (empty($orderNo)) {
                throw new Exception('回调数据中缺少订单号');
            }
            // 查找订单
            $order = RechargeOrder::where(['order_no'=>$orderNo, 'amount'=>$notifyData['amount']])->find();

            if (!$order) {
                throw new Exception('订单不存在');
            }
            
            // 检查订单状态
            if ($order->status === 'SUCCESS') {
                return ['success' => true, 'message' => '订单已处理'];
            }
            
            // 获取支付通道代码
            $channelCode = $order->payment_code;
            if (empty($channelCode)) {
                throw new Exception('订单支付通道信息缺失');
            }
            
            // 获取对应的支付服务
            $paymentService = $this->getPaymentService($channelCode);
            
            // 验证回调数据
            $verifyResult = $paymentService->verifyNotify($notifyData);
            if (!$verifyResult['success']) {
                throw new Exception($verifyResult['message'] ?? '回调验证失败');
            }
            
            $tradeNo = $verifyResult['trade_no'] ?? '';
            $amount = $verifyResult['amount'] ?? 0;
            
            // 验证金额
            if (abs($order->amount - $amount) > 0.01) {
                throw new Exception('金额不匹配');
            }
            
            Db::startTrans();
            try {
                // 更新订单状态
                $order->status = 'SUCCESS';
                $order->trade_no = $tradeNo;
                $order->success_time = time();
                $order->notify_count = ($order->notify_count ?? 0) + 1;
                $order->last_notify_time = time();
                $order->save();
                
                // 增加用户余额
                $financeService = new FinanceService();
                $financeService->adjustUserBalance(
                    $order->user_id,
                    $order->actual_amount,
                    '用户充值，订单号：' . $orderNo,
                    'RECHARGE_ADD'
                );
                
                // 处理赠送金额 - 分别处理代理商赠送和系统赠送
                if ($order->gift_amount > 0) {
                    $user = User::find($order->user_id);
                    $agentId = $user->parent_id ?? 0;
                    
                    $agentGiftAmount = 0;
                    $systemGiftAmount = 0;
                    
                    // 分别获取代理商赠送和系统赠送金额
                    if ($agentId) {
                        $agentGift = RechargeGift::getGiftByAmount($agentId, $order->amount);
                        if ($agentGift) {
                            $agentGiftAmount = floatval($agentGift['bonus_amount']);
                        }
                    }
                    
                    $systemGift = RechargeGift::getGiftByAmount(0, $order->amount);
                    if ($systemGift) {
                        $systemGiftAmount = floatval($systemGift['bonus_amount']);
                    }
                    
                    // 处理代理商赠送
                    if ($agentGiftAmount > 0) {
                        $agent = User::find($agentId);
                        if ($agent) {
                            // 检查代理余额是否充足
                            $agentBalance = $agent->money;
                            
                            if (bccomp($agentBalance, $agentGiftAmount) >= 0) {
                                // 代理余额充足，先扣除代理余额
                                $financeService->adjustUserBalance(
                                    $agentId,
                                    -$agentGiftAmount,
                                    '充值赠送扣款，会员：' . $order->user_id,
                                    'RECHARGE_GIFT_DEDUCT'
                                );
                                
                                // 再给会员充值赠送
                                $financeService->adjustUserBalance(
                                    $order->user_id,
                                    $agentGiftAmount,
                                    '代理商充值赠送，订单号：' . $orderNo,
                                    'RECHARGE_GIFT_ADD',
                                    true  // 更新gift_money字段
                                );
                            } else {
                                // 代理余额不足，关闭该代理所有的充值赠送活动
                                RechargeGift::where('agent_id', $agentId)
                                    ->where('status', RechargeGift::STATUS_ENABLED)
                                    ->update(['status' => RechargeGift::STATUS_DISABLED]);
                                
                                // 发送邮件通知代理余额不足
                                $this->sendBalanceInsufficientEmail($agent, $agentGiftAmount, $orderNo);
                                
                                Log::warning('代理余额不足，关闭充值赠送活动', [
                                    'agent_id' => $agentId,
                                    'order_no' => $orderNo,
                                    'gift_amount' => $agentGiftAmount
                                ]);
                            }
                        }
                    }
                    
                    // 处理系统赠送（单独记录账变）
                    if ($systemGiftAmount > 0) {
                        $financeService->adjustUserBalance(
                            $order->user_id,
                            $systemGiftAmount,
                            '系统充值赠送，订单号：' . $orderNo,
                            'RECHARGE_GIFT_ADD',
                            true  // 更新gift_money字段
                        );
                    }
                }
                
                Db::commit();
                
                Log::info('支付成功处理完成', [
                    'order_no' => $orderNo,
                    'trade_no' => $tradeNo,
                    'amount' => $amount
                ]);
                
                return ['success' => true, 'message' => '处理成功'];
                
            } catch (Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            Log::error('支付回调处理失败', [
                'channel_code' => $channelCode,
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
     * 获取支付服务实例
     * @param string $channelCode 支付通道代码
     * @return mixed
     * @throws Exception
     */
    private function getPaymentService(string $channelCode)
    {
        $serviceClass = match ($channelCode) {
            'yishun' => YishunService::class,
            'moni' => MoniService::class,
            'ttian' => TtianService::class,
            default => throw new Exception('不支持的支付通道: ' . $channelCode)
        };
        
        if (!class_exists($serviceClass)) {
            throw new Exception('支付服务类不存在: ' . $serviceClass);
        }
        
        return new $serviceClass();
    }
    
    /**
     * 生成订单号
     * @return string
     */
    private function generateOrderNo(): string
    {
        return 'R' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 获取异步通知URL
     * @param string $channelCode
     * @return string
     */
    private function getNotifyUrl(string $channelCode): string
    {
        return request()->domain() . '/api/charge/payNotify?server=1';
    }
    
    /**
     * 获取同步返回URL
     * @return string
     */
    private function getReturnUrl(): string
    {
        return request()->domain() . '/pay/return';
    }
    
    /**
     * 发送余额不足邮件通知
     * @param User $agent 代理用户对象
     * @param float $giftAmount 赠送金额
     * @param string $orderNo 订单号
     * @return void
     */
    private function sendBalanceInsufficientEmail(User $agent, float $giftAmount, string $orderNo): void
    {
        try {
            if (empty($agent->email)) {
                Log::warning('代理邮箱为空，无法发送邮件通知', ['agent_id' => $agent->id]);
                return;
            }
            
            $mail = new Email();
            
            if (!$mail->configured) {
                Log::warning('邮件服务未配置，无法发送通知');
                return;
            }
            
            $subject = '余额不足通知 - 充值赠送活动已关闭';
            $content = "尊敬的代理商，\n\n" .
                      "您的账户余额不足，无法完成充值赠送。\n" .
                      "订单号：{$orderNo}\n" .
                      "所需赠送金额：¥{$giftAmount}\n" .
                      "当前余额：¥{$agent->money}\n\n" .
                      "为避免影响业务，系统已自动关闭您的所有充值赠送活动。\n" .
                      "请及时充值后重新开启赠送活动。\n\n" .
                      "如有疑问，请联系客服。";
            
            // 发送邮件
            $mail->isSMTP();
            $mail->addAddress($agent->email);
            $mail->isHTML(true);
            $mail->setSubject($subject);
            $mail->Body = "<p>" . nl2br(htmlspecialchars($content)) . "</p>";
            $mail->AltBody = $content;
            
            $mail->send();
            Log::info("余额不足邮件发送成功", ['agent_id' => $agent->id, 'email' => $agent->email]);
            
        } catch (Exception $e) {
            Log::error("余额不足邮件发送失败", [
                'agent_id' => $agent->id,
                'email' => $agent->email ?? '',
                'error' => $e->getMessage()
            ]);
        }
    }
}