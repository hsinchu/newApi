<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * User 模型
 * @property int    $id      用户ID
 * @property string password 密码密文
 */
class User extends Model
{
    /**
     * 表名
     */
    
    protected $name = 'user';

    /**
     * 字段信息
     */
    protected $schema = [
        'id'                   => 'int',
        'group_id'             => 'int',
        'parent_id'            => 'int',
        'username'             => 'string',
        'is_agent'             => 'int',
        'user_tag'             => 'string',
        'nickname'             => 'string',
        'real_name'            => 'string',
        'id_card'              => 'string',
        'email'                => 'string',
        'mobile'               => 'string',
        'avatar'               => 'string',
        'gender'               => 'int',
        'birthday'             => 'date',
        'money'                => 'int',
        'unwith_money'         => 'int',
        'gift_money'           => 'int',
        'score'                => 'int',
        'last_login_time'      => 'int',
        'last_login_ip'        => 'string',
        'login_failure'        => 'int',
        'join_ip'              => 'string',
        'join_time'            => 'int',
        'motto'                => 'string',
        'password'             => 'string',
        'pay_password'         => 'string',
        'is_verified'          => 'int',
        'invite_code'          => 'string',
        'invited_by'           => 'string',
        'default_rebate_rate'  => 'float',
        'default_nowin_rate'  => 'float',
        'nowin_rate'           => 'float',
        'rebate_rate'          => 'float',
        'agent_favorite'       => 'int',
        'last_bet_time'        => 'int',
        'register_ip'          => 'string',
        'extra_data'           => 'string',
        'salt'                 => 'string',
        'status'               => 'int',
        'update_time'          => 'int',
        'create_time'          => 'int',
    ];

    /**
     * JSON字段
     */
    protected $json = ['extra_data'];

    /**
     * 追加属性
     */
    protected $append = [
        'status_text',
        'gender_text',
        'is_agent_text',
        'is_verified_text',
    ];

    protected $autoWriteTimestamp = true;

    public function getAvatarAttr($value): string
    {
        return full_url($value, false, config('buildadmin.default_avatar'));
    }

    public function setAvatarAttr($value): string
    {
        return $value == full_url('', false, config('buildadmin.default_avatar')) ? '' : $value;
    }

    public function getMoneyAttr($value): string
    {
        return bcdiv($value, 100, 2);
    }

    public function setMoneyAttr($value): string
    {
        return bcmul($value, 100, 2);
    }

    public function getFrozenMoneyAttr($value): string
    {
        return bcdiv($value, 100, 2);
    }

    public function setFrozenMoneyAttr($value): string
    {
        return bcmul($value, 100, 2);
    }

    public function getUnwithMoneyAttr($value): string
    {
        return bcdiv($value, 100, 2);
    }

    public function setUnwithMoneyAttr($value): string
    {
        return bcmul($value, 100, 2);
    }

    public function getGiftMoneyAttr($value): string
    {
        return bcdiv($value, 100, 2);
    }

    public function setGiftMoneyAttr($value): string
    {
        return bcmul($value, 100, 2);
    }

    public function getUserTagAttr($value): array
    {
        return $value ? explode(',', $value) : [];
    }

    public function setUserTagAttr($value): string
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    public function getStatusTextAttr($value, $row): string
    {
        $statusMap = [
            0 => '审核中',
            1 => '启用',
            2 => '禁用',
        ];
        return $statusMap[$row['status']] ?? '未知';
    }

    public function getGenderTextAttr($value, $row): string
    {
        $genderMap = [
            0 => '未知',
            1 => '男',
            2 => '女',
        ];
        $gender = isset($row['gender']) ? $row['gender'] : 0;
        return $genderMap[$gender] ?? '未知';
    }

    public function getIsAgentTextAttr($value, $row): string
    {
        return isset($row['is_agent']) && $row['is_agent'] == 1 ? '是' : '否';
    }

