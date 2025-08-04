<?php

namespace app\admin\controller\lottery;

use app\common\controller\Backend;
use app\common\model\LotteryBonus as BonusModel;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 游戏赔率管理
 */
class LotteryBonus extends Backend
{
    /**
     * Bonus模型对象
     * @var BonusModel
     */
    protected object $model;
    
    protected array $withJoinTable = ['lotteryType'];

    protected string|array $defaultSortField = ['lottery_id' => 'desc'];
    
    protected string|array $quickSearchField = ['name', 'key'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new BonusModel();
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
                // 处理bonus_json数据
                if (isset($data['bonus_json']) && is_array($data['bonus_json'])) {
                    $data['bonus_json'] = json_encode($data['bonus_json'], JSON_UNESCAPED_UNICODE);
                }

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
                // 处理bonus_json数据
                if (isset($data['bonus_json']) && is_array($data['bonus_json'])) {
                    $data['bonus_json'] = json_encode($data['bonus_json'], JSON_UNESCAPED_UNICODE);
                }

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
        // 游戏赔率不允许删除，只能编辑
        $this->error(__('Delete operation is not allowed'));
    }
    
}