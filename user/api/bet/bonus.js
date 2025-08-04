import request from '@/utils/request.js';

/**
 * 获取奖金配置
 * @param {Object} params 查询参数
 * @param {string} params.type 玩法类型 - 对应数据库fa_lottery_bonus表的type字段
 * @param {Array|Object} [params.data] 选号数据 - 根据不同玩法类型传入相应格式的数据
 * @returns {Promise<Object>} API响应 - 包含money(奖金), note(注数), bonusmax(最高奖金), bonusmin(最低奖金)
 */
export function getBonus(params) {
	if (!params?.type) {
		return Promise.reject(new Error('玩法类型不能为空'));
	}
	
	// 使用POST请求，与customer端保持一致
	return request.post('/bonus/getBonus', params);
}

/**
 * 获取最小奖金列表
 * @returns {Promise} API响应
 */
export function getBonusminList() {
	// 使用POST请求，与customer端保持一致
	return request.post('/bonus/getBonusminList', {});
}



/**
 * 计算和值奖金
 * @param {Array} data 选号数据 - 选中的和值数组(0-27)
 * @returns {Promise<Object>} API响应
 */
export function calculateTotalBonus(data) {
	return getBonus({
		type: 'total',
		data: data
	});
}



/**
 * 计算大小奖金
 * @param {Array} data 选号数据 - 大小选择数组
 * @returns {Promise<Object>} API响应
 */
export function calculateSizeBonus(data) {
	return getBonus({
		type: 'size',
		data: data
	});
}

/**
 * 计算直选定位奖金
 * @param {Object} data 选号数据 - {0: 百位数组, 1: 十位数组, 2: 个位数组}
 * @returns {Promise<Object>} API响应
 */
export function calculateDirectBonus(data) {
	return getBonus({
		type: 'direct',
		data: data
	});
}

/**
 * 计算直选复式奖金
 * @param {Array} data 选号数据 - 选中的号码数组
 * @returns {Promise<Object>} API响应
 */
export function calculateMultipleBonus(data) {
	return getBonus({
		type: 'multiple',
		data: data
	});
}

/**
 * 计算直选跨度奖金
 * @param {Array} data 选号数据 - 选中的跨度数组
 * @returns {Promise<Object>} API响应
 */
export function calculateLeapBonus(data) {
	return getBonus({
		type: 'span',
		data: data
	});
}

/**
 * 计算组三单式奖金
 * @param {Array|Object} data 选号数据 - 组三单式选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculateThreeBonus(data) {
	return getBonus({
		type: 'three_single',
		data: data
	});
}

/**
 * 计算组三复式奖金
 * @param {Array|Object} data 选号数据 - 组三复式选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculateThreeMultipleBonus(data) {
	return getBonus({
		type: 'three_multiple',
		data: data
	});
}

/**
 * 计算组六奖金
 * @param {Array} data 选号数据 - 组六选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculateSixBonus(data) {
	return getBonus({
		type: 'six',
		data: data
	});
}

/**
 * 计算一码定位奖金
 * @param {Object} data 选号数据 - {0: 一码数组, 1: [], 2: []}
 * @returns {Promise<Object>} API响应
 */
export function calculateLocation1Bonus(data) {
	return getBonus({
		type: 'location1',
		data: data
	});
}

/**
 * 计算两码定位奖金
 * @param {Object} data 选号数据 - {0: 两码数组, 1: 两码数组, 2: []}
 * @returns {Promise<Object>} API响应
 */
export function calculateLocation2Bonus(data) {
	return getBonus({
		type: 'location2',
		data: data
	});
}

/**
 * 计算不定位奖金
 * @param {Array} data 选号数据 - 不定位选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculateDelocalizationBonus(data) {
	return getBonus({
		type: 'delocalization',
		data: data
	});
}

/**
 * 计算单选全胆奖金
 * @param {Array} data 选号数据 - 单选全胆选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculatePairBonus(data) {
	return getBonus({
		type: 'single_all',
		data: data
	});
}

/**
 * 计算单双奖金
 * @param {Array} data 选号数据 - 单双选择数据
 * @returns {Promise<Object>} API响应
 */
export function calculateFirstsdBonus(data) {
	return getBonus({
		type: 'firstsd',
		data: data
	});
}

/**
 * 计算对子奖金
 * @param {Array} data 选号数据 - 对子选择数据
 * @returns {Promise<Object>} API响应
 */
export function calculateDuiziBonus(data) {
	return getBonus({
		type: 'pair',
		data: data
	});
}

/**
 * 计算组三拖胆奖金
 * @param {Object} data 选号数据 - {danma: 胆码数组, tuoma: 拖码数组}
 * @returns {Promise<Object>} API响应
 */
export function calculateThreeDragBonus(data) {
	return getBonus({
		type: 'three_towing',
		data: data
	});
}

/**
 * 计算组六拖胆奖金
 * @param {Array} data 选号数据 - 组六拖胆选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculateSixDragBonus(data) {
	return getBonus({
		type: 'six_drag',
		data: data
	});
}

/**
 * 计算组六跨度奖金
 * @param {Array} data 选号数据 - 组六跨度选号数据
 * @returns {Promise<Object>} API响应
 */
export function calculateSixSpanBonus(data) {
	return getBonus({
		type: 'six_span',
		data: data
	});
}

// 玩法类型枚举
export const PLAY_TYPE = {
	TOTAL: 'total',
	SIZE: 'size',
	DIRECT: 'direct',
	MULTIPLE: 'multiple',
	SPAN: 'span',
	THREE_SINGLE: 'three_single',
	THREE_MULTIPLE: 'three_multiple',
	THREE_DRAG: 'three_drag',
	SIX: 'six',
	SIX_DRAG: 'six_drag',
	SIX_SPAN: 'six_span',
	LOCATION1: 'location1',
	LOCATION2: 'location2',
	DELOCALIZATION: 'delocalization',
	FIRSTSD: 'firstsd',
	PAIR: 'pair',
	SINGLE_ALL: 'single_all'
};

export default {
	getBonus,
	getBonusminList,
	calculateTotalBonus,
	calculateSizeBonus,
	calculateDirectBonus,
	calculateMultipleBonus,
	calculateLeapBonus,
	calculateThreeBonus,
	calculateThreeMultipleBonus,
	calculateThreeDragBonus,
	calculateSixBonus,
	calculateSixDragBonus,
	calculateSixSpanBonus,
	calculateLocation1Bonus,
	calculateLocation2Bonus,
	calculateDelocalizationBonus,
	calculateFirstsdBonus,
	calculateDuiziBonus,
	calculatePairBonus,
	PLAY_TYPE
};