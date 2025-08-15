<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Banner as BannerModel;

/**
 * 轮播图API
 */
class Banner extends Api
{
    protected array $noNeedLogin = ['index', 'detail'];
    
    /**
     * 获取轮播图列表
     */
    public function index()
    {
        $limit = $this->request->param('limit', 10);
        
        $list = BannerModel::getPublicList($limit);
        
        $this->success('获取成功', [
            'list' => $list
        ]);
    }
    
    /**
     * 获取轮播图详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $detail = BannerModel::getPublicDetail($id);
        
        if (!$detail) {
            $this->error('轮播图不存在');
        }
        
        $this->success('获取成功', [
            'detail' => $detail
        ]);
    }
}