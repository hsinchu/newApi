/**
 * 用户相关API接口
 */
import request from '@/utils/request.js';

//获取支付方式列表
export function getDanoList() {
	return request.get('/other/danoList');
}

//获取公共信息
export function getPublicData() {
	return request.get('/other/getPublicData');
}
