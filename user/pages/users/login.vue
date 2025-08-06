<template>
	<view class="container">
		<!-- 状态栏占位 -->
		<view class="status-bar"></view>
		

		
		<!-- Logo区域 -->
		<view class="logo-section">
			<view class="logo">
				<image src="/static/images/logo.jpg" mode="aspectFit" class="logo-img"></image>
			</view>
			<text class="welcome-text">用户登陆</text>
		</view>
		
		<!-- 登录表单 -->
		<view class="form-section">
			<!-- 用户名输入 -->
			<view class="input-group">
				<view class="input-wrapper">
					<uv-icon name="account-fill" size="20" color="#e1e1e1"></uv-icon>
					<input 
						v-model="formData.username" 
						type="text" 
						placeholder="请输入用户名" 
						placeholder-style="color: #b9b9b9;"
						class="input-field"
						maxlength="20"
					/>
				</view>
			</view>
			
			<!-- 密码输入 -->
			<view class="input-group">
				<view class="input-wrapper">
					<uv-icon name="lock-fill" size="20" color="#e1e1e1"></uv-icon>
					<input 
						v-model="formData.password" 
						:password="!showPassword"
						placeholder="请输入密码" 
						placeholder-style="color: #b9b9b9;"
						class="input-field"
					/>
					<view class="eye-icon" @tap="togglePassword">
						<uv-icon :name="showPassword ? 'eye-fill' : 'eye-off'" size="20" color="#b9b9b9"></uv-icon>
					</view>
				</view>
			</view>
			
			<!-- 登录按钮 -->
			<uv-button
				type="primary"
				shape="circle"
				size="large"
				@click="handleLogin"
				class="login-btn"
				:loading="loading"
				:disabled="loading"
			>
				{{ loading ? '登录中...' : '登录' }}
			</uv-button>
			
			<!-- 协议条款 -->
			<view class="agreement-section">
				<view class="checkbox-wrapper" @tap="toggleAgreement">
					<uv-icon 
						:name="agreed ? 'checkmark-circle-fill' : 'checkmark-circle'" 
						size="16" 
						:color="agreed ? '#52c41a' : '#b9b9b9'"
					></uv-icon>
				</view>
				<text class="agreement-text">
					为保障您的合法权益，请阅读并同意
					<text class="link-text" @tap="showUserAgreement">《用户协议》</text>
					和
					<text class="link-text" @tap="showPrivacyPolicy">《隐私政策》</text>
				</text>
			</view>
			
			<!-- 注册链接 -->
			<view class="register-section">
				<text class="register-text">还没有账号？</text>
				<text class="register-link" @tap="goToRegister">立即注册</text>
			</view>
			
			<!-- 找回密码链接 -->
			<view class="register-section">
				<text class="register-text">忘记密码？</text>
				<text class="register-link" @tap="goToFindPass">找回密码</text>
			</view>
		</view>
		
		<!-- 用户协议弹窗 -->
		<uv-popup ref="agreementPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-header">
					<text class="popup-title">用户协议</text>
					<view class="popup-close" @tap="closePopup">
						<uv-icon name="close" size="20" color="#e1e1e1"></uv-icon>
					</view>
				</view>
				<scroll-view class="popup-scroll" scroll-y="true">
					<text class="popup-text">
						欢迎使用我们的服务！
						
						本协议是您与我们的之间关于使用我们的服务的法律协议。请您仔细阅读本协议的全部条款。
						
						1. 服务内容
						我们的为用户提供人工智能相关服务，包括但不限于AI对话、内容生成等功能。
						
						2. 用户义务
						用户应当遵守相关法律法规，不得利用本服务从事违法违规活动。
						
						3. 隐私保护
						我们重视用户隐私，将按照隐私政策保护用户个人信息。
						
						4. 免责声明
						在法律允许的范围内，我们的不承担因使用本服务而产生的任何直接或间接损失。
					</text>
				</scroll-view>
				<view class="popup-footer">
					<uv-button text="我已阅读并同意" @click="agreeAndClose" class="popup-agree-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 隐私政策弹窗 -->
		<uv-popup ref="privacyPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-header">
					<text class="popup-title">隐私政策</text>
					<view class="popup-close" @tap="closePopup">
						<uv-icon name="close" size="20" color="#e1e1e1"></uv-icon>
					</view>
				</view>
				<scroll-view class="popup-scroll" scroll-y="true">
					<text class="popup-text">
						我们的隐私政策
						
						我们深知个人信息对您的重要性，并会尽全力保护您的个人信息安全可靠。
						
						1. 信息收集
						我们会收集您主动提供的信息，如注册信息、使用记录等。
						
						2. 信息使用
						我们使用收集的信息来提供、维护和改进我们的服务。
						
						3. 信息保护
						我们采用行业标准的安全措施来保护您的个人信息。
						
						4. 信息共享
						除法律要求外，我们不会与第三方分享您的个人信息。
						
						5. 联系我们
						如有隐私相关问题，请联系我们的客服团队。
					</text>
				</scroll-view>
				<view class="popup-footer">
					<uv-button text="我已阅读并同意" @click="agreeAndClose" class="popup-agree-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
