<template>
    <el-dialog
        v-model="state.visible"
        :title="'代理返水记录 - ' + state.agentInfo.username"
        width="1200px"
        :close-on-click-modal="false"
        @close="onClose"
    >
        <div class="record-container">
            <!-- 搜索条件 -->
            <el-form :model="state.searchForm" :inline="true" class="search-form">
                <el-form-item label="彩种分类">
                    <el-select v-model="state.searchForm.lottery_category" clearable placeholder="请选择彩种分类">
                        <el-option label="竞彩" value="SPORTS" />
                        <el-option label="福彩" value="WELFARE" />
                        <el-option label="单场" value="SPORTS_SINGLE" />
                        <el-option label="快彩" value="QUICK" />
                    </el-select>
                </el-form-item>
                <el-form-item label="记录状态">
                    <el-select v-model="state.searchForm.record_status" clearable placeholder="请选择记录状态">
                        <el-option label="待结算" value="pending" />
                        <el-option label="已结算" value="settled" />
                        <el-option label="已取消" value="cancelled" />
                    </el-select>
                </el-form-item>
                <el-form-item label="结算日期">
                    <el-date-picker
                        v-model="state.searchForm.settlement_date"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始日期"
                        end-placeholder="结束日期"
                        format="YYYY-MM-DD"
                        value-format="YYYY-MM-DD"
                    />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="loadRecords">查询</el-button>
                    <el-button @click="resetSearch">重置</el-button>
                </el-form-item>
            </el-form>
            
            <!-- 统计信息 -->
            <el-row :gutter="20" class="stats-row">
                <el-col :span="6">
                    <el-card class="stats-card">
                        <div class="stats-item">
                            <div class="stats-label">总记录数</div>
                            <div class="stats-value">{{ state.stats.totalRecords }}</div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :span="6">
                    <el-card class="stats-card">
                        <div class="stats-item">
                            <div class="stats-label">总投注金额</div>
                            <div class="stats-value">¥{{ formatMoney(state.stats.totalBetAmount) }}</div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :span="6">
                    <el-card class="stats-card">
                        <div class="stats-item">
                            <div class="stats-label">总中奖金额</div>
                            <div class="stats-value">¥{{ formatMoney(state.stats.totalWinAmount) }}</div>
                        </div>
                    </el-card>
                </el-col>
                <el-col :span="6">
                    <el-card class="stats-card">
                        <div class="stats-item">
                            <div class="stats-label">净返水金额</div>
                            <div class="stats-value text-success">¥{{ formatMoney(state.stats.totalRebateAmount) }}</div>
                        </div>
                    </el-card>
                </el-col>
            </el-row>
            
            <!-- 记录表格 -->
            <el-table
                :data="state.records"
                v-loading="state.loading"
                stripe
                border
                class="records-table"
            >
                <el-table-column prop="category_text" label="彩种分类" width="100" align="center" />
                <el-table-column prop="bet_amount" label="投注金额" width="120" align="right">
                    <template #default="{ row }">
                        ¥{{ formatMoney(row.bet_amount) }}
                    </template>
                </el-table-column>
                <el-table-column prop="win_amount" label="中奖金额" width="120" align="right">
                    <template #default="{ row }">
                        ¥{{ formatMoney(row.win_amount) }}
                    </template>
                </el-table-column>
                <el-table-column prop="no_win_amount" label="未中奖金额" width="120" align="right">
                    <template #default="{ row }">
                        ¥{{ formatMoney(row.no_win_amount) }}
                    </template>
                </el-table-column>
                <el-table-column prop="profit_loss" label="盈亏金额" width="120" align="right">
                    <template #default="{ row }">
                        <span :class="row.profit_loss >= 0 ? 'text-success' : 'text-danger'">
                            ¥{{ formatMoney(row.profit_loss) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column prop="rebate_amount" label="总返水金额" width="120" align="right">
                    <template #default="{ row }">
                        <span class="text-success">¥{{ formatMoney(row.rebate_amount) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="commission_amount" label="佣金金额" width="120" align="right">
                    <template #default="{ row }">
                        <span class="text-primary">¥{{ formatMoney(row.commission_amount) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="净返水金额" width="120" align="right">
                    <template #default="{ row }">
                        <span class="text-warning">¥{{ formatMoney(getNetRebate(row)) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="no_win_rebate_amount" label="未中奖返佣金额" width="140" align="right">
                    <template #default="{ row }">
                        <span class="text-info">¥{{ formatMoney(row.no_win_rebate_amount) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="no_win_rate" label="未中奖返佣比例" width="130" align="center">
                    <template #default="{ row }">
                        {{ row.no_win_rate }}%
                    </template>
                </el-table-column>
                <el-table-column prop="bet_rebate_amount" label="投注返佣金额" width="130" align="right">
                    <template #default="{ row }">
                        <span class="text-warning">¥{{ formatMoney(row.bet_rebate_amount) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="bet_rate" label="投注返佣比例" width="120" align="center">
                    <template #default="{ row }">
                        {{ row.bet_rate }}%
                    </template>
                </el-table-column>
                <el-table-column prop="rebate_type_text" label="返水方式" width="100" align="center" />
                <el-table-column prop="settlement_date" label="结算日期" width="120" align="center" />
                <el-table-column prop="record_status_text" label="记录状态" width="100" align="center">
                    <template #default="{ row }">
                        <el-tag :type="getStatusTagType(row.record_status)"
                            size="small"
                        >
                            {{ row.record_status_text }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="create_time" label="创建时间" width="160" align="center">
                    <template #default="{ row }">
                        {{ formatDateTime(row.create_time) }}
                    </template>
                </el-table-column>
            </el-table>
            
            <!-- 分页 -->
            <el-pagination
                v-model:current-page="state.pagination.page"
                v-model:page-size="state.pagination.limit"
                :total="state.pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next, jumper"
                @size-change="loadRecords"
                @current-change="loadRecords"
                class="pagination"
            />
        </div>
        
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="onClose">关闭</el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive } from 'vue'
import { ElMessage } from 'element-plus'
import createAxios from '/@/utils/axios'
import { timeFormat } from '/@/utils/common'

// 格式化金额
const formatMoney = (amount: any) => {
    if (!amount) return '0.00'
    const num = parseFloat(amount.toString())
    return num.toFixed(2)
}

// 格式化日期时间
const formatDateTime = (dateTime: any) => {
    if (!dateTime) return ''
    return timeFormat(dateTime, 'yyyy-mm-dd hh:MM:ss')
}

interface AgentInfo {
    id: number
    username: string
}

interface RebateRecord {
    id: number
    category: string
    category_text: string
    bet_amount: number
    win_amount: number
    no_win_amount: number
    profit_loss: number
    rebate_amount: number
    commission_amount: number
    no_win_rebate_amount: number
    no_win_rate: number
    bet_rebate_amount: number
    bet_rate: number
    rebate_type: string
    rebate_type_text: string
    settlement_date: string
    record_status: string
    record_status_text: string
    create_time: number
}

const state = reactive({
    visible: false,
    loading: false,
    agentInfo: {} as AgentInfo,
    records: [] as RebateRecord[],
    searchForm: {
        lottery_category: '',
        record_status: '',
        settlement_date: [] as string[],
    },
    pagination: {
        page: 1,
        limit: 20,
        total: 0,
    },
    stats: {
        totalRecords: 0,
        totalBetAmount: 0,
        totalWinAmount: 0,
        totalRebateAmount: 0,
    }
})

const open = (agentInfo: AgentInfo) => {
    state.agentInfo = agentInfo
    state.visible = true
    resetSearch()
    loadRecords()
}

const loadRecords = async () => {
    state.loading = true
    try {
        const params = {
            agent_id: state.agentInfo.id,
            page: state.pagination.page,
            limit: state.pagination.limit,
            ...state.searchForm,
        }
        
        const res = await createAxios({
            url: '/admin/user.AgentRebate/getRecords',
            method: 'GET',
            params: params
        })
        if (res.code === 1) {
            state.records = res.data.list || []
            state.pagination.total = res.data.total || 0
            
            // 计算统计信息
            calculateStats()
        } else {
            ElMessage.error(res.msg || '加载记录失败')
        }
    } catch (error) {
        console.error('加载记录失败:', error)
        ElMessage.error('加载记录失败')
    } finally {
        state.loading = false
    }
}

// 计算净返水金额（总返水 - 佣金）
const getNetRebate = (record: RebateRecord) => {
    return (record.rebate_amount || 0) - (record.commission_amount || 0)
}

const calculateStats = () => {
    state.stats.totalRecords = state.records.length
    state.stats.totalBetAmount = state.records.reduce((sum, record) => sum + record.bet_amount, 0)
    state.stats.totalWinAmount = state.records.reduce((sum, record) => sum + record.win_amount, 0)
    state.stats.totalRebateAmount = state.records.reduce((sum, record) => sum + getNetRebate(record), 0)
}

const resetSearch = () => {
    state.searchForm = {
        lottery_category: '',
        record_status: '',
        settlement_date: [],
    }
    state.pagination.page = 1
}

const getStatusTagType = (status: string) => {
    const typeMap: Record<string, string> = {
        pending: 'warning',
        settled: 'success',
        cancelled: 'danger',
    }
    return typeMap[status] || 'info'
}

const onClose = () => {
    state.visible = false
}

defineExpose({
    open
})
</script>

<style scoped>
.record-container {
    padding: 0;
}

.search-form {
    margin-bottom: 20px;
    padding: 20px;
    border-radius: 6px;
}

.stats-row {
    margin-bottom: 20px;
}

.stats-card {
    text-align: center;
}

.stats-item {
    padding: 10px;
}

.stats-label {
    font-size: 14px;
    margin-bottom: 8px;
}

.stats-value {
    font-size: 20px;
    font-weight: bold;
}

.text-success {
    color: #67c23a;
}

.text-danger {
    color: #f56c6c;
}

.records-table {
    margin-bottom: 20px;
}

.pagination {
    text-align: right;
}

.dialog-footer {
    text-align: right;
}
</style>