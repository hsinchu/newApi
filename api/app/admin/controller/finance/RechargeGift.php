<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use app\common\model\RechargeGift as RechargeGiftModel;
use app\common\model\User;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 充值赠送配置管理
 */
class RechargeGift extends Backend
{
    /**
     * RechargeGift模型对象
     * @var RechargeGiftModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['agent_id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new RechargeGiftModel();
    }

    /**
     * 查看
     */
    public function index(): void
    {
        // 如果是select返回
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();

        $where['agent_id'] = 0;
        
        $res = $this->model
            ->withoutGlobalScope()
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $list = [];
        foreach ($res->items() as $item) {
            $list[] = [
                'id' => $item['id'],
                'charge_amount' => $item['charge_amount'],
                'bonus_amount' => $item['bonus_amount'],
                'status' => $item['status'],
                'status_text' => $this->getStatusText($item['status']),
                'create_time' => $item['create_time'],
                'update_time' => $item['update_time'],
            ];
        }

        $this->success('', [
            'list'   => $list,
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
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

            $result = false;
            Db::startTrans();
            try {
                // 验证金额
                if ($data['charge_amount'] <= 0) {
                    throw new ValidateException('最低充值金额必须大于0');
                }
                if ($data['bonus_amount'] < 0) {
                    throw new ValidateException('赠送金额不能小于0');
                }

                // 检查是否已存在相同配置
                $exists = $this->model->where([
                    'charge_amount' => $data['charge_amount']
                ])->find();
                if ($exists) {
                    throw new ValidateException('该充值金额配置已存在');
                }

                $data['create_time'] = time();
                $data['update_time'] = time();
                
                $result = $this->model->save($data);
                Db::commit();
            } catch (ValidateException|\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->success('');
    }

    /**
     * 编辑
     */
    public function edit(): void
    {
        $id  = $this->request->param($this->model->getPk());
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);

            $result = false;
            Db::startTrans();
            try {
                // 验证金额
                if ($data['charge_amount'] <= 0) {
                    throw new ValidateException('最低充值金额必须大于0');
                }
                if ($data['bonus_amount'] < 0) {
                    throw new ValidateException('赠送金额不能小于0');
                }

                // 检查是否已存在相同配置（排除当前记录）
                $exists = $this->model->where([
                    'charge_amount' => $data['charge_amount']
                ])->where('id', '<>', $id)->find();
                if ($exists) {
                    throw new ValidateException('该充值金额配置已存在');
                }

                $data['update_time'] = time();
                
                $result = $row->save($data);
                Db::commit();
            } catch (ValidateException|\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        $this->success('', [
            'row' => $row,
        ]);
    }

    /**
     * 删除
     */
    public function del(): void
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $pk    = $this->model->getPk();
        $data  = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans();
        try {
            foreach ($data as $v) {
                $count += $v->delete();
            }
            Db::commit();
        } catch (\PDOException|\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(__('Deleted successfully'));
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status): string
    {
        $statusMap = [
            0 => '禁用',
            1 => '启用',
        ];
        return $statusMap[$status] ?? '未知';
    }



    /**
     * 批量修改状态
     */
    public function changeStatus(): void
    {
        $ids = $this->request->param('ids');
        $status = $this->request->param('status');
        
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }
        
        if (!in_array($status, [0, 1])) {
            $this->error('状态参数错误');
        }

        $count = 0;
        Db::startTrans();
        try {
            $count = $this->model->where('id', 'in', $ids)->update([
                'status' => $status,
                'update_time' => time()
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        
        if ($count) {
            $this->success(__('Update successful'));
        } else {
            $this->error(__('No rows updated'));
        }
    }
}