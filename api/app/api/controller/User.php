<?php

namespace app\api\controller;

use Throwable;
use ba\Captcha;
use ba\ClickCaptcha;
use think\facade\Config;
use app\common\facade\Token;
use app\common\model\UserMoneyLog;
use app\common\controller\Frontend;
use app\api\validate\User as UserValidate;
use Exception;
use think\facade\Db;
use app\common\model\PaymentMethod;
use app\common\model\PaymentChannel;
use app\common\model\RechargeGift;
use app\common\model\VerificationCode;
use app\common\model\User as UserModel;
use app\common\library\MoneyLogTypeHelper;
use app\common\model\RedPacket;
use app\common\model\RedPacketRecord;
use app\common\model\BetOrder;

class User extends Frontend
{
    protected array $noNeedLogin = ['checkIn', 'logout', 'sendEmailCode', 'resetPassword'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 会员签入(登录和注册)
     * @throws Throwable
     */
    public function checkIn(): void
    {
        $openMemberCenter = Config::get('buildadmin.open_member_center');
        if (!$openMemberCenter) {
            $this->error(__('Member center disabled'));
        }

        // 检查登录态
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'type' => $this->auth::LOGGED_IN
            ], $this->auth::LOGIN_RESPONSE_CODE);
        }

        $userLoginCaptchaSwitch = Config::get('buildadmin.user_login_captcha');

        if ($this->request->isPost()) {
            $params = $this->request->post(['tab', 'email', 'mobile', 'username', 'password', 'keep', 'captcha', 'captchaId', 'captchaInfo', 'registerType', 'type', 'emailCode', 'inviteCode']);

            // 提前检查 tab ，然后将以 tab 值作为数据验证场景
            if (!in_array($params['tab'] ?? '', ['login', 'register'])) {
                $this->error(__('Unknown operation'));
            }

            $validate = new UserValidate();
            try {
                $validate->scene($params['tab'])->check($params);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }

            if ($params['tab'] == 'login') {
                if ($userLoginCaptchaSwitch) {
                    $captchaObj = new ClickCaptcha();
                    if (!$captchaObj->check($params['captchaId'], $params['captchaInfo'])) {
                        $this->error(__('Captcha error'));
                    }
                }
                $res = $this->auth->login($params['username'], $params['password'], !empty($params['keep']));
            } elseif ($params['tab'] == 'register') {
                // 验证邮箱验证码
                if (!empty($params['emailCode'])) {
                    if (!\app\common\model\VerificationCode::verifyCode($params['email'], $params['emailCode'], 'register')) {
                        $this->error('邮箱验证码错误或已过期');
                    }
                } else {
                    $captchaObj = new Captcha();
                    if (!$captchaObj->check($params['captcha'], $params[$params['registerType']] . 'user_register')) {
                        $this->error(__('Please enter the correct verification code'));
                    }
                }
                
                // 验证邀请码（如果提供）
                $inviterUserId = null;
                if (!empty($params['inviteCode'])) {
                    $inviter = \app\common\model\User::where('invite_code', $params['inviteCode'])->find();
                    if (!$inviter) {
                        $this->error('邀请码不存在');
                    }
                    $inviterUserId = $inviter->id;
                }
                
                $mobile = $params['registerType'] == 'mobile' ? $params['mobile'] : '';
                $res = $this->auth->register($params['username'], $params['password'], $mobile, $params['email'], $inviterUserId);
            }

            if (isset($res) && $res === true) {
                $userInfo = $this->auth->getUserInfo();
                    $isAgent = isset($userInfo['is_agent']) ? $userInfo['is_agent'] : 0;
                // 如果是代理商登录，验证用户是否为代理商
                if (isset($params['type']) && $params['type'] === 'agent') {
                    if ($isAgent != 1) {
                        $this->auth->logout(); // 退出登录
                        $this->error('您是用户，无法登录代理商系统');
                    }
                }else{
                    if ($isAgent == 1) {
                        $this->auth->logout(); // 退出登录
                        $this->error('您是代理，无法登录用户系统');
                    }
                }
                
                $this->success(__('Login succeeded!'), [
                    'userInfo'  => $this->auth->getUserInfo(),
                    'routePath' => '/user'
                ]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ?: __('Check in failed, please try again or contact the website administrator~');
                $this->error($msg);
            }
        }

        $this->success('', [
            'userLoginCaptchaSwitch'  => $userLoginCaptchaSwitch,
            'accountVerificationType' => get_account_verification_type()
        ]);
    }

    public function logout(): void
    {
        if ($this->request->isPost()) {
            $refreshToken = $this->request->post('refreshToken', '');
            if ($refreshToken) Token::delete((string)$refreshToken);
            $this->auth->logout();
            $this->success();
        }
    }

    /**
     * 获取用户资金变动记录
     */
    public function moneyLog(): void
    {
        $params = $this->request->get(['page', 'limit', 'type', 'start_date', 'end_date']);
        $userId = $this->auth->id;
        
        // 设置默认参数
        $page = max(1, intval($params['page'] ?? 1));
        $limit = max(1, min(100, intval($params['limit'] ?? 20)));
        $type = $params['type'] ?? '';
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';
        
        try {
            // 构建查询条件
            $where = ['user_id' => $userId];
            
            // 使用统一的类型配置进行类型过滤
            if (!empty($type)) {
                $typeCondition = MoneyLogTypeHelper::buildTypeCondition($type);
                if (!empty($typeCondition)) {
                    $where[] = $typeCondition;
                }
            }
            
            // 按日期过滤
            if ($startDate && $endDate) {
                $startTimestamp = strtotime($startDate . ' 00:00:00');
                $endTimestamp = strtotime($endDate . ' 23:59:59');
                $where[] = ['create_time', 'between', [$startTimestamp, $endTimestamp]];
            }
            
            // 查询数据
            $query = UserMoneyLog::where($where)
                ->order('create_time desc');
            
            // 获取总数
            $total = $query->count();
            
            // 计算统计数据（当前筛选条件下的所有数据）
            $allLogs = $query->select();
            $totalAmount = 0;
            $totalIncome = 0;
            $totalExpense = 0;
            
            foreach ($allLogs as $log) {
                $amount = floatval($log->money);
                $totalAmount += $amount;
                if ($amount > 0) {
                    $totalIncome += $amount;
                } else {
                    $totalExpense += abs($amount);
                }
            }
            
            // 分页查询
            $offset = ($page - 1) * $limit;
            $logs = $query->limit($offset, $limit)->select();
            
            // 格式化数据
            $list = [];
            foreach ($logs as $log) {
                // 将数据库类型转换为前端需要的类型
                $frontendType = MoneyLogTypeHelper::dbToFrontend($log->type);
                
                $list[] = [
                    'id' => $log->id,
                    'type' => $frontendType,
                    'amount' => floatval($log->money), // 模型已经处理了除以100的转换
                    'remark' => $log->memo,
                    'createtime' => date('Y-m-d H:i:s', $log->create_time)
                ];
            }            
            $result = [
                'data' => $list,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'statistics' => [
                    'total_amount' => $totalAmount,
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'net_change' => $totalAmount
                ]
            ];
        } catch (Throwable $e) {
            $this->error('获取资金变动记录失败：' . $e->getMessage());
        }
            
        $this->success('获取资金变动记录成功', $result);
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
                                'fee_rate' => floatval($param['fee_rate'] ?? 0),
                                'min_amount' => floatval($param['min_amount'] ?? 1),
                                'max_amount' => floatval($param['max_amount'] ?? 50000)
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
     * 获取可领取的红包列表（优化版）
     */
    public function availableRedPackets(): void
    {
        try {
            $userId = $this->auth->id;
            $currentTime = time();
            // 1. 获取用户今日投注金额（缓存优化）
            $todayBetAmount = $this->getUserTodayBetAmount($userId);
            
            // 2. 获取用户已领取的红包ID列表（一次查询）
            $claimedPacketIds = RedPacketRecord::where('user_id', $userId)
                ->column('red_packet_id');
            
            // 3. 主查询优化 - 使用复合索引
            $query = RedPacket::where('status', 'ACTIVE')
                ->where(function($query) use ($currentTime) {
                    $query->where('expire_time', '>', $currentTime)
                          ->whereOr('expire_time', 0);
                })
                ->where('remaining_count', '>', 0)
                ->where('target_type', 'in', [0, 2]); // 0=全部，2=用户
            
            // 排除已领取的红包
            if (!empty($claimedPacketIds)) {
                $query->where('id', 'not in', $claimedPacketIds);
            }
            
            $redPackets = $query->order('create_time', 'desc')
                ->limit(10) // 限制查询数量，避免大量数据
                ->select();
            
            // 4. 过滤条件检查和数据格式化
            $availablePackets = [];
            foreach ($redPackets as $packet) {
                // 检查投注条件
                if ($packet->condition_type === 'MIN_BET') {
                    $minBetAmount = floatval($packet->condition_value);
                    if ($todayBetAmount < $minBetAmount) {
                        continue;
                    }
                }

                $hasReceived = RedPacketRecord::where('user_id', $userId)
                    ->where('red_packet_id', $packet->id)
                    ->count();
                
                if ($hasReceived > 0) {
                    continue;
                }
                
                // 计算剩余金额
                $remainingAmount = bcsub($packet->total_amount, $packet->received_amount, 0);
                
                $availablePackets[] = [
                    'id' => $packet->id,
                    'title' => $packet->title,
                    'blessing' => $packet->blessing,
                    'type' => $packet->type,
                    'total_amount' => $packet->total_amount,
                    'total_count' => $packet->total_count,
                    'received_count' => $packet->received_count,
                    'remaining_count' => $packet->remaining_count,
                    'remaining_amount' => abs(bcdiv($remainingAmount, 100, 2)),
                    'expire_time' => $packet->expire_time,
                    'condition_type' => $packet->condition_type,
                    'condition_value' => $packet->condition_value,
                    'amount' => $packet->type === 'FIXED' && $packet->remaining_count > 0 ? 
                        bcdiv($remainingAmount, $packet->remaining_count, 2) : 
                        '随机金额'
                ];
            }
            
        } catch (\Exception $e) {
            $this->error('获取失败：' . $e->getMessage());
        }
            
        $this->success('获取成功', $availablePackets);
    }
    
    /**
     * 获取用户今日投注金额（带缓存）
     */
    private function getUserTodayBetAmount(int $userId): float
    {
        // 先尝试从缓存获取
        $amount = \app\service\RedPacketCacheService::getUserTodayBetAmount($userId);
        
        if ($amount === null) {
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayEnd = strtotime(date('Y-m-d 23:59:59'));
            
            $totalAmount = BetOrder::where('user_id', $userId)
                ->where('create_time', 'between', [$todayStart, $todayEnd])
                ->where('status', 'in', ['CONFIRMED', 'WINNING', 'PAID', 'LOSING'])
                ->sum('total_amount');
            
            $amount = bcdiv($totalAmount ?: 0, 100, 2);
            
            // 缓存投注金额
            \app\service\RedPacketCacheService::cacheUserTodayBetAmount($userId, floatval($amount));
        }
        
        return floatval($amount);
    }

    /**
     * 获取已领取的红包
     */
    public function receivedRedPackets(): void
    {
        try {
            $userId = $this->auth->id;
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            
            // 查询用户已领取的红包记录
            $records = RedPacketRecord::with(['redPacket'])
                ->where('user_id', $userId)
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            $list = [];
            $totalAmount = 0;
            $totalCount = 0;
            
            foreach ($records->items() as $record) {
                $list[] = [
                    'id' => $record->id,
                    'amount' => $record->amount,
                    'time' => date('n月j日 H:i', $record->create_time),
                    'title' => $record->redPacket ? $record->redPacket->title : '红包已删除',
                    'blessing' => $record->redPacket ? $record->redPacket->blessing : '',
                    'create_time' => $record->create_time
                ];
                $totalAmount = bcadd($totalAmount, $record->amount, 2);
                $totalCount++;
            }
            
            // 获取用户总的红包统计
            $totalStats = RedPacketRecord::where('user_id', $userId)
                ->field('COUNT(*) as total_count, SUM(amount) as total_amount')
                ->find();
            
            $this->success('获取成功', [
                'list' => $list,
                'total' => $records->total(),
                'page' => $page,
                'limit' => $limit,
                'stats' => [
                    'totalAmount' => $totalStats->total_amount ?: '0.00',
                    'totalCount' => $totalStats->total_count ?: 0
                ]
            ]);
        } catch (\Exception $e) {
             $this->error('获取失败：' . $e->getMessage());
         }
     }
     
     /**
      * 领取红包（优化版）
      */
     public function claimRedPacket(): void
     {
         $redPacketId = $this->request->param('red_packet_id');
         if (!$redPacketId) {
             $this->error('红包ID不能为空');
         }

         $userId = $this->auth->id;
         $currentTime = time();

         // 开启事务
         Db::startTrans();
         try {
             // 查询红包信息（使用索引优化）
             $redPacket = RedPacket::where('id', $redPacketId)
                 ->where('status', 'ACTIVE')
                 ->where(function($query) use ($currentTime) {
                     $query->where('expire_time', '>', $currentTime)
                           ->whereOr('expire_time', 0);
                 })
                 ->where('remaining_count', '>', 0)
                 ->lock(true)
                 ->find();

             if (!$redPacket) {
                 throw new \Exception('红包不存在或已过期');
             }

             // 检查用户是否已经领取过（使用唯一索引）
             $existRecord = RedPacketRecord::where('red_packet_id', $redPacketId)
                 ->where('user_id', $userId)
                 ->find();

             if ($existRecord) {
                 throw new \Exception('您已经领取过这个红包了');
             }

             // 检查领取条件（使用缓存的投注金额）
             if ($redPacket->condition_type === 'MIN_BET') {
                 $minBetAmount = floatval($redPacket->condition_value);
                 $todayBetAmount = $this->getUserTodayBetAmount($userId);
                 
                 if ($todayBetAmount < $minBetAmount) {
                     throw new \Exception("今日投注金额不足{$minBetAmount}元，无法领取红包");
                 }
             }

             // 计算红包金额（包含分布式锁和amount_list更新）
             $amount = $this->calculateRedPacketAmount($redPacket);
             if ($amount <= 0) {
                 throw new \Exception('红包已被抢完或金额计算错误');
             }

             // 创建领取记录
             $record = new RedPacketRecord();
             $record->red_packet_id = $redPacketId;
             $record->user_id = $userId;
             $record->amount = bcdiv($amount, 100, 2);
             $record->ip = $this->request->ip();
             $record->user_agent = $this->request->header('user-agent', '');
             $record->create_time = $currentTime;
             $record->save();

             // 重新获取红包信息（因为amount_list已在calculateRedPacketAmount中更新）
             $redPacket = RedPacket::where('id', $redPacketId)->lock(true)->find();
             
             // 更新红包统计
             $redPacket->received_count += 1;
             $redPacket->received_amount = bcadd($redPacket->received_amount, $amount, 0);
             
             // 计算剩余数量：检查amount_list中还有多少未使用的红包
             // 直接获取原始数据，避免访问器缓存问题
             $amountListRaw = $redPacket->getData('amount_list');
             $amountList = is_string($amountListRaw) ? json_decode($amountListRaw, true) : $amountListRaw;
             $remainingCount = 0;
             if (!empty($amountList)) {
                 foreach ($amountList as $amountData) {
                     foreach ($amountData as $amount_val => $user_id) {
                         if ($user_id == 0) {
                             $remainingCount++;
                         }
                     }
                 }
             }
             $redPacket->remaining_count = $remainingCount;
             
             // 检查红包是否已领完
             if ($remainingCount <= 0) {
                 $redPacket->status = 'FINISHED';
             }
             
             $redPacket->save();

             // 使用FinanceService调整用户余额
             $amountInYuan = bcdiv($amount, 100, 2);
             $financeService = new \app\service\FinanceService();
             $financeService->adjustUserBalance(
                 $userId,
                 $amountInYuan,
                 '领取红包：' . $redPacket->title,
                 'RED_PACKET_RECEIVE'
             );

             // 清理相关缓存
             $this->clearUserRedPacketCache($userId);

             Db::commit();

         } catch (\Exception $e) {
             Db::rollback();
             $this->error('领取失败：' . $e->getMessage());
         }

        $this->success('领取成功', [
            'amount' => $amountInYuan,
            'title' => $redPacket->title,
            'blessing' => $redPacket->blessing
        ]);
     }
     
     /**
      * 计算红包金额
      */
     private function calculateRedPacketAmount(RedPacket $redPacket): int
     {
         if ($redPacket->type === 'FIXED') {
             // 固定红包：平均分配
             $remainingAmount = bcsub($redPacket->total_amount, $redPacket->received_amount, 0);
             return intval(bcdiv($remainingAmount, $redPacket->remaining_count, 0));
         } else {
             // 随机红包：从预设金额列表中按顺序领取
             // 直接从数据库获取最新的amount_list，避免模型访问器缓存问题
             $freshRedPacket = RedPacket::where('id', $redPacket->id)->lock(true)->find();
             if (!$freshRedPacket) {
                 return 0;
             }
             
             // 获取原始的amount_list数据（绕过访问器）
             $amountListRaw = $freshRedPacket->getData('amount_list');
             $amountList = is_string($amountListRaw) ? json_decode($amountListRaw, true) : $amountListRaw;
             
             if (!empty($amountList)) {
                 // 遍历amount_list，找到第一个未使用的红包（值为0）
                 foreach ($amountList as $index => $amountData) {
                     foreach ($amountData as $amount => $userId) {
                         if ($userId == 0) {  // 未使用的红包
                             // 使用Redis分布式锁防止高并发重复领取
                             $lockKey = "redpacket_claim_{$redPacket->id}_{$amount}_{$index}";
                             $cache = \think\facade\Cache::store();
                             
                             // 尝试获取锁，超时时间5秒
                             if ($cache->set($lockKey, $this->auth->id, 5)) {
                                 try {
                                     // 再次检查数据库中的状态，确保没有被其他进程修改
                                     $doubleCheckRedPacket = RedPacket::where('id', $redPacket->id)->lock(true)->find();
                                     $doubleCheckAmountListRaw = $doubleCheckRedPacket->getData('amount_list');
                                     $doubleCheckAmountList = is_string($doubleCheckAmountListRaw) ? json_decode($doubleCheckAmountListRaw, true) : $doubleCheckAmountListRaw;
                                     
                                     // 检查该位置是否仍然可用
                                     if (isset($doubleCheckAmountList[$index]) && isset($doubleCheckAmountList[$index][$amount]) && $doubleCheckAmountList[$index][$amount] == 0) {
                                         // 标记为当前用户已使用
                                         $doubleCheckAmountList[$index][$amount] = $this->auth->id;
                                         // 直接更新数据库字段，绕过修改器
                                         $doubleCheckRedPacket->setAttr('amount_list', json_encode($doubleCheckAmountList));
                                         $doubleCheckRedPacket->save();
                                         
                                         // 释放锁
                                         $cache->delete($lockKey);
                                         return intval($amount);
                                     }
                                 } catch (\Exception $e) {
                                     // 释放锁
                                     $cache->delete($lockKey);
                                     throw $e;
                                 }
                                 // 释放锁
                                 $cache->delete($lockKey);
                             }
                         }
                     }
                 }
             }
         }
         return 0;
     }
     
     /**
      * 清理用户红包相关缓存
      */
     private function clearUserRedPacketCache(int $userId): void
     {
         \app\service\RedPacketCacheService::clearUserRedPacketCache($userId);
         
         // 额外清理可能的前端缓存标识
         $cache = \think\facade\Cache::store();
         $cache->delete("user_available_redpackets_{$userId}");
         $cache->delete("user_received_redpackets_{$userId}");
     }

     //获取我的红包记录
     /**
      * 获取我的红包记录（支持滚动加载分页）
      */
     public function myRedPacketRecords(): void
     {
         try {
             $userId = $this->auth->id;
             $page = $this->request->param('page', 1);
             $limit = $this->request->param('limit', 20);
             
             // 查询用户已领取的红包记录
             $records = RedPacketRecord::with(['redPacket'])
                 ->where('user_id', $userId)
                 ->order('create_time', 'desc')
                 ->paginate([
                     'list_rows' => $limit,
                     'page' => $page
                 ]);
             
             $list = [];
             
             foreach ($records->items() as $record) {
                 $list[] = [
                     'id' => $record->id,
                     'amount' => $record->amount, // 转换为元
                     'time' => date('n月j日 H:i', $record->create_time),
                     'title' => $record->redPacket ? $record->redPacket->title : '红包已删除',
                     'blessing' => $record->redPacket ? $record->redPacket->blessing : '',
                     'create_time' => $record->create_time,
                     'red_packet_id' => $record->red_packet_id
                 ];
             }
             
             // 获取用户总的红包统计（仅在第一页时查询）
             $stats = [];
             if ($page == 1) {
                 $totalStats = RedPacketRecord::where('user_id', $userId)
                     ->field('COUNT(*) as total_count, SUM(amount) as total_amount')
                     ->find();
                 
                 $stats = [
                     'totalAmount' => bcdiv($totalStats->total_amount ?: 0, 100, 2),
                     'totalCount' => $totalStats->total_count ?: 0
                 ];
             }
             
             $result = [
                 'list' => $list,
                 'total' => $records->total(),
                 'page' => $page,
                 'limit' => $limit,
                 'hasMore' => $page < $records->lastPage(), // 是否还有更多数据
                 'lastPage' => $records->lastPage()
             ];
             
             // 第一页时包含统计信息
             if ($page == 1) {
                 $result['stats'] = $stats;
             }
             
         } catch (\Exception $e) {
             $this->error('获取失败：' . $e->getMessage());
         }
             
        $this->success('获取成功', $result);
     }

     //发送电子邮箱验证码
     public function sendEmailCode(): void
     {
         $email = $this->request->param('email');
         $type = $this->request->param('type', 'register'); // 默认为注册类型
         
         if (empty($email)) {
             $this->error('邮箱不能为空');
         }
         
         // 验证邮箱格式
         if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             $this->error('邮箱格式不正确');
         }
         
         try {
             // 检查发送频率
             if (!VerificationCode::checkSendFrequency($email, $type)) {
                 $this->error('发送过于频繁，请1分钟后再试');
             }
             
             // 如果是注册类型，检查邮箱是否已存在
             if ($type === 'register') {
                 $existUser = UserModel::where('email', $email)->find();
                 if ($existUser) {
                     $this->error('该邮箱已被注册');
                 }
             }
             
             // 生成验证码
             $code = VerificationCode::generateCode($email, $type);
             
             // 发送邮件（这里需要配置邮件服务）
             $this->sendEmail($email, $code, $type);
         } catch (\Exception $e) {
             $this->error('发送失败：' . $e->getMessage());
         }
            
        $this->success('验证码发送成功，请查收邮件');
     }
     
     /**
     * 发送邮件
     * @param string $email 邮箱
     * @param string $code 验证码
     * @param string $type 类型
     * @throws \Exception
     */
    private function sendEmail($email, $code, $type)
    {
        // 根据类型设置邮件标题和内容
        $subjects = [
            'register' => '注册验证码',
            'reset_password' => '找回密码验证码',
            'reset_pay_password' => '找回支付密码验证码',
            'change_email' => '修改邮箱验证码'
        ];
        
        $subject = $subjects[$type] ?? '验证码';
        $content = "您的验证码是：{$code}，有效期10分钟，请勿泄露给他人。";
        
        try {
            $mail = new \app\common\library\Email();
            
            if (!$mail->configured) {
                throw new \Exception('邮件服务未配置');
            }
            
            // 发送邮件
            $mail->isSMTP();
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->setSubject($subject);
            $mail->Body = "<p>{$content}</p>";
            $mail->AltBody = $content;
            
            $mail->send();
            \think\facade\Log::info("邮件发送成功到 {$email}");
        } catch (\Exception $e) {
            \think\facade\Log::error("邮件发送失败到 {$email}：" . $e->getMessage());
            throw new \Exception('邮件发送失败，请稍后重试');
        }
     }

     //找回密码
     public function resetPassword(): void
     {
         $data = $this->request->param();
         try {
             $email = $data['email'] ?? '';
             $code = $data['code'] ?? '';
             $password = $data['password'] ?? '';
             if (empty($email) || empty($code) || empty($password)) {
                 throw new \Exception('参数错误');
             }
             // 验证验证码
             $verification = VerificationCode::where([
                 'email' => $email,
                 'code' => $code,
                 'type' => VerificationCode::TYPE_RESET_PASSWORD,
                 'status' => VerificationCode::STATUS_UNUSED
             ])->find();
             if (!$verification) {
                 throw new \Exception('验证码错误');
             }
             // 更新用户密码
             $user = UserModel::where('email', $email)->find();
             if (!$user) {
                 throw new \Exception('用户不存在');
             }
             $user->password = password_hash($password, PASSWORD_DEFAULT);
             $user->save();
             // 标记验证码为已使用
             $verification->status = VerificationCode::STATUS_USED;
             $verification->save();
         } catch (\Exception $e) {
             $this->error('重置失败：' . $e->getMessage());
         }
         $this->success('重置成功');
     }

     //找回支付密码
     public function resetPayPassword(): void
     {
         $data = $this->request->param();
         try {
             $email = $data['email'] ?? '';
             $code = $data['code'] ?? '';
             $payPassword = $data['payPassword'] ?? '';
             if (empty($email) || empty($code) || empty($payPassword)) {
                 throw new \Exception('参数错误');
             }
             // 验证支付密码格式（6位数字）
             if (!preg_match('/^\d{6}$/', $payPassword)) {
                 throw new \Exception('支付密码必须是6位数字');
             }
             // 验证验证码
             $verification = VerificationCode::where([
                 'email' => $email,
                 'code' => $code,
                 'type' => 'reset_pay_password',
                 'status' => VerificationCode::STATUS_UNUSED
             ])->find();
             if (!$verification) {
                 throw new \Exception('验证码错误');
             }
             // 更新用户支付密码
             $user = UserModel::where('email', $email)->find();
             if (!$user) {
                 throw new \Exception('用户不存在');
             }
             $user->pay_password = password_hash($payPassword, PASSWORD_DEFAULT);
             $user->save();
             // 标记验证码为已使用
             $verification->status = VerificationCode::STATUS_USED;
             $verification->save();
         } catch (\Exception $e) {
             $this->error('重置失败：' . $e->getMessage());
         }
         $this->success('重置成功');
     }    
}