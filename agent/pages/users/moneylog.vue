<template>
	<view class="money-log-container">
		<!-- 主体内容 -->
		<view class="content-wrapper">
			<uv-vtabs 
				:list="categoryList" 
				:current="currentCategory" 
				@change="onCategoryChange"
				:chain="false"
				barWidth="200rpx"
				barBgColor="#f8f9fa"
				:barItemStyle="barItemStyle"
				:barItemActiveStyle="barItemActiveStyle"
				:contentStyle="contentStyle"
			>
				<uv-vtabs-item>
					<!-- 右侧内容区域 -->
					<view class="right-content">
						<!-- 固定在顶部的统计信息 -->
						<view class="fixed-header">
							<view class="date-header">
								<text class="date-title">统计：{{ formatAmount(totalIncome - totalExpense) }}</text>
								<view class="date-selector" @click="openDatePicker">
									<text class="date-text">{{ dateRangeText }}</text>
									<uv-icon name="calendar" color="#fd4300" size="16"></uv-icon>
								</view>
							</view>
						</view>
						<uv-empty v-if="moneyLogList.length == 0" mode="data" icon="/static/images/no-data.png"></uv-empty>
						<!-- 变动列表 -->
						<view class="list-section">
							<scroll-view 
							scroll-y 
							class="scroll-list" 
							@scrolltolower="handleScrollToLower"
							lower-threshold="50"
							>
								<view v-for="(item, index) in moneyLogList" :key="index" class="log-item">
									<view class="log-info">
										<text class="log-title">{{ item.remark || getLogTypeName(item.type) }}</text>
										<text class="log-time">{{ formatTime(item.createtime) }}</text>
									</view>
									<text class="log-amount" :class="item.amount >= 0 ? 'income' : 'expense'">
										{{ item.amount >= 0 ? '+' : '' }}{{ formatAmount(Math.abs(item.amount)) }}
									</text>
								</view>
								<!-- 加载更多组件 -->
								<uv-load-more 
									v-if="moneyLogList.length > 0" 
									:status="loadStatus" 
									loadmore-text="上拉加载更多"
									loading-text="正在加载..."
									nomore-text="没有更多了"
									color="#999"
									margin-top="20"
									margin-bottom="20"
								></uv-load-more>
							</scroll-view>
						</view>
					</view>
				</uv-vtabs-item>
			</uv-vtabs>
		</view>
		
		<!-- 日期选择器 -->
		<uv-calendars 
			ref="calendar"
			:insert="false"
			mode="range"
			:date="selectedDateRange"
			@confirm="onDateConfirm"
			@close="onDateClose"
			color="#fd4300"
			confirmColor="#fd4300"
			startText="开始"
			endText="结束"
			:allowSameDay="true"
			:startDate="getMinDate()"
			:endDate="getMaxDate()"
		></uv-calendars>
	</view>
</template>

