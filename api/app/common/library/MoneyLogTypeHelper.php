<?php

namespace app\common\library;

/**
 * 资金变动类型帮助类
 * 统一管理类型配置，避免重复定义
 */
class MoneyLogTypeHelper
{
    /**
     * 获取类型配置
     * @return array
     */
    public static function getConfig(): array
    {
        return config('money_log_types');
    }

    /**
     * 数据库类型转前端类型
     * @param string $dbType 数据库类型
     * @return string 前端类型
     */
    public static function dbToFrontend(string $dbType): string
    {
        $config = self::getConfig();
        return $config['db_to_frontend'][$dbType] ?? 'other';
    }

    /**
     * 前端类型转数据库类型
     * @param string $frontendType 前端类型
     * @return string|array 数据库类型
     */
    public static function frontendToDb(string $frontendType)
    {
        $config = self::getConfig();
        return $config['frontend_to_db'][$frontendType] ?? [];
    }

    /**
     * 获取类型分组
     * @param string $type 类型
     * @return array 数据库类型数组
     */
    public static function getTypeGroup(string $type): array
    {
        $config = self::getConfig();
        return $config['type_groups'][$type] ?? [];
    }

    /**
     * 获取类型中文名称
     * @param string $type 类型
     * @return string 中文名称
     */
    public static function getTypeName(string $type): string
    {
        $config = self::getConfig();
        return $config['type_names'][$type] ?? $type;
    }

    /**
     * 获取所有前端类型
     * @return array
     */
    public static function getAllFrontendTypes(): array
    {
        $config = self::getConfig();
        return array_keys($config['type_names']);
    }

    /**
     * 获取所有数据库类型
     * @return array
     */
    public static function getAllDbTypes(): array
    {
        $config = self::getConfig();
        return array_keys($config['db_to_frontend']);
    }

    /**
     * 构建类型过滤条件
     * @param string $type 前端类型
     * @return array 查询条件
     */
    public static function buildTypeCondition(string $type): array
    {
        if (empty($type)) {
            return [];
        }

        $dbTypes = self::frontendToDb($type);
        if (empty($dbTypes)) {
            return [];
        }

        if (is_array($dbTypes)) {
            return ['type', 'in', $dbTypes];
        } else {
            return ['type', '=', $dbTypes];
        }
    }

    /**
     * 批量转换数据库类型到前端类型
     * @param array $logs 日志数组
     * @param string $typeField 类型字段名，默认为'type'
     * @return array
     */
    public static function batchConvertDbToFrontend(array $logs, string $typeField = 'type'): array
    {
        $config = self::getConfig();
        $mapping = $config['db_to_frontend'];

        foreach ($logs as &$log) {
            if (isset($log[$typeField])) {
                $log[$typeField] = $mapping[$log[$typeField]] ?? 'other';
            }
        }

        return $logs;
    }

    /**
     * 获取反向类型映射（用于兼容旧代码）
     * @return array
     */
    public static function getReverseTypeMap(): array
    {
        $config = self::getConfig();
        return $config['db_to_frontend'];
    }

    /**
     * 获取类型映射（用于兼容旧代码）
     * @return array
     */
    public static function getTypeMap(): array
    {
        $config = self::getConfig();
        return $config['type_groups'];
    }
}