<template>
	<view class="container">		
		<!-- 日期选择区域 -->
		<view class="date-selector">
			<uv-icon name="calendar" color="orangered" size="20"></uv-icon>
			<view class="date-range-picker" @tap="showDatePicker">
				<text class="date-text">{{dateRange}}</text>
				<uv-icon name="arrow-down" color="#999" size="14"></uv-icon>
			</view>
		</view>
		
		<!-- 日历组件 -->
		<uv-calendar 
			ref="calendar"
			mode="range"
			:maxRange="30"
			:color="'#ff4500'"
            :showTitle="false"
			:defaultDate="defaultDate"
			:maxDate="formatDate(new Date())"
			:minDate="formatDate(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000))"
			:closeOnClickOverlay="true"
			@confirm="onDateConfirm"
			@close="onDateClose">
		</uv-calendar>
		
		<!-- 统计内容区域 -->
		<view class="scroll-container">
			<view class="statistics-content">
			<!-- 顶部统计卡片 -->
			<view class="top-card">
				<view class="card-item">
					<uv-count-to 
						:endVal="parseFloat(statisticsData.total_bet_amount)" 
						:decimals="2" 
						:duration="2000" 
						:fontSize="18" 
						color="orangered" 
						:bold="true"
						customStyle="margin-bottom: 8rpx">
					</uv-count-to>
					<text class="card-label">会员总投注</text>
				</view>
				<view class="card-divider"></view>
				<view class="card-item">
					<uv-count-to 
						:endVal="parseFloat(statisticsData.total_prize_amount)" 
						:decimals="2" 
						:duration="2000" 
						:fontSize="18" 
						color="orangered" 
						:bold="true"
						customStyle="margin-bottom: 8rpx">
					</uv-count-to>
					<text class="card-label">会员总中奖</text>
				</view>
			</view>
			
			<view class="recharge-section">
				<view class="recharge-item" @tap="goToRecharge">
					<uv-icon name="plus-circle" color="#FFA500" size="20"></uv-icon>
					<text class="recharge-text">会员充值总金额</text>
					<text class="recharge-amount red">{{statisticsData.member_recharge_amount}}</text>
					<uv-icon name="arrow-right" color="#999" size="14"></uv-icon>
				</view>
				<view class="recharge-item" @tap="goToWithdraw">
					<uv-icon name="minus-circle" color="#FFA500" size="20"></uv-icon>
					<text class="recharge-text">会员提现总金额</text>
					<text class="recharge-amount red">{{statisticsData.member_withdraw_amount}}</text>
					<uv-icon name="arrow-right" color="#999" size="14"></uv-icon>
				</view>
				<view class="recharge-item" @tap="goToWithdraw">
					<uv-icon name="minus-circle" color="#FFA500" size="20"></uv-icon>
					<text class="recharge-text">会员佣金总金额</text>
					<text class="recharge-amount red">{{statisticsData.member_commission_amount}}</text>
					<uv-icon name="arrow-right" color="#999" size="14"></uv-icon>
				</view>
			</view>
			
			<!-- 加扣款统计 -->
			<view class="adjustment-section">
				<view class="adjustment-row">
					<view class="adjustment-item">
						<text class="adjustment-label">新增会员</text>
						<text class="adjustment-value orange">{{statisticsData.new_member_count}}</text>
					</view>
					<view class="adjustment-divider"></view>
					<view class="adjustment-item">
						<text class="adjustment-label">给会员加款金额</text>
						<text class="adjustment-value red">{{statisticsData.member_add_amount}}</text>
					</view>
					<view class="adjustment-divider"></view>
					<view class="adjustment-item">
						<text class="adjustment-label">给会员扣款金额</text>
						<text class="adjustment-value red">{{statisticsData.member_deduct_amount}}</text>
					</view>
				</view>
			</view>
			</view>
		</view>
    </view>
</template>

