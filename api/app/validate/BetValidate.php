<?php

namespace app\validate;

use think\Validate;
use think\exception\ValidateException;
use think\facade\Log;
use think\facade\Config;
use app\common\model\LotteryBonus;
use app\common\model\LotteryType;

/**
 * 投注通用验证类
 * 
 * 提供投注参数的基础验证功能，包括：
 * - 彩种代码验证
 * - 期号验证
 * - 投注数据格式验证
 * - 金额计算验证
 * 
 * @author BuildAdmin Team
 * @version 1.0.0
 */
class BetValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'lottery_code' => 'require|alphaNum|length:2,10',
        'period_no' => 'require|alphaNum|length:1,20',
        'bet_data' => 'require|array',
        'total_amount' => 'require|number|gt:0|lt:1000000'
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'lottery_code.require' => '彩种代码不能为空',
        'lottery_code.alphaNum' => '彩种代码只能包含字母和数字',
        'lottery_code.length' => '彩种代码长度必须在2-10位之间',
        'period_no.require' => '期号不能为空',
        'period_no.alphaNum' => '期号只能包含字母和数字',
        'period_no.length' => '期号长度不能超过20位',
        'bet_data.require' => '投注数据不能为空',
        'bet_data.array' => '投注数据格式错误',
        'total_amount.require' => '投注金额不能为空',
        'total_amount.number' => '投注金额必须为数字',
        'total_amount.gt' => '投注金额必须大于0',
        'total_amount.lt' => '单次投注金额不能超过100万元'
    ];

    /**
     * 最小投注金额
     * @var float
     */
    protected $minBetAmount = 2.0;

    /**
     * 最大投注金额
     * @var float
     */
    protected $maxBetAmount = 10000.0;

    /**
     * 最大投注项数
     * @var int
     */
    protected $maxBetItems = 1000;

    /**
     * 验证投注参数
     * 
     * @param array $params 投注参数
     * @return array 验证结果 ['code' => int, 'msg' => string, 'data' => array]
     */
    public function validateBetParams(array $params): array
    {
        $startTime = microtime(true);
        
        try {
            // 记录验证开始日志
            Log::info('开始投注参数验证', [
                'lottery_code' => $params['lottery_code'] ?? '',
                'period_no' => $params['period_no'] ?? '',
                'bet_items_count' => count($params['bet_data'] ?? [])
            ]);

            // 基础参数验证
            if (!$this->check($params)) {
                $error = $this->getError();
                Log::warning('投注参数基础验证失败', ['error' => $error, 'params' => $params]);
                return $this->buildErrorResponse($error);
            }

            // 验证投注数据格式
            $betData = $params['bet_data'];
            if (empty($betData) || !is_array($betData)) {
                return $this->buildErrorResponse('投注数据不能为空');
            }

            // 验证投注项数量限制
            if (count($betData) > $this->maxBetItems) {
                return $this->buildErrorResponse("单次投注项数不能超过{$this->maxBetItems}项");
            }

            $totalAmount = 0;
            $validatedBetData = [];
            
            foreach ($betData as $index => $betItem) {
                // 验证单个投注项
                $itemValidation = $this->validateBetItem($betItem, $index, $params['lottery_code'] ?? '');
                if ($itemValidation['code'] != 1) {
                    Log::warning('投注项验证失败', [
                        'index' => $index,
                        'error' => $itemValidation['msg'],
                        'bet_item' => $betItem
                    ]);
                    return $itemValidation;
                }
                
                // 计算总金额
                $money = (float)($betItem['money'] ?? 0);
                $multiplier = (int)($betItem['multiplier'] ?? 1);
                $note = (int)($betItem['note'] ?? 1);
                $itemAmount = $money * $multiplier * $note;
                $totalAmount += $itemAmount;
                
                // 验证单项金额范围
                if ($itemAmount < $this->minBetAmount) {
                    return $this->buildErrorResponse("投注项" . ($index + 1) . "金额不能低于{$this->minBetAmount}元");
                }
                
                if ($itemAmount > $this->maxBetAmount) {
                    return $this->buildErrorResponse("投注项" . ($index + 1) . "金额不能超过{$this->maxBetAmount}元");
                }
                
                $validatedBetData[] = array_merge($betItem, ['calculated_amount' => $itemAmount]);
            }

            // 验证总金额是否匹配（允许小数点精度误差）
            $expectedAmount = (float)$params['total_amount'];
            if (abs($totalAmount - $expectedAmount) > 0.01) {
                Log::warning('投注金额计算不匹配', [
                    'calculated' => $totalAmount,
                    'expected' => $expectedAmount,
                    'difference' => abs($totalAmount - $expectedAmount)
                ]);
                return $this->buildErrorResponse("投注金额计算错误，计算金额：{$totalAmount}元，提交金额：{$expectedAmount}元");
            }

            $result = [
                'code' => 1,
                'msg' => '验证通过',
                'data' => [
                    'lottery_code' => $params['lottery_code'],
                    'period_no' => $params['period_no'],
                    'bet_data' => $validatedBetData,
                    'total_amount' => $totalAmount,
                    'validation_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
                ]
            ];
            
            Log::info('投注参数验证成功', [
                'lottery_code' => $params['lottery_code'],
                'total_amount' => $totalAmount,
                'bet_items' => count($validatedBetData),
                'validation_time' => $result['data']['validation_time']
            ]);
            
            return $result;

        } catch (\Exception $e) {
            Log::error('投注参数验证异常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'params' => $params
            ]);
            return $this->buildErrorResponse('参数验证异常，请稍后重试');
        }
    }

    /**
     * 验证单个投注项
     * 
     * @param array $betItem 投注项数据
     * @param int $index 投注项索引
     * @param string $lotteryCode 彩种代码（可选）
     * @return array 验证结果
     */
    protected function validateBetItem(array $betItem, int $index, string $lotteryCode = ''): array
    {
        $itemLabel = "投注项" . ($index + 1);
        $requiredFields = ['type_key', 'type_name', 'money', 'note'];
        
        // 验证必要字段
        foreach ($requiredFields as $field) {
            if (!isset($betItem[$field]) || $betItem[$field] === '' || $betItem[$field] === null) {
                return $this->buildErrorResponse("{$itemLabel}缺少必要字段：{$field}");
            }
        }

        // 验证玩法类型
        $typeKey = trim($betItem['type_key']);
        $typeName = trim($betItem['type_name']);
        if (empty($typeKey)) {
            return $this->buildErrorResponse("{$itemLabel}玩法类型不能为空");
        }
        if (empty($typeName)) {
            return $this->buildErrorResponse("{$itemLabel}玩法名称不能为空");
        }

        // 验证type_key和type_name是否在lottery_bonus表中存在，并获取限额信息
        $bonusValidation = $this->validateTypeKeyAndName($typeKey, $typeName, $lotteryCode);
        if ($bonusValidation['code'] != 1) {
            return $this->buildErrorResponse("{$itemLabel}{$bonusValidation['msg']}");
        }
        
        // 获取该玩法的限额信息
        $bonusInfo = $bonusValidation['data']['bonus_info'] ?? null;
        if (!$bonusInfo) {
            return $this->buildErrorResponse("{$itemLabel}无法获取玩法限额信息");
        }

        // 验证单注金额 - 根据新的验证规则
        $money = (float)($betItem['money'] ?? 0);
        $bonusMinPrice = (float)($bonusInfo['min_price'] ?? 2.0);
        $bonusMaxPrice = (float)($bonusInfo['max_price'] ?? 10000.0);
        $bonusType = (int)($bonusInfo['bonus_type'] ?? 1);
        $lotteryMinBet = (float)($bonusInfo['lottery_min_bet'] ?? 2.0);
        $lotteryMaxBet = (float)($bonusInfo['lottery_max_bet'] ?? 10000.0);
        
        if ($money <= 0) {
            return $this->buildErrorResponse("{$itemLabel}投注金额必须大于0");
        }
        // 根据bonus_type进行不同的验证
        if ($bonusType == 1) {
            // bonus_type=1时，投注金额必须为lottery_bonus的min_price，只能加倍数
            if ($money != $bonusMinPrice) {
                return $this->buildErrorResponse("数据变动，请清空投注后返回重新进入");
                // return $this->buildErrorResponse("{$itemLabel}此玩法投注金额必须为{$bonusMinPrice}元，不能更改，只能通过倍数调整总金额");
            }
        } else {
            // bonus_type=1时，按照原逻辑验证
            // 最小投注金额取较小值
            $minBetAmount = min($bonusMinPrice, $lotteryMinBet);
            // 最大投注金额取较大值
            $maxBetAmount = max($bonusMaxPrice, $lotteryMaxBet);
            
            if ($money < $minBetAmount) {
                return $this->buildErrorResponse("{$itemLabel}单注金额不能低于{$minBetAmount}元");
            }
            if ($money > $maxBetAmount) {
                return $this->buildErrorResponse("{$itemLabel}单注金额不能超过{$maxBetAmount}元");
            }
        }

        // 验证注数
        $note = (int)($betItem['note'] ?? 0);
        if ($note <= 0) {
            return $this->buildErrorResponse("{$itemLabel}注数必须大于0");
        }
        if ($note > 10000) {
            return $this->buildErrorResponse("{$itemLabel}注数不能超过10000注");
        }

        // 验证倍数
        $multiplier = (int)($betItem['multiplier'] ?? 1);
        if ($multiplier <= 0) {
            return $this->buildErrorResponse("{$itemLabel}倍数必须大于0");
        }
        if ($multiplier > 999) {
            return $this->buildErrorResponse("{$itemLabel}倍数不能超过999倍");
        }

        // 验证总金额（单注金额 × 注数 × 倍数）
        $totalItemAmount = $money * $note * $multiplier;
        
        // 根据bonus_type确定最大金额限制
        if ($bonusType == 1) {
            $maxSingleAmount = $bonusMinPrice;
        } else {
            $maxSingleAmount = max($bonusMaxPrice, $lotteryMaxBet);
        }
        
        $maxTotalAmount = $maxSingleAmount * $note * $multiplier;
        
        if ($totalItemAmount > $maxTotalAmount) {
            return $this->buildErrorResponse("{$itemLabel}总投注金额{$totalItemAmount}元超过限额{$maxTotalAmount}元");
        }

        return ['code' => 1, 'msg' => '验证通过'];
    }

    /**
     * 验证type_key和type_name是否在lottery_bonus表中存在
     * 
     * @param string $typeKey 玩法类型键值
     * @param string $typeName 玩法名称
     * @param string $lotteryCode 彩种代码
     * @return array 验证结果
     */
    private function validateTypeKeyAndName(string $typeKey, string $typeName, string $lotteryCode): array
    {
        try {
            // 根据lottery_code获取lottery_id和彩种信息
            $lottery = LotteryType::where('type_code', $lotteryCode)->find();
            if (!$lottery) {
                Log::error("Lottery not found for code: {$lotteryCode}");
                return $this->buildErrorResponse("彩种代码无效: {$lotteryCode}");
            }
            
            // 检查lottery_bonus表中是否存在对应的type_key和type_name
             $bonusRecord = LotteryBonus::where('lottery_id', $lottery->id)
                 ->where('type_key', $typeKey)
                 ->where('type_name', $typeName)
                 ->find();
                 
             if (!$bonusRecord) {
                 Log::warning("Type key/name not found in lottery_bonus: lottery_id={$lottery->id}, type_key={$typeKey}, type_name={$typeName}");
                 return $this->buildErrorResponse("投注类型无效: {$typeKey} - {$typeName}");
             }
             
             // 构建限额信息，包含彩种和玩法的限额信息
             $bonusInfo = [
                 'min_price' => (float)$bonusRecord->min_price,
                 'max_price' => (float)$bonusRecord->max_price,
                 'bonus_type' => (int)$bonusRecord->bonus_type,
                 'bonus_json' => $bonusRecord->bonus_json,
                 'lottery_min_bet' => (float)$lottery->min_bet_amount,
                 'lottery_max_bet' => (float)$lottery->max_bet_amount
             ];
             
             return $this->buildSuccessResponse('验证通过', ['bonus_info' => $bonusInfo]);
            
        } catch (\Exception $e) {
            Log::error("validateTypeKeyAndName error: " . $e->getMessage());
            return $this->buildErrorResponse('验证投注类型时发生错误');
        }
    }

    /**
     * 构建错误响应
     * 
     * @param string $message 错误消息
     * @param int $code 错误代码
     * @return array
     */
    protected function buildErrorResponse(string $message, int $code = 0): array
    {
        return [
            'code' => $code,
            'msg' => $message,
            'data' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 构建成功响应
     * 
     * @param string $message 成功消息
     * @param array $data 返回数据
     * @return array
     */
    protected function buildSuccessResponse(string $message = '操作成功', array $data = []): array
    {
        return [
            'code' => 1,
            'msg' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 获取配置值
     * 
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return Config::get('lottery.' . $key, $default);
    }

    /**
     * 获取彩种验证器
     * 
     * @param string $lotteryCode 彩种代码
     * @return BetValidate|null 验证器实例
     */
    public static function getLotteryValidator(string $lotteryCode): ?BetValidate
    {
        try {
            // 从配置文件获取验证器映射，如果没有则使用默认映射
            $validatorMap = Config::get('lottery.validators', [
                'fc3d' => 'app\\validate\\Fc3dValidate',
                'ssq' => 'app\\validate\\SsqValidate',
                'dlt' => 'app\\validate\\DltValidate',
                'qlc' => 'app\\validate\\QlcValidate',
                'pls' => 'app\\validate\\PlsValidate',
                'plw' => 'app\\validate\\PlwValidate',
            ]);

            $lotteryCode = strtolower(trim($lotteryCode));
            
            if (empty($lotteryCode)) {
                Log::warning('彩种代码为空，无法获取验证器');
                return null;
            }

            if (isset($validatorMap[$lotteryCode])) {
                $validatorClass = $validatorMap[$lotteryCode];
                
                if (class_exists($validatorClass)) {
                    $validator = new $validatorClass();
                    
                    // 确保返回的是BetValidate的实例
                    if ($validator instanceof BetValidate) {
                        Log::info("成功创建彩种验证器", [
                            'lottery_code' => $lotteryCode,
                            'validator_class' => $validatorClass
                        ]);
                        return $validator;
                    } else {
                        Log::error("验证器类不是BetValidate的实例", [
                            'lottery_code' => $lotteryCode,
                            'validator_class' => $validatorClass
                        ]);
                    }
                } else {
                    Log::warning("验证器类不存在", [
                        'lottery_code' => $lotteryCode,
                        'validator_class' => $validatorClass
                    ]);
                }
            } else {
                Log::info("未找到专用验证器，将使用通用验证器", [
                    'lottery_code' => $lotteryCode,
                    'available_validators' => array_keys($validatorMap)
                ]);
            }

            return null;
            
        } catch (\Exception $e) {
            Log::error('获取彩种验证器异常', [
                'lottery_code' => $lotteryCode,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * 获取支持的彩种列表
     * 
     * @return array 支持的彩种代码列表
     */
    public static function getSupportedLotteries(): array
    {
        $validatorMap = Config::get('lottery.validators', [
            'fc3d' => 'app\\validate\\Fc3dValidate',
            'ssq' => 'app\\validate\\SsqValidate',
        ]);
        
        return array_keys($validatorMap);
    }

    /**
     * 检查彩种是否支持专用验证器
     * 
     * @param string $lotteryCode 彩种代码
     * @return bool
     */
    public static function hasLotteryValidator(string $lotteryCode): bool
    {
        return self::getLotteryValidator($lotteryCode) !== null;
    }
}