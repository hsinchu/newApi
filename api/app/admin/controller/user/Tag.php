<?php

namespace app\admin\controller\user;

use Throwable;
use app\common\model\UserTag;
use app\common\controller\Backend;

class Tag extends Backend
{
    /**
     * @var object
     * @phpstan-var UserTag
     */
    protected object $model;

    // 排除字段
    protected string|array $preExcludeFields = ['update_time', 'create_time'];

    protected string|array $quickSearchField = 'name';

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new UserTag();
    }
    
}