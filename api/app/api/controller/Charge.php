<?php

namespace app\api\controller;

use Throwable;
use app\common\controller\Frontend;
use app\common\model\PaymentMethod;
use app\common\model\PaymentChannel;
use app\common\model\RechargeGift;
use think\facade\Db;
use Exception;

class Charge extends Frontend
{

    protected array $noNeedLogin = ['payNotify'];

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
     * 获取充值赠送活动列表（包含系统配置和代理商配置）
     */
    public function rechargeGiftList(): void
    {
        try {
            $agentId = $this->auth->parent_id ?? 0;
            
            // 查询系统配置（agent_id = 0）
            $systemGifts = RechargeGift::where('agent_id', 0)
                ->where('status', 1)
                ->order('charge_amount', 'asc')
                ->select()
                ->toArray();

            // 查询代理商配置（如果用户有代理商）
            $agentGifts = [];
            if ($agentId > 0) {
                $agentGifts = RechargeGift::where('agent_id', $agentId)
                    ->where('status', 1)
                    ->order('charge_amount', 'asc')
                    ->select()
                    ->toArray();
            }

            $result = [
                'system_gifts' => [],
                'agent_gifts' => []
            ];
            
            // 处理系统配置
            foreach ($systemGifts as $gift) {
                $result['system_gifts'][] = [
                    'id' => $gift['id'],
                    'charge_amount' => floatval($gift['charge_amount']),
                    'bonus_amount' => floatval($gift['bonus_amount']),
                    'display_text' => '充值¥' . $gift['charge_amount'] . '送¥' . $gift['bonus_amount'] . '（系统）',
                    'type' => 'system'
                ];
            }
            
            // 处理代理商配置
            foreach ($agentGifts as $gift) {
                // 实时检查代理商余额是否足够支付赠送金额
                if ($agentId > 0) {
                    $agent = \app\common\model\User::find($agentId);
                    if ($agent) {
                        $agentBalance = $agent->money; // 转换为元
                        $bonusAmount = floatval($gift['bonus_amount']);
                        
                        // 如果代理商余额不足，自动关闭该赠送活动
                        if (bccomp($agentBalance, $bonusAmount, 2) < 0) {
                            RechargeGift::where('id', $gift['id'])
                                ->update(['status' => 0]);
                            
                            continue; // 跳过这个赠送活动
                        }
                    }
                }
                
                $result['agent_gifts'][] = [
                    'id' => $gift['id'],
                    'charge_amount' => floatval($gift['charge_amount']),
                    'bonus_amount' => floatval($gift['bonus_amount']),
                    'display_text' => '充值¥' . $gift['charge_amount'] . '送¥' . $gift['bonus_amount'] . '（代理商）',
                    'type' => 'agent'
                ];
            }
            
            // 合并所有配置并按充值金额排序
            $allGifts = array_merge($result['system_gifts'], $result['agent_gifts']);
            usort($allGifts, function($a, $b) {
                return $a['charge_amount'] <=> $b['charge_amount'];
            });
            
            $result['all_gifts'] = $allGifts;
            
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
            // 获取前端传递的参数
            $params = $this->request->post(['amount', 'payment_method', 'payment_channel']);
            
            // 参数验证
            if (empty($params['amount']) || $params['amount'] <= 0) {
                throw new Exception('充值金额不能为空或小于等于0');
            }
            
            if (empty($params['payment_method'])) {
                throw new Exception('请选择支付方式');
            }
            
            if (empty($params['payment_channel'])) {
                throw new Exception('请选择支付通道');
            }
            
            // 验证用户登录状态
            if (!$this->auth || !$this->auth->id) {
                throw new Exception('用户未登录');
            }
            
            // 调用支付服务处理充值
            $payService = new \app\service\pay\PayService();
            $result = $payService->processRecharge([
                'user_id' => $this->auth->id,
                'agent_id' => $this->auth->parent_id ?? 0,
                'amount' => floatval($params['amount']),
                'payment_method' => $params['payment_method'],
                'payment_channel' => $params['payment_channel'],
                'client_ip' => $this->request->ip(),
                'user_agent' => $this->request->header('user-agent') ?? ''
            ]);
            if (!$result['success']) {
                throw new Exception($result['message'] ?? '支付处理失败');
            }

            $payment_channel = explode('_', $params['payment_channel']);
            
            // 如果是模拟支付通道，直接调用支付成功回调
            if ($payment_channel[0] === 'moni') {
                // 构造模拟回调数据
                $mockNotifyData = [
                    'order_no' => $result['data']['order_no'],
                    'out_trade_no' => $result['data']['order_no'],
                    'trade_no' => $result['data']['trade_no'],
                    'amount' => $result['data']['amount'],
                    'status' => 'SUCCESS'
                ];
                // 调用支付成功回调处理
                $notifyResult = $payService->handlePaymentNotify($mockNotifyData);
                
                if (!$notifyResult['success']) {
                    throw new Exception($notifyResult['message'] ?? '模拟支付回调处理失败');
                }
            }
            
            // 返回支付结果，包含前端需要的字段
            $responseData = [
                'order_no' => $result['data']['order_no'] ?? '',
                'pay_url' => $result['data']['pay_url'] ?? '',
                'qr_code' => $result['data']['qr_code'] ?? '',
                'amount' => $result['data']['amount'] ?? 0,
                'actual_amount' => $result['data']['actual_amount'] ?? 0,
                'gift_amount' => $result['data']['gift_amount'] ?? 0,
                'trade_no' => $result['data']['trade_no'] ?? '',
                'is_popup' => $result['data']['is_popup'] ?? false,
                'expire_time' => $result['data']['expire_time'] ?? ''
            ];
            
        } catch (Throwable $e) {
            $this->error('充值失败：' . $e->getMessage());
        }
            
        $this->success('支付订单创建成功', $responseData);
    }

