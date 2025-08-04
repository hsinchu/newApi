<template>
	<view class="container" :style="{ paddingTop: statusBarHeight + 'px' }">
		
		<scroll-view class="scroll-container" scroll-y="true">
			<!-- 红包基本信息 -->
			<view class="info-section" v-if="redpacketInfo">
				<view class="redpacket-card" :class="getCardClass(redpacketInfo.status)">
					<!-- 卡片头部 -->
					<view class="card-header">
						<view class="header-content">
							<view class="redpacket-icon">
								<uv-icon name="gift" color="#fff" size="40"></uv-icon>
							</view>
							<view class="title-section">
								<text class="redpacket-title">{{redpacketInfo.title}}</text>
								<text class="redpacket-type">{{getTypeText(redpacketInfo.type)}}</text>
							</view>
						</view>
						<view class="header-status">
							<text class="status-text" :class="getStatusClass(redpacketInfo.status)">{{getStatusText(redpacketInfo.status)}}</text>
							<text class="create-time">{{formatTime(redpacketInfo.create_time)}}</text>
						</view>
					</view>
					
					<!-- 祝福语 -->
					<view class="blessing-section" v-if="redpacketInfo.blessing">
						<text class="blessing-icon">💝</text>
						<text class="redpacket-blessing">{{redpacketInfo.blessing}}</text>
					</view>
					
					<!-- 金额展示 -->
					<view class="amount-section">
						<view class="amount-item main-amount">
							<text class="amount-label">总金额</text>
							<text class="amount-value">¥{{formatAmount(redpacketInfo.total_amount)}}</text>
						</view>
						<view class="amount-grid">
							<view class="amount-item">
								<text class="amount-label">红包个数</text>
								<text class="amount-value">{{redpacketInfo.total_count}}</text>
							</view>
							<view class="amount-item">
								<text class="amount-label">已领取</text>
								<text class="amount-value received">{{redpacketInfo.received_count}}</text>
							</view>
							<view class="amount-item">
								<text class="amount-label">剩余个数</text>
								<text class="amount-value remaining">{{redpacketInfo.total_count - redpacketInfo.received_count}}</text>
							</view>
							<view class="amount-item">
								<text class="amount-label">剩余金额</text>
								<text class="amount-value remaining">¥{{formatAmount(redpacketInfo.total_amount - redpacketInfo.received_amount)}}</text>
							</view>
						</view>
					</view>
					
					<!-- 进度条 -->
					<view class="progress-section" v-if="redpacketInfo.status === 'ACTIVE'">
						<view class="progress-info">
							<text class="progress-label">领取进度</text>
							<text class="progress-text">{{calculateProgress()}}%</text>
						</view>
						<uv-line-progress 
							:percentage="calculateProgress()" 
							activeColor="#fff" 
							inactiveColor="rgba(255,255,255,0.3)"
							height="8"
							borderRadius="4"
						></uv-line-progress>
						<view class="progress-stats">
							<text class="stat-item">{{redpacketInfo.received_count}}/{{redpacketInfo.total_count}} 已领取</text>
							<text class="stat-item">{{getExpireText()}}</text>
						</view>
					</view>
					
					<!-- 领取条件 -->
					<view class="condition-section" v-if="hasCondition()">
						<text class="condition-title">领取条件</text>
						<text class="condition-text">{{getConditionText(redpacketInfo.condition_type, redpacketInfo.condition_value)}}</text>
					</view>
					
					<!-- 操作按钮 -->
					<view class="action-buttons">
						<view class="button-row">
							<text 
								v-if="redpacketInfo.status === 'ACTIVE'"
								class="action-btn cancel-btn" 
								@click="showCancelConfirm"
							>取消红包</text>
							<text 
								v-if="redpacketInfo.status === 'ACTIVE'"
								class="action-btn share-btn" 
								@click="shareRedPacket"
							>分享红包</text>

						</view>
					</view>
				</view>
			</view>
			
			<!-- 红包详细信息 -->
			<view class="details-section" v-if="redpacketInfo">
				<view class="section-title">
					<uv-icon name="list" color="#ff934a" size="20"></uv-icon>
					<text class="title-text">详细信息</text>
				</view>
				
				<view class="detail-list">
					<view class="detail-item">
						<text class="detail-label">红包类型</text>
						<text class="detail-value">{{redpacketInfo.type === 'RANDOM' ? '随机红包' : '固定红包'}}</text>
					</view>
					<view class="detail-item">
						<text class="detail-label">发送对象</text>
						<text class="detail-value">{{getTargetTypeText(redpacketInfo.target_type)}}</text>
					</view>
					<view class="detail-item">
						<text class="detail-label">领取条件</text>
						<text class="detail-value">{{getConditionText(redpacketInfo.condition_type, redpacketInfo.condition_value)}}</text>
					</view>
					<view class="detail-item">
						<text class="detail-label">已领取金额</text>
						<text class="detail-value amount">¥{{parseFloat(redpacketInfo.received_amount).toFixed(2)}}</text>
					</view>
					<view class="detail-item">
						<text class="detail-label">剩余金额</text>
						<text class="detail-value amount">¥{{(parseFloat(redpacketInfo.total_amount) - parseFloat(redpacketInfo.received_amount)).toFixed(2)}}</text>
					</view>
					<view class="detail-item">
						<text class="detail-label">创建时间</text>
						<text class="detail-value">{{formatTime(redpacketInfo.create_time)}}</text>
					</view>
					<view class="detail-item" v-if="redpacketInfo.expire_time">
						<text class="detail-label">过期时间</text>
						<text class="detail-value">{{formatTime(redpacketInfo.expire_time)}}</text>
					</view>
				</view>
			</view>
			

			
			<!-- 领取记录 -->
			<view class="records-section">
				<view class="section-title">
					<uv-icon name="account" color="#ff934a" size="20"></uv-icon>
					<text class="title-text">领取记录</text>
					<text class="record-count">({{recordList.length}})</text>
				</view>
				
				<view class="record-list" v-if="recordList.length > 0">
					<view class="record-item" v-for="(record, index) in recordList" :key="record.id">
						<view class="record-info">
							<uv-avatar 
								:src="record.user_avatar || '/static/images/default-avatar.png'" 
								:text="(record.user_nickname || record.username || '').charAt(0)" 
								size="35" 
								shape="circle" 
								bgColor="#252525"
							></uv-avatar>
							<view class="record-details">
								<text class="record-name">{{record.user_nickname || record.username || '用户' + record.user_id}}</text>
								<text class="record-time">{{formatTime(record.create_time)}}</text>
							</view>
						</view>
						<view class="record-amount">
							<text class="amount-text">¥{{parseFloat(record.amount).toFixed(2)}}</text>
						</view>
					</view>
				</view>
				
				<!-- 空状态 -->
				<view class="empty-state" v-if="recordList.length === 0 && !loading">
					<uv-icon name="account" color="#666" size="60"></uv-icon>
					<text class="empty-text">暂无领取记录</text>
				</view>
			</view>
		</scroll-view>
		
		<!-- 加载状态 -->
		<view class="loading-state" v-if="loading">
			<uv-loading-icon mode="circle" color="#ff934a"></uv-loading-icon>
			<text class="loading-text">加载中...</text>
		</view>
	</view>
