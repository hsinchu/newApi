import request from '@/utils/request.js'

/**
 * 获取当前期数信息
 * @param {string} lotteryCode 彩种代码
 */
export function getCurrentPeriod(lotteryCode = 'ff3d') {
	return request.get('/lottery/getCurrentPeriod', {
		type: lotteryCode
	})
}

//通过期号和彩种获取奖金池
export function getBonusPool(periodNo, lotteryCode) {
	return request.get('/lottery/getBonusPool', {
		periodNo: periodNo,
		type: lotteryCode
	})
}

/**
 * 获取历史开奖记录
 * @param {Object} params 查询参数
 * @param {string} params.lottery_code 彩种代码
 * @param {number} params.page 页码
 * @param {number} params.limit 每页数量
 * @param {string} params.period_no 期号（可选）
 */
export function getHistoryDraw(params) {
	return request.get('/lottery/getHistoryDraw', params)
}

/**
 * 获取彩种列表
 */
export function getLotteryTypes() {
	return request.get('/lottery/getLotteryTypes')
}

/**
 * 获取彩种详情
 * @param {string} lotteryCode 彩种代码
 */
export function getGameInfo(lotteryCode) {
	return request.get('/lottery/getGameInfo', {
		type: lotteryCode
	})
}

/**
 * 获取当前游戏玩法的赔率
 * @param {string} lotteryCode 彩种代码
 * @param {string} betType 投注类型
 */
export function getGameOdds(lotteryCode, betType) {
	return request.get('/lottery/getGameOdds', {
		type: lotteryCode,
		betType: betType
	})	
}

//获取各彩种的最新开奖记录(1条)
export function getLatestDraw(lotteryCode) {
	return request.get('/lottery/getLatestDraw', {
		type: lotteryCode
	})
}

//获取所有开放彩种的最新开奖记录
export function getAllLatestDraw() {
	return request.get('/lottery/getAllLatestDraw')
}

/**
 * 获取最大投注额
 * @param {Object} params 查询参数
 * @param {string} params.lottery_code 彩种代码
 * @param {string} params.period 期号
 * @param {string} params.play_type 玩法类型 (big/small/middle)
 * @param {number} params.odds 赔率
 */
export function getMaxBetAmount(params) {
	return request.get('/bet/getMaxBetAmount', params)
}
