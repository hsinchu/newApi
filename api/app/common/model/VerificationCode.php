<?php

namespace app\common\model;

use think\Model;

class VerificationCode extends Model
{
    protected $name = 'verification_codes';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'email'       => 'string',
        'code'        => 'string',
        'type'        => 'string',
        'status'      => 'int',
        'expire_time' => 'int',
        'create_time' => 'int',
        'update_time' => 'int',
    ];
    
    // 验证码类型常量
    const TYPE_REGISTER = 'register';
    const TYPE_RESET_PASSWORD = 'reset_password';
    const TYPE_RESET_PAY_PASSWORD = 'reset_pay_password';
    const TYPE_CHANGE_EMAIL = 'change_email';
    
    // 状态常量
    const STATUS_UNUSED = 0;
    const STATUS_USED = 1;
    
    /**
     * 生成验证码
     * @param string $email 邮箱
     * @param string $type 类型
     * @return string
     */
    public static function generateCode($email, $type = self::TYPE_REGISTER)
    {
        // 生成6位数字验证码
        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // 设置过期时间（10分钟）
        $expireTime = time() + 600;
        
        // 先将该邮箱该类型的未使用验证码设为已使用
        self::where([
            'email' => $email,
            'type' => $type,
            'status' => self::STATUS_UNUSED
        ])->update(['status' => self::STATUS_USED]);
        
        // 创建新验证码
        self::create([
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'status' => self::STATUS_UNUSED,
            'expire_time' => $expireTime,
            'create_time' => time(),
            'update_time' => time()
        ]);
        
        return $code;
    }
    
    /**
     * 验证验证码
     * @param string $email 邮箱
     * @param string $code 验证码
     * @param string $type 类型
     * @return bool
     */
    public static function verifyCode($email, $code, $type = self::TYPE_REGISTER)
    {
        $verification = self::where([
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'status' => self::STATUS_UNUSED
        ])->find();
        
        if (!$verification) {
            return false;
        }
        
        // 检查是否过期
        if ($verification->expire_time < time()) {
            return false;
        }
        
        // 标记为已使用
        $verification->status = self::STATUS_USED;
        $verification->update_time = time();
        $verification->save();
        
        return true;
    }
    
    /**
     * 检查验证码发送频率
     * @param string $email 邮箱
     * @param string $type 类型
     * @return bool
     */
    public static function checkSendFrequency($email, $type = self::TYPE_REGISTER)
    {
        // 检查1分钟内是否已发送过验证码
        $lastSend = self::where([
            'email' => $email,
            'type' => $type
        ])->where('create_time', '>', time() - 60)->find();
        
        return !$lastSend;
    }
}