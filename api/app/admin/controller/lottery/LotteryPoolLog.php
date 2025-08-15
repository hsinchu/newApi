<?php

namespace app\admin\controller\lottery;

use app\common\controller\Backend;
use app\common\model\LotteryPoolLog as LotteryPoolLogModel;
use app\common\model\LotteryType;
use think\facade\Db;
use think\facade\Validate;
use Exception;

/**
 * 服务费记录管理控制器
 */
class LotteryPoolLog extends Backend
{
    /**
     * LotteryPoolLog模型对象
     * @var LotteryPoolLogModel
     */
    protected object $model;
    
    protected array|string $preExcludeFields = ['id', 'update_time'];
    
    protected string|array $quickSearchField = ['type_code', 'period_no'];

    public function initialize(): void
    {        
        parent::initialize();
        $this->model = new LotteryPoolLogModel();
    }

    /**
     * 查看列表
     */
    public function index(): void
    {
        // 设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withJoin(['lotteryType'], 'LEFT')
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
     * 添加服务费记录
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
                $validate = Validate::rule([
                    'type_code'    => 'require|max:255',
                    'period_no'    => 'require|max:25',
                    'bonus_system' => 'require|float|egt:0',
                ]);
                
                if (!$validate->check($data)) {
                    throw new Exception($validate->getError());
                }
                
                // 检查彩种是否存在
                $lotteryType = LotteryType::where('type_code', $data['type_code'])->find();
                if (!$lotteryType) {
                    throw new Exception('彩种不存在');
                }
                
                // 检查期号是否已存在
                $exists = $this->model->where('type_code', $data['type_code'])
                    ->where('period_no', $data['period_no'])
                    ->find();
                if ($exists) {
                    throw new Exception('该期号的服务费记录已存在');
                }
                
                $data['update_time'] = time() * 1000;
                $result = $this->model->save($data);
                $this->model->commit();
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

        // 获取彩种列表
        $lotteryTypes = LotteryType::where('is_enabled', 1)
            ->field('type_code,type_name')
            ->select()
            ->toArray();
        
        $this->success('', [
            'lotteryTypes' => $lotteryTypes,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 编辑服务费记录
     */
    public function edit(): void
    {
        $id = $this->request->param($this->model->getPk());
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
            $this->model->startTrans();
            try {
                // 验证数据
                $validate = Validate::rule([
                    'type_code'    => 'require|max:255',
                    'period_no'    => 'require|max:25', 
                    'bonus_system' => 'require|float|egt:0',
                ]);
                
                if (!$validate->check($data)) {
                    throw new Exception($validate->getError());
                }
                
                // 检查彩种是否存在
                $lotteryType = LotteryType::where('type_code', $data['type_code'])->find();
                if (!$lotteryType) {
                    throw new Exception('彩种不存在');
                }
                
                // 检查期号是否已存在（排除自己）
                $exists = $this->model->where('type_code', $data['type_code'])
                    ->where('period_no', $data['period_no'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    throw new Exception('该期号的服务费记录已存在');
                }
                
                $data['update_time'] = time() * 1000;
                $result = $row->save($data);
                $this->model->commit();
            } catch (Exception $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        // 获取彩种列表
        $lotteryTypes = LotteryType::where('is_enabled', 1)
            ->field('type_code,type_name')
            ->select()
            ->toArray();
        
        $this->success('', [
            'row' => $row,
            'lotteryTypes' => $lotteryTypes,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 删除服务费记录
     */
    public function del(): void
    {
        $ids = $this->request->param('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', ['ids']));
        }

        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $count = $this->model->where($this->dataLimitField, 'in', $adminIds)->where($pk, 'in', $ids)->count();
            if ($count != count($ids)) {
                $this->error(__('You can only delete your own data'));
            }
        }

        $data = $this->model->where($pk, 'in', $ids)->select();
        $count = 0;
        $this->model->startTrans();
        try {
            foreach ($data as $v) {
                $count += $v->delete();
            }
            $this->model->commit();
        } catch (Exception $e) {
            $this->model->rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success(__('Deleted successfully'));
        } else {
            $this->error(__('No rows were deleted'));
        }
    }

    /**
     * 获取统计数据
     */
    public function getStatistics(): void
    {
        $typeCode = $this->request->param('type_code', '');
        $startTime = $this->request->param('start_time', '');
        $endTime = $this->request->param('end_time', '');
        
        $where = [];
        if ($typeCode) {
            $where[] = ['type_code', '=', $typeCode];
        }
        if ($startTime) {
            $where[] = ['update_time', '>=', strtotime($startTime) * 1000];
        }
        if ($endTime) {
            $where[] = ['update_time', '<=', strtotime($endTime . ' 23:59:59') * 1000];
        }
        
        $statistics = $this->model->where($where)->field([
            'COUNT(*) as total_count',
            'SUM(bonus_system) as total_bonus',
            'AVG(bonus_system) as avg_bonus',
            'MAX(bonus_system) as max_bonus',
            'MIN(bonus_system) as min_bonus'
        ])->find();
        
        $this->success('', $statistics);
    }
}