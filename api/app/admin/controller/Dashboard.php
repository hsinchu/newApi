<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\BetOrder;
use app\common\model\LotteryType;
use app\common\model\LotteryDraw;
use think\db\Query;

class Dashboard extends Backend
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function index(): void
    {
        $this->success('', [
            'remark' => get_route_remark()
        ]);
    }

    public function data(): void
    {
        // 获取统计数据
        $statistics = [
            'userRegistrations' => $this->getUserRegistrations(),  // 用户注册数
            'betAmount' => $this->getBetAmount(),                  // 会员投注量
            'totalUsers' => $this->getTotalUsers(),                // 用户总数
            'lotteryTypes' => $this->getLotteryTypes()             // 已开启彩种数
        ];
        
        // 获取图表数据
        $chartData = [
            // 会员增长数据（最近7天）
            'userGrowth' => $this->getUserGrowthData(),
            
            // 投注增长数据（最近7天）
            'betGrowth' => $this->getBetGrowthData(),
            
            // 雷达图数据（各投注类型）
            'radarData' => $this->getRadarData(),
            
            // 各彩种投注数据
            'lotteryBetData' => $this->getLotteryBetData(),
            
            // 各彩种中奖情况
            'lotteryWinData' => $this->getLotteryWinData()
        ];
        
        // 新会员列表（最近5个）
        $newMembers = $this->getNewMembers();
        
        $this->success('获取数据成功', [
            'statistics' => $statistics,
            'chartData' => $chartData,
            'newMembers' => $newMembers
        ]);
    }

    /**
     * 获取用户注册数（今日）
     */
    private function getUserRegistrations(): int
    {
        return User::whereTime('create_time', 'today')->count();
    }

    /**
     * 获取投注总金额（今日，单位：元）
     */
    private function getBetAmount(): float
    {
        $amount = BetOrder::whereTime('create_time', 'today')->sum('total_amount');
        return round($amount / 100, 2); // 分转元
    }

    /**
     * 获取用户总数
     */
    private function getTotalUsers(): int
    {
        return User::count();
    }

    /**
     * 获取已开启彩种数
     */
    private function getLotteryTypes(): int
    {
        return LotteryType::where('is_enabled', 1)->count();
    }

    /**
     * 获取会员增长数据（最近7天）
     */
    private function getUserGrowthData(): array
    {
        $dates = [];
        $visits = [];
        $registrations = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayName = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][date('w', strtotime($date))];
            
            $dates[] = $dayName;
            // 这里用注册数模拟访问量，实际项目中可以有专门的访问统计表
            $dayRegistrations = User::whereTime('create_time', $date)->count();
            $visits[] = $dayRegistrations * 2; // 假设访问量是注册量的2倍
            $registrations[] = $dayRegistrations;
        }
        
        return [
            'dates' => $dates,
            'visits' => $visits,
            'registrations' => $registrations
        ];
    }

    /**
     * 获取投注增长数据（最近7天）
     */
    private function getBetGrowthData(): array
    {
        $dates = [];
        $amounts = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayName = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'][date('w', strtotime($date))];
            
            $dates[] = $dayName;
            $dayAmount = BetOrder::whereTime('create_time', $date)->sum('total_amount');
            $amounts[] = round($dayAmount / 100, 2); // 分转元
        }
        
        return [
            'dates' => $dates,
            'amounts' => $amounts
        ];
    }

    /**
     * 获取雷达图数据（各投注类型）
     */
    private function getRadarData(): array
    {
        // 获取各彩种的投注统计
        $lotteryStats = LotteryType::where('is_enabled', 1)
            ->field('id,type_name')
            ->select()
            ->toArray();
        
        $indicators = [];
        $data = [];
        
        foreach ($lotteryStats as $lottery) {
            $betCount = BetOrder::where('lottery_type_id', $lottery['id'])
                ->whereTime('create_time', 'month')
                ->count();
            
            $indicators[] = ['name' => $lottery['type_name'], 'max' => 100];
            // 将投注数量转换为百分比（假设最大值为当前最大投注数）
            $data[] = min(100, $betCount);
        }
        
        // 如果没有数据，返回默认数据
        if (empty($indicators)) {
            // 动态获取彩种分类数据
            $categories = LotteryType::where('is_enabled', 1)
                ->field('category')
                ->group('category')
                ->select()
                ->toArray();
            
            $categoryNames = [
                'WELFARE' => '福彩',
                'SPORTS' => '体彩', 
                'QUICK' => '快彩',
                'SPORTS_SINGLE' => '竞彩'
            ];
            
            foreach ($categories as $cat) {
                $name = $categoryNames[$cat['category']] ?? '其他';
                $indicators[] = ['name' => $name, 'max' => 100];
                $data[] = 0;
            }
            
            // 如果仍然没有数据，使用最基本的默认值
            if (empty($indicators)) {
                $indicators = [['name' => '暂无彩种', 'max' => 100]];
                $data = [0];
            }
        }
        
        return [
            'indicators' => $indicators,
            'data' => $data
        ];
    }

    /**
     * 获取各彩种投注数据
     */
    private function getLotteryBetData(): array
    {
        $result = BetOrder::alias('bo')
            ->join('lottery_type lt', 'bo.lottery_type_id = lt.id')
            ->field('lt.type_name as name, SUM(bo.total_amount) as value')
            ->where('lt.is_enabled', 1)
            ->group('bo.lottery_type_id')
            ->order('value desc')
            ->limit(10)
            ->select()
            ->toArray();
        
        // 分转元
        foreach ($result as &$item) {
            $item['value'] = round($item['value'] / 100, 2);
        }
        
        // 如果没有投注数据，动态获取彩种作为默认数据
        if (empty($result)) {
            $defaultLotteries = LotteryType::where('is_enabled', 1)
                ->field('type_name as name')
                ->order('sort_order desc')
                ->limit(5)
                ->select()
                ->toArray();
            
            foreach ($defaultLotteries as &$item) {
                $item['value'] = 0;
            }
            
            return $defaultLotteries ?: [['name' => '暂无彩种', 'value' => 0]];
        }
        
        return $result;
    }

    /**
     * 获取各彩种中奖情况（中奖金额）
     */
    private function getLotteryWinData(): array
    {
        $result = BetOrder::alias('bo')
            ->join('lottery_type lt', 'bo.lottery_type_id = lt.id')
            ->field('lt.type_name as name, SUM(bo.win_amount) as value')
            ->where('lt.is_enabled', 1)
            ->where('bo.status', 'in', [BetOrder::STATUS_WINNING, BetOrder::STATUS_PAID])
            ->group('bo.lottery_type_id')
            ->order('value desc')
            ->limit(10)
            ->select()
            ->toArray();
        
        // 分转元
        foreach ($result as &$item) {
            $item['value'] = round($item['value']/100, 2);
        }
        
        // 如果没有中奖数据，动态获取彩种作为默认数据
        if (empty($result)) {
            $defaultLotteries = LotteryType::where('is_enabled', 1)
                ->field('type_name as name')
                ->order('sort_order desc')
                ->limit(5)
                ->select()
                ->toArray();
            
            foreach ($defaultLotteries as &$item) {
                $item['value'] = 0;
            }
            
            return $defaultLotteries ?: [['name' => '暂无彩种', 'value' => 0]];
        }
        
        return $result;
    }

    /**
     * 获取新会员列表（最近5个）
     */
    private function getNewMembers()
    {
        $users = User::where('id', '>', 0)
            ->order('create_time desc')
            ->limit(5)
            ->select()
            ->toArray();
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar'] ?: '/static/images/avatar.png',
                'joinTime' => date('Y-m-d H:i:s', $user['create_time'])
            ];
        }
        
        return $result;
    }
}