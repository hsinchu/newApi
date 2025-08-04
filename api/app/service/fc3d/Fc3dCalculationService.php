<?php

namespace app\service\fc3d;

use app\common\model\LotteryBonus;
use app\common\model\LotteryType;
use app\service\fc3d\Fc3dValidationService;

/**
 * 福彩3D统一计算服务
 * 整合所有玩法的奖金计算功能
 */
class Fc3dCalculationService extends BaseService
{
    /**
     * 奖金配置
     */
    private $prizeConfig = [
        // 直选类奖金
        'zhixuan_fushi' => 1040,
        'zhixuan_danshi' => 1040,
        'zhixuan_hezhi' => 1040,
        'zhixuan_kuadu' => 1040,
        
        // 组选类奖金
        'zusan_fushi' => 346,
        'zusan_danshi' => 346,
        'zuliu_fushi' => 173,
        'zuliu_danshi' => 173,
        
        // 定位类奖金
        'zuxuan_yima_dingwei' => 104,
        'zuxuan_liangma_dingwei' => 104,
        'zuxuan_yima_budingwei' => 104,
        
        // 其他类奖金
        'hezhi' => 173,
        'daxiaohe' => 208,
        
        // 形态类奖金
        'hezhi_daxiao' => 208,
        'hezhi_danshuang' => 208
    ];
    
