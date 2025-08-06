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
	return request.post('/account/changePassword', data);
}

/**
 * 修改支付密码
 * @param {Object} data 修改支付密码数据
 * @param {string} data.oldPayPassword 旧支付密码
 * @param {string} data.newPayPassword 新支付密码
 */
export function changePayPassword(data) {
	return request.post('/info/updateProfile', data);
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
 * 获取用户统计数据
 * @param {Object} params 查询参数
 */
export function getUserStatistics(params) {
	return request.get('/user/statistics', params);
}

/**
 * 获取用户资金变动记录
 * @param {Object} params 查询参数
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.type 变动类型 (recharge/withdraw/bet/win/rebate/redpacket/other)
 * @param {string} params.start_date 开始日期
 * @param {string} params.end_date 结束日期
 */
export function getMoneyLog(params = {}) {
	return request.get('/user/moneyLog', params);
}

//发送电子邮箱验证码
export function sendEmailCode(email, type) {
	return request.post('/user/sendEmailCode', {
		email: email,
		type: type
	});
}

//找回密码
export function resetPassword(data) {
	return request.post('/user/resetPassword', data);
}

//找回支付密码
export function resetPayPassword(data) {
	return request.post('/user/resetPayPassword', data);
}