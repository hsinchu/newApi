<template>
  <div class="default-main ba-table-box">
    <!-- 数据概览 -->
    <el-card class="overview-card" style="margin-bottom: 20px;">
      <template #header>
        <div class="card-header">
          <span>开奖数据概览</span>
          <el-button type="primary" size="small" @click="loadOverview">刷新</el-button>
        </div>
      </template>
      <el-row :gutter="20">
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-title">今日开奖</div>
            <div class="stat-value">{{ overview.today?.count || 0 }}期</div>
            <!-- <div class="stat-desc">投注: ¥{{ formatAmount(overview.today?.total_bet || 0) }}</div> -->
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-title">昨日开奖</div>
            <div class="stat-value">{{ overview.yesterday?.count || 0 }}期</div>
            <!-- <div class="stat-desc">投注: ¥{{ formatAmount(overview.yesterday?.total_bet || 0) }}</div> -->
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-title">本周开奖</div>
            <div class="stat-value">{{ overview.week?.count || 0 }}期</div>
            <!-- <div class="stat-desc">投注: ¥{{ formatAmount(overview.week?.total_bet || 0) }}</div> -->
          </div>
        </el-col>
        <el-col :span="6">
          <div class="stat-item">
            <div class="stat-title">本月开奖</div>
            <div class="stat-value">{{ overview.month?.count || 0 }}期</div>
            <!-- <div class="stat-desc">投注: ¥{{ formatAmount(overview.month?.total_bet || 0) }}</div> -->
          </div>
        </el-col>
      </el-row>
    </el-card>
    
    <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

    <!-- 表格顶部菜单 -->
    <TableHeader
      :buttons="['refresh', 'comSearch', 'quickSearch', 'columnDisplay']"
      quick-search-placeholder="快速搜索：期号/彩种代码"
    />

    <!-- 表格 -->
    <Table ref="tableRef" />

    <!-- 表单 -->
    <PopupForm ref="formRef" />

    <!-- 手动开奖弹窗 -->
    <el-dialog v-model="drawDialog.visible" title="手动开奖" width="600px" :close-on-click-modal="false">
      <el-form :model="drawDialog.form" label-width="100px">
        <el-form-item label="期号">
          <el-input v-model="drawDialog.form.period_no" readonly />
        </el-form-item>
        <el-form-item label="彩种">
          <el-input v-model="drawDialog.form.lottery_type_name" readonly />
        </el-form-item>
        <el-form-item label="开奖号码" required>
          <el-input
            v-model="drawDialog.form.draw_numbers"
            placeholder="请输入开奖号码，用逗号分隔，例如：1,2,3,4,5"
          />
        </el-form-item>
        <el-form-item label="开奖详情">
          <el-input
            v-model="drawDialog.form.draw_result_text"
            type="textarea"
            :rows="3"
            placeholder="可选：输入开奖详情JSON格式，例如：{'numbers': [1, 2, 3, 4, 5], 'bonus': 6}"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="drawDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="confirmDraw" :loading="drawDialog.loading">确认开奖</el-button>
      </template>
    </el-dialog>

    <!-- 结算弹窗 -->
    <el-dialog v-model="settleDialog.visible" title="结算确认" width="500px" :close-on-click-modal="false">
      <div class="settle-info">
        <p><strong>期号：</strong>{{ settleDialog.data.period_no }}</p>
        <p><strong>彩种：</strong>{{ settleDialog.data.lottery_type_name }}</p>
        <p><strong>开奖结果：</strong></p>
        <el-card class="mt-2">
          <pre>{{ JSON.stringify(settleDialog.data.draw_result, null, 2) }}</pre>
        </el-card>
        <p class="mt-3"><strong>确定要结算此期开奖吗？结算后将自动处理所有相关投注订单。</strong></p>
      </div>
      <template #footer>
        <el-button @click="settleDialog.visible = false">取消</el-button>
        <el-button type="primary" @click="confirmSettle" :loading="settleDialog.loading">确认结算</el-button>
      </template>
    </el-dialog>

    <!-- 统计弹窗 -->
    <el-dialog v-model="statsDialog.visible" title="开奖统计" width="800px" :close-on-click-modal="false">
      <div v-loading="statsDialog.loading" class="stats-content">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-statistic title="总开奖期数" :value="statsDialog.data.total_count" />
          </el-col>
          <el-col :span="8">
            <el-statistic title="总投注金额" :value="statsDialog.data.total_bet_amount" prefix="¥" />
          </el-col>
          <el-col :span="8">
            <el-statistic title="总中奖金额" :value="statsDialog.data.total_win_amount" prefix="¥" />
          </el-col>
        </el-row>
        <el-row :gutter="20" class="mt-4">
          <el-col :span="8">
            <el-statistic title="总投注笔数" :value="statsDialog.data.total_bet_count" />
          </el-col>
          <el-col :span="8">
            <el-statistic title="中奖笔数" :value="statsDialog.data.total_win_count" />
          </el-col>
          <el-col :span="8">
            <el-statistic title="中奖率" :value="statsDialog.data.win_rate" suffix="%" />
          </el-col>
        </el-row>
        <el-row :gutter="20" class="mt-4">
          <el-col :span="12">
            <el-statistic title="平台盈利" :value="statsDialog.data.profit_amount" prefix="¥" />
          </el-col>
          <el-col :span="12">
            <el-statistic title="盈利率" :value="statsDialog.data.profit_rate" suffix="%" />
          </el-col>
        </el-row>
      </div>
      <template #footer>
        <el-button @click="statsDialog.visible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted, reactive } from 'vue'
