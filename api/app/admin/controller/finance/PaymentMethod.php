<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use app\common\model\PaymentMethod as PaymentMethodModel;
use app\service\PaymentService;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 支付方式管理
 */
class PaymentMethod extends Backend
{
    /**
     * PaymentMethod模型对象
     * @var PaymentMethodModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['method_name', 'method_code'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new PaymentMethodModel();
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
        $res = $this->model
            ->withoutGlobalScope()
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
            Db::startTrans();
            try {
                // 验证代码唯一性
                if (!PaymentMethodModel::isCodeUnique($data['method_code'])) {
                    throw new ValidateException('支付方式代码已存在');
                }

                $data['created_by'] = $this->auth->id;
                $data['updated_by'] = $this->auth->id;
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

        $this->error(__('Parameter error'));
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

        $dataLimitAdminIds = $this->getDataLimitAdminIds();
        if ($dataLimitAdminIds && !in_array($row[$this->dataLimitField], $dataLimitAdminIds)) {
            $this->error(__('You have no permission'));
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
                // 验证代码唯一性
                if (!PaymentMethodModel::isCodeUnique($data['method_code'], $id)) {
                    throw new ValidateException('支付方式代码已存在');
                }

                $data['updated_by'] = $this->auth->id;
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
            'row' => $row
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

        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($ids)) {
            $ids = array_filter($ids);
        } else {
            $ids = array_filter(explode(',', $ids));
        }

        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $dataLimitAdminIds = $this->getDataLimitAdminIds();
        if ($dataLimitAdminIds) {
            $this->model = $this->model->where($this->dataLimitField, 'in', $dataLimitAdminIds);
        }

        $count = 0;
        Db::startTrans();
        try {
            foreach ($ids as $id) {
                $row = $this->model->find($id);
                if ($row) {
                    $count += $row->delete();
                }
            }
            Db::commit();
        } catch (\Exception $e) {
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
     * 切换状态
     */
    public function toggleStatus(): void
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $row = $this->model->find($id);
        if (!$row) {
            $this->error('记录不存在');
        }
        
        $row->is_enabled = $status ? 1 : 0;
        $row->updated_by = $this->auth->id;
        
        if ($row->save()) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 获取支付方式选项
     */
    public function getOptions(): void
    {
        $data = PaymentMethodModel::getOptions();
        $this->success('', $data);
    }
}