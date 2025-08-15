<template>
    <div class="default-main">
        <div class="banner">
            <el-row :gutter="10">
                <el-col :md="24" :lg="18">
                    <div class="welcome suspension">
                        <img class="welcome-img" :src="headerSvg" alt="" />
                        <div class="welcome-text">
                            <div class="welcome-title">{{ adminInfo.nickname + t('utils.comma') + getGreet() }}</div>
                        </div>
                    </div>
                </el-col>
                <el-col :lg="6" class="hidden-md-and-down">
                    <div class="working">
                        <img class="working-coffee" :src="coffeeSvg" alt="" />
                        <div class="working-text">
                            今日已工作<span class="time">{{ state.workingTimeFormat }}</span>
                        </div>
                        <div @click="onChangeWorkState()" class="working-opt working-rest">
                            {{ state.pauseWork ? '继续工作' : '休息一下' }}
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
        <div class="small-panel-box">
            <el-row :gutter="20">
                <el-col :sm="12" :lg="6">
                    <div class="small-panel user-reg suspension">
                        <div class="small-panel-title">会员今日注册</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#8595F4" size="20" name="fa fa-line-chart" />
                                <el-statistic :value="userRegNumberOutput" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+14%</div> -->
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel bet suspension">
                        <div class="small-panel-title">会员今日投注量</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#AD85F4" size="20" name="fa fa-money" />
                                <el-statistic :value="betNumberOutput" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+50%</div> -->
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel users suspension">
                        <div class="small-panel-title">会员总数</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#74A8B5" size="20" name="fa fa-users" />
                                <el-statistic :value="usersNumberOutput" :value-style="statisticValueStyle" />
                            </div>
                            <!-- <div class="content-right">+28%</div> -->
                        </div>
                    </div>
                </el-col>
                <el-col :sm="12" :lg="6">
                    <div class="small-panel lottery suspension">
                        <div class="small-panel-title">已开启彩种</div>
                        <div class="small-panel-content">
                            <div class="content-left">
                                <Icon color="#F48595" size="20" name="fa fa-trophy" />
                                <el-statistic :value="lotteryNumberOutput" :value-style="statisticValueStyle" />
                            </div>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
        <div class="growth-chart">
            <el-row :gutter="20">
                <el-col class="lg-mb-20" :xs="24" :sm="24" :md="12" :lg="9">
                    <el-card shadow="hover" header="会员增长">
                        <div class="user-growth-chart" :ref="chartRefs.set"></div>
                    </el-card>
                </el-col>
                <el-col class="lg-mb-20" :xs="24" :sm="24" :md="12" :lg="9">
                    <el-card shadow="hover" header="投注增长">
                        <div class="bet-growth-chart" :ref="chartRefs.set"></div>
                    </el-card>
                </el-col>
                <el-col :xs="24" :sm="24" :md="24" :lg="6">
                    <el-card class="new-user-card" shadow="hover" header="新会员">
                        <div class="new-user-growth">
                            <el-scrollbar>
                                <div 
                                    v-for="member in (state.dashboardData?.newMembers || [])"
                                    :key="member.id"
                                    class="new-user-item"
                                >
                                    <img class="new-user-avatar" :src="member.avatar || fullUrl('/static/images/avatar.png')" alt="" />
                                    <div class="new-user-base">
                                        <div class="new-user-name">{{ member.username }}</div>
                                        <div class="new-user-time">{{ formatJoinTime(member.joinTime) }}加入我们</div>
                                    </div>
                                    <Icon class="new-user-arrow" color="#8595F4" name="fa fa-angle-right" />
                                </div>
                                <!-- 默认显示内容，当没有API数据时 -->
                                <div v-if="!state.dashboardData?.newMembers?.length" class="new-user-item">
                                    <img class="new-user-avatar" :src="fullUrl('/static/images/avatar.png')" alt="" />
                                    <div class="new-user-base">
                                        <div class="new-user-name">暂无新会员</div>
                                        <div class="new-user-time">等待数据加载...</div>
                                    </div>
                                    <Icon class="new-user-arrow" color="#8595F4" name="fa fa-angle-right" />
                                </div>
                            </el-scrollbar>
                        </div>
                    </el-card>
                </el-col>
            </el-row>
        </div>

        <div class="growth-chart">
            <el-row :gutter="20">
                <el-col class="lg-mb-20" :xs="24" :sm="24" :md="24" :lg="12">
                    <el-card shadow="hover" header="各彩种中奖情况">
                        <div class="lottery-win-chart" :ref="chartRefs.set"></div>
                    </el-card>
                </el-col>
                <el-col class="lg-mb-20" :xs="24" :sm="24" :md="24" :lg="12">
                    <el-card shadow="hover" header="各彩种投注数据">
                        <div class="lottery-bet-chart" :ref="chartRefs.set"></div>
                    </el-card>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useEventListener, useTemplateRefsList, useTransition } from '@vueuse/core'
