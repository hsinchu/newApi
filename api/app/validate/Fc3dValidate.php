<?php

namespace app\validate;

use app\validate\BetValidate;
use think\facade\Log;

/**
 * 福彩3D验证类
 * 
 * 提供福彩3D彩种的专用验证功能，包括：
 * - 直选定位注数验证
 * - 组三/组六注数验证
 * - 大小单双注数验证
 * - 和值注数验证
 * 
 * @author BuildAdmin Team
 * @version 1.0.0
 */
class Fc3dValidate extends BetValidate
{
    /**
     * 验证投注参数（福彩3D专用）
     * 
     * @param array $params 投注参数
     * @return array 验证结果
     */
    public function validateBetParams(array $params): array
    {
        $startTime = microtime(true);
        
        try {
            Log::info('开始福彩3D专用验证', [
                'lottery_code' => $params['lottery_code'] ?? '',
                'period_no' => $params['period_no'] ?? ''
            ]);
            
            // 先进行基础验证
            $result = parent::validateBetParams($params);
            
            if ($result['code'] != 1) {
                Log::warning('福彩3D基础验证失败', ['error' => $result['msg']]);
                return $result;
            }

            // 进行福彩3D特有的验证
            $betData = $result['data']['bet_data'];
            $validatedItems = 0;
            
            foreach ($betData as $index => $betItem) {
                $typeKey = $betItem['type_key'] ?? '';
                $note = (int)($betItem['note'] ?? 0);
                $numbers = $betItem['numbers'] ?? null;
                
                // 根据玩法类型进行注数验证
                $noteValidation = $this->validatePlayTypeNote($typeKey, $numbers, $note, $index);
                if ($noteValidation['code'] != 1) {
                    Log::warning('福彩3D玩法验证失败', [
                        'index' => $index,
                        'type_key' => $typeKey,
                        'error' => $noteValidation['msg']
                    ]);
                    return $noteValidation;
                }
                
                $validatedItems++;
            }
            
            // 添加验证统计信息
            $result['data']['fc3d_validation'] = [
                'validated_items' => $validatedItems,
                'validation_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
            ];
            
            Log::info('福彩3D验证完成', [
                'validated_items' => $validatedItems,
                'total_amount' => $result['data']['total_amount'],
                'validation_time' => $result['data']['fc3d_validation']['validation_time']
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('福彩3D验证异常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'params' => $params
            ]);
            return $this->buildErrorResponse('福彩3D验证异常，请稍后重试');
        }
    }

    /**
     * 福彩3D支持的玩法类型映射
     * @var array
     */
    protected $playTypeMap = [
        // 直选类
        'zhixuan_fushi' => '直选复式',
        'zhixuan_hezhi' => '直选和值',
        'zhixuan_kuadu' => '直选跨度',
        
        // 组选类
        'zusan_danshi' => '组三单式',
        'zusan_fushi' => '组三复式',
        'zusan_tuodan' => '组三拖胆',
        'zuliu_fushi' => '组六复式',
        'zuliu_tuodan' => '组六拖胆',
        
        // 定位类
        'zuxuan_yima_dingwei' => '一码定位',
        'zuxuan_liangma_dingwei' => '两码定位',
        'zuxuan_yima_budingwei' => '一码不定位',
        
        // 形态类
        'duizi' => '对子',
        'hezhi_daxiao' => '和值大小',
        'hezhi_danshuang' => '和值单双'
    ];

    /**
     * 验证玩法注数
     * 
     * @param string $typeKey 玩法类型
     * @param mixed $numbers 选中号码
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validatePlayTypeNote(string $typeKey, $numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        // 检查玩法类型是否支持
        if (!isset($this->playTypeMap[$typeKey])) {
            Log::warning('不支持的福彩3D玩法类型', [
                'type_key' => $typeKey,
                'index' => $index,
                'supported_types' => array_keys($this->playTypeMap)
            ]);
            return $this->buildErrorResponse("{$itemLabel}玩法类型'{$typeKey}'暂不支持");
        }
        
        try {
            switch ($typeKey) {
                // 直选类验证
                case 'zhixuan_fushi':
                    return $this->validateDirectComplexNote($numbers, $note, $index);
                    
                case 'zhixuan_hezhi':
                    return $this->validateDirectSumNote($numbers, $note, $index);
                    
                case 'zhixuan_kuadu':
                    return $this->validateDirectSpanNote($numbers, $note, $index);
                
                // 组选类验证
                case 'zusan_danshi':
                    return $this->validateGroupThreeSingleNote($numbers, $note, $index);
                
                case 'zusan_fushi':
                    return $this->validateGroupThreeNote($numbers, $note, $index);
                
                case 'zusan_tuodan':
                    return $this->validateGroupThreeDragNote($numbers, $note, $index);
                
                case 'zuliu_fushi':
                    return $this->validateGroupSixNote($numbers, $note, $index);
                
                case 'zuliu_tuodan':
                    return $this->validateGroupSixDragNote($numbers, $note, $index);
                
                // 定位类验证
                case 'zuxuan_yima_dingwei':
                case 'zuxuan_liangma_dingwei':
                case 'zuxuan_yima_budingwei':
                    return $this->validatePositionNote($numbers, $note, $index, $typeKey);
                
                // 形态类验证
                case 'daxiao':
                case 'danshuang':
                case 'duizi':
                case 'daxiaodanshuang':
                    return $this->validateBigSmallOddEvenNote($numbers, $note, $index);
                
                // 新的和值形态类验证
                case 'hezhi_daxiao':
                case 'hezhi_danshuang':
                    return $this->validateHezhiXingtaiNote($numbers, $note, $index, $typeKey);
                
                default:
                    // 对于已知但未实现的玩法，记录日志但允许通过
                    Log::info('福彩3D玩法验证跳过', [
                        'type_key' => $typeKey,
                        'type_name' => $this->playTypeMap[$typeKey],
                        'reason' => '验证方法未实现'
                    ]);
                    return $this->buildSuccessResponse('验证通过（跳过专用验证）');
            }
            
        } catch (\Exception $e) {
            Log::error('福彩3D玩法验证异常', [
                'type_key' => $typeKey,
                'index' => $index,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->buildErrorResponse("{$itemLabel}验证异常，请检查投注数据");
        }
    }

    /**
     * 验证直选定位注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateDirectSelectNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 验证必要的位数字段
        $requiredFields = ['bai', 'shi', 'ge'];
        foreach ($requiredFields as $field) {
            if (!isset($numbers[$field]) || !is_array($numbers[$field])) {
                return $this->buildErrorResponse("{$itemLabel}缺少{$field}位选号数据");
            }
        }
        
        $baiCount = count($numbers['bai']);
        $shiCount = count($numbers['shi']);
        $geCount = count($numbers['ge']);
        
        // 验证每位至少选择一个号码
        if ($baiCount === 0 || $shiCount === 0 || $geCount === 0) {
            return $this->buildErrorResponse("{$itemLabel}每位至少需要选择一个号码");
        }
        
        // 验证选号范围（0-9）
        foreach (['bai' => $numbers['bai'], 'shi' => $numbers['shi'], 'ge' => $numbers['ge']] as $position => $nums) {
            foreach ($nums as $num) {
                if (!is_numeric($num) || $num < 0 || $num > 9) {
                    return $this->buildErrorResponse("{$itemLabel}{$position}位包含无效号码：{$num}");
                }
            }
        }
        
        // 直选定位：注数 = 百位选中数 × 十位选中数 × 个位选中数
        $calculatedNote = $baiCount * $shiCount * $geCount;
        
        if ($calculatedNote !== $note) {
            Log::warning('直选定位注数计算不匹配', [
                'index' => $index,
                'bai_count' => $baiCount,
                'shi_count' => $shiCount,
                'ge_count' => $geCount,
                'calculated' => $calculatedNote,
                'provided' => $note
            ]);
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('直选定位验证通过');
    }

    /**
     * 验证组三注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateGroupThreeNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers) || !isset($numbers['selected'])) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应包含selected字段");
        }
        
        $selected = $numbers['selected'];
        if (!is_array($selected)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，selected应为数组");
        }
        
        $selectedCount = count($selected);
        
        // 验证至少选择2个号码
        if ($selectedCount < 2) {
            return $this->buildErrorResponse("{$itemLabel}组三至少需要选择2个号码");
        }
        
        // 验证最多选择10个号码
        if ($selectedCount > 10) {
            return $this->buildErrorResponse("{$itemLabel}组三最多只能选择10个号码");
        }
        
        // 验证选号范围和去重
        $validNumbers = [];
        foreach ($selected as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return $this->buildErrorResponse("{$itemLabel}包含无效号码：{$num}");
            }
            $validNumbers[] = (int)$num;
        }
        
        // 去重检查
        $uniqueNumbers = array_unique($validNumbers);
        if (count($uniqueNumbers) !== count($validNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号中包含重复号码");
        }
        
        // 组三复式：不管选几个数都是一注，奖金会随着数量增多而变化
        $calculatedNote = 1;
        
        if ($calculatedNote !== $note) {
            Log::warning('组三注数计算不匹配', [
                'index' => $index,
                'selected_count' => $selectedCount,
                'calculated' => $calculatedNote,
                'provided' => $note
            ]);
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('组三验证通过');
    }

    /**
     * 验证组六注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateGroupSixNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers) || !isset($numbers['selected'])) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应包含selected字段");
        }
        
        $selected = $numbers['selected'];
        if (!is_array($selected)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，selected应为数组");
        }
        
        $selectedCount = count($selected);
        
        // 验证至少选择3个号码
        if ($selectedCount < 3) {
            return $this->buildErrorResponse("{$itemLabel}组六至少需要选择3个号码");
        }
        
        // 验证最多选择10个号码
        if ($selectedCount > 10) {
            return $this->buildErrorResponse("{$itemLabel}组六最多只能选择10个号码");
        }
        
        // 验证选号范围和去重
        $validNumbers = [];
        foreach ($selected as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return $this->buildErrorResponse("{$itemLabel}包含无效号码：{$num}");
            }
            $validNumbers[] = (int)$num;
        }
        
        // 去重检查
        $uniqueNumbers = array_unique($validNumbers);
        if (count($uniqueNumbers) !== count($validNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号中包含重复号码");
        }
        
        // 组六复式：不管选几个数都是一注，奖金会随着数量增多而变化
        $calculatedNote = 1;
        
        if ($calculatedNote !== $note) {
            Log::warning('组六注数计算不匹配', [
                'index' => $index,
                'selected_count' => $selectedCount,
                'calculated' => $calculatedNote,
                'provided' => $note
            ]);
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('组六验证通过');
    }

    /**
     * 验证大小单双注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateBigSmallOddEvenNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 检查是否为Form组件的数据格式（包含options或pairs字段）
        if (isset($numbers['options'])) {
            // 大小或单双玩法：验证options字段
            if (!is_array($numbers['options'])) {
                return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，options应为数组");
            }
            
            $options = $numbers['options'];
            
            // 验证必需的位置字段
            if (!isset($options['bai']) || !isset($options['shi']) || !isset($options['ge'])) {
                return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应包含bai、shi、ge位置数据");
            }
            
            if (!is_array($options['bai']) || !is_array($options['shi']) || !is_array($options['ge'])) {
                return $this->buildErrorResponse("{$itemLabel}位置选号数据应为数组格式");
            }
            
            $baiCount = count($options['bai']);
            $shiCount = count($options['shi']);
            $geCount = count($options['ge']);
            
            // 验证每个位置至少选择1个选项
            if ($baiCount < 1 || $shiCount < 1 || $geCount < 1) {
                return $this->buildErrorResponse("{$itemLabel}每个位置至少需要选择1个选项");
            }
            
            // 验证选项的有效性
            $validBigSmallOptions = ['big', 'small'];
            $validOddEvenOptions = ['odd', 'even'];
            
            foreach (['bai' => $options['bai'], 'shi' => $options['shi'], 'ge' => $options['ge']] as $pos => $posOptions) {
                foreach ($posOptions as $option) {
                    // 检查是否为有效的大小或单双选项
                    if (!in_array($option, $validBigSmallOptions) && !in_array($option, $validOddEvenOptions)) {
                        return $this->buildErrorResponse("{$itemLabel}{$pos}位包含无效选项：{$option}");
                    }
                }
                
                // 去重检查
                $uniqueOptions = array_unique($posOptions);
                if (count($uniqueOptions) !== count($posOptions)) {
                    return $this->buildErrorResponse("{$itemLabel}{$pos}位选项中包含重复项");
                }
            }
            
            // 计算注数：三个位置选中选项数量的乘积
            $calculatedNote = $baiCount * $shiCount * $geCount;
            
            if ($calculatedNote !== $note) {
                return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
            }
            
        } elseif (isset($numbers['pairs'])) {
            // 对子玩法：验证pairs字段
            if (!is_array($numbers['pairs'])) {
                return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，pairs应为数组");
            }
            
            $pairs = $numbers['pairs'];
            $selectedCount = count($pairs);
            
            // 验证至少选择1个对子类型
            if ($selectedCount < 1) {
                return $this->buildErrorResponse("{$itemLabel}对子玩法至少需要选择1个对子类型");
            }
            
            // 验证对子类型的有效性
            $validPairOptions = ['front_pair', 'back_pair', 'leopard'];
            foreach ($pairs as $pair) {
                if (!in_array($pair, $validPairOptions)) {
                    return $this->buildErrorResponse("{$itemLabel}包含无效对子类型：{$pair}");
                }
            }
            
            // 去重检查
            $uniquePairs = array_unique($pairs);
            if (count($uniquePairs) !== count($pairs)) {
                return $this->buildErrorResponse("{$itemLabel}对子类型中包含重复项");
            }
            
            // 对子：注数 = 选中的对子类型数量
            if ($selectedCount !== $note) {
                return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
            }
            
        } elseif (isset($numbers['selected'])) {
            // 兼容旧的数据格式（BigSmallOddEven组件）
            $selected = $numbers['selected'];
            if (!is_array($selected)) {
                return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，selected应为数组");
            }
            
            $selectedCount = count($selected);
            
            // 验证至少选择1个选项
            if ($selectedCount < 1) {
                return $this->buildErrorResponse("{$itemLabel}大小单双至少需要选择1个选项");
            }
            
            // 验证最多选择8个选项（大小单双共8种组合）
            if ($selectedCount > 8) {
                return $this->buildErrorResponse("{$itemLabel}大小单双最多只能选择8个选项");
            }
            
            // 验证选项的有效性
            $validOptions = ['大大大', '大大小', '大小大', '大小小', '小大大', '小大小', '小小大', '小小小'];
            foreach ($selected as $option) {
                if (!in_array($option, $validOptions)) {
                    return $this->buildErrorResponse("{$itemLabel}包含无效选项：{$option}");
                }
            }
            
            // 去重检查
            $uniqueOptions = array_unique($selected);
            if (count($uniqueOptions) !== count($selected)) {
                return $this->buildErrorResponse("{$itemLabel}选项中包含重复项");
            }
            
            // 大小单双：注数 = 选中的组合数
            if ($selectedCount !== $note) {
                return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
            }
            
        } else {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应包含options、pairs或selected字段");
        }
        
        return $this->buildSuccessResponse('形态类验证通过');
    }

    /**
     * 验证和值形态类注数（和值大小、和值单双）
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @param string $typeKey 玩法类型
     * @return array 验证结果
     */
    private function validateHezhiXingtaiNote($numbers, int $note, int $index, string $typeKey): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 处理前端数据格式
        $selectedOptions = [];
        if (isset($numbers['sumOptions'])) {
            $selectedOptions = $numbers['sumOptions'];
        } else {
            $selectedOptions = $numbers;
        }
        
        if (!is_array($selectedOptions)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，sumOptions应为数组");
        }
        
        $selectedCount = count($selectedOptions);
        
        // 验证至少选择1个选项
        if ($selectedCount < 1) {
            return $this->buildErrorResponse("{$itemLabel}和值形态至少需要选择1个选项");
        }
        
        // 根据玩法类型验证选项的有效性
        switch ($typeKey) {
            case 'hezhi_daxiao':
                $validOptions = ['大', '小'];
                $maxSelections = 2;
                break;
                
            case 'hezhi_danshuang':
                $validOptions = ['单', '双'];
                $maxSelections = 2;
                break;
                
            default:
                return $this->buildErrorResponse("{$itemLabel}未知的和值形态类型：{$typeKey}");
        }
        
        // 验证最多选择数量
        if ($selectedCount > $maxSelections) {
            return $this->buildErrorResponse("{$itemLabel}和值形态最多只能选择{$maxSelections}个选项");
        }
        
        // 验证选项的有效性
        foreach ($selectedOptions as $option) {
            if (!in_array($option, $validOptions)) {
                return $this->buildErrorResponse("{$itemLabel}包含无效选项：{$option}，有效选项为：" . implode('、', $validOptions));
            }
        }
        
        // 去重检查
        $uniqueOptions = array_unique($selectedOptions);
        if (count($uniqueOptions) !== count($selectedOptions)) {
            return $this->buildErrorResponse("{$itemLabel}选项中包含重复项");
        }
        
        // 和值形态：注数 = 选中的选项数量
        if ($selectedCount !== $note) {
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('和值形态验证通过');
    }

    /**
     * 验证和值注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateSumNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers) || !isset($numbers['selected'])) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应包含selected字段");
        }
        
        $selected = $numbers['selected'];
        if (!is_array($selected)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，selected应为数组");
        }
        
        $selectedCount = count($selected);
        
        // 验证至少选择1个和值
        if ($selectedCount < 1) {
            return $this->buildErrorResponse("{$itemLabel}和值至少需要选择1个");
        }
        
        // 验证最多选择28个和值（0-27）
        if ($selectedCount > 28) {
            return $this->buildErrorResponse("{$itemLabel}和值最多只能选择28个");
        }
        
        // 验证和值范围
        foreach ($selected as $sum) {
            if (!is_numeric($sum) || $sum < 0 || $sum > 27) {
                return $this->buildErrorResponse("{$itemLabel}包含无效和值：{$sum}，和值范围应为0-27");
            }
        }
        
        // 去重检查
        $uniqueSums = array_unique($selected);
        if (count($uniqueSums) !== count($selected)) {
            return $this->buildErrorResponse("{$itemLabel}和值中包含重复项");
        }
        
        // 和值：注数 = 选中的和值数量
        if ($selectedCount !== $note) {
            Log::warning('和值注数计算不匹配', [
                'index' => $index,
                'selected_count' => $selectedCount,
                'provided' => $note
            ]);
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('和值验证通过');
    }

    /**
     * 验证直选复式注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateDirectComplexNote($numbers, int $note, int $index): array
    {
        // 直选复式与直选定位验证逻辑相同
        return $this->validateDirectSelectNote($numbers, $note, $index);
    }

    /**
     * 验证直选和值注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateDirectSumNote($numbers, int $note, int $index): array
    {
        // 直选和值与和值验证逻辑相同
        return $this->validateSumNote($numbers, $note, $index);
    }

    /**
     * 验证直选跨度注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateDirectSpanNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers) || !isset($numbers['selected'])) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应包含selected字段");
        }
        
        $selected = $numbers['selected'];
        if (!is_array($selected)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，selected应为数组");
        }
        
        $selectedCount = count($selected);
        
        // 验证至少选择1个跨度
        if ($selectedCount < 1) {
            return $this->buildErrorResponse("{$itemLabel}跨度至少需要选择1个");
        }
        
        // 验证最多选择9个跨度（0-9）
        if ($selectedCount > 10) {
            return $this->buildErrorResponse("{$itemLabel}跨度最多只能选择10个");
        }
        
        // 验证跨度范围
        foreach ($selected as $span) {
            if (!is_numeric($span) || $span < 0 || $span > 9) {
                return $this->buildErrorResponse("{$itemLabel}包含无效跨度：{$span}，跨度范围应为0-9");
            }
        }
        
        // 去重检查
        $uniqueSpans = array_unique($selected);
        if (count($uniqueSpans) !== count($selected)) {
            return $this->buildErrorResponse("{$itemLabel}跨度中包含重复项");
        }
        
        // 跨度：注数 = 选中的跨度数量
        if ($selectedCount !== $note) {
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('直选跨度验证通过');
    }

    /**
     * 验证定位类注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @param string $typeKey 玩法类型
     * @return array 验证结果
     */
    private function validatePositionNote($numbers, int $note, int $index, string $typeKey): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 根据不同定位类型验证数据格式和计算注数
        switch ($typeKey) {
            case 'zuxuan_yima_dingwei':
                // 一码定位：验证position和numbers字段
                if (!isset($numbers['position']) || !isset($numbers['numbers'])) {
                    return $this->buildErrorResponse("{$itemLabel}一码定位数据格式错误，应包含position和numbers字段");
                }
                
                if (!is_array($numbers['numbers'])) {
                    return $this->buildErrorResponse("{$itemLabel}一码定位numbers字段应为数组");
                }
                
                $selectedNumbers = $numbers['numbers'];
                $selectedCount = count($selectedNumbers);
                
                // 验证选择数量
                if ($selectedCount < 1) {
                    return $this->buildErrorResponse("{$itemLabel}一码定位至少需要选择1个号码");
                }
                if ($selectedCount > 10) {
                    return $this->buildErrorResponse("{$itemLabel}一码定位最多只能选择10个号码");
                }
                
                // 验证选号范围
                foreach ($selectedNumbers as $num) {
                    if (!is_numeric($num) || $num < 0 || $num > 9) {
                        return $this->buildErrorResponse("{$itemLabel}包含无效号码：{$num}");
                    }
                }
                
                // 去重检查
                $uniqueNumbers = array_unique($selectedNumbers);
                if (count($uniqueNumbers) !== count($selectedNumbers)) {
                    return $this->buildErrorResponse("{$itemLabel}选号中包含重复号码");
                }
                
                // 一码定位：注数 = 选中的号码数量
                if ($selectedCount !== $note) {
                    return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
                }
                break;
                
            case 'zuxuan_liangma_dingwei':
                // 两码定位：验证positionCombo和numbers字段
                if (!isset($numbers['positionCombo']) || !isset($numbers['numbers'])) {
                    return $this->buildErrorResponse("{$itemLabel}两码定位数据格式错误，应包含positionCombo和numbers字段");
                }
                
                if (!is_array($numbers['numbers'])) {
                    return $this->buildErrorResponse("{$itemLabel}两码定位numbers字段应为数组");
                }
                
                $positionCombo = $numbers['positionCombo'];
                $positionNumbers = $numbers['numbers'];
                
                // 根据位置组合获取需要验证的位置
                $positions = [];
                switch ($positionCombo) {
                    case 'bai_shi':
                        $positions = ['bai', 'shi'];
                        break;
                    case 'bai_ge':
                        $positions = ['bai', 'ge'];
                        break;
                    case 'shi_ge':
                        $positions = ['shi', 'ge'];
                        break;
                    default:
                        return $this->buildErrorResponse("{$itemLabel}无效的位置组合：{$positionCombo}");
                }
                
                $calculatedNote = 1;
                foreach ($positions as $pos) {
                    if (!isset($positionNumbers[$pos]) || !is_array($positionNumbers[$pos])) {
                        return $this->buildErrorResponse("{$itemLabel}缺少{$pos}位选号数据");
                    }
                    
                    $posNumbers = $positionNumbers[$pos];
                    $posCount = count($posNumbers);
                    
                    // 验证每个位置至少选择1个号码
                    if ($posCount < 1) {
                        return $this->buildErrorResponse("{$itemLabel}{$pos}位至少需要选择1个号码");
                    }
                    if ($posCount > 10) {
                        return $this->buildErrorResponse("{$itemLabel}{$pos}位最多只能选择10个号码");
                    }
                    
                    // 验证选号范围
                    foreach ($posNumbers as $num) {
                        if (!is_numeric($num) || $num < 0 || $num > 9) {
                            return $this->buildErrorResponse("{$itemLabel}{$pos}位包含无效号码：{$num}");
                        }
                    }
                    
                    // 去重检查
                    $uniqueNumbers = array_unique($posNumbers);
                    if (count($uniqueNumbers) !== count($posNumbers)) {
                        return $this->buildErrorResponse("{$itemLabel}{$pos}位选号中包含重复号码");
                    }
                    
                    $calculatedNote *= $posCount;
                }
                
                // 两码定位：注数 = 两个位置选中号码数量的乘积
                if ($calculatedNote !== $note) {
                    return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
                }
                break;
                
            case 'zuxuan_yima_budingwei':
                // 一码不定位：验证numbers字段
                if (!isset($numbers['numbers'])) {
                    return $this->buildErrorResponse("{$itemLabel}一码不定位数据格式错误，应包含numbers字段");
                }
                
                if (!is_array($numbers['numbers'])) {
                    return $this->buildErrorResponse("{$itemLabel}一码不定位numbers字段应为数组");
                }
                
                $selectedNumbers = $numbers['numbers'];
                $selectedCount = count($selectedNumbers);
                
                // 验证选择数量
                if ($selectedCount < 1) {
                    return $this->buildErrorResponse("{$itemLabel}一码不定位至少需要选择1个号码");
                }
                if ($selectedCount > 10) {
                    return $this->buildErrorResponse("{$itemLabel}一码不定位最多只能选择10个号码");
                }
                
                // 验证选号范围
                foreach ($selectedNumbers as $num) {
                    if (!is_numeric($num) || $num < 0 || $num > 9) {
                        return $this->buildErrorResponse("{$itemLabel}包含无效号码：{$num}");
                    }
                }
                
                // 去重检查
                $uniqueNumbers = array_unique($selectedNumbers);
                if (count($uniqueNumbers) !== count($selectedNumbers)) {
                    return $this->buildErrorResponse("{$itemLabel}选号中包含重复号码");
                }
                
                // 一码不定位：注数 = 选中的号码数量
                if ($selectedCount !== $note) {
                    return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
                }
                break;
                
            default:
                return $this->buildErrorResponse("{$itemLabel}未知的定位类型：{$typeKey}");
        }
        
        return $this->buildSuccessResponse('定位类验证通过');
    }

