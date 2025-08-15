<?php

namespace app\common\model;

use think\Model;

/**
 * UserLevel 模型
 */
class UserLevel extends Model
{
    protected $name = 'user_level';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'               => 'int',
        'name'             => 'string',
        'min_bet_amount'   => 'float',
        'max_bet_amount'   => 'float',
        'bet_percentage'   => 'float',
        'upgrade_condition'=> 'float',
        'level'            => 'int',
        'description'      => 'string',
        'status'           => 'int',
        'sort'             => 'int',
        'create_time'      => 'int',
        'update_time'      => 'int',
    ];

    /**
     * 追加属性
     */
    protected $append = [
        'status_text',
    ];

    protected $autoWriteTimestamp = true;

    protected $updateTime = 'update_time';
    protected $createTime = 'create_time';

    /**
     * 状态文本访问器
     * @param $value
     * @param $row
     * @return string
     */
    public function getStatusTextAttr($value, $row): string
    {
        $statusMap = [
            0 => '禁用',
            1 => '启用',
        ];
        return $statusMap[$row['status']] ?? '未知';
    }

    /**
     * 获取启用的等级
     * @return array
     */
    public static function getEnabledLevels(): array
    {
        return self::where('status', 1)
            ->order('level', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取等级选项
     * @return array
     */
    public static function getOptions(): array
    {
        $levels = self::getEnabledLevels();
        $options = [];
        foreach ($levels as $level) {
            $options[$level['id']] = $level['name'];
        }
        return $options;
    }

    /**
     * 根据投注额获取对应等级
     * @param float $betAmount
     * @return array|null
     */
    public static function getLevelByBetAmount(float $betAmount): ?array
    {
        return self::where('status', 1)
            ->where('upgrade_condition', '<=', $betAmount)
            ->order('level', 'desc')
            ->find()
            ?->toArray();
    }

    /**
     * 获取下一个等级
     * @param int $currentLevel
     * @return array|null
     */
    public static function getNextLevel(int $currentLevel): ?array
    {
        return self::where('status', 1)
            ->where('level', '>', $currentLevel)
            ->order('level', 'asc')
            ->find()
            ?->toArray();
    }
}