<template>
  <div class="default-main ba-table-box">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="mb-4">
      <el-col :span="6">
        <el-card class="stat-card">
          <el-statistic title="总彩票类型" :value="overviewStats.total_lottery_types" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <el-statistic title="总投注订单" :value="overviewStats.total_bet_orders" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <el-statistic title="总投注金额" :value="overviewStats.total_bet_amount" prefix="¥" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card class="stat-card">
          <el-statistic title="总中奖金额" :value="overviewStats.total_win_amount" prefix="¥" />
        </el-card>
      </el-col>
    </el-row>

    <!-- 统计选项卡 -->
    <el-tabs v-model="activeTab" @tab-click="handleTabClick" style="margin-top:12px;">
      <!-- 用户统计 -->
      <el-tab-pane label="用户统计" name="userStats">
        <div class="stats-content">
          <div class="filter-bar mb-4">
            <el-row :gutter="20">
              <el-col :span="6">
                <el-date-picker
                  v-model="userStatsFilter.dateRange"
                  type="daterange"
                  range-separator="至"
                  start-placeholder="开始日期"
                  end-placeholder="结束日期"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  @change="loadUserStats"
                />
              </el-col>
              <el-col :span="4">
                <el-select v-model="userStatsFilter.lottery_type_id" placeholder="选择彩票类型" clearable @change="loadUserStats">
                  <el-option label="全部" value="" />
                  <el-option
                    v-for="item in lotteryTypes"
                    :key="item.id"
                    :label="item.type_name"
                    :value="item.id"
                  />
                </el-select>
              </el-col>
              <el-col :span="4">
                <el-button type="primary" @click="loadUserStats">查询</el-button>
                <el-button @click="exportUserStats">导出</el-button>
              </el-col>
            </el-row>
          </div>
          
          <el-table v-loading="userStatsLoading" :data="userStatsData" border>
            <el-table-column prop="user_id" label="用户ID" />
            <el-table-column prop="username" label="用户名" />
            <el-table-column prop="total_bets" label="投注次数" />
            <el-table-column prop="total_amount" label="投注金额">
              <template #default="{ row }">
                ¥{{ row.total_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="win_amount" label="中奖金额">
              <template #default="{ row }">
                ¥{{ row.win_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="win_rate" label="中奖率">
              <template #default="{ row }">
                {{ row.win_rate }}%
              </template>
            </el-table-column>
            <el-table-column prop="profit_loss" label="盈亏">
              <template #default="{ row }">
                <span :class="row.profit_loss >= 0 ? 'text-success' : 'text-danger'">
                  ¥{{ row.profit_loss }}
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="last_bet_time" label="最后投注时间" />
          </el-table>
          
          <el-pagination
            v-model:current-page="userStatsPagination.page"
            v-model:page-size="userStatsPagination.limit"
            :total="userStatsPagination.total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="loadUserStats"
            @current-change="loadUserStats"
            class="mt-4"
          />
        </div>
      </el-tab-pane>

      <!-- 开奖统计 -->
      <el-tab-pane label="开奖统计" name="drawStats">
        <div class="stats-content">
          <div class="filter-bar mb-4">
            <el-row :gutter="20">
              <el-col :span="6">
                <el-date-picker
                  v-model="drawStatsFilter.dateRange"
                  type="daterange"
                  range-separator="至"
                  start-placeholder="开始日期"
                  end-placeholder="结束日期"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  @change="loadDrawStats"
                />
              </el-col>
              <el-col :span="4">
                <el-select v-model="drawStatsFilter.lottery_type_id" placeholder="选择彩票类型" clearable @change="loadDrawStats">
                  <el-option label="全部" value="" />
                  <el-option
                    v-for="item in lotteryTypes"
                    :key="item.id"
                    :label="item.type_name"
                    :value="item.id"
                  />
                </el-select>
              </el-col>
              <el-col :span="4">
                <el-button type="primary" @click="loadDrawStats">查询</el-button>
                <el-button @click="exportDrawStats">导出</el-button>
              </el-col>
            </el-row>
          </div>
          
          <el-table v-loading="drawStatsLoading" :data="drawStatsData" border>
            <el-table-column prop="lottery_type_name" label="彩票类型" />
            <el-table-column prop="draw_no" label="期号" />
            <el-table-column prop="draw_time" label="开奖时间" />
            <el-table-column prop="total_bets" label="投注笔数" />
            <el-table-column prop="total_amount" label="投注金额">
              <template #default="{ row }">
                ¥{{ row.total_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="win_bets" label="中奖笔数" />
            <el-table-column prop="win_amount" label="中奖金额">
              <template #default="{ row }">
                ¥{{ row.win_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="profit_amount" label="平台盈利">
              <template #default="{ row }">
                <span :class="row.profit_amount >= 0 ? 'text-success' : 'text-danger'">
                  ¥{{ row.profit_amount }}
                </span>
              </template>
            </el-table-column>
            <el-table-column prop="status" label="状态">
              <template #default="{ row }">
                <el-tag :type="getDrawStatusType(row.status)">{{ row.status_text }}</el-tag>
              </template>
            </el-table-column>
          </el-table>
          
          <el-pagination
            v-model:current-page="drawStatsPagination.page"
            v-model:page-size="drawStatsPagination.limit"
            :total="drawStatsPagination.total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="loadDrawStats"
            @current-change="loadDrawStats"
            class="mt-4"
          />
        </div>
      </el-tab-pane>

      <!-- 投注统计 -->
      <el-tab-pane label="投注统计" name="betStats">
        <div class="stats-content">
          <div class="filter-bar mb-4">
            <el-row :gutter="20">
              <el-col :span="6">
                <el-date-picker
                  v-model="betStatsFilter.dateRange"
                  type="daterange"
                  range-separator="至"
                  start-placeholder="开始日期"
                  end-placeholder="结束日期"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  @change="loadBetStats"
                />
              </el-col>
              <el-col :span="4">
                <el-select v-model="betStatsFilter.lottery_type_id" placeholder="选择彩票类型" clearable @change="loadBetStats">
                  <el-option label="全部" value="" />
                  <el-option
                    v-for="item in lotteryTypes"
                    :key="item.id"
                    :label="item.type_name"
                    :value="item.id"
                  />
                </el-select>
              </el-col>
              <el-col :span="4">
                <el-select v-model="betStatsFilter.status" placeholder="选择状态" clearable @change="loadBetStats">
                  <el-option label="全部" value="" />
                  <el-option label="待确认" value="PENDING" />
                  <el-option label="待开奖" value="CONFIRMED" />
                  <el-option label="中奖" value="WINNING" />
                  <el-option label="未中奖" value="LOSING" />
                  <el-option label="已取消" value="CANCELLED" />
                  <el-option label="已退款" value="REFUNDED" />
                </el-select>
              </el-col>
              <el-col :span="4">
                <el-button type="primary" @click="loadBetStats">查询</el-button>
                <!-- <el-button @click="exportBetStats">导出</el-button> -->
              </el-col>
            </el-row>
          </div>
          
          <!-- 投注统计图表 -->
          <el-row :gutter="20" class="mb-4">
            <el-col :span="12">
              <el-card>
                <template #header>
                  <span>投注金额趋势</span>
                </template>
                <div id="betAmountChart" style="height: 300px;"></div>
              </el-card>
            </el-col>
            <el-col :span="12">
              <el-card>
                <template #header>
                  <span>投注状态分布</span>
                </template>
                <div id="betStatusChart" style="height: 300px;"></div>
              </el-card>
            </el-col>
          </el-row>
          
          <el-table v-loading="betStatsLoading" :data="betStatsData" border>
            <el-table-column prop="date" label="日期" />
            <el-table-column prop="lottery_type_name" label="彩票类型" />
            <el-table-column prop="total_bets" label="投注笔数" />
            <el-table-column prop="total_amount" label="投注金额">
              <template #default="{ row }">
                ¥{{ row.total_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="avg_amount" label="平均投注">
              <template #default="{ row }">
                ¥{{ row.avg_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="win_bets" label="中奖笔数" />
            <el-table-column prop="win_amount" label="中奖金额">
              <template #default="{ row }">
                ¥{{ row.win_amount }}
              </template>
            </el-table-column>
            <el-table-column prop="win_rate" label="中奖率">
              <template #default="{ row }">
                {{ row.win_rate }}%
              </template>
            </el-table-column>
          </el-table>
          
          <el-pagination
            v-model:current-page="betStatsPagination.page"
            v-model:page-size="betStatsPagination.limit"
            :total="betStatsPagination.total"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="loadBetStats"
            @current-change="loadBetStats"
            class="mt-4"
          />
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { baTableApi } from '/@/api/common'
import * as echarts from 'echarts'

defineOptions({
  name: 'lottery/statistics',
})

const api = new baTableApi('/admin/lottery.Statistics/')

// 总览统计
const overviewStats = reactive({
  total_lottery_types: 0,
  total_bet_orders: 0,
  total_bet_amount: 0,
  total_win_amount: 0
})

// 当前选项卡
const activeTab = ref('userStats')

// 彩票类型选项
const lotteryTypes = ref<any[]>([])

// 用户统计
const userStatsLoading = ref(false)
const userStatsData = ref<any[]>([])
const userStatsFilter = reactive({
  dateRange: [],
  lottery_type_id: ''
})
const userStatsPagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

// 开奖统计
const drawStatsLoading = ref(false)
const drawStatsData = ref<any[]>([])
const drawStatsFilter = reactive({
  dateRange: [],
  lottery_type_id: ''
})
const drawStatsPagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

// 投注统计
const betStatsLoading = ref(false)
const betStatsData = ref<any[]>([])
const betStatsFilter = reactive({
  dateRange: [],
  lottery_type_id: '',
  status: ''
})
const betStatsPagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

// 图表实例
let betAmountChart: echarts.ECharts | null = null
let betStatusChart: echarts.ECharts | null = null

// 加载总览统计
const loadOverviewStats = async () => {
  try {
    const res = await api.postData('overview')
    if (res.code === 1) {
      Object.assign(overviewStats, res.data)
    }
  } catch (error) {
    console.error('加载总览统计失败:', error)
  }
}

// 加载彩票类型
const loadLotteryTypes = async () => {
  try {
    const res = await api.postData('lotteryTypes')
    if (res.code === 1) {
      lotteryTypes.value = res.data
    }
  } catch (error) {
    console.error('加载彩票类型失败:', error)
  }
}

// 加载用户统计
const loadUserStats = async () => {
  userStatsLoading.value = true
  try {
    const params = {
      page: userStatsPagination.page,
      limit: userStatsPagination.limit,
      ...userStatsFilter
    }
    const res = await api.postData('userStats', params)
    if (res.code === 1) {
      userStatsData.value = res.data.list
      userStatsPagination.total = res.data.total
    }
  } catch (error) {
    console.error('加载用户统计失败:', error)
  } finally {
    userStatsLoading.value = false
  }
}

// 加载开奖统计
const loadDrawStats = async () => {
  drawStatsLoading.value = true
  try {
    const params = {
      page: drawStatsPagination.page,
      limit: drawStatsPagination.limit,
      ...drawStatsFilter
    }
    const res = await api.postData('drawStats', params)
    if (res.code === 1) {
      drawStatsData.value = res.data.list
      drawStatsPagination.total = res.data.total
    }
  } catch (error) {
    console.error('加载开奖统计失败:', error)
  } finally {
    drawStatsLoading.value = false
  }
}

// 加载投注统计
const loadBetStats = async () => {
  betStatsLoading.value = true
  try {
    const params = {
      page: betStatsPagination.page,
      limit: betStatsPagination.limit,
      ...betStatsFilter
    }
    const res = await api.postData('betStats', params)
    if (res.code === 1) {
      betStatsData.value = res.data.list
      betStatsPagination.total = res.data.total
      
      // 更新图表
      updateCharts(res.data.charts)
    }
  } catch (error) {
    console.error('加载投注统计失败:', error)
  } finally {
    betStatsLoading.value = false
  }
}

// 更新图表
const updateCharts = (chartData: any) => {
  if (betAmountChart && chartData.betAmount) {
    betAmountChart.setOption({
      title: { text: '投注金额趋势' },
      tooltip: { trigger: 'axis' },
      xAxis: {
        type: 'category',
        data: chartData.betAmount.dates
      },
      yAxis: { type: 'value' },
      series: [{
        data: chartData.betAmount.amounts,
        type: 'line',
        smooth: true
      }]
    })
  }
  
  if (betStatusChart && chartData.betStatus) {
    betStatusChart.setOption({
      title: { text: '投注状态分布' },
      tooltip: { trigger: 'item' },
      series: [{
        type: 'pie',
        radius: '50%',
        data: chartData.betStatus,
        emphasis: {
          itemStyle: {
            shadowBlur: 10,
            shadowOffsetX: 0,
            shadowColor: 'rgba(0, 0, 0, 0.5)'
          }
        }
      }]
    })
  }
}

// 初始化图表
const initCharts = () => {
  nextTick(() => {
    const betAmountEl = document.getElementById('betAmountChart')
    const betStatusEl = document.getElementById('betStatusChart')
    
    if (betAmountEl) {
      betAmountChart = echarts.init(betAmountEl)
    }
    
    if (betStatusEl) {
      betStatusChart = echarts.init(betStatusEl)
    }
  })
}

// 选项卡切换
const handleTabClick = (tab: any) => {
  if (tab.name === 'betStats') {
    initCharts()
    loadBetStats()
  } else if (tab.name === 'userStats') {
    loadUserStats()
  } else if (tab.name === 'drawStats') {
    loadDrawStats()
  }
}

// 获取开奖状态类型
const getDrawStatusType = (status: string) => {
  const types: Record<string, string> = {
    'PENDING': 'warning',
    'DRAWN': 'success',
    'SETTLED': 'primary',
    'CANCELLED': 'danger'
  }
  return types[status] || 'info'
}

// 导出功能
const exportUserStats = () => {
  ElMessage.info('导出功能开发中...')
}

const exportDrawStats = () => {
  ElMessage.info('导出功能开发中...')
}

const exportBetStats = () => {
  ElMessage.info('导出功能开发中...')
}

onMounted(() => {
  loadOverviewStats()
  loadLotteryTypes()
  loadUserStats()
})
</script>

<style scoped lang="scss">
.stat-card {
  text-align: center;
  
  .el-statistic {
    --el-statistic-content-font-size: 24px;
  }
}

.stats-content {
  .filter-bar {
    padding: 20px;
    border-radius: 4px;
  }
  
  .text-success {
    color: #67c23a;
  }
  
  .text-danger {
    color: #f56c6c;
  }
}

.el-tabs {
  .el-tab-pane {
    min-height: 400px;
  }
}

</style>