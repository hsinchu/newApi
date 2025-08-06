/**
 * 代理商相关API接口
 */
import request from '@/utils/request.js';

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
 * @param {number} params.is_favorite 是否收藏 0=全部 1=仅收藏
 */
export function getMembers(params = {}) {
	return request.get('/agent/members', params);
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
 * 收藏/取消收藏会员
 * @param {Object} data 收藏数据
 * @param {number} data.member_id 会员ID
 * @param {number} data.is_favorite 是否收藏 1=收藏 0=取消收藏
 */
export function toggleMemberFavorite(data) {
	return request.post('/agent/toggleMemberFavorite', data);
}

/**
 * 获取会员详细信息
 * @param {Object} params 查询参数
 * @param {number} params.member_id 会员ID
 */
export function getMemberDetail(member_id) {
	return request.get('/agent/memberDetail', {
		id: member_id
	});
}

// 设置会员返佣比例
export function setMemberRebate(data) {
	return request.post('/agent/setMemberRebate', data);
}

/**
 * 获取会员投注记录
 * @param {Object} params 查询参数
 * @param {number} params.member_id 会员ID
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.start_date 开始日期
 * @param {string} params.end_date 结束日期
 */
export function getMemberBetRecords(params = {}) {
	return request.get('/agent/memberBetRecords', {
		params
	});
}

/**
 * 获取会员资金变动记录
 * @param {Object} params 查询参数
 * @param {number} params.member_id 会员ID
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.type 变动类型
 */
export function getMemberMoneyLogs(params = {}) {
	return request.get('/agent/memberMoneyLogs', {
		params
	});
}

/**
 * 设置会员返佣比例
 * @param {Object} data 设置数据
 * @param {number} data.member_id 会员ID
 * @param {number} data.rebate_rate 返佣比例
 */
export function setMemberRebateRate(data) {
	return request.post('/agent/setMemberRebateRate', data);
}

/**
 * 批量操作会员
 * @param {Object} data 操作数据
 * @param {Array} data.member_ids 会员ID数组
 * @param {string} data.action 操作类型 favorite/unfavorite/enable/disable
 */
export function batchOperateMembers(data) {
	return request.post('/agent/batchOperateMembers', data);
}

/**
 * 获取代理商统计数据
 * @param {Object} params 查询参数
 * @param {string} params.start_date 开始日期 YYYY-MM-DD
 * @param {string} params.end_date 结束日期 YYYY-MM-DD
 */
export function getStatistics(params = {}) {
	return request.get('/agent/getStatistics', {
		params
	});
}

/**
 * 获取会员订单列表
 * @param {Object} params 查询参数
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.status 订单状态
 * @param {string} params.keyword 搜索关键词
 * @param {number} params.member_id 指定会员ID
 */
export function getMemberOrders(params = {}) {
	return request.get('/agent/memberOrders', 
		params
	);
}

export function setUserMoney(params = {}){
	return request.post('/agent/setUserMoney', 
		params
	);
}

export function getMoneyLog(params = {}){
	return request.get('/user/moneyLog', params);
}

export function getUserMoneyLog(params = {}){
	return request.get('/agent/getUserMoneyLog', params);
}

//代配置充值赠送
export function getAgentRecharge(params = {}){
	return request.get('/agent/getAgentRecharge', params);
}

//充值赠送新增和修改保存
export function saveAgentRecharge(data){
	return request.post('/agent/saveAgentRecharge', data);
}

//删除充值赠送配置
export function deleteAgentRecharge(id){
	return request.post('/agent/deleteAgentRecharge', {id});
}

//切换充值赠送配置状态
export function toggleAgentRechargeStatus(id){
	return request.post('/agent/toggleAgentRechargeStatus', {id});
}

//支付密码验证
export function verifyPayPassword(password){
	return request.post('/agent/verifyPayPassword', {password: password});
}
