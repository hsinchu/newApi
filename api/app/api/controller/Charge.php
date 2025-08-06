<?php

namespace app\api\controller;

use Throwable;
use think\facade\Config;
use app\common\controller\Frontend;
use app\common\model\PaymentMethod;
use app\common\model\PaymentChannel;
use app\common\model\RechargeGift;
use app\common\model\RechargeOrder;
use app\common\model\User as UserModel;
use app\service\FinanceService;
use Exception;

class Charge extends Frontend
{
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 获取支付方式列表
     */
    public function payType(): void
    {
        try {
            // 查询启用的支付方式
            $paymentMethods = PaymentMethod::where('is_enabled', 1)
                ->order('sort_order desc, id asc')
                ->select()
                ->toArray();

            if (empty($paymentMethods)) {
                throw new Exception('暂无可用的支付方式');
            }

            // 查询启用的支付通道
            $paymentChannels = PaymentChannel::where('is_enabled', 1)
                ->order('sort_order desc, id asc')
                ->select()
                ->toArray();

            $result = [];
            
            foreach ($paymentMethods as $method) {
                $methodData = [
                    'id' => $method['method_code'],
                    'name' => $method['method_name'],
                    'icon' => $method['method_icon'],
                    'description' => $method['description'],
                    'channels' => []
                ];

                // 查找该支付方式对应的通道
                foreach ($paymentChannels as $channel) {
                    $channelParams = $channel['channel_params'];
                    if (!is_array($channelParams)) {
                        continue;
                    }

                    // 查找匹配当前支付方式的通道配置
                    foreach ($channelParams as $param) {
                        if (isset($param['method_id']) && 
                            $param['method_id'] == $method['id'] && 
                            isset($param['is_enabled']) && 
                            $param['is_enabled'] == '1') {
                            
                            $methodData['channels'][] = [
                                'id' => $channel['channel_code'] . '_' . $param['method_id'],
                                'name' => $channel['external_name'],
                                'channel_code' => $param['channel_code'] ?? $channel['channel_code'],
                                'min_amount' => floatval($param['min_amount'] ?? 1),
                                'max_amount' => floatval($param['max_amount'] ?? 50000),
                                'channel_id' => $channel['id']
                            ];
                        }
                    }
                }

                // 只返回有可用通道的支付方式
                if (!empty($methodData['channels'])) {
                    $result[] = $methodData;
                }
            }
        } catch (Throwable $e) {
            $this->error('获取支付方式失败：' . $e->getMessage());
        }

        $this->success('获取支付方式成功', $result);
    }

    /**
     * 获取代理充值赠送活动列表
     */
    public function rechargeGiftList(): void
    {
        try {
            $agentId = $this->auth->parent_id;
            
            if (!$agentId) {
                throw new Exception('您还没有代理商，无法查看充值赠送活动');
            }

            // 查询该代理的启用状态的充值赠送配置
            $rechargeGifts = RechargeGift::where('agent_id', $agentId)
                ->where('status', 1)
                ->order('charge_amount', 'asc')
                ->select()
                ->toArray();

            $result = [];
            foreach ($rechargeGifts as $gift) {
                $result[] = [
                    'id' => $gift['id'],
                    'charge_amount' => floatval($gift['charge_amount']),
                    'bonus_amount' => floatval($gift['bonus_amount']),
                    'display_text' => '充值¥' . $gift['charge_amount'] . '送¥' . $gift['bonus_amount']
                ];
            }
        } catch (Throwable $e) {
            $this->error('获取充值赠送活动失败：' . $e->getMessage());
        }

        $this->success('获取充值赠送活动成功', $result);
    }

