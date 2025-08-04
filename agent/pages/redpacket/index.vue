<template>
	<view class="container" :style="{ paddingTop: statusBarHeight + 'px', paddingBottom: safeAreaBottom + 'px' }">
		<!-- 统计卡片 -->
		<view class="stats-section">
			<view class="stats-card">
				<view class="stat-item" @click="showStatsDetail">
					<text class="stat-value">{{formatNumber(statistics.total_count)}}</text>
					<text class="stat-label">总红包数</text>
					<text class="stat-trend" v-if="statistics.count_trend">{{statistics.count_trend}}</text>
				</view>
				<view class="stat-item" @click="showStatsDetail">
					<text class="stat-value">{{formatAmount(statistics.total_amount)}}</text>
					<text class="stat-label">总金额</text>
					<text class="stat-trend" v-if="statistics.amount_trend">{{statistics.amount_trend}}</text>
				</view>
				<view class="stat-item" @click="showStatsDetail">
					<text class="stat-value">{{formatNumber(statistics.received_count)}}</text>
					<text class="stat-label">
						已领取：<text class="stat-rate">{{calculateReceiveRate()}}%</text>
					</text>					
				</view>
			</view>
		</view>
		
		<!-- 操作按钮 -->
		<view class="action-section">
			<uv-button 
				type="primary" 
				text="发放红包" 
				@click="createRedPacket"
				customStyle="background: orangered; color: #e1e1e1; border: none; border-radius: 40rpx;"
			></uv-button>
		</view>
		
		<!-- 筛选区域 -->
			<view class="filter-section">
				<view class="filter-tabs-wrapper">
					<scroll-view class="filter-tabs" scroll-x="true" show-scrollbar="false">
						<view class="tab-container">
							<view 
								v-for="(tab, index) in tabList" 
								:key="index"
								class="filter-tab" 
								:class="{ active: activeTab === index }"
								@click="onTabChange(index)"
							>
								<text class="tab-text">{{tab.name}}</text>
								<view class="tab-indicator" v-if="activeTab === index"></view>
							</view>
						</view>
					</scroll-view>
				</view>
			</view>
		
		<!-- 红包列表 -->
		<view class="redpacket-list-section">
			<view 
				class="scroll-container" 
				@scroll="handleScroll"
			>
				<view 
					v-for="(item, index) in redpacketList" 
					:key="item.id"
					class="redpacket-item"
					:class="getItemClass(item)"
					@tap="viewDetail(item)"
				>
					<!-- 红包头部 -->
					<view class="redpacket-header">
						<view class="header-left">
							<view class="title-row">
								<text class="redpacket-title">{{item.title}}</text>
								<view class="type-badge">{{getTypeText(item.type)}}</view>
							</view>
							<text class="redpacket-time">{{formatTime(item.create_time)}}</text>
						</view>
						<view class="header-right">
							<view class="status-badge" :class="getStatusClass(item.status)">{{getStatusText(item.status)}}</view>
						</view>
					</view>
					
					<!-- 祝福语 -->
					<text class="redpacket-blessing" v-if="item.blessing">{{item.blessing}}</text>
					
					<!-- 红包信息 -->
					<view class="redpacket-info">
						<view class="amount-section">
							<view class="amount-main">
								<text class="amount-symbol">¥</text>
								<text class="amount-value">{{formatAmountValue(item.total_amount)}}</text>
							</view>
							<text class="amount-label">总金额</text>
						</view>
						<view class="info-grid">
							<view class="info-item">
								<text class="info-value">{{item.total_count}}</text>
								<text class="info-label">红包个数</text>
							</view>
							<view class="info-item">
								<text class="info-value received">{{item.received_count}}</text>
								<text class="info-label">已领取</text>
							</view>
							<view class="info-item">
								<text class="info-value">{{calculateProgress(item)}}%</text>
								<text class="info-label">完成度</text>
							</view>
						</view>
						<view class="condition-info" v-if="item.condition_type && item.condition_type !== 'NONE'">
							<uv-icon name="setting" color="#ff934a" size="14"></uv-icon>
							<text class="condition-text">{{getConditionText(item)}}</text>
						</view>
					</view>
					
					<!-- 进度条 -->
					<view class="redpacket-progress" v-if="item.status === 'ACTIVE' && item.total_count > 0">
						<uv-line-progress 
							:percentage="calculateProgress(item)" 
							activeColor="#ff934a" 
							inactiveColor="#333"
							height="6"
							borderRadius="3"
						></uv-line-progress>
					</view>
					
					<!-- 操作按钮 -->
					<view class="redpacket-actions">
						<view class="action-left">
							<view 
								v-if="item.status === 'ACTIVE'"
								class="action-btn secondary"
								@click.stop="showCancelConfirm(item, index)"
							>
								<uv-icon name="close" color="#999" size="14"></uv-icon>
								<text class="btn-text">取消</text>
							</view>
							<view 
								v-if="item.status === 'ACTIVE'"
								class="action-btn secondary"
								@click.stop="shareRedPacket(item)"
							>
								<uv-icon name="share" color="#52c41a" size="14"></uv-icon>
								<text class="btn-text">分享</text>
							</view>
						</view>
						<view class="action-right">
							<view class="action-btn primary" @click.stop="viewDetail(item)">
								<text class="btn-text">查看详情</text>
								<uv-icon name="arrow-right" color="#fff" size="12"></uv-icon>
							</view>
						</view>
					</view>
					

				</view>
				
				<!-- 空状态 -->
				<view class="empty-state" v-if="!loading && redpacketList.length === 0">
					<uv-icon name="gift" color="#666" size="60"></uv-icon>
					<text class="empty-text">暂无红包数据</text>
					<text class="empty-tip">点击上方按钮发放红包</text>
				</view>
				
				<!-- 加载更多 -->
				<view class="load-more" v-if="hasMore && redpacketList.length > 0">
					<uv-loading-icon mode="circle" color="#ff934a" v-if="loadingMore"></uv-loading-icon>
					<text class="load-text">{{loadingMore ? '加载中...' : '上拉加载更多'}}</text>
				</view>
			</view>
		</view>
		
		<!-- 加载状态 -->
		<view class="loading-state" v-if="loading && redpacketList.length === 0">
			<uv-loading-icon mode="circle" color="#ff934a"></uv-loading-icon>
			<text class="loading-text">加载中...</text>
		</view>
	</view>
