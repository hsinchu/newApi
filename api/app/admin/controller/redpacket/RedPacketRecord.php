<?php

namespace app\admin\controller\redpacket;

use app\common\model\RedPacketRecord as RedPacketRecordModel;
use app\common\controller\Backend;

/**
 * 红包领取记录控制器
 */
class RedPacketRecord extends Backend
{
    /**
     * RedPacketRecord模型对象
     * @var RedPacketRecordModel
     */
    protected object $model;
    
    protected array|string $preExcludeFields = ['id', 'create_time'];
    
    protected array $withJoinTable = ['redPacket', 'user'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new RedPacketRecordModel();
    }

    /**
     * 查看列表
     */
    public function index(): void
    {
        $this->request->filter(['strip_tags', 'trim']);
        
        if ($this->request->param('select')) {
            $this->select();
        }

        [$where, $alias, $limit, $order] = $this->queryBuilder();
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        
        $this->success('', [
             'list'   => $res->items(),
             'total'  => $res->total(),
             'remark' => get_route_remark(),
         ]);
    }
}