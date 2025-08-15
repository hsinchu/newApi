import { baTableApi } from '/@/api/common'

const controllerUrl = '/admin/finance.RechargeGift/'

export const baTableApiType = new baTableApi(controllerUrl)

/**
 * 切换充值赠送配置状态
 * @param id 配置ID
 * @param status 状态值
 */
export function toggleStatus(id: number, status: number) {
    return baTableApiType.postData('toggleStatus', { id, status })
}

/**
 * 获取代理商列表（用于下拉选择）
 */
export function getAgentList() {
    return baTableApiType.postData('getAgentList', {})
}