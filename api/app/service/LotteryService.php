<?php

declare(strict_types=1);

namespace app\service;

use app\common\model\LotteryType;
use app\common\model\BetOrder;
use app\common\model\LotteryDraw;
use app\common\model\LotteryTime;
use app\common\model\LotteryBonus;
use think\facade\Db;
use think\facade\Log;
use Exception;

/**
 * 彩票服务类
 */
class LotteryService
{

    /**
     * 获取游戏信息
     * @param string $typeCode 彩种代码
     * @return array
     */
    public function getGameInfo(string $typeCode): array
    {
        try {
            $lotteryType = LotteryType::field('id,type_code,type_name,default_pool,min_bet_amount,max_bet_amount,daily_limit,auto_draw,is_enabled,remark')
                ->where('type_code', $typeCode)
                ->find();
                
            if (!$lotteryType) {
                return ['code' => 0, 'msg' => '彩种不存在'];
            }
            
            return ['code' => 1, 'msg' => '获取成功', 'data' => $lotteryType];
        } catch (Exception $e) {
            Log::error('获取游戏信息失败: ' . $e->getMessage());
            return ['code' => 0, 'msg' => '获取游戏信息失败'];
        }
    }
    
    /**
     * 获取彩种统计
     * @param int $lotteryTypeId 彩种ID
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     */
    public function getLotteryStats(int $lotteryTypeId, string $startDate = '', string $endDate = ''): array
    {
        try {
            $where = [['lottery_type_id', '=', $lotteryTypeId]];
            
            if (!empty($startDate)) {
                $where[] = ['create_time', '>=', strtotime($startDate)];
            }
            if (!empty($endDate)) {
                $where[] = ['create_time', '<=', strtotime($endDate . ' 23:59:59')];
            }
            
            // 订单统计
            $orderStats = BetOrder::where($where)
                ->field([
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_bet_amount',
                    'SUM(CASE WHEN status = "WINNING" THEN win_amount ELSE 0 END) as total_win_amount',
                    'COUNT(CASE WHEN status = "WINNING" THEN 1 END) as win_orders'
                ])
                ->find();
                
            // 开奖统计
            $drawStats = LotteryDraw::getDrawStats($lotteryTypeId, $startDate, $endDate);
            
            return [
                'order_stats' => [
                    'total_orders' => $orderStats->total_orders ?: 0,
                    'total_bet_amount' => number_format(($orderStats->total_bet_amount ?: 0) / 100, 2),
                    'total_win_amount' => number_format(($orderStats->total_win_amount ?: 0) / 100, 2),
                    'win_orders' => $orderStats->win_orders ?: 0,
                    'win_rate' => $orderStats->total_orders > 0 ? round(($orderStats->win_orders / $orderStats->total_orders) * 100, 2) : 0,
                    'profit_amount' => number_format((($orderStats->total_bet_amount ?: 0) - ($orderStats->total_win_amount ?: 0)) / 100, 2)
                ],
                'draw_stats' => $drawStats
            ];
        } catch (Exception $e) {
            Log::error('获取彩种统计失败: ' . $e->getMessage());
            return [
                'order_stats' => [
                    'total_orders' => 0,
                    'total_bet_amount' => '0.00',
                    'total_win_amount' => '0.00',
                    'win_orders' => 0,
                    'win_rate' => 0,
                    'profit_amount' => '0.00'
                ],
                'draw_stats' => []
            ];
        }
    }