    /**
     * 根据玩法类型计算奖金
     * 
     * @param string $betType 投注类型
     * @param array $betContent 投注内容
     * @param array $drawNumbers 开奖号码
     * @param int $betAmount 单注金额（分）
     * @param int $multiple 倍数
     * @param array $order 订单信息（可选）
     * @return array 返回奖金计算结果
     */
    public function calculateWinAmount($betType, $betContent, $drawNumbers, $betAmount = 200, $multiple = 1, $order = [])
    {
        try {
            // 首先验证是否中奖
            $validationService = new Fc3dValidationService();
            $winValidation = $validationService->checkWin($betType, $betContent, $drawNumbers);
            if (!$winValidation['is_win']) {
                return [
                    'success' => true,
                    'data' => [
                        'bet_type' => $betType,
                        'win_count' => 0,
                        'bet_amount' => $betAmount,
                        'prize_per_bet' => 0,
                        'total_prize' => 0,
                        'win_amount' => 0
                    ]
                ];
            }
            
            $winCount = $winValidation['win_count'] ?? 1;
            
            // 获取单注奖金
            $lotteryId = $order['lottery_type_id'] ?? null;
            $prizePerBet = $this->getPrizePerBet($betType, $lotteryId, $betContent, $betAmount, $drawNumbers);
            
            if ($prizePerBet === false) {
                return $this->errorResult('不支持的投注类型: ' . $betType);
            }
            
            // 计算总奖金
            $totalPrize = $winCount * $prizePerBet * $multiple;
            
            return [
                'success' => true,
                'data' => [
                    'bet_type' => $betType,
                    'win_count' => $winCount,
                    'bet_amount' => $betAmount,
                    'prize_per_bet' => $prizePerBet,
                    'total_prize' => $totalPrize,
                    'win_amount' => $totalPrize  // AutoPaid.php 期望的字段
                ]
            ];
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * 获取单注奖金
     * 
     * @param string $betType 投注类型
     * @param int $lotteryId 彩种ID（可选，默认为福彩3D）
     * @param array $betContent 投注内容（用于特殊玩法的奖金计算）
     * @param int $betAmount 投注金额（分）
     * @return int|false 单注奖金（分）或false
     */
    public function getPrizePerBet($betType, $lotteryId = null, $betContent = [], $betAmount = 200, $drawNumbers = '')
    {
        try {
            
            // 从数据库获取奖金配置
            $bonusRecord = LotteryBonus::where('lottery_id', $lotteryId)
                ->where('type_key', $betType)
                ->where('status', 1)
                ->find();
                
            // 如果没有找到奖金配置，直接返回false
            if (!$bonusRecord) {
                return false;
            }
                
            $bonusJson = $bonusRecord->bonus_json;
            $bonusType = $bonusRecord->bonus_type ?? 1;
            
            // 根据bonus_type确定奖金计算方式
            if ($bonusType == 1) {
                // 固定奖金模式
                return $this->calculateFixedBonus($bonusJson, $betType, $betContent, $drawNumbers);
            } else if ($bonusType == 2) {
                // 倍数模式：投注金额 * bonus
                return $this->calculateMultiplierBonus($bonusJson, $betType, $betContent, $betAmount);
            }
            
            return false;
            
        } catch (\Exception $e) {
            // 获取不到奖金配置时直接返回false
            return false;
        }
    }
    
    /**
     * 设置奖金配置
     * 
     * @param array $config 奖金配置
     * @return void
     */
    public function setPrizeConfig($config)
    {
        if (is_array($config)) {
            $this->prizeConfig = array_merge($this->prizeConfig, $config);
        }
    }
    
    /**
     * 获取奖金配置
     * 
     * @return array
     */
    public function getPrizeConfig()
    {
        return $this->prizeConfig;
    }
    
    /**
     * 计算固定奖金模式的奖金
     * 
     * @param mixed $bonusJson 奖金配置JSON
     * @param string $betType 投注类型
     * @param array $betContent 投注内容
     * @return int 奖金（分）
     */
    private function calculateFixedBonus($bonusJson, $betType, $betContent = [], $drawNumbers = '')
    {
        if (is_string($bonusJson)) {
            $bonusData = json_decode($bonusJson, true);
        } else {
            $bonusData = $bonusJson;
        }
        
        if (!$bonusData) {
            return 0;
        }
        
        // 对于直选和值、直选跨度等特殊玩法
        if (in_array($betType, ['zhixuan_hezhi', 'zhixuan_kuadu'])) {
            return $this->getSpecialPlayBonus($bonusData, $betContent, $drawNumbers);
        }
        
        // 对于组三复式、组六复式等按选择数量计算的玩法
        if (in_array($betType, ['zusan_fushi', 'zuliu_fushi'])) {
            return $this->getGroupPlayBonus($bonusData, $betContent);
        }
        
        // 默认情况：如果是数组取第一个值，如果是单个值直接返回
        if (is_array($bonusData)) {

            return isset($bonusData[0]) ? (float)$bonusData[0] : 0;
        }
        
        return (float)$bonusData;
    }
    
    /**
     * 计算倍数模式的奖金
     * 
     * @param mixed $bonusJson 奖金配置JSON
     * @param string $betType 投注类型
     * @param array $betContent 投注内容
     * @param int $betAmount 投注金额（分）
     * @return int 奖金（分）
     */
    private function calculateMultiplierBonus($bonusJson, $betType, $betContent = [], $betAmount = 200)
    {
        if (is_string($bonusJson)) {
            $bonusData = json_decode($bonusJson, true);
        } else {
            $bonusData = $bonusJson;
        }
        
        if (!$bonusData) {
            return 0;
        }
        
        // 对于大小和等选项类玩法
        if ($betType === 'daxiaohe') {
            $multiplier = $this->getOptionMultiplier($bonusData, $betContent);
            return (float)($betAmount * $multiplier);
        }
        
        // 默认情况：如果是数组取第一个值，如果是单个值直接使用
        $multiplier = 1.0;
        if (is_array($bonusData)) {
            $multiplier = isset($bonusData[0]) ? (float)$bonusData[0] : 1.0;
        } else {
            $multiplier = (float)$bonusData;
        }
        
        return (float)($betAmount * $multiplier);
    }
    
    /**
     * 获取特殊玩法的奖金（如直选和值、直选跨度）
     * 
     * @param array $bonusData 奖金数据
     * @param array $betContent 投注内容
     * @param string $drawNumbers 开奖号码
     * @return int 奖金（分）
     */
    private function getSpecialPlayBonus($bonusData, $betContent = [], $drawNumbers = '')
    {
        if (empty($drawNumbers)) {
            return 0;
        }
        
        // 解析开奖号码
        $numbers = explode(',', $drawNumbers);
        if (count($numbers) !== 3) {
            return 0;
        }
        
        switch($betContent['type_key']){
            case 'zhixuan_hezhi':
                // 计算和值
                $sum = array_sum($numbers);
                if (isset($bonusData[$sum])) {
                    return (float)$bonusData[$sum];
                }
                break;
            case 'zhixuan_kuadu':
                // 计算跨度
                $span = max($numbers) - min($numbers);
                if (isset($bonusData[$span])) {
                    return (float)$bonusData[$span];
                }
                break;
        }
        
        return 0;
    }
    
    /**
     * 获取组选玩法的奖金（如组三复式、组六复式）
     * 
     * @param array $bonusData 奖金数据
     * @param array $betContent 投注内容
     * @return int 奖金（分）
     */
    private function getGroupPlayBonus($bonusData, $betContent = [])
    {
        // 根据选择的数字个数获取奖金
        if (isset($betContent['numbers'])) {
            $count = count($betContent['numbers']['selected']);
            if (isset($bonusData[$count])) {
                return (float)$bonusData[$count];
            }
        }
        
        return 0;
    }
    
    /**
     * 获取选项类玩法的奖金（如大小和）
     * 
     * @param array $bonusData 奖金数据
     * @param array $betContent 投注内容
     * @return int 奖金（分）
     */
    private function getOptionPlayBonus($bonusData, $betContent = [])
    {
        if (isset($betContent['numbers'])) {
            $option = $betContent['numbers'];
            if (isset($bonusData[$option])) {
                return (float)$bonusData[$option];
            }
        }
        
        // 默认返回第一个奖金值
        $values = array_values($bonusData);
        return isset($values[0]) ? (float)$values[0] : 0;
    }
    
    /**
     * 获取选项类玩法的倍数（如大小和）
     * 
     * @param array $bonusData 奖金数据
     * @param array $betContent 投注内容
     * @return float 倍数
     */
    private function getOptionMultiplier($bonusData, $betContent = [])
    {
        if (isset($betContent['numbers'])) {
            $option = $betContent['numbers'];
            if (isset($bonusData[$option])) {
                return (float)$bonusData[$option];
            }
        }
        
        // 默认返回第一个倍数值
        $values = array_values($bonusData);
        return isset($values[0]) ? (float)$values[0] : 1.0;
    }
    
    /**
     * 计算投注成本
     * 
     * @param string $betType 投注类型
     * @param mixed $betNumbers 投注号码
     * @param int $betAmount 单注金额（分）
     * @param int $multiple 倍数
     * @return array 返回投注成本计算结果
     */
    public function calculateBetCost($betType, $betNumbers, $betAmount = 200, $multiple = 1)
    {
        try {
            // 使用验证服务计算注数
            $validationService = new Fc3dValidationService();
            $betCount = $validationService->calculateBetCount($betType, $betNumbers);
            
            if ($betCount <= 0) {
                return $this->errorResult('无效的投注号码或投注类型');
            }
            
            // 计算总成本
            $totalCost = $betCount * $betAmount * $multiple;
            
            return [
                'success' => true,
                'data' => [
                    'bet_type' => $betType,
                    'bet_count' => $betCount,
                    'bet_amount' => $betAmount,
                    'multiple' => $multiple,
                    'total_cost' => $totalCost
                ]
            ];
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * 计算中奖概率
     * 
     * @param string $betType 投注类型
     * @param mixed $betNumbers 投注号码
     * @return array 返回中奖概率计算结果
     */
    public function calculateWinProbability($betType, $betNumbers)
    {
        try {
            // 使用验证服务计算注数
            $validationService = new Fc3dValidationService();
            $betCount = $validationService->calculateBetCount($betType, $betNumbers);
            
            if ($betCount <= 0) {
                return $this->errorResult('无效的投注号码或投注类型');
            }
            
            // 获取玩法的总可能组合数
            $totalCombinations = $this->getTotalCombinations($betType);
            
            if ($totalCombinations === false) {
                return $this->errorResult('无法计算该玩法的中奖概率');
            }
            
            // 计算中奖概率
            $winProbability = $betCount / $totalCombinations;
            $winProbabilityPercent = $winProbability * 100;
            
            return [
                'success' => true,
                'data' => [
                    'bet_type' => $betType,
                    'bet_count' => $betCount,
                    'total_combinations' => $totalCombinations,
                    'win_probability' => $winProbability,
                    'win_probability_percent' => $winProbabilityPercent,
                    'odds' => '1:' . number_format($totalCombinations / $betCount, 2)
                ]
            ];
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * 获取玩法的总可能组合数
     * 
     * @param string $betType 投注类型
     * @return int|false 总组合数或false
     */
    private function getTotalCombinations($betType)
    {
        $combinations = [
            // 直选类：1000种组合（000-999）
            'zhixuan_fushi' => 1000,
            'zhixuan_danshi' => 1000,
            'zhixuan_hezhi' => 1000,
            'zhixuan_kuadu' => 1000,
            
            // 组三：270种组合（每个数字出现2次的组合）
            'zusan_fushi' => 270,
            'zusan_danshi' => 270,
            
            // 组六：120种组合（三个不同数字的组合）
            'zuliu_fushi' => 120,
            'zuliu_danshi' => 120,
            
            // 定位类
            'zuxuan_yima_dingwei' => 10,  // 每个位置10种可能
            'zuxuan_liangma_dingwei' => 100, // 两个位置组合
            'zuxuan_yima_budingwei' => 10,
            
            // 和值：28种可能（0-27）
            'hezhi' => 28,
            
            // 大小和：2种可能（大、小）
            'daxiaohe' => 2,
            
            // 形态类：2种可能
            'hezhi_daxiao' => 2,
            'hezhi_danshuang' => 2
        ];
        
        return isset($combinations[$betType]) ? $combinations[$betType] : false;
    }
    
    /**
     * 生成投注统计报告
     * 
     * @param array $betData 投注数据
     * @return array 返回统计报告
     */
    public function generateBetReport($betData)
    {
        try {
            $report = [
                'total_orders' => 0,
                'total_cost' => 0,
                'total_bets' => 0,
                'bet_types' => [],
                'summary' => []
            ];
            
            foreach ($betData as $bet) {
                $betType = $bet['bet_type'] ?? '';
                $betAmount = $bet['bet_amount'] ?? 200;
                $multiple = $bet['multiple'] ?? 1;
                $betNumbers = $bet['bet_numbers'] ?? [];
                
                // 计算投注成本
                $costResult = $this->calculateBetCost($betType, $betNumbers, $betAmount, $multiple);
                
                if ($costResult['success']) {
                    $cost = $costResult['data']['total_cost'];
                    $betCount = $costResult['data']['bet_count'];
                    
                    $report['total_orders']++;
                    $report['total_cost'] += $cost;
                    $report['total_bets'] += $betCount;
                    
                    if (!isset($report['bet_types'][$betType])) {
                        $report['bet_types'][$betType] = [
                            'count' => 0,
                            'total_cost' => 0,
                            'total_bets' => 0
                        ];
                    }
                    
                    $report['bet_types'][$betType]['count']++;
                    $report['bet_types'][$betType]['total_cost'] += $cost;
                    $report['bet_types'][$betType]['total_bets'] += $betCount;
                }
            }
            
            // 生成摘要
            $report['summary'] = [
                'average_cost_per_order' => $report['total_orders'] > 0 ? 
                    $report['total_cost'] / $report['total_orders'] : 0,
                'average_bets_per_order' => $report['total_orders'] > 0 ? 
                    $report['total_bets'] / $report['total_orders'] : 0,
                'most_popular_bet_type' => $this->getMostPopularBetType($report['bet_types'])
            ];
            
            return [
                'success' => true,
                'data' => $report
            ];
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * 获取最受欢迎的投注类型
     * 
     * @param array $betTypes 投注类型统计
     * @return string
     */
    private function getMostPopularBetType($betTypes)
    {
        if (empty($betTypes)) {
            return '';
        }
        
        $maxCount = 0;
        $popularType = '';
        
        foreach ($betTypes as $type => $data) {
            if ($data['count'] > $maxCount) {
                $maxCount = $data['count'];
                $popularType = $type;
            }
        }
        
        return $popularType;
    }
    
    /**
     * 获取支持的投注类型列表
     * 
     * @return array
     */
    public function getSupportedBetTypes()
    {
        return array_keys($this->prizeConfig);
    }
    
    /**
     * 验证投注类型是否支持
     * 
     * @param string $betType 投注类型
     * @return bool
     */
    public function isSupportedBetType($betType)
    {
        return isset($this->prizeConfig[$betType]);
    }
}