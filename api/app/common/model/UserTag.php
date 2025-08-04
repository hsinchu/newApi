<?php

namespace app\common\model;

use think\Model;

class UserTag extends Model
{
    protected $name = 'user_tag';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'color'       => 'string',
        'description' => 'string',
        'status'      => 'int',
        'sort'        => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
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
     * 获取启用的标签
     * @return array
     */
    public static function getEnabledTags(): array
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取标签选项
     * @return array
     */
    public static function getOptions(): array
    {
        $tags = self::getEnabledTags();
        $options = [];
        foreach ($tags as $tag) {
            $options[$tag['id']] = $tag['name'];
        }
        return $options;
    }

    /**
     * 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }

    /**
     * 检查标签是否启用
     * @param int $id
     * @return bool
     */
    public static function isEnabled(int $id): bool
    {
        return self::where('id', $id)
            ->where('status', 1)
            ->count() > 0;
    }

    /**
     * 获取标签颜色选项
     * @return array
     */
    public static function getColorOptions(): array
    {
        return [
            '#409EFF' => '蓝色',
            '#67C23A' => '绿色',
            '#E6A23C' => '橙色',
            '#F56C6C' => '红色',
            '#909399' => '灰色',
            '#9C27B0' => '紫色',
            '#FF5722' => '深橙色',
            '#795548' => '棕色',
            '#607D8B' => '蓝灰色',
            '#4CAF50' => '深绿色',
        ];
    }
}