    /**
     * 验证跨度注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateSpanNote($numbers, int $note, int $index): array
    {
        // 跨度与直选跨度验证逻辑相同
        return $this->validateDirectSpanNote($numbers, $note, $index);
    }

    /**
     * 验证组三单式注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateGroupThreeSingleNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 验证必需字段
        if (!isset($numbers['valid']) || !is_array($numbers['valid'])) {
            return $this->buildErrorResponse("{$itemLabel}缺少有效号码数据");
        }
        
        $validNumbers = $numbers['valid'];
        $selectedCount = count($validNumbers);
        
        // 验证至少有1个有效号码
        if ($selectedCount < 1) {
            return $this->buildErrorResponse("{$itemLabel}组三单式至少需要输入1个有效号码");
        }
        
        // 验证每个号码的格式
        foreach ($validNumbers as $number) {
            if (!$this->isValidGroupThreeNumber($number)) {
                return $this->buildErrorResponse("{$itemLabel}包含无效的组三号码：{$number}");
            }
        }
        
        // 去重检查
        $uniqueNumbers = array_unique($validNumbers);
        if (count($uniqueNumbers) !== count($validNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}号码中包含重复项");
        }
        
        // 组三单式：注数 = 有效号码数量
        if ($selectedCount !== $note) {
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$selectedCount}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('组三单式验证通过');
    }
    
    /**
     * 验证是否为有效的组三号码
     * 
     * @param string $number 号码
     * @return bool 是否有效
     */
    private function isValidGroupThreeNumber(string $number): bool
    {
        // 必须是3位数字
        if (!preg_match('/^\d{3}$/', $number)) {
            return false;
        }
        
        $digits = str_split($number);
        $counts = array_count_values($digits);
        $countValues = array_values($counts);
        
        // 组三：必须有一个数字出现2次，另一个数字出现1次
        return in_array(2, $countValues) && in_array(1, $countValues) && count($countValues) === 2;
    }

