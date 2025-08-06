<?php

namespace app\admin\controller\user;

use Throwable;
use app\common\controller\Backend;
use app\common\model\User as UserModel;
use app\service\UserService;

class User extends Backend
{
    /**
     * @var object
     * @phpstan-var UserModel
     */
    protected object $model;

    /**
     * @var UserService
     */
    protected UserService $userService;

    /**
     * @var \app\service\FinanceService
     */
    protected $financeService;

    protected array $withJoinTable = ['userGroup', 'parentUser'];

    // 排除字段
    protected string|array $preExcludeFields = ['last_login_time', 'login_failure', 'password', 'salt'];

    protected string|array $quickSearchField = ['username', 'nickname', 'id', 'real_name', 'email', 'mobile'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new UserModel();
        $this->userService = new UserService();
        $this->financeService = new \app\service\FinanceService();
    }

    /**
     * 查看
     * @throws Throwable
     */
    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withoutField('password,salt')
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

    public function selTag(): void
    {
        if ($this->request->param('user_tag')) {
            $userTags = $this->request->param('user_tag');
            if (!is_array($userTags)) {
                $userTags = [$userTags];
            }
            
            // 构建 FIND_IN_SET 查询条件
            $tagConditions = [];
            foreach ($userTags as $tag) {
                $tagConditions[] = "FIND_IN_SET('{$tag}', user_tag)";
            }
            
            // 使用原生 SQL 进行查询
            $res = $this->model->field('id,is_agent,username,nickname')->whereRaw('(' . implode(' OR ', $tagConditions) . ')')->select();
            $this->success('ok', $res);
        }
    }

    /**
     * 调整用户余额
     * @throws Throwable
     */
    public function setMoney(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $amount = $this->request->post('money');
            $type = $this->request->post('type'); // 'ADMIN_ADD' 或 'ADMIN_DEDUCT'
            $memo = $this->request->post('memo', '');

            if (!$userId || !is_numeric($amount) || !in_array($type, ['ADMIN_ADD', 'ADMIN_DEDUCT'])) {
                $this->error('参数错误');
            }

            // 扣除操作时金额为负数
            $adjustAmount = $type === 'ADMIN_DEDUCT' ? -abs(floatval($amount)) : abs(floatval($amount));

            try {
                $memoBefore = $amount > 0 ? '管理员充值' : '管理员扣除';
                $this->financeService->adjustUserBalance($userId, $adjustAmount, $memoBefore.'：'.$memo, $type);
                $this->jsonReturn($type === 'ADMIN_ADD' ? '充值成功' : '扣除成功');
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }else{
            $userId = $this->request->param('user_id');
            if ($userId) {
                $user = $this->model->find($userId);
                if ($user) {
                    $this->success('', [
                        'user' => [
                            'id' => $user->id,
                            'username' => $user->username,
                            'nickname' => $user->nickname,
                            'money' => $user->money,
                            'unwith_money' => $user->unwith_money
                        ]
                    ]);
                }
            }
            $this->error('用户不存在');
        }
    }

    /**
     * 添加
     * @throws Throwable
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error('参数不能为空');
            }
            $data['parent_id'] = $data['parent_id'] ?? 0;
            if($data['is_agent'] == 0 && $data['parent_id'] < 1){
                $this->error('必须选择上级代理商');
            }

            try {
                $user = $this->userService->createUser($data);
                $this->jsonReturn('添加成功', ['id' => $user->id]);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * 编辑
     * @throws Throwable
     */
    public function edit(): void
    {
        $pk  = $this->model->getPk();
        $id  = $this->request->param($pk);
        $row = $this->model->find($id);
        if (!$row) {
            $this->error('记录不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error('参数不能为空');
            }
            $data['parent_id'] = $data['parent_id'] ?? 0;
            if(isset($data['is_agent']) && $data['is_agent'] == 0 && $data['parent_id'] < 1){
                $this->error('必须选择上级代理商');
            }

            try {
                $this->userService->updateUser($id, $data);
                $this->jsonReturn('更新成功');
                return;
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        unset($row->salt);
        $row->password = '';
        $this->success('', [
            'row' => $row
        ]);
    }

    /**
     * 重写select
     * @throws Throwable
     */
    public function select(): void
    {
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $res = $this->model
            ->withoutField('password,salt')
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        foreach ($res as $re) {
            $re->nickname_text = $re->username . '(ID:' . $re->id . ')';
        }

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }
    
    /**
     * 调整用户余额
     * @throws Throwable
     */
    public function adjustBalance(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $amount = $this->request->post('amount');
            $remark = $this->request->post('remark', '');
            $type = $this->request->post('type', 'admin_adjust');

            if (!$userId || !is_numeric($amount)) {
                $this->error('参数错误');
            }

            try {
                $this->financeService->adjustUserBalance($userId, floatval($amount), $remark, $type);
                $this->jsonReturn('余额调整成功');
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->error('请求方式错误');
    }

    /**
     * 调整用户积分
     * @throws Throwable
     */
    public function adjustScore(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $score = $this->request->post('score');
            $remark = $this->request->post('remark', '');
            $type = $this->request->post('type', 'admin_adjust');

            if (!$userId || !is_numeric($score)) {
                $this->error('参数错误');
            }

            try {
                $this->financeService->adjustUserScore($userId, intval($score), $remark, $type);
                $this->jsonReturn('积分调整成功');
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->error('请求方式错误');
    }

    /**
     * 重置用户密码
     * @throws Throwable
     */
    public function resetPassword(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $newPassword = $this->request->post('password');

            if (!$userId || !$newPassword) {
                $this->error('参数错误');
            }

            try {
                $this->userService->resetPassword($userId, $newPassword);
                $this->jsonReturn('密码重置成功');
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->error('请求方式错误');
    }

    /**
     * 更改用户状态
     * @throws Throwable
     */
    public function changeStatus(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $status = $this->request->post('status');

            if (!$userId || !in_array($status, [0, 1, 2])) {
                $this->error('参数错误');
            }

            try {
                $this->userService->changeUserStatus($userId, $status);
                $this->jsonReturn('状态更改成功');
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->error('请求方式错误');
    }

    /**
     * 实名认证
     * @throws Throwable
     */
    public function realNameAuth(): void
    {
        if ($this->request->isPost()) {
            $userId = $this->request->post('user_id');
            $realName = $this->request->post('real_name');
            $idCard = $this->request->post('id_card');

            if (!$userId || !$realName || !$idCard) {
                $this->error('参数错误');
            }

            try {
                $this->userService->realNameAuth($userId, $realName, $idCard);
                $this->jsonReturn('实名认证成功');
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        $this->error('请求方式错误');
    }
}