import { login } from '@/api/user.js';

export default {
		data() {
			return {
				formData: {
					username: '',
					password: ''
				},
				showPassword: false,
				agreed: false,
				loading: false
			}
		},
		onLoad() {
			// 检查登录状态
			this.checkLoginStatus();
		},
		methods: {
			// 跳转找回密码页面
			goToFindPass() {
				uni.navigateTo({
					url: '/pages/users/findpass'
				});
			},
			// 检查登录状态
			checkLoginStatus() {
				const token = uni.getStorageSync('ba-user-token');
				const userInfo = uni.getStorageSync('userInfo');
				
				// 如果有token和用户信息，且用户是代理商，则跳转到个人中心
				if (token && userInfo && userInfo.is_agent === 0) {
					uni.reLaunch({
						url: '/pages/profile/profile'
					});
				}
			},

		
		// 切换密码显示
		togglePassword() {
			this.showPassword = !this.showPassword;
		},
		
		// 切换协议同意状态
		toggleAgreement() {
			this.agreed = !this.agreed;
		},
		
		// 处理登录
		async handleLogin() {
			// 表单验证
			if (!this.validateForm()) {
				return;
			}
			
			if (!this.agreed) {
				uni.showToast({
					title: '请先同意用户协议和隐私政策',
					icon: 'none'
				});
				return;
			}
			
			this.loading = true;
			
			try {
				// 调用登录接口，添加代理商类型标识
				const response = await login({
					username: this.formData.username,
					password: this.formData.password,
					type: 'user' // 标识这是代理商登录
				});
				
				if (response.code === 1) {
					// 验证是否为代理商
					if (response.data.userInfo.is_agent == 1) {
						uni.showToast({
							title: '您不是用户，无法登录用户系统',
							icon: 'none',
							duration: 3000
						});
						return;
					}
					
					// 保存token和用户信息
					uni.setStorageSync('ba-user-token', response.data.userInfo.token);
					
					// 保存用户信息
					uni.setStorageSync('userInfo', response.data.userInfo);
					
					uni.showToast({
						title: '登录成功',
						icon: 'success'
					});
					
					// 登录成功后跳转到会员中心
					setTimeout(() => {
						uni.switchTab({
							url: '/pages/profile/profile'
						});
					}, 1500);
				} else {
					uni.showToast({
						title: response.msg || '登录失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('登录失败:', error);
				uni.showToast({
					title: error.msg || '登录失败，请重试',
					icon: 'none'
				});
			} finally {
				this.loading = false;
			}
		},
		
		// 表单验证
		validateForm() {
			if (!this.formData.username.trim()) {
				uni.showToast({
					title: '请输入用户名',
					icon: 'none'
				});
				return false;
			}
			
			if (this.formData.username.length < 3) {
				uni.showToast({
					title: '用户名长度不能少于3位',
					icon: 'none'
				});
				return false;
			}
			
			if (!/^[a-zA-Z0-9_]{3,20}$/.test(this.formData.username)) {
				uni.showToast({
					title: '用户名只能包含字母、数字和下划线',
					icon: 'none'
				});
				return false;
			}
			
			if (!this.formData.password.trim()) {
				uni.showToast({
					title: '请输入密码',
					icon: 'none'
				});
				return false;
			}
			
			if (this.formData.password.length < 6) {
				uni.showToast({
					title: '密码长度不能少于6位',
					icon: 'none'
				});
				return false;
			}
			
			return true;
		},
		
		// 显示用户协议
		showUserAgreement() {
			this.$refs.agreementPopup.open();
		},
		
		// 显示隐私政策
		showPrivacyPolicy() {
			this.$refs.privacyPopup.open();
		},
		
		// 关闭弹窗
		closePopup() {
			this.$refs.agreementPopup?.close();
			this.$refs.privacyPopup?.close();
		},
		
		// 同意并关闭弹窗
		agreeAndClose() {
			this.agreed = true;
			this.closePopup();
		},
		
		// 跳转到注册页面
		goToRegister() {
			uni.navigateTo({
				url: '/pages/users/register'
			});
		}
	}
}
</script>

<style lang="scss">
.container {
	min-height: 100vh;
	background-color: #000;
	position: relative;
	/* #ifdef APP-PLUS */
	padding-top: var(--status-bar-height);
	/* #endif */
	/* #ifdef H5 */
	padding-top: env(safe-area-inset-top);
	/* #endif */
}

.status-bar {
	/* #ifdef APP-PLUS */
	height: var(--status-bar-height);
	/* #endif */
	/* #ifdef H5 */
	height: env(safe-area-inset-top);
	/* #endif */
}



.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 120rpx 0 80rpx;
}

.logo {
	width: 120rpx;
	height: 120rpx;
	margin-bottom: 40rpx;
}

.logo-img {
	width: 100%;
	height: 100%;
}

.welcome-text {
	font-size: 36rpx;
	font-weight: 500;
	color: #e1e1e1;
}

.form-section {
	padding: 0 60rpx;
	/* #ifdef APP-PLUS */
	padding-left: calc(env(safe-area-inset-left) + 60rpx);
	padding-right: calc(env(safe-area-inset-right) + 60rpx);
	/* #endif */
}

.input-group {
	margin-bottom: 40rpx;
}

.input-wrapper {
	display: flex;
	align-items: center;
	background-color: #1a1a1a;
	border: 2rpx solid #333;
	border-radius: 20rpx;
	padding: 0 30rpx;
	height: 100rpx;
	gap: 20rpx;
}

.input-field {
	flex: 1;
	font-size: 28rpx;
	color: #e1e1e1;
	height: 100%;
	background-color: transparent;
}

.eye-icon {
	padding: 10rpx;
}

.login-btn {
	width: 100%;
	margin-top: 60rpx;
}

.agreement-section {
	display: flex;
	align-items: flex-start;
	gap: 16rpx;
	margin: 40rpx 0;
}

.checkbox-wrapper {
	padding: 4rpx;
}

.agreement-text {
	font-size: 24rpx;
	color: #b9b9b9;
	line-height: 1.6;
	flex: 1;
}

.link-text {
	color: orangered;
	text-decoration: underline;
}

.register-section {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
	margin-top: 60rpx;
}

.register-text {
	font-size: 26rpx;
	color: #b9b9b9;
}

.register-link {
	font-size: 26rpx;
	color: orangered;
	font-weight: 500;
}

/* 弹窗样式 */
.popup-content {
	width: 640rpx;
	max-height: 80vh;
	background-color: #1a1a1a;
	border-radius: 20rpx;
	padding: 0;
	overflow: hidden;
}

.popup-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 40rpx 40rpx 20rpx;
	border-bottom: 1px solid #333;
}

.popup-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #e1e1e1;
}

.popup-close {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.popup-scroll {
	max-height: 400rpx;
	padding: 20rpx 40rpx;
}

.popup-text {
	font-size: 26rpx;
	color: #e1e1e1;
	line-height: 1.8;
	white-space: pre-line;
}

.popup-footer {
	padding: 20rpx 40rpx 40rpx;
	border-top: 1px solid #333;
}

.popup-agree-btn {
	background-color: orangered !important;
	color: #e1e1e1 !important;
	border-radius: 12rpx;
	height: 80rpx;
	line-height: 80rpx;
	font-size: 28rpx;
	font-weight: 500;
}
</style>