    /**
     * 验证组三拖胆注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateGroupThreeDragNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 验证必需字段
        if (!isset($numbers['dan']) || !is_array($numbers['dan'])) {
            return $this->buildErrorResponse("{$itemLabel}缺少胆码数据");
        }
        
        if (!isset($numbers['tuo']) || !is_array($numbers['tuo'])) {
            return $this->buildErrorResponse("{$itemLabel}缺少拖码数据");
        }
        
        $danNumbers = $numbers['dan'];
        $tuoNumbers = $numbers['tuo'];
        
        $danCount = count($danNumbers);
        $tuoCount = count($tuoNumbers);
        
        // 验证胆码数量（必须为1个）
        if ($danCount !== 1) {
            return $this->buildErrorResponse("{$itemLabel}组三拖胆必须选择1个胆码，实际选择{$danCount}个");
        }
        
        // 验证拖码数量（至少1个）
        if ($tuoCount < 1) {
            return $this->buildErrorResponse("{$itemLabel}组三拖胆至少需要选择1个拖码");
        }
        
        // 验证胆码范围
        foreach ($danNumbers as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return $this->buildErrorResponse("{$itemLabel}胆码包含无效号码：{$num}");
            }
        }
        
        // 验证拖码范围
        foreach ($tuoNumbers as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return $this->buildErrorResponse("{$itemLabel}拖码包含无效号码：{$num}");
            }
        }
        
        // 验证胆码和拖码不能重复
        $intersection = array_intersect($danNumbers, $tuoNumbers);
        if (!empty($intersection)) {
            return $this->buildErrorResponse("{$itemLabel}胆码和拖码不能重复，重复号码：" . implode(',', $intersection));
        }
        
        // 去重检查
        if (count(array_unique($danNumbers)) !== count($danNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}胆码中包含重复号码");
        }
        if (count(array_unique($tuoNumbers)) !== count($tuoNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}拖码中包含重复号码");
        }
        
        // 组三拖胆：注数 = 拖码数量
        $calculatedNote = $tuoCount;
        
        if ($calculatedNote !== $note) {
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('组三拖胆验证通过');
    }

    /**
     * 验证组六拖胆注数
     * 
     * @param mixed $numbers 选号数据
     * @param int $note 注数
     * @param int $index 投注项索引
     * @return array 验证结果
     */
    private function validateGroupSixDragNote($numbers, int $note, int $index): array
    {
        $itemLabel = "投注项" . ($index + 1);
        
        if (!is_array($numbers)) {
            return $this->buildErrorResponse("{$itemLabel}选号数据格式错误，应为数组格式");
        }
        
        // 验证必需字段
        if (!isset($numbers['dan']) || !is_array($numbers['dan'])) {
            return $this->buildErrorResponse("{$itemLabel}缺少胆码数据");
        }
        
        if (!isset($numbers['tuo']) || !is_array($numbers['tuo'])) {
            return $this->buildErrorResponse("{$itemLabel}缺少拖码数据");
        }
        
        $danNumbers = $numbers['dan'];
        $tuoNumbers = $numbers['tuo'];
        
        $danCount = count($danNumbers);
        $tuoCount = count($tuoNumbers);
        
        // 验证胆码数量（1-2个）
        if ($danCount < 1 || $danCount > 2) {
            return $this->buildErrorResponse("{$itemLabel}组六拖胆胆码数量应为1-2个，实际选择{$danCount}个");
        }
        
        // 验证拖码数量
        $minTuoCount = 3 - $danCount;
        if ($tuoCount < $minTuoCount) {
            return $this->buildErrorResponse("{$itemLabel}组六拖胆至少需要选择{$minTuoCount}个拖码，实际选择{$tuoCount}个");
        }
        
        // 验证胆码范围
        foreach ($danNumbers as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return $this->buildErrorResponse("{$itemLabel}胆码包含无效号码：{$num}");
            }
        }
        