</template>

<script>
import authMixin from '@/mixins/auth.js';
import { getRedPacketDetail, getRedPacketRecords, cancelRedPacket } from '@/api/redpacket.js';

export default {
	mixins: [authMixin],
	data() {
		return {
				statusBarHeight: 0,
				redpacketId: '',
				redpacketInfo: null,
				recordList: [],
				loading: false
			}
	},
	onLoad(options) {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync();
		this.statusBarHeight = systemInfo.statusBarHeight || 0;
		
		if (options.id) {
			this.redpacketId = options.id;
			this.loadData();
		}
	},
	methods: {
		// 加载数据
		async loadData() {
			this.loading = true;
			
			try {
				// 并行加载红包详情和领取记录
				const [detailResponse, recordsResponse] = await Promise.all([
					getRedPacketDetail({ id: this.redpacketId }),
					getRedPacketRecords({ red_packet_id: this.redpacketId })
				]);
				
				if (detailResponse.code === 1) {
					this.redpacketInfo = detailResponse.data;
				} else {
					uni.showToast({
						title: detailResponse.msg || '加载红包详情失败',
						icon: 'none'
					});
				}
				
				if (recordsResponse.code === 1) {
					this.recordList = recordsResponse.data.data || [];
				} else {
					console.error('加载领取记录失败:', recordsResponse.msg);
				}
			} catch (error) {
				console.error('加载数据失败:', error);
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				this.loading = false;
			}
		},
		

		
		// 显示取消确认
		showCancelConfirm() {
			uni.showModal({
				title: '确认取消',
				content: '确定要取消这个红包吗？取消后无法恢复，剩余金额将退回账户。',
				confirmColor: '#ff934a',
				success: (res) => {
					if (res.confirm) {
						this.cancelRedPacket();
					}
				}
			});
		},
		
		// 取消红包
		async cancelRedPacket() {
			uni.showLoading({ title: '处理中...' });
			
			try {
				const response = await cancelRedPacket({ id: this.redpacketId });
				if (response.code === 1) {
					uni.showToast({
						title: '取消成功',
						icon: 'success'
					});
					this.loadData();
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
			} finally {
				uni.hideLoading();
			}
		},
		
		// 分享红包
		shareRedPacket() {
			// 构建分享内容
			const shareContent = `🧧 ${this.redpacketInfo.title}\n💰 总金额：¥${this.formatAmount(this.redpacketInfo.total_amount)}\n🎁 红包个数：${this.redpacketInfo.total_count}个\n${this.redpacketInfo.blessing ? '💌 ' + this.redpacketInfo.blessing : ''}`;
			
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
			this.loadData().finally(() => {
				uni.stopPullDownRefresh();
			});
		},
		
		// 计算进度
		calculateProgress() {
			if (!this.redpacketInfo || this.redpacketInfo.total_count === 0) {
				return 0;
			}
			return Math.round((this.redpacketInfo.received_count / this.redpacketInfo.total_count) * 100);
		},
		
		// 获取卡片样式类
		getCardClass(status) {
			return {
				'card-active': status === 'ACTIVE',
				'card-finished': status === 'FINISHED',
				'card-cancelled': status === 'CANCELLED',
				'card-expired': status === 'EXPIRED'
			};
		},
		
		// 获取类型文本
		getTypeText(type) {
			return type === 'RANDOM' ? '随机红包' : '固定红包';
		},
		
		// 获取过期文本
		getExpireText() {
			if (!this.redpacketInfo.expire_time || this.redpacketInfo.expire_time === 0) {
				return '永久有效';
			}
			
			const now = Date.now() / 1000;
			const expireTime = this.redpacketInfo.expire_time;
			const diff = expireTime - now;
			
			if (diff <= 0) {
				return '已过期';
			} else if (diff < 3600) {
				return `${Math.ceil(diff / 60)}分钟后过期`;
			} else if (diff < 86400) {
				return `${Math.ceil(diff / 3600)}小时后过期`;
			} else {
				return `${Math.ceil(diff / 86400)}天后过期`;
			}
		},
		
		// 是否有条件
		hasCondition() {
			return this.redpacketInfo && this.redpacketInfo.condition_type !== 'NONE';
		},
		
		// 格式化金额
		formatAmount(amount) {
			return parseFloat(amount).toFixed(2);
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
		
		// 获取发送对象文本
		getTargetTypeText(targetType) {
			const textMap = {
				0: '全部用户',
				1: '代理商',
				2: '普通用户'
			};
			return textMap[targetType] || '未知';
		},
		
		// 获取条件文本
		getConditionText(conditionType, conditionValue) {
			if (conditionType === 'NONE') {
				return '无条件';
			} else if (conditionType === 'MIN_BET') {
				return `今日最低投注 ¥${(parseFloat(conditionValue) || 0).toFixed(2)}`;
			}
			return '未知条件';
		},
		
		// 格式化时间
		formatTime(timestamp) {
			if (!timestamp) return '';
			const date = new Date(timestamp * 1000);
			
			// #ifdef APP-PLUS
			// App环境下使用标准格式
			const year = date.getFullYear();
			const month = String(date.getMonth() + 1).padStart(2, '0');
			const day = String(date.getDate()).padStart(2, '0');
			const hours = String(date.getHours()).padStart(2, '0');
			const minutes = String(date.getMinutes()).padStart(2, '0');
			const seconds = String(date.getSeconds()).padStart(2, '0');
			return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
			// #endif
			
			// #ifndef APP-PLUS
			// 其他环境使用toLocaleString
			return date.toLocaleString('zh-CN', {
				year: 'numeric',
				month: '2-digit',
				day: '2-digit',
				hour: '2-digit',
				minute: '2-digit',
				second: '2-digit',
				hour12: false
			});
			// #endif
		}
	}
}
</script>

<style scoped>
.container {
	background-color: #252525;
	min-height: 100vh;
}

.scroll-container {
	height: 100vh;
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
	/* #ifdef APP-PLUS */
	height: calc(100vh - env(safe-area-inset-top) - env(safe-area-inset-bottom));
	/* #endif */
	/* #ifdef MP */
	height: 100vh;
	/* #endif */
	/* #ifdef H5 */
	height: 100vh;
	/* #endif */
}

/* 红包信息卡片 */
.info-section {
	margin: 25rpx 25rpx 15rpx;
}

.redpacket-card {
	background: linear-gradient(135deg, #DC143C 0%, #B22222 50%, #8B0000 100%);
	border-radius: 55rpx 55rpx 0 0;
	padding: 30rpx 25rpx;
	box-shadow: 0 15rpx 35rpx rgba(220, 20, 60, 0.6), 0 8rpx 25rpx rgba(178, 34, 34, 0.4);
	position: relative;
	overflow: hidden;
}

.redpacket-card::before {
	content: '';
	position: absolute;
	top: -50%;
	right: -30%;
	width: 200%;
	height: 200%;
	background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.05) 50%, transparent 70%);
	pointer-events: none;
	animation: shimmer 3s ease-in-out infinite;
}

.redpacket-card::after {
	content: '';
	position: absolute;
	bottom: -20rpx;
	left: -20rpx;
	width: 120rpx;
	height: 120rpx;
	background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
	border-radius: 50%;
	pointer-events: none;
}

@keyframes shimmer {
	0%, 100% { transform: translateX(-10rpx) translateY(-10rpx); opacity: 0.8; }
	50% { transform: translateX(10rpx) translateY(10rpx); opacity: 1; }
}

.redpacket-card.card-finished {
	background: linear-gradient(135deg, #4a5568 0%, #2d3748 50%, #1a202c 100%);
	box-shadow: 0 20rpx 60rpx rgba(74, 85, 104, 0.4), 0 8rpx 25rpx rgba(45, 55, 72, 0.2);
}

.redpacket-card.card-cancelled {
	background: linear-gradient(135deg, #718096 0%, #4a5568 50%, #2d3748 100%);
	box-shadow: 0 20rpx 60rpx rgba(113, 128, 150, 0.4), 0 8rpx 25rpx rgba(74, 85, 104, 0.2);
}

.redpacket-card.card-expired {
	background: linear-gradient(135deg, #a0aec0 0%, #718096 50%, #4a5568 100%);
	box-shadow: 0 20rpx 60rpx rgba(160, 174, 192, 0.4), 0 8rpx 25rpx rgba(113, 128, 150, 0.2);
}

.card-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 30rpx;
	position: relative;
	z-index: 1;
}

.header-content {
	display: flex;
	align-items: center;
	flex: 1;
}

.redpacket-icon {
	width: 90rpx;
	height: 90rpx;
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 24rpx;
	backdrop-filter: blur(15rpx);
	border: 2rpx solid rgba(255, 255, 255, 0.3);
	box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.1);
}

.title-section {
	display: flex;
	flex-direction: column;
}

.redpacket-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #FFD700;
	margin-bottom: 8rpx;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.5);
	letter-spacing: 1rpx;
}

