<template>
	<view class="redpacket-container">		
		<!-- 红包头部信息区域 -->
		<view class="header-section">
			<!-- 统计数据 -->
			<view class="stats-row">
				<view v-for="(item, index) in statsData" :key="'stat-' + index" class="stat-container">
					<view class="stat-item">
						<text class="stat-number">{{ item.count }}</text>
						<text class="stat-label">{{ item.label }}</text>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 红包列表 -->
		<view class="redpacket-list">
			<view class="list-content">
				<view 
					v-for="(item, index) in redpacketList" 
					:key="index"
					class="redpacket-item"
				>
					<view class="item-left">
						<view class="item-info">
							<text class="item-time">{{ item.time }}</text>
						</view>
					</view>
					<view class="item-right">
						<text class="item-amount">{{ item.amount }}元</text>
					</view>
				</view>
				
				<!-- 空状态 -->
				<view v-if="redpacketList.length === 0 && !loading" class="empty-state">
					<uv-icon name="gift" size="60" color="#666"></uv-icon>
					<text class="empty-text">暂无红包记录</text>
				</view>
				
				<!-- 加载状态 -->
				<view v-if="redpacketList.length > 0" class="load-more">
					<view v-if="loading" class="loading-text">
						<uv-loading-icon mode="flower"></uv-loading-icon>
						<text>加载中...</text>
					</view>
					<view v-else-if="!hasMore" class="no-more-text">
						<text>没有更多数据了</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { myRedPacketRecords } from '@/api/redpacket.js';
export default {
	data() {
		return {
			totalCount: 0,
			totalAmount: '0.00',
			statsData: [
				{ count: '0.00', label: '总金额' },
				{ count: '0', label: '红包个数' }
			],
			redpacketList: [],
			// 分页相关
			currentPage: 1,
			limit: 20,
			hasMore: true,
			loading: false,
			refreshing: false
		}
	},
	methods: {
		// 获取红包数据
		async loadRedpacketData(isRefresh = false) {
			if (this.loading) return;
			
			this.loading = true;
			
			try {
				// 如果是刷新，重置分页
				if (isRefresh) {
					this.currentPage = 1;
					this.hasMore = true;
					this.redpacketList = [];
				}
				
				const response = await myRedPacketRecords({
					page: this.currentPage,
					limit: this.limit
				});
				
				if (response.code === 1) {
					const data = response.data;
					
					// 如果是第一页或刷新，直接赋值；否则追加数据
					if (this.currentPage === 1) {
						this.redpacketList = data.list || [];
						// 更新统计数据（仅第一页返回）
						if (data.stats) {
							this.totalCount = data.stats.totalCount || 0;
							this.totalAmount = data.stats.totalAmount || '0.00';
							this.updateStatsData();
						}
					} else {
						this.redpacketList = this.redpacketList.concat(data.list || []);
					}
					
					// 更新分页状态
					this.hasMore = data.hasMore || false;
					this.currentPage++;
					
				} else {
					uni.showToast({
						title: response.msg || '获取数据失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('获取红包数据失败:', error);
				uni.showToast({
					title: '获取数据失败',
					icon: 'none'
				});
			} finally {
				this.loading = false;
				this.refreshing = false;
			}
		},
		
		// 加载更多数据
		async loadMore() {
			if (!this.hasMore || this.loading) return;
			await this.loadRedpacketData();
		},
		
		// 更新统计数据
		updateStatsData() {
			this.statsData = [
				{ count: this.totalAmount, label: '总金额' },
				{ count: this.totalCount.toString(), label: '红包个数' }
			];
		},
		
		// 下拉刷新
		async onPullDownRefresh() {
			this.refreshing = true;
			try {
				await this.loadRedpacketData(true);
			} catch (error) {
				console.error('刷新失败:', error);
			} finally {
				uni.stopPullDownRefresh();
				this.refreshing = false;
			}
		},
		
		// 触底加载更多
		async onReachBottom() {
			await this.loadMore();
		}
	},
	onLoad() {
		// 页面加载时获取红包数据
		this.loadRedpacketData();
		// 初始化统计数据
		this.updateStatsData();
	}
}
</script>

<style scoped lang="scss">
.redpacket-container {
	background: #ff3434;
}

// 红包头部信息区域
.header-section {
	padding: 30rpx 32rpx 55rpx;
	position: relative;
	
	.stats-row {
		display: flex;
		align-items: center;
		justify-content: space-around;
		margin-bottom: 20rpx;
		
		.stat-container {
			display: flex;
			align-items: center;
			flex: 1;
			justify-content: center;
			
			.stat-item {
				display: flex;
				flex-direction: column;
				align-items: center;
				
				.stat-number {
					color: #ffffff;
					font-size: 36rpx;
					font-weight: bold;
					margin-bottom: 10rpx;
				}
				
				.stat-label {
					color: rgba(255, 255, 255, 0.8);
					font-size: 26rpx;
				}
			}
		}
	}
	
	.redpacket-info {
		display: flex;
		align-items: center;
		gap: 20rpx;
		margin-bottom: 50rpx;
		justify-content: center;
		
		.redpacket-title {
			display: flex;
			flex-direction: column;
			gap: 8rpx;
			
			.title-text {
				color: #ffffff;
				font-size: 32rpx;
				font-weight: 600;
			}
			
			.subtitle-text {
				color: rgba(255, 255, 255, 0.8);
				font-size: 26rpx;
			}
		}
	}
	
	.stats-info {
		text-align: center;
		
		.stats-text {
			color: rgba(255, 255, 255, 0.85);
			font-size: 26rpx;
		}
	}
}

// 红包列表
.redpacket-list {
	background: #f5f5f5;
	flex: 1;
	margin-top: -30rpx;
	border-radius: 55rpx 55rpx 0 0;
	position: relative;
	z-index: 1;
	
	.list-content {
		padding: 35rpx 35rpx 25rpx;
		
		.redpacket-item {
			background: #ffffff;
			border-radius: 0;
			padding: 25rpx 15rpx;
			margin-bottom: 0;
			display: flex;
			align-items: center;
			justify-content: space-between;
			border-bottom: 1rpx solid #f0f0f0;
			
			&:last-child {
				border-bottom: none;
			}
			
			.item-left {
				display: flex;
				align-items: center;
				gap: 24rpx;
				flex: 1;
				
				.item-info {
					display: flex;
					flex-direction: column;
					gap: 8rpx;
					
					.item-title {
						color: #333;
						font-size: 30rpx;
						font-weight: 500;
					}
					
					.item-time {
						color: #999;
						font-size: 26rpx;
					}
				}
			}
			
			.item-right {
				.item-amount {
					color: red;
					font-size: 25rpx;
					font-weight: 450;
				}
			}
		}
		
		.empty-state {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 120rpx 0;
			
			.empty-text {
				color: #999;
				font-size: 28rpx;
				margin-top: 24rpx;
			}
		}
		
		.load-more {
			padding: 30rpx 0;
			text-align: center;
			
			.loading-text {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 16rpx;
				color: #666;
				font-size: 26rpx;
			}
			
			.no-more-text {
				color: #999;
				font-size: 24rpx;
				padding: 20rpx 0;
			}
		}
	}
}
</style>