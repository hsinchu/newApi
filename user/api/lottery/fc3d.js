import request from '@/utils/request.js'

/**
 * 获取福彩3D今天期号信息
 * @returns {Promise} API响应
 */
export function getFC3DTodayPeriod() {
	return request.get('/lottery/getFC3DTodayPeriod')
}

/**
 * 获取福彩3D指定日期期号信息
 * @param {string} date 日期字符串，格式：YYYY-MM-DD
 * @returns {Promise} API响应
 */
export function getFC3DPeriodByDate(date) {
	return request.get('/lottery/getFC3DPeriodByDate', {
		date: date
	})
}

/**
 * 获取福彩3D下一期期号信息
 * @returns {Promise} API响应
 */
export function getFC3DNextPeriod() {
	return request.get('/lottery/getFC3DNextPeriod')
}

/**
 * 获取福彩3D期号状态
 * @param {string} periodNo 期号
 * @returns {Promise} API响应
 */
export function getFC3DPeriodStatus(periodNo) {
	return request.get('/lottery/getFC3DPeriodStatus', {
		period_no: periodNo
	})
}

/**
 * 获取福彩3D开奖结果
 * @param {string} periodNo 期号
 * @returns {Promise} API响应
 */
export function getFC3DDrawResult(periodNo) {
	return request.get('/lottery/getFC3DDrawResult', {
		period_no: periodNo
	})
}

/**
 * 获取福彩3D历史开奖记录
 * @param {Object} params 查询参数
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.start_date 开始日期
 * @param {string} params.end_date 结束日期
 * @returns {Promise} API响应
 */
export function getFC3DHistoryDraw(params = {}) {
	return request.get('/lottery/getFC3DHistoryDraw', params)
} 