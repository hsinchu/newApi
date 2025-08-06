<template>
	<view class="withdraw-log-container">
		
		<!-- 主体内容 -->
		<view class="content-wrapper">
			<!-- 状态筛选 -->
			<view class="filter-section">
				<scroll-view scroll-x class="status-filter">
					<view 
						v-for="(status, index) in statusList" 
						:key="index" 
						class="status-item" 
						:class="{ active: currentStatus === status.value }"
						@tap="onStatusChange(status.value)"
					>
						<text class="status-text">{{ status.label }}</text>
					</view>
				</scroll-view>
			</view>
			
			<!-- 空数据提示 -->
			<uv-empty v-if="withdrawList.length == 0 && !loading" mode="data" icon="/static/images/no-data.png" text="暂无提现记录"></uv-empty>
			
			<!-- 提现记录列表 -->
			<view class="list-section" v-if="withdrawList.length > 0">
				<scroll-view 
					scroll-y 
					class="scroll-list" 
					@scrolltolower="handleScrollToLower"
					lower-threshold="100"
				>
					<view v-for="(item, index) in withdrawList" :key="index" class="withdraw-item">
						<view class="withdraw-header">
							<view class="order-info">
								<text class="order-no">{{ item.orderNo }}</text>
								<text class="create-time">{{ item.createTime }}</text>
							</view>
							<view class="status-badge" :class="getStatusClass(item.status)">
								<text class="status-text">{{ item.statusName }}</text>
							</view>
						</view>
						
						<view class="withdraw-content">
							<view class="account-info">
								<text class="account-type">{{ getAccountTypeName(item.accountType) }}</text>
								<text class="account-name">{{ item.accountName }}</text>
								<text class="account-number">{{ item.accountNumber }}</text>
								<text v-if="item.bankName" class="bank-name">{{ item.bankName }}</text>
							</view>
							
							<view class="amount-info">
								<view class="amount-row">
									<text class="amount-label">提现金额：</text>
									<text class="amount-value">¥{{ item.amount }}</text>
								</view>
								<view class="amount-row" v-if="item.fee > 0">
									<text class="amount-label">手续费：</text>
									<text class="fee-value">¥{{ item.fee }}</text>
								</view>
								<view class="amount-row">
									<text class="amount-label">实际到账：</text>
									<text class="actual-amount">¥{{ item.actualAmount }}</text>
								</view>
							</view>
						</view>
						
						<!-- 备注信息 -->
						<view class="remark-section" v-if="item.remark || item.adminRemark">
							<view v-if="item.remark" class="remark-item">
								<text class="remark-label">申请备注：</text>
								<text class="remark-text">{{ item.remark }}</text>
							</view>
							<view v-if="item.adminRemark" class="remark-item">
								<text class="remark-label">处理备注：</text>
								<text class="remark-text">{{ item.adminRemark }}</text>
							</view>
						</view>
						
						<!-- 时间信息 -->
						<view class="time-section">
							<view v-if="item.auditTime" class="time-item">
								<text class="time-label">审核时间：</text>
								<text class="time-text">{{ item.auditTime }}</text>
							</view>
							<view v-if="item.completeTime" class="time-item">
								<text class="time-label">完成时间：</text>
								<text class="time-text">{{ item.completeTime }}</text>
							</view>
						</view>
					</view>
					
					<!-- 加载更多组件 -->
					<uv-load-more 
						v-if="withdrawList.length > 0" 
						:status="loadStatus" 
						:loadingText="'正在加载...'"
						:loadmoreText="'上拉加载更多'"
						:nomoreText="'没有更多了'"
					/>
				</scroll-view>
			</view>
		</view>
	</view>
</template>

<script>
import { getWithdrawRecordList } from '@/api/charge.js'

