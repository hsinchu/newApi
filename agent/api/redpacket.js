import request from '@/utils/request.js';

// 红包状态枚举
export const RED_PACKET_STATUS = {
	ACTIVE: 'ACTIVE',
	FINISHED: 'FINISHED',
	CANCELLED: 'CANCELLED',
	EXPIRED: 'EXPIRED'
};

// 红包类型枚举
export const RED_PACKET_TYPE = {
	RANDOM: 'RANDOM',
	FIXED: 'FIXED'
};

// 领取条件类型枚举
export const CONDITION_TYPE = {
	NONE: 'NONE',
	MIN_BET: 'MIN_BET'
};

// 目标用户类型枚举
export const TARGET_TYPE = {
	ALL: 0,
	AGENT: 1,
	USER: 2
};

// 默认分页参数
const DEFAULT_PAGE_PARAMS = {
	page: 1,
	limit: 10
};

/**
 * 获取红包列表
 * @param {Object} params 查询参数
 * @param {number} [params.page=1] 页码
 * @param {number} [params.limit=10] 每页数量
 * @param {string} [params.status] 状态筛选
 * @param {string} [params.keyword] 关键词搜索
 * @returns {Promise} API响应
 */
export function getRedPackets(params = {}) {
	const queryParams = {
		...DEFAULT_PAGE_PARAMS,
		...params
	};
	
	// 参数验证
	if (queryParams.page < 1) queryParams.page = 1;
	if (queryParams.limit < 1 || queryParams.limit > 100) queryParams.limit = 10;
	
	return request.get('/redPacket/index', queryParams);
}

// 保持向后兼容
export const getRedPacketList = getRedPackets;

/**
 * 获取红包详情
 * @param {Object} params 查询参数
 * @param {number} params.id 红包ID
 * @returns {Promise} API响应
 */
export function getRedPacketDetail(params) {
	if (!params?.id) {
		return Promise.reject(new Error('红包ID不能为空'));
	}
	return request.get('/redPacket/detail', params);
}

/**
 * 创建红包
 * @param {Object} data 红包数据
 * @param {string} data.title 红包标题
 * @param {string} data.blessing 祝福语
 * @param {string} data.type 红包类型 RANDOM|FIXED
 * @param {number} data.total_amount 总金额(元)
 * @param {number} data.total_count 红包个数
 * @param {number} [data.target_type=2] 发送对象 0=全部,1=代理商,2=用户
 * @param {string} [data.condition_type='NONE'] 领取条件类型
 * @param {string} [data.condition_value=''] 领取条件值
 * @param {number} data.expire_time 过期时间戳
 * @returns {Promise} API响应
 */
export function createRedPacket(data) {
	// 数据验证
	if (!data?.title?.trim()) {
		return Promise.reject(new Error('红包标题不能为空'));
	}
	if (!data?.blessing?.trim()) {
		return Promise.reject(new Error('祝福语不能为空'));
	}
	if (!data?.total_amount || data.total_amount <= 0) {
		return Promise.reject(new Error('总金额必须大于0'));
	}
	if (!data?.total_count || data.total_count <= 0) {
		return Promise.reject(new Error('红包个数必须大于0'));
	}
	if (!data?.expire_time) {
		return Promise.reject(new Error('过期时间不能为空'));
	}
	
	// 设置默认值
	const requestData = {
		target_type: TARGET_TYPE.USER,
		condition_type: CONDITION_TYPE.NONE,
		condition_value: '',
		...data,
		title: data.title.trim(),
		blessing: data.blessing.trim()
	};
	
	return request.post('/redPacket/create', requestData);
}

/**
 * 取消红包
 * @param {Object} data 参数
 * @param {number} data.id 红包ID
 * @returns {Promise} API响应
 */
export function cancelRedPacket(data) {
	if (!data?.id) {
		return Promise.reject(new Error('红包ID不能为空'));
	}
	return request.post('/redPacket/cancel', data);
}

/**
 * 获取红包领取记录
 * @param {Object} params 查询参数
 * @param {number} params.red_packet_id 红包ID
 * @param {number} [params.page=1] 页码
 * @param {number} [params.limit=10] 每页数量
 * @returns {Promise} API响应
 */
export function getRedPacketRecords(params = {}) {
	if (!params?.red_packet_id) {
		return Promise.reject(new Error('红包ID不能为空'));
	}
	
	const queryParams = {
		...DEFAULT_PAGE_PARAMS,
		...params
	};
	
	return request.get('/redPacket/records', queryParams);
}

/**
 * 获取红包统计数据
 * @returns {Promise} API响应
 */
export function getRedPacketStats() {
	return request.get('/redPacket/stats');
}

/**
 * 领取红包
 * @param {Object} data 参数
 * @param {number} data.id 红包ID
 * @returns {Promise} API响应
 */
export function receiveRedPacket(data) {
	if (!data?.id) {
		return Promise.reject(new Error('红包ID不能为空'));
	}
	return request.post('/redPacket/receive', data);
}

/**
 * 获取我的红包记录
 * @param {Object} params 查询参数
 * @param {number} [params.page=1] 页码
 * @param {number} [params.limit=10] 每页数量
 * @returns {Promise} API响应
 */
export function getMyRedPackets(params = {}) {
	const queryParams = {
		...DEFAULT_PAGE_PARAMS,
		...params
	};
	
	return request.get('/redPacket/my', queryParams);
}

/**
 * 批量取消红包
 * @param {Array<number>} ids 红包ID数组
 * @returns {Promise} API响应
 */
export function batchCancelRedPackets(ids) {
	if (!Array.isArray(ids) || ids.length === 0) {
		return Promise.reject(new Error('红包ID数组不能为空'));
	}
	return request.post('/redPacket/batchCancel', { ids });
}

/**
 * 获取红包状态文本映射
 * @returns {Object} 状态文本映射
 */
export function getStatusTextMap() {
	return {
		[RED_PACKET_STATUS.ACTIVE]: '进行中',
		[RED_PACKET_STATUS.FINISHED]: '已完成',
		[RED_PACKET_STATUS.CANCELLED]: '已取消',
		[RED_PACKET_STATUS.EXPIRED]: '已过期'
	};
}

/**
 * 获取红包类型文本映射
 * @returns {Object} 类型文本映射
 */
export function getTypeTextMap() {
	return {
		[RED_PACKET_TYPE.RANDOM]: '随机红包',
		[RED_PACKET_TYPE.FIXED]: '固定红包'
	};
}