import * as echarts from 'echarts'
import { CSSProperties, nextTick, onActivated, onBeforeMount, onMounted, onUnmounted, reactive, toRefs, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { index, getData } from '/@/api/backend/dashboard'
import coffeeSvg from '/@/assets/dashboard/coffee.svg'
import headerSvg from '/@/assets/dashboard/header-1.svg'
import { useAdminInfo } from '/@/stores/adminInfo'
import { WORKING_TIME } from '/@/stores/constant/cacheKey'
import { useNavTabs } from '/@/stores/navTabs'
import { fullUrl, getGreet } from '/@/utils/common'
import { Local } from '/@/utils/storage'
let workTimer: number

// 格式化会员加入时间
const formatJoinTime = (joinTime: string) => {
    if (!joinTime) return ''
    
    const joinDate = new Date(joinTime)
    const now = new Date()
    const diffMs = now.getTime() - joinDate.getTime()
    const diffMins = Math.floor(diffMs / (1000 * 60))
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
    
    if (diffMins < 60) {
        return `${diffMins}分钟前`
    } else if (diffHours < 24) {
        return `${diffHours}小时前`
    } else {
        return `${diffDays}天前`
    }
}

defineOptions({
    name: 'dashboard',
})

const d = new Date()
const { t } = useI18n()
const navTabs = useNavTabs()
const adminInfo = useAdminInfo()
const chartRefs = useTemplateRefsList<HTMLDivElement>()

const state: {
    charts: any[]
    remark: string
    workingTimeFormat: string
    pauseWork: boolean
    dashboardData: any
} = reactive({
    charts: [],
    remark: 'dashboard.Loading',
    workingTimeFormat: '',
    pauseWork: false,
    dashboardData: null,
})

/**
 * 带有数字向上变化特效的数据
 */
const countUp = reactive({
    userRegNumber: 0,
    betNumber: 0,
    usersNumber: 0,
    lotteryNumber: 0,
})

const countUpRefs = toRefs(countUp)
const userRegNumberOutput = useTransition(countUpRefs.userRegNumber, { duration: 1500 })
const betNumberOutput = useTransition(countUpRefs.betNumber, { duration: 1500 })
const usersNumberOutput = useTransition(countUpRefs.usersNumber, { duration: 1500 })
const lotteryNumberOutput = useTransition(countUpRefs.lotteryNumber, { duration: 1500 })
const statisticValueStyle: CSSProperties = {
    fontSize: '28px',
}

// 获取基础信息
index().then((res) => {
    state.remark = res.data.remark
})

// 获取dashboard数据
getData().then((res) => {
    state.dashboardData = res.data
    // 更新统计数据
    if (res.data.statistics) {
        countUpRefs.userRegNumber.value = res.data.statistics.userRegistrations || 0
        countUpRefs.betNumber.value = res.data.statistics.betAmount || 0
        countUpRefs.usersNumber.value = res.data.statistics.totalUsers || 0
        countUpRefs.lotteryNumber.value = res.data.statistics.lotteryTypes || 0
    }
    
    // 重新初始化图表
    nextTick(() => {
        initUserGrowthChart()
        initBetGrowthChart()
        initLotteryWinChart()
        initLotteryBetChart()
    })
})

const initCountUp = () => {
    // 默认数据，将通过API更新
    countUpRefs.userRegNumber.value = 0
    countUpRefs.betNumber.value = 0
    countUpRefs.usersNumber.value = 0
    countUpRefs.lotteryNumber.value = 0
}

const initUserGrowthChart = () => {
    const userGrowthChart = echarts.init(chartRefs.value[0] as HTMLElement)
    
    // 使用API数据或默认数据
    const chartData = state.dashboardData?.chartData?.userGrowth || {
        dates: ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
        visits: [100, 160, 280, 230, 190, 200, 480],
        registrations: [45, 180, 146, 99, 210, 127, 288]
    }
    
    const option = {
        grid: {
            top: 0,
            right: 0,
            bottom: 20,
            left: 0,
        },
        xAxis: {
            data: chartData.dates,
        },
        yAxis: {},
        legend: {
            data: ['访问量', '注册量'],
            textStyle: {
                color: '#73767a',
            },
        },
        series: [
            {
                name: '访问量',
                data: chartData.visits,
                type: 'line',
                smooth: true,
                areaStyle: {
                    color: '#8595F4',
                },
            },
            {
                name: '注册量',
                data: chartData.registrations,
                type: 'line',
                smooth: true,
                areaStyle: {
                    color: '#F48595',
                    opacity: 0.5,
                },
            },
        ],
    }
    userGrowthChart.setOption(option)
    state.charts.push(userGrowthChart)
}

const initBetGrowthChart = () => {
    const betGrowthChart = echarts.init(chartRefs.value[1] as HTMLElement)
    
    // 使用API数据
    const radarData = state.dashboardData?.chartData?.radarData || {
        indicators: [{ name: '暂无数据', max: 100 }],
        data: [0]
    }
    
    const option = {
        tooltip: {
            trigger: 'item'
        },
        radar: {
            indicator: radarData.indicators,
            radius: '70%'
        },
        series: [{
            name: '投注类型分析',
            type: 'radar',
            data: [{
                value: radarData.data,
                name: '投注数据',
                areaStyle: {
                    color: 'rgba(133, 149, 244, 0.3)'
                },
                lineStyle: {
                    color: '#8595F4',
                    width: 2
                },
                itemStyle: {
                    color: '#8595F4'
                }
            }]
        }]
    }
    betGrowthChart.setOption(option)
    state.charts.push(betGrowthChart)
}

const initLotteryWinChart = () => {
    const lotteryWinChart = echarts.init(chartRefs.value[2] as HTMLElement)
    
    // 使用API数据
    const winData = state.dashboardData?.chartData?.lotteryWinData || [
        { name: '暂无数据', value: 0 }
    ]
    
    const option = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            },
            formatter: function (params: any) {
                return params[0].name + ': ' + params[0].value + '元'
            }
        },
        xAxis: {
            type: 'category',
            data: winData.map(item => item.name),
            axisTick: { show: false },
            axisLine: { 
                lineStyle: {
                    color: '#e0e0e0'
                }
            },
            axisLabel: {
                color: '#666'
            }
        },
        yAxis: {
            type: 'value',
            splitLine: { 
                lineStyle: {
                    color: '#f0f0f0'
                }
            },
            axisTick: { show: false },
            axisLine: { show: false },
            axisLabel: {
                color: '#666'
            }
        },
        series: [{
            name: '中奖金额',
            type: 'bar',
            data: winData.map(item => item.value),
            itemStyle: {
                color: '#8595F4',
                borderRadius: [4, 4, 0, 0]
            },
            emphasis: {
                itemStyle: {
                    color: '#6B7EE8'
                }
            }
        }]
    }
    lotteryWinChart.setOption(option)
    state.charts.push(lotteryWinChart)
}

