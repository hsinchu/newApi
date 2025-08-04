<template>
	<view class="history-container">
		<!-- 加载状态 -->
		<view v-if="loading" class="loading-container">
			<text class="loading-text">加载中...</text>
		</view>
		
		<!-- 历史记录列表 -->
		<view v-else-if="historyList.length > 0" class="history-list">
			<view v-for="(item, index) in historyList" :key="index" class="history-item">
				<view class="item-header">
					<text class="period">第{{ item.period_no }}期</text>
					<text class="draw-time">{{ formatDateTime(item.draw_time) }}</text>
				</view>
				<view class="numbers-container">
					<view class="number-group">
						<text v-for="(number, numIndex) in parseNumbers(item.open_code)" :key="numIndex" class="number" :data-number="number">{{ number }}</text>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 空数据提示 -->
		<view v-else class="empty-container">
			<text class="empty-text">暂无开奖记录</text>
		</view>
		
		<!-- 没有更多数据提示 -->
		<view v-if="!hasMore && historyList.length > 0" class="no-more">
			<text class="no-more-text">没有更多数据了</text>
		</view>
		
		<!-- 加载更多状态 -->
		<view v-if="loadingMore" class="loading-more">
			<text class="loading-more-text">加载中...</text>
		</view>
	</view>
</template>

<script>
	import { getHistoryDraw } from '@/api/lottery/lottery.js';
	import { formatDateTime } from '@/utils/common.js';
	
	export default {
		data() {
			return {
				lotteryCode: '',
				lotteryName: '',
				historyList: [],
				loading: false,
				loadingMore: false,
				page: 1,
				limit: 20,
				hasMore: true,
				total: 0
			}
		},
		
		onLoad(options) {
			if (options.code) {
				this.lotteryCode = options.code;
				this.loadHistoryData();
				uni.setNavigationBarTitle({
					title: this.getLotteryName(this.lotteryCode)+'开奖历史'
				});
			}
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			this.page = 1;
			this.historyList = [];
			this.hasMore = true;
			this.loadHistoryData();
			// 延迟停止下拉刷新动画
			setTimeout(() => {
				uni.stopPullDownRefresh();
			}, 1000);
		},
		
		// 上拉加载更多
		onReachBottom() {
			if (this.hasMore && !this.loading && !this.loadingMore) {
				this.loadMore();
			}
		},
		
		methods: {
			// 加载历史数据
			async loadHistoryData() {
				this.loading = true;
				try {
					const params = {
						lottery_code: this.lotteryCode,
						page: this.page,
						limit: this.limit
					};
					
					const response = await getHistoryDraw(params);
					
					if (response.code === 1 && response.data) {
						const { list, total } = response.data;
						
						// 设置彩种名称（从第一条记录获取，如果有的话）
						if (list.length > 0 && !this.lotteryName) {
							this.lotteryName = this.getLotteryName(this.lotteryCode);
						}
						
						if (this.page === 1) {
							this.historyList = list;
						} else {
							this.historyList.push(...list);
						}
						
						this.total = total;
						this.hasMore = this.historyList.length < total;
					} else {
						uni.showToast({
							title: response.msg || '获取历史记录失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('获取历史记录失败:', error);
					uni.showToast({
						title: '网络错误，请稍后重试',
						icon: 'none'
					});
				} finally {
					this.loading = false;
				}
			},
			
			// 加载更多
			async loadMore() {
				if (!this.hasMore || this.loadingMore) return;
				
				this.loadingMore = true;
				this.page++;
				
				try {
					const params = {
						lottery_code: this.lotteryCode,
						page: this.page,
						limit: this.limit
					};
					
					const response = await getHistoryDraw(params);
					
					if (response.code === 1 && response.data) {
						const { list, total } = response.data;
						
						this.historyList.push(...list);
						this.total = total;
						this.hasMore = this.historyList.length < total;
					} else {
						this.page--; // 回退页码
						uni.showToast({
							title: response.msg || '加载失败',
							icon: 'none'
						});
					}
				} catch (error) {
					this.page--; // 回退页码
					console.error('加载更多失败:', error);
					uni.showToast({
						title: '网络错误，请稍后重试',
						icon: 'none'
					});
				} finally {
					this.loadingMore = false;
				}
			},
			
			// 解析开奖号码
			parseNumbers(openCode) {
				if (!openCode) return [];
				
				// 处理包含特殊号码的情况（如双色球：1,2,3,4,5,6|7）
				if (openCode.includes('|')) {
					const parts = openCode.split('|');
					return parts[0].split(',').map(num => num.trim());
				}
				
				// 普通号码（如3D：1,2,3）
				return openCode.split(',').map(num => num.trim());
			},
			
			// 格式化时间
			formatDateTime(timestamp) {
				return formatDateTime(timestamp);
			},
			
			// 获取彩种名称
			getLotteryName(code) {
				const nameMap = {
					'3d': '福彩3D',
					'ff3d': '分分3D',
					'5f3d': '5分3D',
					'ssq': '双色球',
					'dlt': '大乐透',
					'qlc': '七乐彩',
					'pl3': '排列三',
					'pl5': '排列五',
					'qxc': '七星彩'
				};
				return nameMap[code] || code;
			}
		}
	}
</script>

<style scoped lang="scss">
	.history-container {
		min-height: 100vh;
		background: #252525;
		color: #e1e1e1;
		padding: 20rpx;
	}
	
	.loading-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 100rpx 0;
		
		.loading-text {
			margin-top: 20rpx;
			font-size: 28rpx;
			color: #999;
		}
	}
	
	.history-list {
		padding-bottom: 40rpx;
	}
	
	.history-item {
		background-color: #1a1a1a;
		border-radius: 55rpx 0 55rpx 0;
		margin: 25rpx 0;
		padding: 30rpx;
		border: 1px solid #2a2a2a;
		transition: all 0.3s ease;
		
		&:active {
			transform: scale(0.98);
			background-color: #252525;
		}
		
		.item-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20rpx;
			
			.period {
				font-size: 25rpx;
				color: #e1e1e1;
				font-weight: 350;
				line-height: 55rpx;
			}
			
			.draw-time {
				font-size: 20rpx;
				color: #999;
				font-weight: 330;
			}
		}
		
		.numbers-container {
			.number-group {
				display: flex;
				flex-wrap: wrap;
				gap: 15rpx;
				
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
			}
		}
	}
	
	.empty-container {
		text-align: center;
		padding: 100rpx 0;
		
		.empty-text {
			font-size: 28rpx;
			color: #999;
			padding: 20rpx;
		}
	}
	
	.no-more {
		text-align: center;
		padding: 30rpx 0;
		
		.no-more-text {
			color: #999;
			font-size: 24rpx;
			opacity: 0.6;
		}
	}
	
	.loading-more {
		text-align: center;
		padding: 30rpx 0;
		
		.loading-more-text {
			color: #999;
			font-size: 28rpx;
			opacity: 0.8;
		}
	}
</style>