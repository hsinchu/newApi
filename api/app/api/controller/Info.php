<?php

namespace app\api\controller;

use Throwable;
use app\common\model\User;
use app\common\controller\Frontend;
use app\service\UserService;
use Exception;

class Info extends Frontend
{
    protected array $noNeedLogin = [];

    public function initialize(): void
    {
        parent::initialize();
    }


    /**
     * 获取用户基本信息
     */
    
    public function info(): void
    {
        try {
            $userInfo = $this->auth->getUserInfo();
            
            // 获取用户详细信息
            $agentData = [
                'id' => $userInfo['id'],
                'username' => $userInfo['username'],
                'nickname' => $userInfo['nickname'],
                'avatar' => $userInfo['avatar'],
                'email' => $userInfo['email'],
                'mobile' => $userInfo['mobile'],
                'is_agent' => $userInfo['is_agent'],
                'is_verified' => $userInfo['is_verified'],
                'money' => $userInfo['money'], 
                'frozen_money' => $userInfo['frozen_money'],
                'gift_money' => $userInfo['gift_money'],
                'score' => $userInfo['score'],
                'invite_code' => $userInfo['invite_code'],
                'last_login_time' => $userInfo['last_login_time'],
                'join_time' => $userInfo['join_time'],
                'nowin_rate' => $userInfo['nowin_rate'],
                'rebate_rate' => $userInfo['rebate_rate'],
                'default_rebate_rate' => $userInfo['default_rebate_rate'],
                'status' => $userInfo['status']
            ];

            if($userInfo['is_agent'] == 0 && $userInfo['parent_id'] > 0){
                $userBrokRate = UserService::getUserBrokRate($userInfo['id']);
                $agentData['nowin_rate'] = $userBrokRate['nowin_rate'];
                $agentData['rebate_rate'] = $userBrokRate['rebate_rate'];
            }

            if($userInfo['is_agent'] == 1){
                $agentData['default_nowin_rate'] = $userInfo['default_nowin_rate'];
                $agentData['default_rebate_rate'] = $userInfo['default_rebate_rate'];
            }

        } catch (Exception $e) {
            $this->error('获取用户信息失败：' . $e->getMessage());
        }
            
        $this->success('获取用户信息成功', $agentData);
    }

