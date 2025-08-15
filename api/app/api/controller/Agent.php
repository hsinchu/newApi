<?php

namespace app\api\controller;

use Throwable;
use think\facade\Db;
use app\common\model\User;
use app\common\controller\Frontend;
use app\api\validate\Agent as AgentValidate;
use app\service\FinanceService;
use app\service\UserService;
use app\common\model\UserMoneyLog;
use app\common\model\BetOrder;
use app\common\model\RechargeGift;
use app\common\library\MoneyLogTypeHelper;
use app\common\model\WithdrawRecord;
use Exception;
use think\middleware\Throttle;

class Agent extends Frontend
{
    protected array $noNeedLogin = [];

    public function initialize(): void
    {
        parent::initialize();
        
        // 验证用户是否为代理商
        if (!$this->auth->isLogin()) {
            $this->error(__('Please login first'));
        }
        
        if ($this->auth->is_agent != 1) {
            $this->error('您不是代理商，无权访问此接口');
        }
    }

    /**
     * 获取代理商统计数据
     */
    public function stats(): void
    {
        try {
            $agentId = $this->auth->id;
        
        // 获取下级会员数量
        $memberCount = User::where('parent_id', $agentId)
            ->where('is_agent', 0)
            ->where('status', 1)
            ->count();
        
            // 获取下级代理商数量
            $subAgentCount = User::where('parent_id', $agentId)
                ->where('is_agent', 1)
                ->where('status', 1)
                ->count();
            
            // 获取代理商余额
            $agentInfo = $this->auth->getUserInfo();
            $balance = bcdiv($agentInfo['money'], 100, 2);
            
            // 获取今日新增会员数
            $todayStart = strtotime(date('Y-m-d 00:00:00'));
            $todayEnd = strtotime(date('Y-m-d 23:59:59'));
            $todayNewMembers = User::where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->where('create_time', 'between', [$todayStart, $todayEnd])
                ->count();
            
            // 获取本月佣金收入（从资金变动记录中统计）
            $monthStart = strtotime(date('Y-m-01 00:00:00'));
            $monthEnd = strtotime(date('Y-m-t 23:59:59'));
            $monthCommission = UserMoneyLog::where('user_id', $agentId)
                ->where('type', 'COMMISSION_ADD')
                ->where('create_time', 'between', [$monthStart, $monthEnd])
                ->sum('money');
            $monthCommission = bcdiv($monthCommission, 100, 2);
            
            // 获取总佣金收入
            $totalCommission = UserMoneyLog::where('user_id', $agentId)
                ->where('type', 'COMMISSION_ADD')
                ->sum('money');
            $totalCommission = bcdiv($totalCommission, 100, 2);
            
            // 获取下级会员总投注金额（本月）
            $memberIds = User::where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->column('id');
            
            $monthBetAmount = 0;
            if (!empty($memberIds)) {
                $monthBetAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'BET_DEDUCT')
                    ->where('create_time', 'between', [$monthStart, $monthEnd])
                    ->sum('money');
                $monthBetAmount = abs(bcdiv($monthBetAmount, 100, 2));
            }
            
            // 获取下级会员总中奖金额（本月）
            $monthPrizeAmount = 0;
            if (!empty($memberIds)) {
                $monthPrizeAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'PRIZE_ADD')
                    ->where('create_time', 'between', [$monthStart, $monthEnd])
                    ->sum('money');
                $monthPrizeAmount = bcdiv($monthPrizeAmount, 100, 2);
            }
            
