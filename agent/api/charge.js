/**
 * 用户相关API接口
 */
import request from '@/utils/request.js';

//获取支付方式列表
export function getPayType() {
	return request.get('/charge/payType');
}

//获取提现数据列表
export function getWithdrawList() {
	return request.get('/charge/withdrawList');
}

//模拟提交支付成功
export function mockPaySuccess(data) {
	return request.post('/charge/mockPaySuccess', data);
}