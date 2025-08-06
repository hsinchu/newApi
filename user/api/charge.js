/**
 * 用户相关API接口
 */
import request from '@/utils/request.js';

//获取支付方式列表
export function getPayType() {
	return request.get('/charge/payType');
}

//获取代理充值赠送活动列表
export function getRechargeGiftList() {
	return request.get('/charge/rechargeGiftList');
}

//获取提现账户列表
export function getWithdrawAccountList() {
	return request.get('/charge/withdrawAccountList');
}

//删除提现账户
export function deleteWithdrawAccount(id) {
	return request.post('/charge/deleteWithdrawAccount', { id: id });
}

//添加提现账户
export function addWithdrawAccount(data) {
	return request.post('/withdraw/addAccount', data);
}

//更新提现账户
export function updateWithdrawAccount(data) {
	return request.post('/withdraw/updateAccount', data);
}

//提交提现申请
export function submitWithdrawApply(data) {
	return request.post('/charge/submitWithdrawApply', data);
}

//获取我的提现记录
export function getWithdrawRecordList(data) {
	return request.post('/withdraw/recordList', data);
}

//模拟提交支付成功
export function mockPaySuccess(data) {
	return request.post('/charge/mockPaySuccess', data);
}