        // 验证拖码范围
        foreach ($tuoNumbers as $num) {
            if (!is_numeric($num) || $num < 0 || $num > 9) {
                return $this->buildErrorResponse("{$itemLabel}拖码包含无效号码：{$num}");
            }
        }
        
        // 验证胆码和拖码不能重复
        $intersection = array_intersect($danNumbers, $tuoNumbers);
        if (!empty($intersection)) {
            return $this->buildErrorResponse("{$itemLabel}胆码和拖码不能重复，重复号码：" . implode(',', $intersection));
        }
        
        // 去重检查
        if (count(array_unique($danNumbers)) !== count($danNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}胆码中包含重复号码");
        }
        if (count(array_unique($tuoNumbers)) !== count($tuoNumbers)) {
            return $this->buildErrorResponse("{$itemLabel}拖码中包含重复号码");
        }
        
        // 组六拖胆注数计算
        $calculatedNote = 0;
        if ($danCount === 0 && $tuoCount >= 3) {
            // 无胆码：C(拖码数, 3)
            $calculatedNote = $this->combination($tuoCount, 3);
        } elseif ($danCount === 1 && $tuoCount >= 2) {
            // 1个胆码：C(拖码数, 2)
            $calculatedNote = $this->combination($tuoCount, 2);
        } elseif ($danCount === 2 && $tuoCount >= 1) {
            // 2个胆码：拖码数
            $calculatedNote = $tuoCount;
        }
        
        if ($calculatedNote !== $note) {
            return $this->buildErrorResponse("{$itemLabel}注数错误，应为{$calculatedNote}注，实际为{$note}注");
        }
        
        return $this->buildSuccessResponse('组六拖胆验证通过');
    }

    /**
     * 计算组合数 C(n, r)
     * 
     * @param int $n 总数
     * @param int $r 选择数
     * @return int 组合数
     */
    private function combination(int $n, int $r): int
    {
        if ($r > $n || $r < 0) return 0;
        if ($r === 0 || $r === $n) return 1;
        
        $result = 1;
        for ($i = 0; $i < $r; $i++) {
            $result = $result * ($n - $i) / ($i + 1);
        }
        return (int)round($result);
    }
}