    /**
     * 更新用户资料
     */
    public function updateProfile()
    {
        try {
            $data = $this->request->post();
            $userId = $this->auth->id;
            
            // 验证数据
			$allowedFields = ['nickname', 'mobile', 'default_rebate_rate', 'default_nowin_rate', 'nowin_rate', 'rebate_rate', 'realName', 'idCard'];
			$updateData = [];
			
			// 处理支付密码修改
			if (isset($data['oldPayPassword']) && isset($data['newPayPassword'])) {
				$user = User::find($userId);
				if (!$user) {
					$this->error('用户不存在');
				}
				
				// 验证原支付密码
				if (!verify_password($data['oldPayPassword'], $user->pay_password, $user->salt)) {
					$this->error('原支付密码错误');
				}
				
				// 验证新支付密码格式
				if (!preg_match('/^\d{6}$/', $data['newPayPassword'])) {
					$this->error('支付密码必须是6位数字');
				}
				
				// 加密新支付密码
				$updateData['pay_password'] = hash_password($data['newPayPassword'], $user->salt);
			}
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                $this->error('没有需要更新的数据');
            }
            
            // 验证昵称是否已存在
            if (isset($updateData['nickname'])) {
                $existingUser = User::where('nickname', $updateData['nickname'])
                    ->where('id', '<>', $userId)
                    ->find();
                if ($existingUser) {
                    $this->error('该昵称已被使用，请选择其他昵称');
                }
            }
            
            // 验证手机号格式
            if (isset($updateData['mobile'])) {
                if (!preg_match('/^1[3-9]\d{9}$/', $updateData['mobile'])) {
                    $this->error('手机号格式不正确');
                }
            }
            
            // 验证返佣比例
            if (isset($updateData['default_rebate_rate'])) {
                $default_rebate_rate = floatval($updateData['default_rebate_rate']);
                if ($default_rebate_rate < 0) {
                    $this->error('返佣比例不能小于0');
                }
                // 验证不能超过当前用户的返佣比例
                $currentUser = User::find($userId);
                if ($default_rebate_rate > $currentUser->rebate_rate) {
                    $this->error('默认返佣比例不能超过您的返佣比例' . $currentUser->rebate_rate . '%');
                }
                $updateData['default_rebate_rate'] = $default_rebate_rate;
            }
            
            // 验证未中奖返佣比例
            if (isset($updateData['default_nowin_rate'])) {
                $default_nowin_rate = floatval($updateData['default_nowin_rate']);
                if ($default_nowin_rate < 0 || $default_nowin_rate > 50) {
                    $this->error('未中奖返佣比例必须在0-50之间');
                }
                // 验证不能超过当前用户的未中奖返佣比例
                if (!isset($currentUser)) {
                    $currentUser = User::find($userId);
                }
                if ($default_nowin_rate > $currentUser->nowin_rate) {
                    $this->error('默认未中奖返佣比例不能超过您的未中奖返佣比例' . $currentUser->nowin_rate . '%');
                }
                $updateData['default_nowin_rate'] = $default_nowin_rate;
            }
            
            // 验证实名认证信息
            if (isset($updateData['realName']) || isset($updateData['idCard'])) {
                if (empty($updateData['realName']) || empty($updateData['idCard'])) {
                    $this->error('真实姓名和身份证号码不能为空');
                }
                
                // 验证身份证号码格式
                if (!preg_match('/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/', $updateData['idCard'])) {
                    $this->error('身份证号码格式不正确');
                }
                
                // 检查身份证号码是否已被使用
                $existingIdCard = User::where('id_card', $updateData['idCard'])
                    ->where('id', '<>', $userId)
                    ->find();
                if ($existingIdCard) {
                    $this->error('该身份证号码已被使用');
                }
                
                // 设置实名认证状态为审核中
                $updateData['real_name_certified'] = 2; // 2=审核中
                $updateData['real_name'] = $updateData['realName'];
                $updateData['id_card'] = $updateData['idCard'];
                
                // 移除临时字段
                unset($updateData['realName'], $updateData['idCard']);
            }
            
            // 更新用户信息
            $user = User::find($userId);
            if (!$user) {
                $this->error('用户不存在');
            }
            
            $user->save($updateData);
            
        } catch (Exception $e) {
            $this->error('更新失败：' . $e->getMessage());
        }
            
        $this->success('更新成功', $updateData);
    }
    
    /**
     * 上传头像
     */
    public function uploadAvatar()
    {
        try {
            $file = $this->request->file('file');
            if (!$file) {
                $this->error('请选择要上传的文件');
            }
            
            // 验证文件类型
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMime(), $allowedTypes)) {
                $this->error('只支持上传图片文件');
            }
            
            // 验证文件大小 (2MB)
            if ($file->getSize() > 2 * 1024 * 1024) {
                $this->error('文件大小不能超过2MB');
            }
            
            // 生成文件名
            $extension = $file->extension();
            $filename = 'avatar_' . $this->auth->id . '_' . time() . '.' . $extension;
            
            // 保存文件
            $savePath = 'uploads/avatars/' . date('Y/m/');
            $file->move(public_path() . $savePath, $filename);
            
            // 生成访问URL
            $avatarUrl = '/' . $savePath . $filename;
            
            // 更新用户头像
            $user = User::find($this->auth->id);
            if ($user) {
                $user->avatar = $avatarUrl;
                $user->save();
            }
            
            $this->success('头像上传成功', ['avatar' => $avatarUrl]);
            
        } catch (Throwable $e) {
            $this->error('上传失败：' . $e->getMessage());
        }
    }
}