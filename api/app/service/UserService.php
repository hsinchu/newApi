<?php

namespace app\service;

use app\common\model\User;
use think\exception\ValidateException;

/**
 * 用户服务类
 * 处理用户相关的业务逻辑
 */
class UserService
{
    /**
     * @var FinanceService
     */
    private $financeService;

    public function __construct()
    {
        $this->financeService = new FinanceService();
    }
    /**
     * 创建用户
     * @param array $data 用户数据
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        // 验证必填字段
        if (empty($data['username'])) {
            throw new ValidateException('用户名不能为空');
        }
        if (empty($data['password'])) {
            throw new ValidateException('密码不能为空');
        }
        if (empty($data['nickname'])) {
            throw new ValidateException('昵称不能为空');
        }

        // 检查用户名是否已存在
        $existUser = User::where('username', $data['username'])->find();
        if ($existUser) {
            throw new ValidateException('用户名已存在');
        }

        // 检查邮箱是否已存在
        if (!empty($data['email'])) {
            $existEmail = User::where('email', $data['email'])->find();
            if ($existEmail) {
                throw new ValidateException('邮箱已存在');
            }
        }

        // 检查手机号是否已存在
        if (!empty($data['mobile'])) {
            $existMobile = User::where('mobile', $data['mobile'])->find();
            if ($existMobile) {
                throw new ValidateException('手机号已存在');
            }
        }

        // 设置默认值
        $userData = [
            'username' => $data['username'],
            'nickname' => $data['nickname'],
            'password' => hash_password($data['password']),
            'group_id' => $data['group_id'] ?? 1,
            'parent_id' => $data['parent_id'] ?? 0,
            'is_agent' => $data['is_agent'] ?? 0,
            'user_tag' => $data['user_tag'] ?? '',
            'email' => $data['email'] ?? '',
            'mobile' => $data['mobile'] ?? '',
            'real_name' => $data['real_name'] ?? '',
            'id_card' => $data['id_card'] ?? '',
            'gender' => $data['gender'] ?? 0,
            'birthday' => $data['birthday'] ?? null,
            'avatar' => $data['avatar'] ?? '',
            'money' => 0,
            'unwith_money' => 0,
            'score' => 0,
            'invite_code' => $this->generateInviteCode(),
            'status' => $data['status'] ?? 1,
            'motto' => $data['motto'] ?? '',
            'pay_password' => !empty($data['pay_password']) ? hash_password($data['pay_password']) : '',
            'is_verified' => $data['is_verified'] ?? 0,
            'salt' => ''
        ];

        $user = new User();
        $user->save($userData);
        
        return $user;
    }

    /**
     * 更新用户信息
     * @param int $userId 用户ID
     * @param array $data 更新数据
     * @return bool
     * @throws \Exception
     */
    public function updateUser(int $userId, array $data): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 如果更新用户名，检查是否重复
        if (isset($data['username']) && $data['username'] !== $user->username) {
            $existUser = User::where('username', $data['username'])->where('id', '<>', $userId)->find();
            if ($existUser) {
                throw new ValidateException('用户名已存在');
            }
        }

