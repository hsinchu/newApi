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
					<text class="lottery-name">{{ lottery.name }} {{ lottery.issue }} {{ lottery.schedule }}</text>
					<view v-if="lottery.isToday" class="today-tag">今日开奖</view>
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
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import authMixin from '@/mixins/auth.js';
	import { getAllLatestDraw } from '@/api/lottery/lottery.js';
	
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
						// 直接使用API返回的数据
						this.lotteryData = response.data.map(item => {
							// 判断是否有特殊号码（双色球、大乐透等）
							const hasSpecial = ['ssq', 'dlt'].includes(item.lottery_code);
							
							return {
								code: item.lottery_code,
								name: item.lottery_name,
								schedule: '',
								hasSpecial: hasSpecial,
								issue: item.latest_draw ? item.latest_draw.period_no + '期' : '暂无数据',
								numbers: item.latest_draw ? this.parseNumbers(item.latest_draw.open_code, hasSpecial) : [],
								specialNumbers: item.latest_draw ? this.parseSpecialNumbers(item.latest_draw.open_code, hasSpecial) : [],
								drawTime: item.latest_draw ? item.latest_draw.draw_time : null,
								isToday: item.latest_draw ? this.isToday(item.latest_draw.draw_time) : false
							};
						});
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
		background: #252525;
		color: #e1e1e1;
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
		background-color: #1a1a1a;
		border-radius: 55rpx 0 55rpx 0;
		margin: 25rpx;
		padding: 30rpx;
		border: 1px solid #2a2a2a;
		transition: all 0.3s ease;
		position: relative;
		cursor: pointer;
		
		&:active {
			transform: scale(0.98);
			background-color: #252525;
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
		
		.lottery-name {
			font-size: 25rpx;
			color: #e1e1e1;
			font-weight: 350;
			line-height:55rpx;
			flex: 1;
		}
		
		.today-tag {
			background: linear-gradient(135deg, #ff5555, #bc0000);
			color: #fff;
			font-size: 20rpx;
			padding: 8rpx 15rpx;
			border-radius: 20rpx;
			margin-right: 20rpx;
			font-weight: 330;
		}
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