<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索 彩票类型名称/彩票类型代码'"
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
  name: 'lottery/index',
})
const tableRef = ref()
const formRef = ref()

const optButtons: OptButton[] = defaultOptButtons(['edit'])

const baTable = new baTableClass(
  new baTableApi('/admin/lottery.LotteryType/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '彩票类型代码', prop: 'type_code', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '彩票类型名称', prop: 'type_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '彩票分类', prop: 'category', align: 'center', operator: 'eq', replaceValue: {
              'SPORTS': '体育彩票',
              'WELFARE': '福利彩票',
              'SPORTS_SINGLE': '体育单场'
          }
      },
      { label: '最小投注金额', prop: 'min_bet_amount', align: 'center', operator: false },
      { label: '最大投注金额', prop: 'max_bet_amount', align: 'center', operator: false },
      { label: '排序', prop: 'sort_order', align: 'center', operator: 'RANGE', sortable: 'custom', width: 90 },
      {
          label: '状态',
          prop: 'is_enabled',
          align: 'center',
          width: 100,
          render: 'switch',
          operator: 'eq',
          sortable: false,
      },
      { label: '更新时间', prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
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
      category: 'SPORTS',
      min_bet_amount: '1.00',
      max_bet_amount: '10000.00',
      sort_order: 100,
      is_enabled: 1
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