</template>

<script>
import authMixin from '@/mixins/auth.js';
import { getRedPackets, cancelRedPacket, getRedPacketStats } from '@/api/redpacket.js';

export default {
	mixins: [authMixin],
	data() {
			return {
				statusBarHeight: 0,
				safeAreaBottom: 0,
				activeTab: 0,
				tabList: [
					{ name: '全部', value: '' },
					{ name: '进行中', value: 'ACTIVE' },
					{ name: '已完成', value: 'FINISHED' },
					{ name: '已取消', value: 'CANCELLED' }
				],
				redpacketList: [],
			statistics: {},
			loading: false,
				loadingMore: false,
				hasMore: true,
				page: 1,
				limit: 10
			}
		},
	onLoad() {
		// 获取状态栏高度和底部安全距离
		const systemInfo = uni.getSystemInfoSync();
		this.statusBarHeight = systemInfo.statusBarHeight || 0;
		
		// 获取底部安全距离
		const safeAreaInsets = systemInfo.safeAreaInsets || {};
		this.safeAreaBottom = safeAreaInsets.bottom || 0;
		
		this.loadStatistics();
		this.loadRedPackets();
		
		// 监听红包创建事件
		uni.$on('redpacketCreated', () => {
			this.loadStatistics();
			this.loadRedPackets(true);
		});
	},
	
	onShow() {
		// 从其他页面返回时刷新数据
		if (this.redpacketList.length > 0) {
			this.loadStatistics();
			this.loadRedPackets(true);
		}
	},
	
	onUnload() {
		// 移除事件监听
		uni.$off('redpacketCreated');
	},
	
	methods: {
		// 加载统计数据
		async loadStatistics() {
			try {
				const response = await getRedPacketStats();
				if (response.code === 1) {
					this.statistics = response.data;
				}
			} catch (error) {
				console.error('加载统计数据失败:', error);
			}
		},
		
		// 加载红包列表
		async loadRedPackets(refresh = false) {
			if (this.loading) return;
			
			if (refresh) {
				this.page = 1;
				this.hasMore = true;
				this.redpacketList = [];
			}
			
			this.loading = true;
			
			try {
				const params = {
					page: this.page,
					limit: this.limit,
					status: this.getStatusFilter()
				};
				
				const response = await getRedPackets(params);
				
				if (response.code === 1) {
					const { data, total } = response.data;
					
					if (refresh) {
						this.redpacketList = data;
					} else {
						this.redpacketList.push(...data);
					}
					
					this.hasMore = this.redpacketList.length < total;
				} else {
					uni.showToast({
						title: response.msg || '加载失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('加载红包列表失败:', error);
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				this.loading = false;
				this.loadingMore = false;
			}
		},
		
		// 获取状态筛选条件
		getStatusFilter() {
			return this.tabList[this.activeTab]?.value || '';
		},
		
		// 获取红包类型文本
		getTypeText(type) {
			const typeMap = {
				'RANDOM': '随机红包',
				'FIXED': '固定红包'
			};
			return typeMap[type] || '未知类型';
		},
		
		// 获取红包项样式类
		getItemClass(item) {
			return {
				'item-active': item.status === 'ACTIVE',
				'item-finished': item.status === 'FINISHED',
				'item-cancelled': item.status === 'CANCELLED',
				'item-expired': item.status === 'EXPIRED',
				'item-expiring': this.isExpiringSoon(item)
			};
		},
		
		// 判断是否即将过期（24小时内）
		isExpiringSoon(item) {
			if (!item.expire_time || item.status !== 'ACTIVE') return false;
			const now = Math.floor(Date.now() / 1000);
			const timeLeft = item.expire_time - now;
			return timeLeft > 0 && timeLeft <= 24 * 60 * 60; // 24小时内
		},
		
		// 获取领取条件文本
		getConditionText(item) {
			if (!item.condition_type || item.condition_type === 'NONE') {
				return '无限制';
			}
			
			switch (item.condition_type) {
				case 'MIN_BET':
					return `今日最低投注 ¥${item.condition_value}`;
				default:
					return item.condition_value || '未知条件';
			}
		},
		
		// 计算进度百分比
		calculateProgress(item) {
			if (!item.total_count || item.total_count === 0) return 0;
			return Math.round((item.received_count / item.total_count) * 100);
		},
		

		
		// 格式化金额值
		formatAmountValue(amount) {
			if (!amount) return '0.00';
			// API返回的金额已经是以元为单位的字符串，直接转换为数字格式化
			const numAmount = parseFloat(amount);
			return numAmount.toFixed(2);
		},
		
		// 显示取消确认
		showCancelConfirm(item, index) {
			uni.showModal({
				title: '确认取消',
				content: `确定要取消红包"${item.title}"吗？取消后剩余金额将退回账户。`,
				confirmText: '确认取消',
				cancelText: '再想想',
				confirmColor: '#ff4757',
				success: (res) => {
					if (res.confirm) {
						this.cancelRedPacket(item, index);
					}
				}
			});
		},
		
		// 分享红包
		shareRedPacket(item) {
			// 构建分享内容
			const shareContent = `🧧 ${item.title}\n💰 总金额：¥${this.formatAmountValue(item.total_amount)}\n🎁 红包个数：${item.total_count}个\n${item.blessing ? '💌 ' + item.blessing : ''}`;
			
			// 复制到剪贴板
			uni.setClipboardData({
				data: shareContent,
				success: () => {
					uni.showToast({
						title: '分享内容已复制',
						icon: 'success'
					});
				},
				fail: () => {
					uni.showToast({
						title: '复制失败',
						icon: 'none'
					});
				}
			});
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 刷新统计数据和红包列表
			Promise.all([
				this.loadStatistics(),
				this.loadRedPackets(true)
			]).finally(() => {
				// 停止下拉刷新
				setTimeout(() => {
					uni.stopPullDownRefresh();
				}, 500);
			});
		},
		
		// 标签切换
		onTabChange(index) {
			this.activeTab = index;
			this.loadRedPackets(true);
		},
		

		
		// 显示统计详情
		showStatsDetail() {
			uni.navigateTo({
				url: '/pages/redpacket/stats'
			});
		},
		
		// 格式化数字
		formatNumber(num) {
			if (!num) return '0';
			if (num >= 10000) {
				return (num / 10000).toFixed(1) + 'w';
			}
			return num.toString();
		},
		
		// 格式化金额
		formatAmount(amount) {
			if (!amount) return '¥0.00';
			// API返回的金额已经是以元为单位的字符串，直接转换为数字格式化
			const numAmount = parseFloat(amount);
			return '¥' + numAmount.toFixed(2);
		},
		
		// 计算领取率
        calculateReceiveRate() {
            if (!this.statistics.total_packets || !this.statistics.received_count) return '0';
            return ((this.statistics.received_count / this.statistics.total_packets) * 100).toFixed(1);
        },
		

		
		// 处理滚动事件
		handleScroll(e) {
			const { scrollTop, scrollHeight, clientHeight } = e.target;
			// 当滚动到底部附近时加载更多
			if (scrollTop + clientHeight >= scrollHeight - 50) {
				this.loadMore();
			}
		},
		
		// 加载更多
		loadMore() {
			if (!this.hasMore || this.loadingMore) return;
			
			this.loadingMore = true;
			this.page++;
			this.loadRedPackets();
		},
		
		// 创建红包
		createRedPacket() {
			uni.navigateTo({
				url: '/pages/redpacket/create'
			});
		},
		
		// 查看详情
		viewDetail(item) {
			uni.navigateTo({
				url: `/pages/redpacket/detail?id=${item.id}`
			});
		},
		
		// 取消红包
		async cancelRedPacket(item) {
			uni.showModal({
				title: '确认取消',
				content: '确定要取消这个红包吗？取消后无法恢复。',
				success: async (res) => {
					if (res.confirm) {
						try {
							const response = await cancelRedPacket({ id: item.id });
							if (response.code === 1) {
								uni.showToast({
									title: '取消成功',
									icon: 'success'
								});
								this.loadStatistics();
								this.loadRedPackets(true);
							} else {
								uni.showToast({
									title: response.msg || '取消失败',
									icon: 'none'
								});
							}
						} catch (error) {
							console.error('取消红包失败:', error);
							uni.showToast({
								title: '网络错误，请重试',
								icon: 'none'
							});
						}
					}
				}
			});
		},
		
		// 获取状态样式类
		getStatusClass(status) {
			const classMap = {
				'ACTIVE': 'status-active',
				'FINISHED': 'status-finished',
				'CANCELLED': 'status-cancelled',
				'EXPIRED': 'status-expired'
			};
			return classMap[status] || '';
		},
		
		// 获取状态文本
		getStatusText(status) {
			const textMap = {
				'ACTIVE': '进行中',
				'FINISHED': '已完成',
				'CANCELLED': '已取消',
				'EXPIRED': '已过期'
			};
			return textMap[status] || '未知';
		},
		
		// 格式化时间
		formatTime(timestamp) {
			if (!timestamp) return '';
			const date = new Date(timestamp * 1000);
			const now = new Date();
			const diff = now - date;
			
			if (diff < 60000) {
				return '刚刚';
			} else if (diff < 3600000) {
				return Math.floor(diff / 60000) + '分钟前';
			} else if (diff < 86400000) {
				return Math.floor(diff / 3600000) + '小时前';
			} else if (diff < 172800000) { // 2天内
				return '昨天 ' + date.toLocaleTimeString().slice(0, 5);
			} else {
				return date.getMonth() + 1 + '月' + date.getDate() + '日';
			}
		},
		
		// 刷新数据
		refreshData() {
			this.loadStatistics();
			this.loadRedPackets(true);
		},
		
		// 快速筛选
		quickFilter(status) {
			const index = this.tabList.findIndex(tab => tab.value === status);
			if (index !== -1) {
				this.activeTab = index;
				this.loadRedPackets(true);
			}
		}
	}
}
</script>

<style scoped>
.container {
}

/* 统计卡片 */
.stats-section {
	margin: 25rpx;
}

.stats-card {
	display: flex;
	justify-content: space-around;
	background-color: #1b1b1b;
	border-radius: 105rpx;
	padding: 30rpx 20rpx;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.3);
	margin-bottom: 20rpx;
}

.stat-item {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.stat-value {
	font-size: 32rpx;
	font-weight: bold;
	color: #ff934a;
	margin-bottom: 8rpx;
}

.stat-label {
	font-size: 24rpx;
	color: #999;
}

/* 操作按钮 */
.action-section {
	margin: 20rpx 30rpx;
	/* #ifdef APP-PLUS */
	margin: 25rpx 40rpx;
	/* #endif */
	/* #ifdef H5 */
	margin: 15rpx 20rpx;
	/* #endif */
}

/* 筛选区域 */
.filter-section {
	margin: 20rpx 30rpx;
	background-color: #1a1a1a;
	border-radius: 50rpx;
	padding: 20rpx;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.3);
}

.filter-tabs {
	white-space: nowrap;
}

.tab-container {
	display: flex;
	gap: 12rpx;
}

.filter-tab {
	position: relative;
	padding: 5rpx 24rpx 12rpx 24rpx;
	border-radius: 35rpx;
	background-color: #333;
	transition: all 0.3s ease;
	flex-shrink: 0;
}

.filter-tab.active {
	background-color: rgba(255, 147, 74, 0.2);
}

.tab-text {
	font-size: 24rpx;
	color: #999;
	transition: color 0.3s ease;
}

.filter-tab.active .tab-text {
	color: #ff934a;
	font-weight: 500;
}

.tab-indicator {
	position: absolute;
	bottom: -2rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 20rpx;
	height: 4rpx;
	background-color: #ff934a;
	border-radius: 2rpx;
}



/* 红包列表 */
.redpacket-list-section {
	margin: 20rpx 22rpx;
	border-radius: 20rpx;
	overflow: hidden;
	background-color: #1a1a1a;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.3);
}

.scroll-container {
	height: calc(100vh - 350rpx);
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
	/* #ifdef APP-PLUS */
	height: calc(100vh - 400rpx - env(safe-area-inset-top) - env(safe-area-inset-bottom));
	/* #endif */
	/* #ifdef MP */
	height: calc(100vh - 370rpx);
	/* #endif */
	/* #ifdef H5 */
	height: calc(100vh - 330rpx);
	/* #endif */
}

.redpacket-item {
	padding: 30rpx;
	border-bottom: 1px solid #333;
	background-color: #1b1b1b;
	border-radius:55rpx 0 55rpx 0;
	transition: all 0.3s ease;
	margin-bottom:15rpx;
}

.redpacket-item:active {
	background-color: #252525;
	transform: scale(0.995);
	/* #ifdef H5 */
	cursor: pointer;
	/* #endif */
}

.redpacket-item:last-child {
	border-bottom: none;
}

.redpacket-item.item-expiring {
	border-left: 10rpx solid #faad14;
	background-color: rgba(250, 173, 20, 0.05);
}

.redpacket-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 20rpx;
}

.header-left {
	flex: 1;
	min-width: 0;
}

.title-row {
	display: flex;
	align-items: center;
	margin-bottom: 8rpx;
	gap: 12rpx;
}

.redpacket-title {
	font-size: 28rpx;
	color: #e1e1e1;
	font-weight: 500;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	flex: 1;
}

.type-badge {
	font-size: 20rpx;
	color: #ff934a;
	background-color: rgba(255, 147, 74, 0.2);
	padding: 4rpx 12rpx;
	border-radius: 12rpx;
	border: 1px solid rgba(255, 147, 74, 0.3);
	flex-shrink: 0;
}

.redpacket-time {
	font-size: 22rpx;
	color: #666;
}

.header-right {
	flex-shrink: 0;
	margin-left: 20rpx;
}

.status-badge {
	font-size: 22rpx;
	padding: 8rpx 16rpx;
	border-radius: 20rpx;
	font-weight: 500;
}

.redpacket-blessing {
	font-size: 24rpx;
	color: #999;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	margin-bottom: 16rpx;
}

.status-text {
	font-size: 22rpx;
	padding: 8rpx 16rpx;
	border-radius: 20rpx;
}

.status-active {
	background-color: rgba(255, 147, 74, 0.2);
	color: #ff934a;
}

.status-finished {
	background-color: rgba(82, 196, 26, 0.2);
	color: #52c41a;
}

.status-cancelled {
	background-color: rgba(153, 153, 153, 0.2);
	color: #999;
}

.status-expired {
	background-color: rgba(245, 34, 45, 0.2);
	color: #f5222d;
}

.redpacket-info {
	margin-bottom: 20rpx;
}

.amount-section {
	display: flex;
	align-items: center;
	margin-bottom: 20rpx;
	gap: 16rpx;
}

.amount-main {
	display: flex;
	align-items: baseline;
	gap: 4rpx;
}

.amount-symbol {
	font-size: 24rpx;
	color: #ff934a;
	font-weight: 500;
}

.amount-value {
	font-size: 30rpx;
	color: #ff934a;
	font-weight: bold;
}

.amount-label {
	font-size: 22rpx;
	color: #999;
}

.info-grid {
	display: flex;
	justify-content: space-between;
	margin-bottom: 16rpx;
}

.info-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	flex: 1;
}

