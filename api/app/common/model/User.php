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
        'game_ids'             => 'string',
        'username'             => 'string',
        'is_agent'             => 'int',
        'user_tag'             => 'string',
        'level_id'             => 'int',
        'total_bet_amount'     => 'float',
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

    public function getGameIdsAttr($value): array
    {
        return $value ? explode(',', $value) : [];
    }

    public function setGameIdsAttr($value): string
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
    
    /**
     * 关联用户等级
     * @return BelongsTo
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'level_id', 'id');
    }
    
    /**
     * 更新投注额并检查升级
     * @param float $betAmount 投注金额
     * @return bool
     */
    public function updateBetAmountAndCheckUpgrade(float $betAmount): bool
    {
        // 更新累计投注额
        $this->total_bet_amount = bcadd($this->total_bet_amount, $betAmount, 2);
        
        // 检查是否需要升级
        $newLevel = UserLevel::getLevelByBetAmount($this->total_bet_amount);
        if ($newLevel && $newLevel['id'] != $this->level_id) {
            $this->level_id = $newLevel['id'];
        }
        
        return $this->save();
    }
    
    /**
     * 获取当前等级信息
     * @return array|null
     */
    public function getCurrentLevel(): ?array
    {
        return $this->level ? $this->level->toArray() : null;
    }
    
    /**
     * 获取下一个等级信息
     * @return UserLevel|null
     */
    public function getNextLevel(): ?UserLevel
    {
        // 直接通过level_id查询当前等级
        if (!$this->level_id) {
            return null;
        }
        
        $currentLevel = UserLevel::find($this->level_id);
        if (!$currentLevel) {
            return null;
        }
        
        return UserLevel::where('level', '>', $currentLevel->level)
            ->order('level', 'asc')
            ->find();
    }
    
    /**
     * 获取升级进度百分比
     * @return float
     */
    public function getUpgradeProgress(): float
    {
        $nextLevel = $this->getNextLevel();
        if (!$nextLevel || !is_object($nextLevel)) {
            return 100.0; // 已达到最高等级
        }
        
        // 直接通过level_id查询当前等级
        $currentCondition = 0;
        if ($this->level_id) {
            $currentLevel = UserLevel::find($this->level_id);
            $currentCondition = $currentLevel ? $currentLevel->upgrade_condition : 0;
        }
        $nextCondition = $nextLevel->upgrade_condition;
        
        if ($nextCondition <= $currentCondition) {
            return 100.0;
        }
        
        $progress = (($this->total_bet_amount - $currentCondition) / ($nextCondition - $currentCondition)) * 100;
        return max(0, min(100, $progress));
    }
}