<template>
  <div class="default-main ba-table-box">
    <el-alert
      class="ba-table-alert"
      v-if="baTable.table.remark"
      :title="baTable.table.remark"
      type="info"
      show-icon
    />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'add', 'edit', 'comSearch', 'quickSearch', 'columnDisplay']"
      :quick-search-placeholder="'快速搜索：类型、名称、键值'"
    >
    </TableHeader>

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
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'

defineOptions({
  name: 'lottery/bonus',
})

const { t } = useI18n()
const tableRef = ref()
const formRef = ref()

const baTable = new baTableClass(
  new baTableApi('/admin/lottery.LotteryBonus/'),
  {
    pk: 'id',
    dragSortLimitField: 'type_id',
    column: [
      { label: 'ID', prop: 'id', align: 'center', width: 70, operator: false, sortable: 'custom' },
      { 
        label: '彩种类型', 
        prop: 'lotteryType.type_name', 
        align: 'center', 
        operator: false,
        render: 'tag',
      },
      { label: '玩法', prop: 'type_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { label: '键值', prop: 'type_key', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询' },
      { 
        label: '最低金额', 
        prop: 'min_price', 
        align: 'center', 
        operator: 'RANGE', 
        sortable: 'custom',
        render: 'tag',
        custom: { '': 'info' }
      },
      { 
        label: '最高金额', 
        prop: 'max_price', 
        align: 'center', 
        operator: 'RANGE', 
        sortable: 'custom',
        render: 'tag',
        custom: { '': 'info' }
      },
      {
          label: '状态',
          prop: 'status',
          align: 'center',
          width: 100,
          render: 'switch',
          operator: 'eq',
          sortable: false,
      },
      { 
        label: '排序', 
        prop: 'weigh', 
        align: 'center', 
        operator: false, 
        width: 80 
      },
      { 
        label: '更新时间', 
        prop: 'update_time', 
        align: 'center', 
        render: 'datetime', 
        operator: false, 
        width: 160 
      },
      {
        label: '操作',
        align: 'center',
        width: 100,
        render: 'buttons',
        buttons: defaultOptButtons(['edit', 'delete']),
        operator: false,
      },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'lottery_id', order: 'desc' },
  },
  {
    defaultItems: {
      type: '',
      name: '',
      key: '',
      min_price: 0,
      max_price: 0,
      bonus: 0,
      bonus_json: '',
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

<style scoped></style>