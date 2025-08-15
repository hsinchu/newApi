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
						<text>（#1）</text>
					</view>
				</view>
				<uv-tags 
					:text="userInfo.is_verified === 1 ? '已认证' : userInfo.is_verified === 2 ? '审核中' : '未认证'" 
					:type="userInfo.is_verified === 1 ? 'success' : userInfo.is_verified === 2 ? 'warning' : 'error'" 
					plain 
					shape="circle">
				</uv-tags>
				<uv-icon name="setting" size="20" color="#e1e1e1" @click="openSettings"></uv-icon>
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

		<!-- 充值/提现按钮 -->
		<view class="action-buttons">
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
		<view class="list-container">
			<view class="list-item" v-for="(item, index) in settingsList" :key="index" @click="handleListClick(item)">
				<text class="list-text">{{ item.title }}</text>
				<uv-icon name="arrow-right" size="16" color="#666"></uv-icon>
			</view>
			</view>
		</view>
		
		<!-- 修改密码弹窗 -->
		<uv-modal v-model="showPasswordModal" title="修改密码" :show-cancel-button="true" @confirm="changePassword" @cancel="cancelPasswordChange">
			<view class="modal-content">
				<uv-form :model="passwordForm" ref="passwordFormRef">
					<uv-form-item label="原密码" prop="oldPassword" :required="true">
						<uv-input v-model="passwordForm.oldPassword" type="password" placeholder="请输入原密码"></uv-input>
					</uv-form-item>
					<uv-form-item label="新密码" prop="newPassword" :required="true">
						<uv-input v-model="passwordForm.newPassword" type="password" placeholder="请输入新密码"></uv-input>
					</uv-form-item>
					<uv-form-item label="确认密码" prop="confirmPassword" :required="true">
						<uv-input v-model="passwordForm.confirmPassword" type="password" placeholder="请再次输入新密码"></uv-input>
					</uv-form-item>
				</uv-form>
			</view>
		</uv-modal>
		
		<!-- 修改支付密码弹窗 -->
		<uv-modal v-model="showPayPasswordModal" title="修改支付密码" :show-cancel-button="true" @confirm="changePayPassword" @cancel="cancelPayPasswordChange">
			<view class="modal-content">
				<uv-form :model="payPasswordForm" ref="payPasswordFormRef">
					<uv-form-item label="原支付密码" prop="oldPayPassword" :required="true">
						<uv-input v-model="payPasswordForm.oldPayPassword" type="password" placeholder="请输入原支付密码"></uv-input>
					</uv-form-item>
					<uv-form-item label="新支付密码" prop="newPayPassword" :required="true">
						<uv-input v-model="payPasswordForm.newPayPassword" type="password" placeholder="请输入新支付密码"></uv-input>
					</uv-form-item>
					<uv-form-item label="确认支付密码" prop="confirmPayPassword" :required="true">
						<uv-input v-model="payPasswordForm.confirmPayPassword" type="password" placeholder="请再次输入新支付密码"></uv-input>
					</uv-form-item>
				</uv-form>
			</view>
		</uv-modal>
	</view>
