<template>
	<view class="share-container">
		<view class="content-wrapper">
			<!-- Tab切换区域 -->
			<!-- <view class="tab-section">
				<view class="tab-container">
					<view 
						class="tab-item" 
						:class="{ active: currentTab === 'agent' }"
						@click="switchTab('agent')"
					>
						<text class="tab-text">邀请代理商</text>
					</view>
					<view 
						class="tab-item" 
						:class="{ active: currentTab === 'user' }"
						@click="switchTab('user')"
					>
						<text class="tab-text">邀请用户</text>
					</view>
				</view>
			</view> -->
			
			<!-- 主要内容区域 -->
			<view class="main-content">
				<!-- 左侧二维码区域 -->
				<view class="qrcode-section">
					<view class="qrcode-wrapper">
						<uv-qrcode 
							ref="qrcode"
							:value="inviteUrl" 
							:size="170"
							:options="qrcodeOptions"
							@complete="onQrcodeComplete"
						></uv-qrcode>
					</view>
					<text class="qrcode-tip">扫码注册</text>
				</view>
				
				<!-- 右侧信息区域 -->
				<view class="info-section">
					<view class="invite-title">
						<text class="title-text">{{ currentTab === 'agent' ? '邀请代理商' : '邀请用户' }}</text>
						<text class="subtitle-text">{{ currentTab === 'agent' ? '共享收益机会' : '一起享受精彩' }}</text>
					</view>
					
					<!-- 邀请码 -->
					<view class="code-item">
						<text class="label">邀请码</text>
						<view class="code-row">
							<text class="code-value">{{ userInfo.invite_code || '加载中...' }}</text>
						</view>
						<view class="copy-btn" @click="copyInviteCode">复制</view>
					</view>
				</view>
			</view>			
			<!-- 邀请链接区域 -->
			<view class="link-section">
				<view class="link-header">
					<text class="link-label">邀请链接</text>
					<view class="copy-btn" @click="copyInviteLink">
						<text class="copy-text">复制</text>
					</view>
				</view>
				<text class="link-text">{{ inviteUrl }}</text>
			</view>
			
			<!-- 操作按钮 -->
			<view class="action-section">
				<view class="action-button save-btn" @click="saveQrcode">
					<uv-icon name="download" size="18" color="#ffffff"></uv-icon>
					<text class="button-text">保存二维码</text>
				</view>
				<view class="action-button share-btn" @click="shareToFriends">
					<uv-icon name="share" size="18" color="#ffffff"></uv-icon>
					<text class="button-text">分享好友</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { getUserInfo } from '@/api/user.js';
import config from '@/utils/config.js';

