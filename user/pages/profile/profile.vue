<template>
	<view class="profile-container">
		<view class="scroll-container">
			<!-- 用户信息区域 -->
			<view class="user-info">
			<view class="avatar-section">
				<view class="user-basic-info" @click="openSettings">
					<view class="user-info-row">
						<uv-avatar src="/static/images/avatar.jpg" size="55" shape="circle"></uv-avatar>
						<text class="username">{{ userInfo.nickname || userInfo.username }}</text>
						<text>#{{userInfo.id}}</text>
					</view>
				</view>
				<uv-tags 
					:text="userInfo.is_verified === 1 ? '已认证' : userInfo.is_verified === 2 ? '审核中' : '未认证'" 
					:type="userInfo.is_verified === 1 ? 'success' : userInfo.is_verified === 2 ? 'warning' : 'error'" 
					plain 
					shape="circle">
				</uv-tags>
				<uv-icon name="setting" size="20" color="#333" @click="openSettings"></uv-icon>
			</view>
			
			<!-- 会员等级信息 -->
			<view class="level-info" v-if="userInfo.level_info">
				<view class="level-badge">
					<text class="level-name">{{ userInfo.level_info.name }}</text>
					<text class="level-number">LV.{{ userInfo.level_info.level }}</text>
				</view>
				<view class="level-progress" v-if="userInfo.next_level">
					<view class="progress-text">
						<text class="progress-label">升级进度</text>
						<text class="progress-value">{{ userInfo.upgrade_progress }}%</text>
					</view>
					<view class="progress-bar">
						<view class="progress-fill" :style="{ width: userInfo.upgrade_progress + '%' }"></view>
					</view>
					<text class="next-level-text">下一等级：{{ userInfo.next_level.name }}</text>
				</view>
			</view>
			
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
			
		<view class="button-container">
			<view 
				v-for="(item, index) in list" 
				:key="index"
				:class="['action-button', index === 0 ? 'orange-button' : 'blue-button']"
				@click="change(index)"
			>
				<text class="button-text">{{ item }}</text>
			</view>
		</view>

		<view class="grid-container">
			<view class="grid-title">常用功能</view>
			<uv-grid :col="4" @click="toDetail">
				<uv-grid-item v-for="(item,index) in baseList" :key="index" class="grid-item">
					<uv-icon :name="item.name" :size="28" color="#333" class="grid-icon"></uv-icon>
					<text class="grid-text">{{item.title}}</text>
				</uv-grid-item>
			</uv-grid>
		</view>
		
		<!-- 设置列表 -->
		<view class="list-container" v-if="settingsList.length > 0">
			<view class="list-item" v-for="(item, index) in settingsList" :key="index" @click="handleListClick(item)">
				<text class="list-text">{{ item.title }}</text>
				<uv-icon name="arrow-right" size="16" color="#666"></uv-icon>
			</view>
			</view>
		</view>
	</view>
	
	<!-- 微信红包样式弹窗 -->
	<view v-if="showRedPacket" class="red-packet-overlay" @click="closeRedPacket">
		<view class="red-packet-container" @click.stop>
			<view class="red-packet-header">
				<view class="red-packet-close" @click="closeRedPacket">×</view>
				<view class="red-packet-avatar">
					<image src="/static/images/logo.png" class="avatar-img"></image>
				</view>
				<view class="red-packet-sender" v-if="currentRedPacket.agent_id > 0">代理红包</view>
				<view class="red-packet-sender" v-else>BNB平台红包</view>
				<view class="red-packet-title">{{ currentRedPacket.title || '恭喜发财，大吉大利' }}</view>
			</view>
			
			<view class="red-packet-body">
				<view class="red-packet-envelope" :class="{ 'opened': redPacketOpened }">
					<view class="envelope-top"></view>
					<view class="envelope-bottom">
						<view v-if="!redPacketOpened" class="open-button" @click="openRedPacket">
							<text class="open-text">开</text>
						</view>
						<view v-else class="red-packet-amount">
							<text class="amount-text">{{ claimedAmount }}</text>
							<text class="amount-unit">元</text>
						</view>
					</view>
				</view>
				
				<view v-if="redPacketOpened" class="red-packet-blessing">
					<text class="blessing-text">{{ currentRedPacket.blessing || '恭喜发财，大吉大利！' }}</text>
				</view>
			</view>
			
			<view class="red-packet-footer">
				<text class="footer-text" v-if="currentRedPacket.agent_id > 0">红包来自代理商</text>
				<text class="footer-text" v-else>红包来自BNB平台</text>
			</view>
		</view>
	</view>
