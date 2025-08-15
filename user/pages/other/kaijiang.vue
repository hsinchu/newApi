<template>
	<view class="container">
		<!-- 加载状态 -->
		<view v-if="loading" class="loading-container">
			<uv-loading-icon mode="flower"></uv-loading-icon>
			<text class="loading-text">加载中...</text>
		</view>
		
		<!-- 开奖列表 -->
		<view v-else class="lottery-list">
			<view 
				v-for="(lottery, index) in lotteryData" 
				:key="lottery.code" 
				class="lottery-item"
				@click="goToHistory(lottery.code)"
			>
				<view class="lottery-header">
					<view class="lottery-logo-section">
						<image v-if="lottery.typeIcon" :src="lottery.typeIcon" class="lottery-logo" mode="aspectFit"></image>
						<view class="lottery-title-section">
							<text class="lottery-name">{{ lottery.name }}</text>
							<view class="period-info">
								<text class="period-text">第<text class="period-number">{{ lottery.currentPeriod }}</text>期</text>
								<text class="deadline-text" v-if="lottery.deadlineInfo">{{ lottery.deadlineInfo }}</text>
							</view>
						</view>
					</view>
					<!-- <view v-if="lottery.isToday" class="today-tag">今日开奖</view> -->
					<uv-icon name="arrow-right" color="#999" size="16"></uv-icon>
				</view>
				<view class="lottery-numbers">
					<view class="number-group">
						<!-- 普通号码 -->
					<view 
						v-for="(number, numIndex) in lottery.numbers" 
						:key="numIndex" 
						class="number red"
						:data-number="number"
					>
						{{ number }}
					</view>
					<!-- 特殊号码 -->
					<view 
						v-for="(special, specIndex) in lottery.specialNumbers" 
						:key="'special-' + specIndex" 
						class="number blue"
						:data-number="special"
					>
						{{ special }}
					</view>
						<!-- 暂无数据提示 -->
						<text v-if="lottery.numbers.length === 0" class="no-data">暂无开奖数据</text>
					</view>
					<!-- 大小和、单双显示 -->
					<view v-if="lottery.category == 'QUICK' && lottery.numbers.length > 0" class="lottery-stats">
						<view class="stat-item">
							<text class="stat-label">和值:</text>
							<text class="stat-value">{{ lottery.sum }}</text>
							<text class="stat-tag" :class="lottery.sizeClass">{{ lottery.sizeText }}</text>
						</view>
						<view class="stat-item">
							<!-- <text class="stat-label">单双:</text> -->
							<text class="stat-tag" :class="lottery.oddEvenClass">{{ lottery.oddEvenText }}</text>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import authMixin from '@/mixins/auth.js';
