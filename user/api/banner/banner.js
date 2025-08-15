import request from '@/utils/request.js';

/**
 * 获取轮播图列表
 * @param {Object} params 请求参数
 * @param {Number} params.limit 限制数量
 */

export function getBannerList(params = {}) {
  return request.get('/banner/index', params)
}