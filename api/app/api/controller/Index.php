<?php

namespace app\api\controller;

use ba\Tree;
use Throwable;
use think\facade\Db;
use think\facade\Config;
use app\common\controller\Frontend;
use app\common\model\UserRule;
use app\common\library\token\TokenExpirationException;

class Index extends Frontend
{
    protected array $noNeedLogin = ['index'];

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 前台和会员中心的初始化请求
     * @throws Throwable
     */
    public function index(): void
    {
        echo '';
    }
}