.redpacket-type {
	font-size: 22rpx;
	color: #FFD700;
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 215, 0, 0.1) 100%);
	padding: 6rpx 16rpx;
	border-radius: 16rpx;
	align-self: flex-start;
	backdrop-filter: blur(10rpx);
	border: 1rpx solid rgba(255, 215, 0, 0.3);
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.2);
	font-weight: 500;
}

.header-status {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	flex-shrink: 0;
}

.status-text {
	font-size: 24rpx;
	padding: 10rpx 20rpx;
	border-radius: 24rpx;
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.9) 0%, rgba(255, 215, 0, 0.8) 100%);
	color: #8B0000;
	margin-bottom: 10rpx;
	backdrop-filter: blur(15rpx);
	border: 1rpx solid rgba(255, 215, 0, 0.4);
	box-shadow: 0 4rpx 15rpx rgba(0, 0, 0, 0.2);
	font-weight: 600;
	text-shadow: none;
}

.create-time {
	font-size: 20rpx;
	color: rgba(255, 215, 0, 0.8);
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.blessing-section {
	display: flex;
	align-items: center;
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.08) 100%);
	border-radius: 55rpx 55rpx 0 0;
	padding: 24rpx;
	margin-bottom: 15rpx;
	position: relative;
	z-index: 1;
	backdrop-filter: blur(15rpx);
	border: 1rpx solid rgba(255, 255, 255, 0.2);
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.1);
}

