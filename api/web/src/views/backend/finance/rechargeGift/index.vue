<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit', 'delete']"
    />

    <!-- 表格 -->
    <Table ref="tableRef" />

    <!-- 表单 -->
    <PopupForm />
    
  </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted } from 'vue'
import baTableClass from '/@/utils/baTable'
import { baTableApi } from '/@/api/common'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import PopupForm from './popupForm.vue'
import { ElMessage } from 'element-plus'

const tableRef = ref()
const optButtons: OptButton[] = [
  {
    render: 'tipButton',
    name: 'edit',
    title: '编辑',
    text: '',
    type: 'primary',
    icon: 'fa fa-edit',
    class: 'table-row-edit',
    disabledTip: false,
    click: (row: TableRow, field: TableColumn) => {
      baTable.onTableAction('edit', { row, field })
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
    click: (row: TableRow, field: TableColumn) => {
      baTable.onTableAction('delete', { row, field })
    },
  },
  {
    render: 'tipButton',
    name: 'toggleStatus',
    title: '切换状态',
    text: '',
    type: 'warning',
    icon: 'fa fa-toggle-on',
    class: 'table-row-toggle',
    disabledTip: false,
    click: (row: TableRow, field: TableColumn) => {
      toggleStatus(row)
    },
  },
]

const baTable = new baTableClass(
  new baTableApi('/admin/finance.RechargeGift/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },

      { 
        label: '最低充值金额', 
        prop: 'charge_amount', 
        align: 'center', 
        operator: 'RANGE',
        sortable: 'custom'
      },
      { 
        label: '赠送金额', 
        prop: 'bonus_amount', 
        align: 'center', 
        operator: 'RANGE',
        sortable: 'custom'
      },
      {
        label: '状态',
        prop: 'status',
        align: 'center',
        render: 'tag',
        operator: 'eq',
        sortable: false,
        replaceValue: {
          0: '禁用',
          1: '启用',
        },
        custom: {
          0: 'danger',
          1: 'success',
        },
      },
      { label: '创建时间', prop: 'create_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
      {
        label: '操作',
        align: 'center',
        width: 96,
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
      charge_amount: '',
      bonus_amount: '',
      status: 1,
    },
  },
  {},
  {}
)

const toggleStatus = (row: TableRow) => {
  const newStatus = row.status ? 0 : 1
  baTable.api
    .postData('changeStatus', { ids: [row.id], status: newStatus })
    .then(() => {
      row.status = newStatus
      baTable.onTableHeaderAction('refresh', {})
    })
    .catch((err: any) => {
      console.error(err)
    })
}

provide('baTable', baTable)

onMounted(() => {
  baTable.table.ref = tableRef.value
  baTable.mount()
  baTable.getIndex()?.then(() => {
    baTable.initSort()
    baTable.dragSort()
  })
})
</script>

<style scoped>
.ba-table-box {
  padding: 20px;
}

.ba-table-alert {
  margin-bottom: 20px;
}
</style>