<script>
	import { getMoneyLog } from '@/api/agent.js'
	import common from '@/api/common.js'
	import { MONEY_LOG_TYPES, getTypeName, getTypeIcon, getTypeClass } from '@/utils/moneyLogTypes.js'
	export default {
		data() {
			return {
			// 分类列表
			categoryList: MONEY_LOG_TYPES.CATEGORY_LIST,
			currentCategory: 0,
			
			// 日期相关
			selectedDateRange: [],
			dateRangeText: '',
			
			// 统计数据
			totalIncome: 0,
			totalExpense: 0,
			
			// 列表数据
			moneyLogList: [],
			page: 1,
			pageSize: 15,
			loading: false,
			loadStatus: 'loadmore', // loadmore | loading | nomore
			
			// 样式配置
			barItemStyle: {
				backgroundColor: 'transparent',
				color: '#666',
				fontSize: '28rpx',
				textAlign: 'center',
				padding: '12rpx 10rpx'
			},
			barItemActiveStyle: {
				backgroundColor: '#3c9cff',
				color: '#fff',
				fontSize: '28rpx',
				textAlign: 'center',
				padding: '12rpx 10rpx'
			},
			contentStyle: {
				backgroundColor: '#f7f7f7',
			}
		}
	},
	computed: {
		// 净变动
		netChange() {
			return this.totalIncome - this.totalExpense
		}
	},
	onLoad() {
		this.initData()
	},
	onPullDownRefresh() {
		this.refreshData()
	},
	
	// 页面滚动到底部时触发
	onReachBottom() {
		console.log('onReachBottom触发，当前状态:', this.loadStatus, '是否正在加载:', this.loading)
		if (this.loadStatus !== 'nomore' && !this.loading) {
			this.loadMore()
		}
	},
	methods: {
		// 初始化数据
	async initData() {
		// 设置默认日期为今天
		this.setDefaultDate()
		this.loading = true
		this.page = 1
		this.moneyLogList = []
		await this.loadMoneyLog()
		this.loading = false
	},
	
	// 设置默认日期为今天
	setDefaultDate() {
		const today = new Date()
		const year = today.getFullYear()
		const month = String(today.getMonth() + 1).padStart(2, '0')
		const day = String(today.getDate()).padStart(2, '0')
		const todayStr = `${year}-${month}-${day}`
		
		this.selectedDateRange = [todayStr, todayStr]
		// 显示简化的月-日格式
		this.dateRangeText = `${month}-${day} 至 ${month}-${day}`
	},
		
		// 刷新数据
		async refreshData() {
			this.page = 1
			this.moneyLogList = []
			await this.loadMoneyLog()
			uni.stopPullDownRefresh()
		},
		
		// 加载资金记录
		async loadMoneyLog() {
			try {
				const params = {
					page: this.page,
					limit: this.pageSize
				}
				
				// 添加分类过滤
				if (this.currentCategory > 0) {
					params.type = this.getTypeByCategory(this.currentCategory)
				}
				
				// 添加日期过滤
				if (this.selectedDateRange.length === 2) {
					params.start_date = this.selectedDateRange[0]
					params.end_date = this.selectedDateRange[1]
				}
				
				const res = await getMoneyLog(params)
			if (res.code === 1) {
				const newList = res.data.data || []
				if (this.page === 1) {
					this.moneyLogList = newList
					// 更新统计数据（使用后端返回的统计数据）
					if (res.data.statistics) {
						this.totalIncome = res.data.statistics.total_income || 0
						this.totalExpense = res.data.statistics.total_expense || 0
					}
				} else {
					this.moneyLogList.push(...newList)
				}
				
				// 更新加载状态
				if (newList.length < this.pageSize) {
					this.loadStatus = 'nomore'
					} else {
						this.loadStatus = 'loadmore'
					}
				} else {
					uni.showToast({
						title: res.msg || '加载失败',
						icon: 'none'
					})
				}
			} catch (error) {
				console.error('加载资金记录失败:', error)
				uni.showToast({
					title: '网络错误',
					icon: 'none'
				})
			}
		},
		
		// 处理scroll-view滚动到底部
		handleScrollToLower() {
			console.log('scroll-view滚动到底部，当前状态:', this.loadStatus, '是否正在加载:', this.loading)
			if (this.loadStatus !== 'nomore' && !this.loading) {
				this.loadMore()
			}
		},
		
		// 加载更多
		async loadMore() {
			console.log('触发加载更多，当前状态:', this.loadStatus, '是否正在加载:', this.loading)
			if (this.loadStatus === 'nomore' || this.loading) {
				console.log('阻止加载更多：', { loadStatus: this.loadStatus, loading: this.loading })
				return
			}
			
			this.loading = true
			this.loadStatus = 'loading'
			this.page++
			console.log('开始加载第', this.page, '页')
			try {
				await this.loadMoneyLog()
			} catch (error) {
				console.error('加载更多失败:', error)
				// 回滚页码
				this.page--
				// 重置状态
				this.loadStatus = 'loadmore'
			} finally {
				this.loading = false
			}
		},
		

		
		// 分类切换
		async onCategoryChange(index) {
			this.currentCategory = index
			// 重新加载数据以获取新的统计信息
			this.page = 1
			this.moneyLogList = []
			await this.loadMoneyLog()
		},
		
		// 打开日期选择器
		openDatePicker() {
			this.$refs.calendar.open()
		},
		
		// 日期确认
		async onDateConfirm(e) {
			console.log('日期选择结果:', JSON.stringify(e))
			let startDate, endDate
			
			startDate = e.range.before
			endDate = e.range.after
			
			if (startDate && endDate) {
				this.selectedDateRange = [startDate, endDate]
				
				// 格式化日期显示（只显示月-日）
				const formatDateShort = (dateStr) => {
					const date = new Date(dateStr)
					const month = String(date.getMonth() + 1).padStart(2, '0')
					const day = String(date.getDate()).padStart(2, '0')
					return `${month}-${day}`
				}
				
				const startShort = formatDateShort(startDate)
				const endShort = formatDateShort(endDate)
				this.dateRangeText = `${startShort} 至 ${endShort}`
				
				// 重新加载数据
				this.page = 1
				this.moneyLogList = []
				await this.loadMoneyLog()
			} else {
				uni.showToast({
					title: '日期选择失败',
					icon: 'none'
				})
			}
		},
		
		// 日期关闭
		onDateClose() {
			// 日期选择器关闭
		},
		
		// 根据分类获取类型
		getTypeByCategory(categoryIndex) {
			if (categoryIndex === 0 || !this.categoryList[categoryIndex]) {
				return ''
			}
			return this.categoryList[categoryIndex].value
		},
		
		// 获取日志类型名称
		getLogTypeName(type) {
			return getTypeName(type)
		},
		
		// 获取日志类型图标
		getLogTypeIcon(type) {
			return getTypeIcon(type)
		},
		
		// 获取日志类型样式类
		getLogTypeClass(type) {
			return getTypeClass(type)
		},
		
		// 格式化金额
		formatAmount(amount) {
			return Number(amount).toFixed(2)
		},
		
		// 格式化时间
		formatTime(time) {
			return common.formatTime(time)
		},
		
		// 获取最大日期（今天）
		getMaxDate() {
			const today = new Date()
			const year = today.getFullYear()
			const month = String(today.getMonth() + 1).padStart(2, '0')
			const day = String(today.getDate()).padStart(2, '0')
			return `${year}-${month}-${day}`
		},
		
		// 获取最小日期（今天之前30天）
		getMinDate() {
			const today = new Date()
			const minDate = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)
			const year = minDate.getFullYear()
			const month = String(minDate.getMonth() + 1).padStart(2, '0')
			const day = String(minDate.getDate()).padStart(2, '0')
			return `${year}-${month}-${day}`
		},
		
		// 返回上一页
		goBack() {
			uni.navigateBack()
		}
	}
}
</script>