export default {
	data() {
		return {
			// 提现记录列表
			withdrawList: [],
			
			// 分页参数
			page: 1,
			pageSize: 10,
			loadStatus: 'loadmore', // loadmore, loading, nomore
			loading: false,
			
			// 状态筛选
			currentStatus: '',
			statusList: [
				{ label: '全部', value: '' },
				{ label: '待审核', value: 0 },
				{ label: '已通过', value: 1 },
				{ label: '已拒绝', value: 2 },
				{ label: '已完成', value: 3 },
			]
		}
	},
	
	onLoad() {
		this.initData()
	},
	
	// 下拉刷新
	onPullDownRefresh() {
		this.refreshData()
	},
	
	methods: {
		// 初始化数据
		async initData() {
			this.loading = true
			this.page = 1
			this.withdrawList = []
			await this.loadWithdrawList()
			this.loading = false
		},
		
		// 刷新数据
		async refreshData() {
			this.page = 1
			this.withdrawList = []
			await this.loadWithdrawList()
			uni.stopPullDownRefresh()
		},
		
		// 加载提现记录
		async loadWithdrawList() {
			try {
				const params = {
					page: this.page,
					limit: this.pageSize
				}
				
				// 添加状态过滤
				if (this.currentStatus !== '') {
					params.status = this.currentStatus
				}
				
				const res = await getWithdrawRecordList(params)
				if (res.code === 1) {
					const newList = res.data.list || []
					if (this.page === 1) {
						this.withdrawList = newList
					} else {
						this.withdrawList.push(...newList)
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
				console.error('加载提现记录失败:', error)
				uni.showToast({
					title: '网络错误',
					icon: 'none'
				})
			}
		},
		
		// 处理scroll-view滚动到底部
		handleScrollToLower() {
			if (this.loadStatus !== 'nomore' && !this.loading) {
				this.loadMore()
			}
		},
		
		// 加载更多
		async loadMore() {
			if (this.loadStatus === 'nomore' || this.loading) {
				return
			}
			
			this.loadStatus = 'loading'
			this.loading = true
			this.page++
			
			await this.loadWithdrawList()
			
			this.loading = false
		},
		
		// 状态筛选切换
		async onStatusChange(status) {
			if (this.currentStatus === status) return
			
			this.currentStatus = status
			this.page = 1
			this.withdrawList = []
			this.loadStatus = 'loadmore'
			await this.loadWithdrawList()
		},
		
		// 获取账户类型名称
		getAccountTypeName(type) {
			const typeMap = {
				'alipay': '支付宝',
				'wechat': '微信',
				'bank': '银行卡'
			}
			return typeMap[type] || '未知'
		},
		
		// 获取状态样式类
		getStatusClass(status) {
			const statusMap = {
				0: 'status-pending',
				1: 'status-approved',
				2: 'status-rejected',
				3: 'status-completed',
				4: 'status-cancelled'
			}
			return statusMap[status] || 'status-pending'
		}
	}
}
</script>

<style lang="scss" scoped>
.withdraw-log-container {
	background-color: #0a0a0a;
	min-height: 100vh;
}

.content-wrapper {
	padding: 20rpx;
}

// 状态筛选
.filter-section {
	margin-bottom: 20rpx;
}

.status-filter {
	white-space: nowrap;
	padding: 10rpx 15rpx;
}

.status-item {
	display: inline-block;
	padding: 8rpx 15rpx;
	margin-right: 20rpx;
	background-color: #2a2a2a;
	border-radius: 25rpx;
	border: 2rpx solid transparent;
	transition: all 0.3s;
	
	&.active {
		background-color: #fd4300;
		border-color: #fd4300;
		
		.status-text {
			color: #ffffff;
		}
	}
}

.status-text {
	font-size: 28rpx;
	color: #cccccc;
	transition: color 0.3s;
}

// 列表样式
.list-section {
	height: calc(100vh - 200rpx);
}

.scroll-list {
	height: 100%;
}

.withdraw-item {
	background-color: #1a1a1a;
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 20rpx;
	border: 2rpx solid #2a2a2a;
}

.withdraw-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 20rpx;
}

.order-info {
	flex: 1;
}

.order-no {
	font-size: 32rpx;
	color: #ffffff;
	font-weight: 600;
	display: block;
	margin-bottom: 8rpx;
}

.create-time {
	font-size: 24rpx;
	color: #888888;
}

.status-badge {
	padding: 8rpx 16rpx;
	border-radius: 12rpx;
	font-size: 24rpx;
	
	&.status-pending {
		background-color: rgba(255, 193, 7, 0.2);
		color: #ffc107;
	}
	
	&.status-approved {
		background-color: rgba(0, 123, 255, 0.2);
		color: #007bff;
	}
	
	&.status-rejected {
		background-color: rgba(220, 53, 69, 0.2);
		color: #dc3545;
	}
	
	&.status-completed {
		background-color: rgba(40, 167, 69, 0.2);
		color: #28a745;
	}
	
	&.status-cancelled {
		background-color: rgba(108, 117, 125, 0.2);
		color: #6c757d;
	}
}

.withdraw-content {
	display: flex;
	justify-content: space-between;
	margin-bottom: 20rpx;
}

.account-info {
	flex: 1;
	margin-right: 20rpx;
}

.account-type {
	font-size: 28rpx;
	color: #fd4300;
	font-weight: 600;
	display: block;
	margin-bottom: 8rpx;
}

.account-name {
	font-size: 26rpx;
	color: #ffffff;
	display: block;
	margin-bottom: 6rpx;
}

.account-number {
	font-size: 24rpx;
	color: #cccccc;
	display: block;
	margin-bottom: 6rpx;
}

.bank-name {
	font-size: 24rpx;
	color: #888888;
	display: block;
}

.amount-info {
	text-align: right;
}

.amount-row {
	display: flex;
	justify-content: flex-end;
	align-items: center;
	margin-bottom: 8rpx;
}

.amount-label {
	font-size: 24rpx;
	color: #888888;
	margin-right: 10rpx;
}

.amount-value {
	font-size: 28rpx;
	color: #ffffff;
	font-weight: 600;
}

.fee-value {
	font-size: 24rpx;
	color: #ff6b6b;
}

.actual-amount {
	font-size: 30rpx;
	color: #fd4300;
	font-weight: 700;
}

// 备注样式
.remark-section {
	padding: 16rpx;
	background-color: #2a2a2a;
	border-radius: 12rpx;
	margin-bottom: 16rpx;
}

.remark-item {
	margin-bottom: 8rpx;
	
	&:last-child {
		margin-bottom: 0;
	}
}

.remark-label {
	font-size: 24rpx;
	color: #888888;
	margin-right: 10rpx;
}

.remark-text {
	font-size: 26rpx;
	color: #cccccc;
	line-height: 1.4;
}

// 时间样式
.time-section {
	padding-top: 16rpx;
	border-top: 2rpx solid #2a2a2a;
}

.time-item {
	display: flex;
	justify-content: space-between;
	margin-bottom: 8rpx;
	
	&:last-child {
		margin-bottom: 0;
	}
}

.time-label {
	font-size: 24rpx;
	color: #888888;
}

.time-text {
	font-size: 24rpx;
	color: #cccccc;
}
</style>