</template>

<script>
	import { getUserInfo } from '@/api/user.js';
	import { getAvailableRedPackets, claimRedPacket } from '@/api/redpacket.js';
	export default {
		data() {
			return {
				list: ['充值', '提现'],
			current: 0,
			baseList: [{
			name: 'red-packet',
			title: '我的红包'
		}, {
			name: 'chat',
			title: '平台公告'
		}, 
		{
			name: 'kefu-ermai',
			title: '在线客服'
		}, 
		{
			name: 'coupon',
			title: '资金变动'
		}],
			settingsList: [
			// {
			// 	title: '在线客服',
			// 	key: 'kefu'
			// }
			],
			userInfo: {
			avatar: '/static/images/avatar.jpg',
			username: '加载中...',
			id: '',
			is_agent: 0
		},
		statsData: [
			{ count: '0.00', label: '余额' },
			{ count: '0.00', label: '返佣' }
		],
			currentTab: 0,
			// 修改密码相关
			showPasswordModal: false,
			passwordForm: {
				oldPassword: '',
				newPassword: '',
				confirmPassword: ''
			},
			// 修改支付密码相关
			showPayPasswordModal: false,
			payPasswordForm: {
				oldPayPassword: '',
				newPayPassword: '',
				confirmPayPassword: ''
			},
			// 红包弹窗相关
			showRedPacket: false,
			redPacketOpened: false,
			currentRedPacket: {},
			claimedAmount: '0.00',
			availableRedPackets: []
			}
		},
		methods: {
			toDetail(name){
				console.log('点击了：'+name);
				// 根据name进行不同的跳转
				switch(name) {
					case 0:
						// 跳转到我的红包页面
						uni.navigateTo({
							url: '/pages/users/redpacket'
						});
						break;
					case 1:
					// 平台公告
					uni.navigateTo({
						url: '/pages/other/dano'
					});
					break;
					case 2:
					// 邀请会员
					uni.navigateTo({
						url: '/pages/users/share'
					});
					break;
					case 3:
						uni.navigateTo({
							url: '/pages/users/moneylog'
						});
						break;
					default:
						break;
				}
			},
			openSettings() {
				console.log('打开设置');
				// 跳转到设置页面
				uni.navigateTo({
					url: '/pages/users/settings/settings'
				});
			},
			change(index) {
				this.current = index;
				// 根据选择的tab进行跳转
				if (index === 0) {
					// 充值
					uni.navigateTo({
						url: '/pages/users/charge'
					});
				} else if (index === 1) {
					// 提现
					uni.navigateTo({
						url: '/pages/users/withdraw'
					});
				}
			},
			handleListClick(item) {
				console.log('点击了列表项：', item.title);
				// 根据item.key进行不同的跳转或操作
				switch(item.key) {
				case 'logout':
					// 退出登录
					this.logout();
					break;
				default:
				break;
		}
		},
		// 获取用户信息的通用方法
		async fetchUserInfo() {
			// 先从本地存储获取用户基本信息
			const localUserInfo = uni.getStorageSync('userInfo');
			if (localUserInfo) {
				this.userInfo = {
					...this.userInfo,
					...localUserInfo
				};
			}
			
			// 获取代理商详细信息
			const agentInfoResponse = await getUserInfo();
			if (agentInfoResponse.code === 1) {
				this.userInfo = {
					...this.userInfo,
					...agentInfoResponse.data
				};
				
				// 更新统计数据中的余额
				this.statsData = [
					{ count: agentInfoResponse.data.money || '0.00', label: '余额' },
					{ count: '0.00', label: '返佣' }
				];
			}
		},
		
		// 获取代理商信息
		async loadAgentInfo() {
			try {
				await this.fetchUserInfo();
			} catch (error) {
				console.error('获取代理商信息失败:', error);
				uni.showToast({
					title: '获取信息失败',
					icon: 'none'
				});
			}
		},
		
		// 下拉刷新
		async onPullDownRefresh() {
			try {
				await this.fetchUserInfo();
			} catch (error) {
				console.error('刷新失败:', error);
			} finally {
				uni.stopPullDownRefresh();
			}
		},
		
		// 退出登录
		logout() {
			uni.showModal({
				title: '提示',
				content: '确定要退出登录吗？',
				success: (res) => {
					if (res.confirm) {
						// 清除本地存储的用户信息和token
						uni.removeStorageSync('ba-user-token');
						uni.removeStorageSync('userInfo');
						
						uni.showToast({
							title: '已退出登录',
							icon: 'success'
						});
						
						// 跳转到登录页面
						setTimeout(() => {
							uni.reLaunch({
								url: '/pages/users/login'
							});
						}, 1500);
					}
				}
			});
		},
		
		// 检查可领取的红包
		async checkAvailableRedPacket() {
			try {
				const response = await getAvailableRedPackets();
				if (response.code === 1 && response.data && response.data.length > 0) {
					this.availableRedPackets = response.data;
					// 显示第一个可领取的红包
					this.showRedPacketModal(response.data[0]);
				}
			} catch (error) {
				console.error('检查红包失败：', error);
			}
		},
		
		// 显示红包领取弹窗
		showRedPacketModal(redPacketData) {
			this.currentRedPacket = redPacketData;
			this.showRedPacket = true;
			this.redPacketOpened = false;
			this.claimedAmount = '0.00';
		},
		
		// 关闭红包弹窗
		closeRedPacket() {
			this.showRedPacket = false;
			this.redPacketOpened = false;
			this.currentRedPacket = {};
			this.claimedAmount = '0.00';
		},
		
		// 打开红包
		async openRedPacket() {
			try {
				const response = await claimRedPacket(this.currentRedPacket.id);
				
				if (response.code === 1) {
					this.redPacketOpened = true;
					this.claimedAmount = response.data.amount;
					
					// 延迟关闭当前红包并检查下一个
					setTimeout(() => {
						this.closeRedPacket();
						// 刷新用户信息和余额
						this.loadAgentInfo();
						
						// 延迟检查是否还有其他可领取的红包
						setTimeout(() => {
							this.checkAvailableRedPacket();
						}, 1000);
					}, 3000);
				} else {
					uni.showToast({
						title: response.msg || '领取失败',
						icon: 'none'
					});
				}
			} catch (error) {
				if (error && error.msg) {
					// 服务器返回的业务错误，显示具体错误信息
					uni.showToast({
						title: error.msg,
						icon: 'none',
						duration: 3000
					});
				} else {
					// 真正的网络错误
					uni.showToast({
						title: '领取失败，请重试',
						icon: 'none',
						duration: 3000
					});
				}
			}
		}
		},
		onLoad() {
			// 页面加载时获取代理商信息
			this.loadAgentInfo();
		},
		
		// 页面显示时检查红包
		onShow() {
			this.checkAvailableRedPacket();
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 重新加载用户信息和统计数据
			this.loadAgentInfo().finally(() => {
				uni.stopPullDownRefresh();
			});
		}
	}