<style lang="scss" scoped>
.money-log-container {
	background-color: #f8f9fa;
}

.right-content {
	height: 100vh;
	display: flex;
	flex-direction: column;
	overflow: hidden;
}

.fixed-header {
	position: sticky;
	top: 0;
	z-index: 100;
	background-color: #fff;
}

.date-header {
	display: flex;
	padding: 15rpx 25rpx;
	justify-content: space-between;
	align-items: center;
	border-radius: 10rpx;
}

.date-title {
	font-size: 25rpx;
	color: #8a9eff;
	font-weight: 500;
}

.date-selector {
	display: flex;
	align-items: center;
	gap: 10rpx;
	padding: 10rpx 15rpx;
	background-color: rgba(0, 122, 255, 0.1);
	border-radius: 25rpx;
	border: 1rpx solid #fd4300;
}

.date-text {
	font-size: 26rpx;
	color: #fd4300;
}

// 列表区域
.list-section {
	flex: 1;
	padding: 0 15rpx 20rpx;
	padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
	height: 0;
}

.scroll-list {
	height: 100%;
	width: 100%;
}



.log-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx;
	background-color: #fff;
	margin: 10rpx 0;
	border-radius: 35rpx 0 35rpx 0;
	border: 1px solid #e9ecef;
}

.log-info {
	display: flex;
	flex-direction: column;
	gap: 6rpx;
	flex: 1;
}

.log-title {
	font-size: 25rpx;
	color: #333;
	font-weight: 400;
}

.log-time {
	font-size: 20rpx;
	color: #666;
	opacity: 0.8;
}

.log-amount {
	font-size: 25rpx;
	font-weight: 500;
	
	&.income {
		color: #00C851;
	}
	
	&.expense {
		color: #FF6B6B;
	}
}
</style>