            $statsData = [
                'balance' => $balance, // 余额
                'member_count' => $memberCount, // 下级会员数
                'sub_agent_count' => $subAgentCount, // 下级代理商数
                'today_new_members' => $todayNewMembers, // 今日新增会员
                'month_commission' => $monthCommission, // 本月佣金
                'total_commission' => $totalCommission, // 总佣金
                'month_bet_amount' => $monthBetAmount, // 本月投注额
                'month_prize_amount' => $monthPrizeAmount, // 本月中奖额
                'month_profit' => bcsub($monthBetAmount, $monthPrizeAmount, 2) // 本月盈利
            ];
        } catch (Exception $e) {
            $this->error('获取统计数据失败：' . $e->getMessage());
        }
        
        $this->success('获取统计数据成功', $statsData);
    }

    /**
     * 获取下级会员列表
     */
    public function members(): void
    {
        try {
            $agentId = $this->auth->id;
            $params = $this->request->param(['keyword', 'agent_favorite']);
            
            $keyword = $params['keyword'] ?? '';
            $isFavorite = $params['agent_favorite'] ?? 0;
            
            $where = [
                ['parent_id', '=', $agentId],
                ['is_agent', '=', 0]
            ];
            
            if ($keyword) {
                $where[] = ['username|nickname|mobile|email', 'like', '%' . $keyword . '%'];
            }
            
            // 收藏筛选
            if ($isFavorite == 1) {
                $where[] = ['agent_favorite', '=', 1];
            }
            
            $members = User::where($where)
                ->field('*')
                ->order('agent_favorite desc, create_time desc')
                ->select();
            
            // 转换金额单位
            $list = $members->toArray();
            foreach ($list as &$item) {
                $item['money'] = $item['money'];
                $item['unwith_money'] = bcdiv($item['unwith_money'], 100, 2);
                $item['agent_favorite'] = (int)$item['agent_favorite'];
                
                // 获取会员最近投注时间
                $lastBetTime = UserMoneyLog::where('user_id', $item['id'])
                    ->where('type', 'BET_DEDUCT')
                    ->order('create_time', 'desc')
                    ->value('create_time');
                $item['last_bet_time'] = $lastBetTime ? $lastBetTime : 0;
                
                // 获取会员本月投注金额
                $monthStart = strtotime(date('Y-m-01 00:00:00'));
                $monthEnd = strtotime(date('Y-m-t 23:59:59'));
                $monthBetAmount = UserMoneyLog::where('user_id', $item['id'])
                    ->where('type', 'BET_DEDUCT')
                    ->where('create_time', 'between', [$monthStart, $monthEnd])
                    ->sum('money');
                $item['month_bet_amount'] = abs($monthBetAmount ?: 0);
            }
        } catch (Exception $e) {
            $this->error('获取会员列表失败：' . $e->getMessage());
        }
            
        $this->success('获取会员列表成功', [
            'data' => $list,
            'total' => count($list)
        ]);
    }

    /**
     * 获取下级代理商列表
     */
    public function subAgents(): void
    {
        try {
            $agentId = $this->auth->id;
            $params = $this->request->param(['page', 'limit', 'keyword']);
            
            // 参数验证
            $validate = new AgentValidate();
            if (!$validate->scene('subAgents')->check($params)) {
                $this->error($validate->getError());
            }
        
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $keyword = $params['keyword'] ?? '';
        
        $where = [
            ['parent_id', '=', $agentId],
            ['is_agent', '=', 1]
        ];
        
        if ($keyword) {
            $where[] = ['username|nickname|mobile|email', 'like', '%' . $keyword . '%'];
        }
        
        $subAgents = User::where($where)
            ->field('id,username,nickname,avatar,email,mobile,money,unwith_money,default_rebate_rate,default_nowin_rate,rebate_rate,nowin_rate,is_verified,last_login_time,join_time,status')
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
        
        // 转换金额单位
        $list = $subAgents->items();
        foreach ($list as &$item) {
            $item['money'] = bcdiv($item['money'], 100, 2);
            $item['unwith_money'] = bcdiv($item['unwith_money'], 100, 2);
            
            // 获取该代理商的下级会员数
            $item['member_count'] = User::where('parent_id', $item['id'])
                ->where('is_agent', 0)
                ->count();
        }
        
            $this->success('获取下级代理商列表成功', [
                'list' => $list,
                'total' => $subAgents->total(),
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Throwable $e) {
            $this->error('获取下级代理商列表失败：' . $e->getMessage());
        }
    }

    /**
     * 收藏/取消收藏会员
     */
    public function toggleMemberFavorite(): void
    {
        try {
            $agentId = $this->auth->id;
            $data = $this->request->post();
            
            // 参数验证
            if (empty($data['member_id'])) {
                $this->error('会员ID不能为空');
            }
            
            $memberId = (int)$data['member_id'];
            
            // 验证会员是否属于当前代理商
            $member = User::where('id', $memberId)
                ->where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->find();
                
            if (!$member) {
                $this->error('会员不存在或不属于您的下级');
            }
            
            // 切换收藏状态
            $newFavoriteStatus = $member->agent_favorite == 1 ? 0 : 1;
            $member->agent_favorite = $newFavoriteStatus;
            $member->save();
            
            $action = $newFavoriteStatus ? '收藏' : '取消收藏';
            
        } catch (Exception $e) {
            $this->error('操作失败：' . $e->getMessage());
        }
        $this->success($action . '成功', [
            'member_id' => $memberId,
            'agent_favorite' => $newFavoriteStatus
        ]);
    }

    /**
     * 获取会员详细信息
     */
    public function memberDetail(): void
    {
        try {
            $agentId = $this->auth->id;
            $memberId = $this->request->param('id');
            
            if (empty($memberId)) {
                $this->error('会员ID不能为空');
            }
            
            // 验证会员是否属于当前代理商
            $member = User::where('id', $memberId)
                ->where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->find();
                
            if (!$member) {
                $this->error('会员不存在或不属于您的下级');
            }
            
            // 转换金额单位
            $member->money = $member->money;
            $member->unwith_money = $member->unwith_money;
            
            // 获取统计数据
            $stats = [];
            
            // 总投注金额
            $totalBetAmount = UserMoneyLog::where('user_id', $memberId)
                ->where('type', 'BET_DEDUCT')
                ->sum('money');
            $stats['total_bet_amount'] = abs(bcdiv($totalBetAmount, 100, 2));
            
            // 总中奖金额
            $totalPrizeAmount = UserMoneyLog::where('user_id', $memberId)
                ->where('type', 'PRIZE_ADD')
                ->sum('money');
            $stats['total_prize_amount'] = bcdiv($totalPrizeAmount, 100, 2);
            
            // 本月投注金额
            $monthStart = strtotime(date('Y-m-01 00:00:00'));
            $monthEnd = strtotime(date('Y-m-t 23:59:59'));
            $monthBetAmount = UserMoneyLog::where('user_id', $memberId)
                ->where('type', 'BET_DEDUCT')
                ->where('create_time', 'between', [$monthStart, $monthEnd])
                ->sum('money');
            $stats['month_bet_amount'] = abs(bcdiv($monthBetAmount, 100, 2));
            
            // 本月中奖金额
            $monthPrizeAmount = UserMoneyLog::where('user_id', $memberId)
                ->where('type', 'PRIZE_ADD')
                ->where('create_time', 'between', [$monthStart, $monthEnd])
                ->sum('money');
            $stats['month_prize_amount'] = bcdiv($monthPrizeAmount, 100, 2);
            
            // 投注次数
            $betCount = UserMoneyLog::where('user_id', $memberId)
                ->where('type', 'BET_DEDUCT')
                ->count();
            $stats['bet_count'] = $betCount;
            
            $userBrokRate = UserService::getUserBrokRate($memberId);
            $member['nowin_rate'] = $userBrokRate['nowin_rate'];
            $member['rebate_rate'] = $userBrokRate['rebate_rate'];
            $member['agent_nowin_rate'] = $userBrokRate['agent_nowin_rate'];
            $member['agent_rebate_rate'] = $userBrokRate['agent_rebate_rate'];
            unset($member['default_nowin_rate']);
            unset($member['default_rebate_rate']);
            unset($member['password']);
            unset($member['last_login_ip']);
            
        } catch (Exception $e) {
            $this->error('获取会员详情失败：' . $e->getMessage());
        }
            
        $this->success('获取会员详情成功', [
            'member' => $member,
            'stats' => $stats,
        ]);
    }

    /**
     * 批量操作会员
     */
    public function batchOperateMembers(): void
    {
        try {
            $agentId = $this->auth->id;
            $data = $this->request->post();
            
            // 参数验证
            if (empty($data['member_ids']) || !is_array($data['member_ids'])) {
                $this->error('请选择要操作的会员');
            }
            
            if (empty($data['action']) || !in_array($data['action'], ['favorite', 'unfavorite', 'enable', 'disable'])) {
                $this->error('操作类型参数错误');
            }
            
            $memberIds = array_map('intval', $data['member_ids']);
            $action = $data['action'];
            
            // 验证会员是否都属于当前代理商
            $validMembers = User::where('id', 'in', $memberIds)
                ->where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->column('id');
                
            if (count($validMembers) !== count($memberIds)) {
                $this->error('部分会员不存在或不属于您的下级');
            }
            
            // 执行批量操作
            $updateData = [];
            switch ($action) {
                case 'favorite':
                    $updateData['agent_favorite'] = 1;
                    $message = '批量收藏成功';
                    break;
                case 'unfavorite':
                    $updateData['agent_favorite'] = 0;
                    $message = '批量取消收藏成功';
                    break;
                case 'enable':
                    $updateData['status'] = 1;
                    $message = '批量启用成功';
                    break;
                case 'disable':
                    $updateData['status'] = 2;
                    $message = '批量禁用成功';
                    break;
            }
            
            User::where('id', 'in', $memberIds)->update($updateData);
            
            $this->success($message, [
                'affected_count' => count($memberIds)
            ]);
            
        } catch (Throwable $e) {
            $this->error('批量操作失败：' . $e->getMessage());
        }
    }

    /**
     * 设置会员返佣比例
     */
    public function setMemberRebate(): void
    {
        try {
            $agentId = $this->auth->id;
            $data = $this->request->post();
            
            // 参数验证
            if (empty($data['member_id'])) {
                throw new Exception('会员ID不能为空');
            }
            
            $memberId = (int)$data['member_id'];
            
            // 验证会员是否属于当前代理商
            $member = User::where('id', $memberId)
                ->where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->find();
                
            if (!$member) {
                throw new Exception('会员不存在或不属于您的下级');
            }
            
            // 获取代理商信息
            $agent = User::where('id', $agentId)->find();
            
            $updateData = [];
            
            // 处理投注返佣比例
            if (isset($data['rebate_rate']) && is_numeric($data['rebate_rate'])) {
                $rebateRate = (float)$data['rebate_rate'];
                
                // 验证返佣比例范围
                if ($rebateRate < -1) {
                    throw new Exception('返佣比例不能小于-1，-1表示使用代理默认值');
                }
                
                // 如果不是-1，验证不能超过代理商的返佣比例
                if ($rebateRate != -1 && $rebateRate > $agent->rebate_rate) {
                    throw new Exception('返佣比例不能超过代理商的返佣比例(' . $agent->rebate_rate . '%)');
                }
                
                $updateData['rebate_rate'] = $rebateRate;
            }
            
            // 处理未中奖返佣比例
            if (isset($data['nowin_rate']) && is_numeric($data['nowin_rate'])) {
                $nowinRate = (float)$data['nowin_rate'];
                
                // 验证返佣比例范围
                if ($nowinRate < -1) {
                    throw new Exception('未中奖返佣比例不能小于-1，-1表示使用代理默认值');   
                }
                
                // 如果不是-1，验证不能超过代理商的未中奖返佣比例
                if ($nowinRate != -1 && $nowinRate > $agent->nowin_rate) {
                    throw new Exception('未中奖返佣比例不能超过代理商的未中奖返佣比例(' . $agent->nowin_rate . '%)');
                }
                
                $updateData['nowin_rate'] = $nowinRate;
            }
            
            if (empty($updateData)) {
                throw new Exception('请提供要更新的返佣比例参数');
            }
            
            // 更新返佣比例
            foreach ($updateData as $field => $value) {
                $member->$field = number_format($value, 2, '.', '');
            }
            $member->save();
            
        } catch (Exception $e) {
            $this->error('设置返佣比例失败：' . $e->getMessage());
        }
            
            $this->success('返佣比例设置成功', [
                'member_id' => $memberId,
                'rebate_rate' => $member->rebate_rate,
                'nowin_rate' => $member->nowin_rate
            ]);
    }

    /**
     * 获取代理商统计数据（按日期范围）
     */
    public function getStatistics(): void
    {
        try {
            $agentId = $this->auth->id;
            $params = $this->request->param(['start_date', 'end_date']);
            
            // 默认查询今天的数据
            $startDate = $params['start_date'] ?? date('Y-m-d');
            $endDate = $params['end_date'] ?? date('Y-m-d');
            
            // 转换为时间戳
            $startTime = strtotime($startDate . ' 00:00:00');
            $endTime = strtotime($endDate . ' 23:59:59');
            
            if ($startTime > $endTime) {
                $this->error('开始日期不能大于结束日期');
            }
            
            // 获取下级会员ID列表
            $memberIds = User::where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->column('id');
            
            $statistics = [];
            
            // 1. 总投注金额（下级会员在指定时间范围内的投注）
            $totalBetAmount = 0;
            if (!empty($memberIds)) {
                $totalBetAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'BET_DEDUCT')
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('money');
                $totalBetAmount = abs(bcdiv($totalBetAmount, 100, 2));
            }
            $statistics['total_bet_amount'] = $totalBetAmount;
            
            // 2. 总中奖金额（下级会员在指定时间范围内的中奖）
            $totalPrizeAmount = 0;
            if (!empty($memberIds)) {
                $totalPrizeAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'PRIZE_ADD')
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('money');
                $totalPrizeAmount = bcdiv($totalPrizeAmount, 100, 2);
            }
            $statistics['total_prize_amount'] = $totalPrizeAmount;
            
            // 3. 新增会员数量（在指定时间范围内新增的下级会员）
            $newMemberCount = User::where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->where('create_time', 'between', [$startTime, $endTime])
                ->count();
            $statistics['new_member_count'] = $newMemberCount;
            
            // 4. 下级会员充值金额（下级会员在指定时间范围内的充值）
            $memberRechargeAmount = 0;
            if (!empty($memberIds)) {
                $memberRechargeAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'RECHARGE_ADD')
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('money');
                $memberRechargeAmount = bcdiv($memberRechargeAmount, 100, 2);
            }
            $statistics['member_recharge_amount'] = $memberRechargeAmount;
            
            // 5. 下级会员提现金额（下级会员在指定时间范围内的提现）
            $memberWithdrawAmount = 0;
            if (!empty($memberIds)) {
                $memberWithdrawAmount = WithdrawRecord::whereIn('user_id', $memberIds)
                    ->where('status', 2)
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('amount');
                $memberWithdrawAmount = abs($memberWithdrawAmount);
            }
            $statistics['member_withdraw_amount'] = $memberWithdrawAmount;
            
            // 6. 下级会员佣金金额（下级会员在指定时间范围内获得的佣金）
            $memberCommissionAmount = 0;
            if (!empty($memberIds)) {
                $memberCommissionAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'COMMISSION_ADD')
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('money');
                $memberCommissionAmount = bcdiv($memberCommissionAmount, 100, 2);
            }
            $statistics['member_commission_amount'] = $memberCommissionAmount;
            
            // 7. 下级会员加款金额（代理给会员加款的记录）
            $memberAddAmount = 0;
            if (!empty($memberIds)) {
                $memberAddAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'AGENT_ADD_TO_USER')
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('money');
                $memberAddAmount = bcdiv($memberAddAmount, 100, 2);
            }
            $statistics['memberAddAmount'] = $memberAddAmount;
            
            // 8. 下级会员扣款金额（代理给会员扣款的记录）
            $memberDeductAmount = 0;
            if (!empty($memberIds)) {
                $memberDeductAmount = UserMoneyLog::whereIn('user_id', $memberIds)
                    ->where('type', 'AGENT_DEDUCT_FROM_USER')
                    ->where('create_time', 'between', [$startTime, $endTime])
                    ->sum('money');
                $memberDeductAmount = abs(bcdiv($memberDeductAmount, 100, 2));
            }
            $statistics['member_deduct_amount'] = $memberDeductAmount;
            
        } catch (Exception $e) {
            $this->error('获取统计数据失败：' . $e->getMessage());
        }
            
        $this->success('获取统计数据成功', $statistics);
    }

    /**
     * 获取会员订单列表
     */
    public function memberOrders(): void
    {
        try {
            $agentId = $this->auth->id;
            $params = $this->request->param(['page', 'limit', 'lottery_code', 'status', 'keyword', 'member_id']);
            
            $page = $params['page'] ?? 1;
            $limit = $params['limit'] ?? 10;
            $status = $params['status'] ?? '';
            $lotteryCode = $params['lottery_code'] ?? '';
            $keyword = $params['keyword'] ?? '';
            $memberId = $params['member_id'] ?? '';
            
            // 获取下级会员ID列表
            $memberIds = User::where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->column('id');
            
            if (empty($memberIds)) {
                $this->success('获取订单列表成功', [
                    'data' => [],
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => $page
                ]);
                return;
            }
            
            // 构建查询条件
            $where = [
                ['user_id', 'in', $memberIds]
            ];
            
            // 如果指定了会员ID，只查询该会员的订单
            if (!empty($memberId)) {
                // 验证会员是否属于当前代理商
                if (!in_array($memberId, $memberIds)) {
                    $this->error('会员不存在或不属于您的下级');
                }
                $where = [['user_id', '=', $memberId]];
            }
            
            // 彩种筛选
            if (!empty($lotteryCode)) {
                $where[] = ['lottery_code', '=', $lotteryCode];
            }
            
            // 状态筛选
            if ($status !== '') {
                if($status == 'WINNING'){
                    $where[] = ['status', 'in', ['WINNING', 'PAID']];
                }else{
                    $where[] = ['status', '=', $status];
                }
            }
            
            // 关键词搜索（订单号、彩票名称）
            if (!empty($keyword)) {
                $where[] = ['order_no|period_no', 'like', '%' . $keyword . '%'];
            }
            
            // 查询订单数据
            $orders = BetOrder::where($where)
                ->with(['user','lotteryType']) 
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]
            );
            
            // 转换数据格式，添加状态映射文本
            $list = [];
            foreach ($orders->items() as $order) {
                $item = $order->toArray();
                // 添加状态文本映射
                $item['status_text'] = $order->status_text;
                $item['nickname'] = $order->user->nickname;
                $item['typename'] = $order->lotteryType->type_name;
                $item['typeicon'] = $order->lotteryType->type_icon;
                unset($item['user']);
                unset($item['lotteryType']);
                $list[] = $item;
            }

        } catch (Throwable $e) {
            $this->error('获取订单列表失败：' . $e->getMessage());
        }
            
        $this->success('获取订单列表成功', [
            'data' => $list,
            'total' => $orders->total(),
            'pages' => $orders->lastPage(),
            'current_page' => $page
        ]);
    }

    /**
     * 设置用户资金（加款/减款）
     */
    public function setUserMoney(): void
    {
        try {
            $agentId = $this->auth->id;
            $data = $this->request->post();
            
            // 参数验证
            if (empty($data['member_id'])) {
                throw new Exception('会员ID不能为空');
            }
            
            if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                throw new Exception('金额参数错误');
            }
            
            if (empty($data['type']) || !in_array($data['type'], ['add', 'reduce'])) {
                throw new Exception('操作类型参数错误');
            }
            
            if (empty($data['pay_password'])) {
                throw new Exception('支付密码不能为空');
            }
            
            $memberId = (int)$data['member_id'];
            $amount = (float)$data['amount'];
            $type = $data['type'];
            $remark = $data['remark'] ?? '';
            $payPassword = $data['pay_password'];
            
            // 验证金额
            if ($amount <= 0) {
                throw new Exception('金额必须大于0');
            }
            
            // 验证会员是否属于当前代理商
            $member = User::where('id', $memberId)
                ->where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->find();
                
            if (!$member) {
                throw new Exception('会员不存在或不属于您的下级');
            }
            
            // 验证支付密码
            $agent = User::where('id', $agentId)->find();
            if (empty($agent->pay_password)) {
                throw new Exception('您尚未设置支付密码，请先设置支付密码');
            }
            
            if (!password_verify($payPassword, $agent->pay_password)) {
                throw new Exception('支付密码错误');
            }
            
            // 计算会员可用余额（总余额 - 不可提现金额）
            $memberAvailableBalance = $member->money - $member->unwith_money;
            
            // 验证余额
            if ($type === 'add') {
                // 加款：检查代理商余额是否足够
                if ($this->auth->money < $amount) {
                    throw new Exception('您的余额不足，无法给会员加款');
                }
            } else {
                // 减款：检查会员可用余额是否足够
                if ($memberAvailableBalance < $amount) {
                    throw new Exception('会员可用余额不足，无法减款');
                }
            }
            
            // 开启事务
            Db::startTrans();
            
            try {
                // 调用财务服务处理资金变动
                $financeService = new FinanceService();
                
                if ($type === 'add') {
                    // 加款：代理商扣款，会员加款
                    $financeService->adjustUserBalance(
                        $agentId,
                        -$amount,
                        '给会员：'.$memberId.'加款【'.$remark.'】',
                        'AGENT_ADD_TO_USER'
                    );
                    
                    $financeService->adjustUserBalance(
                        $memberId,
                        $amount,
                        '代理商加款【'.$remark.'】',
                        'AGENT_ADD_TO_USER'
                    );
                    
                    $message = '加款成功';
                } else {
                    // 减款：会员扣款，代理商加款
                    $financeService->adjustUserBalance(
                        $memberId,
                        -$amount,
                        '代理商减款【'.$remark.'】',
                        'AGENT_DEDUCT_FROM_USER'
                    );
                    
                    $financeService->adjustUserBalance(
                        $agentId,
                        $amount,
                        '从会员：'.$memberId.'减款【'.$remark.'】',
                        'AGENT_DEDUCT_FROM_USER'
                    );
                    
                    $message = '减款成功';
                }
                
                // 提交事务
                Db::commit();
                
                // 获取更新后的用户余额
                $updatedMember = User::find($memberId);
                $updatedAgent = User::find($agentId);
                
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error('操作失败：' . $e->getMessage());
        }
                
        $this->success($message, [
            'member_id' => $memberId,
            'new_balance' => $updatedMember->money,
            'agent_balance' => $updatedAgent->money,
            'amount' => $amount,
            'type' => $type
        ]);
    }

    /**
     * 获取用户资金记录
     */
    public function getUserMoneyLog(): void
    {
        try {
            $agentId = $this->auth->id;
            $params = $this->request->param(['member_id', 'page', 'limit', 'type', 'start_date', 'end_date']);
            $memberId = $params['member_id'] ?? null;
            $page = (int)($params['page'] ?? 1);
            $limit = (int)($params['limit'] ?? 15);
            $type = $params['type'] ?? '';
            $startDate = $params['start_date'] ?? '';
            $endDate = $params['end_date'] ?? '';
            
            if (empty($memberId)) {
                $this->error('会员ID不能为空');
            }
            // 验证会员是否属于当前代理商
            $member = User::where('id', $memberId)
                ->where('parent_id', $agentId)
                ->where('is_agent', 0)
                ->find();
                
            if (!$member) {
                $this->error('会员不存在或不属于您的下级');
            }
            
            // 构建查询条件
            $where = [['user_id', '=', $memberId]];
            
            // 类型过滤
            if (!empty($type)) {
                $typeCondition = MoneyLogTypeHelper::buildTypeCondition($type);
                if (!empty($typeCondition)) {
                    $where[] = $typeCondition;
                }
            }
            
            // 日期过滤
            if (!empty($startDate) && !empty($endDate)) {
                $startTime = strtotime($startDate . ' 00:00:00');
                $endTime = strtotime($endDate . ' 23:59:59');
                $where[] = ['create_time', 'between', [$startTime, $endTime]];
            }
            
            // 查询资金记录
            $moneyLogs = UserMoneyLog::where($where)
                ->field('*')
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            $list = $moneyLogs->items();
            
            // 格式化数据
            foreach ($list as &$item) {
                $item['amount'] = $item['money'];
                $item['before'] = $item['before'];
                $item['after'] = $item['after'];
                $item['createtime'] = $item['create_time'];
                
                // 将数据库类型转换为前端类型
                $item['type'] = MoneyLogTypeHelper::dbToFrontend($item['type']);
            }
            
            // 计算统计数据（基于当前筛选条件）
            $statistics = [];
            
            // 计算总金额
            $totalMoneySum = UserMoneyLog::where($where)
                ->sum('money');
            $statistics['total_amount'] = bcdiv($totalMoneySum, 100, 2);
            
            // 计算收入总额（正数）
            $totalIncome = UserMoneyLog::where($where)
                ->where('money', '>', 0)
                ->sum('money');
            $statistics['total_income'] = bcdiv($totalIncome, 100, 2);
            
            // 计算支出总额（负数的绝对值）
            $totalExpense = UserMoneyLog::where($where)
                ->where('money', '<', 0)
                ->sum('money');
            $statistics['total_expense'] = abs(bcdiv($totalExpense, 100, 2));
            
        } catch (Exception $e) {
            $this->error('获取资金记录失败：' . $e->getMessage());
        }            
        $this->success('获取资金记录成功', [
            'data' => $list,
            'total' => $moneyLogs->total(),
            'page' => $page,
            'limit' => $limit,
            'statistics' => $statistics
        ]);
    }

    /**
     * 获取代理商充值赠送配置
     */
    public function getAgentRecharge(): void
    {
        try {
            $agentId = $this->auth->id;
            
            // 使用模型获取充值赠送配置
            $rechargeConfigs = RechargeGift::getAgentConfigs($agentId);
            
            // 格式化数据
            $list = [];
            foreach ($rechargeConfigs as $config) {
                $list[] = [
                    'id' => $config->id,
                    'chargeAmount' => $config->charge_amount,
                    'bonusAmount' => $config->bonus_amount,
                    'updateTime' => date('m-d H:i', $config->update_time),
                    'status' => (int)$config->status
                ];
            }
            
        } catch (Exception $e) {
            $this->error('获取充值配置失败：' . $e->getMessage());
        }
        
        $this->success('获取充值配置成功', [
            'list' => $list
        ]);
    }

    /**
     * 保存代理商充值赠送配置
     */
    public function saveAgentRecharge(): void
    {
        try {
            $agentId = $this->auth->id;
            $data = $this->request->post();
            
            // 参数验证
            if (empty($data['chargeAmount']) || !is_numeric($data['chargeAmount'])) {
                throw new Exception('充值金额参数错误');
            }
            
            if (empty($data['bonusAmount']) || !is_numeric($data['bonusAmount'])) {
                throw new Exception('赠送金额参数错误');
            }
            
            $chargeAmount = (float)$data['chargeAmount'];
            $bonusAmount = (float)$data['bonusAmount'];
            $status = isset($data['status']) ? (int)$data['status'] : 1;
            
            // 验证金额范围
            if ($chargeAmount <= 0 || $bonusAmount <= 0) {
                throw new Exception('充值金额和赠送金额必须大于0');
            }
            
            if ($bonusAmount >= $chargeAmount) {
                throw new Exception('赠送金额不能大于等于充值金额');
            }
            
            if (!empty($data['id'])) {
                // 更新现有配置
                $configId = (int)$data['id'];
                
                // 验证配置是否属于当前代理商
                $existConfig = RechargeGift::where('id', $configId)
                    ->where('agent_id', $agentId)
                    ->find();
                    
                if (!$existConfig) {
                    throw new Exception('配置不存在或无权限修改');
                }
                
                // 检查是否与其他配置重复（排除当前配置）
                $duplicate = RechargeGift::where('agent_id', $agentId)
                    ->where('charge_amount', $chargeAmount)
                    ->where('id', '<>', $configId)
                    ->find();
                    
                if ($duplicate) {
                    throw new Exception('该充值金额的配置已存在');
                }
                
                $existConfig->charge_amount = $chargeAmount;
                $existConfig->bonus_amount = $bonusAmount;
                $existConfig->status = $status;
                $result = $existConfig->save();
                    
                if ($result == false) {
                    throw new Exception('更新配置失败');
                }
            } else {
                // 新增配置
                // 检查是否已存在相同充值金额的配置
                $existConfig = RechargeGift::where('agent_id', $agentId)
                    ->where('charge_amount', $chargeAmount)
                    ->find();
                    
                if ($existConfig) {
                    throw new Exception('该充值金额的配置已存在');
                }
                
                $rechargeGift = new RechargeGift();
                $rechargeGift->agent_id = $agentId;
                $rechargeGift->charge_amount = $chargeAmount;
                $rechargeGift->bonus_amount = $bonusAmount;
                $rechargeGift->status = $status;
                
                $result = $rechargeGift->save();
                
                if (!$result) {
                    throw new Exception('添加配置失败');
                }
            }
            
        } catch (Exception $e) {
            $this->error('保存配置失败：' . $e->getMessage());
        }

        $this->success('配置成功');
    }

    /**
     * 删除代理商充值赠送配置
     */
    public function deleteAgentRecharge(): void
    {
        try {
            $agentId = $this->auth->id;
            $configId = $this->request->param('id');
            
            if (empty($configId)) {
                throw new Exception('配置ID不能为空');
            }
            
            // 验证配置是否属于当前代理商
            $existConfig = RechargeGift::where('id', $configId)
                ->where('agent_id', $agentId)
                ->find();
                
            if (!$existConfig) {
                throw new Exception('配置不存在或无权限删除');
            }
            
            $result = $existConfig->delete();
                
            if (!$result) {
                throw new Exception('删除配置失败');
            }
            
        } catch (Exception $e) {
            $this->error('删除配置失败：' . $e->getMessage());
        }
        
        $this->success('删除配置成功');
    }
    
    /**
     * 验证支付密码
     */
    public function verifyPayPassword(): void
    {
        try {
            $agentId = $this->auth->id;
            $password = $this->request->post('password');
            
            if (empty($password)) {
                throw new Exception('支付密码不能为空');
            }
            
            // 获取代理商信息
            $agent = User::where('id', $agentId)->find();
            
            if (!$agent) {
                throw new Exception('代理商不存在');
            }
            
            // 验证支付密码
            if (empty($agent->pay_password)) {
                throw new Exception('您尚未设置支付密码，请先设置支付密码');
            }
            
            if (!password_verify($password, $agent->pay_password)) {
                throw new Exception('支付密码错误');
            }
            
        } catch (Exception $e) {
            $this->error('验证失败：' . $e->getMessage());
        }
        
        $this->success('验证成功');
    }

    /**
     * 切换代理商充值赠送配置状态
     */
    public function toggleAgentRechargeStatus(): void
    {
        try {
            $agentId = $this->auth->id;
            $configId = $this->request->param('id');
            
            if (empty($configId)) {
                throw new Exception('配置ID不能为空');
            }
            
            // 验证配置是否属于当前代理商
            $existConfig = RechargeGift::where('id', $configId)
                ->where('agent_id', $agentId)
                ->find();
                
            if (!$existConfig) {
                throw new Exception('配置不存在或无权限修改');
            }
            
            // 切换状态
            $newStatus = $existConfig->status == 1 ? 0 : 1;
            $existConfig->status = $newStatus;
            
            $result = $existConfig->save();
                
            if ($result == false) {
                $this->error('状态切换失败');
            }
            
        } catch (Exception $e) {
            $this->error('状态切换失败：' . $e->getMessage());
        }

        $statusText = $newStatus == 1 ? '启用' : '停用';
        $this->success($statusText . '成功', ['status' => $newStatus]);
    }
}