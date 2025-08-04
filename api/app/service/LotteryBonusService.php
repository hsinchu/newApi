<?php

namespace app\service;
use app\common\model\LotteryBonus;
use app\common\model\LotteryType;
use think\exception\ValidateException;

/**
 * 彩票奖金服务类
 * 处理彩票奖金配置相关的业务逻辑
 */
class LotteryBonusService
{

    /**
     * 获取彩种赔率
     */
    public static function getOneBonus($where)
    {
        return LotteryBonus::where($where)->value('bonus_json');
    }

    /**
     * 通过type_id查询所有已开启的记录，按weigh排序
     * @param int $typeId 彩种ID
     * @return array
     */
    public static function getBonusByTypeId($typeId)
    {
        return LotteryBonus::where('lottery_id', $typeId)
            ->where('status', 1)
            ->order('weigh', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 通过type_id+key查询bonus_json值
     * @param int $typeId 彩种ID
     * @param string $key 键值
     * @return string|null
     */
    public static function getBonusJsonByTypeIdAndKey($typeId, $key)
    {
        return LotteryBonus::where('lottery_id', $typeId)
            ->where('key', $key)
            ->where('status', 1)
            ->value('bonus_json');
    }

    /**
     * 验证bonus值是否存在于记录的bonus_json中
     * @param int $typeId 彩种ID
     * @param string $key 键值
     * @param string $bonus 要验证的bonus值
     * @return array
     */
    public static function validateBonus($typeId, $key, $bonus)
    {
        $bonusJson = self::getBonusJsonByTypeIdAndKey($typeId, $key);
        
        if (!$bonusJson) {
            return [
                'code' => 0,
                'msg' => '未找到对应的赔率配置'
            ];
        }
        
        try {
            $bonusData = json_decode($bonusJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'code' => 0,
                    'msg' => '赔率配置格式错误'
                ];
            }
            
            // 处理不同的数据格式
            if (is_array($bonusData)) {
                // 数组格式：查找匹配的bonus值
                foreach ($bonusData as $item) {
                    if (is_array($item) && isset($item['key']) && isset($item['value'])) {
                        // 对象数组格式：[{"key":"直选","value":"1000"}]
                        if ($item['key'] == $bonus || $item['value'] == $bonus) {
                            return [
                                'code' => 1,
                                'data' => [$item['key'] => $item['value']]
                            ];
                        }
                    } else {
                        // 简单数组格式：["1000", "333"]
                        if ($item == $bonus) {
                            return [
                                'code' => 1,
                                'data' => [$bonus => $bonus]
                            ];
                        }
                    }
                }
            } else if (is_object($bonusData)) {
                // 对象格式：{"直选":"1000","组选":"333"}
                foreach ($bonusData as $k => $v) {
                    if ($k == $bonus || $v == $bonus) {
                        return [
                            'code' => 1,
                            'data' => [$k => $v]
                        ];
                    }
                }
            }
            
            return [
                'code' => 0,
                'msg' => '未找到匹配的bonus值'
            ];
            
        } catch (\Exception $e) {
            return [
                'code' => 0,
                'msg' => '数据解析失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 根据彩种代码和投注号码获取赔率
     * @param string $lotteryCode 彩种代码
     * @param string $numbers 投注号码
     * @return float
     */
    public function getTypeKeyBonus($lotteryCode, $numbers)
    {
        try {
            // 根据彩种代码获取彩种ID
            $lotteryType = \app\common\model\LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lotteryType) {
                return 1.0;
            }
            
            // 根据投注号码确定投注类型
            $betType = $this->determineBetType($numbers);
            
            // 获取对应的赔率配置
            $bonusRecord = LotteryBonus::where('lottery_id', $lotteryType->id)
                ->where('key', $betType)
                ->where('status', 1)
                ->find();
                
            if (!$bonusRecord || !$bonusRecord->bonus_json) {
                return 1.0;
            }
            
            $bonusData = $bonusRecord->bonus_json;
            
            // 根据具体号码获取赔率
            if (is_array($bonusData)) {
                foreach ($bonusData as $item) {
                    if (is_array($item) && isset($item['key']) && isset($item['value'])) {
                        if ($item['key'] == $numbers) {
                            return (float)$item['value'];
                        }
                    }
                }
                // 如果没有找到具体号码的赔率，返回第一个赔率
                if (!empty($bonusData) && is_array($bonusData[0]) && isset($bonusData[0]['value'])) {
                    return (float)$bonusData[0]['value'];
                }
            }
            
            return 1.0;
        } catch (\Exception $e) {
            return 1.0;
        }
    }
    
    /**
     * 根据彩种ID和投注类型获取赔率配置
     * @param int $lotteryId 彩种ID
     * @param string $betType 投注类型
     * @return array|null
     */
    public function getBonusByTypeIdAndKey($lotteryId, $betType)
    {
        try {
            
            // 获取对应的赔率配置
            $bonusRecord = LotteryBonus::where('lottery_id', $lotteryId)
                ->where('key', $betType)
                ->where('status', 1)
                ->find();
                
            if (!$bonusRecord) {
                return null;
            }
            
            return [
                'id' => $bonusRecord->id,
                'lottery_id' => $bonusRecord->lottery_id,
                'type' => $bonusRecord->type,
                'type_name' => $bonusRecord->type_name,
                'key' => $bonusRecord->key,
                'name' => $bonusRecord->name,
                'min_price' => $bonusRecord->min_price,
                'max_price' => $bonusRecord->max_price,
                'bonus_json' => $bonusRecord->bonus_json
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 根据彩种代码和投注类型获取赔率配置（向后兼容）
     * @param string $lotteryCode 彩种代码
     * @param string $betType 投注类型
     * @return array|null
     */
    public function getBonusByTypeAndKey($lotteryCode, $betType)
    {
        try {
            // 根据彩种代码获取彩种ID
            $lotteryType = \app\common\model\LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lotteryType) {
                return null;
            }
            
            return $this->getBonusByTypeIdAndKey($lotteryType->id, $betType);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 根据投注号码确定投注类型（向后兼容）
     * @param string $numbers 投注号码
     * @return string
     */
    private function determineBetType($numbers)
    {
        // 根据号码内容判断投注类型
        if (in_array($numbers, ['大'])) {
            return 'da';
        }
        if (in_array($numbers, ['小'])) {
            return 'xiao';
        }
        if ($numbers == '和') {
            return 'he';
        }
        
        // 默认返回和类型
        return 'he';
    }
    
    /**
     * 检查投注金额限制和完整验证
     * @param array $betData 投注数据
     * @param int $lotteryTypeId 彩种ID
     * @return bool
     * @throws \Exception
     */
    public function checkBetAmountLimit(array $betData, int $lotteryTypeId): bool
    {
        // 获取彩种信息
        $lotteryType = LotteryType::find($lotteryTypeId);
        if (!$lotteryType) {
            throw new \Exception('彩种不存在');
        }
        if ($lotteryType['is_enabled'] != 1) {
            throw new \Exception('彩种暂未开放');
        }

        // 验证投注数据格式
        if (!is_array($betData) || empty($betData)) {
            throw new \Exception('投注数据不能为空');
        }

        foreach ($betData as $index => $betItem) {
            // 验证投注项格式
            if (!is_array($betItem)) {
                throw new \Exception("投注项{$index}格式错误");
            }
            
            // 验证必填字段
            $itemRequiredFields = ['type_key', 'type_name', 'numbers', 'note', 'money', 'multiplier', 'bonus'];
            foreach ($itemRequiredFields as $field) {
                if (!isset($betItem[$field]) || $betItem[$field] === '') {
                    throw new \Exception("投注项{$index}缺少{$field}字段");
                }
            }
            
            // 基础数据类型验证
            $betAmount = (float)($betItem['money'] ?? 0);
            $bonus = isset($betItem['bonus']) ? (float)$betItem['bonus'] : null;
            $typeKey = $betItem['type_key'] ?? '';
            $typeName = $betItem['type_name'] ?? '';
            $note = (int)($betItem['note'] ?? 0);
            $multiplier = (int)($betItem['multiplier'] ?? 1);
            
            // 验证投注金额基础条件
            if ($betAmount <= 0) {
                throw new \Exception("投注项{$index}金额必须大于0");
            }
            
            // 验证倍数
            if ($multiplier <= 0 || $multiplier > 999) {
                throw new \Exception("投注项{$index}倍数必须在1-999之间");
            }
            
            // 动态验证投注类型 - 检查该彩种是否支持此投注类型
            $bonusInfo = $this->getBonusByTypeIdAndKey($lotteryType['id'], $typeKey);
            if(!$bonusInfo){
                throw new \Exception("投注项{$index}投注类型[{$typeName}]的type_key[{$typeKey}]不存在或未开启");
            }
            
            // 验证type_key和type_name是否匹配
            if ($bonusInfo['type'] !== $typeKey || $bonusInfo['type_name'] !== $typeName) {
                throw new \Exception("投注项{$index}的type_key或type_name与配置不匹配");
            }
            
            // 验证注数
            if ($note <= 0) {
                throw new \Exception("投注项{$index}注数必须大于0");
            }
            
            // 计算有效的最小和最大限制（按照quickGame.vue的逻辑）
            $gameMinBet = $lotteryType->min_bet_amount ?? 1;
            $gameMaxBet = $lotteryType->max_bet_amount ?? 0;
            
            $playMinPrice = 0;
            $playMaxPrice = 0;
            
            if ($bonusInfo && isset($bonusInfo['bonus_json'])) {
                $bonusJson = is_string($bonusInfo['bonus_json']) ? 
                    json_decode($bonusInfo['bonus_json'], true) : 
                    $bonusInfo['bonus_json'];
                    
                if ($bonusJson) {
                    $playMinPrice = $bonusJson['min_price'] ?? 0;
                    $playMaxPrice = $bonusJson['max_price'] ?? 0;
                }
            }
            
            $minLimit = max($playMinPrice, $gameMinBet);
            $maxLimit = $gameMaxBet;
            
            if ($playMaxPrice > 0) {
                $maxLimit = $gameMaxBet > 0 ? min($playMaxPrice, $gameMaxBet) : $playMaxPrice;
            }
            
            // 验证投注金额限制
            if ($betAmount < $minLimit) {
                throw new \Exception("投注项{$index}投注金额不能少于{$minLimit}元");
            }
            
            if ($maxLimit > 0 && $betAmount > $maxLimit) {
                throw new \Exception("投注项{$index}投注金额不能超过{$maxLimit}元");
            }
        }
        
        return true;
    }
    
    /**
     * 验证投注参数（完整验证包含基础参数）
     * @param array $params 完整的投注参数
     * @return array 验证结果
     */
    public function validateBetParams(array $params): array
    {
        // 必填参数检查
        $requiredFields = [
            'lottery_code' => '彩种代码',
            'period_no' => '期号',
            'bet_data' => '投注内容'
        ];
        
        foreach ($requiredFields as $field => $name) {
            if (!isset($params[$field]) || $params[$field] === '') {
                return ['code' => 0, 'msg' => $name . '不能为空'];
            }
        }
        
        // 数据类型和范围验证
        $validatedData = [];
        
        // 彩种代码验证
        $validatedData['lottery_code'] = trim($params['lottery_code']);
        if (strlen($validatedData['lottery_code']) > 50) {
            return ['code' => 0, 'msg' => '彩种代码长度不能超过50个字符'];
        }
        
        // 期号验证
        $validatedData['period_no'] = trim($params['period_no']);
        if (strlen($validatedData['period_no']) > 50) {
            return ['code' => 0, 'msg' => '期号长度不能超过50个字符'];
        }
        
        // 投注数据验证
        if (is_string($params['bet_data'])) {
            $betData = json_decode($params['bet_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['code' => 0, 'msg' => '投注数据格式错误'];
            }
        } else {
            $betData = $params['bet_data'];
        }
        
        if (!is_array($betData) || empty($betData)) {
            return ['code' => 0, 'msg' => '投注数据不能为空'];
        }
        
        // 获取彩种ID
        $lotteryType = LotteryType::where('type_code', $validatedData['lottery_code'])->find();
        if (!$lotteryType) {
            return ['code' => 0, 'msg' => '彩种不存在'];
        }
        
        // 使用统一的验证方法
        try {
            $this->checkBetAmountLimit($betData, $lotteryType->id);
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
        
        $validatedData['bet_data'] = $betData;
        
        // 计算总金额（考虑每个投注项的倍数）
        $totalAmount = 0;
        foreach ($betData as $betItem) {
            $itemMultiplier = (int)($betItem['multiplier'] ?? 1);
            $totalAmount += (float)$betItem['money'] * $itemMultiplier;
        }
        $validatedData['total_amount'] = $totalAmount;
        
        if ($validatedData['total_amount'] > 100000000) { // 最大100万元
            return ['code' => 0, 'msg' => '总投注金额不能超过100万元'];
        }
        
        return ['code' => 1, 'msg' => '验证通过', 'data' => $validatedData];
    }
}