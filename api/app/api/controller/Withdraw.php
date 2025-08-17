<?php

namespace app\api\controller;

use app\common\controller\Frontend;
use app\common\model\WithdrawAccount;
use app\common\model\WithdrawRecord;
use app\common\model\User;
use app\common\model\UserMoneyLog;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 提现管理控制器
 */
class Withdraw extends Frontend
{
    protected array $noNeedLogin = [];    

    public function initialize(): void
    {
        parent::initialize();
    }
    
    /**
     * 获取提现账号列表
     */
    public function getAccounts()
    {
        $type = $this->request->get('type', '');
        
        $accounts = WithdrawAccount::getUserAccounts($this->auth->id, $type);
        
        $list = [];
        foreach ($accounts as $account) {
            $accountData = [
                'id' => $account->id,
                'type' => $account->type,
                'typeName' => $account->type_name,
                'accountName' => $account->account_name,
                'accountNumber' => $account->masked_account,
                'isDefault' => (bool)$account->is_default,
                'status' => $account->status,
                'createTime' => date('Y-m-d H:i:s', $account->create_time)
            ];
            
            // 根据账号类型添加特定字段
            switch ($account->type) {
                case WithdrawAccount::TYPE_ALIPAY:
                    if ($account->qr_code) {
                        $accountData['qrCode'] = $account->qr_code;
                    }
                    break;
                    
                case WithdrawAccount::TYPE_WECHAT:
                    if ($account->phone_number) {
                        $accountData['phoneNumber'] = $account->phone_number;
                    }
                    if ($account->qr_code) {
                        $accountData['qrCode'] = $account->qr_code;
                    }
                    break;
                    
                case WithdrawAccount::TYPE_BANK:
                    $accountData['bankName'] = $account->bank_name;
                    if ($account->bank_branch) {
                        $accountData['bankBranch'] = $account->bank_branch;
                    }
                    break;
            }
            
            $list[] = $accountData;
        }
        
        $this->success('获取成功', $list);
    }
    