.blessing-icon {
	font-size: 36rpx;
	margin-right: 20rpx;
	filter: drop-shadow(0 2rpx 4rpx rgba(0, 0, 0, 0.2));
}

.redpacket-blessing {
	font-size: 28rpx;
	color: #FFD700;
	flex: 1;
	line-height: 1.5;
	font-weight: 500;
	text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.4);
}

.amount-section {
	position: relative;
	z-index: 1;
	margin-bottom: 30rpx;
}

.amount-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 24rpx;
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.08) 100%);
	border-radius: 12rpx;
	backdrop-filter: blur(15rpx);
	border: 1rpx solid rgba(255, 255, 255, 0.2);
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.1);
}

.main-amount {
	margin-bottom: 15rpx;
}

.main-amount .amount-value {
	font-size: 52rpx;
	font-weight: bold;
	text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.3);
	letter-spacing: 1rpx;
}

.amount-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16rpx;
}

.amount-label {
	font-size: 22rpx;
	color: rgba(255, 215, 0, 0.9);
	margin-bottom: 8rpx;
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.amount-value {
	font-size: 30rpx;
	font-weight: 600;
	color: #FFD700;
	text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.4);
}

.amount-value.received {
	color: #90EE90;
	text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.4);
}

.amount-value.remaining {
	color: #FFD700;
	text-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.4);
}

