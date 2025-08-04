<?php

namespace app\service\fc3d;

/**
 * 福彩3D统一验证服务
 * 整合所有玩法的中奖验证、注数计算和号码验证功能
 */
class Fc3dValidationService extends BaseService
{
    /**
     * 根据玩法类型验证中奖
     * 
     * @param string $betType 投注类型
     * @param mixed $betNumbers 投注号码
     * @param string $drawNumbers 开奖号码
     * @return array 返回中奖结果
     */
    public function checkWin($betType, $betNumbers, $drawNumbers)
    {
        try {
            // 处理新的数据格式，支持完整的投注数据对象
            if (is_array($betNumbers) && isset($betNumbers['type_key'])) {
                $betType = $betNumbers['type_key'];
                $numbers = $betNumbers['numbers'];
            } else {
                $numbers = $betNumbers;
            }
            
            switch ($betType) {
                case 'zhixuan_fushi':
                    return $this->checkZhixuanFushiWin($numbers, $drawNumbers);
                    
                case 'zhixuan_danshi':
                    return $this->checkZhixuanDanshiWin($numbers, $drawNumbers);
                    
                case 'zhixuan_hezhi':
                    return $this->checkZhixuanHezhiWin($numbers, $drawNumbers);
                    
                case 'zhixuan_kuadu':
                    return $this->checkZhixuanKuaduWin($numbers, $drawNumbers);
                    
                case 'zusan_fushi':
                    return $this->checkZusanFushiWin($numbers, $drawNumbers);
                    
                case 'zusan_danshi':
                    return $this->checkZusanDanshiWin($numbers, $drawNumbers);

                case 'zuliu_fushi':
                    return $this->checkZuliuFushiWin($numbers, $drawNumbers);
                    
                case 'zuliu_danshi':
                    return $this->checkZuliuDanshiWin($numbers, $drawNumbers);
                    
                case 'zuxuan_yima_dingwei':
                    return $this->checkDingweiWin($numbers, $drawNumbers, $betType);
                case 'zuxuan_liangma_dingwei':
                    return $this->checkDingweiWin($numbers, $drawNumbers, $betType);
                case 'zuxuan_yima_budingwei':
                    return $this->checkDingweiWin($numbers, $drawNumbers, $betType);
                    
                case 'hezhi':
                    return $this->checkHezhiWin($numbers, $drawNumbers);
                    
                case 'daxiaohe':
                    return $this->checkDaxiaoheWin($numbers, $drawNumbers);
                    
                case 'hezhi_daxiao':
                case 'hezhi_danshuang':
                    return $this->checkXingtaiWin($numbers, $drawNumbers, $betType);
                    
                default:
                    return $this->errorResult('不支持的投注类型: ' . $betType);
            }
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * 根据玩法类型计算投注注数
     * 
     * @param string $betType 投注类型
     * @param mixed $betNumbers 投注号码
     * @return int 注数
     */
    public function calculateBetCount($betType, $betNumbers)
    {
        try {
            // 处理新的数据格式，支持完整的投注数据对象
            if (is_array($betNumbers) && isset($betNumbers['type_key'])) {
                $betType = $betNumbers['type_key'];
                $numbers = $betNumbers['numbers'];
            } else {
                $numbers = $betNumbers;
            }
            
            switch ($betType) {
                case 'zhixuan_fushi':
                    return $this->calculateZhixuanFushiBetCount($numbers);
                    
                case 'zhixuan_danshi':
                    return $this->calculateZhixuanDanshiBetCount($numbers);
                    
                case 'zhixuan_hezhi':
                    return $this->calculateZhixuanHezhiBetCount($numbers);
                    
                case 'zhixuan_kuadu':
                    return $this->calculateZhixuanKuaduBetCount($numbers);
                    
                case 'zusan_fushi':
                    return $this->calculateZusanFushiBetCount($numbers);
                    
                case 'zusan_danshi':
                    return $this->calculateZusanDanshiBetCount($numbers);
                    
                case 'zuliu_fushi':
                    return $this->calculateZuliuFushiBetCount($numbers);
                    
                case 'zuliu_danshi':
                    return $this->calculateZuliuDanshiBetCount($numbers);
                    
                case 'zuxuan_yima_dingwei':
                case 'zuxuan_liangma_dingwei':
                case 'zuxuan_yima_budingwei':
                    return $this->calculateDingweiBetCount($numbers, $betType);
                    
                case 'hezhi':
                    return $this->calculateHezhiBetCount($numbers);
                    
                case 'daxiaohe':
                    return $this->calculateDaxiaoheBetCount($numbers);
                    
                case 'hezhi_daxiao':
                case 'hezhi_danshuang':
                    return $this->calculateXingtaiBetCount($numbers, $betType);
                    
                default:
                    return 0;
            }
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * 验证投注号码格式
     * 
     * @param string $betType 投注类型
     * @param mixed $betNumbers 投注号码
     * @return bool
     */
    public function validateBetNumbers($betType, $betNumbers)
    {
        try {
            // 处理新的数据格式，支持完整的投注数据对象
            if (is_array($betNumbers) && isset($betNumbers['type_key'])) {
                $betType = $betNumbers['type_key'];
                $numbers = $betNumbers['numbers'];
            } else {
                $numbers = $betNumbers;
            }
            
            switch ($betType) {
                case 'zhixuan_fushi':
                    return $this->validateZhixuanFushiBetNumbers($numbers);
                    
                case 'zhixuan_danshi':
                    return $this->validateZhixuanDanshiBetNumbers($numbers);
                    
                case 'zhixuan_hezhi':
                    return $this->validateZhixuanHezhiBetNumbers($numbers);
                    
                case 'zhixuan_kuadu':
                    return $this->validateZhixuanKuaduBetNumbers($numbers);
                    
                case 'zusan_fushi':
                    return $this->validateZusanFushiBetNumbers($numbers);
                    
                case 'zusan_danshi':
                    return $this->validateZusanDanshiBetNumbers($numbers);
                    
                case 'zuliu_fushi':
                    return $this->validateZuliuFushiBetNumbers($numbers);
                    
                case 'zuliu_danshi':
                    return $this->validateZuliuDanshiBetNumbers($numbers);
                    
                case 'zuxuan_yima_dingwei':
                    return $this->validateDingweiBetNumbers($numbers, $betType);
                case 'zuxuan_liangma_dingwei':
                    return $this->validateDingweiBetNumbers($numbers, $betType);
                case 'zuxuan_yima_budingwei':
                    return $this->validateDingweiBetNumbers($numbers, $betType);
                    
                case 'hezhi':
                    return $this->validateHezhiBetNumbers($numbers);
                    
                case 'daxiaohe':
                    return $this->validateDaxiaoheBetNumbers($numbers);
                    
                case 'hezhi_daxiao':
                case 'hezhi_danshuang':
                    return $this->validateXingtaiBetNumbers($numbers, $betType);
                    
                default:
                    return false;
            }
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    // ==================== 直选复式 ====================
    
    /**
     * 验证直选复式中奖
     */
    private function checkZhixuanFushiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateZhixuanFushiBetNumbers($betNumbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 检查是否中奖
        $baiWin = in_array($drawArray[0], $betNumbers['bai']);
        $shiWin = in_array($drawArray[1], $betNumbers['shi']);
        $geWin = in_array($drawArray[2], $betNumbers['ge']);
        
        $isWin = $baiWin && $shiWin && $geWin;
        
        // 计算中奖注数（直选复式只有一种中奖情况）
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'bai_win' => $baiWin,
            'shi_win' => $shiWin,
            'ge_win' => $geWin,
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers
        ]);
    }
    
    /**
     * 计算直选复式投注注数
     */
    private function calculateZhixuanFushiBetCount($betNumbers)
    {
        return $this->calculateThreePositionBetCount($betNumbers);
    }
    
    /**
     * 验证直选复式投注号码格式
     */
    private function validateZhixuanFushiBetNumbers($betNumbers)
    {
        return $this->validateThreePositionBet($betNumbers);
    }
    
    // ==================== 直选单式 ====================
    
    /**
     * 验证直选单式中奖
     */
    private function checkZhixuanDanshiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateZhixuanDanshiBetNumbers($betNumbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        $winCount = 0;
        $winDetails = [];
        
        // 遍历每一注投注号码
        foreach ($betNumbers as $index => $betNumber) {
            if (is_array($betNumber) && count($betNumber) === 3) {
                // 检查是否完全匹配
                if ($betNumber[0] == $drawArray[0] && 
                    $betNumber[1] == $drawArray[1] && 
                    $betNumber[2] == $drawArray[2]) {
                    $winCount++;
                    $winDetails[] = [
                        'index' => $index,
                        'bet_number' => $betNumber,
                        'is_win' => true
                    ];
                } else {
                    $winDetails[] = [
                        'index' => $index,
                        'bet_number' => $betNumber,
                        'is_win' => false
                    ];
                }
            }
        }
        
        return $this->successResult($winCount > 0, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'win_details' => $winDetails
        ]);
    }
    
    /**
     * 计算直选单式投注注数
     */
    private function calculateZhixuanDanshiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        $count = 0;
        foreach ($betNumbers as $betNumber) {
            if (is_array($betNumber) && count($betNumber) === 3) {
                $valid = true;
                foreach ($betNumber as $num) {
                    if (!$this->isValidNumber($num)) {
                        $valid = false;
                        break;
                    }
                }
                if ($valid) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * 验证直选单式投注号码格式
     */
    private function validateZhixuanDanshiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $betNumber) {
            if (!is_array($betNumber) || count($betNumber) !== 3) {
                return false;
            }
            
            foreach ($betNumber as $num) {
                if (!$this->isValidNumber($num)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    // ==================== 直选和值 ====================
    
    /**
     * 验证直选和值中奖
     */
    private function checkZhixuanHezhiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 处理新的数据格式：{"selected":["2","10","16","15"]}
        $numbers = [];
        if (isset($betNumbers['selected']) && is_array($betNumbers['selected'])) {
            $numbers = $betNumbers['selected'];
        } else {
            // 处理旧的数据格式：["2","10","16","15"]
            $numbers = $betNumbers;
        }
        
        // 验证投注号码格式
        if (!$this->validateZhixuanHezhiBetNumbers($numbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 计算开奖号码和值
        $drawSum = $this->calculateSum($drawArray);
        
        // 检查是否中奖
        $isWin = in_array($drawSum, $numbers);
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'draw_sum' => $drawSum,
            'bet_numbers' => $betNumbers,
            'numbers' => $numbers
        ]);
    }
    
    /**
     * 计算直选和值投注注数
     */
    private function calculateZhixuanHezhiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        $count = 0;
        foreach ($betNumbers as $sum) {
            if ($this->isValidSum($sum)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * 验证直选和值投注号码格式
     */
    private function validateZhixuanHezhiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $sum) {
            if (!$this->isValidSum($sum)) {
                return false;
            }
        }
        
        return true;
    }
    
    // ==================== 直选跨度 ====================
    
    /**
     * 验证直选跨度中奖
     */
    private function checkZhixuanKuaduWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 处理新的数据格式：{"selected":["9","8","1"]}
        $numbers = [];
        if (isset($betNumbers['selected']) && is_array($betNumbers['selected'])) {
            $numbers = $betNumbers['selected'];
        } else {
            // 处理旧的数据格式：["9","8","1"]
            $numbers = $betNumbers;
        }
        
        // 验证投注号码格式
        if (!$this->validateZhixuanKuaduBetNumbers($numbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 计算开奖号码跨度
        $drawSpan = max($drawArray) - min($drawArray);
        
        // 检查是否中奖
        $isWin = in_array($drawSpan, $numbers);
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'draw_span' => $drawSpan,
            'bet_numbers' => $betNumbers,
            'numbers' => $numbers
        ]);
    }
    
    /**
     * 计算直选跨度投注注数
     */
    private function calculateZhixuanKuaduBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        $count = 0;
        foreach ($betNumbers as $span) {
            if (is_numeric($span) && $span >= 0 && $span <= 9) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * 验证直选跨度投注号码格式
     */
    private function validateZhixuanKuaduBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $span) {
            if (!is_numeric($span) || $span < 0 || $span > 9) {
                return false;
            }
        }
        
        return true;
    }
    
    // ==================== 组三复式 ====================
    
    /**
     * 验证组三复式中奖
     */
    private function checkZusanFushiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 检查开奖号码是否为组三形态（有两个相同数字）
        $drawCounts = array_count_values($drawArray);
        $isZusanDraw = in_array(2, $drawCounts);
        
        if (!$isZusanDraw) {
            return $this->successResult(false, 0, [
                'draw_numbers' => $drawArray,
                'bet_numbers' => $betNumbers,
                'reason' => '开奖号码不是组三形态'
            ]);
        }
        
        // 处理新的数据格式：{"selected":["2","9"]}
        $numbers = [];
        if (isset($betNumbers['selected']) && is_array($betNumbers['selected'])) {
            $numbers = $betNumbers['selected'];
        } else {
            // 处理旧的数据格式：["2","9"]
            $numbers = $betNumbers;
        }
        
        // 验证投注号码格式
        if (!$this->validateZusanFushiBetNumbers($numbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 检查是否中奖
        $drawSorted = $drawArray;
        sort($drawSorted);
        $betSorted = $numbers;
        sort($betSorted);
        
        // 检查数组1的两个元素是否都在数组2中
        $isWin = in_array($drawSorted[0], $betSorted) && in_array($drawSorted[1], $betSorted);
        
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'numbers' => $numbers,
            'draw_sorted' => $drawSorted,
            'bet_sorted' => $betSorted
        ]);
    }
    
    /**
     * 计算组三复式投注注数
     */
    private function calculateZusanFushiBetCount($betNumbers)
    {
        if (!is_array($betNumbers) || count($betNumbers) < 2) {
            return 0;
        }
        
        $uniqueNumbers = array_unique($betNumbers);
        $count = count($uniqueNumbers);
        
        // 组三复式注数计算：C(n,2) * 2 = n * (n-1)
        return $count >= 2 ? $count * ($count - 1) : 0;
    }
    
    /**
     * 验证组三复式投注号码格式
     */
    private function validateZusanFushiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || count($betNumbers) < 2) {
            return false;
        }
        
        foreach ($betNumbers as $num) {
            if (!$this->isValidNumber($num)) {
                return false;
            }
        }
        
        // 检查是否有重复号码
        return count($betNumbers) === count(array_unique($betNumbers));
    }
    
    // ==================== 组三单式 ====================
    
    /**
     * 验证组三单式中奖
     */
    private function checkZusanDanshiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 检查开奖号码是否为组三形态
        $drawCounts = array_count_values($drawArray);
        $isZusanDraw = in_array(2, $drawCounts);
        
        if (!$isZusanDraw) {
            return $this->successResult(false, 0, [
                'draw_numbers' => $drawArray,
                'bet_numbers' => $betNumbers,
                'reason' => '开奖号码不是组三形态'
            ]);
        }
        
        // 处理新的数据格式：["229"]
         $numbers = [];
         if (is_array($betNumbers) && !empty($betNumbers)) {
             // 检查是否为新格式的字符串数组
             if (isset($betNumbers[0]) && is_string($betNumbers[0]) && strlen($betNumbers[0]) === 3) {
                 // 新格式：["229"] - 将字符串转换为数字数组
                 foreach ($betNumbers as $betString) {
                     if (is_string($betString) && strlen($betString) === 3) {
                         $numbers[] = str_split($betString);
                     }
                 }
             } else {
                 // 旧格式：[["2","2","9"]]
                 $numbers = $betNumbers;
             }
         }
        
        // 验证投注号码格式
        if (!$this->validateZusanDanshiBetNumbers($numbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        $winCount = 0;
        $winDetails = [];
        
        // 对开奖号码排序
        $drawSorted = $drawArray;
        sort($drawSorted);
        
        // 遍历每一注投注号码
        foreach ($numbers as $index => $betNumber) {
            if (is_array($betNumber) && count($betNumber) === 3) {
                // 对投注号码排序
                $betSorted = $betNumber;
                sort($betSorted);
                
                // 检查是否匹配
                if ($drawSorted == $betSorted) {
                    $winCount++;
                    $winDetails[] = [
                        'index' => $index,
                        'bet_number' => $betNumber,
                        'is_win' => true
                    ];
                } else {
                    $winDetails[] = [
                        'index' => $index,
                        'bet_number' => $betNumber,
                        'is_win' => false
                    ];
                }
            }
        }
        
        return $this->successResult($winCount > 0, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'numbers' => $numbers,
            'win_details' => $winDetails
        ]);
    }
    
    /**
     * 计算组三单式投注注数
     */
    private function calculateZusanDanshiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        $count = 0;
        foreach ($betNumbers as $betNumber) {
            if (is_array($betNumber) && count($betNumber) === 3) {
                // 验证是否为组三形态（有两个相同数字）
                $counts = array_count_values($betNumber);
                if (in_array(2, $counts)) {
                    $valid = true;
                    foreach ($betNumber as $num) {
                        if (!$this->isValidNumber($num)) {
                            $valid = false;
                            break;
                        }
                    }
                    if ($valid) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * 验证组三单式投注号码格式
     */
    private function validateZusanDanshiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $betNumber) {
            if (!is_array($betNumber) || count($betNumber) !== 3) {
                return false;
            }
            
            // 验证是否为组三形态（有两个相同数字）
            $counts = array_count_values($betNumber);
            if (!in_array(2, $counts)) {
                return false;
            }
            
            foreach ($betNumber as $num) {
                if (!$this->isValidNumber($num)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    // ==================== 组六复式 ====================
    
    /**
     * 验证组六复式中奖
     */
    private function checkZuliuFushiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 检查开奖号码是否为组六形态（三个不同数字）
        $drawCounts = array_count_values($drawArray);
        $isZuliuDraw = !in_array(2, $drawCounts) && !in_array(3, $drawCounts);
        
        if (!$isZuliuDraw) {
            return $this->successResult(false, 0, [
                'draw_numbers' => $drawArray,
                'bet_numbers' => $betNumbers,
                'reason' => '开奖号码不是组六形态'
            ]);
        }
        
        // 处理新的数据格式：{"selected":["4","6","5","9"]}
        $numbers = [];
        if (isset($betNumbers['selected']) && is_array($betNumbers['selected'])) {
            $numbers = $betNumbers['selected'];
        } else {
            // 处理旧的数据格式：["4","6","5","9"]
            $numbers = $betNumbers;
        }
        // 验证投注号码格式
        if (!$this->validateZuliuFushiBetNumbers($numbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 检查是否中奖
        $drawSorted = $drawArray;
        sort($drawSorted);
        $betSorted = $numbers;
        sort($betSorted);

        $intersection = array_intersect($drawSorted, $betSorted);
        
        $isWin = count($intersection) === count($drawSorted) && count($drawSorted) === 3;

        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'numbers' => $numbers,
            'draw_sorted' => $drawSorted,
            'bet_sorted' => $betSorted
        ]);
    }
    
    /**
     * 计算组六复式投注注数
     */
    private function calculateZuliuFushiBetCount($betNumbers)
    {
        if (!is_array($betNumbers) || count($betNumbers) < 3) {
            return 0;
        }
        
        $uniqueNumbers = array_unique($betNumbers);
        $count = count($uniqueNumbers);
        
        // 组六复式注数计算：C(n,3) = n * (n-1) * (n-2) / 6
        return $count >= 3 ? ($count * ($count - 1) * ($count - 2)) / 6 : 0;
    }
    
    /**
     * 验证组六复式投注号码格式
     */
    private function validateZuliuFushiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || count($betNumbers) < 3) {
            return false;
        }
        
        foreach ($betNumbers as $num) {
            if (!$this->isValidNumber($num)) {
                return false;
            }
        }
        
        // 检查是否有重复号码
        return count($betNumbers) === count(array_unique($betNumbers));
    }
    
    // ==================== 组六单式 ====================
    
    /**
     * 验证组六单式中奖
     */
    private function checkZuliuDanshiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateZuliuDanshiBetNumbers($betNumbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 检查开奖号码是否为组六形态
        $drawCounts = array_count_values($drawArray);
        $isZuliuDraw = !in_array(2, $drawCounts) && !in_array(3, $drawCounts);
        
        if (!$isZuliuDraw) {
            return $this->successResult(false, 0, [
                'draw_numbers' => $drawArray,
                'bet_numbers' => $betNumbers,
                'reason' => '开奖号码不是组六形态'
            ]);
        }
        
        $winCount = 0;
        $winDetails = [];
        
        // 对开奖号码排序
        $drawSorted = $drawArray;
        sort($drawSorted);
        
        // 遍历每一注投注号码
        foreach ($betNumbers as $index => $betNumber) {
            if (is_array($betNumber) && count($betNumber) === 3) {
                // 对投注号码排序
                $betSorted = $betNumber;
                sort($betSorted);
                
                // 检查是否匹配
                if ($drawSorted === $betSorted) {
                    $winCount++;
                    $winDetails[] = [
                        'index' => $index,
                        'bet_number' => $betNumber,
                        'is_win' => true
                    ];
                } else {
                    $winDetails[] = [
                        'index' => $index,
                        'bet_number' => $betNumber,
                        'is_win' => false
                    ];
                }
            }
        }
        
        return $this->successResult($winCount > 0, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'win_details' => $winDetails
        ]);
    }
    
    /**
     * 计算组六单式投注注数
     */
    private function calculateZuliuDanshiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        $count = 0;
        foreach ($betNumbers as $betNumber) {
            if (is_array($betNumber) && count($betNumber) === 3) {
                // 验证是否为组六形态（三个不同数字）
                $counts = array_count_values($betNumber);
                if (!in_array(2, $counts) && !in_array(3, $counts)) {
                    $valid = true;
                    foreach ($betNumber as $num) {
                        if (!$this->isValidNumber($num)) {
                            $valid = false;
                            break;
                        }
                    }
                    if ($valid) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * 验证组六单式投注号码格式
     */
    private function validateZuliuDanshiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $betNumber) {
            if (!is_array($betNumber) || count($betNumber) !== 3) {
                return false;
            }
            
            // 验证是否为组六形态（三个不同数字）
            $counts = array_count_values($betNumber);
            if (in_array(2, $counts) || in_array(3, $counts)) {
                return false;
            }
            
            foreach ($betNumber as $num) {
                if (!$this->isValidNumber($num)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    // ==================== 定位类玩法 ====================
    
    /**
     * 验证定位类玩法中奖
     */
    private function checkDingweiWin($betNumbers, $drawNumbers, $betType)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateDingweiBetNumbers($betNumbers, $betType)) {
            return $this->errorResult('投注号码格式错误');
        }
        switch ($betType) {
            case 'zuxuan_yima_dingwei':
                return $this->checkYimaDingweiWin($betNumbers, $drawArray);
            case 'zuxuan_liangma_dingwei':
                return $this->checkLiangmaDingweiWin($betNumbers, $drawArray);
            case 'zuxuan_yima_budingwei':
                return $this->checkYimaBudingweiWin($betNumbers, $drawArray);
            default:
                return $this->errorResult('不支持的定位玩法类型');
        }
    }
    
    /**
     * 验证一码定位中奖
     */
    private function checkYimaDingweiWin($betNumbers, $drawArray)
    {
        $winCount = 0;
        $winDetails = [];
        // 处理新的数据格式：{"position":"ge","numbers":["1"]}
        if (isset($betNumbers['position']) && isset($betNumbers['numbers'])) {
            $position = $betNumbers['position'];
            $numbers = $betNumbers['numbers'];
            $positionIndex = $this->getPositionIndex($position);
            if ($positionIndex !== false && is_array($numbers)) {
                $isWin = in_array($drawArray[$positionIndex], $numbers);
                if ($isWin) {
                    $winCount++;
                }
                
                $winDetails[$position] = [
                    'numbers' => $numbers,
                    'draw_number' => $drawArray[$positionIndex],
                    'is_win' => $isWin
                ];
            }
        } else {
            // 处理旧的数据格式：{"bai":["2","9"], "shi":["3","2"], "ge":["1"]}
            foreach ($betNumbers as $position => $numbers) {
                if (!is_array($numbers)) {
                    continue;
                }
                
                $positionIndex = $this->getPositionIndex($position);
                if ($positionIndex === false) {
                    continue;
                }
                
                $isWin = in_array($drawArray[$positionIndex], $numbers);
                if ($isWin) {
                    $winCount++;
                }
                
                $winDetails[$position] = [
                    'numbers' => $numbers,
                    'draw_number' => $drawArray[$positionIndex],
                    'is_win' => $isWin
                ];
            }
        }
        
        return $this->successResult($winCount > 0, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'win_details' => $winDetails
        ]);
    }
    
    /**
     * 验证两码定位中奖
     */
    private function checkLiangmaDingweiWin($betNumbers, $drawArray)
    {
        $winCount = 0;
        $winDetails = [];
        
        // 处理新的数据格式：{"positionCombo":"bai_ge","numbers":{"bai":["2"],"shi":[],"ge":["4"]}}
        if (isset($betNumbers['positionCombo']) && isset($betNumbers['numbers'])) {
            $positionCombo = $betNumbers['positionCombo'];
            $numbers = $betNumbers['numbers'];
            
            $positions = $this->parsePositionKey($positionCombo);
            if (count($positions) === 2 && is_array($numbers)) {
                $pos1 = $positions[0];
                $pos2 = $positions[1];
                
                $pos1Index = $this->getPositionIndex($pos1);
                $pos2Index = $this->getPositionIndex($pos2);
                
                if ($pos1Index !== false && $pos2Index !== false) {
                    // 检查对应位置是否有选号且中奖
                    $pos1Numbers = isset($numbers[$pos1]) ? $numbers[$pos1] : [];
                    $pos2Numbers = isset($numbers[$pos2]) ? $numbers[$pos2] : [];
                    
                    $pos1Win = is_array($pos1Numbers) && in_array($drawArray[$pos1Index], $pos1Numbers);
                    $pos2Win = is_array($pos2Numbers) && in_array($drawArray[$pos2Index], $pos2Numbers);
                    
                    $isWin = $pos1Win && $pos2Win;
                    
                    if ($isWin) {
                        $winCount++;
                    }
                    
                    $winDetails[$positionCombo] = [
                        'position_combo' => $positionCombo,
                        'numbers' => $numbers,
                        'draw_numbers' => [$drawArray[$pos1Index], $drawArray[$pos2Index]],
                        'is_win' => $isWin,
                        'pos1_win' => $pos1Win,
                        'pos2_win' => $pos2Win
                    ];
                }
            }
        } else {
            // 处理旧的数据格式：{"bai_ge":["2","4"], "bai_shi":["8","2"]}
            foreach ($betNumbers as $positionKey => $numbers) {
                if (!is_array($numbers)) {
                    continue;
                }
                
                $positions = $this->parsePositionKey($positionKey);
                if (count($positions) !== 2) {
                    continue;
                }
                
                $pos1Index = $this->getPositionIndex($positions[0]);
                $pos2Index = $this->getPositionIndex($positions[1]);
                
                if ($pos1Index === false || $pos2Index === false) {
                    continue;
                }
                
                $isWin = in_array($drawArray[$pos1Index], $numbers) && 
                         in_array($drawArray[$pos2Index], $numbers);
                
                if ($isWin) {
                    $winCount++;
                }
                
                $winDetails[$positionKey] = [
                    'numbers' => $numbers,
                    'draw_numbers' => [$drawArray[$pos1Index], $drawArray[$pos2Index]],
                    'is_win' => $isWin
                ];
            }
        }
        
        return $this->successResult($winCount > 0, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'win_details' => $winDetails
        ]);
    }
    
    /**
     * 验证一码不定位中奖
     */
    private function checkYimaBudingweiWin($betNumbers, $drawArray)
    {
        $winCount = 0;
        $winDetails = [];
        
        // 处理新的数据格式：{"numbers":["2","3"]}
        if (isset($betNumbers['numbers']) && is_array($betNumbers['numbers'])) {
            $numbers = $betNumbers['numbers'];
            
            foreach ($numbers as $number) {
                $isWin = in_array($number, $drawArray);
                
                if ($isWin) {
                    $winCount++;
                }
                
                $winDetails[] = [
                    'number' => $number,
                    'draw_numbers' => $drawArray,
                    'is_win' => $isWin
                ];
            }
        } else {
            // 处理旧的数据格式：["2","3"]
            if (!is_array($betNumbers) || empty($betNumbers)) {
                return $this->errorResult('投注号码格式错误');
            }
            
            foreach ($betNumbers as $number) {
                $isWin = in_array($number, $drawArray);
                
                if ($isWin) {
                    $winCount++;
                }
                
                $winDetails[] = [
                    'number' => $number,
                    'draw_numbers' => $drawArray,
                    'is_win' => $isWin
                ];
            }
        }
        
        return $this->successResult($winCount > 0, $winCount, [
            'draw_numbers' => $drawArray,
            'bet_numbers' => $betNumbers,
            'win_details' => $winDetails,
            'win_count' => $winCount
        ]);
    }
    
    /**
     * 计算定位类玩法投注注数
     */
    private function calculateDingweiBetCount($betNumbers, $betType)
    {
        switch ($betType) {
            case 'zuxuan_yima_dingwei':
                return $this->calculateYimaDingweiBetCount($betNumbers);
            case 'zuxuan_liangma_dingwei':
                return $this->calculateLiangmaDingweiBetCount($betNumbers);
            case 'zuxuan_yima_budingwei':
                return $this->calculateYimaBudingweiBetCount($betNumbers);
            default:
                return 0;
        }
    }
    
    /**
     * 计算一码定位投注注数
     */
    private function calculateYimaDingweiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        $totalCount = 0;
        foreach ($betNumbers as $position => $numbers) {
            if (is_array($numbers)) {
                $totalCount += count($numbers);
            }
        }
        
        return $totalCount;
    }
    
    /**
     * 计算两码定位投注注数
     */
    private function calculateLiangmaDingweiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        // 处理新的数据格式：{"positionCombo":"bai_shi","numbers":{"bai":["9"],"shi":["4"],"ge":[]}}
        if (isset($betNumbers['positionCombo']) && isset($betNumbers['numbers'])) {
            $positionCombo = $betNumbers['positionCombo'];
            $numbers = $betNumbers['numbers'];
            
            $positions = $this->parsePositionKey($positionCombo);
            if (count($positions) === 2) {
                $pos1 = $positions[0];
                $pos2 = $positions[1];
                
                $pos1Count = isset($numbers[$pos1]) && is_array($numbers[$pos1]) ? count($numbers[$pos1]) : 0;
                $pos2Count = isset($numbers[$pos2]) && is_array($numbers[$pos2]) ? count($numbers[$pos2]) : 0;
                
                // 两码定位注数计算：两个位置选号数量的乘积
                return $pos1Count * $pos2Count;
            }
        } else {
            // 处理旧的数据格式：{"bai_ge":["2","4"], "bai_shi":["8","2"]}
            $totalCount = 0;
            foreach ($betNumbers as $positionKey => $numbers) {
                if (is_array($numbers)) {
                    $count = count($numbers);
                    // 旧格式中，每个位置组合的注数是选号数量的组合数
                    $totalCount += $count >= 2 ? ($count * ($count - 1)) / 2 : 0;
                }
            }
            return $totalCount;
        }
        
        return 0;
    }
    
    /**
     * 计算一码不定位投注注数
     */
    private function calculateYimaBudingweiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        return count($betNumbers);
    }
    