    /**
     * 支付回调处理
     */
    public function payNotify(): void
    {
        try {
            // 获取回调数据
            $notifyData = $this->request->param();
            Db::name('test')->insert(['info'=>json_encode($notifyData)]);
            // $notifyData = json_decode('{"server":"1","extend":"\u6269\u5c55\u5b57\u6bb5","merchant_no":"696265672","order_money":"1000","order_no":"R202508101520155915","order_state":"82002","pay_money":"1000","pay_time":"20250810152126","platform_order_no":"TT250810152016545703","sign":"B18DE1C75412B4E5989B476DDE3ADF91"}', true);
            // 记录回调日志
            \think\facade\Log::info('支付回调数据', [
                'notify_data' => $notifyData,
                'headers' => $this->request->header(),
                'ip' => $this->request->ip()
            ]);
            
            // 调用支付服务处理回调
            $payService = new \app\service\pay\PayService();
            $result = $payService->handlePaymentNotify($notifyData);
            
            if ($result['success']) {
                // 回调处理成功，返回成功响应
                echo 'success';
            } else {
                // 回调处理失败，返回失败响应
                echo 'fail';
            }
            
        } catch (Throwable $e) {
            \think\facade\Log::error('支付回调处理异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            echo 'fail';
        }
        
        exit;
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
            
            // 从fa_config表动态获取系统配置
            $minAmount = get_sys_config('withdraw_min_amount', 50); // 最小提现金额
            $maxAmount = get_sys_config('withdraw_max_amount', 10000); // 最大提现金额
            $feeRate = get_sys_config('withdraw_fee_rate', 0); // 手续费率
            
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
            
            // 实名认证检查逻辑
            $isRealNameRequired = false;
            $realnameThreshold = get_sys_config('withdraw_realname_threshold', 5000);
            
            // 提现大于阈值金额必须实名认证
            if ($amount > $realnameThreshold && $account->type != 2) {
                $isRealNameRequired = true;
            }
            
            // 银行卡提现必须实名认证（无论金额大小）
            if ($account->type == 2) { // 2=银行卡
                $isRealNameRequired = true;
            }
            
            // 检查是否需要实名认证
            $user = $this->auth->getUser();
            if ($isRealNameRequired && $user->is_verified != 1) {
                throw new \Exception('该提现方式需要完成实名认证后才能操作', 403);
            }
            
            // 检查实名信息是否匹配
            if ($isRealNameRequired && $account->account_name != $user->real_name) {
                throw new \Exception('请使用实名的身份证提现', 403);
            }
            
            // 检查每日提现次数限制
            $dailyLimit = get_sys_config('withdraw_daily_limit', 3);
            $todayWithdrawCount = \app\common\model\WithdrawRecord::where('user_id', $this->auth->id)
                ->whereTime('create_time', 'today')
                ->count();
            
            if ($todayWithdrawCount >= $dailyLimit) {
                throw new \Exception('今日已提现' . $dailyLimit . '次，请明日再试');
            }
            
            // 检查余额
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