    /**
     * 添加提现账号
     */
    public function addAccount()
    {
        $data = $this->request->post();
        
        // 检查是否可以添加新账号
        $this->checkCanAddAccount();
        
        // 验证必填字段
        $this->validateAccountData($data);
        
        try {
            Db::startTrans();
            
            $accountData = [
                'user_id' => $this->auth->id,
                'type' => $data['type'],
                'account_name' => $data['accountName'],
                'status' => WithdrawAccount::STATUS_ENABLED,
                'is_default' => 0,
                'create_time' => time(),
                'update_time' => time()
            ];
            
            // 根据类型设置不同字段
            switch ($data['type']) {
                case WithdrawAccount::TYPE_ALIPAY:
                    $accountData['account_number'] = $data['alipayAccount'];
                    $accountData['qr_code'] = $data['alipayQrCode'] ?? '';
                    break;
                    
                case WithdrawAccount::TYPE_WECHAT:
                    $accountData['account_number'] = $data['wechatAccount'];
                    $accountData['phone_number'] = $data['phoneNumber'] ?? '';
                    $accountData['qr_code'] = $data['wechatQrCode'] ?? '';
                    break;
                    
                case WithdrawAccount::TYPE_BANK:
                    $accountData['account_number'] = $data['bankCardNumber'];
                    $accountData['bank_name'] = $data['bankName'];
                    $accountData['bank_branch'] = $data['bankBranch'] ?? '';
                    break;
                    
                default:
                    throw new ValidateException('不支持的账号类型');
            }
            
            $account = WithdrawAccount::create($accountData);
            
            // 如果是用户的第一个账号，设为默认
            $userAccountCount = WithdrawAccount::getUserAccountCount($this->auth->id);
            
            if ($userAccountCount == 1) {
                $account->setDefault();
            }
            
            Db::commit();
            
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
            
        // 返回完整的账号信息
        $accountData = [
            'id' => $account->id,
            'type' => $account->type,
            'typeName' => $account->type_name,
            'accountName' => $account->account_name,
            'accountNumber' => $account->masked_account,
            'isDefault' => (bool)$account->is_default,
            'status' => $account->status,
            'createTime' => date('Y-m-d H:i:s', $account->create_time)
        ];
        
        // 根据账号类型添加特定字段
        switch ($account->type) {
            case WithdrawAccount::TYPE_ALIPAY:
                if ($account->qr_code) {
                    $accountData['qrCode'] = $account->qr_code;
                }
                break;
                
            case WithdrawAccount::TYPE_WECHAT:
                if ($account->phone_number) {
                    $accountData['phoneNumber'] = $account->phone_number;
                }
                if ($account->qr_code) {
                    $accountData['qrCode'] = $account->qr_code;
                }
                break;
                
            case WithdrawAccount::TYPE_BANK:
                $accountData['bankName'] = $account->bank_name;
                if ($account->bank_branch) {
                    $accountData['bankBranch'] = $account->bank_branch;
                }
                break;
        }
        
        $this->success('添加成功', $accountData);
    }
    
    /**
     * 更新提现账号
     */
    public function updateAccount()
    {
        $id = $this->request->post('id');
        $data = $this->request->post();
        
        $account = WithdrawAccount::checkOwnership($id, $this->auth->id);
        
        if (!$account) {
            $this->error('账号不存在或无权限操作');
        }
        
        // 验证数据（传递账号ID用于排除重复检查）
        $data['id'] = $id;
        $this->validateAccountData($data, $account->type);
        
        try {
            $updateData = [
                'account_name' => $data['accountName'],
                'update_time' => time()
            ];
            
            // 根据类型更新不同字段
            switch ($account->type) {
                case WithdrawAccount::TYPE_ALIPAY:
                    $updateData['account_number'] = $data['alipayAccount'];
                    if (isset($data['alipayQrCode'])) {
                        $updateData['qr_code'] = $data['alipayQrCode'];
                    }
                    break;
                    
                case WithdrawAccount::TYPE_WECHAT:
                    $updateData['account_number'] = $data['wechatAccount'];
                    if (isset($data['phoneNumber'])) {
                        $updateData['phone_number'] = $data['phoneNumber'];
                    }
                    if (isset($data['wechatQrCode'])) {
                        $updateData['qr_code'] = $data['wechatQrCode'];
                    }
                    break;
                    
                case WithdrawAccount::TYPE_BANK:
                    $updateData['account_number'] = $data['bankCardNumber'];
                    $updateData['bank_name'] = $data['bankName'];
                    if (isset($data['bankBranch'])) {
                        $updateData['bank_branch'] = $data['bankBranch'];
                    }
                    break;
            }
            
            $account->save($updateData);
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
            
        // 重新获取更新后的账号信息
            $account->refresh();
            
            // 返回完整的账号信息
            $accountData = [
                'id' => $account->id,
                'type' => $account->type,
                'typeName' => $account->type_name,
                'accountName' => $account->account_name,
                'accountNumber' => $account->masked_account,
                'isDefault' => (bool)$account->is_default,
                'status' => $account->status,
                'updateTime' => date('Y-m-d H:i:s', $account->update_time)
            ];
            
            // 根据账号类型添加特定字段
            switch ($account->type) {
                case WithdrawAccount::TYPE_ALIPAY:
                    if ($account->qr_code) {
                        $accountData['qrCode'] = $account->qr_code;
                    }
                    break;
                    
                case WithdrawAccount::TYPE_WECHAT:
                    if ($account->phone_number) {
                        $accountData['phoneNumber'] = $account->phone_number;
                    }
                    if ($account->qr_code) {
                        $accountData['qrCode'] = $account->qr_code;
                    }
                    break;
                    
                case WithdrawAccount::TYPE_BANK:
                    $accountData['bankName'] = $account->bank_name;
                    if ($account->bank_branch) {
                        $accountData['bankBranch'] = $account->bank_branch;
                    }
                    break;
            }
            
            $this->success('更新成功', $accountData);
    }
    
    /**
     * 删除提现账号
     */
    public function deleteAccount()
    {
        $id = $this->request->post('id');
        
        $account = WithdrawAccount::checkOwnership($id, $this->auth->id);
        
        if (!$account) {
            $this->error('账号不存在或无权限操作');
        }
        
        // 检查是否有未完成的提现记录
        $pendingRecord = WithdrawRecord::where('account_id', $id)
            ->whereIn('status', [WithdrawRecord::STATUS_PENDING, WithdrawRecord::STATUS_APPROVED])
            ->find();
        
        if ($pendingRecord) {
            $this->error('该账号有未完成的提现记录，无法删除');
        }
        
        try {
            Db::startTrans();
            
            $account->save([
                'status' => WithdrawAccount::STATUS_DISABLED,
                'update_time' => time()
            ]);
            
            // 如果删除的是默认账号，需要设置新的默认账号
            if ($account->is_default) {
                $newDefault = WithdrawAccount::where('user_id', $this->auth->id)
                    ->where('status', WithdrawAccount::STATUS_ENABLED)
                    ->where('id', '<>', $id)
                    ->order('create_time desc')
                    ->find();
                
                if ($newDefault) {
                    $newDefault->setDefault();
                }
            }
            
            Db::commit();
            $this->success('删除成功');
            
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 设置默认账号
     */
    public function setDefaultAccount()
    {
        $id = $this->request->post('id');
        
        $account = WithdrawAccount::checkOwnership($id, $this->auth->id);
        
        if (!$account) {
            $this->error('账号不存在或无权限操作');
        }
        
        try {
            $account->setDefault();
            $this->success('设置成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 获取账号详情
     */
    public function getAccountDetail()
    {
        $id = $this->request->get('id');
        
        if (!$id) {
            $this->error('账号ID不能为空');
        }
        
        $account = WithdrawAccount::checkOwnership($id, $this->auth->id);
        
        if (!$account) {
            $this->error('账号不存在或无权限操作');
        }
        
        $accountData = [
            'id' => $account->id,
            'type' => $account->type,
            'typeName' => $account->type_name,
            'accountName' => $account->account_name,
            'accountNumber' => $account->account_number, // 返回完整账号用于编辑
            'maskedAccount' => $account->masked_account,
            'isDefault' => (bool)$account->is_default,
            'status' => $account->status,
            'statusName' => $account->status_name,
            'createTime' => date('Y-m-d H:i:s', $account->create_time),
            'updateTime' => date('Y-m-d H:i:s', $account->update_time)
        ];
        
        // 根据账号类型添加特定字段
        switch ($account->type) {
            case WithdrawAccount::TYPE_ALIPAY:
                if ($account->qr_code) {
                    $accountData['qrCode'] = $account->qr_code;
                }
                break;
                
            case WithdrawAccount::TYPE_WECHAT:
                if ($account->phone_number) {
                    $accountData['phoneNumber'] = $account->phone_number;
                }
                if ($account->qr_code) {
                    $accountData['qrCode'] = $account->qr_code;
                }
                break;
                
            case WithdrawAccount::TYPE_BANK:
                $accountData['bankName'] = $account->bank_name;
                if ($account->bank_branch) {
                    $accountData['bankBranch'] = $account->bank_branch;
                }
                break;
        }
        
        $this->success('获取成功', $accountData);
    }
    
    /**
     * 获取账号统计信息
     */
    public function getAccountStats()
    {
        $userId = $this->auth->id;
        
        $stats = [
            'total' => WithdrawAccount::getUserAccountCount($userId),
            'alipay' => WithdrawAccount::getUserAccountCount($userId, WithdrawAccount::TYPE_ALIPAY),
            'wechat' => WithdrawAccount::getUserAccountCount($userId, WithdrawAccount::TYPE_WECHAT),
            'bank' => WithdrawAccount::getUserAccountCount($userId, WithdrawAccount::TYPE_BANK),
            'hasDefault' => WithdrawAccount::getDefaultAccount($userId) ? true : false
        ];
        
        $this->success('获取成功', $stats);
    }
    
    /**
     * 提交提现申请
     */
    public function submit()
    {
        $accountId = $this->request->post('accountId');
        $amount = floatval($this->request->post('amount'));
        $payPassword = $this->request->post('payPassword', '');
        $remark = $this->request->post('remark', '');
        
        // 验证提现金额
        if ($amount <= 0) {
            $this->error('提现金额必须大于0');
        }
        
        // 获取系统配置
        $minAmount = config('withdraw.min_amount', 50);
        $maxAmount = config('withdraw.max_amount', 10000);
        $feeRate = config('withdraw.fee_rate', 0);
        
        if ($amount < $minAmount) {
            $this->error('提现金额不能少于' . $minAmount . '元');
        }
        
        if ($amount > $maxAmount) {
            $this->error('提现金额不能超过' . $maxAmount . '元');
        }
        
        // 验证支付密码
        $user = User::find($this->auth->id);
        if (!$user->checkPayPassword($payPassword)) {
            $this->error('支付密码错误');
        }
        
        // 验证账号
        $account = WithdrawAccount::checkOwnership($accountId, $this->auth->id);
        
        if (!$account) {
            $this->error('提现账号不存在或无权限操作');
        }
        
        // 检查余额
        if ($user->money < $amount) {
            $this->error('余额不足');
        }
        
        try {
            Db::startTrans();
            
            // 计算手续费
            $fee = $amount * $feeRate / 100;
            
            // 创建提现记录
            $record = WithdrawRecord::createRecord($this->auth->id, $accountId, $amount, $fee, $remark);
            
            // 扣除用户余额
            $user->money -= $amount;
            $user->save();
            
            // 记录资金变动
            UserMoneyLog::create([
                'user_id' => $this->auth->id,
                'money' => -$amount,
                'before' => $user->money + $amount,
                'after' => $user->money,
                'memo' => '提现申请：' . $record->order_no,
                'createtime' => time()
            ]);
            
            Db::commit();
            
            $this->success('提现申请提交成功', [
                'orderNo' => $record->order_no,
                'amount' => $amount,
                'fee' => $fee,
                'actualAmount' => $amount - $fee
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 获取提现记录
     */
    public function getRecords()
    {
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 10);
        $status = $this->request->get('status', '');
        
        $query = WithdrawRecord::where('user_id', $this->auth->id)
            ->order('create_time desc');
        
        if ($status !== '') {
            $query->where('status', $status);
        }
        
        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page
        ]);
        
        $records = [];
        foreach ($list->items() as $record) {
            $records[] = [
                'id' => $record->id,
                'orderNo' => $record->order_no,
                'accountType' => $record->account_type,
                'accountName' => $record->account_name,
                'accountNumber' => $record->account_number,
                'bankName' => $record->bank_name,
                'amount' => $record->amount,
                'fee' => $record->fee,
                'actualAmount' => $record->actual_amount,
                'status' => $record->status,
                'statusName' => $record->status_name,
                'statusColor' => $record->status_color,
                'remark' => $record->remark,
                'adminRemark' => $record->admin_remark,
                'createTime' => date('Y-m-d H:i:s', $record->create_time),
                'auditTime' => $record->audit_time ? date('Y-m-d H:i:s', $record->audit_time) : '',
                'completeTime' => $record->complete_time ? date('Y-m-d H:i:s', $record->complete_time) : ''
            ];
        }
        
        $this->success('获取成功', [
            'list' => $records,
            'total' => $list->total(),
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * 获取提现记录列表（前端调用）
     */
    public function recordList()
    {
        $page = $this->request->post('page', 1);
        $limit = $this->request->post('limit', 10);
        $status = $this->request->post('status', '');
        
        $query = WithdrawRecord::where('user_id', $this->auth->id)
            ->order('create_time desc');
        
        if ($status !== '') {
            $query->where('status', $status);
        }
        
        $list = $query->paginate([
            'list_rows' => $limit,
            'page' => $page
        ]);
        
        $records = [];
        foreach ($list->items() as $record) {
            $records[] = [
                'id' => $record->id,
                'orderNo' => $record->order_no,
                'accountType' => $record->account_type,
                'accountName' => $record->account_name,
                'accountNumber' => $record->account_number,
                'bankName' => $record->bank_name,
                'amount' => $record->amount,
                'fee' => $record->fee,
                'actualAmount' => $record->actual_amount,
                'status' => $record->status,
                'statusName' => $record->status_name,
                'statusColor' => $record->status_color,
                'remark' => $record->remark,
                'adminRemark' => $record->admin_remark,
                'createTime' => date('Y-m-d H:i:s', $record->create_time),
                'auditTime' => $record->audit_time ? date('Y-m-d H:i:s', $record->audit_time) : '',
                'completeTime' => $record->complete_time ? date('Y-m-d H:i:s', $record->complete_time) : ''
            ];
        }
        
        $this->success('获取成功', [
            'list' => $records,
            'total' => $list->total(),
            'page' => $page,
            'limit' => $limit
        ]);
    }
    
    /**
     * 获取提现配置
     */
    public function getConfig()
    {
        $config = [
            'minAmount' => config('withdraw.min_amount', 50),
            'maxAmount' => config('withdraw.max_amount', 10000),
            'feeRate' => config('withdraw.fee_rate', 0),
            'workingDays' => config('withdraw.working_days', '1-3个工作日'),
            'dailyLimit' => config('withdraw.daily_limit', 50000), // 每日提现限额
            'monthlyLimit' => config('withdraw.monthly_limit', 200000), // 每月提现限额
            'maxAccountCount' => config('withdraw.max_account_count', 10), // 最大账号数量
            'notice' => config('withdraw.notice', [
                '提现申请提交后，将在1-3个工作日内到账',
                '请确保账号信息准确无误，错误信息可能导致提现失败',
                '提现手续费将从提现金额中扣除',
                '提现记录可在个人中心查看',
                '每个用户最多可添加10个提现账号',
                '为保障资金安全，大额提现可能需要人工审核'
            ])
        ];
        
        $this->success('获取成功', $config);
    }
    
    /**
     * 验证用户是否可以添加新账号
     */
    private function checkCanAddAccount()
    {
        $maxAccountCount = config('withdraw.max_account_count', 10);
        $currentCount = WithdrawAccount::getUserAccountCount($this->auth->id);
        
        if ($currentCount >= $maxAccountCount) {
            throw new ValidateException('最多只能添加' . $maxAccountCount . '个提现账号');
        }
    }
    
    /**
     * 验证账号数据
     */
    private function validateAccountData($data, $type = null)
    {
        $type = $type ?: $data['type'];
        
        // 验证账号类型
        if (!in_array($type, [WithdrawAccount::TYPE_ALIPAY, WithdrawAccount::TYPE_WECHAT, WithdrawAccount::TYPE_BANK])) {
            $this->error('账号类型错误');
        }
        
        // 验证账号名称（真实姓名）
        if (empty($data['accountName']) || !is_string($data['accountName'])) {
            $this->error('账号名称不能为空');   
        }
        
        if (mb_strlen($data['accountName']) > 100) {
            $this->error('账号名称长度不能超过100个字符');
        }
        
        // 验证真实姓名格式（中文姓名2-10个字符，英文姓名2-50个字符）
        if (!preg_match('/^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-zA-Z\s]{2,50}$/u', $data['accountName'])) {
            $this->error('请输入正确的真实姓名');
        }
        
        switch ($type) {
            case WithdrawAccount::TYPE_ALIPAY:
                // 验证支付宝账号
                if (empty($data['alipayAccount'])) {
                    $this->error('支付宝账号不能为空');
                }
                if (mb_strlen($data['alipayAccount']) > 100) {
                    $this->error('支付宝账号长度不能超过100个字符');
                }
                // 支付宝账号格式验证（手机号或邮箱）
                if (!preg_match('/^1[3-9]\d{9}$/', $data['alipayAccount']) && 
                    !filter_var($data['alipayAccount'], FILTER_VALIDATE_EMAIL)) {
                    $this->error('请输入正确的支付宝账号（手机号或邮箱）');
                }
                // 验证收款码（可选）
                if (isset($data['alipayQrCode']) && !empty($data['alipayQrCode'])) {
                    if (mb_strlen($data['alipayQrCode']) > 255) {
                        $this->error('收款码路径长度不能超过255个字符');
                    }
                }
                break;
                
            case WithdrawAccount::TYPE_WECHAT:
                // 验证微信号
                if (empty($data['wechatAccount'])) {
                    $this->error('微信号不能为空');
                }
                if (mb_strlen($data['wechatAccount']) > 100) {
                    $this->error('微信号长度不能超过100个字符');
                }
                // 微信号格式验证（6-20位字母、数字、下划线、减号）
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{5,19}$/', $data['wechatAccount'])) {
                    $this->error('微信号格式不正确（6-20位，字母开头，可包含字母数字下划线减号）');
                }
                // 验证手机号（可选）
                if (isset($data['phoneNumber']) && !empty($data['phoneNumber'])) {
                    if (!preg_match('/^1[3-9]\d{9}$/', $data['phoneNumber'])) {
                        $this->error('请输入正确的手机号');
                    }
                }
                // 验证收款码（可选）
                if (isset($data['wechatQrCode']) && !empty($data['wechatQrCode'])) {
                    if (mb_strlen($data['wechatQrCode']) > 255) {
                        $this->error('收款码路径长度不能超过255个字符');
                    }
                }
                break;
                
            case WithdrawAccount::TYPE_BANK:
                // 验证银行卡号
                if (empty($data['bankCardNumber'])) {
                    $this->error('银行卡号不能为空');
                }
                if (mb_strlen($data['bankCardNumber']) > 100) {
                    $this->error('银行卡号长度不能超过100个字符');
                }
                // 银行卡号格式验证（16-19位数字）
                if (!preg_match('/^\d{16,19}$/', $data['bankCardNumber'])) {
                    $this->error('请输入正确的银行卡号（16-19位数字）');
                }
                
                // 验证银行名称
                if (empty($data['bankName'])) {
                    $this->error('银行名称不能为空');
                }
                if (mb_strlen($data['bankName']) > 100) {
                    $this->error('银行名称长度不能超过100个字符');
                }
                
                // 银行卡绑定必须实名认证
                $user = $this->auth->getUser();
                if ($user->is_verified != 1) {
                    $this->error('银行卡绑定需要完成实名认证后才能操作');
                }
                
                // 验证持卡人姓名必须与实名认证姓名一致
                if ($data['accountName'] != $user->real_name) {
                    $this->error('持卡人姓名必须与实名认证姓名一致');
                }
                
                // 验证开户行（可选）
                if (isset($data['bankBranch']) && !empty($data['bankBranch'])) {
                    if (mb_strlen($data['bankBranch']) > 200) {
                        throw new ValidateException('开户行信息长度不能超过200个字符');
                    }
                }
                break;
        }
        
        // 检查账号是否已存在（排除当前更新的账号）
        $accountNumber = '';
        switch ($type) {
            case WithdrawAccount::TYPE_ALIPAY:
                $accountNumber = $data['alipayAccount'];
                break;
            case WithdrawAccount::TYPE_WECHAT:
                $accountNumber = $data['wechatAccount'];
                break;
            case WithdrawAccount::TYPE_BANK:
                $accountNumber = $data['bankCardNumber'];
                break;
        }
        
        $excludeId = isset($data['id']) ? $data['id'] : null;
        if (WithdrawAccount::checkAccountExists($this->auth->id, $type, $accountNumber, $excludeId)) {
            $this->error('该账号已存在，请勿重复添加');
        }
    }
}