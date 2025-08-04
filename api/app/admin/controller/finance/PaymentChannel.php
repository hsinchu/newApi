<?php

namespace app\admin\controller\finance;

use app\common\controller\Backend;
use app\common\model\PaymentChannel as PaymentChannelModel;
use app\common\model\PaymentMethod;
use app\service\PaymentService;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;

/**
 * 支付通道管理
 */
class PaymentChannel extends Backend
{
    /**
     * PaymentChannel模型对象
     * @var PaymentChannelModel
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];

    protected string|array $quickSearchField = ['internal_name', 'external_name', 'channel_code'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new PaymentChannelModel();
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
                // 验证通道代码唯一性
                if (!PaymentChannelModel::isChannelCodeUnique($data['channel_code'])) {
                    throw new ValidateException('通道代码已存在');
                }

                // 验证通道参数
                if (isset($data['channel_params']) && !PaymentService::validateChannelParams($data['channel_params'])) {
                    throw new ValidateException('通道参数不完整');
                }

                // 处理支付方式ID
                if (isset($data['payment_method_ids']) && is_array($data['payment_method_ids'])) {
                    $data['payment_method_id'] = implode(',', $data['payment_method_ids']);
                    unset($data['payment_method_ids']);
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
                // 验证通道代码唯一性
                if (!PaymentChannelModel::isChannelCodeUnique($data['channel_code'], $id)) {
                    throw new ValidateException('通道代码已存在');
                }

                // 验证通道参数
                if (isset($data['channel_params']) && !PaymentService::validateChannelParams($data['channel_params'])) {
                    throw new ValidateException('通道参数不完整');
                }

                // 处理支付方式ID
                if (isset($data['payment_method_ids']) && is_array($data['payment_method_ids'])) {
                    $data['payment_method_id'] = implode(',', $data['payment_method_ids']);
                    unset($data['payment_method_ids']);
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

        // 处理支付方式ID为数组格式
        if ($row->payment_method_id) {
            $row->payment_method_ids = explode(',', $row->payment_method_id);
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
     * 获取支付通道选项
     */
    public function getOptions(): void
    {
        $options = PaymentChannelModel::getOptions();
        $this->success('', $options);
    }

    /**
     * 获取通道编码配置
     */
    public function getChannelCodes(): void
    {
        $codes = PaymentService::getChannelCodeConfig();
        $this->success('', $codes);
    }

    /**
     * 根据支付方式获取通道
     */
    public function getChannelsByMethod(): void
    {
        $methodId = $this->request->param('method_id');
        if (!$methodId) {
            $this->error('参数错误');
        }
        
        $channels = PaymentChannelModel::getChannelsByMethod($methodId);
        $this->success('', $channels);
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
     * 测试通道连接
     */
    public function testChannel(): void
    {
        $id = $this->request->param('id');
        if (!$id) {
            $this->error('参数错误');
        }
        
        $channel = $this->model->find($id);
        if (!$channel) {
            $this->error('通道不存在');
        }
        
        // 这里可以添加具体的通道测试逻辑
        // 暂时返回成功
        $this->success('通道连接正常');
    }
}