<?php

namespace app\api\controller;

use app\common\model\BetOrder;
use app\common\model\LotteryType;
use app\common\controller\Frontend;
use app\service\LotteryService;
use think\facade\Db;
use think\facade\Request;
use think\facade\Cache;
use Exception;
use think\facade\Log;
use think\exception\ValidateException;
use app\service\FinanceService;
use app\validate\BetValidate;
use app\service\BetOrderService;
use app\service\LotteryBetService;
use app\service\WebsockService;

class Bet extends Frontend
{

    public function initialize(): void
    {
        parent::initialize();
        
        // 验证用户是否为代理商
        if (!$this->auth->isLogin()) {
            $this->error(__('Please login first'));
        }
        
        if ($this->auth->is_agent != 0) {
            $this->error('您不是用户，无权访问此接口');
        }
    }
    
    /**
     * 提交投注
     */
    public function submit()
    {
        try {
            // 获取用户信息
            $user = $this->auth->getUser();
            
            // 获取请求参数
            $params = Request::param();
            
            // 参数验证（包含期号验证、余额检查等）
            $validateResult = $this->validateBetParams($params, $user);
            if ($validateResult['code'] != 1) {
                throw new ValidateException($validateResult['msg']);
            }

            $lotteryType = LotteryType::where('type_code', $params['lottery_code'])->find();
            
            $validatedData = $validateResult['data'];
            $totalAmount = $validatedData['total_amount'];
            
            // 开启事务
            Db::startTrans();
            
            try {
                // 计算赠送金额扣除（确保不超过用户实际拥有的gift_money）
                $giftMoneyToDeduct = min($user->gift_money, $totalAmount);
                $giftMoneyRatio = $totalAmount > 0 ? $giftMoneyToDeduct / $totalAmount : 0;
                
                // 批量创建投注订单
                $orderNos = [];
                $orderIds = [];

                foreach ($validatedData['bet_data'] as $betItem) {
                    // 生成订单号
                    $orderNo = BetOrder::generateOrderNo();
                    $orderNos[] = $orderNo;
                    
                    // 计算当前订单的赠送金额（考虑单项倍数和注数）
                    $itemMultiplier = $betItem['multiplier'] ?? 1;
                    $itemNote = $betItem['note'] ?? 1;
                    $orderTotalAmount = $betItem['money'] * $itemMultiplier * $itemNote;
                    $orderGiftMoney = (int)($orderTotalAmount * $giftMoneyRatio);

                    $lotteryService = new LotteryService();
                    
                    // 获取赔率值
                    $odds = $this->getOddsFromBetItem($betItem, $validatedData['lottery_code']);
                    
                    // 构建bet_content，对于快彩只保存玩法的值
                    $betContent = [
                        'numbers' => $betItem['numbers'] ?? $betItem['type_name'], // 玩法值（如'大'、'小'、'和'）
                        'type_key' => $betItem['type_key'],
                        'type_name' => $betItem['type_name'],
                        'odds' => $odds
                    ];
                    
                    // 创建投注订单
                    $betOrder = BetOrder::create([
                        'order_no' => $orderNo,
                        'user_id' => $user->id,
                        'lottery_type_id' => $lotteryService->getLotteryTypeId($validatedData['lottery_code']),     
                        'lottery_code' => $validatedData['lottery_code'],
                        'period_no' => $validatedData['period_no'],
                        'bet_content' => $lotteryType['category'] == 'QUICK' ? $betContent['type_key'] : json_encode($betContent, JSON_UNESCAPED_UNICODE),
                        'bet_amount' => $betItem['money'],
                        'gift_money' => $orderGiftMoney,
                        'gift_money_ratio' => $giftMoneyRatio,
                        'multiple' => $itemMultiplier,
                        'note' => $itemNote,
                        'total_amount' => $orderTotalAmount,
                        'win_amount' => 0,
                        'commission_amount' => 0,
                        'agent_id' => $this->auth->parent_id,
                        'odds' => $odds,
                        'bet_type' => $betItem['type_key'],
                        'bet_type_name' => $betItem['type_name'],
                        'status' => BetOrder::STATUS_CONFIRMED,
                        'draw_result' => '',
                        'draw_time' => 0,
                        'settle_time' => 0,
                        'ip' => Request::ip(),
                        'user_agent' => Request::header('User-Agent', ''),
                        'remark' => '',
                        'create_time' => time(),
                        'update_time' => time()
                    ]);
                    
                    $orderIds[] = $betOrder->id;
                }
                
                // 扣除用户赠送金额（直接操作gift_money字段，不添加账变记录）
                if ($giftMoneyToDeduct > 0) {
                    $user->gift_money -= $giftMoneyToDeduct;
                    $user->save();
                }
                
                // 记录资金变动（扣除投注金额）
                $financeService = new FinanceService();
                $financeService->adjustUserBalance($user->id, -$totalAmount, '投注扣款', 'BET_DEDUCT');
                
                // 处理投注返佣
                BetOrderService::processBetRebate($user->id, $totalAmount, $orderNos);
                
                // 提交事务
                Db::commit();
                
                $lotteryBetService = new LotteryBetService();
                $lotteryBetService->updateBonusPool($validatedData['lottery_code'], $totalAmount);
                
                // 投注成功后，清理用户红包相关缓存（因为投注金额发生变化）
                \app\service\RedPacketCacheService::clearUserRedPacketCache($user->id);
                
                // 投注成功后，推送奖池更新（80%的投注金额）
                // WebsockService::pushPrizePoolUpdate($validatedData['lottery_code'], $totalAmount, $user->id);
                
            } catch (ValidateException $e) {
                Db::rollback();
                Log::error('投注验证失败: ' . $e->getMessage());
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                Log::error('投注失败: ' . $e->getMessage());
                $this->error('投注失败，请重试：'.$e->getMessage());
            }
            
        } catch (Exception $e) {
            Log::error('投注异常: ' . $e->getMessage());
            $this->error('系统异常：'.$e->getMessage());
        }
                
        $this->success('投注成功', [
            'order_nos' => $orderNos,
            'order_ids' => $orderIds,
            'total_amount' => number_format($totalAmount / 100, 2),
            'remaining_balance' => number_format(($user->money - $totalAmount) / 100, 2),
            'period_no' => $validatedData['period_no']
        ]);
    }
    
