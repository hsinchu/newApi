import request from '@/utils/request.js';

/**
 * 提交投注
 * @param {Object} data 投注数据
 * @returns {Promise}
 */
export function submitBet(data) {
  return request.post('/bet/submit', data)
}

/**
 * 获取投注记录
 * @param {Object} params 查询参数
 * @returns {Promise}
 */
export function getBetOrders(params) {
  return request.get('/order/getOrders', params)
}