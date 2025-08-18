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
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
                'start_time_text' => $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '',
                'end_time_text' => $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '',
                'is_active' => $this->isTimeActive($item['start_time'], $item['end_time']),
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

                // 处理时间字段
                if (isset($data['start_time']) && $data['start_time']) {
                    $data['start_time'] = strtotime($data['start_time']);
                }
                if (isset($data['end_time']) && $data['end_time']) {
                    $data['end_time'] = strtotime($data['end_time']);
                }

                // 验证时间区间
                if ($data['start_time'] && $data['end_time'] && $data['start_time'] >= $data['end_time']) {
                    throw new ValidateException('开始时间必须小于结束时间');
                }

                // 检查是否已存在相同配置
                $exists = $this->model->where([
                    'charge_amount' => $data['charge_amount']
                ])->where('agent_id', 0)->find();

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

                // 处理时间字段
                if (isset($data['start_time']) && $data['start_time']) {
                    $data['start_time'] = strtotime($data['start_time']);
                }
                if (isset($data['end_time']) && $data['end_time']) {
                    $data['end_time'] = strtotime($data['end_time']);
                }

                // 验证时间区间
                if ($data['start_time'] && $data['end_time'] && $data['start_time'] >= $data['end_time']) {
                    throw new ValidateException('开始时间必须小于结束时间');
                }

                // 检查是否已存在相同配置（排除当前记录）
                $exists = $this->model->where([
                    'charge_amount' => $data['charge_amount']
                ])->where('agent_id', 0)->where('id', '<>', $id)->find();

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

        // 格式化时间字段供前端显示
        if ($row['start_time']) {
            $row['start_time'] = date('Y-m-d H:i:s', $row['start_time']);
        }
        if ($row['end_time']) {
            $row['end_time'] = date('Y-m-d H:i:s', $row['end_time']);
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
     * 判断时间区间是否有效
     */
    private function isTimeActive($start_time, $end_time)
    {
        $current_time = time();
        
        // 如果没有设置时间区间，默认为有效
        if (!$start_time && !$end_time) {
            return true;
        }
        
        // 检查当前时间是否在有效区间内
        if ($start_time && $current_time < $start_time) {
            return false; // 还未开始
        }
        
        if ($end_time && $current_time > $end_time) {
            return false; // 已过期
        }
        
        return true;
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