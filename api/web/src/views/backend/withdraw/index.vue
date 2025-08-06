<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索：订单号/用户名'"
    />

    <!-- 表格 -->
    <Table ref="tableRef" />

    <!-- 提现详情弹窗 -->
    <el-dialog v-model="detailDialog.visible" title="提现详情" width="800px" :close-on-click-modal="false">
      <div v-loading="detailDialog.loading" class="withdraw-detail">
        <!-- 基本信息 -->
        <div class="detail-section">
          <el-descriptions :column="2" border size="default">
            <el-descriptions-item label="订单号">
              <span class="order-no">{{ detailDialog.data.order_no }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="用户">
              <el-tag type="info">{{ detailDialog.data.user?.username }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="账户类型">
              <el-tag type="primary">{{ detailDialog.data.account_type_name }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="状态">
              <el-tag :type="getStatusType(detailDialog.data.status)">
                {{ getStatusText(detailDialog.data.status) }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="申请金额">
              <span class="amount-text">¥{{ detailDialog.data.amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="手续费">
              <span class="fee-text">¥{{ detailDialog.data.fee }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="实际到账">
              <span class="actual-amount">¥{{ detailDialog.data.actual_amount }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="申请时间">
              <span class="time-text">{{ detailDialog.data.create_time }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="处理时间">
              <span class="time-text">{{ detailDialog.data.process_time || '未处理' }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="处理人">
              <span>{{ detailDialog.data.process_admin || '无' }}</span>
            </el-descriptions-item>
          </el-descriptions>
        </div>
        
        <!-- 账户信息 -->
        <div class="detail-section" v-if="detailDialog.data.account_info">
          <h4>账户信息</h4>
          <el-descriptions :column="2" border size="default">
            <el-descriptions-item label="账户名">
              <span>{{ detailDialog.data.account_info.account_name }}</span>
            </el-descriptions-item>
            <el-descriptions-item label="账户号码">
              <span>{{ detailDialog.data.account_info.account_number }}</span>
            </el-descriptions-item>
            <el-descriptions-item v-if="detailDialog.data.account_info.bank_name" label="银行名称">
              <span>{{ detailDialog.data.account_info.bank_name }}</span>
            </el-descriptions-item>
          </el-descriptions>
        </div>
        
        <!-- 备注信息 -->
        <div class="detail-section" v-if="detailDialog.data.admin_remark">
          <h4>处理备注</h4>
          <div class="remark-content">
            {{ detailDialog.data.admin_remark }}
          </div>
        </div>
      </div>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="detailDialog.visible = false">关闭</el-button>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, provide } from 'vue'
import { baTableApi } from '/@/api/common'
import baTableClass from '/@/utils/baTable'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { ElMessage, ElMessageBox } from 'element-plus'

// 定义类型接口，避免any类型
interface TableRow {
  id: number
  order_no: string
  status: 'pending' | 'approved' | 'completed' | 'rejected' | 'cancelled' | 'processing'
  [key: string]: any
}

interface TableColumn {
  [key: string]: any
}

interface OptButton {
  render: string
  name: string
  title: string
  text?: string
  type: string
  icon: string
  class?: string
  disabledTip: string | boolean
  click: (row: TableRow, field: TableColumn) => void
  display: (column: TableColumn, row: TableRow) => boolean
}

defineOptions({
  name: 'backend/withdraw/index',
})

// 详情弹窗
const detailDialog = reactive({
  visible: false,
  loading: false,
  data: {} as any
})

// 获取状态类型
const getStatusType = (status: string) => {
  const types: Record<string, string> = {
    pending: 'warning',
    approved: 'info',
    completed: 'success',
    rejected: 'danger',
    cancelled: 'info'
  }
  return types[status] || 'info'
}

// 获取状态文本
const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: '待审核',
    approved: '已通过',
    completed: '已完成',
    rejected: '已拒绝',
    cancelled: '已取消'
  }
  return texts[status] || '未知'
}

// 通过
const handleApprove = async (row: TableRow) => {
  try {
    await ElMessageBox.confirm('确认通过该提现申请？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await baTable.api.postData('approve', { id: row.id })
    ElMessage.success('操作成功')
    baTable.onTableHeaderAction('refresh', {})
  } catch (error: any) {
    console.log(error)
    if (error !== 'cancel') {
      // 优先显示后端返回的具体错误消息
      const errorMsg = error.msg || error.message || '操作失败'
      ElMessage.error(errorMsg)
    }
  }
}

// 拒绝
const handleReject = async (row: TableRow) => {
  try {
    const { value } = await ElMessageBox.prompt('请输入拒绝原因', '拒绝提现', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      inputPattern: /.+/,
      inputErrorMessage: '拒绝原因不能为空'
    })
    
    await baTable.api.postData('reject', { id: row.id, remark: value })
    ElMessage.success('操作成功')
    baTable.onTableHeaderAction('refresh', {})
  } catch (error: any) {
    if (error !== 'cancel') {
      // 优先显示后端返回的具体错误消息
      const errorMsg = error.msg || error.message || '操作失败'
      ElMessage.error(errorMsg)
    }
  }
}

// 完成
const handleComplete = async (row: TableRow) => {
  try {
    await ElMessageBox.confirm('确认完成该提现申请？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await baTable.api.postData('complete', { id: row.id })
    ElMessage.success('操作成功')
    baTable.onTableHeaderAction('refresh', {})
  } catch (error: any) {
    if (error !== 'cancel') {
      // 优先显示后端返回的具体错误消息
      const errorMsg = error.msg || error.message || '操作失败'
      ElMessage.error(errorMsg)
    }
  }
}

// 查看详情
const showDetail = async (row: TableRow) => {
  detailDialog.visible = true
  detailDialog.loading = true
  try {
    const res = await baTable.api.postData('detail', { id: row.id })
    detailDialog.data = res.data
  } catch (error: any) {
    // 优先显示后端返回的具体错误消息
    const errorMsg = error.msg || error.message || '获取详情失败'
    ElMessage.error(errorMsg)
  } finally {
    detailDialog.loading = false
  }
}

const tableRef = ref()
// 操作按钮配置
const optButtons = [
  {
    render: 'tipButton',
    class: 'el-button el-button--success el-button--small',
    text: '通过',
    tip: '通过提现申请',
    disabledTip: '当前状态不可通过',
    click: handleApprove,
    // display: (column: any, row: any) => row.status === 'pending'
  },
  {
    render: 'tipButton',
    class: 'el-button el-button--danger el-button--small',
    text: '拒绝',
    tip: '拒绝提现申请',
    disabledTip: '当前状态不可拒绝',
    click: handleReject,
    // display: (column: any, row: any) => row.status === 'pending'
  },
  {
    render: 'tipButton',
    class: 'el-button el-button--primary el-button--small',
    text: '完成',
    tip: '完成提现申请',
    disabledTip: '当前状态不可完成',
    click: handleComplete,
    // display: (column: any, row: any) => ['approved', 'processing'].includes(row.status)
  },
  {
    render: 'tipButton',
    class: 'el-button el-button--info el-button--small',
    text: '详情',
    tip: '查看详情',
    click: showDetail,
    display: () => true
  }
]
const baTable = new baTableClass(
  new baTableApi('/admin/finance.Withdraw/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false, width: 50 },
      { label: '订单号', prop: 'order_no', align: 'center', width: 200, operator: 'LIKE' },
      { label: '用户名', prop: 'user.username', align: 'center', width: 100, operator: 'LIKE' },
      { label: '账户类型', prop: 'account_type_name', align: 'center', width: 90, operator: false },
      {
        label: '姓名',
        prop: 'account_info.account_name',
        align: 'center',
        width: 150,
        operator: false,
        slotName: 'account_name'
      },
      {
        label: '申请金额',
        prop: 'amount',
        align: 'center',
        width: 100,
        operator: 'eq',
        slotName: 'amount'
      },
      {
        label: '手续费',
        prop: 'fee',
        align: 'center',
        width: 80,
        operator: false,
        slotName: 'fee'
      },
      {
        label: '实际到账',
        prop: 'actual_amount',
        align: 'center',
        width: 100,
        operator: false,
        slotName: 'actual_amount'
      },
      {
        label: '状态',
        prop: 'status',
        align: 'center',
        width: 80,
        operator: 'eq',
        operatorPlaceholder: '请选择状态',
        render: 'tag',
        custom: {
            pending: 'warning',
            approved: 'success',
            completed: 'success',
            rejected: 'danger',
            cancelled: 'info'
          },
        replaceValue: {
            pending: '待审核',
            approved: '已通过',
            completed: '已完成',
            rejected: '已拒绝',
            cancelled: '已取消'
          },
        comSearchRender: 'select',
        remote: {
          pk: 'value',
          field: 'label',
          data: [
            { value: 'pending', label: '待审核' },
            { value: 'approved', label: '已通过' },
            { value: 'completed', label: '已完成' },
            { value: 'rejected', label: '已拒绝' },
            { value: 'cancelled', label: '已取消' }
          ]
        }
      },
      { label: '申请时间', prop: 'create_time', align: 'center', width: 155, operator: 'RANGE', render: 'datetime' },
      { label: '处理时间', prop: 'process_time', align: 'center', width: 155, operator: false, render: 'datetime' },
      { label: '处理人', prop: 'process_admin', align: 'center', width: 80, operator: false },
      {
        label: '操作',
        align: 'center',
        width: 205, 
        render: 'buttons',
        fixed: 'right',
        buttons: optButtons,
        operator: false
      }
    ],
    dblClickNotEditColumn: [undefined]
  },
  {
    defaultItems: {
      status: 'pending'
    }
  }
)

provide('baTable', baTable)

onMounted(() => {
  baTable.table.ref = tableRef.value
  baTable.mount()
  baTable.getIndex()
})
</script>

<style scoped>
.withdraw-detail .detail-section {
  margin-bottom: 20px;
}

.withdraw-detail .detail-section h4 {
  margin-bottom: 10px;
  color: #303133;
  font-weight: 600;
}

.withdraw-detail .remark-content {
  padding: 12px;
  background-color: #f5f7fa;
  border-radius: 4px;
  color: #606266;
  line-height: 1.6;
}

.withdraw-detail .order-no {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #409eff;
}

.withdraw-detail .amount-text {
  color: #f56c6c;
  font-weight: 600;
}

.withdraw-detail .fee-text {
  color: #e6a23c;
  font-weight: 600;
}

.withdraw-detail .actual-amount {
  color: #67c23a;
  font-weight: 600;
}

.withdraw-detail .time-text {
  color: #909399;
}

/* 表格行按钮样式 - 修复显示问题 */
.table-row-btn {
  margin-right: 6px;
  padding: 4px 8px;
  font-size: 12px;
}

/* 最后一个按钮去除右边距 */
.table-row-btn:last-child {
  margin-right: 0;
}

/* 调整操作列样式 */
:deep(.el-table__column--fixed-right) {
  padding: 0 5px;
}

:deep(.el-table .cell) {
  white-space: nowrap;
}
</style>