import baTableClass from '/@/utils/baTable'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import PopupForm from './popupForm.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { ElMessage, ElMessageBox } from 'element-plus'

defineOptions({
  name: 'lotterydraw/index',
})
const tableRef = ref()
const formRef = ref()

const drawDialog = reactive({
  visible: false,
  loading: false,
  form: {
    id: 0,
    period_no: '',
    lottery_type_name: '',
    draw_numbers: '',
    draw_result_text: ''
  }
})

const settleDialog = reactive({
  visible: false,
  loading: false,
  data: {} as any
})

const statsDialog = reactive({
  visible: false,
  loading: false,
  data: {} as any
})

// 概览数据
const overview = ref({
  today: null,
  yesterday: null,
  week: null,
  month: null
})

// 格式化金额
const formatAmount = (amount: number) => {
  return (amount / 100).toFixed(2)
}

// 加载概览数据
const loadOverview = () => {
  baTable.api.postData('overview').then((res) => {
    if (res.code === 1) {
      overview.value = res.data
    }
  })
}

const baTable = new baTableClass(
  new baTableApi('/admin/lottery.LotteryDraw/'),
  {
    pk: 'id',
    column: [
      // { type: 'selection', align: 'center', operator: false },
      // { label: 'ID', prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
      { label: '彩种代码', prop: 'lottery_code', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 100 },
      { label: '彩种名称', prop: 'lottery_type_name', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 100 },
      { label: '期号', prop: 'period_no', align: 'center', operator: 'LIKE', operatorPlaceholder: '模糊查询', width: 120 },
      { label: '开奖号码', prop: 'draw_numbers', align: 'center', operator: false, width: 150 },
      { label: '开奖时间', prop: 'draw_time', align: 'center', operator: 'RANGE', sortable: 'custom', width: 160 },
      { label: '结算时间', prop: 'settle_time', align: 'center', operator: 'RANGE', width: 160 },
      { label: '投注金额', prop: 'total_bet_amount', align: 'center', operator: 'RANGE', render: 'tag', width: 111 },
      { label: '中奖金额', prop: 'total_win_amount', align: 'center', operator: 'RANGE', render: 'tag', width: 111 },
      { label: '投注笔数', prop: 'bet_count', align: 'center', operator: 'RANGE', width: 88 },
      { label: '中奖笔数', prop: 'win_count', align: 'center', operator: 'RANGE', width: 88 },
      { label: '状态', prop: 'status', align: 'center', operator: 'eq', replaceValue: {
        'PENDING': '待开奖',
        'DRAWN': '已开奖',
        'SETTLED': '已结算',
        'CANCELLED': '已取消'
      }, render: 'tag', custom: {
        'PENDING': 'warning',
        'DRAWN': 'primary',
        'SETTLED': 'success',
        'CANCELLED': 'danger'
      }},
      { label: '更新时间', prop: 'update_time', align: 'center', render: 'datetime', operator: 'RANGE', sortable: 'custom', width: 160, timeFormat: 'yyyy-mm-dd hh:MM:ss' },
    ],
    dblClickNotEditColumn: [undefined],
    defaultOrder: { prop: 'id', order: 'desc' },
  },
  {
    defaultItems: {
      status: 'PENDING',
      prize_pool: '0.00',
      draw_time: new Date().getTime() / 1000
    },
  }
)