.info-value {
	font-size: 24rpx;
	color: #e1e1e1;
	font-weight: 500;
	margin-bottom: 4rpx;
}

.info-value.received {
	color: #52c41a;
}

.info-label {
	font-size: 20rpx;
	color: #999;
}

.condition-info {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 12rpx 16rpx;
	background-color: rgba(255, 147, 74, 0.1);
	border-radius: 12rpx;
	border: 1px solid rgba(255, 147, 74, 0.2);
	margin-bottom: 16rpx;
}

.condition-text {
	font-size: 22rpx;
	color: #ff934a;
}

.redpacket-progress {
	margin-bottom: 20rpx;
}

.redpacket-actions {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 16rpx;
	border-top: 1px solid #333;
}

.action-left {
	display: flex;
	gap: 16rpx;
}

.action-right {
	flex-shrink: 0;
}

.action-btn {
	display: flex;
	align-items: center;
	gap: 6rpx;
	padding: 12rpx 20rpx;
	border-radius: 20rpx;
	transition: all 0.3s ease;
}

.action-btn.secondary {
	background-color: #333;
	border: 1px solid #444;
}

.action-btn.secondary:active {
	background-color: #444;
}

.action-btn.primary {
	background-color: #ff934a;
	border: 1px solid #ff934a;
	/* #ifdef H5 */
	cursor: pointer;
	/* #endif */
}

.action-btn.primary:active {
	background-color: #e8843f;
	transform: scale(0.98);
}

.btn-text {
	font-size: 22rpx;
	color: #e1e1e1;
}

.action-btn.primary .btn-text {
	color: #fff;
	font-weight: 500;
}

/* 空状态 */
.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 40rpx;
	text-align: center;
}

.empty-text {
	font-size: 28rpx;
	color: #666;
	margin-top: 24rpx;
}

.empty-tip {
	font-size: 24rpx;
	color: #999;
	margin-top: 12rpx;
}

/* 加载更多 */
.load-more {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 30rpx;
}

.load-text {
	font-size: 24rpx;
	color: #999;
	margin-top: 10rpx;
}

/* 加载状态 */
.loading-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 40rpx;
}

.loading-text {
	font-size: 26rpx;
	color: #999;
	margin-top: 20rpx;
}

.stat-rate{color:#cbcbcb;}
</style>