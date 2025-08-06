/**
 * 用户相关API接口
 */
import request from '@/utils/request.js';

/**
 * 用户登录
 * @param {Object} data 登录数据
 * @param {string} data.username 用户名/手机号
 * @param {string} data.password 密码
 */
export function login(data) {
	return request.post('/user/checkIn', {
		tab: 'login',
		...data
	});
}

/**
 * 用户注册
 * @param {Object} data 注册数据
 * @param {string} data.username 用户名
 * @param {string} data.mobile 手机号
 * @param {string} data.password 密码
 */
export function register(data) {
	return request.post('/user/checkIn', {
		tab: 'register',
		...data
	});
}

/**
 * 获取用户信息
 */
export function getUserInfo() {
	return request.get('/info/info');
}

/**
 * 退出登录
 */
export function logout() {
	return request.post('/user/logout');
}

/**
 * 修改密码
 * @param {Object} data 修改密码数据
 * @param {string} data.oldPassword 旧密码
 * @param {string} data.newPassword 新密码
 */
export function changePassword(data) {
	return request.post('/user/changePassword', data);
}

/**
 * 发送短信验证码
 * @param {Object} data 发送验证码数据
 * @param {string} data.mobile 手机号
 * @param {string} data.type 验证码类型 (register/login/reset)
 */
export function sendSmsCode(data) {
	return request.post('/user/sendSmsCode', data);
}

/**
 * 验证短信验证码
 * @param {Object} data 验证数据
 * @param {string} data.mobile 手机号
 * @param {string} data.code 验证码
 * @param {string} data.type 验证码类型
 */
export function verifySmsCode(data) {
	return request.post('/user/verifySmsCode', data);
}

/**
 * 获取代理商统计数据
 * 包括余额、会员数量、返佣等
 */
export function getAgentStats() {
	return request.get('/agent/stats');
}

/**
 * 获取下级会员列表
 * @param {Object} params 查询参数
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.keyword 搜索关键词
 */
export function getAgentMembers(params = {}) {
	return request.get('/agent/members', {
		params
	});
}

/**
 * 获取下级代理商列表
 * @param {Object} params 查询参数
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.keyword 搜索关键词
 */
export function getSubAgents(params = {}) {
	return request.get('/agent/subAgents', {
		params
	});
}

/**
 * 更新用户资料
 * @param {Object} data 用户资料数据
 * @param {string} data.nickname 昵称
 * @param {string} data.phone 手机号
 * @param {string} data.avatar 头像
 * @param {number} data.commission 返佣比例
 */
export function updateUserProfile(data) {
	return request.post('/info/updateProfile', data);
}

/**
 * 上传头像
 * @param {File} file 头像文件
 */
export function uploadAvatar(file) {
	return request.upload('/info/uploadAvatar', {
		file: file
	});
}

/**
 * 发送邮箱验证码
 * @param {Object} data 发送验证码数据
 * @param {string} data.email 邮箱地址
 * @param {string} data.type 验证码类型 (register/reset_password/reset_pay_password)
 */
export function sendEmailCode(data) {
	return request.post('/user/sendEmailCode', data);
}

/**
 * 重置支付密码
 * @param {Object} data 重置数据
 * @param {string} data.email 邮箱地址
 * @param {string} data.code 验证码
 * @param {string} data.pay_password 新支付密码
 */
export function resetPayPassword(data) {
	return request.post('/user/resetPayPassword', data);
}

/**
 * 获取提现账号列表
 * @param {string} type 账号类型 (alipay/wechat/bank)
 */
export function getWithdrawAccounts(type = '') {
	return request.get('/withdraw/getAccounts', {
		params: { type }
	});
}

/**
 * 添加提现账号
 * @param {Object} data 账号数据
 * @param {string} data.type 账号类型
 * @param {string} data.accountName 账号名称
 * @param {string} data.alipayAccount 支付宝账号
 * @param {string} data.wechatAccount 微信号
 * @param {string} data.bankCardNumber 银行卡号
 * @param {string} data.bankName 银行名称
 */
export function addWithdrawAccount(data) {
	return request.post('/withdraw/addAccount', data);
}

/**
 * 更新提现账号
 * @param {Object} data 账号数据
 * @param {number} data.id 账号ID
 */
export function updateWithdrawAccount(data) {
	return request.post('/withdraw/updateAccount', data);
}

/**
 * 删除提现账号
 * @param {number} id 账号ID
 */
export function deleteWithdrawAccount(id) {
	return request.post('/withdraw/deleteAccount', { id });
}

/**
 * 设置默认提现账号
 * @param {number} id 账号ID
 */
export function setDefaultWithdrawAccount(id) {
	return request.post('/withdraw/setDefaultAccount', { id });
}

/**
 * 提交提现申请
 * @param {Object} data 提现数据
 * @param {number} data.accountId 账号ID
 * @param {number} data.amount 提现金额
 * @param {string} data.payPassword 支付密码
 * @param {string} data.remark 备注
 */
export function submitWithdraw(data) {
	return request.post('/withdraw/submit', data);
}

/**
 * 获取提现记录
 * @param {Object} params 查询参数
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.status 状态筛选
 */
export function getWithdrawRecords(params = {}) {
	return request.get('/withdraw/getRecords', {
		params
	});
}

/**
 * 获取提现配置
 */
export function getWithdrawConfig() {
	return request.get('/withdraw/getConfig');
}