        /**
     * 获取当前期号信息
     * @param string $lotteryName 彩种名称【其他彩种】
     * @return array
     */
    public function getCurrentPeriodOther(string $lotteryName = '3d')
    {
        try {
            // 获取当前时间和日期
            $currentTime = date('H:i:s');
            $currentDate = date('Y-m-d');
            
            // 查询今天的彩种信息
            $periodInfo = LotteryTime::where('lottery_name', $lotteryName)
                ->where('draw_date', $currentDate)
                ->where('status', 'active')
                ->find();
            
            // 如果今天没有数据，查询明天的数据（用于跨日处理）
            $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
            $tomorrowPeriodInfo = LotteryTime::where('lottery_name', $lotteryName)
                ->where('draw_date', $tomorrowDate)
                ->where('status', 'active')
                ->find();
            
            if (!$periodInfo) {
                return ['code' => 0, 'msg' => '未找到今日期号信息'];
            }
            
            // 判断当前时间状态和应该使用的期号
            $closingTime = $periodInfo['closing_time'];
            $nextIssueStartTime = $periodInfo['next_issue_start_time'];
            
            $currentTimestamp = strtotime($currentTime);
            $closingTimestamp = strtotime($closingTime);
            $nextIssueTimestamp = strtotime($nextIssueStartTime);
            
            // 确定使用的期号和状态
            $usePeriodInfo = $periodInfo;
            $status = 'normal'; // 正常状态
            
            if ($currentTimestamp >= $nextIssueTimestamp && $tomorrowPeriodInfo) {
                // 在next_issue_start_time之后，使用明天的期号
                $usePeriodInfo = $tomorrowPeriodInfo;
                $status = 'normal';
                $targetDate = $tomorrowDate;
                // 重新计算明天期号的时间戳
                $closingTimestamp = strtotime($tomorrowDate . ' ' . $tomorrowPeriodInfo['closing_time']);
                $nextIssueTimestamp = strtotime($tomorrowDate . ' ' . $tomorrowPeriodInfo['next_issue_start_time']);
            } elseif ($currentTimestamp > $closingTimestamp && $currentTimestamp < $nextIssueTimestamp) {
                // 在closing_time之后，next_issue_start_time之前，使用今天的期号但状态为封盘
                $status = 'closed';
                $targetDate = $currentDate;
            } else {
                // closing_time之前，状态正常
                $status = 'normal';
                $targetDate = $currentDate;
            }
            
            // 生成完整期号：日期+期号 (格式：YYYYMMDD + 期号)
            $dateStr = str_replace('-', '', $targetDate);            
            switch($lotteryName){
                case '3d':
                    $periodNumber = substr($dateStr, 0, 4) . str_pad((string)$usePeriodInfo['current_issue_number'], 3, '0', STR_PAD_LEFT);
                    break;
                case 'pl3':
                    $periodNumber = substr($dateStr, 2, 2) . str_pad((string)$usePeriodInfo['current_issue_number'], 2, '0', STR_PAD_LEFT);
                    break;
            }
            
            // 计算剩余时间（到截止时间的秒数）
            if ($status === 'closed') {
                // 封盘状态，剩余时间为0
                $remainingMinutes = 0;
            } else {
                // 正常状态，使用已经计算好的closingTimestamp
                $remainingMinutes = max(0, $closingTimestamp - $currentTimestamp);
            }
            
            // 获取最近一期开奖号码
            $lastOpen = LotteryDraw::where(['lottery_code' => $lotteryName])
                ->field('period_no,draw_numbers')
                ->order('period_no', 'desc')
                ->find();
            
            $result = [
                'period_number' => $periodNumber, // 期号
                'current_issue_number' => $usePeriodInfo['current_issue_number'], // 当前期数
                'draw_time_start' => $usePeriodInfo['draw_time_start'], // 开奖开始时间
                'draw_time_end' => $usePeriodInfo['draw_time_end'], // 开奖结束时间
                'closing_time' => $usePeriodInfo['closing_time'], // 截止时间
                'remaining_minutes' => $remainingMinutes, // 剩余秒数
                'next_issue_start_time' => $usePeriodInfo['next_issue_start_time'], // 下期开始时间
                'issue_time_interval' => $usePeriodInfo['issue_time_interval'], // 期时间间隔（秒）
                'current_time' => $currentTime, // 当前时间
                'current_date' => $currentDate, // 当前日期
                'lottery_name' => $lotteryName, // 彩种名称
                'last_open_period_no' => $lastOpen['period_no'] ?? '', // 最近一期开奖期号
                'last_open_code' => $lastOpen['draw_numbers'] ?? '', // 最近一期开奖号码
                'status' => $status, // 当前状态：normal-正常，closed-封盘中
                'target_date' => $targetDate, // 目标日期
            ];
            
            return ['code' => 1, 'msg' => '获取成功', 'data' => $result];
            
        } catch (Exception $e) {
            Log::error('获取期号信息失败: ' . $e->getMessage());
            return ['code' => 0, 'msg' => '获取期号信息失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 获取当前期号信息
     * @param string $lotteryName 彩种名称
     * @return array
     */
    public function getCurrentPeriod(string $lotteryName = 'ff3d'): array
    {
        try {
            // 获取当前时间
            $currentTime = date('H:i:s');
            $currentDate = date('Y-m-d');
            
            // 查询当前时间对应的期号信息
            $periodInfo = LotteryTime::where('lottery_name', $lotteryName)
                ->where('draw_time_start', '<=', $currentTime)
                ->where('draw_time_end', '>=', $currentTime)
                ->where('status', 'active')
                ->find();
            
            if (!$periodInfo) {
                // 如果当前时间没有匹配的期号，查找下一期
                $periodInfo = LotteryTime::where('lottery_name', $lotteryName)
                    ->where('draw_time_start', '>', $currentTime)
                    ->where('status', 'active')
                    ->order('current_issue_number', 'asc')
                    ->find();
            }
            
            if (!$periodInfo) {
                return ['code' => 0, 'msg' => '未找到期号信息'];
            }
            
            // 生成期号格式：年月日+期数（如2507140001）
            $periodNumber = $this->buildPeriodNumber($periodInfo['current_issue_number'], $lotteryName);
            
            // 计算截止时间
            $closingTime = $periodInfo['closing_time'];
            $drawTimeStart = $periodInfo['draw_time_start'];
            $drawTimeEnd = $periodInfo['draw_time_end'];
            
            // 计算剩余时间（分钟）
            $currentTimestamp = strtotime($currentTime);
            $closingTimestamp = strtotime($closingTime);
            
            // 如果截止时间小于当前时间，说明是第二天的时间
            if ($closingTimestamp < $currentTimestamp) {
                $closingTimestamp += 24 * 60 * 60; // 加一天
            }
            
            $remainingMinutes = max(0, floor(($closingTimestamp - $currentTimestamp)));

            //获取最近一期开奖号码
            $lastOpen = LotteryDraw::where(['lottery_code'=>$lotteryName])->field('period_no,draw_numbers')->order('period_no', 'desc')->find();
            
            // 判断当前状态
            $status = 'normal'; // 默认正常状态
            if ($remainingMinutes <= 0) {
                $status = 'closed'; // 封盘状态
            }
            
            $result = [
                'period_number' => $periodNumber, // 期号
                'current_issue_number' => $periodInfo['current_issue_number'], // 当前期数
                'draw_time_start' => $drawTimeStart, // 开奖开始时间
                'draw_time_end' => $drawTimeEnd, // 开奖结束时间
                'closing_time' => $closingTime, // 截止时间
                'remaining_minutes' => $remainingMinutes, // 剩余秒数
                'next_issue_start_time' => $periodInfo['next_issue_start_time'], // 下期开始时间
                'issue_time_interval' => $periodInfo['issue_time_interval'], // 期时间间隔（秒）
                'current_time' => $currentTime, // 当前时间
                'current_date' => $currentDate, // 当前日期
                'lottery_name' => $lotteryName, // 彩种名称
                'last_open_period_no' => $lastOpen['period_no'] ?? '', // 最近一期开奖期号
                'last_open_code' => $lastOpen['draw_numbers'] ?? '', // 最近一期开奖号码
                'status' => $status, // 当前状态：normal-正常，closed-封盘中
            ];
            
            return ['code' => 1, 'msg' => '获取成功', 'data' => $result];
            
        } catch (Exception $e) {
            Log::error('获取期号信息失败: ' . $e->getMessage());
            return ['code' => 0, 'msg' => '获取期号信息失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 获取上一期期号信息
     * @param string $lotteryName 彩种名称，默认为ff3d
     * @return array
     */
    public function getPreviousPeriod(string $lotteryName = 'ff3d'): array
    {
        try {
            // 先获取当前期号信息
            $currentPeriodResult = $this->getCurrentPeriod($lotteryName);
            
            if ($currentPeriodResult['code'] != 1) {
                return $currentPeriodResult;
            }
            
            $currentIssueNumber = $currentPeriodResult['data']['current_issue_number'];
            $previousIssueNumber = $currentIssueNumber - 1;
            
            // 如果上一期是0，需要处理跨日情况
            if ($previousIssueNumber <= 0) {
                // 获取昨天的最后一期
                $yesterday = date('ymd', strtotime('-1 day'));
                
                // 查询昨天的最大期数
                $maxIssue = Db::name('lottery_time')
                    ->where('lottery_name', $lotteryName)
                    ->where('status', 'active')
                    ->max('current_issue_number');
                
                $previousIssueNumber = $maxIssue ?: 1440; // 默认1440期（每分钟一期）
                $dateStr = $yesterday;
            } else {
                $dateStr = date('ymd');
            }
            
            // 生成上一期期号
            $previousPeriodNumber = $this->buildPeriodNumber($previousIssueNumber, $lotteryName, $dateStr);
            
            // 查询上一期的时间信息
            $periodInfo = Db::name('lottery_time')
                ->where('lottery_name', $lotteryName)
                ->where('current_issue_number', $previousIssueNumber)
                ->where('status', 'active')
                ->find();
            
            if (!$periodInfo) {
                return ['code' => 0, 'msg' => '未找到上一期期号信息'];
            }
            
            $result = [
                'period_number' => $previousPeriodNumber,
                'current_issue_number' => $previousIssueNumber,
                'draw_time_start' => $periodInfo['draw_time_start'],
                'draw_time_end' => $periodInfo['draw_time_end'],
                'closing_time' => $periodInfo['closing_time'],
                'lottery_name' => $lotteryName
            ];
            
            return ['code' => 1, 'msg' => '获取成功', 'data' => $result];
            
        } catch (Exception $e) {
            Log::error('获取上一期期号信息失败: ' . $e->getMessage());
            return ['code' => 0, 'msg' => '获取上一期期号信息失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 验证期号是否有效
     * @param string $periodNumber 期号
     * @param string $lotteryName 彩种名称
     * @return array
     */
    public function validatePeriod(string $periodNumber, string $lotteryName = 'ff3d'): array
    {
        $lotteryTypeCategory = LotteryType::where('type_code', $lotteryName)->value('category');
        if($lotteryTypeCategory == 'QUICK'){
            $currentPeriodResult = $this->getCurrentPeriod($lotteryName);
        }else{
            $currentPeriodResult = $this->getCurrentPeriodOther($lotteryName);
        }
        
        if ($currentPeriodResult['code'] != 1) {
            return $currentPeriodResult;
        }
        
        $currentPeriod = $currentPeriodResult['data']['period_number'];
        
        if ($periodNumber !== $currentPeriod) {
            return ['code' => 0, 'msg' => '期号已过期，当前期号为：' . $currentPeriod];
        }
        
        // 检查是否还在投注时间内
        $remainingMinutes = $currentPeriodResult['data']['remaining_minutes'];
        
        if ($remainingMinutes <= 0) {
            return ['code' => 0, 'msg' => '当前期号已截止投注'];
        }
        
        return ['code' => 1, 'msg' => '期号验证通过', 'data' => $currentPeriodResult['data']];
    }

    /**
     * 根据彩种代码返回彩种ID
     * @param string $typeCode 彩种代码
     * @return int|null 彩种ID
     */
    public function getLotteryTypeId(string $typeCode): ?int
    {
        try {
            return LotteryType::where('type_code', $typeCode)->value('id');
        } catch (Exception $e) {
            Log::error('获取彩种ID失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 获取期号补零位数
     * @param string $lotteryName 彩种名称
     * @return int
     */
    private function getPeriodPadLength(string $lotteryName): int
    {
        $padLengthMap = [
            'ff3d' => 4,
            '5f3d' => 3,
            '30f3d' => 2,
            '2s3d' => 2,
            '12s3d' => 1,
        ];
        
        return $padLengthMap[$lotteryName] ?? 4;
    }
    
    /**
     * 构建期号
     * @param int $issueNumber 期数
     * @param string $lotteryName 彩种名称
     * @param string|null $dateStr 日期字符串
     * @return string
     */
    private function buildPeriodNumber(int $issueNumber, string $lotteryName, ?string $dateStr = null): string
    {
        $dateStr = $dateStr ?: date('ymd');
        $padLength = $this->getPeriodPadLength($lotteryName);
        $issueNumberStr = str_pad((string)$issueNumber, $padLength, '0', STR_PAD_LEFT);
        
        return $dateStr . $issueNumberStr;
    }
    
    /**
     * 手动开奖
     * @param string $lotteryCode 彩种代码
     * @param string $periodNo 期号
     * @param string $drawNumbers 开奖号码
     * @param array $options 选项
     * @return bool
     */
    public function manualDraw(string $lotteryCode, string $periodNo, string $drawNumbers, array $options = []): bool
    {
        try {
            // 检查是否已存在开奖结果
            $exists = LotteryDraw::where('lottery_code', $lotteryCode)
                ->where('period_no', $periodNo)
                ->find();
            
            if ($exists) {
                throw new Exception('该期号已存在开奖结果');
            }
            
            // 获取彩种信息
            $lotteryType = LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lotteryType) {
                throw new Exception('彩种不存在');
            }
            
            // 创建开奖记录
            $drawData = [
                'lottery_type_id' => $lotteryType->id,
                'lottery_code' => $lotteryCode,
                'period_no' => $periodNo,
                'draw_numbers' => $drawNumbers,
                'draw_time' => $options['draw_time'] ?? time(),
                'status' => LotteryDraw::STATUS_DRAWN,
                'is_official' => 0, // 手动开奖标记为非官方
                'remark' => $options['remark'] ?? '手动开奖',
                'created_by' => $options['created_by'] ?? 0
            ];
            
            $draw = LotteryDraw::create($drawData);
            
            if ($draw) {
                Log::info('手动开奖成功', [
                    'lottery_code' => $lotteryCode,
                    'period_no' => $periodNo,
                    'draw_numbers' => $drawNumbers
                ]);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('手动开奖失败: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取开奖统计
     * @param array $params 查询参数
     * @return array
     */
    public function getDrawStatistics(array $params = []): array
    {
        try {
            $startTime = $params['start_time'] ?? '';
            $endTime = $params['end_time'] ?? '';
            $lotteryCode = $params['lottery_code'] ?? '';
            $status = $params['status'] ?? '';
            $groupBy = $params['group_by'] ?? 'day';
            
            $query = LotteryDraw::alias('ld')
                ->leftJoin('lottery_type lt', 'ld.lottery_type_id = lt.id');
            
            // 时间条件
            if ($startTime) {
                $query->where('ld.draw_time', '>=', strtotime($startTime));
            }
            if ($endTime) {
                $query->where('ld.draw_time', '<=', strtotime($endTime . ' 23:59:59'));
            }
            
            // 彩种条件
            if ($lotteryCode) {
                $query->where('ld.lottery_code', $lotteryCode);
            }
            
            // 状态条件
            if ($status) {
                $query->where('ld.status', $status);
            }
            
            // 分组统计
            $dateFormat = match($groupBy) {
                'hour' => '%Y-%m-%d %H:00:00',
                'month' => '%Y-%m-01',
                default => '%Y-%m-%d'
            };
            
            $stats = $query
                ->field([
                    "DATE_FORMAT(FROM_UNIXTIME(ld.draw_time), '{$dateFormat}') as date_group",
                    'COUNT(*) as total_draws',
                    'COUNT(CASE WHEN ld.status = "DRAWN" THEN 1 END) as drawn_count',
                    'COUNT(CASE WHEN ld.status = "SETTLED" THEN 1 END) as settled_count',
                    'ld.lottery_code',
                    'lt.type_name'
                ])
                ->group('date_group, ld.lottery_code')
                ->order('date_group DESC')
                ->select()
                ->toArray();
            
            // 汇总统计
            $summary = $query
                ->field([
                    'COUNT(*) as total_draws',
                    'COUNT(CASE WHEN ld.status = "DRAWN" THEN 1 END) as total_drawn',
                    'COUNT(CASE WHEN ld.status = "SETTLED" THEN 1 END) as total_settled',
                    'COUNT(CASE WHEN ld.status = "PENDING" THEN 1 END) as total_pending'
                ])
                ->find();
            
            return [
                'stats' => $stats,
                'summary' => $summary ? $summary->toArray() : [],
                'group_by' => $groupBy
            ];
        } catch (Exception $e) {
            Log::error('获取开奖统计失败: ' . $e->getMessage());
            return [
                'stats' => [],
                'summary' => [],
                'group_by' => $groupBy
            ];
        }
     }
     
     /**
      * 获取游戏赔率
      * @param string $lotteryCode 彩种代码
      * @param string $betType 投注类型
      * @return array
      */
     public static function getGameOdds(string $lotteryCode, string $betType = ''): array
     {
         try {
             // 根据彩种代码获取彩种ID
             $lotteryType = LotteryType::where('type_code', $lotteryCode)->find();
             if (!$lotteryType) {
                 return ['code' => 0, 'msg' => '彩种不存在'];
             }
             
             $query = LotteryBonus::where('lottery_id', $lotteryType->id)
                 ->where('status', 1);
             
             // 如果指定了投注类型，则只查询该类型
             if (!empty($betType)) {
                 $query->where('type_key', $betType);
             }
             
             $bonusList = $query->order('weigh', 'desc')->select();
             
             if (empty($bonusList)) {
                 return ['code' => 0, 'msg' => '未找到赔率配置'];
             }
             
             $result = [];
             
             foreach ($bonusList as $bonus) {
                 $bonusJson = $bonus['bonus_json'];
                     
                // 处理奖金显示
                $bonusDisplay = '';
                if (is_array($bonusJson) && !empty($bonusJson)) {
                    // 检查是否为关联数组（对象格式）
                    if (array_keys($bonusJson) !== range(0, count($bonusJson) - 1)) {
                        // 关联数组（对象格式），如组三复式、组六复式
                        $bonusValues = array_values($bonusJson);
                        if (count($bonusValues) == 1) {
                            $bonusDisplay = '奖金：' . $bonusValues[0];
                        } else {
                            $minBonus = min($bonusValues);
                            $maxBonus = max($bonusValues);
                            $bonusDisplay = '奖金：' . $minBonus . '-' . $maxBonus;
                        }
                    } else {
                        // 索引数组（普通数组格式）
                        if (count($bonusJson) == 1) {
                            // 只有一个奖金值
                            $bonusDisplay = '奖金：' . $bonusJson[0];
                        } else {
                            // 多个奖金值，显示区间
                            $minBonus = min($bonusJson);
                            $maxBonus = max($bonusJson);
                            $bonusDisplay = '奖金：' . $minBonus . '-' . $maxBonus;
                        }
                    }
                }
                
                $result[] = [
                    'id' => $bonus['id'],
                    'lottery_id' => $bonus['lottery_id'],
                    'type_name' => $bonus['type_name'],
                    'type_key' => $bonus['type_key'],
                    'min_price' => $bonus['min_price'],
                    'max_price' => $bonus['max_price'],
                    'bonus_json' => $bonusJson,
                    'bonus_display' => $bonusDisplay,
                    'weigh' => $bonus['weigh']
                ];
             }
             
             return ['code' => 1, 'msg' => '获取成功', 'data' => $result];
         } catch (Exception $e) {
             Log::error('获取游戏赔率失败: ' . $e->getMessage());
             return ['code' => 0, 'msg' => '获取游戏赔率失败：' . $e->getMessage()];
         }
     }
}