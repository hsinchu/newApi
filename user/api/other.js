/**
 * 用户相关API接口
 */
import request from '@/utils/request.js';

//获取支付方式列表
export function getDanoList() {
	return request.get('/other/danoList');
}