<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Dano;
use think\response\Json;

/**
 * 其他接口
 */
class Other extends Api
{
    protected $noNeedLogin = ['danoList', 'danoDetail'];
    
    /**
     * 获取公告列表
     * @return Json
     */
    public function danoList(): Json
    {
        try {
            $list = Dano::getPublicList(20);
            
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item->id,
                    'name' => mb_substr($item->title, 0, 8) . (mb_strlen($item->title) > 8 ? '...' : ''),
                    'title' => $item->title,
                    'content' => $item->content,
                    'create_time' => $item->create_time,
                    'sort_num' => $item->sort_num,
                    'attachment' => null // 暂时不支持附件
                ];
            }
        } catch (\Exception $e) {
            return $this->error('获取失败：' . $e->getMessage());
        }
            
        return $this->success('获取成功', $result);
    }
    
    /**
     * 获取公告详情
     * @return Json
     */
    public function danoDetail(): Json
    {
        $id = $this->request->param('id/d', 0);
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        try {
            $detail = Dano::getPublicDetail($id);
            
            if (!$detail) {
                return $this->error('公告不存在或已下架');
            }
            
            $result = [
                'id' => $detail->id,
                'title' => $detail->title,
                'content' => $detail->content,
                'create_time' => $detail->create_time,
                'sort_num' => $detail->sort_num
            ];
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error('获取失败：' . $e->getMessage());
        }
    }
}