const initLotteryBetChart = () => {
    const lotteryBetChart = echarts.init(chartRefs.value[3] as HTMLElement)
    
    // 使用API数据
    const betData = state.dashboardData?.chartData?.lotteryBetData || [
        { name: '暂无数据', value: 0 }
    ]
    
    const option = {
        tooltip: {
            trigger: 'item',
            formatter: '{a} <br/>{b} : {c} ({d}%)',
        },
        legend: {
            type: 'scroll',
            orient: 'vertical',
            right: 10,
            top: 20,
            bottom: 20,
            data: betData.map(item => item.name),
            textStyle: {
                color: '#73767a',
            },
        },
        series: [
            {
                name: '彩种投注',
                type: 'pie',
                radius: '55%',
                center: ['40%', '50%'],
                data: betData,
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)',
                    },
                },
            },
        ],
    }

    lotteryBetChart.setOption(option)
    state.charts.push(lotteryBetChart)
}

const echartsResize = () => {
    nextTick(() => {
        for (const key in state.charts) {
            state.charts[key].resize()
        }
    })
}

const onChangeWorkState = () => {
    const time = parseInt((new Date().getTime() / 1000).toString())
    const workingTime = Local.get(WORKING_TIME)
    if (state.pauseWork) {
        // 继续工作
        workingTime.pauseTime += time - workingTime.startPauseTime
        workingTime.startPauseTime = 0
        Local.set(WORKING_TIME, workingTime)
        state.pauseWork = false
        startWork()
    } else {
        // 暂停工作
        workingTime.startPauseTime = time
        Local.set(WORKING_TIME, workingTime)
        clearInterval(workTimer)
        state.pauseWork = true
    }
}