        // 如果更新邮箱，检查是否重复
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $existEmail = User::where('email', $data['email'])->where('id', '<>', $userId)->find();
            if ($existEmail) {
                throw new ValidateException('邮箱已存在');
            }
        }

        // 如果更新手机号，检查是否重复
        if (isset($data['mobile']) && $data['mobile'] !== $user->mobile) {
            $existMobile = User::where('mobile', $data['mobile'])->where('id', '<>', $userId)->find();
            if ($existMobile) {
                throw new ValidateException('手机号已存在');
            }
        }

        // 如果有密码更新，进行加密
        if (!empty($data['password'])) {
            $data['password'] = hash_password($data['password']);
            $data['salt'] = '';
        } else {
            unset($data['password']);
        }

        // 如果有支付密码更新，进行加密
        if (!empty($data['pay_password'])) {
            $data['pay_password'] = hash_password($data['pay_password']);
        } else {
            unset($data['pay_password']);
        }

        // 如果是代理商且修改了默认返佣比例，需要调整下级会员的返佣比例
        if ($user->is_agent == 1) {
            $this->adjustSubMemberRebateRates($userId, $data);
        }

        return $user->save($data);
    }

    /**
     * 删除用户
     * @param int $userId 用户ID
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 检查用户是否有余额
        if ($user->money > 0) {
            throw new ValidateException('用户还有余额，无法删除');
        }

        return $user->delete();
    }

    /**
     * 调整用户余额
     * @param int $userId 用户ID
     * @param float $amount 调整金额（正数为增加，负数为减少）
     * @param string $remark 备注
     * @param string $type 变动类型
     * @return bool
     * @throws \Exception
     */
    public function adjustUserBalance(int $userId, float $amount, string $remark = '', string $type = 'admin_adjust'): bool
    {
        return $this->financeService->adjustUserBalance($userId, $amount, $remark, $type);
    }

    /**
     * 调整用户积分
     * @param int $userId 用户ID
     * @param int $score 调整积分（正数为增加，负数为减少）
     * @param string $remark 备注
     * @param string $type 变动类型
     * @return bool
     * @throws \Exception
     */
    public function adjustUserScore(int $userId, int $score, string $remark = '', string $type = 'admin_adjust'): bool
    {
        return $this->financeService->adjustUserScore($userId, $score, $remark, $type);
    }

    /**
     * 获取用户详细信息
     * @param int $userId 用户ID
     * @return User|null
     */
    public function getUserInfo(int $userId): ?User
    {
        return User::with(['userGroup'])->find($userId);
    }

    /**
     * 获取用户列表
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getUserList(array $where = [], int $page = 1, int $limit = 15): array
    {
        $query = User::with(['userGroup']);
        
        // 添加查询条件
        if (!empty($where['username'])) {
            $query->where('username', 'like', '%' . $where['username'] . '%');
        }
        if (!empty($where['nickname'])) {
            $query->where('nickname', 'like', '%' . $where['nickname'] . '%');
        }
        if (!empty($where['email'])) {
            $query->where('email', 'like', '%' . $where['email'] . '%');
        }
        if (!empty($where['mobile'])) {
            $query->where('mobile', 'like', '%' . $where['mobile'] . '%');
        }
        if (isset($where['status'])) {
            $query->where('status', $where['status']);
        }
        if (isset($where['group_id'])) {
            $query->where('group_id', $where['group_id']);
        }

        $result = $query->withoutField('password,salt')
                       ->order('id', 'desc')
                       ->paginate($limit, false, ['page' => $page]);

        return [
            'list' => $result->items(),
            'total' => $result->total(),
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * 生成邀请码
     * @return string
     */
    private function generateInviteCode(): string
    {
        do {
            // 生成8位随机邀请码
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
            $exists = User::where('invite_code', $code)->find();
        } while ($exists);
        
        return $code;
    }

    /**
     * 重置用户密码
     * @param int $userId 用户ID
     * @param string $newPassword 新密码
     * @return bool
     * @throws \Exception
     */
    public function resetPassword(int $userId, string $newPassword): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        return $user->resetPassword($userId, $newPassword) > 0;
    }

    /**
     * 更改用户状态
     * @param int $userId 用户ID
     * @param int $status 状态 (0: 审核中, 1: 启用, 2: 禁用)
     * @return bool
     * @throws \Exception
     */
    public function changeUserStatus(int $userId, int $status): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        $user->status = $status;
        return $user->save();
    }

    /**
     * 实名认证
     * @param int $userId 用户ID
     * @param string $realName 真实姓名
     * @param string $idCard 身份证号
     * @return bool
     * @throws \Exception
     */
    public function realNameAuth(int $userId, string $realName, string $idCard): bool
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 检查身份证号是否已被使用
        $existIdCard = User::where('id_card', $idCard)->where('id', '<>', $userId)->find();
        if ($existIdCard) {
            throw new ValidateException('身份证号已被使用');
        }

        $user->real_name = $realName;
        $user->id_card = $idCard;
        return $user->save();
    }

    /**
     * 调整下级会员的返佣比例
     * @param int $agentId 代理商ID
     * @param array $data 更新的数据
     * @return void
     * @throws \Exception
     */
    private function adjustSubMemberRebateRates(int $agentId, array $data): void
    {
        // 检查是否修改了默认返佣比例
        $needUpdateDefaultRebate = isset($data['rebate_rate']);
        $needUpdateDefaultNowin = isset($data['nowin_rate']);
        
        if (!$needUpdateDefaultRebate && !$needUpdateDefaultNowin) {
            return;
        }

        // 获取下级会员列表
        $subMembers = User::where('parent_id', $agentId)->select();
        
        foreach ($subMembers as $member) {
            $updateMemberData = [];
            
            // 处理默认投注返佣比例
            if ($needUpdateDefaultRebate) {
                $newDefaultRebateRate = (float)$data['rebate_rate'];
                
                // 如果会员的默认返佣比例大于新设置的值，则调整为新值
                if ($member->default_rebate_rate > $newDefaultRebateRate) {
                    $updateMemberData['default_rebate_rate'] = $newDefaultRebateRate;
                }
                
                // 如果会员的正式返佣比例大于新设置的值，则调整为新值（-1表示使用默认值，不需要调整）
                if ($member->rebate_rate != -1 && $member->rebate_rate > $newDefaultRebateRate) {
                    $updateMemberData['rebate_rate'] = $newDefaultRebateRate;
                }
            }
            
            // 处理默认不中奖返佣比例
            if ($needUpdateDefaultNowin) {
                $newDefaultNowinRate = (float)$data['nowin_rate'];
                
                // 如果会员的默认不中奖返佣比例大于新设置的值，则调整为新值
                if ($member->default_nowin_rate > $newDefaultNowinRate) {
                    $updateMemberData['default_nowin_rate'] = $newDefaultNowinRate;
                }
                
                // 如果会员的正式不中奖返佣比例大于新设置的值，则调整为新值（-1表示使用默认值，不需要调整）
                if ($member->nowin_rate != -1 && $member->nowin_rate > $newDefaultNowinRate) {
                    $updateMemberData['nowin_rate'] = $newDefaultNowinRate;
                }
            }
            
            // 如果有需要更新的数据，则保存
            if (!empty($updateMemberData)) {
                $member->save($updateMemberData);
            }
        }
    }

    /**
     * 获取用户的佣金比例
     */
    public static function getUserBrokRate(int $userId): array
    {
        $user = User::find($userId);
        $parent = User::find($user->parent_id);

        //要确保用户的返佣不能高于代理的返佣
        $userRebateRate = $user->rebate_rate > $parent->rebate_rate ? $parent->rebate_rate : $user->rebate_rate;
        $userNowinRate = $user->nowin_rate > $parent->nowin_rate ? $parent->nowin_rate : $user->nowin_rate;

        $rebate_rate = $user->rebate_rate == -1 ? $parent->default_rebate_rate : $userRebateRate;
        $nowin_rate = $user->nowin_rate == -1 ? $parent->default_nowin_rate : $userNowinRate;
        return [
            'parent_id' => $user->parent_id,
            'rebate_rate' => $rebate_rate,
            'nowin_rate' => $nowin_rate,
            'agent_rebate_rate' => $parent->rebate_rate,
            'agent_nowin_rate' => $parent->nowin_rate,
        ];
    }
}