<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit']"
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
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'

defineOptions({
  name: 'finance/paymentMethod',
})
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
  new baTableApi('/admin/finance.PaymentMethod/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '支付方式代码', prop: 'method_code', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '支付方式名称', prop: 'method_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      {
        label: '支付图标',
        prop: 'method_icon',
        align: 'center',
        operator: false,
        render: 'image',
        imageHeight: 40,
      },
      { label: '描述', prop: 'description', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', showOverflowTooltip: true },
      {
        label: '是否启用',
        prop: 'is_enabled',
        align: 'center',
        render: 'tag',
        operator: 'eq',
        sortable: false,
        replaceValue: {
          0: '禁用',
          1: '启用',
        },
      },
      { label: '排序', prop: 'sort_order', align: 'center', operator: 'RANGE', sortable: 'custom' },
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
    defaultOrder: { prop: 'sort_order', order: 'desc' },
  },
  {
    defaultItems: {
      method_code: '',
      method_name: '',
      method_icon: '',
      description: '',
      is_enabled: 1,
      sort_order: 0,
    },
  }
)

provide('baTable', baTable)

const toggleStatus = (row: TableRow) => {
  const newStatus = row.is_enabled ? 0 : 1
  baTable.api
    .postData('toggleStatus', { id: row.id, status: newStatus })
    .then(() => {
      row.is_enabled = newStatus
      baTable.onTableHeaderAction('refresh', {})
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
  })
})
</script>

<style scoped lang="scss"></style>