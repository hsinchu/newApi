<?php

namespace app\api\validate;

use think\Validate;
use think\facade\Config;

class User extends Validate
{
    protected $failException = true;

    protected $rule = [
        'username'     => 'require|regex:^[a-zA-Z][a-zA-Z0-9_]{2,15}$|unique:user',
        'password'     => 'require|regex:^(?!.*[&<>"\'

]).{6,32}$',
        'registerType' => 'in:email,mobile',
        'email'        => 'email|unique:user',
        'mobile'       => 'mobile|unique:user',
        'emailCode'    => 'length:6',
        'inviteCode'   => 'require',
        // 注册邮箱或手机验证码
        'captcha'      => 'require',
        // 登录点选验证码
        'captchaId'    => 'require',
        'captchaInfo'  => 'require',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'register' => ['username', 'password', 'email', 'emailCode', 'inviteCode'],
    ];

    /**
     * 登录验证场景
     */
    public function sceneLogin(): User
    {
        $fields = ['username', 'password'];

        // 根据系统配置的登录验证码开关调整验证场景的字段
        $userLoginCaptchaSwitch = Config::get('buildadmin.user_login_captcha');
        if ($userLoginCaptchaSwitch) {
            $fields[] = 'captchaId';
            $fields[] = 'captchaInfo';
        }

        return $this->only($fields)->remove('username', ['regex', 'unique']);
    }

    public function __construct()
    {
        $this->field   = [
            'username'     => __('Username'),
            'email'        => __('Email'),
            'mobile'       => __('Mobile'),
            'password'     => __('Password'),
            'emailCode'    => __('Email verification code'),
            'inviteCode'   => __('Invitation code'),
            'captcha'      => __('captcha'),
            'captchaId'    => __('captchaId'),
            'captchaInfo'  => __('captcha'),
            'registerType' => __('Register type'),
        ];
        $this->message = array_merge($this->message, [
            'username.regex' => __('Please input correct username'),
            'password.regex' => __('Please input correct password')
        ]);
        parent::__construct();
    }
}