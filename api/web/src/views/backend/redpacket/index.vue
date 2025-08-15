<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="'请输入红包标题、祝福语进行搜索'"
        >
            <template #refreshAppend>
                <el-button @click="checkExpired" type="warning" plain>
                    检查过期红包
                </el-button>
            </template>
        </TableHeader>

        <!-- 表格 -->
        <Table ref="tableRef" />

        <!-- 表单 -->
        <PopupForm ref="formRef" />

        <!-- 统计弹窗 -->
        <el-dialog v-model="statsDialog.visible" title="红包统计" width="800px">
            <div v-if="statsDialog.loading" class="text-center">
                <el-icon class="is-loading"><Loading /></el-icon>
                <span class="ml-2">加载中...</span>
            </div>
            <div v-else-if="statsDialog.data">
                <el-descriptions :column="2" border class="mb-4">
                    <el-descriptions-item label="红包总金额">{{ (statsDialog.data.total_amount / 100).toFixed(2) }}元</el-descriptions-item>
                    <el-descriptions-item label="红包总个数">{{ statsDialog.data.total_count }}个</el-descriptions-item>
                    <el-descriptions-item label="已领取金额">{{ (statsDialog.data.received_amount / 100).toFixed(2) }}元</el-descriptions-item>
                    <el-descriptions-item label="已领取个数">{{ statsDialog.data.received_count }}个</el-descriptions-item>
                    <el-descriptions-item label="剩余金额">{{ ((statsDialog.data.total_amount - statsDialog.data.received_amount) / 100).toFixed(2) }}元</el-descriptions-item>
                    <el-descriptions-item label="剩余个数">{{ statsDialog.data.remaining_count }}个</el-descriptions-item>
                    <el-descriptions-item label="领取条件" :span="2">
                        <el-tag type="info">{{ getConditionText(statsDialog.data.condition_type, statsDialog.data.condition_value) }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="红包状态" :span="2">
                        <el-tag :type="getStatusType(statsDialog.data.status)">{{ getStatusText(statsDialog.data.status) }}</el-tag>
                    </el-descriptions-item>
                </el-descriptions>
                
                <!-- 领取记录 -->
                <div class="records-section">
                    <h4 class="records-title">领取记录</h4>
                    <div v-if="statsDialog.recordsLoading" class="text-center">
                        <el-icon class="is-loading"><Loading /></el-icon>
                        <span class="ml-2">加载记录中...</span>
                    </div>
                    <el-table v-else :data="statsDialog.records" stripe style="width: 100%" max-height="300">
                        <el-table-column prop="user.username" label="用户" width="120" />
                        <el-table-column prop="amount" label="领取金额" width="100">
                            <template #default="scope">
                                {{ (scope.row.amount / 100).toFixed(2) }}元
                            </template>
                        </el-table-column>
                        <el-table-column prop="receive_time" label="领取时间" width="160">
                            <template #default="scope">
                                {{ new Date(scope.row.receive_time).toLocaleString() }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="ip" label="IP地址" width="120" />
                        <el-table-column prop="user_agent" label="设备信息" show-overflow-tooltip />
                    </el-table>
                </div>
            </div>
            <template #footer>
                <el-button @click="statsDialog.visible = false">关闭</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted } from 'vue'
import baTableClass from '/@/utils/baTable'
import { baTableApi } from '/@/api/common'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import createAxios from '/@/utils/axios'
import { ElMessage, ElMessageBox } from 'element-plus'

defineOptions({
    name: 'redpacket',
})
const tableRef = ref()
const formRef = ref()

// 统计弹窗
const statsDialog = ref({
    visible: false,
    loading: false,
    data: null as any,
    records: [] as any[],
    recordsLoading: false
})

const optButtons: OptButton[] = []

// 添加自定义操作按钮
optButtons.push(
    {
        render: 'tipButton',
        name: 'stats',
        title: '统计',
        text: '',
        type: 'primary',
        icon: 'fa fa-bar-chart',
        class: 'table-row-stats',
        disabledTip: false,
        click: (row: TableRow, field: TableColumn) => {
            showStats(row.id)
        },
    },
    {
        render: 'tipButton',
        name: 'cancel',
        title: '取消红包',
        text: '',
        type: 'danger',
        icon: 'fa fa-ban',
        class: 'table-row-cancel',
        disabledTip: false,
        display: (row: TableRow, field: TableColumn) => {
            return row.status === 'ACTIVE'
        },
        click: (row: TableRow, field: TableColumn) => {
            cancelRedPacket(row.id)
        },
    },

)

const baTable = new baTableClass(
    new baTableApi('/admin/redpacket.RedPacket/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { 
                label: '发放对象', 
                prop: 'target_type', 
                align: 'center', 
                width: 120,
                operator: 'eq',
                render: 'tag',
                custom: { 0: 'info', 1: 'success', 2: 'danger' },
                replaceValue: { 0: '全部', 1: '代理商', 2: '用户' },
            },
            { label: '代理ID', prop: 'agent_id', align: 'center', width: 88, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '红包标题', prop: 'title', align: 'center', width: 150, operator: 'LIKE', operatorPlaceholder: '模糊查询' },
            { label: '祝福语', prop: 'blessing', align: 'center', width: 200, operator: 'LIKE', operatorPlaceholder: '模糊查询', showOverflowTooltip: true },
            { 
                label: '红包类型', 
                prop: 'type', 
                align: 'center', 
                width: 120,
                operator: 'eq',
                render: 'tag',
                custom: { 'RANDOM': 'success', 'FIXED': 'danger' },
                replaceValue: {
                    'RANDOM': '随机红包',
                    'FIXED': '固定红包'
                }
            },
            { 
                label: '总金额', 
                prop: 'total_amount', 
                align: 'center', 
                width: 100, 
                operator: 'RANGE', 
                sortable: 'custom', 
                renderFormatter: (row: TableRow, field: TableColumn, value: any) => {
                    return (value / 100).toFixed(2)
                }
            },
            { label: '总个数', prop: 'total_count', align: 'center', width: 90, operator: 'RANGE', sortable: 'custom', suffix: '个' },
            { label: '已领取', prop: 'received_count', align: 'center', width: 90, operator: 'RANGE', sortable: 'custom', suffix: '个' },
            { label: '剩余个数', prop: 'remaining_count', align: 'center', width: 90, operator: 'RANGE', sortable: 'custom', suffix: '个' },
            { 
                label: '已领金额', 
                prop: 'received_amount', 
                align: 'center', 
                width: 100, 
                operator: 'RANGE', 
                sortable: 'custom', 
            },
            { 
                label: '状态', 
                prop: 'status', 
                align: 'center', 
                width: 100,
                operator: 'eq',
                render: 'tag',
                custom: { 'ACTIVE': 'success', 'FINISHED': 'info' , 'CANCELLED': 'danger' , 'EXPIRED': 'danger' },
                replaceValue: {
                    'ACTIVE': '进行中',
                    'FINISHED': '已完成',
                    'CANCELLED': '已取消',
                    'EXPIRED': '已过期'
                }
            },
            { label: '过期时间', prop: 'expire_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 175 },
            { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom' },
            {
                label: '操作',
                align: 'center',
                width: 95,
                render: 'buttons',
                fixed: 'right',
                buttons: optButtons,
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined],
        defaultOrder: { prop: 'id', order: 'desc' },
    },
    {
        defaultItems: {},
    }
)

provide('baTable', baTable)

// 显示统计信息
const showStats = async (id: number) => {
    statsDialog.value.visible = true
    statsDialog.value.loading = true
    statsDialog.value.recordsLoading = true
    statsDialog.value.data = null
    statsDialog.value.records = []
    
    try {
        // 获取统计信息
        const statsRes = await createAxios({
            url: '/admin/redpacket.RedPacket/stats',
            method: 'GET',
            params: { id }
        })
        
        if (statsRes.code === 1) {
            statsDialog.value.data = statsRes.data
        } else {
            ElMessage.error(statsRes.msg || '获取统计信息失败')
        }
        
        // 获取领取记录
        const recordsRes = await createAxios({
            url: '/admin/redpacket.RedPacketRecord/index',
            method: 'GET',
            params: { 
                red_packet_id: id,
                limit: 100
            }
        })
        
        if (recordsRes.code === 1) {
            statsDialog.value.records = recordsRes.data.list || []
        }
        
    } catch (error) {
        ElMessage.error('获取信息失败')
    } finally {
        statsDialog.value.loading = false
        statsDialog.value.recordsLoading = false
    }
}

// 取消红包
const cancelRedPacket = async (id: number) => {
    try {
        await ElMessageBox.confirm('确定要取消这个红包吗？取消后用户将无法继续领取。', '确认取消', {
            type: 'warning'
        })
        
        const res = await createAxios({
            url: '/admin/redpacket.RedPacket/cancel',
            method: 'POST',
            data: { id }
        })
        
        if (res.code === 1) {
            ElMessage.success('红包已取消')
            baTable.onTableHeaderAction('refresh', {})
        } else {
            ElMessage.error(res.msg || '取消失败')
        }
    } catch (error) {
        // 用户取消操作
    }
}

// 检查过期红包
const checkExpired = async () => {
    try {
        const res = await createAxios({
            url: '/admin/redpacket.RedPacket/checkExpired',
            method: 'POST'
        })
        
        if (res.code === 1) {
            ElMessage.success(`已处理 ${res.data.count || 0} 个过期红包`)
            baTable.onTableHeaderAction('refresh', {})
        } else {
            ElMessage.error(res.msg || '检查失败')
        }
    } catch (error) {
        ElMessage.error('检查失败')
    }
}

// 获取状态类型
const getStatusType = (status: string) => {
    const types: Record<string, string> = {
        'ACTIVE': 'success',
        'FINISHED': 'info',
        'CANCELLED': 'warning',
        'EXPIRED': 'danger'
    }
    return types[status] || 'info'
}

// 获取状态文本
const getStatusText = (status: string) => {
    const texts: Record<string, string> = {
        'ACTIVE': '进行中',
        'FINISHED': '已完成',
        'CANCELLED': '已取消',
        'EXPIRED': '已过期'
    }
    return texts[status] || status
}

// 获取条件文本
const getConditionText = (conditionType: string, conditionValue: string) => {
    const conditionTexts: Record<string, string> = {
        'NONE': '无条件',
        'MIN_BET': `今日最低投注${conditionValue}元`,
        'USER_LEVEL': `用户等级${conditionValue}级以上`
    }
    return conditionTexts[conditionType] || '未知条件'
}

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss">
.ml-2 {
    margin-left: 8px;
}

.text-center {
    text-align: center;
}

.mb-4 {
    margin-bottom: 16px;
}

.records-section {
    margin-top: 20px;
    
    .records-title {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 600;
        color: #303133;
    }
}

.text-gray-500 {
    color: #909399;
}

.py-4 {
    padding: 16px 0;
}
</style>