    public function getIsVerifiedTextAttr($value, $row): string
    {
        return isset($row['is_verified']) && $row['is_verified'] == 1 ? '已认证' : '未认证';
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * 重置用户密码
     * @param int|string $uid         用户ID
     * @param string     $newPassword 新密码
     * @return int|User
     */
    public function resetPassword(int|string $uid, string $newPassword): int|User
    {
        return $this->where(['id' => $uid])->update(['password' => hash_password($newPassword), 'salt' => '']);
    }

    /**
     * 获取启用的用户
     * @return array
     */
    public static function getEnabledUsers(): array
    {
        return self::where('status', 1)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取用户选项
     * @return array
     */
    public static function getOptions(): array
    {
        return self::where('status', 1)
            ->order('id', 'desc')
            ->column('nickname', 'id');
    }

    /**
     * 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            0 => '审核中',
            1 => '启用',
            2 => '禁用',
        ];
    }

    /**
     * 检查用户是否启用
     * @param int $userId
     * @return bool
     */
    public static function isEnabled(int $userId): bool
    {
        return self::where('id', $userId)
            ->where('status', 1)
            ->count() > 0;
    }

    /**
     * 获取代理商用户
     * @return array
     */
    public static function getAgents(): array
    {
        return self::where('is_agent', 1)
            ->where('status', 1)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }
    
    /**
     * 关联投注订单
     */
    public function betOrders()
    {
        return $this->hasMany(BetOrder::class, 'user_id');
    }
    
    /**
     * 关联资金记录
     */
    public function moneyLogs()
    {
        return $this->hasMany(UserMoneyLog::class, 'user_id');
    }
    
    /**
     * 关联红包领取记录
     */
    public function redPacketRecords()
    {
        return $this->hasMany(RedPacketRecord::class, 'user_id');
    }
    
    /**
     * 关联下级用户
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }
    
    /**
     * 获取用户总余额（可用+不可提现）
     */
    public function getTotalBalanceAttr($value, $data): string
    {
        $total = $data['money'] + $data['unwith_money'];
        return bcdiv($total, 100, 2);
    }
    
    /**
     * 获取最后登录时间文本
     */
    public function getLastLoginTimeTextAttr($value, $data): string
    {
        return $data['last_login_time'] ? date('Y-m-d H:i:s', $data['last_login_time']) : '从未登录';
    }
    
    /**
     * 获取注册时间文本
     */
    public function getCreateTimeTextAttr($value, $data): string
    {
        return date('Y-m-d H:i:s', $data['create_time']);
    }
    
    /**
     * 获取用户等级文本
     */
    public function getUserLevelTextAttr($value, $data): string
    {
        return $this->userGroup ? $this->userGroup->name : '普通用户';
    }
    
    /**
     * 检查支付密码
     */
    public function checkPayPassword(string $password): bool
    {
        return password_verify($password, $this->pay_password);
    }
    
    /**
     * 设置支付密码
     */
    public function setPayPassword(string $password): bool
    {
        $this->pay_password = password_hash($password, PASSWORD_DEFAULT);
        return $this->save();
    }
    
    /**
     * 获取用户统计信息
     */
    public function getUserStats(): array
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');
        
        // 投注统计
        $betStats = $this->betOrders()
            ->field([
                'COUNT(*) as total_bets',
                'SUM(bet_amount) as total_bet_amount',
                'SUM(CASE WHEN status = "WINNING" THEN win_amount ELSE 0 END) as total_win_amount'
            ])
            ->find();
            
        // 今日投注
        $todayBets = $this->betOrders()
            ->whereTime('create_time', 'today')
            ->count();
            
        // 本月投注
        $monthBets = $this->betOrders()
            ->whereTime('create_time', 'month')
            ->count();
            
        return [
            'total_bets' => $betStats['total_bets'] ?? 0,
            'total_bet_amount' => bcdiv($betStats['total_bet_amount'] ?? 0, 100, 2),
            'total_win_amount' => bcdiv($betStats['total_win_amount'] ?? 0, 100, 2),
            'today_bets' => $todayBets,
            'month_bets' => $monthBets,
            'win_rate' => $betStats['total_bets'] > 0 ? 
                round(($betStats['total_win_amount'] / $betStats['total_bet_amount']) * 100, 2) : 0
        ];
    }
    
    /**
     * 生成邀请码
     */
    public static function generateInviteCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('invite_code', $code)->find());
        
        return $code;
    }
    
    /**
     * 根据邀请码查找用户
     */
    public static function findByInviteCode(string $code): ?User
    {
        return self::where('invite_code', $code)->find();
    }
}