.progress-section {
	position: relative;
	z-index: 1;
	margin-bottom: 30rpx;
}

.progress-info {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}

.progress-label {
	font-size: 24rpx;
	color: rgba(255, 215, 0, 0.9);
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.progress-text {
	font-size: 24rpx;
	font-weight: 600;
	color: #FFD700;
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.progress-stats {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 12rpx;
}

.stat-item {
	font-size: 20rpx;
	color: rgba(255, 215, 0, 0.8);
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.condition-section {
	background-color: rgba(255, 255, 255, 0.1);
	border-radius: 16rpx;
	padding: 20rpx;
	margin-bottom: 30rpx;
	position: relative;
	z-index: 1;
	backdrop-filter: blur(10rpx);
}

.condition-title {
	font-size: 22rpx;
	color: rgba(255, 215, 0, 0.9);
	margin-bottom: 8rpx;
	display: block;
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.condition-text {
	font-size: 26rpx;
	color: #FFD700;
	font-weight: 500;
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.action-buttons {
	position: relative;
	z-index: 1;
}

.button-row {
	display: flex;
	gap: 16rpx;
	flex-wrap: wrap;
}

.action-btn {
	flex: 1;
	min-width: 130rpx;
	height: 65rpx;
	line-height: 65rpx;
	text-align: center;
	font-size: 28rpx;
	font-weight: 500;
	border-radius: 40rpx;
	backdrop-filter: blur(15rpx);
	transition: all 0.3s ease;
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.15);
	text-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.2);
}

.cancel-btn {
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.25) 0%, rgba(255, 215, 0, 0.15) 100%);
	color: #FFD700;
	border: 2rpx solid rgba(255, 215, 0, 0.4);
}

.cancel-btn:active {
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 215, 0, 0.08) 100%);
	transform: translateY(2rpx);
}

