<?php

namespace app\common\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * 提现账号模型
 * @property int    $id           账号ID
 * @property int    $user_id      用户ID
 * @property string $type         账号类型：alipay-支付宝，wechat-微信，bank-银行卡
 * @property string $account_name 账号名称（真实姓名/持卡人姓名）
 * @property string $account_number 账号（支付宝账号/微信号/银行卡号）
 * @property string $bank_name    银行名称（银行卡专用）
 * @property string $bank_branch  开户行（银行卡专用）
 * @property string $qr_code      收款码图片路径（支付宝/微信专用）
 * @property string $phone_number 手机号（微信专用）
 * @property int    $status       状态：0-禁用，1-启用
 * @property int    $is_default   是否默认：0-否，1-是
 * @property int    $create_time  创建时间
 * @property int    $update_time  更新时间
 */
class WithdrawAccount extends Model
{
    protected $name = 'withdraw_account';
    
    protected $schema = [
        'id'             => 'int',
        'user_id'        => 'int',
        'type'           => 'string',
        'account_name'   => 'string',
        'account_number' => 'string',
        'bank_name'      => 'string',
        'bank_branch'    => 'string',
        'qr_code'        => 'string',
        'phone_number'   => 'string',
        'status'         => 'int',
        'is_default'     => 'int',
        'create_time'    => 'int',
        'update_time'    => 'int',
    ];
    
    protected $type = [
        // 移除时间字段的timestamp类型转换，因为数据库字段为int类型
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 账号类型常量
    const TYPE_ALIPAY = 'alipay';
    const TYPE_WECHAT = 'wechat';
    const TYPE_BANK = 'bank';
    
    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
    
    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 获取账号类型名称
     */
    public function getTypeNameAttr($value, $data)
    {
        $types = [
            self::TYPE_ALIPAY => '支付宝',
            self::TYPE_WECHAT => '微信支付',
            self::TYPE_BANK => '银行卡',
        ];
        return $types[$data['type']] ?? '未知';
    }
    
    /**
     * 获取脱敏账号
     */
    public function getMaskedAccountAttr($value, $data)
    {
        $account = $data['account_number'] ?? '';
        if (empty($account)) {
            return '';
        }
        
        $type = $data['type'] ?? '';
        
        switch ($type) {
            case self::TYPE_BANK:
                // 银行卡号脱敏：保留前4位和后4位
                if (strlen($account) > 8) {
                    return substr($account, 0, 4) . '****' . substr($account, -4);
                }
                break;
                
            case self::TYPE_ALIPAY:
                // 支付宝账号脱敏
                if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
                    // 邮箱格式：保留前3位和@后的域名
                    $parts = explode('@', $account);
                    if (count($parts) == 2 && strlen($parts[0]) > 3) {
                        return substr($parts[0], 0, 3) . '****@' . $parts[1];
                    }
                } else {
                    // 手机号格式：保留前3位和后4位
                    if (strlen($account) > 7) {
                        return substr($account, 0, 3) . '****' . substr($account, -4);
                    }
                }
                break;
                
            case self::TYPE_WECHAT:
                // 微信号脱敏：保留前3位和后2位
                if (strlen($account) > 5) {
                    return substr($account, 0, 3) . '****' . substr($account, -2);
                }
                break;
        }
        
        return $account;
    }
    
    /**
     * 设置默认账号
     */
    public function setDefault()
    {
        // 先取消该用户的其他默认账号
        self::where('user_id', $this->user_id)
            ->where('id', '<>', $this->id)
            ->update([
                'is_default' => 0,
                'update_time' => time()
            ]);
        
        // 设置当前账号为默认
        $this->save([
            'is_default' => 1,
            'update_time' => time()
        ]);
    }
    
    /**
     * 获取用户的默认账号
     */
    public static function getDefaultAccount($userId)
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ENABLED)
            ->where('is_default', 1)
            ->find();
    }
    
    /**
     * 获取用户的账号列表
     */
    public static function getUserAccounts($userId, $type = null)
    {
        $query = self::where('user_id', $userId)
            ->where('status', self::STATUS_ENABLED)
            ->order('is_default desc, create_time desc');
        
        if ($type && in_array($type, [self::TYPE_ALIPAY, self::TYPE_WECHAT, self::TYPE_BANK])) {
            $query->where('type', $type);
        }
        
        return $query->select();
    }
    
    /**
     * 获取状态名称
     */
    public function getStatusNameAttr($value, $data)
    {
        $statuses = [
            self::STATUS_DISABLED => '已禁用',
            self::STATUS_ENABLED => '正常',
        ];
        return $statuses[$data['status']] ?? '未知';
    }
    
    /**
     * 获取状态颜色
     */
    public function getStatusColorAttr($value, $data)
    {
        $colors = [
            self::STATUS_DISABLED => '#ff4757',
            self::STATUS_ENABLED => '#2ed573',
        ];
        return $colors[$data['status']] ?? '#747d8c';
    }
    
    /**
     * 验证账号是否属于指定用户
     */
    public static function checkOwnership($accountId, $userId)
    {
        return self::where('id', $accountId)
            ->where('user_id', $userId)
            ->where('status', self::STATUS_ENABLED)
            ->find();
    }
    
    /**
     * 获取用户账号数量
     */
    public static function getUserAccountCount($userId, $type = null)
    {
        $query = self::where('user_id', $userId)
            ->where('status', self::STATUS_ENABLED);
            
        if ($type && in_array($type, [self::TYPE_ALIPAY, self::TYPE_WECHAT, self::TYPE_BANK])) {
            $query->where('type', $type);
        }
        
        return $query->count();
    }
    
    /**
     * 检查账号是否已存在
     */
    public static function checkAccountExists($userId, $type, $accountNumber, $excludeId = null)
    {
        $query = self::where('user_id', $userId)
            ->where('type', $type)
            ->where('account_number', $accountNumber)
            ->where('status', self::STATUS_ENABLED);
            
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        
        return $query->find();
    }
}