<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 统计卡片 -->
    <el-row :gutter="15" class="stats-cards" v-if="stats">
      <el-col :span="3">
        <el-card class="stats-card">
          <div class="stats-content">
            <div class="stats-value">{{ stats.total_count }}</div>
            <div class="stats-label">总订单数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card success">
          <div class="stats-content">
            <div class="stats-value">{{ stats.success_count }}</div>
            <div class="stats-label">成功订单</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card warning">
          <div class="stats-content">
            <div class="stats-value">¥{{ stats.success_amount }}</div>
            <div class="stats-label">成功金额</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card danger">
          <div class="stats-content">
            <div class="stats-value">{{ stats.failed_count }}</div>
            <div class="stats-label">失败订单</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card info">
          <div class="stats-content">
            <div class="stats-value">{{ stats.pending_count }}</div>
            <div class="stats-label">待支付</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card processing">
          <div class="stats-content">
            <div class="stats-value">{{ stats.processing_count }}</div>
            <div class="stats-label">处理中</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card cancelled">
          <div class="stats-content">
            <div class="stats-value">{{ stats.cancelled_count }}</div>
            <div class="stats-label">已取消</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="3">
        <el-card class="stats-card timeout">
          <div class="stats-content">
            <div class="stats-value">{{ stats.timeout_count }}</div>
            <div class="stats-label">已超时</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索：订单号、第三方订单号、用户名'"
    >
      <template #refreshAfter>
        <el-button type="success" @click="exportOrders">
          <Icon name="fa fa-download" />
          导出订单
        </el-button>
      </template>
    </TableHeader>

    <!-- 表格 -->
    <Table ref="tableRef" />

    <!-- 表单 -->
    <PopupForm />
  </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted, reactive } from 'vue'
import baTableClass from '/@/utils/baTable'
import { baTableApi } from '/@/api/common'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import Icon from '/@/components/icon/index.vue'
import { ElMessage, ElMessageBox } from 'element-plus'

defineOptions({
  name: 'finance/rechargeOrder',
})
const tableRef = ref()
const stats = ref(null)

const optButtons: OptButton[] = [
  {
    render: 'tipButton',
    name: 'handleSuccess',
    title: '成功',
    text: '',
    type: 'success',
    icon: 'fa fa-check',
    class: 'table-row-success',
    disabledTip: false,
    display: (row: TableRow) => row.status === 'PENDING' || row.status === 'PROCESSING',
    click: (row: TableRow, field: TableColumn) => {
      handleSuccess(row)
    },
  },
  {
    render: 'tipButton',
    name: 'handleFailed',
    title: '失败',
    text: '',
    type: 'danger',
    icon: 'fa fa-times',
    class: 'table-row-failed',
    disabledTip: false,
    display: (row: TableRow) => row.status === 'PENDING' || row.status === 'PROCESSING',
    click: (row: TableRow, field: TableColumn) => {
      handleFailed(row)
    },
  },
  {
    render: 'tipButton',
    name: 'cancelOrder',
    title: '取消',
    text: '',
    type: 'warning',
    icon: 'fa fa-ban',
    class: 'table-row-cancel',
    disabledTip: false,
    display: (row: TableRow) => row.status === 'PENDING',
    click: (row: TableRow, field: TableColumn) => {
      cancelOrder(row)
    },
  },
  {
    render: 'tipButton',
    name: 'delete',
    title: '删除',
    text: '',
    type: 'danger',
    icon: 'fa fa-trash',
    class: 'table-row-delete',
    disabledTip: false,
    display: (row: TableRow) => row.status === 'PENDING',
    click: (row: TableRow, field: TableColumn) => {
      baTable.onTableAction('delete', { row })
    },
  },
]

