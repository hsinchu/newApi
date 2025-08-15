<?php

namespace app\validate;

use think\Validate;

/**
 * 轮播图验证器
 */
class Banner extends Validate
{
    protected $rule = [
        'title'     => 'require|max:200',
        'image'     => 'require|max:500',
        'link_type' => 'require|in:0,1,2',
        'link_url'  => 'max:500',
        'sort_num'  => 'integer|>=:0',
        'status'    => 'require|in:0,1',
    ];

    protected $message = [
        'title.require'     => '轮播图标题不能为空',
        'title.max'         => '轮播图标题不能超过200个字符',
        'image.require'     => '轮播图片不能为空',
        'image.max'         => '轮播图片地址不能超过500个字符',
        'link_type.require' => '链接类型不能为空',
        'link_type.in'      => '链接类型值不正确',
        'link_url.max'      => '链接地址不能超过500个字符',
        'sort_num.integer'  => '排序序号必须是整数',
        'sort_num.>='       => '排序序号不能小于0',
        'status.require'    => '状态不能为空',
        'status.in'         => '状态值不正确',
    ];

    protected $scene = [
        'add'  => ['title', 'image', 'link_type', 'link_url', 'sort_num', 'status'],
        'edit' => ['title', 'image', 'link_type', 'link_url', 'sort_num', 'status'],
    ];

    /**
     * 自定义验证规则：当link_type不为0时，link_url不能为空
     */
    protected function checkLinkUrl($value, $rule, $data = [])
    {
        if (isset($data['link_type']) && $data['link_type'] != 0 && empty($value)) {
            return '当选择链接类型时，链接地址不能为空';
        }
        return true;
    }
}