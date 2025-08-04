<?php

namespace app\admin\controller\redpacket;

use app\common\model\RedPacket as RedPacketModel;
use app\common\model\User;
use app\common\controller\Backend;
use app\service\RedPacketService;
use Exception;

/**
 * 红包管理控制器
 */
class RedPacket extends Backend
{
    /**
     * RedPacket模型对象
     * @var RedPacketModel
     */
    protected object $model;
    
    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];
    
    protected array $withJoinTable = ['agent'];
    
    protected string|array $quickSearchField = ['title', 'blessing'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new RedPacketModel();
    }

    /**
     * 添加
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->auth->id;
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 验证数据
                $this->validateRedPacketData($data);
                
                // 使用服务创建红包
                $redPacketService = new RedPacketService();
                $redPacket = $redPacketService->createRedPacket($data);
                
                $this->model->commit();
                $result = $redPacket;
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->success('', [
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 编辑（已禁用）
     */
    public function edit(): void
    {
        $this->error('红包不支持编辑操作');
    }

    /**
     * 删除（已禁用）
     */
    public function del(): void
    {
        $this->error('红包不支持删除操作');
    }

    /**
     * 取消红包
     */
    public function cancel(): void
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }

        try {
            $redPacketService = new RedPacketService();
            $result = $redPacketService->cancelRedPacket($id);
            
            if ($result) {
                $this->jsonReturn('红包已取消');
            } else {
                $this->error('取消失败');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取红包统计
     */
    public function stats(): void
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }

        try {
            $redPacketService = new RedPacketService();
            $stats = $redPacketService->getRedPacketStats($id);
            
            $this->jsonReturn('', $stats);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取代理商列表
     */
    public function getAgents(): void
    {
        try {
            $agents = User::where('is_agent', '1')
                ->where('status', 1)
                ->field('id,username,nickname')
                ->select();
            
            $this->jsonReturn('获取成功', $agents);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 验证红包数据
     */
    private function validateRedPacketData(array $data): void
    {
        if (empty($data['title'])) {
            throw new Exception('红包标题不能为空');
        }
        
        if (empty($data['agent_id'])) {
            // throw new Exception('请选择代理商');
        }
        
        if (empty($data['type']) || !in_array($data['type'], [RedPacketModel::TYPE_RANDOM, RedPacketModel::TYPE_FIXED])) {
            throw new Exception('红包类型错误');
        }
        
        if (empty($data['total_amount']) || $data['total_amount'] <= 0) {
            throw new Exception('红包总金额必须大于0');
        }
        
        if (empty($data['total_count']) || $data['total_count'] <= 0) {
            throw new Exception('红包总个数必须大于0');
        }
        
        if ($data['total_count'] > 100) {
            throw new Exception('红包个数不能超过100个');
        }
        
        // 检查平均金额是否太小
        $avgAmount = bcdiv($data['total_amount'], $data['total_count'], 2);
        if (bccomp($avgAmount, '0.01', 2) < 0) {
            throw new Exception('平均红包金额不能小于0.01元');
        }
    }

    /**
     * 检查过期红包
     */
    public function checkExpired(): void
    {
        try {
            $redPacketService = new RedPacketService();
            $count = $redPacketService->checkExpiredRedPackets();
            
            $this->jsonReturn('检查完成', ['count' => $count]);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}