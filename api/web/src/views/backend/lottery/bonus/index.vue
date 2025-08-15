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
    <!-- 过滤条件 -->
    <div class="filter-bar" style="width: 200px;">
      <el-row :gutter="20">
        <el-col :span="24">
          <el-select 
            v-model="selectedLotteryType" 
            placeholder="选择彩种类型" 
            clearable 
            @change="handleLotteryTypeChange"
            style="width: 100%"
          >
            <el-option label="全部" value="" />
            <el-option
              v-for="item in lotteryTypes"
              :key="item.id"
              :label="item.type_name"
              :value="item.id"
            />
          </el-select>
        </el-col>
      </el-row>
    </div>
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

// 彩种类型选项
const lotteryTypes = ref<any[]>([])
const selectedLotteryType = ref('')

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

// 加载彩种类型
const loadLotteryTypes = async () => {
  try {
    const statisticsApi = new baTableApi('/admin/lottery.Statistics/')
    const res = await statisticsApi.postData('lotteryTypes')
    if (res.code === 1) {
      lotteryTypes.value = res.data
    }
  } catch (error) {
    console.error('加载彩种类型失败:', error)
  }
}

// 彩种类型过滤
const handleLotteryTypeChange = (value: string) => {
  selectedLotteryType.value = value
  if (value) {
    // 根据选择的彩种ID找到对应的彩种名称
    const selectedType = lotteryTypes.value.find(item => item.id == value)
    if (selectedType) {
      // 使用search数组格式进行过滤
      baTable.table.filter!.search = [{
        field: 'lotteryType.type_name',
        val: selectedType.type_name,
        operator: 'eq',
        render: 'tag'
      }]
    }
  } else {
    // 清空搜索条件
    delete baTable.table.filter!.search
  }
  baTable.getIndex()
}

provide('baTable', baTable)

onMounted(() => {
  baTable.table.ref = tableRef.value
  baTable.mount()
  loadLotteryTypes()
  baTable.getIndex()?.then(() => {
    baTable.initSort()
    baTable.dragSort()
  })
})
</script>
