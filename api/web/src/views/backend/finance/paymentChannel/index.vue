<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索：通道代码、内部名称、外部名称'"
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
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'

defineOptions({
  name: 'finance/paymentChannel',
})
const tableRef = ref()
const optButtons: OptButton[] = [
  {
    render: 'tipButton',
    name: 'edit',
    title: 'Edit',
    text: '',
    type: 'primary',
    icon: 'fa fa-edit',
    class: 'table-row-edit',
    disabledTip: false,
    click: (row: TableRow, field: TableColumn) => {
      baTable.onTableAction('edit', { row })
    },
  },
  // {
  //   render: 'tipButton',
  //   name: 'delete',
  //   title: 'Delete',
  //   text: '',
  //   type: 'danger',
  //   icon: 'fa fa-trash',
  //   class: 'table-row-delete',
  //   disabledTip: false,
  //   click: (row: TableRow, field: TableColumn) => {
  //     ElMessageBox.confirm(
  //       `确定要删除支付通道 "${row.external_name}" 吗？此操作不可恢复！`,
  //       '删除确认',
  //       {
  //         confirmButtonText: '确定删除',
  //         cancelButtonText: '取消',
  //         type: 'warning',
  //       }
  //     ).then(() => {
  //       baTable.onTableAction('delete', { row })
  //     }).catch(() => {
  //       // 用户取消删除
  //     })
  //   },
  // },
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
  new baTableApi('/admin/finance.PaymentChannel/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '通道代码', prop: 'channel_code', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '内部名称', prop: 'internal_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '外部名称', prop: 'external_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '商户ID', prop: 'merchant_id', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', showOverflowTooltip: true },
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
        width: 95,
        render: 'buttons',
        fixed: 'right',
        buttons: optButtons,
        operator: false,
      },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'sort_order', order: 'desc' },
  },
  {
    defaultItems: {
      channel_code: '',
      internal_name: '',
      external_name: '',
      merchant_id: '',
      secret_key: '',
      callback_ip: '',
      notify_url: '',
      return_url: '',
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

const testChannel = (row: TableRow) => {
  baTable.api
    .postData('testChannel', { id: row.id })
    .then((res: any) => {
      ElMessage.success(res.msg || '测试成功')
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