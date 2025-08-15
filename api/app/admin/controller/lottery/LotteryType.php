<?php

namespace app\admin\controller\lottery;

use app\common\model\LotteryType as LotteryTypeModel;
use app\common\model\BetOrder;
use app\common\model\LotteryDraw;
use app\common\controller\Backend;
use think\facade\Log;
use think\facade\Validate;
use Exception;

/**
 * 彩种管理控制器
 */
class LotteryType extends Backend
{
    /**
     * LotteryType模型对象
     * @var LotteryTypeModel
     */
    protected object $model;
    
    protected array|string $preExcludeFields = ['id', 'create_time', 'update_time'];
    
    protected string|array $quickSearchField = ['type_name', 'type_code'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new LotteryTypeModel();
    }

    /**
     * 添加彩种
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
                // 验证彩种数据
                $this->validateLotteryTypeData($data);
                
                // 检查彩种代码是否重复
                $exists = $this->model->where('type_code', $data['type_code'])->find();
                if ($exists) {
                    throw new Exception('彩种代码已存在');
                }
                
                // 设置默认值
                $data['status'] = $data['status'] ?? 1;
                $data['sort'] = $data['sort'] ?? 0;
                $data['created_by'] = $this->auth->id;
                
                $result = $this->model->save($data);
                $this->model->commit();
                
                // 记录操作日志
                Log::info('管理员添加彩种', [
                    'admin_id' => $this->auth->id,
                    'lottery_type_id' => $this->model->id,
                    'type_code' => $data['type_code'],
                    'type_name' => $data['type_name']
                ]);
            } catch (Exception $e) {
                $this->model->rollback();
                Log::error('添加彩种失败', [
                    'admin_id' => $this->auth->id,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        // 获取彩种分类列表
        $categories = $this->getLotteryCategories();
        
        $this->success('', [
            'categories' => $categories,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 编辑彩种
     */
    public function edit(): void
    {
        $id = $this->request->param($this->model->getPk());
        
        // 参数验证
        $validate = Validate::rule([
            'id' => 'require|integer|gt:0'
        ]);
        
        if (!$validate->check(['id' => $id])) {
            $this->error($validate->getError());
        }
        
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
                if (count($data) > 2) {
                    $this->validateLotteryTypeData($data);
                    // 检查彩种代码是否重复（排除自己）
                    $exists = $this->model
                        ->where('type_code', $data['type_code'])
                        ->where('id', '<>', $id)
                        ->find();
                    if ($exists) {
                        throw new Exception('彩种代码已存在');
                    }
                }                
                
                // 如果禁用彩种，检查是否有未结算的订单
                if (isset($data['is_enabled']) && $data['is_enabled'] == 0 && $row->status == 1) {
                    $pendingOrders = BetOrder::where('lottery_type_id', $id)
                        ->whereIn('status', ['PENDING', 'CONFIRMED'])
                        ->count();
                    if ($pendingOrders > 0) {
                        throw new Exception('该彩种还有未结算的订单，无法禁用');
                    }
                }
                
                $data['updated_by'] = $this->auth->id;
                
                $result = $row->save($data);
                $this->model->commit();
                
                // 记录操作日志
                Log::info('管理员编辑彩种', [
                    'admin_id' => $this->auth->id,
                    'lottery_type_id' => $id,
                    'changes' => array_diff_assoc($data, $row->getOrigin())
                ]);
            } catch (Exception $e) {
                $this->model->rollback();
                Log::error('编辑彩种失败', [
                    'admin_id' => $this->auth->id,
                    'lottery_type_id' => $id,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        // 获取彩种分类列表
        $categories = $this->getLotteryCategories();
        
        $this->success('', [
            'row' => $row,
            'categories' => $categories,
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 切换状态
     */
    public function toggle(): void
    {
        $ids = $this->request->param('ids');
        $field = $this->request->param('field', 'status');
        $value = $this->request->param('value');
        
        // 参数验证
        $validate = Validate::rule([
            'ids' => 'require',
            'field' => 'require|in:status',
            'value' => 'require|in:0,1'
        ]);
        
        if (!$validate->check(['ids' => $ids, 'field' => $field, 'value' => $value])) {
            $this->error($validate->getError());
        }
        
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        
        // 验证ID格式
        foreach ($ids as $id) {
            if (!is_numeric($id) || $id <= 0) {
                $this->error('ID格式错误');
            }
        }
        
        $this->model->startTrans();
        try {
            $successCount = 0;
            $failedItems = [];
            
            foreach ($ids as $id) {
                $row = $this->model->find($id);
                if (!$row) {
                    $failedItems[] = "ID {$id} 不存在";
                    continue;
                }
                
                // 如果要禁用彩种，检查是否有未结算的订单
                if ($field === 'status' && $value == 0 && $row->status == 1) {
                    $pendingOrders = BetOrder::where('lottery_type_id', $id)
                        ->whereIn('status', ['PENDING', 'CONFIRMED'])
                        ->count();
                    if ($pendingOrders > 0) {
                        $failedItems[] = "彩种 {$row->type_name} 还有未结算的订单，无法禁用";
                        continue;
                    }
                }
                
                $oldValue = $row->$field;
                $row->save([
                    $field => $value,
                    'updated_by' => $this->auth->id
                ]);
                
                $successCount++;
                
                // 记录操作日志
                Log::info('管理员切换彩种状态', [
                    'admin_id' => $this->auth->id,
                    'lottery_type_id' => $id,
                    'type_name' => $row->type_name,
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $value
                ]);
            }
            
            $this->model->commit();
            
            $message = "成功操作 {$successCount} 项";
            if (!empty($failedItems)) {
                $message .= "，失败：" . implode('；', $failedItems);
            }
            
            $this->success($message);
        } catch (Exception $e) {
            $this->model->rollback();
            Log::error('切换彩种状态失败', [
                'admin_id' => $this->auth->id,
                'ids' => $ids,
                'field' => $field,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取彩种列表（用于下拉选择）
     */
    public function getList(): void
    {
        try {
            $list = $this->model
                ->field('id,type_code,type_name,category')
                ->order('sort_order desc, id asc')
                ->select();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
            
        $this->success('获取成功', $list);
    }

    /**
     * 验证彩种数据
     */
    private function validateLotteryTypeData(array $data): void
    {
        $validate = Validate::rule([
            'type_code' => 'require|regex:/^[a-zA-Z0-9_]+$/|length:2,20',
            'type_name' => 'require|length:2,50',
            'category' => 'require|in:SPORTS,WELFARE,SPORTS_SINGLE,QUICK',
            'max_bet_amount' => 'require|regex:/^\d+(\.\d{1,2})?$/|gt:0',
            'min_bet_amount' => 'require|regex:/^\d+(\.\d{1,2})?$/|gt:0',
            'status' => 'in:0,1',
            'sort' => 'integer|egt:0'
        ]);
        
        if (!$validate->check($data)) {
            throw new Exception($validate->getError());
        }
        
        // 验证投注金额范围
        if ($data['min_bet_amount'] >= $data['max_bet_amount']) {
            throw new Exception('最小投注金额必须小于最大投注金额');
        }
    }

    /**
     * 获取启用的彩种列表
     */
    public function getEnabledList(): void
    {
        try {
            $list = $this->model
                ->where('is_enabled', 1)
                ->order('sort_order', 'asc')
                ->column('type_name', 'id');
            
            $this->jsonReturn('获取成功', $list);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 生成期号
     */
    public function generateDrawNo(): void
    {
        $lotteryTypeId = $this->request->param('lottery_type_id');
        
        // 参数验证
        $validate = Validate::rule([
            'lottery_type_id' => 'require|integer|gt:0'
        ]);
        
        if (!$validate->check(['lottery_type_id' => $lotteryTypeId])) {
            $this->error($validate->getError());
        }
        
        try {
            $lotteryType = $this->model->find($lotteryTypeId);
            if (!$lotteryType) {
                $this->error('彩种不存在');
            }
            
            // 获取今天的日期
            $today = date('Ymd');
            
            // 查找今天最大的期号
            $lastDrawNo = LotteryDraw::where('lottery_code', $lotteryType->type_code)
                ->where('period_no', 'like', $today . '%')
                ->order('period_no', 'desc')
                ->value('period_no');
            
            if ($lastDrawNo) {
                // 提取序号并加1
                $sequence = intval(substr($lastDrawNo, -3)) + 1;
            } else {
                $sequence = 1;
            }
            
            $drawNo = $today . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            
            $this->success('生成成功', ['draw_no' => $drawNo]);
        } catch (Exception $e) {
            Log::error('生成期号失败', [
                'admin_id' => $this->auth->id,
                'lottery_type_id' => $lotteryTypeId,
                'error' => $e->getMessage()
            ]);
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取彩种分类列表
     */
    private function getLotteryCategories(): array
    {
        return [
            'SPORTS' => '体育彩票',
            'WELFARE' => '福利彩票',
            'SPORTS_SINGLE' => '竞彩单场',
            'QUICK' => '快速彩'
        ];
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}