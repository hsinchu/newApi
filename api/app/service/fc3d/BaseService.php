<?php

namespace app\service\fc3d;

use think\Service;

/**
 * 福彩3D基础服务类
 * 提供公共方法，减少代码重复
 */
class BaseService
{
    /**
     * 解析开奖号码
     * 
     * @param string|array $drawNumbers 开奖号码
     * @return array|false 解析后的开奖号码数组，失败返回false
     */
    protected function parseDrawNumbers($drawNumbers)
    {
        try {
            // 统一处理开奖号码格式
            $drawArray = is_array($drawNumbers) ? $drawNumbers : explode(',', $drawNumbers);
            
            if (count($drawArray) !== 3) {
                return false;
            }
            
            // 转换为整数并验证范围
            $result = [];
            foreach ($drawArray as $num) {
                $intNum = intval(trim($num));
                if ($intNum < 0 || $intNum > 9) {
                    return false;
                }
                $result[] = $intNum;
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 解析投注号码
     * 
     * @param mixed $betNumbers 投注号码
     * @return array|false 解析后的投注号码，失败返回false
     */
    protected function parseBetNumbers($betNumbers)
    {
        try {
            if (is_string($betNumbers)) {
                $decoded = json_decode($betNumbers, true);
                return $decoded !== null ? $decoded : false;
            }
            
            return is_array($betNumbers) ? $betNumbers : false;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 创建错误结果
     * 
     * @param string $message 错误信息
     * @return array
     */
    protected function errorResult($message)
    {
        return [
            'is_win' => false,
            'win_count' => 0,
            'error' => $message
        ];
    }
    
    /**
     * 创建成功结果
     * 
     * @param bool $isWin 是否中奖
     * @param int $winCount 中奖注数
     * @param array $detail 详细信息
     * @return array
     */
    protected function successResult($isWin, $winCount, $detail = [])
    {
        $result = [
            'is_win' => $isWin,
            'win_count' => $winCount
        ];
        
        if (!empty($detail)) {
            $result['win_detail'] = $detail;
        }
        
        return $result;
    }
    
    /**
     * 验证号码是否在有效范围内(0-9)
     * 
     * @param mixed $number 要验证的号码
     * @return bool
     */
    protected function isValidNumber($number)
    {
        return is_numeric($number) && $number >= 0 && $number <= 9;
    }
    
    /**
     * 验证号码数组是否有效
     * 
     * @param array $numbers 号码数组
     * @return bool
     */
    protected function isValidNumberArray($numbers)
    {
        if (!is_array($numbers) || empty($numbers)) {
            return false;
        }
        
        foreach ($numbers as $number) {
            if (!$this->isValidNumber($number)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证数组是否唯一（无重复元素）
     * 
     * @param array $array 要验证的数组
     * @return bool
     */
    protected function isUniqueArray($array)
    {
        return count($array) === count(array_unique($array));
    }
    
    /**
     * 验证三位数投注格式（百十个位）
     * 
     * @param array $betNumbers 投注号码
     * @param array $requiredPositions 必需的位置，默认['bai', 'shi', 'ge']
     * @return bool
     */
    protected function validateThreePositionBet($betNumbers, $requiredPositions = ['bai', 'shi', 'ge'])
    {
        if (!is_array($betNumbers)) {
            return false;
        }
        
        foreach ($requiredPositions as $position) {
            if (!isset($betNumbers[$position]) || !is_array($betNumbers[$position])) {
                return false;
            }
            
            // 每个位置至少需要选择1个号码
            if (count($betNumbers[$position]) < 1) {
                return false;
            }
            
            // 验证每个号码是否在0-9范围内
            if (!$this->isValidNumberArray($betNumbers[$position])) {
                return false;
            }
            
            // 验证号码是否唯一
            if (!$this->isUniqueArray($betNumbers[$position])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 计算三位数复式投注注数
     * 
     * @param array $betNumbers 投注号码
     * @param array $positions 位置数组，默认['bai', 'shi', 'ge']
     * @return int
     */
    protected function calculateThreePositionBetCount($betNumbers, $positions = ['bai', 'shi', 'ge'])
    {
        try {
            if (!$this->validateThreePositionBet($betNumbers, $positions)) {
                return 0;
            }
            
            $count = 1;
            foreach ($positions as $position) {
                $count *= count($betNumbers[$position]);
            }
            
            return $count;
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * 计算开奖号码和值
     * 
     * @param array $drawNumbers 开奖号码数组
     * @return int
     */
    protected function calculateSum($drawNumbers)
    {
        return array_sum($drawNumbers);
    }
    
    /**
     * 判断和值大小
     * 
     * @param int $sum 和值
     * @return string '大'|'小'
     */
    protected function getSumSize($sum)
    {
        return $sum >= 14 ? '大' : '小';
    }
    
    /**
     * 判断和值单双
     * 
     * @param int $sum 和值
     * @return string '单'|'双'
     */
    protected function getSumParity($sum)
    {
        return $sum % 2 === 1 ? '单' : '双';
    }
    
    /**
     * 验证和值范围
     * 
     * @param int $sum 和值
     * @return bool
     */
    protected function isValidSum($sum)
    {
        return $sum >= 0 && $sum <= 27;
    }
    
    /**
     * 统一异常处理
     * 
     * @param \Exception $e 异常对象
     * @return array
     */
    protected function handleException(\Exception $e)
    {
        return $this->errorResult('中奖验证异常: ' . $e->getMessage());
    }
    
    /**
     * 获取选中的选项（用于大小和等玩法）
     * 
     * @param array $betNumbers 投注号码
     * @return string
     */
    protected function getSelectedOption($betNumbers)
    {
        if (isset($betNumbers['key'])) {
            return $betNumbers['key'];
        } elseif (isset($betNumbers['selected']) && is_array($betNumbers['selected']) && count($betNumbers['selected']) > 0) {
            return $betNumbers['selected'][0];
        } elseif (is_string($betNumbers)) {
            return $betNumbers;
        }
        
        return '';
    }
    
    /**
     * 判断大小和类型
     * 
     * @param int $sum 和值
     * @return string
     */
    protected function getDaxiaoheType($sum)
    {
        if ($sum >= 19 && $sum <= 27) {
            return '大';
        } elseif ($sum >= 9 && $sum <= 18) {
            return '和';
        } elseif ($sum >= 0 && $sum <= 8) {
            return '小';
        }
        
        return '';
    }
    
    /**
     * 验证大小和选项是否有效
     * 
     * @param string $option 选项
     * @return bool
     */
    protected function isValidDaxiaoheOption($option)
    {
        $validOptions = ['da', 'he', 'xiao', 'big', 'middle', 'small', '大', '和', '小'];
        return in_array($option, $validOptions);
    }
    
    /**
     * 转换大小和选项为中文
     * 
     * @param string $option 选项
     * @return string
     */
    protected function convertDaxiaoheOption($option)
    {
        switch ($option) {
            case 'da':
            case 'big':
            case '大':
                return '大';
            case 'he':
            case 'middle':
            case '和':
                return '和';
            case 'xiao':
            case 'small':
            case '小':
                return '小';
            default:
                return '';
        }
    }
    
    // 注意：子类可以根据需要实现以下方法：
    // - checkWin($betNumbers, $drawNumbers): 验证中奖
    // - calculateBetCount($betNumbers): 计算投注注数  
    // - validateBetNumbers($betNumbers): 验证投注号码格式
    // Fc3dValidationService作为调度器类有特殊的方法签名，不强制实现这些方法
}