import request from '@/utils/request.js'

/**
 * 获取彩种列表
 */
export function getLotteryTypes() {
	return request.get('/lottery/getLotteryTypes')
}