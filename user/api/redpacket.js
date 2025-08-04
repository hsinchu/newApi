import request from '@/utils/request.js';

//获取可领取的红包
export function getAvailableRedPackets() {
	return request.get('/user/availableRedPackets');
}

//领取红包
export function claimRedPacket(redPacketId) {
	return request.post('/user/claimRedPacket', {
		red_packet_id: redPacketId
	});
}

//获取我的红包记录
export function myRedPacketRecords(params = {}) {
	return request.get('/user/myRedPacketRecords', {
		params: {
			page: params.page || 1,
			limit: params.limit || 20
		}
	});
}

// 兼容旧的函数名
export function getMyRedPacketRecords(params = {}) {
	return myRedPacketRecords(params);
}
