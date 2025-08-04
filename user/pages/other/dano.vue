<template>
	<view class="dano-container">
		<view class="content-wrapper">
			<!-- 加载状态 -->
			<view v-if="loading" class="loading-container">
				<uv-loading-icon mode="flower" color="#3c9cff" size="40"></uv-loading-icon>
				<text class="loading-text">加载中...</text>
			</view>
			
			<!-- 空数据状态 -->
			<view v-else-if="announceList.length === 0" class="empty-container">
				<uv-icon name="file-text" size="60" color="#666"></uv-icon>
				<text class="empty-text">暂无公告</text>
			</view>
			
			<!-- 公告列表 -->
			<uv-vtabs 
				v-else
				:list="announceList" 
				:current="current" 
				@change="tabChange"
				:chain="false"
				bar-width="200rpx"
				bar-bg-color="#2a2a2a"
				:bar-item-style="{
					padding: '12rpx 8rpx',
					borderBottom: '1px solid #2a2a2a',
					fontSize: '28rpx',
					textAlign: 'center',
					color: '#c5c5c5'
				}"
				:bar-item-active-style="{
					backgroundColor: '#3c9cff',
					textAlign: 'center',
					fontWeight: 'bold',
					fontSize: '28rpx',
					color: '#e1e1e1',
					padding: '12rpx 8rpx',
				}"
				:content-style="{
					backgroundColor: '#000',
				}"
			>
				<uv-vtabs-item>
					<view class="announce-detail">
						<view class="announce-content">
							<text class="content-text">{{ announceList[current] && announceList[current].content }}</text>
						</view>
						<view class="announce-footer" v-if="announceList[current] && announceList[current].attachment">
							<view class="attachment-section">
								<uv-icon name="attachment" size="16" color="#ff7c4d"></uv-icon>
								<text class="attachment-text">{{ announceList[current].attachment }}</text>
							</view>
						</view>
					</view>
				</uv-vtabs-item>
			</uv-vtabs>
		</view>
	</view>
</template>

<script>
import { getDanoList } from '@/api/other.js'

export default {
	data() {
		return {
			current: 0,
			announceList: [],
			loading: false
		}
	},
	methods: {
		tabChange(index) {
			this.current = index;
			console.log('切换到公告：', this.announceList[index].name);
		},
		
		// 获取公告列表
		async loadDanoList() {
			try {
				this.loading = true;
				const res = await getDanoList();
				if (res.code === 1 && res.data) {
					this.announceList = res.data;
					if (this.announceList.length > 0) {
						this.current = 0;
					}
				} else {
					uni.showToast({
						title: res.msg || '获取公告失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('获取公告列表失败：', error);
				uni.showToast({
					title: '网络错误，请稍后重试',
					icon: 'none'
				});
			} finally {
				this.loading = false;
			}
		}
	},
	
	onLoad() {
		console.log('平台公告页面加载完成');
		this.loadDanoList();
	}
}
</script>

<style scoped lang="scss">
.content-wrapper {
	
}
.dano-container {
	background: linear-gradient(180deg, #000 0%, #1a1a1a 100%);
	color: #e1e1e1;
	min-height: 100vh;
}

// 加载状态样式
.loading-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 0;
	
	.loading-text {
		margin-top: 20rpx;
		color: #999;
		font-size: 28rpx;
	}
}

// 空数据状态样式
.empty-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 0;
	
	.empty-text {
		margin-top: 20rpx;
		color: #666;
		font-size: 28rpx;
	}
}

.announce-detail {
	background:#252525;
	padding: 20rpx;
}

.announce-header {
	margin-bottom: 40rpx;
	padding-bottom: 30rpx;
	border-bottom: 2rpx solid #2a2a2a;
	
	.announce-title {
		display: block;
		color: #ff7c4d;
		font-size: 36rpx;
		font-weight: bold;
		margin-bottom: 20rpx;
		line-height: 1.4;
	}
	
	.announce-date {
		display: block;
		color: #999;
		font-size: 24rpx;
	}
}

.announce-content {
	margin-bottom: 40rpx;
	
	.content-text {
		display: block;
		color: #e0e0e0;
		font-size: 28rpx;
		line-height: 2;
		text-align: justify;
		word-break: break-word;
	}
}

.announce-footer {
	padding-top: 30rpx;
	border-top: 1rpx solid #2a2a2a;
	
	.attachment-section {
		display: flex;
		align-items: center;
		gap: 16rpx;
		padding: 20rpx;
		background: linear-gradient(135deg, #252525, #1a1a1a);
		border-radius: 16rpx;
		border: 1rpx solid #333;
		transition: all 0.3s ease;
		
		&:active {
			transform: scale(0.98);
			background: linear-gradient(135deg, #2a2a2a, #1f1f1f);
		}
		
		.attachment-text {
			color: #ff7c4d;
			font-size: 26rpx;
			font-weight: 500;
		}
	}
}

/* 自定义vtabs样式 */
:deep(.uv-vtabs) {
	.uv-vtabs__bar {
		background-color: #1a1a1a !important;
		border-right: 2rpx solid #2a2a2a;
	}
	
	.uv-vtabs__bar__item {
		transition: all 0.3s ease;
		font-size: 26rpx;
		
		&:hover {
			background-color: #252525 !important;
		}
	}
	
	.uv-vtabs__bar__item--active {
		position: relative;
		
		&::before {
			content: '';
			position: absolute;
			left: 0;
			top: 50%;
			transform: translateY(-50%);
			width: 4rpx;
			height: 60%;
			background: linear-gradient(180deg, #ff7c4d, #ff5722);
			border-radius: 0 4rpx 4rpx 0;
		}
	}
	
	.uv-vtabs__content {
		background-color: #000 !important;
	}
}
</style>