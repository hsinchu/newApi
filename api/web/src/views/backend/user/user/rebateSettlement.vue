<template>
    <el-dialog
        v-model="state.visible"
        :title="'发放返点 - ' + state.agentInfo.username"
        width="1000px"
        :close-on-click-modal="false"
        @close="onClose"
    >
        <div class="settlement-container">
            <!-- 代理信息 -->
            <el-card class="agent-info-card" shadow="never">
                <template #header>
                    <div class="card-header">
                        <span>代理信息</span>
                    </div>
                </template>
                <el-row :gutter="20">
                    <el-col :span="6">
                        <div class="info-item">
                            <span class="label">代理账号：</span>
                            <span class="value">{{ state.agentInfo.username }}</span>
                        </div>
                    </el-col>
                    <el-col :span="6">
                        <div class="info-item">
                            <span class="label">当前余额：</span>
                            <span class="value text-primary">¥{{ formatMoney(state.agentInfo.money || 0) }}</span>
                        </div>
                    </el-col>
                    <el-col :span="6">
                        <div class="info-item">
                            <span class="label">返水方式：</span>
                            <span class="value">{{ state.rebateConfig.rebate_type_text || '未配置' }}</span>
                        </div>
                    </el-col>
                    <el-col :span="6">
                        <div class="info-item">
                            <span class="label">返水周期：</span>
                            <span class="value">{{ state.rebateConfig.settlement_cycle_text || '未配置' }}</span>
                        </div>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="6">
                        <div class="info-item">
                            <span class="label">上次发放：</span>
                            <span class="value">{{ state.lastSettlementTime || '从未发放' }}</span>
                        </div>
                    </el-col>
                </el-row>
            </el-card>
            
            <!-- 未结算返点信息 -->
            <el-card class="pending-rebate-card" shadow="never">
                <template #header>
                    <div class="card-header">
                        <span>未结算返点信息</span>
                        <el-button type="primary" size="small" @click="loadPendingRebate">刷新数据</el-button>
                    </div>
                </template>
                
                <div v-loading="state.loading">
                    <el-row :gutter="20" class="summary-row">
                        <el-col :span="4">
                            <div class="summary-item">
                                <div class="summary-label">总投注金额</div>
                                <div class="summary-value">¥{{ formatMoney(state.pendingRebate.totalBetAmount) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="4">
                            <div class="summary-item">
                                <div class="summary-label">总中奖金额</div>
                                <div class="summary-value">¥{{ formatMoney(state.pendingRebate.totalWinAmount) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="4">
                            <div class="summary-item">
                                <div class="summary-label">总盈亏金额</div>
                                <div class="summary-value" :class="state.pendingRebate.totalProfitLoss >= 0 ? 'text-success' : 'text-danger'">
                                    ¥{{ formatMoney(state.pendingRebate.totalProfitLoss) }}
                                </div>
                            </div>
                        </el-col>
                        <el-col :span="4">
                            <div class="summary-item">
                                <div class="summary-label">未中奖返水</div>
                                <div class="summary-value text-warning">¥{{ formatMoney(state.pendingRebate.totalNoWinRebate) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="4">
                            <div class="summary-item">
                                <div class="summary-label">投注返水</div>
                                <div class="summary-value text-info">¥{{ formatMoney(state.pendingRebate.totalBetRebate) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="4">
                            <div class="summary-item">
                                <div class="summary-label">总返水金额</div>
                                <div class="summary-value text-success">¥{{ formatMoney(state.pendingRebate.totalRebateAmount) }}</div>
                            </div>
                        </el-col>
                    </el-row>
                    <el-row :gutter="20" class="summary-row">
                        <el-col :span="6">
                            <div class="summary-item">
                                <div class="summary-label">已发放佣金</div>
                                <div class="summary-value text-warning">¥{{ formatMoney(state.pendingRebate.totalCommissionAmount) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="6">
                            <div class="summary-item">
                                <div class="summary-label">净返水金额</div>
                                <div class="summary-value text-primary">¥{{ formatMoney(getNetRebateAmount()) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="6">
                            <div class="summary-item">
                                <div class="summary-label">可结算金额</div>
                                <div class="summary-value text-primary font-bold">¥{{ formatMoney(state.pendingRebate.settlableAmount) }}</div>
                            </div>
                        </el-col>
                        <el-col :span="6">
                            <div class="summary-item">
                                <div class="summary-label">结算说明</div>
                                <div class="summary-desc">净返水 - 已发佣金</div>
                            </div>
                        </el-col>
                    </el-row>
                    
                    <!-- 分类详情 -->
                    <el-table
                        :data="state.pendingRebate.categoryDetails"
                        stripe
                        border
                        class="category-table"
                    >
                        <el-table-column prop="category_text" label="彩种分类" width="120" align="center" />
                        <el-table-column prop="bet_amount" label="投注金额" width="140" align="right">
                            <template #default="{ row }">
                                ¥{{ formatMoney(row.bet_amount) }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="win_amount" label="中奖金额" width="140" align="right">
                            <template #default="{ row }">
                                ¥{{ formatMoney(row.win_amount) }}
                            </template>
                        </el-table-column>
                        <!-- <el-table-column prop="no_win_amount" label="未中奖金额" width="140" align="right">
                            <template #default="{ row }">
                                ¥{{ formatMoney(row.no_win_amount) }}
                            </template>
                        </el-table-column> -->
                        <el-table-column prop="profit_loss" label="盈亏金额" width="140" align="right">
                            <template #default="{ row }">
                                <span :class="row.profit_loss >= 0 ? 'text-success' : 'text-danger'">
                                    ¥{{ formatMoney(row.profit_loss) }}
                                </span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="no_win_rate" label="未中奖返水比例" width="150" align="center">
                            <template #default="{ row }">
                                {{ row.no_win_rate }}%
                            </template>
                        </el-table-column>
                        <el-table-column prop="no_win_rebate_amount" label="未中奖返水金额" width="140" align="right">
                            <template #default="{ row }">
                                <span class="text-warning">¥{{ formatMoney(row.no_win_rebate_amount) }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="bet_rate" label="投注返水比例" width="120" align="center">
                            <template #default="{ row }">
                                {{ row.bet_rate }}%
                            </template>
                        </el-table-column>
                        <el-table-column prop="bet_rebate_amount" label="投注返水金额" width="140" align="right">
                            <template #default="{ row }">
                                <span class="text-info">¥{{ formatMoney(row.bet_rebate_amount) }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="rebate_amount" label="当前返水金额" width="140" align="right">
                            <template #default="{ row }">
                                <span class="text-success">¥{{ formatMoney(row.rebate_amount) }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="record_count" label="记录数" width="80" align="center" />
                    </el-table>
                </div>
            </el-card>
            
            <!-- 发放说明 -->
            <el-alert
                v-if="state.pendingRebate.settlableAmount > 0"
                title="发放说明"
                type="info"
                :closable="false"
                class="distribute-alert"
            >
                <template #default>
                    <p>• 发放后将直接增加代理账户余额</p>
                    <p>• 发放记录将被标记为已结算状态</p>
                    <p>• 发放操作不可撤销，请确认无误后操作</p>
                    <p>• 可结算金额 = 净返水金额 - 已发放佣金</p>
                </template>
            </el-alert>
        </div>
        
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="onClose">取消</el-button>
                <el-button
                    type="primary"
                    :disabled="state.pendingRebate.settlableAmount <= 0 || state.distributing"
                    :loading="state.distributing"
                    @click="distributeRebate"
                >
                    {{ state.distributing ? '发放中...' : '确认发放' }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import createAxios from '/@/utils/axios'

// 格式化金额
const formatMoney = (amount: any) => {
    if (!amount) return '0.00'
    const num = parseFloat(amount.toString())
    return num.toFixed(2)
}

interface AgentInfo {
    id: number
    username: string
    money?: number
}

interface RebateConfig {
    rebate_type_text?: string
    settlement_cycle_text?: string
}

interface CategoryDetail {
    category: string
    category_text: string
    bet_amount: number
    win_amount: number
    no_win_amount: number
    profit_loss: number
    rebate_rate: number
    rebate_amount: number
    no_win_rate: number
    bet_rate: number
    no_win_rebate_amount: number
    bet_rebate_amount: number
    record_count: number
}

interface PendingRebate {
    totalBetAmount: number
    totalWinAmount: number
    totalProfitLoss: number
    totalRebateAmount: number
    totalNoWinRebate: number
    totalBetRebate: number
    totalCommissionAmount: number
    settlableAmount: number
    categoryDetails: CategoryDetail[]
}

const state = reactive({
    visible: false,
    loading: false,
    distributing: false,
    agentInfo: {} as AgentInfo,
    rebateConfig: {} as RebateConfig,
    lastSettlementTime: '',
    pendingRebate: {
        totalBetAmount: 0,
        totalWinAmount: 0,
        totalProfitLoss: 0,
        totalRebateAmount: 0,
        totalNoWinRebate: 0,
        totalBetRebate: 0,
        totalCommissionAmount: 0,
        settlableAmount: 0,
        categoryDetails: [],
    } as PendingRebate
})

const open = (agentInfo: AgentInfo) => {
    state.agentInfo = agentInfo
    state.visible = true
    loadPendingRebate()
}

const loadPendingRebate = async () => {
    state.loading = true
    try {
        const res = await createAxios({
            url: '/admin/user.AgentRebate/getPendingRebate',
            method: 'GET',
            params: {
                agent_id: state.agentInfo.id
            }
        })
        
        if (res.code === 1) {
            // 映射新的数据结构
            state.pendingRebate = {
                totalBetAmount: res.data.totalBetAmount || 0,
                totalWinAmount: res.data.totalWinAmount || 0,
                totalProfitLoss: (res.data.totalBetAmount || 0) - (res.data.totalWinAmount || 0),
                totalRebateAmount: res.data.totalRebate || 0,
                totalNoWinRebate: res.data.totalNoWinRebate || 0,
                totalBetRebate: res.data.totalBetRebate || 0,
                totalCommissionAmount: res.data.totalCommissionAmount || 0,
                settlableAmount: res.data.settlableAmount || 0,
                categoryDetails: (res.data.categoryStats || []).filter(item => item.category !== null).map(item => ({
                    category: item.category,
                    category_text: item.category_text,
                    bet_amount: parseFloat(item.bet_amount) / 100, // 转换为元
                    win_amount: parseFloat(item.win_amount) / 100, // 转换为元
                    no_win_amount: item.no_win_amount / 100, // 转换为元
                    profit_loss: item.profit_loss / 100, // 转换为元
                    rebate_amount: item.rebate_amount / 100, // 转换为元
                    no_win_rate: item.no_win_rate,
                    bet_rate: item.bet_rate,
                    no_win_rebate_amount: item.no_win_rebate_amount / 100, // 转换为元
                    bet_rebate_amount: item.bet_rebate_amount / 100, // 转换为元
                    record_count: item.record_count
                }))
            }
            state.rebateConfig = res.data.config || {}
            state.lastSettlementTime = res.data.lastSettlementTime || '从未发放'
            
            // 更新代理余额信息
            if (res.data.agentInfo) {
                state.agentInfo.money = res.data.agentInfo.money
            }
        } else {
            ElMessage.error(res.msg || '加载未结算返点信息失败')
        }
    } catch (error) {
        console.error('加载未结算返点信息失败:', error)
        ElMessage.error('加载未结算返点信息失败')
    } finally {
        state.loading = false
    }
}

// 计算净返水金额（总返水 - 已发放佣金）
const getNetRebateAmount = () => {
    return (state.pendingRebate.totalRebateAmount || 0) - (state.pendingRebate.totalCommissionAmount || 0)
}

const distributeRebate = async () => {
    if (state.pendingRebate.settlableAmount <= 0) {
        ElMessage.warning('没有可发放的返点')
        return
    }
    
    try {
        await ElMessageBox.confirm(
            `确认发放返点 ¥${formatMoney(state.pendingRebate.settlableAmount)} 给代理 ${state.agentInfo.username}？`,
            '确认发放',
            {
                confirmButtonText: '确认发放',
                cancelButtonText: '取消',
                type: 'warning',
            }
        )
        
        state.distributing = true
        
        const res = await createAxios({
            url: '/admin/user.AgentRebate/distributeRebate',
            method: 'POST',
            data: {
                agent_id: state.agentInfo.id
            }
        }, {
            showSuccessMessage: true
        })
        
        if (res.code === 1) {
            ElMessage.success('返点发放成功')
            // 重新加载数据
            await loadPendingRebate()
        } else {
            ElMessage.error(res.msg || '返点发放失败')
        }
    } catch (error) {
        if (error !== 'cancel') {
            console.error('返点发放失败:', error)
            ElMessage.error('返点发放失败')
        }
    } finally {
        state.distributing = false
    }
}

const onClose = () => {
    state.visible = false
}

defineExpose({
    open
})
</script>

<style scoped>
.settlement-container {
    padding: 0;
}

.agent-info-card,
.pending-rebate-card {
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: bold;
}

.info-item {
    padding: 8px 0;
}

.label {
    margin-right: 8px;
}

.value {
    font-weight: 500;
}

.text-primary {
    color: #409eff;
}

.text-success {
    color: #67c23a;
}

.text-danger {
    color: #f56c6c;
}

.summary-row {
    margin-bottom: 20px;
    padding: 20px;
    border-radius: 6px;
}

.summary-item {
    text-align: center;
    padding: 10px;
}

.summary-label {
    font-size: 14px;
    margin-bottom: 8px;
}

.summary-value {
    font-size: 18px;
    font-weight: bold;
}

.summary-desc {
    font-size: 12px;
    color: #909399;
    text-align: center;
}

.font-bold {
    font-weight: 700;
    font-size: 20px;
}

.category-table {
    margin-bottom: 20px;
}

.distribute-alert {
    margin-bottom: 20px;
}

.distribute-alert :deep(.el-alert__content) {
    line-height: 1.6;
}

.distribute-alert p {
    margin: 4px 0;
}

.dialog-footer {
    text-align: right;
}
</style>