<script>
	import authMixin from '@/mixins/auth.js';
	import { getStatistics } from '@/api/agent.js';
	
	export default {
		mixins: [authMixin],
		data() {
			const today = new Date();
			const year = today.getFullYear();
			const month = String(today.getMonth() + 1).padStart(2, '0');
			const day = String(today.getDate()).padStart(2, '0');
			const todayStr = `${year}-${month}-${day}`;
			return {
				dateRange: `${todayStr} 至 ${todayStr}`,
				defaultDate: [todayStr, todayStr],
				loading: false,
				// 统计数据
				statisticsData: {
					total_bet_amount: 0, // 会员总投注
					total_prize_amount: 0, // 会员总中奖
					member_recharge_amount: 0, // 会员充值总金额
					member_withdraw_amount: 0, // 会员提现总金额
					member_commission_amount: 0, // 会员佣金总金额
					new_member_count: 0, // 新增会员
					member_add_amount: 0, // 给会员加款金额
					member_deduct_amount: 0 // 给会员扣款金额
				}
			}
		},
		mounted() {
			this.loadStatistics();
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 重新加载统计数据
			this.loadStatistics();
			// 延迟停止下拉刷新动画
			setTimeout(() => {
				uni.stopPullDownRefresh();
			}, 1000);
		},
		methods: {
			formatDate(date) {
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const day = String(date.getDate()).padStart(2, '0');
				return `${year}-${month}-${day}`;
			},
			showDatePicker() {
				this.$refs.calendar.open();
			},
			onDateConfirm(e) {
				console.log('选择的日期范围:', e);
				if (e && e.length >= 2) {
					const startDate = e[0];
					const endDate = e[e.length - 1];
					this.dateRange = `${startDate} 至 ${endDate}`;
					// 重新加载统计数据
					this.loadStatistics();
				} else if (e && e.length === 1) {
					this.dateRange = e[0];
					// 重新加载统计数据
					this.loadStatistics();
				}
			},
			onDateClose() {
				console.log('日历关闭');
			},
			goToRecharge() {
				console.log('跳转到充值页面');
				// 这里可以添加跳转逻辑
			},
			goToWithdraw() {
				console.log('跳转到提现页面');
				// 这里可以添加跳转逻辑
			},

			// 获取日期范围
			getDateRange() {
				const dateRangeParts = this.dateRange.split(' 至 ');
				if (dateRangeParts.length === 2) {
					return {
						start_date: dateRangeParts[0],
						end_date: dateRangeParts[1]
					};
				} else {
					// 单日期情况
					const singleDate = dateRangeParts[0];
					return {
						start_date: singleDate,
						end_date: singleDate
					};
				}
			},
			// 加载统计数据
			async loadStatistics() {
				if (this.loading) return;
				
				try {
					this.loading = true;
					const dateRange = this.getDateRange();
					const response = await getStatistics(dateRange);
					
					if (response.code === 1) {
					// 只处理HTML模板中实际使用的字段
					this.statisticsData = {
						total_bet_amount: this.formatNumber(response.data.total_bet_amount),
						total_prize_amount: this.formatNumber(response.data.total_prize_amount),
						member_recharge_amount: this.formatNumber(response.data.member_recharge_amount),
						member_withdraw_amount: this.formatNumber(response.data.member_withdraw_amount),
						member_commission_amount: this.formatNumber(response.data.member_commission_amount),
						new_member_count: parseInt(response.data.new_member_count) || 0,
						member_add_amount: this.formatNumber(response.data.memberAddAmount),
						member_deduct_amount: this.formatNumber(response.data.member_deduct_amount)
					};
					} else {
						uni.showToast({
							title: response.msg || '获取统计数据失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('获取统计数据失败:', error);
					uni.showToast({
							title: '网络错误，请重试',
							icon: 'none'
						});
				} finally {
					this.loading = false;
				}
			},
			// 格式化数字
			formatNumber(value) {
				const num = parseFloat(value) || 0;
				return num.toFixed(2);
			}
		}
    }
</script>
<style scoped lang="scss">
	
	.date-selector {
		display: flex;
		align-items: center;
		padding: 12rpx 20rpx;
		margin: 20rpx;
	}
	
	.date-range-picker {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 16rpx 20rpx;
		margin-left: 16rpx;
	}
	
	.date-text {
		font-size: 28rpx;
		color:#e1e1e1;
	}
	
	.statistics-content {
		padding: 0 20rpx 120rpx;
	}
	
	.top-card {
		background: linear-gradient(180deg, rgb(90, 90, 90) 0%, rgb(37, 37, 37) 100%);
		border-radius: 45rpx 45rpx 0 0;
		margin-top:12px;
		padding: 40rpx 30rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
		color: white;
	}
	
	.card-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		flex: 1;
	}
	
	.card-number {
		font-size: 48rpx;
		font-weight: 350;
		margin-bottom: 8rpx;
	}
	
	.card-label {
		margin-top:10px;
		font-size: 24rpx;
        font-weight:450;
		opacity: 0.9;
	}
	
	.card-divider {
		width: 2rpx;
		height: 60rpx;
		background-color: rgba(255, 255, 255, 0.3);
	}
	
	.agent-info {
		background-color: #fff;
		border-radius: 12rpx;
		padding: 24rpx 30rpx;
		margin-bottom: 20rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
	}
	
	.agent-text {
		font-size: 28rpx;
		color: #333;
	}
	
	.detail-section {
		display: flex;
		gap: 20rpx;
		margin-bottom: 20rpx;
	}
	
	.detail-item {
		flex: 1;
		background-color: #fff;
		border-radius: 12rpx;
		padding: 30rpx 20rpx;
		display: flex;
		align-items: center;
		gap: 16rpx;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
	}
	
	.detail-text {
		font-size: 26rpx;
		color: #333;
	}
	
	.summary-section, .adjustment-section {
		background-color: #252525;
		border-radius: 45rpx;
		padding: 30rpx;
		margin-top: 25rpx;
	}
	
	.summary-row, .adjustment-row {
		display: flex;
		justify-content: space-between;
	}
	
	.summary-item, .adjustment-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		flex: 1;
	}
	
	.summary-label, .adjustment-label {
		font-size: 26rpx;
		color: #e1e1e1;
		margin: 22rpx;
	}
	
	.summary-value, .adjustment-value {
		font-size: 32rpx;
		line-height:32px;
		font-weight: bold;
	}
	
	.recharge-section {
		background-color: #252525;
		border-radius: 0 0 45rpx 45rpx;
		margin-bottom: 20rpx;
	}
	
	.recharge-item {
		padding: 40rpx 30rpx;
		display: flex;
		align-items: center;
		gap: 16rpx;
		border-bottom: 1rpx solid #5e5e5e;
	}
	
	.recharge-item:last-child {
		border-bottom: none;
	}
	
	.recharge-text {
		flex: 1;
		font-size: 28rpx;
		color: #e1e1e1;
	}
	
	.recharge-amount {
		font-size: 28rpx;
		font-weight: bold;
		margin-right: 16rpx;
	}
	
	.red {
		color: #FF4757;
	}
	
	.orange {
		color: #FFA500;
	}
	
	.adjustment-divider {
		width: 2rpx;
		height: 60rpx;
		background-color: #7b7b7b;
		align-self: center;
	}
	
	.loading-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60rpx 0;
		margin: 40rpx 0;
	}
	
	.loading-text {
		font-size: 28rpx;
		color: #999;
		margin-top: 20rpx;
	}
	
	.statistics-content {
		/* 安卓下的内边距优化 */
		padding: 0 20rpx 140rpx;
	}
	
	.top-card {
		/* 安卓下的阴影优化 */
		box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
	}

</style>
