<?php

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * Admin模型
 * @property int    $id              管理员ID
 * @property string $username        管理员用户名
 * @property string $nickname        管理员昵称
 * @property string $email           管理员邮箱
 * @property string $mobile          管理员手机号
 * @property string $last_login_ip   上次登录IP
 * @property string $last_login_time 上次登录时间
 * @property int    $login_failure   登录失败次数
 * @property string $password        密码密文
 * @property string $salt            密码盐（废弃待删）
 * @property string $status          状态:enable=启用,disable=禁用,...(string存储，可自定义其他)
 */
class Admin extends Model
{
    /**
     * 表名
     */
    protected $name = 'admin';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'              => 'int',
        'username'        => 'string',
        'nickname'        => 'string',
        'avatar'          => 'string',
        'email'           => 'string',
        'mobile'          => 'string',
        'login_failure'   => 'int',
        'last_login_time' => 'int',
        'last_login_ip'   => 'string',
        'password'        => 'string',
        'salt'            => 'string',
        'motto'           => 'string',
        'status'          => 'string',
        'update_time'     => 'int',
        'create_time'     => 'int',
    ];

    /**
     * @var string 自动写入时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 追加属性
     */
    protected $append = [
        'group_arr',
        'group_name_arr',
        'status_text',
        'last_login_time_text',
    ];

    public function getGroupArrAttr($value, $row): array
    {
        return Db::name('admin_group_access')
            ->where('uid', $row['id'])
            ->column('group_id');
    }

    public function getGroupNameArrAttr($value, $row): array
    {
        $groupAccess = Db::name('admin_group_access')
            ->where('uid', $row['id'])
            ->column('group_id');
        return AdminGroup::whereIn('id', $groupAccess)->column('name');
    }

    public function getAvatarAttr($value): string
    {
        return full_url($value, false, config('buildadmin.default_avatar'));
    }

    public function setAvatarAttr($value): string
    {
        return $value == full_url('', false, config('buildadmin.default_avatar')) ? '' : $value;
    }

    public function getStatusTextAttr($value, $row): string
    {
        $statusMap = [
            'enable'  => '启用',
            'disable' => '禁用',
        ];
        return $statusMap[$row['status']] ?? $row['status'];
    }

    public function getLastLoginTimeAttr($value): string
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getLastLoginTimeTextAttr($value, $row): string
    {
        return $row['last_login_time'] ? date('Y-m-d H:i:s', $row['last_login_time']) : '从未登录';
    }

    /**
     * 重置用户密码
     * @param int|string $uid         管理员ID
     * @param string     $newPassword 新密码
     * @return int|Admin
     */
    public function resetPassword(int|string $uid, string $newPassword): int|Admin
    {
        return $this->where(['id' => $uid])->update(['password' => hash_password($newPassword), 'salt' => '']);
    }

    /**
     * 获取启用的管理员
     * @return array
     */
    public static function getEnabledAdmins(): array
    {
        return self::where('status', 'enable')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取管理员选项
     * @return array
     */
    public static function getOptions(): array
    {
        return self::where('status', 'enable')
            ->order('id', 'asc')
            ->column('nickname', 'id');
    }

    /**
     * 检查管理员是否启用
     * @param int $adminId
     * @return bool
     */
    public static function isEnabled(int $adminId): bool
    {
        return self::where('id', $adminId)
            ->where('status', 'enable')
            ->count() > 0;
    }
}