    /**
     * 模拟提交支付成功
     */
    public function mockPaySuccess(): void
    {
        try {
            $params = $this->request->post(['amount', 'payment_method', 'payment_channel']);
            
            if (empty($params['amount']) || $params['amount'] <= 0) {
                throw new Exception('充值金额不能为空或小于等于0');
            }
            
            if (empty($params['payment_method'])) {
                throw new Exception('请选择支付方式');
            }
            
            if (empty($params['payment_channel'])) {
                throw new Exception('请选择支付通道');
            }
            
            // 生成订单号
            $orderNo = 'R' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // 解析支付通道ID
            $channelInfo = explode('_', $params['payment_channel']);
            $channelCode = $channelInfo[0] ?? '';
            $methodId = $channelInfo[1] ?? 0;
            
            // 查找支付通道
            $channel = PaymentChannel::where('channel_code', $channelCode)
                ->where('is_enabled', 1)
                ->find();
                
            if (!$channel) {
                throw new Exception('支付通道不存在或已禁用');
            }
            
            // 验证金额限制
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
            
            if ($params['amount'] < $minAmount) {
                throw new Exception('充值金额不能小于¥' . $minAmount);
            }

            if ($params['amount'] > $maxAmount) {
                throw new Exception('充值金额不能大于¥' . $maxAmount);
            }

            // 计算手续费
            $feeAmount = $params['amount'] * ($feeRate / 100);
            $actualAmount = $params['amount'];
            
            // 计算赠送金额
            $giftAmount = 0;
            $agentId = $this->auth->parent_id;
            if ($agentId) {
                $rechargeGift = RechargeGift::getGiftByAmount($agentId, $params['amount']);
                if ($rechargeGift) {
                    $giftAmount = floatval($rechargeGift['bonus_amount']);
                }
            }
            
            // 创建充值订单
            $orderData = [
                'order_no' => $orderNo,
                'user_id' => $this->auth->id,
                'amount' => $params['amount'],
                'actual_amount' => $actualAmount,
                'fee_amount' => $feeAmount,
                'gift_amount' => $giftAmount,
                'status' => 'SUCCESS', // 模拟成功
                'payment_method' => $params['payment_method'],
                'payment_channel' => $params['payment_channel'],
                'payment_code' => $channelCode,
                'method_id' => $methodId,
                'channel_id' => $channel['id'],
                'client_ip' => $this->request->ip(),
                'user_agent' => $this->request->header('user-agent'),
                'success_time' => time(),
                'create_time' => time(),
                'update_time' => time()
            ];
            
            $order = RechargeOrder::create($orderData);
            
            if (!$order) {
                throw new Exception('创建充值订单失败');
            }
            
            // 添加充值金额的资金变动记录
            $userId = $this->auth->id;
            $financeService = new FinanceService();
            
            // 记录充值金额
            $financeService->adjustUserBalance(
                $userId,
                $actualAmount,
                '用户充值，订单号：' . $orderNo,
                'RECHARGE_ADD'
            );
            
            // 如果有赠送金额，检查代理余额并处理赠送
            if ($giftAmount > 0 && $agentId) {
                // 获取代理信息
                $agent = UserModel::find($agentId);
                if (!$agent) {
                    throw new Exception('代理商信息不存在');
                }
                
                // 检查代理余额是否充足（转换为分进行比较）
                $agentBalance = $agent->money;
                $giftAmountCents = $giftAmount;
                
                if (bccomp($agentBalance, $giftAmountCents) >= 0) {
                    // 代理余额充足，先扣除代理余额
                    $financeService->adjustUserBalance(
                        $agentId,
                        -$giftAmount,
                        '充值赠送扣款，会员：' . $this->auth->id,
                        'RECHARGE_GIFT_DEDUCT'
                    );
                    
                    // 再给会员充值赠送
                    $financeService->adjustUserBalance(
                        $userId,
                        $giftAmount,
                        '充值赠送，订单号：' . $orderNo,
                        'RECHARGE_GIFT_ADD',
                        true  // 更新gift_money字段
                    );
                } else {
                    // 代理余额不足，关闭该代理所有的充值赠送活动
                    RechargeGift::where('agent_id', $agentId)
                        ->where('status', RechargeGift::STATUS_ENABLED)
                        ->update(['status' => RechargeGift::STATUS_DISABLED]);
                    
                    // 重置赠送金额为0，不进行赠送操作
                    $giftAmount = 0;
                }
            }
            
            // 获取更新后的用户信息
            $user = $this->auth->getUser();
            $totalAmount = $actualAmount + $giftAmount;
            
            $result = [
                'order_no' => $orderNo,
                'amount' => $params['amount'],
                'actual_amount' => $actualAmount,
                'fee_amount' => $feeAmount,
                'gift_amount' => $giftAmount,
                'total_amount' => $totalAmount,
                'new_balance' => $user->money
            ];
            
        } catch (Throwable $e) {
            $this->error('充值失败：' . $e->getMessage());
        }
        
        $this->success('充值成功', $result);
    }

