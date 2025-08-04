<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索 公告标题'"
    />

    <!-- 表格 -->
    <Table ref="tableRef" />

    <!-- 表单 -->
    <PopupForm ref="formRef" />
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
  name: 'dano',
})
const tableRef = ref()
const formRef = ref()

const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

const baTable = new baTableClass(
  new baTableApi('/admin/other.dano/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '公告标题', prop: 'title', align: 'left', operator: 'LIKE', operatorPlaceholder: '模糊查询', minWidth: 200 },
      {
        label: '状态',
        prop: 'status',
        align: 'center',
        width: 180,
        render: 'switch',
        operator: 'eq',
        sortable: false,
        replaceValue: {
          0: '已下架',
          1: '正常显示'
        }
      },
      { label: '排序', prop: 'sort_num', align: 'center', operator: 'RANGE', sortable: 'custom' },
      {
        label: '操作',
        align: 'center',
        width: 115,
        render: 'buttons',
        fixed: 'right',
        buttons: optButtons,
        operator: false,
      },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'sort_num', order: 'desc' },
  },
  {
    defaultItems: {
      title: '',
      content: '',
      sort_num: 0,
      status: 1
    },
  }
)

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

<style scoped lang="scss"></style>