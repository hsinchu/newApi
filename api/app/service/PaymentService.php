<?php

namespace app\service;

use app\common\model\PaymentChannel;
use app\common\model\PaymentMethod;
use app\common\model\RechargeOrder;
use app\common\model\User;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

/**
 * 支付服务类
 */
class PaymentService
{
    /**
     * 获取可用的支付方式
     * @return array
     */
    public static function getAvailablePaymentMethods(): array
    {
        return PaymentMethod::getEnabledMethods();
    }

    /**
     * 获取支付方式的可用通道
     * @param int $methodId
     * @return array
     */
    public static function getAvailableChannels(int $methodId): array
    {
        return PaymentChannel::getChannelsByMethod($methodId);
    }

    /**
     * 创建充值订单
     * @param array $data
     * @return RechargeOrder
     * @throws Exception
     */
    public static function createRechargeOrder(array $data): RechargeOrder
    {
        // 验证用户
        $user = User::find($data['user_id']);
        if (!$user) {
            throw new Exception('用户不存在');
        }

        // 验证支付通道
        $channel = PaymentChannel::find($data['payment_channel_id']);
        if (!$channel || !$channel->is_enabled) {
            throw new Exception('支付通道不可用');
        }

        // 验证金额
        if ($data['amount'] <= 0) {
            throw new Exception('充值金额必须大于0');
        }

        Db::startTrans();
        try {
            // 创建订单
            $orderData = [
                'user_id' => $data['user_id'],
                'order_no' => RechargeOrder::generateOrderNo(),
                'payment_channel_id' => $data['payment_channel_id'],
                'amount' => $data['amount'],
                'actual_amount' => $data['amount'],
                'fee' => 0,
                'status' => RechargeOrder::STATUS_PENDING, // 待支付
                'payment_method' => $data['payment_method'] ?? '',
                'channel_name' => $channel->external_name,
                'client_ip' => $data['client_ip'] ?? '',
                'user_agent' => $data['user_agent'] ?? '',
                'remark' => $data['remark'] ?? '',
            ];

            $order = RechargeOrder::create($orderData);
            
            Db::commit();
            return $order;
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 处理支付成功
     * @param string $orderNo
     * @param array $notifyData
     * @return bool
     * @throws Exception
     */
    public static function handlePaymentSuccess(string $orderNo, array $notifyData = []): bool
    {
        $order = RechargeOrder::findByOrderNo($orderNo);
        if (!$order) {
            throw new Exception('订单不存在');
        }

        if ($order->status == RechargeOrder::STATUS_SUCCESS) {
            return true; // 已经处理过
        }

        Db::startTrans();
        try {
            // 更新订单状态
            $order->status = RechargeOrder::STATUS_SUCCESS;
            $order->success_time = time();
            $order->last_notify_time = time();
            $order->notify_content = $notifyData;
            $order->trade_no = $notifyData['trade_no'] ?? '';
            $order->save();

            // 增加用户余额
            $user = User::find($order->user_id);
            if ($user) {
                $beforeBalance = $user->money;
                $user->money = bcadd($user->money, $order->actual_amount, 2);
                $user->save();

                // 记录资金变动日志
                FinanceService::addBalanceLog([
                    'user_id' => $user->id,
                    'type' => 'recharge',
                    'amount' => $order->actual_amount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $user->money,
                    'remark' => '充值成功，订单号：' . $order->order_no,
                    'order_no' => $order->order_no,
                ]);
            }

            Db::commit();
            
            Log::info('充值成功处理完成', [
                'order_no' => $orderNo,
                'user_id' => $order->user_id,
                'amount' => $order->actual_amount,
            ]);
            
            return true;
        } catch (Exception $e) {
            Db::rollback();
            Log::error('充值成功处理失败', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理支付失败
     * @param string $orderNo
     * @param string $failReason
     * @param array $notifyData
     * @return bool
     * @throws Exception
     */
    public static function handlePaymentFailed(string $orderNo, string $failReason = '', array $notifyData = []): bool
    {
        $order = RechargeOrder::findByOrderNo($orderNo);
        if (!$order) {
            throw new Exception('订单不存在');
        }

        if ($order->status != RechargeOrder::STATUS_PENDING) {
            return true; // 已经处理过
        }

        try {
            // 更新订单状态
            $order->status = RechargeOrder::STATUS_FAILED;
            $order->remark = $failReason;
            $order->last_notify_time = time();
            $order->notify_content = $notifyData;
            $order->save();

            Log::info('充值失败处理完成', [
                'order_no' => $orderNo,
                'fail_reason' => $failReason,
            ]);
            
            return true;
        } catch (Exception $e) {
            Log::error('充值失败处理失败', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 取消订单
     * @param string $orderNo
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public static function cancelOrder(string $orderNo, string $reason = ''): bool
    {
        $order = RechargeOrder::findByOrderNo($orderNo);
        if (!$order) {
            throw new Exception('订单不存在');
        }

        if ($order->status != RechargeOrder::STATUS_PENDING) {
            throw new Exception('只能取消待支付的订单');
        }

        try {
            $order->status = RechargeOrder::STATUS_CANCELLED;
            $order->remark = $reason ?: '用户取消';
            $order->save();
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取支付通道编码配置
     * @return array
     */
    public static function getChannelCodeConfig(): array
    {
        return [
            'alipay' => [
                'name' => '支付宝',
                'codes' => [
                    'P0000' => '支付宝扫码',
                    '3333' => '支付宝H5',
                    '606' => '支付宝WAP',
                    '666' => '支付宝APP',
                    '911' => '支付宝转账',
                ]
            ],
            'wechat' => [
                'name' => '微信支付',
                'codes' => [
                    'W0001' => '微信扫码',
                    'W0002' => '微信H5',
                    'W0003' => '微信小程序',
                    'W0004' => '微信APP',
                ]
            ],
            'unionpay' => [
                'name' => '银联支付',
                'codes' => [
                    'U0001' => '银联扫码',
                    'U0002' => '银联网关',
                    'U0003' => '银联快捷',
                ]
            ],
            'bank' => [
                'name' => '银行卡',
                'codes' => [
                    'B0001' => '网银支付',
                    'B0002' => '快捷支付',
                    'B0003' => '银行转账',
                ]
            ],
        ];
    }

    /**
     * 验证支付通道参数
     * @param array $params
     * @return bool
     */
    public static function validateChannelParams(array $params): bool
    {
        $required = ['method_id', 'channel_code', 'min_amount', 'max_amount', 'fee_rate'];
        foreach($params as $v){
            foreach ($required as $field) {
                if (empty($v[$field])) {
                    return false;
                }
            }
        }
        
        return true;
    }
}