const startWork = () => {
    const workingTime = Local.get(WORKING_TIME) || { date: '', startTime: 0, pauseTime: 0, startPauseTime: 0 }
    const currentDate = d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate()
    const time = parseInt((new Date().getTime() / 1000).toString())

    if (workingTime.date != currentDate) {
        workingTime.date = currentDate
        workingTime.startTime = time
        workingTime.pauseTime = workingTime.startPauseTime = 0
        Local.set(WORKING_TIME, workingTime)
    }

    let startPauseTime = 0
    if (workingTime.startPauseTime <= 0) {
        state.pauseWork = false
        startPauseTime = 0
    } else {
        state.pauseWork = true
        startPauseTime = time - workingTime.startPauseTime // 已暂停时间
    }

    let workingSeconds = time - workingTime.startTime - workingTime.pauseTime - startPauseTime

    state.workingTimeFormat = formatSeconds(workingSeconds)
    if (!state.pauseWork) {
        workTimer = window.setInterval(() => {
            workingSeconds++
            state.workingTimeFormat = formatSeconds(workingSeconds)
        }, 1000)
    }
}

const formatSeconds = (seconds: number) => {
    var secondTime = 0 // 秒
    var minuteTime = 0 // 分
    var hourTime = 0 // 小时
    var dayTime = 0 // 天
    var result = ''

    if (seconds < 60) {
        secondTime = seconds
    } else {
        // 获取分钟，除以60取整数，得到整数分钟
        minuteTime = Math.floor(seconds / 60)
        // 获取秒数，秒数取佘，得到整数秒数
        secondTime = Math.floor(seconds % 60)
        // 如果分钟大于60，将分钟转换成小时
        if (minuteTime >= 60) {
            // 获取小时，获取分钟除以60，得到整数小时
            hourTime = Math.floor(minuteTime / 60)
            // 获取小时后取佘的分，获取分钟除以60取佘的分
            minuteTime = Math.floor(minuteTime % 60)
            if (hourTime >= 24) {
                // 获取天数， 获取小时除以24，得到整数天
                dayTime = Math.floor(hourTime / 24)
                // 获取小时后取余小时，获取分钟除以24取余的分；
                hourTime = Math.floor(hourTime % 24)
            }
        }
    }

    result =
        hourTime +
        '小时' +
        ((minuteTime >= 10 ? minuteTime : '0' + minuteTime) + '分钟') +
        ((secondTime >= 10 ? secondTime : '0' + secondTime) + '秒')
    if (dayTime > 0) {
        result = dayTime + '天' + result
    }
    return result
}

onActivated(() => {
    echartsResize()
})

onMounted(() => {
    startWork()
    initCountUp()
    initUserGrowthChart()
    initBetGrowthChart()
    initLotteryWinChart()
    initLotteryBetChart()
    useEventListener(window, 'resize', echartsResize)
})

onBeforeMount(() => {
    for (const key in state.charts) {
        state.charts[key].dispose()
    }
})

onUnmounted(() => {
    clearInterval(workTimer)
})

watch(
    () => navTabs.state.tabFullScreen,
    () => {
        echartsResize()
    }
)
</script>