    /**
     * 验证定位类玩法投注号码格式
     */
    private function validateDingweiBetNumbers($betNumbers, $betType)
    {
        switch ($betType) {
            case 'zuxuan_yima_dingwei':
                return $this->validateYimaDingweiBetNumbers($betNumbers);
            case 'zuxuan_liangma_dingwei':
                return $this->validateLiangmaDingweiBetNumbers($betNumbers);
            case 'zuxuan_yima_budingwei':
                return $this->validateYimaBudingweiBetNumbers($betNumbers);
            default:
                return false;
        }
    }
    
    /**
     * 验证一码定位投注号码格式
     */
    private function validateYimaDingweiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        $validPositions = ['bai', 'shi', 'ge'];
        
        if (!in_array($betNumbers['position'], $validPositions)) {
            return false;
        }
        
        if (!is_array($betNumbers['numbers']) || empty($betNumbers['numbers'])) {
            return false;
        }
        foreach ($betNumbers['numbers'] as $num) {
            if (!$this->isValidNumber($num)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证两码定位投注号码格式
     */
    private function validateLiangmaDingweiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        // 处理新的数据格式：{"positionCombo":"bai_shi","numbers":{"bai":["9"],"shi":["4"],"ge":[]}}
        if (isset($betNumbers['positionCombo']) && isset($betNumbers['numbers'])) {
            $validPositionKeys = ['bai_shi', 'bai_ge', 'shi_ge'];
            
            if (!in_array($betNumbers['positionCombo'], $validPositionKeys)) {
                return false;
            }
            
            if (!is_array($betNumbers['numbers'])) {
                return false;
            }
            
            $positions = $this->parsePositionKey($betNumbers['positionCombo']);
            if (count($positions) !== 2) {
                return false;
            }
            
            // 验证对应位置的选号
            foreach ($positions as $position) {
                if (isset($betNumbers['numbers'][$position]) && is_array($betNumbers['numbers'][$position])) {
                    foreach ($betNumbers['numbers'][$position] as $num) {
                        if (!$this->isValidNumber($num)) {
                            return false;
                        }
                    }
                }
            }
            
            return true;
        } else {
            // 处理旧的数据格式：{"bai_ge":["2","4"], "bai_shi":["8","2"]}
            $validPositionKeys = ['bai_shi', 'bai_ge', 'shi_ge'];
            
            foreach ($betNumbers as $positionKey => $numbers) {
                if (!in_array($positionKey, $validPositionKeys)) {
                    return false;
                }
                
                if (!is_array($numbers)) {
                    return false;
                }
                
                foreach ($numbers as $num) {
                    if (!$this->isValidNumber($num)) {
                        return false;
                    }
                }
            }
            
            return true;
        }
    }
    
    /**
     * 验证一码不定位投注号码格式
     */
    private function validateYimaBudingweiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $num) {
            foreach($num as $n){
                if (!$this->isValidNumber($n)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    // ==================== 和值玩法 ====================
    
    /**
     * 验证和值玩法中奖
     */
    private function checkHezhiWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateHezhiBetNumbers($betNumbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 计算开奖号码和值
        $drawSum = $this->calculateSum($drawArray);
        
        // 检查是否中奖
        $isWin = in_array($drawSum, $betNumbers);
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'draw_sum' => $drawSum,
            'bet_numbers' => $betNumbers
        ]);
    }
    
    /**
     * 计算和值玩法投注注数
     */
    private function calculateHezhiBetCount($betNumbers)
    {
        if (!is_array($betNumbers)) {
            return 0;
        }
        
        return count($betNumbers);
    }
    
    /**
     * 验证和值玩法投注号码格式
     */
    private function validateHezhiBetNumbers($betNumbers)
    {
        if (!is_array($betNumbers) || empty($betNumbers)) {
            return false;
        }
        
        foreach ($betNumbers as $sum) {
            if (!$this->isValidSum($sum)) {
                return false;
            }
        }
        
        return true;
    }
    
    // ==================== 大小和玩法 ====================
    
    /**
     * 验证大小和玩法中奖
     */
    private function checkDaxiaoheWin($betNumbers, $drawNumbers)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateDaxiaoheBetNumbers($betNumbers)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 计算开奖号码和值
        $drawSum = $this->calculateSum($drawArray);
        
        // 判断大小和类型
        $drawType = $this->getDaxiaoheType($drawSum);
        
        // 获取选中的选项
        $selectedOption = $this->getSelectedOption($betNumbers);
        $convertedOption = $this->convertDaxiaoheOption($selectedOption);
        
        // 检查是否中奖
        $isWin = ($drawType === $convertedOption);
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'draw_sum' => $drawSum,
            'draw_type' => $drawType,
            'selected_option' => $selectedOption,
            'converted_option' => $convertedOption
        ]);
    }
    
    /**
     * 计算大小和玩法投注注数
     */
    private function calculateDaxiaoheBetCount($betNumbers)
    {
        // 大小和玩法每次只能选择一个选项，所以注数为1
        return 1;
    }
    
    /**
     * 验证大小和玩法投注号码格式
     */
    private function validateDaxiaoheBetNumbers($betNumbers)
    {
        $selectedOption = $this->getSelectedOption($betNumbers);
        return $this->isValidDaxiaoheOption($selectedOption);
    }
    
    // ==================== 形态类玩法 ====================
    
    /**
     * 验证形态类玩法中奖
     */
    private function checkXingtaiWin($betNumbers, $drawNumbers, $betType)
    {
        // 解析开奖号码
        $drawArray = $this->parseDrawNumbers($drawNumbers);
        if ($drawArray === false) {
            return $this->errorResult('开奖号码格式错误');
        }
        
        // 验证投注号码格式
        if (!$this->validateXingtaiBetNumbers($betNumbers, $betType)) {
            return $this->errorResult('投注号码格式错误');
        }
        
        // 计算开奖号码和值
        $drawSum = $this->calculateSum($drawArray);
        
        switch ($betType) {
            case 'hezhi_daxiao':
                return $this->checkHezhiDaxiaoWin($betNumbers, $drawSum, $drawArray);
            case 'hezhi_danshuang':
                return $this->checkHezhiDanshuangWin($betNumbers, $drawSum, $drawArray);
            default:
                return $this->errorResult('不支持的形态玩法类型');
        }
    }
    
    /**
     * 验证和值大小中奖
     */
    private function checkHezhiDaxiaoWin($betNumbers, $drawSum, $drawArray)
    {
        // 判断和值大小
        $drawType = $this->getSumSize($drawSum);
        
        // 获取选中的选项
        $selectedOption = $this->getSelectedOption($betNumbers);
        
        // 转换选项
        $convertedOption = '';
        switch ($selectedOption) {
            case 'da':
            case 'big':
            case '大':
                $convertedOption = '大';
                break;
            case 'xiao':
            case 'small':
            case '小':
                $convertedOption = '小';
                break;
        }
        
        // 检查是否中奖
        $isWin = ($drawType === $convertedOption);
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'draw_sum' => $drawSum,
            'draw_type' => $drawType,
            'selected_option' => $selectedOption,
            'converted_option' => $convertedOption
        ]);
    }
    
    /**
     * 验证和值单双中奖
     */
    private function checkHezhiDanshuangWin($betNumbers, $drawSum, $drawArray)
    {
        // 判断和值单双
        $drawType = $this->getSumParity($drawSum);
        
        // 获取选中的选项
        $selectedOption = $this->getSelectedOption($betNumbers);
        
        // 转换选项
        $convertedOption = '';
        switch ($selectedOption) {
            case 'dan':
            case 'odd':
            case '单':
                $convertedOption = '单';
                break;
            case 'shuang':
            case 'even':
            case '双':
                $convertedOption = '双';
                break;
        }
        
        // 检查是否中奖
        $isWin = ($drawType === $convertedOption);
        $winCount = $isWin ? 1 : 0;
        
        return $this->successResult($isWin, $winCount, [
            'draw_numbers' => $drawArray,
            'draw_sum' => $drawSum,
            'draw_type' => $drawType,
            'selected_option' => $selectedOption,
            'converted_option' => $convertedOption
        ]);
    }
    
    /**
     * 计算形态类玩法投注注数
     */
    private function calculateXingtaiBetCount($betNumbers, $betType)
    {
        // 形态类玩法每次只能选择一个选项，所以注数为1
        return 1;
    }
    
    /**
     * 验证形态类玩法投注号码格式
     */
    private function validateXingtaiBetNumbers($betNumbers, $betType)
    {
        $selectedOption = $this->getSelectedOption($betNumbers);
        
        switch ($betType) {
            case 'hezhi_daxiao':
                return in_array($selectedOption, ['da', 'xiao', 'big', 'small', '大', '小']);
            case 'hezhi_danshuang':
                return in_array($selectedOption, ['dan', 'shuang', 'odd', 'even', '单', '双']);
            default:
                return false;
        }
    }
    
    // ==================== 辅助方法 ====================
    
    /**
     * 获取选中的选项（用于大小和等玩法）
     * 
     * @param array $betNumbers 投注号码
     * @return string
     */
    protected function getSelectedOption($betNumbers)
    {
        // 处理数组格式的投注数据
        if (is_array($betNumbers)) {
            // 优先处理numbers字段（新格式）
            if (isset($betNumbers['numbers'])) {
                $numbers = $betNumbers['numbers'];
                if (is_array($numbers) && count($numbers) > 0) {
                    return $numbers[0]; // 返回第一个选项
                } elseif (is_string($numbers)) {
                    return $numbers;
                }
            }
            
            // 处理key字段
            if (isset($betNumbers['key'])) {
                return $betNumbers['key'];
            }
            
            // 处理selected字段
            if (isset($betNumbers['selected']) && is_array($betNumbers['selected']) && count($betNumbers['selected']) > 0) {
                return $betNumbers['selected'][0];
            }
            
            // 处理直接数组格式
            if (count($betNumbers) > 0) {
                $firstValue = reset($betNumbers);
                if (is_string($firstValue)) {
                    return $firstValue;
                }
            }
        } elseif (is_string($betNumbers)) {
            return $betNumbers;
        }
        
        return '';
    }
    
    /**
     * 获取位置索引
     */
    private function getPositionIndex($position)
    {
        switch ($position) {
            case 'bai':
                return 0;
            case 'shi':
                return 1;
            case 'ge':
                return 2;
            default:
                return false;
        }
    }
    
    /**
     * 解析位置键
     */
    private function parsePositionKey($positionKey)
    {
        switch ($positionKey) {
            case 'bai_shi':
                return ['bai', 'shi'];
            case 'bai_ge':
                return ['bai', 'ge'];
            case 'shi_ge':
                return ['shi', 'ge'];
            default:
                return [];
        }
    }
    
    /**
     * 获取支持的投注类型列表
     * 
     * @return array
     */
    public function getSupportedBetTypes()
    {
        return [
            // 直选类
            'zhixuan_fushi' => '直选复式',
            'zhixuan_danshi' => '直选单式',
            'zhixuan_hezhi' => '直选和值',
            'zhixuan_kuadu' => '直选跨度',
            
            // 组选类
            'zusan_fushi' => '组三复式',
            'zusan_danshi' => '组三单式',
            'zuliu_fushi' => '组六复式',
            'zuliu_danshi' => '组六单式',
            
            // 定位类
            'zuxuan_yima_dingwei' => '一码定位',
            'zuxuan_liangma_dingwei' => '两码定位',
            'zuxuan_yima_budingwei' => '一码不定位',
            
            // 其他类
            'hezhi' => '和值',
            'daxiaohe' => '大小和',
            
            // 形态类
            'hezhi_daxiao' => '和值大小',
            'hezhi_danshuang' => '和值单双'
        ];
    }
    
    /**
     * 批量验证中奖（用于开奖结算）
     * 
     * @param array $betOrders 投注订单数组
     * @param string $drawNumbers 开奖号码
     * @return array 返回批量验证结果
     */
    public function batchCheckWin($betOrders, $drawNumbers)
    {
        $results = [];
        
        foreach ($betOrders as $order) {
            try {
                $betContent = is_string($order['bet_content']) ? 
                    json_decode($order['bet_content'], true) : $order['bet_content'];
                
                if (!$betContent) {
                    $results[$order['id']] = $this->errorResult('投注内容格式错误');
                    continue;
                }
                
                // 支持新的数据格式
                if (isset($betContent['type_key'])) {
                    // 新格式：包含完整投注信息
                    $result = $this->checkWin($betContent['type_key'], $betContent, $drawNumbers);
                } else {
                    // 旧格式：兼容处理
                    $betType = $order['bet_type'] ?? '';
                    $result = $this->checkWin($betType, $betContent, $drawNumbers);
                }
                
                $results[$order['id']] = $result;
                
            } catch (\Exception $e) {
                $results[$order['id']] = $this->handleException($e);
            }
        }
        
        return $results;
    }
}