</script>

<style scoped lang="scss">

	.profile-container {
		background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
		padding-bottom:25rpx;
		color: #333333;
	}
	
	.user-info {
		padding: 40rpx 20rpx 20rpx 20rpx;
		
		.avatar-section {
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 30rpx;
			
			.user-basic-info {
				display: flex;
				flex-direction: column;
				align-items: center;
				
				.user-id {
					color: #999;
					font-size: 24rpx;
					margin-bottom: 10rpx;
				}
				
				.user-info-row {
					padding-left:10px;
					display: flex;
					flex-direction: row;
					align-items: center;
					gap: 15rpx;
				}
				
				.username {
					color: #333333;
					font-size: 32rpx;
					font-weight: bold;
				}
			}
		}
		
		/* 会员等级样式 */
	.level-info {
		margin: 20rpx 0;
		padding: 25rpx;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border-radius: 15rpx;
		color: #fff;
	}
	
	.level-badge {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 15rpx;
	}
	
	.level-name {
		font-size: 32rpx;
		font-weight: bold;
		color: #fff;
	}
	
	.level-number {
		font-size: 24rpx;
		background: rgba(255, 255, 255, 0.2);
		padding: 8rpx 16rpx;
		border-radius: 20rpx;
		color: #fff;
	}
	
	.level-progress {
		margin-top: 15rpx;
	}
	
	.progress-text {
		display: flex;
		justify-content: space-between;
		margin-bottom: 10rpx;
	}
	
	.progress-label {
		font-size: 26rpx;
		color: rgba(255, 255, 255, 0.8);
	}
	
	.progress-value {
		font-size: 26rpx;
		font-weight: bold;
		color: #fff;
	}
	
	.progress-bar {
		height: 8rpx;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 4rpx;
		overflow: hidden;
		margin-bottom: 10rpx;
	}
	
	.progress-fill {
		height: 100%;
		background: linear-gradient(90deg, #ffd700, #ffed4e);
		border-radius: 4rpx;
		transition: width 0.3s ease;
	}
	
	.next-level-text {
		font-size: 24rpx;
		color: rgba(255, 255, 255, 0.7);
	}
	
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
						color: rgb(255, 124, 77);
						font-size: 30rpx;
						font-weight: bold;
						margin-bottom: 10rpx;
					}
					
					.stat-label {
						color: #333;
						font-size: 28rpx;
					}
				}
			}
		}
		
		.action-buttons {
			display: flex;
			justify-content: center;
			margin-bottom: 60rpx;
		}
		
		.tab-section {
			margin-bottom: 40rpx;
		}
	}
	
	.grid-container {
		padding: 0 16rpx;
		margin: 20rpx 0;
		background-color: #ffffff;
		border-radius: 38rpx;
		padding: 24rpx 16rpx;
		margin: 16rpx;
		border: 1px solid #e0e0e0;
	}
	
	.grid-title {
		color: #333333;
		font-size: 28rpx;
		font-weight: 600;
		margin-bottom: 30rpx;
		padding-left: 15rpx;
	}
	
	.grid-item {
		background-color: #f8f9fa !important;
		padding: 30rpx 20rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		transition: all 0.3s ease;
	}
	
	.grid-item:active {
		transform: scale(0.95);
		background-color: #e9ecef !important;
	}
	
	.grid-icon {
		margin-bottom: 16rpx;
		transition: transform 0.2s ease;
	}
	
	.grid-item:active .grid-icon {
		transform: scale(1.1);
	}
	
	.grid-text {
		color: #333333;
		font-size: 24rpx;
		text-align: center;
		font-weight: 500;
	}

	.button-container {
		padding: 12rpx 25rpx;
		display: flex;
		gap: 20rpx;
	}
	
	.action-button {
		flex: 1;
		height: 88rpx;
		border-radius: 15rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.3s ease;
		cursor: pointer;
	}
	
	.orange-button {
		background: linear-gradient(135deg, #ff7c4d, #ff5722);
		box-shadow: 0 4rpx 12rpx rgba(255, 124, 77, 0.3);
	}
	
	.orange-button:active {
		transform: scale(0.95);
		box-shadow: 0 2rpx 8rpx rgba(255, 124, 77, 0.4);
	}
	
	.blue-button {
		background: linear-gradient(135deg, #4d9eff, #2196f3);
		box-shadow: 0 4rpx 12rpx rgba(77, 158, 255, 0.3);
	}
	
	.blue-button:active {
		transform: scale(0.95);
		box-shadow: 0 2rpx 8rpx rgba(77, 158, 255, 0.4);
	}
	
	.button-text {
		color: #ffffff;
		font-size: 28rpx;
		font-weight: 600;
	}
	
	.list-container {
		background-color: #ffffff;
		border-radius: 38rpx;
		margin: 16rpx;
		padding: 8rpx 0;
		overflow: hidden;
		border: 1px solid #e0e0e0;
	}
	
	.list-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 32rpx 40rpx;
		background-color: #ffffff;
		border-bottom: 1px solid #e0e0e0;
		transition: background-color 0.2s ease;
		cursor: pointer;
	}
	
	.list-item:last-child {
		border-bottom: none;
	}
	
	.list-item:active {
		background-color: #f8f9fa;
	}
	
	.list-text {
		color: #333333;
		font-size: 28rpx;
		font-weight: 500;
		flex: 1;
	}
	
	/* 红包弹窗样式 */
	.red-packet-overlay {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: rgba(0, 0, 0, 0.8);
		z-index: 996;
		display: flex;
		align-items: center;
		justify-content: center;
		animation: fadeIn 0.3s ease-in-out;
	}
	
	.red-packet-container {
		width: 600rpx;
		background: linear-gradient(135deg, #ff6b6b, #ee5a24);
		border-radius: 20rpx;
		position: relative;
		overflow: hidden;
		animation: slideUp 0.4s ease-out;
		box-shadow: 0 20rpx 60rpx rgba(255, 107, 107, 0.3);
	}
	
	.red-packet-header {
		padding: 60rpx 40rpx 40rpx;
		text-align: center;
		position: relative;
	}
	
	.red-packet-close {
		position: absolute;
		top: 20rpx;
		right: 30rpx;
		width: 60rpx;
		height: 60rpx;
		color: rgba(255, 255, 255, 0.8);
		font-size: 40rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 50%;
		transition: all 0.2s ease;
	}
	
	.red-packet-close:active {
		background: rgba(255, 255, 255, 0.1);
		transform: scale(0.9);
	}
	
	.red-packet-avatar {
		width: 120rpx;
		height: 120rpx;
		border-radius: 50%;
		margin: 0 auto 20rpx;
		border: 4rpx solid rgba(255, 255, 255, 0.3);
		overflow: hidden;
	}
	
	.avatar-img {
		width: 100%;
		height: 100%;
		border-radius: 50%;
	}
	
	.red-packet-sender {
		color: rgba(255, 255, 255, 0.9);
		font-size: 28rpx;
		margin-bottom: 10rpx;
	}
	
	.red-packet-title {
		color: #fff;
		font-size: 32rpx;
		font-weight: bold;
		line-height: 1.4;
	}
	
	.red-packet-body {
		padding: 40rpx;
		text-align: center;
	}
	
	.red-packet-envelope {
		position: relative;
		width: 300rpx;
		height: 300rpx;
		margin: 0 auto;
		transition: all 0.5s ease;
	}
	
	.envelope-top {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 50%;
		background: linear-gradient(135deg, #ffd700, #ffb347);
		border-radius: 150rpx 150rpx 0 0;
		border: 6rpx solid #fff;
		box-sizing: border-box;
		transition: all 0.5s ease;
	}
	
	.envelope-bottom {
		position: absolute;
		bottom: 0;
		left: 0;
		width: 100%;
		height: 50%;
		background: linear-gradient(135deg, #ff6b6b, #ee5a24);
		border-radius: 0 0 150rpx 150rpx;
		border: 6rpx solid #fff;
		box-sizing: border-box;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.5s ease;
	}
	
	.red-packet-envelope.opened .envelope-top {
		transform: translateY(-20rpx) rotateX(-15deg);
	}
	
	.red-packet-envelope.opened .envelope-bottom {
		transform: translateY(20rpx);
	}
	
	.open-button {
		width: 120rpx;
		height: 120rpx;
		background: linear-gradient(135deg, #ffd700, #ffb347);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 4rpx solid #fff;
		box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.2);
		transition: all 0.2s ease;
		animation: pulse 2s infinite;
	}
	
	.open-button:active {
		transform: scale(0.95);
	}
	
	.open-text {
		color: #d4af37;
		font-size: 48rpx;
		font-weight: bold;
		text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.1);
	}
	
	.red-packet-amount {
		display: flex;
		flex-direction: column;
		align-items: center;
		animation: bounceIn 0.6s ease-out;
	}
	
	.amount-text {
		color: #ffd700;
		font-size: 72rpx;
		font-weight: bold;
		text-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.2);
		margin-bottom: 10rpx;
	}
	
	.amount-unit {
		color: #fff;
		font-size: 28rpx;
		opacity: 0.9;
	}
	
	.red-packet-blessing {
		margin-top: 40rpx;
		padding: 30rpx;
		background: rgba(255, 255, 255, 0.1);
		border-radius: 15rpx;
		animation: fadeInUp 0.5s ease-out 0.3s both;
	}
	
	.blessing-text {
		color: #fff;
		font-size: 28rpx;
		line-height: 1.5;
		opacity: 0.9;
	}
	
	.red-packet-footer {
		padding: 30rpx;
		text-align: center;
		border-top: 1rpx solid rgba(255, 255, 255, 0.1);
	}
	
	.footer-text {
		color: rgba(255, 255, 255, 0.7);
		font-size: 24rpx;
	}
	
	/* 动画效果 */
	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}
	
	@keyframes slideUp {
		from {
			transform: translateY(100rpx);
			opacity: 0;
		}
		to {
			transform: translateY(0);
			opacity: 1;
		}
	}
	
	@keyframes pulse {
		0%, 100% {
			transform: scale(1);
		}
		50% {
			transform: scale(1.05);
		}
	}
	
	@keyframes bounceIn {
		0% {
			transform: scale(0.3);
			opacity: 0;
		}
		50% {
			transform: scale(1.05);
		}
		70% {
			transform: scale(0.9);
		}
		100% {
			transform: scale(1);
			opacity: 1;
		}
	}
	
	@keyframes fadeInUp {
		from {
			transform: translateY(30rpx);
			opacity: 0;
		}
		to {
			transform: translateY(0);
			opacity: 1;
		}
	}
</style>
