<?php

namespace app\common\model;

use Throwable;
use think\Model;
use think\facade\Cache;

/**
 * 系统配置模型
 * @property mixed $content
 * @property mixed $rule
 * @property mixed $extend
 * @property mixed $allow_del
 */
class Config extends Model
{
    /**
     * 表名
     */
    protected $name = 'config';

    /**
     * 关闭自动写入时间戳
     */
    protected $autoWriteTimestamp = false;

    /**
     * 字段信息
     */
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'group'       => 'string',
        'title'       => 'string',
        'tip'         => 'string',
        'type'        => 'string',
        'value'       => 'text',
        'content'     => 'text',
        'rule'        => 'string',
        'extend'      => 'text',
        'allow_del'   => 'int',
        'weight'      => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
    ];

    public static string $cacheTag = 'sys_config';

    protected $append = [
        'value',
        'content',
        'extend',
        'input_extend',
    ];

    protected array $jsonDecodeType = ['checkbox', 'array', 'selects'];
    protected array $needContent    = ['radio', 'checkbox', 'select', 'selects'];

    /**
     * 入库前
     * @throws Throwable
     */
    public static function onBeforeInsert(Config $model): void
    {
        if (!in_array($model->getData('type'), $model->needContent)) {
            $model->content = null;
        } else {
            $model->content = json_encode(str_attr_to_array($model->getData('content')));
        }
        if (is_array($model->rule)) {
            $model->rule = implode(',', $model->rule);
        }
        if ($model->getData('extend') || $model->getData('inputExtend')) {
            $extend      = str_attr_to_array($model->getData('extend'));
            $inputExtend = str_attr_to_array($model->getData('inputExtend'));
            if ($inputExtend) $extend['baInputExtend'] = $inputExtend;
            if ($extend) $model->extend = json_encode($extend);
        }
        $model->allow_del = 1;
    }

    /**
     * 写入后
     */
    public static function onAfterWrite(): void
    {
        // 清理配置缓存
        Cache::tag(self::$cacheTag)->clear();
    }

    public function getValueAttr($value, $row)
    {
        if (!isset($row['type']) || $value == '0') return $value;
        if (in_array($row['type'], $this->jsonDecodeType)) {
            return empty($value) ? [] : json_decode($value, true);
        } elseif ($row['type'] == 'switch') {
            return (bool)$value;
        } elseif ($row['type'] == 'editor') {
            return !$value ? '' : htmlspecialchars_decode($value);
        } elseif (in_array($row['type'], ['city', 'remoteSelects'])) {
            if (!$value) return [];
            if (!is_array($value)) return explode(',', $value);
            return $value;
        } else {
            return $value ?: '';
        }
    }

    public function setValueAttr(mixed $value, $row): mixed
    {
        if (in_array($row['type'], $this->jsonDecodeType)) {
            return $value ? json_encode($value) : '';
        } elseif ($row['type'] == 'switch') {
            return $value ? '1' : '0';
        } elseif ($row['type'] == 'time') {
            return $value ? date('H:i:s', strtotime($value)) : '';
        } elseif ($row['type'] == 'city') {
            if ($value && is_array($value)) {
                return implode(',', $value);
            }
            return $value ?: '';
        } elseif (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    public function getContentAttr($value, $row)
    {
        if (!isset($row['type'])) return '';
        if (in_array($row['type'], $this->needContent)) {
            $arr = json_decode($value, true);
            return $arr ?: [];
        } else {
            return '';
        }
    }

    public function getExtendAttr($value)
    {
        if ($value) {
            $arr = json_decode($value, true);
            if ($arr) {
                unset($arr['baInputExtend']);
                return $arr;
            }
        }
        return [];
    }

    public function getInputExtendAttr($value, $row)
    {
        if ($row && $row['extend']) {
            $arr = json_decode($row['extend'], true);
            if ($arr && isset($arr['baInputExtend'])) {
                return $arr['baInputExtend'];
            }
        }
        return [];
    }

    /**
     * 获取配置值
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getConfigValue(string $name, $default = null)
    {
        $config = Cache::tag(self::$cacheTag)->get('config_' . $name);
        if ($config === null) {
            $configModel = self::where('name', $name)->find();
            if ($configModel) {
                $config = $configModel->value;
                Cache::tag(self::$cacheTag)->set('config_' . $name, $config, 3600);
            } else {
                $config = $default;
            }
        }
        return $config;
    }

    /**
     * 设置配置值
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public static function setConfigValue(string $name, $value): bool
    {
        $config = self::where('name', $name)->find();
        if ($config) {
            $config->value = $value;
            $result = $config->save();
            if ($result) {
                Cache::tag(self::$cacheTag)->delete('config_' . $name);
            }
            return $result;
        }
        return false;
    }

    /**
     * 获取配置分组
     * @return array
     */
    public static function getGroups(): array
    {
        return self::distinct(true)
            ->column('group');
    }

    /**
     * 获取配置类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            'string'        => '字符串',
            'text'          => '文本域',
            'editor'        => '富文本编辑器',
            'number'        => '数字',
            'radio'         => '单选',
            'checkbox'      => '复选框',
            'select'        => '下拉选择',
            'selects'       => '多选下拉',
            'switch'        => '开关',
            'date'          => '日期',
            'time'          => '时间',
            'datetime'      => '日期时间',
            'image'         => '图片',
            'images'        => '多图片',
            'file'          => '文件',
            'files'         => '多文件',
            'color'         => '颜色选择器',
            'city'          => '城市选择器',
            'array'         => '数组',
            'remoteSelects' => '远程下拉选择',
        ];
    }

    /**
     * 批量获取配置
     * @param array $names
     * @return array
     */
    public static function getConfigs(array $names): array
    {
        $configs = [];
        foreach ($names as $name) {
            $configs[$name] = self::getConfigValue($name);
        }
        return $configs;
    }
}