import { getAllLatestDraw, getCurrentPeriod } from '@/api/lottery/lottery.js';
	
	export default {
		mixins: [authMixin],
		data() {
			return {
				lotteryData: [],
				lotteryTypes: [],
				loading: false
			}
		},
		mounted() {
			// 页面加载时获取最新开奖数据
			this.loadLotteryData();
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 重新加载开奖数据
			this.loadLotteryData();
			// 延迟停止下拉刷新动画
			setTimeout(() => {
				uni.stopPullDownRefresh();
			}, 1000);
		},
		
		methods: {
			// 加载开奖数据
			async loadLotteryData() {
				this.loading = true;
				try {
					// 获取所有开放彩种的最新开奖记录
					const response = await getAllLatestDraw();
					
					if (response.code === 1 && response.data) {
						// 处理每个彩种数据并获取当前期数信息
						const lotteryPromises = response.data.map(async (item) => {
							// 判断是否有特殊号码（双色球、大乐透等）
							const hasSpecial = ['ssq', 'dlt'].includes(item.lottery_code);
							
							// 获取当前期数信息
							let currentPeriod = '';
							let deadlineInfo = '';
							
							try {
								const periodResponse = await getCurrentPeriod(item.lottery_code);
								if (periodResponse.code === 1 && periodResponse.data) {
									const periodData = periodResponse.data;
									currentPeriod = periodData.period_number || '';
									
									// 格式化截止时间信息
									if (periodData.closing_time) {
										const today = new Date();
										const tomorrow = new Date(today);
										tomorrow.setDate(tomorrow.getDate() + 1);
										
										// 判断是今天还是明天截止
										const dayStr = periodData.remaining_minutes > 0 ? '今天' : '明天';
										deadlineInfo = `${dayStr}${periodData.closing_time}截止`;
									}
								}
							} catch (periodError) {
								console.warn(`获取${item.lottery_name}期数信息失败:`, periodError);
							}
							
							// 计算和值、大小和、单双
								const numbers = item.latest_draw ? this.parseNumbers(item.latest_draw.open_code, hasSpecial) : [];
								const sum = numbers.reduce((acc, num) => acc + parseInt(num), 0);
								const sizeText = sum > 18 ? '大' : sum < 9 ? '小' : '和';
								const sizeClass = sum > 18 ? 'size-big' : sum < 9 ? 'size-small' : 'size-middle';
								const oddEvenText = sum % 2 === 0 ? '双' : '单';
								const oddEvenClass = sum % 2 === 0 ? 'even' : 'odd';
								
								return {
								code: item.lottery_code,
								name: item.lottery_name,
								typeIcon: item.type_icon,
								category: item.lottery_category,
								schedule: '',
								hasSpecial: hasSpecial,
								issue: item.latest_draw ? item.latest_draw.period_no + '期' : '暂无数据',
								numbers: numbers,
								specialNumbers: item.latest_draw ? this.parseSpecialNumbers(item.latest_draw.open_code, hasSpecial) : [],
								drawTime: item.latest_draw ? item.latest_draw.draw_time : null,
								isToday: item.latest_draw ? this.isToday(item.latest_draw.draw_time) : false,
								currentPeriod: currentPeriod,
								deadlineInfo: deadlineInfo,
								sum: sum,
								sizeText: sizeText,
								sizeClass: sizeClass,
								oddEvenText: oddEvenText,
								oddEvenClass: oddEvenClass
							};
						});
						
						// 等待所有彩种数据处理完成
						this.lotteryData = await Promise.all(lotteryPromises);
					} else {
						// 如果API调用失败，显示空数据提示
						this.lotteryData = [];
						uni.showToast({
							title: response.msg || '获取开奖数据失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('获取开奖数据失败:', error);
					this.lotteryData = [];
					uni.showToast({
						title: '网络错误，请稍后重试',
						icon: 'none'
					});
				} finally {
					this.loading = false;
				}
			},
			
			// 解析开奖号码
			parseNumbers(openCode, hasSpecial) {
				if (!openCode) return [];
				const numbers = openCode.split(',');
				if (hasSpecial) {
					// 有特殊号码的彩种，去掉最后一个或两个号码
					return numbers.slice(0, -1);
				}
				return numbers;
			},
			
			// 解析特殊号码
			parseSpecialNumbers(openCode, hasSpecial) {
				if (!openCode || !hasSpecial) return [];
				const numbers = openCode.split(',');
				// 返回最后一个号码作为特殊号码
				return [numbers[numbers.length - 1]];
			},
			
			// 判断是否为今天
			isToday(drawTime) {
				if (!drawTime) return false;
				const today = new Date().toDateString();
				const drawDate = new Date(drawTime).toDateString();
				return today === drawDate;
			},
			
			// 点击彩票项
			handleLotteryClick(lottery) {
				console.log('点击了彩票:', lottery.name);
				// 可以跳转到详细页面
				// uni.navigateTo({
				//     url: `/pages/lottery/detail?code=${lottery.code}`
				// });
			},
			
			// 跳转到历史记录页面
			goToHistory(lotteryCode) {
				uni.navigateTo({
					url: `/pages/other/history?code=${lotteryCode}`
				});
			}
		}
	}
</script>

<style scoped lang="scss">
	.container {
		background: #f8f9fa;
		color: #333;
	}
	
	.announcement-header {
		padding: 30rpx 40rpx 20rpx;
		text-align: center;
		
		.announcement-title {
			font-size: 36rpx;
			font-weight: bold;
			color: #ff4500;
			border-bottom: 2px solid #ff4500;
			padding-bottom: 10rpx;
			display: inline-block;
		}
	}
	
	.lottery-item {
		background-color: #fff;
		border-radius: 55rpx 0 55rpx 0;
		margin: 15rpx;
		padding: 30rpx;
		border: 1px solid #e9ecef;
		transition: all 0.3s ease;
		position: relative;
		cursor: pointer;
		
		&:active {
			transform: scale(0.98);
			background-color: #f8f9fa;
		}
	}
	
	.arrow-icon {
		position: absolute;
		right: 30rpx;
		top: 50%;
		transform: translateY(-50%);
		font-size: 32rpx;
		color: #999;
		font-weight: bold;
	}
	
	.lottery-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 20rpx;
	}
	
	.lottery-logo-section {
		flex: 1;
		display: flex;
		align-items: center;
		gap: 20rpx;
	}
	
	.lottery-logo {
		width: 60rpx;
		height: 60rpx;
		border-radius: 8rpx;
	}
	
	.lottery-title-section {
		flex: 1;
		display: flex;
		flex-direction: column;
		gap: 8rpx;
	}
	
	.lottery-name {
		font-size: 32rpx;
		font-weight: bold;
		color: #333;
	}
	
	.period-info {
		display: flex;
		align-items: center;
		gap: 20rpx;
	}
	
	.period-text {
		font-size: 28rpx;
		color: #666;
	}
	
	.period-number {
		color: #ff6b35;
		font-weight: bold;
	}
	
	.deadline-text {
		font-size: 24rpx;
		color: #999;
		background: #f5f5f5;
		padding: 4rpx 12rpx;
		border-radius: 12rpx;
	}
	
	.today-tag {
		background: linear-gradient(135deg, #ff6b35, #f7931e);
		color: white;
		font-size: 20rpx;
		padding: 8rpx 16rpx;
		border-radius: 20rpx;
		margin-left: 20rpx;
	}
	
	.lottery-stats {
		margin-top: 20rpx;
		margin-left:25rpx;
		display: flex;
		gap: 30rpx;
		align-items: center;
	}
	
	.stat-item {
		display: flex;
		align-items: center;
		gap: 18rpx;
	}
	
	.stat-label {
		font-size: 24rpx;
		color: #666;
	}
	
	.stat-value {
		font-size: 28rpx;
		font-weight: bold;
		color: #333;
	}
	
	.stat-tag {
		font-size: 20rpx;
		padding: 4rpx 12rpx;
		border-radius: 12rpx;
		font-weight: bold;
	}
	
	.size-big {
		background: #ff4757;
		color: white;
	}
	
	.size-small {
		background: #3742fa;
		color: white;
	}
	
	.size-middle {
		background: #2ed573;
		color: white;
	}
	
	.odd {
		background: #ffa502;
		color: white;
	}
	
	.even {
		background: #5352ed;
		color: white;
	}
	
	.lottery-numbers {
		display: flex;
		align-items: center;
	}
	
	.number-group {
		display: flex;
		align-items: center;
		gap: 15rpx;
	}
	
	.number {
		width: 60rpx;
		height: 60rpx;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 24rpx;
		font-weight: bold;
		color: #fff;
		position: relative;
		transition: all 0.3s ease;
		transform-style: preserve-3d;
		box-shadow: 
			0 4rpx 12rpx rgba(255, 69, 0, 0.4),
			0 2rpx 4rpx rgba(0, 0, 0, 0.3),
			inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3),
			inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
		
		&:hover {
			transform: translateY(-2rpx) scale(1.05);
		}
	}
	
	// 按数字内容0-9设置不同颜色的数字球样式
	// 数字0的样式
	.number[data-number="0"] {
		background: radial-gradient(circle at 30% 30%, #FF6B6B, #FF5252 40%, #F44336 70%, #D32F2F);
		box-shadow: 0 4rpx 12rpx rgba(255, 107, 107, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字1的样式
	.number[data-number="1"] {
		background: radial-gradient(circle at 30% 30%, #4CAF50, #45a049 40%, #388e3c 70%, #2e7d32);
		box-shadow: 0 4rpx 12rpx rgba(76, 175, 80, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字2的样式
	.number[data-number="2"] {
		background: radial-gradient(circle at 30% 30%, #2196F3, #1976D2 40%, #1565C0 70%, #0d47a1);
		box-shadow: 0 4rpx 12rpx rgba(33, 150, 243, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字3的样式
	.number[data-number="3"] {
		background: radial-gradient(circle at 30% 30%, #9C27B0, #7B1FA2 40%, #6A1B9A 70%, #4A148C);
		box-shadow: 0 4rpx 12rpx rgba(156, 39, 176, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字4的样式
	.number[data-number="4"] {
		background: radial-gradient(circle at 30% 30%, #FF9800, #F57C00 40%, #EF6C00 70%, #E65100);
		box-shadow: 0 4rpx 12rpx rgba(255, 152, 0, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字5的样式
	.number[data-number="5"] {
		background: radial-gradient(circle at 30% 30%, #E91E63, #C2185B 40%, #AD1457 70%, #880E4F);
		box-shadow: 0 4rpx 12rpx rgba(233, 30, 99, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字6的样式
	.number[data-number="6"] {
		background: radial-gradient(circle at 30% 30%, #00BCD4, #0097A7 40%, #00838F 70%, #006064);
		box-shadow: 0 4rpx 12rpx rgba(0, 188, 212, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字7的样式
	.number[data-number="7"] {
		background: radial-gradient(circle at 30% 30%, #795548, #5D4037 40%, #4E342E 70%, #3E2723);
		box-shadow: 0 4rpx 12rpx rgba(121, 85, 72, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字8的样式
	.number[data-number="8"] {
		background: radial-gradient(circle at 30% 30%, #607D8B, #455A64 40%, #37474F 70%, #263238);
		box-shadow: 0 4rpx 12rpx rgba(96, 125, 139, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	// 数字9的样式
	.number[data-number="9"] {
		background: radial-gradient(circle at 30% 30%, #b09f09, #9d8104 40%, #F9A825 70%, #F57F17);
		box-shadow: 0 4rpx 12rpx rgba(255, 235, 59, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	}
	
	.red {
		background: radial-gradient(circle at 30% 30%, #ff6b35, #ff4500 60%, #cc3300 100%);
		box-shadow: 
			0 8rpx 16rpx rgba(255, 69, 0, 0.4),
			0 4rpx 8rpx rgba(255, 69, 0, 0.3),
			inset 0 2rpx 4rpx rgba(255, 255, 255, 0.2),
			inset 0 -2rpx 4rpx rgba(0, 0, 0, 0.2);
	}
	
	.blue {
		background: radial-gradient(circle at 30% 30%, #4d9eff, #2196f3 60%, #1976d2 100%);
		box-shadow: 
			0 8rpx 16rpx rgba(33, 150, 243, 0.4),
			0 4rpx 8rpx rgba(33, 150, 243, 0.3),
			inset 0 2rpx 4rpx rgba(255, 255, 255, 0.2),
			inset 0 -2rpx 4rpx rgba(0, 0, 0, 0.2);
	}
	
	.loading-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60rpx 0;
	}
	
	.loading-text {
		margin-top: 20rpx;
		font-size: 28rpx;
		color: #999;
	}
	
	.no-data {
		font-size: 28rpx;
		color: #999;
		padding: 20rpx;
	}
</style>