const baTable = new baTableClass(
  new baTableApi('/admin/finance.RechargeOrder/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false, width: 50 },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '订单号', prop: 'order_no', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 200, showOverflowTooltip: true },
      { label: '第三方交易号', prop: 'trade_no', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 180, showOverflowTooltip: true },
      { label: '用户名', prop: 'username', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 100 },
      { label: '用户昵称', prop: 'nickname', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 120, showOverflowTooltip: true },
      { label: '充值金额', prop: 'amount', align: 'center', operator: 'RANGE', sortable: 'custom', width: 100 },
      { label: '实际到账', prop: 'actual_amount', align: 'center', operator: 'RANGE', sortable: 'custom', width: 100 },
      { label: '手续费', prop: 'fee_amount', align: 'center', operator: false, width: 80 },
      { label: '赠送金额', prop: 'gift_amount', align: 'center', operator: false, width: 90 },
      {
        label: '状态',
        prop: 'status',
        align: 'center',
        render: 'tag',
        operator: 'eq',
        sortable: false,
        width: 90,
        replaceValue: {
          'PENDING': '待支付',
          'PROCESSING': '处理中',
          'SUCCESS': '支付成功',
          'FAILED': '支付失败',
          'CANCELLED': '已取消',
          'TIMEOUT': '已超时',
        },
      },
      { label: '支付方式', prop: 'payment_method', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 100 },
      { label: '支付通道', prop: 'payment_channel', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 100 },
      { label: '通道名称', prop: 'channel_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 120, showOverflowTooltip: true },
      { label: '客户端IP', prop: 'client_ip', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 120 },
      { label: '通知次数', prop: 'notify_count', align: 'center', operator: false, width: 80 },
      { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
      { label: '成功时间', prop: 'success_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
      { label: '过期时间', prop: 'expire_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
      {
        label: '操作',
        align: 'center',
        width: 240,
        fixed: 'right',
        render: 'buttons',
        buttons: optButtons,
        operator: false,
      },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'id', order: 'desc' },
  },
  {
    defaultItems: {
      user_id: '',
      channel_id: '',
      amount: '',
      actual_amount: '',
      fee_amount: '',
      gift_amount: '',
      payment_method: '',
      payment_channel: '',
      status: 'PENDING',
      client_ip: '',
      remark: '',
      admin_remark: '',
    },
  }
)

provide('baTable', baTable)

const handleSuccess = (row: TableRow) => {
  ElMessageBox.confirm('确认将此订单标记为支付成功？', '确认操作', {
    type: 'warning',
  })
    .then(() => {
      baTable.api
        .postData('handleSuccess', { id: row.id })
        .then(() => {
          ElMessage.success('处理成功')
          baTable.onTableHeaderAction('refresh', {})
          loadStats()
        })
        .catch((err: any) => {
          console.error(err)
        })
    })
    .catch(() => {})
}

const handleFailed = (row: TableRow) => {
  ElMessageBox.prompt('请输入失败原因', '处理失败', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    inputPlaceholder: '失败原因',
  })
    .then(({ value }) => {
      baTable.api
        .postData('handleFailed', { id: row.id, reason: value || '管理员手动处理' })
        .then(() => {
          ElMessage.success('处理成功')
          baTable.onTableHeaderAction('refresh', {})
          loadStats()
        })
        .catch((err: any) => {
          console.error(err)
        })
    })
    .catch(() => {})
}

const cancelOrder = (row: TableRow) => {
  ElMessageBox.prompt('请输入取消原因', '取消订单', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    inputPlaceholder: '取消原因',
  })
    .then(({ value }) => {
      baTable.api
        .postData('cancelOrder', { id: row.id, reason: value || '管理员取消' })
        .then(() => {
          ElMessage.success('取消成功')
          baTable.onTableHeaderAction('refresh', {})
          loadStats()
        })
        .catch((err: any) => {
          console.error(err)
        })
    })
    .catch(() => {})
}

const exportOrders = () => {
  const params = {
    start_date: '',
    end_date: '',
    status: '',
  }
  
  baTable.api
    .postData('export', params)
    .then((res: any) => {
      // 这里可以处理导出逻辑
      ElMessage.success('导出成功')
    })
    .catch((err: any) => {
      console.error(err)
    })
}

const loadStats = () => {
  baTable.api
    .get('getStats')
    .then((res: any) => {
      stats.value = res.data
    })
    .catch((err: any) => {
      console.error(err)
    })
}

onMounted(() => {
  baTable.table.ref = tableRef.value
  baTable.mount()
  baTable.getIndex()?.then(() => {
    baTable.initSort()
    baTable.dragSort()
    loadStats()
  })
})
</script>

<style scoped lang="scss">
.stats-cards {
  margin-bottom: 20px;
  
  .stats-card {
    text-align: center;
    
    &.success {
      border-left: 4px solid #67c23a;
    }
    
    &.warning {
      border-left: 4px solid #e6a23c;
    }
    
    &.danger {
      border-left: 4px solid #f56c6c;
    }
    
    &.info {
      border-left: 4px solid #909399;
    }
    
    &.primary {
      border-left: 4px solid #409eff;
    }
    
    &.processing {
      border-left: 4px solid #409eff;
    }
    
    &.cancelled {
      border-left: 4px solid #f56c6c;
    }
    
    &.timeout {
      border-left: 4px solid #909399;
    }
    
    .stats-content {
      .stats-value {
        font-size: 24px;
        font-weight: bold;
        color: #303133;
        margin-bottom: 5px;
      }
      
      .stats-label {
        font-size: 14px;
        color: #909399;
      }
    }
  }
}
</style>