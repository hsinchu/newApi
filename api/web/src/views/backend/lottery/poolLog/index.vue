<template>
  <div class="default-main ba-table-box">
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索 彩种代码/期号'"
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
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'

defineOptions({
  name: 'lottery/poolLog/index',
})
const tableRef = ref()
const formRef = ref()

const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

const baTable = new baTableClass(
  new baTableApi('/admin/lottery.LotteryPoolLog/'),
  {
    pk: 'id',
    column: [
      { type: 'selection', align: 'center', operator: false },
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: false, sortable: 'custom' },
      { 
        label: '彩种代码', 
        prop: 'type_code', 
        align: 'center', 
        operator: 'LIKE', 
        operatorPlaceholder: '模糊查询',
        width: 120
      },
      { 
        label: '彩种名称', 
        prop: 'lotteryType.type_name', 
        align: 'center', 
        operator: 'LIKE',
        width: 150
      },
      { 
        label: '期号', 
        prop: 'period_no', 
        align: 'center', 
        operator: 'LIKE', 
        operatorPlaceholder: '模糊查询',
        width: 150
      },
      { 
        label: '服务费金额', 
        prop: 'bonus_system', 
        align: 'center', 
        operator: false,
        operatorPlaceholder: '金额范围',
        width: 120,
      },
      { 
        label: '更新时间', 
        prop: 'update_time', 
        align: 'center', 
        operator: false,
        operatorPlaceholder: '时间范围',
        render: 'datetime',
        sortable: 'custom'
      },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'update_time', order: 'desc' },
  },
  {
    defaultItems: {
      type_code: '',
      period_no: '',
      bonus_system: '0.00',
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

<style scoped>
.ba-table-box {
  padding: 20px;
}
</style>