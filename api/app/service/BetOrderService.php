<?php

namespace app\service;
use Exception;
use think\facade\Log;
use app\common\model\LotteryBonus;
use app\service\UserService;
/**
 * 订单服务类
 * 处理订单相关的业务逻辑
 */
class BetOrderService
{
    /**
     * @var BetOrderService
     */
    private $betOrderService;

    public function __construct()
    {
        $this->betOrderService = new BetOrderService();
    }
    /**
     * 处理投注返佣
     * @param int $userId 用户ID
     * @param float $betAmount 投注金额
     * @param array $orderNos 订单号数组
     * @return void
     */
    public static function processBetRebate(int $userId, float $betAmount, array $orderNos, $type = 'bet'): void
    {
        try {
            // 获取用户返佣比例信息
            $rebateInfo = UserService::getUserBrokRate($userId);
            
            $financeService = new FinanceService();
            $orderNoStr = implode(',', $orderNos);
            // 用户自身投注返佣
            if($type == 'bet'){
                if ($rebateInfo['rebate_rate'] > 0) {
                    $userRebateAmount = $betAmount * ($rebateInfo['rebate_rate'] / 100);
                    $financeService->adjustUserBalance(
                        $userId, 
                        $userRebateAmount, 
                        '投注返佣', 
                        'COMMISSION_ADD'
                    );
                }
            }else{
                // 非投注类型，直接计算差值返佣
                if ($rebateInfo['nowin_rate'] > 0) {
                    $userRebateAmount = $betAmount * ($rebateInfo['nowin_rate'] / 100);
                    $financeService->adjustUserBalance(
                        $userId, 
                        $userRebateAmount, 
                        '未中奖返佣', 
                        'COMMISSION_ADD'
                    );
                }
            }
            
            // 上级代理返佣（差值返佣）
            if ($rebateInfo['parent_id'] > 0) {
                $agentRebateRate = $rebateInfo['agent_rebate_rate'];
                $agentNowinRate = $rebateInfo['agent_nowin_rate'];
                $userRebateRate = $rebateInfo['rebate_rate'];
                $nowinRebateRate = $rebateInfo['nowin_rate'];
                
                // 计算代理应得的返佣差值
                $rebateDiff = $agentRebateRate - $userRebateRate;
                $nowinDiff = $agentNowinRate - $nowinRebateRate;
                
                if($type == 'bet'){
                    if($rebateDiff > 0){
                        $agentRebateAmount = $betAmount * ($rebateDiff / 100);
                        $financeService->adjustUserBalance(
                            $rebateInfo['parent_id'], 
                            $agentRebateAmount, 
                            '下级投注返佣 - 用户ID：' . $userId, 
                            'COMMISSION_ADD'
                        );
                    }
                }else{
                    if($nowinDiff > 0){
                        $agentNowinAmount = $betAmount * ($nowinDiff / 100);
                        $financeService->adjustUserBalance(
                            $rebateInfo['parent_id'], 
                            $agentNowinAmount, 
                            '下级未中奖返佣 - 用户ID：' . $userId, 
                            'COMMISSION_ADD'
                        );
                    }
                }
            }
            
        } catch (Exception $e) {
            Log::error('处理投注返佣失败: ' . $e->getMessage());
            // 返佣失败不影响投注主流程，只记录日志
        }
    }
    
    /**
     * 根据投注类型获取类型名称（向后兼容）
     * @param string $type
     * @return string
     */
    public static function getBetTypeName(string $type): string
    {
        // 尝试从数据库获取类型名称
        try {
            $bonusRecord = LotteryBonus::where('type', $type)
                ->where('status', 1)
                ->find();
            
            if ($bonusRecord && $bonusRecord->type_name) {
                return $bonusRecord->type_name;
            }
        } catch (Exception $e) {
            // 如果查询失败，使用默认映射
        }
        
        return $names[$type] ?? '未知类型';
    }
}