</template>
<script>
	import { getUserInfo, getAgentStats } from '@/api/user.js';
	export default {
		data() {
			return {
				list: ['充值', '提现'],
				current: 0,
				baseList: [{
				name: 'play-circle',
				title: '充值活动'
			}, {
				name: 'red-packet',
				title: '红包管理'
			}, {
				name: 'share',
				title: '邀请代理商'
			}, {
				name: 'coupon',
				title: '资金变动'
			}],
			settingsList: [
			{
				title: '平台公告',
				key: 'dano'
			},
			// {
			// 	title: '领取佣金',
			// 	key: 'my_bonus'
			// },
			{
				title: '在线客服',
				key: 'service'
			}],
			userInfo: {
			avatar: '/static/images/avatar.svg',
			username: '加载中...',
			id: '',
			is_agent: 0
		},
		statsData: [
			{ count: '0.00', label: '余额' },
			{ count: '0', label: '会员' },
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
			}
			}
		},
		methods: {
			toDetail(name){
				switch(name) {
					case 0:
						// 跳转充值活动
						uni.navigateTo({
							url: '/pages/activity/charge'
						});
						break;
					case 1:
						uni.navigateTo({
							url: '/pages/redpacket/index'
						});
						break;
					case 2:
						// 邀请代理商
						uni.navigateTo({
							url: '/pages/users/share'
						});
						break;
					case 3:
						// 资金变动
						uni.navigateTo({
							url: '/pages/users/moneylog'
						});
						break;
					default:
						break;
				}
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
			openSettings() {
				console.log('打开设置');
				// 跳转到设置页面
				uni.navigateTo({
					url: '/pages/users/settings/settings'
				});
			},
			handleListClick(item) {
				console.log('点击了列表项：', item.title);
				// 根据item.key进行不同的跳转或操作
				switch(item.key) {
				case 'dano':
					// 跳转到平台公告页面
					uni.navigateTo({
						url: '/pages/other/dano'
					});
					break;
				case 'charge':
					// 跳转到充值活动页面
					uni.navigateTo({
						url: '/pages/activity/charge'
					});
					break;
				case 'logout':
					// 退出登录
					this.logout();
					break;
				default:
				break;
		}
		},
		// 获取用户信息和统计数据的通用方法
		async fetchUserData() {
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
			}
			
			// 获取代理商统计数据
			const statsResponse = await getAgentStats();
			if (statsResponse.code === 1) {
				const stats = statsResponse.data;
				this.statsData = [
					{ count: this.userInfo.money || '0.00', label: '余额' },
					{ count: stats.member_count || '0', label: '会员' },
					{ count: stats.total_commission || '0.00', label: '返佣' }
				];
			}
		},
		
		// 获取代理商信息
		async loadAgentInfo() {
			try {
				await this.fetchUserData();
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
				await this.fetchUserData();
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
		
		// 显示修改密码弹窗
		showChangePasswordModal() {
			this.showPasswordModal = true;
		},
		
		// 显示修改支付密码弹窗
		showChangePayPasswordModal() {
			this.showPayPasswordModal = true;
		},
		
		// 修改密码
		async changePassword() {
			if (!this.passwordForm.oldPassword || !this.passwordForm.newPassword || !this.passwordForm.confirmPassword) {
				uni.showToast({
					title: '请填写完整信息',
					icon: 'none'
				});
				return;
			}
			
			if (this.passwordForm.newPassword !== this.passwordForm.confirmPassword) {
				uni.showToast({
					title: '两次输入的密码不一致',
					icon: 'none'
				});
				return;
			}
			
			try {
				const response = await this.$http.post('/agent/changePassword', {
					old_password: this.passwordForm.oldPassword,
					new_password: this.passwordForm.newPassword
				});
				
				if (response.data.code === 200) {
					uni.showToast({
						title: '密码修改成功',
						icon: 'success'
					});
					this.showPasswordModal = false;
					this.resetPasswordForm();
				} else {
					uni.showToast({
						title: response.data.msg || '修改失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('修改密码失败：', error);
				uni.showToast({
					title: '修改失败，请重试',
					icon: 'none'
				});
			}
		},
		
		// 修改支付密码
		async changePayPassword() {
			if (!this.payPasswordForm.oldPayPassword || !this.payPasswordForm.newPayPassword || !this.payPasswordForm.confirmPayPassword) {
				uni.showToast({
					title: '请填写完整信息',
					icon: 'none'
				});
				return;
			}
			
			if (this.payPasswordForm.newPayPassword !== this.payPasswordForm.confirmPayPassword) {
				uni.showToast({
					title: '两次输入的支付密码不一致',
					icon: 'none'
				});
				return;
			}
			
			try {
				const response = await this.$http.post('/agent/changePayPassword', {
					old_pay_password: this.payPasswordForm.oldPayPassword,
					new_pay_password: this.payPasswordForm.newPayPassword
				});
				
				if (response.data.code === 200) {
					uni.showToast({
						title: '支付密码修改成功',
						icon: 'success'
					});
					this.showPayPasswordModal = false;
					this.resetPayPasswordForm();
				} else {
					uni.showToast({
						title: response.data.msg || '修改失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('修改支付密码失败：', error);
				uni.showToast({
					title: '修改失败，请重试',
					icon: 'none'
				});
			}
		},
		
		// 取消修改密码
		cancelPasswordChange() {
			this.showPasswordModal = false;
			this.resetPasswordForm();
		},
		
		// 取消修改支付密码
		cancelPayPasswordChange() {
			this.showPayPasswordModal = false;
			this.resetPayPasswordForm();
		},		
		// 重置密码表单
		resetPasswordForm() {
			this.passwordForm = {
				oldPassword: '',
				newPassword: '',
				confirmPassword: ''
			};
		},
		
		// 重置支付密码表单
		resetPayPasswordForm() {
			this.payPasswordForm = {
				oldPayPassword: '',
				newPayPassword: '',
				confirmPayPassword: ''
			};
		}
		},
		onLoad() {
			// 页面加载时获取代理商信息
			this.loadAgentInfo();
		}
	}
</script>

<style scoped lang="scss">
	.profile-container {
		background-color: #f8f9fa;
		padding-bottom:25rpx;
		color: #333;
	}
	
	.header {
		padding: 20rpx 40rpx;
		.header-content {
			display: flex;
			justify-content: space-between;
			align-items: center;
			.score {
			color: #333;
			font-size: 32rpx;
			font-weight: bold;
		}
		}
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
					color: #333;
					font-size: 32rpx;
					font-weight: bold;
				}
			}
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
						color: #666;
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
	
	.content-area {
		flex: 1;
		padding: 0 40rpx;
		
		.empty-state {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 100rpx 0;
			
			.empty-icon {
				margin-bottom: 40rpx;
				opacity: 0.6;
			}
			
			.empty-text {
				color: #999;
				font-size: 32rpx;
				margin-bottom: 20rpx;
			}
		}
	}
	
	.grid-container {
		padding: 0 16rpx;
		margin: 20rpx 0;
		background-color: #fff;
		border: 1px solid #e9ecef;
		border-radius: 38rpx;
		padding: 24rpx 16rpx;
		margin: 16rpx;
	}
	
	.grid-title {
		color: #666;
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
		color: #333;
		font-size: 24rpx;
		text-align: center;
		font-weight: 500;
	}
	
	.list-container {
		background-color: #fff;
		border: 1px solid #e9ecef;
		border-radius: 38rpx;
		margin: 16rpx;
		padding: 8rpx 0;
		overflow: hidden;
	}
	
	.list-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 32rpx 40rpx;
		background-color: #fff;
		border-bottom: 1px solid #e9ecef;
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
		color: #333;
		font-size: 28rpx;
		font-weight: 500;
		flex: 1;
	}
	
	.action-buttons {
		display: flex;
		gap: 20rpx;
		padding: 0 20rpx;
		margin-bottom: 30rpx;
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
	
	.button-text {
		color: #fff;
		font-size: 28rpx;
		font-weight: 500;
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
		background: linear-gradient(135deg, #007AFF, #0056CC);
		box-shadow: 0 4rpx 12rpx rgba(0, 122, 255, 0.3);
	}
	
	.blue-button:active {
		transform: scale(0.95);
		box-shadow: 0 2rpx 8rpx rgba(0, 122, 255, 0.4);
	}
</style>