.share-btn {
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.95) 0%, rgba(255, 215, 0, 0.85) 100%);
	color: #8B0000;
	border: 2rpx solid rgba(255, 215, 0, 0.6);
	font-weight: 600;
}

.share-btn:active {
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.85) 0%, rgba(255, 215, 0, 0.75) 100%);
	transform: translateY(2rpx);
}

.refresh-btn {
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 215, 0, 0.08) 100%);
	color: rgba(255, 215, 0, 0.9);
	border: 2rpx solid rgba(255, 215, 0, 0.3);
}

.refresh-btn:active {
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.08) 0%, rgba(255, 215, 0, 0.05) 100%);
	transform: translateY(2rpx);
}

/* 详细信息 */
.details-section {
	margin: 20rpx 25rpx;
	background-color: #1a1a1a;
	border-radius: 30rpx;
	padding: 30rpx;
	border: 1px solid #333;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.3);
}

.section-title {
	display: flex;
	align-items: center;
	margin-bottom: 30rpx;
}

.title-text {
	font-size: 28rpx;
	color: #e1e1e1;
	font-weight: 500;
	margin-left: 12rpx;
}

.record-count {
	font-size: 24rpx;
	color: #999;
	margin-left: 8rpx;
}

.detail-list {
	display: flex;
	flex-direction: column;
}

.detail-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx 0;
	border-bottom: 1px solid #333;
}

.detail-item:last-child {
	border-bottom: none;
}

.detail-label {
	font-size: 26rpx;
	color: #999;
}

.detail-value {
	font-size: 26rpx;
	color: #e1e1e1;
}

.detail-value.amount {
	color: #ff934a;
	font-weight: 500;
}



/* 领取记录 */
.records-section {
	margin: 20rpx 25rpx;
	background-color: #1a1a1a;
	border-radius: 30rpx;
	padding: 30rpx;
	border: 1px solid #333;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.3);
}

.record-list {
	display: flex;
	flex-direction: column;
}

.record-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx 0;
	border-bottom: 1px solid #333;
}

.record-item:last-child {
	border-bottom: none;
}

.record-info {
	display: flex;
	align-items: center;
	flex: 1;
	min-width: 0;
}

.record-details {
	margin-left: 20rpx;
	flex: 1;
	min-width: 0;
}

.record-name {
	font-size: 26rpx;
	color: #e1e1e1;
	margin-bottom: 4rpx;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.record-time {
	font-size: 22rpx;
	color: #666;
	display: block;
}

.record-amount {
	flex-shrink: 0;
	margin-left: 20rpx;
}

.amount-text {
	font-size: 26rpx;
	color: #ff934a;
	font-weight: 500;
}

/* 空状态 */
.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 80rpx 40rpx;
	text-align: center;
}

.empty-text {
	font-size: 28rpx;
	color: #666;
	margin-top: 24rpx;
}

/* 加载状态 */
.loading-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 40rpx;
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
}

.loading-text {
	font-size: 26rpx;
	color: #999;
	margin-top: 20rpx;
}
</style>