export default {
	data() {
		return {
			currentTab: 'user', // 当前选中的tab，默认为代理商
			userInfo: {
				invite_code: ''
			},
			inviteUrl: '',
			baseUrl: '', // 邀请注册链接
			qrcodeOptions: {
				size: 200,
				margin: 8,
				colorDark: '#000000',
				colorLight: '#ffffff',
				correctLevel: 'M'
			}
		}
	},
	methods: {
		// 切换tab
		switchTab(tab) {
			this.currentTab = tab;
			this.updateInviteUrl();
		},
		
		// 更新邀请链接
		updateInviteUrl() {
			if (this.userInfo.invite_code) {
				const type = this.currentTab; // 'agent' 或 'user'
				const appConfig = config.getConfig();
				// 从API地址提取域名，构建注册链接
				const apiUrl = appConfig.baseURL;
				const baseHost = apiUrl.replace('/api', ''); // 移除/api后缀
				this.baseUrl = `${baseHost}/register?code=`;
				// this.baseUrl = `${baseHost}/register?type=${type}&code=`;
				this.inviteUrl = this.baseUrl + this.userInfo.invite_code;
			}
		},
		
		// 获取用户信息
		async fetchUserInfo() {
			try {
				const response = await getUserInfo();
				if (response.code === 1) {
					this.userInfo = response.data;
					// 生成邀请链接
					this.updateInviteUrl();
				} else {
					uni.showToast({
						title: '获取用户信息失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('获取用户信息失败:', error);
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			}
		},
		
		// 复制邀请码
		copyInviteCode() {
			if (!this.userInfo.invite_code) {
				uni.showToast({
					title: '邀请码未加载',
					icon: 'none'
				});
				return;
			}
			
			uni.setClipboardData({
				data: this.userInfo.invite_code,
				success: () => {
					uni.showToast({
						title: '邀请码已复制',
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
		
		// 复制邀请链接
		copyInviteLink() {
			if (!this.inviteUrl) {
				uni.showToast({
					title: '邀请链接未生成',
					icon: 'none'
				});
				return;
			}
			
			uni.setClipboardData({
				data: this.inviteUrl,
				success: () => {
					uni.showToast({
						title: '邀请链接已复制',
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
		
		// 保存二维码
		saveQrcode() {
			if (!this.$refs.qrcode) {
				uni.showToast({
					title: '二维码未生成',
					icon: 'none'
				});
				return;
			}
			
			this.$refs.qrcode.save({
				success: (res) => {
					uni.showToast({
						title: '保存到相册成功',
						icon: 'success'
					});
				},
				fail: (err) => {
					console.error('保存二维码失败:', err);
					uni.showToast({
						title: '保存失败，请检查权限',
						icon: 'none'
					});
				}
			});
		},
		
		// 分享给好友
		shareToFriends() {
			uni.share({
				provider: 'weixin',
				scene: 'WXSceneSession',
				type: 0,
				href: this.inviteUrl,
				title: '邀请您注册',
				summary: `我的邀请码：${this.userInfo.invite_code}，快来一起体验吧！`,
				imageUrl: '',
				success: (res) => {
					console.log('分享成功:', res);
				},
				fail: (err) => {
					console.error('分享失败:', err);
					// 如果分享失败，则复制链接
					this.copyInviteLink();
				}
			});
		},
		
		// 二维码生成完成
		onQrcodeComplete(e) {
			console.log('二维码生成完成:', e);
		},
		
		// 返回上一页
		goBack() {
			uni.navigateBack();
		}
	},
	onLoad() {
		// 页面加载时获取用户信息
		this.fetchUserInfo();
	},
	
	// 下拉刷新
	onPullDownRefresh() {
		this.fetchUserInfo().finally(() => {
			uni.stopPullDownRefresh();
		});
	}
}
</script>

<style scoped lang="scss">
.content-wrapper{background:#f8f9fa;padding:25rpx;}

.tab-section {
	margin-bottom: 20rpx;
	
	.tab-container {
		display: flex;
		background-color: #e9ecef;
		border-radius: 25rpx;
		padding: 6rpx;
		gap: 6rpx;
		
		.tab-item {
			flex: 1;
			height: 60rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 20rpx;
			transition: all 0.3s ease;
			cursor: pointer;
			
			.tab-text {
			font-size: 26rpx;
			font-weight: 500;
			color: #666;
			transition: color 0.3s ease;
		}
			
			&.active {
				background: linear-gradient(135deg, #ff7c4d, #ff5722);
				box-shadow: 0 2rpx 8rpx rgba(255, 124, 77, 0.3);
				
				.tab-text {
					color: #ffffff;
					font-weight: 600;
				}
			}
			
			&:active {
				transform: scale(0.95);
			}
		}
	}
}

.main-content {
	display: flex;
	align-items: flex-start;
	gap: 20rpx;
	padding: 30rpx 20rpx;
}

.qrcode-section {
	flex-shrink: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	
	.qrcode-wrapper {
		background-color: #ffffff;
		padding: 15rpx;
		margin-bottom: 15rpx;
		box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.2);
	}
	
	.qrcode-tip {
		color: #666;
		font-size: 24rpx;
		text-align: center;
	}
}

.info-section {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 20rpx;
	
	.invite-title {
		.title-text {
			color: #333;
			font-size: 32rpx;
			font-weight: bold;
			display: block;
			margin-bottom: 8rpx;
		}
		
		.subtitle-text {
			color: #666;
			font-size: 24rpx;
			display: block;
		}
	}
	
	.code-item {
		.label {
			color: #666;
			font-size: 24rpx;
			margin-bottom: 10rpx;
			display: block;
		}
		
		.code-row {
			display: flex;
			align-items: center;
			justify-content: space-between;
			background-color: #fff;
			border: 1rpx solid #e9ecef;
			border-radius: 12rpx;
			padding: 15rpx;
			
			.code-value {
				color: #333;
				font-size: 26rpx;
				font-weight: bold;
				flex: 1;
				margin-right: 10rpx;
			}
		}
	}
}

.link-section {
	background-color: #fff;
	border: 1rpx solid #e9ecef;
	border-radius: 45rpx;
	padding: 20rpx;
	margin-bottom: 15rpx;
	
	.link-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 15rpx;
		
		.link-label {
			color: #666;
			font-size: 24rpx;
			font-weight: 500;
		}
	}
	
	.link-text {
		color: #333;
		font-size: 24rpx;
		word-break: break-all;
		line-height: 1.4;
		border-radius: 12rpx;
		padding: 15rpx;
		display: block;
	}
}

.copy-btn {
	display: flex;
	align-items: center;
	gap: 8rpx;
	background-color: rgba(255, 124, 77, 0.1);
	border: 1rpx solid #ff7c4d;
	border-radius: 12rpx;
	width:65rpx;
	color:#ff7c4d;
	text-align:center;
	padding: 8rpx 12rpx;
	transition: all 0.3s ease;
	
	.copy-text {
		color: #ff7c4d;
		font-size: 25rpx;
		font-weight: 500;
	}
	
	&:active {
		transform: scale(0.95);
		background-color: rgba(255, 124, 77, 0.2);
	}
}

.action-section {
	padding: 0 20rpx 20rpx;
	display: flex;
	gap: 15rpx;
	
	.action-button {
		flex: 1;
		height: 70rpx;
		border-radius: 35rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10rpx;
		transition: all 0.3s ease;
		
		.button-text {
			color: #ffffff;
			font-size: 24rpx;
			font-weight: 600;
		}
		
		&.save-btn {
			background: linear-gradient(135deg, #666, #555);
			box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.2);
		}
		
		&.share-btn {
			background: linear-gradient(135deg, #ff7c4d, #ff5722);
			box-shadow: 0 2rpx 8rpx rgba(255, 124, 77, 0.3);
		}
		
		&:active {
			transform: scale(0.95);
		}
	}
}
</style>