    /**
     * 获取提现账户列表
     */
    public function withdrawAccountList() {
        try {
            $type = $this->request->get('type', '');
            
            $query = \app\common\model\WithdrawAccount::where('user_id', $this->auth->id)
                ->where('status', 1)
                ->order('is_default desc, create_time desc');
            
            if ($type) {
                $query->where('type', $type);
            }
            
            $accounts = $query->select();
            
            $list = [];
            foreach ($accounts as $account) {
                $list[] = [
                    'id' => $account->id,
                    'type' => $account->type,
                    'typeName' => $account->type_name,
                    'account_name' => $account->account_name,
                    'account_number' => $account->masked_account,
                    'bank_name' => $account->bank_name,
                    'isDefault' => $account->is_default,
                    'createTime' => date('Y-m-d H:i:s', $account->create_time)
                ];
            }
        } catch (\Throwable $e) {
            $this->error('获取提现账户失败：' . $e->getMessage());
        }
            
        $this->success('获取成功', $list);
    }
    
    /**
     * 删除提现账户
     */
    public function deleteWithdrawAccount() {
        try {
            $id = $this->request->post('id');
            
            if (!$id) {
                throw new \Exception('账户ID不能为空');
            }
            
            $account = \app\common\model\WithdrawAccount::where('user_id', $this->auth->id)
                ->where('id', $id)
                ->find();
            
            if (!$account) {
                throw new \Exception('账户不存在或不属于当前用户');
            }
            
            // 软删除：设置状态为禁用
            $account->save([
                'status' => 0,
                'update_time' => time()
            ]);
            
            // 如果删除的是默认账户，需要设置新的默认账户
            if ($account->is_default) {
                $newDefault = \app\common\model\WithdrawAccount::where('user_id', $this->auth->id)
                    ->where('status', 1)
                    ->where('id', '<>', $id)
                    ->order('create_time desc')
                    ->find();
                
                if ($newDefault) {
                    $newDefault->save([
                        'is_default' => 1,
                        'update_time' => time()
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->error('删除失败：' . $e->getMessage());
        }
            
        $this->success('删除成功');
    }
    
    /**
     * 提交提现申请
     */
    public function submitWithdrawApply() {
        try {
            $accountId = $this->request->post('accountId');
            $amount = floatval($this->request->post('amount'));
            $remark = $this->request->post('remark', '');
            
            if (!$accountId) {
                throw new \Exception('请选择提现账户');
            }
            
            if ($amount <= 0) {
                throw new \Exception('提现金额必须大于0');
            }
            
            // 获取系统配置
            $minAmount = 50; // 最小提现金额
            $maxAmount = 10000; // 最大提现金额
            $feeRate = 0; // 手续费率
            
            if ($amount < $minAmount) {
                throw new \Exception('提现金额不能少于' . $minAmount . '元');
            }
            
            if ($amount > $maxAmount) {
                throw new \Exception('提现金额不能超过' . $maxAmount . '元');
            }
            
            // 验证账户
            $account = \app\common\model\WithdrawAccount::where('user_id', $this->auth->id)
                ->where('id', $accountId)
                ->where('status', 1)
                ->find();
            
            if (!$account) {
                throw new \Exception('提现账户不存在或已禁用');
            }
            
            // 检查余额
            $user = $this->auth->getUser();
            if ($user->money < $amount) {
                throw new \Exception('余额不足');
            }
            
            // 计算手续费
            $fee = $amount * ($feeRate / 100);
            $actualAmount = $amount - $fee;
            
            // 创建提现记录
            $orderNo = 'WD' . date('YmdHis') . rand(1000, 9999);
            $recordData = [
                'order_no' => $orderNo,
                'user_id' => $this->auth->id,
                'account_id' => $accountId,
                'account_type' => $account->type,
                'account_name' => $account->account_name,
                'account_number' => $account->masked_account,
                'bank_name' => $account->bank_name ?: '',
                'amount' => $amount,
                'fee' => $fee,
                'actual_amount' => $actualAmount,
                'status' => 0, // 待审核
                'remark' => $remark,
                'create_time' => time(),
                'update_time' => time()
            ];
            
            $record = \app\common\model\WithdrawRecord::create($recordData);
            
            if (!$record) {
                throw new \Exception('创建提现申请失败');
            }
            
            // 不可提现用户资金
            $financeService = new \app\service\FinanceService();
            $financeService->adjustUserBalance(
                $this->auth->id,
                -$amount,
                '提现申请，订单号：' . $orderNo,
                'WITHDRAW_DEDUCT'
            );
            
            $result = [
                'orderNo' => $orderNo,
                'amount' => $amount,
                'fee' => $fee,
                'actualAmount' => $actualAmount,
                'accountInfo' => [
                    'type' => $account->type,
                    'typeName' => $account->type_name,
                    'accountName' => $account->account_name,
                    'accountNumber' => $account->masked_account
                ]
            ];
        } catch (\Throwable $e) {
            $this->error('提现申请失败：' . $e->getMessage());
        }
            
        $this->success('提现申请提交成功', $result);
    }
}