provide('baTable', baTable)

defineExpose({
    baTable,
    overview,
    formatAmount,
    loadOverview
})

const showDrawDialog = (row: any) => {
  drawDialog.form.id = row.id
  drawDialog.form.period_no = row.period_no
  drawDialog.form.lottery_type_name = row.lottery_type?.type_name || ''
  drawDialog.form.draw_numbers = ''
  drawDialog.form.draw_result_text = ''
  drawDialog.visible = true
}

const confirmDraw = () => {
  if (!drawDialog.form.draw_numbers.trim()) {
    ElMessage.error('请输入开奖号码')
    return
  }
  
  // 验证开奖号码格式
  const numbers = drawDialog.form.draw_numbers.split(',')
  for (let num of numbers) {
    const trimmedNum = num.trim()
    if (!/^\d+$/.test(trimmedNum) || parseInt(trimmedNum) < 0 || parseInt(trimmedNum) > 99) {
      ElMessage.error('开奖号码格式错误，应为0-99之间的数字，用逗号分隔')
      return
    }
  }
  
  let drawResult = null
  if (drawDialog.form.draw_result_text.trim()) {
    try {
      drawResult = JSON.parse(drawDialog.form.draw_result_text)
    } catch (e) {
      ElMessage.error('开奖详情格式错误，请输入有效的JSON格式')
      return
    }
  }
  
  drawDialog.loading = true
  baTable.api.postData('manualDraw', {
    id: drawDialog.form.id,
    draw_numbers: drawDialog.form.draw_numbers,
    draw_result: drawResult
  }).then((res) => {
    if (res.code === 1) {
      ElMessage.success(res.msg)
      drawDialog.visible = false
      baTable.onTableHeaderAction('refresh', {})
    }
  }).finally(() => {
    drawDialog.loading = false
  })
}

const showSettleDialog = (row: any) => {
  settleDialog.data = {
    id: row.id,
    period_no: row.period_no,
    lottery_type_name: row.lottery_type?.type_name || '',
    draw_result: row.draw_result || {}
  }
  settleDialog.visible = true
}

const confirmSettle = () => {
  settleDialog.loading = true
  baTable.api.postData('settle', {
    id: settleDialog.data.id
  }).then((res) => {
    if (res.code === 1) {
      ElMessage.success(res.msg)
      settleDialog.visible = false
      baTable.onTableHeaderAction('refresh', {})
    }
  }).finally(() => {
    settleDialog.loading = false
  })
}

const showStats = (id?: number) => {
  statsDialog.loading = true
  statsDialog.visible = true
  
  const params = id ? { id } : {}
  baTable.api.postData('statistics', params).then((res) => {
    if (res.code === 1) {
      statsDialog.data = res.data
    }
  }).finally(() => {
    statsDialog.loading = false
  })
}

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
    // 加载概览数据
    loadOverview()
})
</script>

<style scoped>
.overview-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-item {
  text-align: center;
  padding: 20px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.el-col:nth-child(2) .stat-item {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.el-col:nth-child(3) .stat-item {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.el-col:nth-child(4) .stat-item {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-title {
  font-size: 14px;
  margin-bottom: 8px;
  opacity: 0.9;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 4px;
}

.stat-desc {
  font-size: 12px;
  opacity: 0.8;
}
</style>

<style scoped lang="scss">
.settle-info {
  p {
    margin: 10px 0;
  }
  
  pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
    max-height: 150px;
    overflow-y: auto;
  }
}

.stats-content {
  .el-statistic {
    text-align: center;
  }
}
</style>