<style scoped lang="scss">
.welcome {
    background: #e1eaf9;
    border-radius: 6px;
    display: flex;
    align-items: center;
    padding: 15px 20px !important;
    box-shadow: 0 0 30px 0 rgba(82, 63, 105, 0.05);
    .welcome-img {
        height: 100px;
        margin-right: 10px;
        user-select: none;
    }
    .welcome-title {
        font-size: 1.5rem;
        line-height: 30px;
        color: var(--ba-color-primary-light);
    }
    .welcome-note {
        padding-top: 6px;
        font-size: 15px;
        color: var(--el-text-color-primary);
    }
}
.working {
    height: 130px;
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    height: 100%;
    position: relative;
    &:hover {
        .working-coffee {
            -webkit-transform: translateY(-4px) scale(1.02);
            -moz-transform: translateY(-4px) scale(1.02);
            -ms-transform: translateY(-4px) scale(1.02);
            -o-transform: translateY(-4px) scale(1.02);
            transform: translateY(-4px) scale(1.02);
            z-index: 999;
        }
    }
    .working-coffee {
        transition: all 0.3s ease;
        width: 80px;
    }
    .working-text {
        display: block;
        width: 100%;
        font-size: 15px;
        text-align: center;
        color: var(--el-text-color-primary);
    }
    .working-opt {
        position: absolute;
        top: -40px;
        right: 10px;
        background-color: rgba($color: #000000, $alpha: 0.3);
        padding: 10px 20px;
        border-radius: 20px;
        color: var(--ba-bg-color-overlay);
        transition: all 0.3s ease;
        cursor: pointer;
        opacity: 0;
        z-index: 999;
        &:active {
            background-color: rgba($color: #000000, $alpha: 0.6);
        }
    }
    &:hover {
        .working-opt {
            opacity: 1;
            top: 0;
        }
        .working-done {
            opacity: 1;
            top: 50px;
        }
    }
}
.small-panel-box {
    margin-top: 20px;
}
.small-panel {
    background-color: #e9edf2;
    border-radius: var(--el-border-radius-base);
    padding: 25px;
    margin-bottom: 20px;
    .small-panel-title {
        color: #92969a;
        font-size: 15px;
    }
    .small-panel-content {
        display: flex;
        align-items: flex-end;
        margin-top: 20px;
        color: #2c3f5d;
        .content-left {
            display: flex;
            align-items: center;
            font-size: 24px;
            .icon {
                margin-right: 10px;
            }
        }
        .content-right {
            font-size: 18px;
            margin-left: auto;
        }
        .color-success {
            color: var(--el-color-success);
        }
        .color-warning {
            color: var(--el-color-warning);
        }
        .color-danger {
            color: var(--el-color-danger);
        }
        .color-info {
            color: var(--el-text-color-secondary);
        }
    }
}
.growth-chart {
    margin-bottom: 20px;
}
.user-growth-chart,
.bet-growth-chart {
    height: 260px;
}
.new-user-growth {
    height: 300px;
}

.lottery-win-chart,
.lottery-bet-chart {
    height: 400px;
}
.new-user-item {
    display: flex;
    align-items: center;
    padding: 20px;
    margin: 10px 15px;
    box-shadow: 0 0 30px 0 rgba(82, 63, 105, 0.05);
    background-color: var(--ba-bg-color-overlay);
    .new-user-avatar {
        height: 48px;
        width: 48px;
        border-radius: 50%;
    }
    .new-user-base {
        margin-left: 10px;
        color: #2c3f5d;
        .new-user-name {
            font-size: 15px;
        }
        .new-user-time {
            font-size: 13px;
        }
    }
    .new-user-arrow {
        margin-left: auto;
    }
}
.new-user-card :deep(.el-card__body) {
    padding: 0;
}

@media screen and (max-width: 425px) {
    .welcome-img {
        display: none;
    }
}
@media screen and (max-width: 1200px) {
    .lg-mb-20 {
        margin-bottom: 20px;
    }
}
html.dark {
    .welcome {
        background-color: var(--ba-bg-color-overlay);
    }
    .working-opt {
        color: var(--el-text-color-primary);
        background-color: var(--ba-border-color);
    }
    .small-panel {
        background-color: var(--ba-bg-color-overlay);
        .small-panel-content {
            color: var(--el-text-color-regular);
        }
    }
    .new-user-item {
        .new-user-base {
            color: var(--el-text-color-regular);
        }
    }
}
</style>