    /**
     * 从投注项中获取赔率值
     * @param array $betItem 投注项数据
     * @param string $lotteryCode 彩种代码
     * @return float 赔率值
     */
    private function getOddsFromBetItem(array $betItem, string $lotteryCode): float
    {
        try {
            // 获取bonus_index
            $bonusIndex = $betItem['bonus_index'] ?? 0;
            
            // 获取彩种信息
            $lotteryType = \app\common\model\LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lotteryType) {
                Log::error('彩种不存在: ' . $lotteryCode);
                return 0;
            }
            
            // 获取玩法信息
            $lotteryBonus = \app\common\model\LotteryBonus::where('lottery_id', $lotteryType->id)
                ->where('type_key', $betItem['type_key'])
                ->where('type_name', $betItem['type_name'])
                ->find();
                
            if (!$lotteryBonus) {
                Log::error('玩法不存在: ' . $betItem['type_key'] . ' - ' . $betItem['type_name']);
                return 0;
            }
            
            // 解析bonus_json获取赔率数组
            $bonusJson = $lotteryBonus->bonus_json;
            if (is_string($bonusJson)) {
                $bonusArray = json_decode($bonusJson, true);
            } else {
                $bonusArray = $bonusJson;
            }
            
            if (!is_array($bonusArray)) {
                Log::error('bonus_json格式错误: ' . json_encode($bonusJson));
                return 0;
            }
            
            // 根据bonus_index获取对应的赔率值
            if (isset($bonusArray[$bonusIndex])) {
                return floatval($bonusArray[$bonusIndex]);
            } else {
                Log::error('bonus_index超出范围: ' . $bonusIndex . ', 数组长度: ' . count($bonusArray));
                // 如果索引超出范围，返回第一个赔率值
                return isset($bonusArray[0]) ? floatval($bonusArray[0]) : 0;
            }
            
        } catch (\Exception $e) {
            Log::error('获取赔率失败: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 验证投注参数（包含期号验证和余额检查）
     * @param array $params
     * @param object $user 用户对象
     * @return array
     */
    private function validateBetParams(array $params, $user): array
    {
        // 获取彩种代码
        $lotteryCode = $params['lottery_code'] ?? '';
        
        // 尝试获取彩种专用验证器
        $validator = BetValidate::getLotteryValidator($lotteryCode);
        
        // 如果没有专用验证器，使用通用验证器
        if (!$validator) {
            $validator = new BetValidate();
        }
        
        // 基础参数验证
        $result = $validator->validateBetParams($params);
        if ($result['code'] != 1) {
            return $result;
        }
        
        $validatedData = $result['data'];
        
        // 期号验证
        $lotteryService = new LotteryService();
        $periodValidation = $lotteryService->validatePeriod(
            $validatedData['period_no'], 
            $validatedData['lottery_code']
        );
        
        if ($periodValidation['code'] != 1) {
            return ['code' => 0, 'msg' => $periodValidation['msg']];
        }

        if($periodValidation['data']['status'] != 'normal'){
            return ['code' => 0, 'msg' => '封盘中，不能投注'];
        }
        
        // 余额检查
        $totalAmount = $validatedData['total_amount'];
        if ($user->money < $totalAmount) {
            return [
                'code' => 0, 
                'msg' => '余额不足，当前余额：' . $user->money . '元，需要：' . $totalAmount . '元'
            ];
        }
        
        // 添加用户信息到验证结果
        $validatedData['user_balance'] = $user->money;
        
        return ['code' => 1, 'msg' => '验证通过', 'data' => $validatedData];
    }
    
    /**
     * 获取最大投注额
     * @return \think\response\Json
     */
    public function getMaxBetAmount()
    {

        try {
            // 获取参数
            $lotteryCode = $this->request->param('lottery_code', '');
            $period = $this->request->param('period', '');
            $playType = $this->request->param('play_type', '');
            $odds = floatval($this->request->param('odds', 0));
            $userId = $this->auth->id ?? 0;
            
            // 参数验证
            if (empty($lotteryCode)) {
                throw new \Exception('彩种代码不能为空');
            }
            if (empty($period)) {
                throw new \Exception('期号不能为空');
            }
            if (empty($playType)) {
                throw new \Exception('玩法类型不能为空');
            }
            if ($odds <= 0) {
                throw new \Exception('赔率必须大于0');
            }
            
            // 验证玩法类型
            $validPlayTypes = ['da', 'xiao', 'he'];
            if (!in_array($playType, $validPlayTypes)) {
                throw new \Exception('无效的玩法类型');
            }
            
            // 获取彩种ID
            $lotteryService = new \app\service\LotteryService();
            $lotteryId = $lotteryService->getLotteryTypeId($lotteryCode);
            if (!$lotteryId) {
                throw new \Exception('无效的彩种代码');
            }
            
            // 调用最大投注计算服务
            $lotteryBetService = new \app\service\LotteryBetService();
            $result = $lotteryBetService->calculateMaxBetAmount($lotteryCode, $period, $playType, $odds, $userId);
            
            if ($result['status'] === 'error') {
                throw new \Exception($result['message']);
            }
            
            // 准备返回数据
            $responseData = [
                'max_bet_amount' => $result['max_bet_amount'],
                'system_max_bet' => $result['system_max_bet'],
                'user_max_bet' => $result['user_max_bet'],
                'current_bonus_pool' => $result['current_bonus_pool'],
                'user_total_bet' => $result['user_total_bet'],
                'odds' => $result['odds'],
                'lottery_code' => $lotteryCode,
                'period' => $period,
                'play_type' => $playType
            ];
            
        } catch (\Exception $e) {
            $this->error('获取最大投注额失败：' . $e->getMessage());
        }
        
        $this->success('获取成功', $responseData);
    }
}