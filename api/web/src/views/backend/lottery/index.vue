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
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: false, sortable: 'custom' },
      { label: '彩种代码', prop: 'type_code', align: 'center', operator: false, operatorPlaceholder: '模糊查询' },
      { label: '彩种名称', prop: 'type_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '彩种分类', prop: 'category', align: 'center', operator: false},
      { label: '平台奖池', prop: 'default_pool', align: 'center', operator: false },
      { label: '奖金池', prop: 'bonus_pool', align: 'center', operator: false },
      { label: '平台抽取', prop: 'bonus_system', align: 'center', operator: false },
      { label: '单人比例(%)', prop: 'max_pool_rate', align: 'center', operator: false },
      { label: '平台服务费(%)', prop: 'bonus_system_rate', align: 'center', operator: false, width: 120 },
      { label: '最小投注', prop: 'min_bet_amount', align: 'center', operator: false },
      { label: '最大投注', prop: 'max_bet_amount', align: 'center', operator: false },
      { label: '排序', prop: 'sort_order', align: 'center', operator: false, sortable: 'custom', width: 90 },
      {
          label: '状态',
          prop: 'is_enabled',
          align: 'center',
          width: 100,
          render: 'switch',
          operator: 'eq',
          sortable: false,
      },
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
      type_code: '',
      type_name: '',
      category: 'QUICK',
      type_icon: '',
      default_pool: '10000.00',
      bonus_pool: '10000.00',
      bonus_system: '0.00',
      bonus_system_rate: '20.0',
      max_pool_rate: '0.0',
      min_bet_amount: '2.00',
      max_bet_amount: '10000.00',
      daily_limit: '0.00',
      commission_rate: '0.0000',
      auto_draw: 1,
      sort_order: 100,
      is_enabled: 1,
      remark: ''
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