<?php

namespace app\api\validate;

use think\Validate;

class Agent extends Validate
{
    protected $rule = [
        'page'    => 'integer|min:1',
        'limit'   => 'integer|between:1,100',
        'keyword' => 'max:50',
    ];

    protected $message = [
        'page.integer'     => '页码必须是整数',
        'page.min'        => '页码不能小于1',
        'limit.integer'   => '每页数量必须是整数',
        'limit.between'   => '每页数量必须在1-100之间',
        'keyword.max'     => '搜索关键词不能超过50个字符',
    ];

    protected $scene = [
        'members' => ['page', 'limit', 'keyword'],
        'subAgents' => ['page', 'limit', 'keyword'],
    ];
}