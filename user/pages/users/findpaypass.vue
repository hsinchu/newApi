<template>
	<view class="container">
		<!-- 找回密码表单 -->
		<view class="form-section">
			<!-- 电子邮箱输入 -->
			<view class="input-group">
				<view class="input-wrapper disabled">
					<uv-icon name="email-fill" size="20" color="#333"></uv-icon>
					<input 
						v-model="formData.email" 
						type="text"
						placeholder="请输入注册邮箱" 
						placeholder-style="color: #b9b9b9;"
						class="input-field"
						disabled
					/>
					<view class="email-tip">
						<text class="tip-text">当前登录邮箱</text>
					</view>
				</view>
			</view>
			
			<!-- 邮箱验证码输入 -->
			<view class="input-group">
				<view class="input-wrapper">
					<uv-icon name="eye" size="20" color="#333"></uv-icon>
					<input 
						v-model="formData.code" 
						type="text"
						placeholder="请输入邮箱验证码" 
						placeholder-style="color: #b9b9b9;"
						class="input-field code-input"
						maxlength="6"
					/>
					<view class="code-btn" @tap="sendEmailCode">
						<text class="code-btn-text" :class="{disabled: emailCodeSending || emailCountdown > 0}">
							{{ emailCountdown > 0 ? `${emailCountdown}s后重发` : (emailCodeSending ? '发送中...' : '发送验证码') }}
						</text>
					</view>
				</view>
			</view>
			
			<!-- 新密码输入 -->
			<view class="input-group">
				<view class="input-wrapper">
					<uv-icon name="lock-fill" size="20" color="#333"></uv-icon>
					<input 
						v-model="formData.password" 
						:password="!showPassword"
						placeholder="请输入新支付密码" 
						placeholder-style="color: #b9b9b9;"
						class="input-field"
					/>
					<view class="eye-icon" @tap="togglePassword">
						<uv-icon :name="showPassword ? 'eye-fill' : 'eye-off'" size="20" color="#b9b9b9"></uv-icon>
					</view>
				</view>
			</view>
			
			<!-- 确认新密码输入 -->
			<view class="input-group">
				<view class="input-wrapper">
					<uv-icon name="lock-fill" size="20" color="#333"></uv-icon>
					<input 
						v-model="formData.confirmPassword" 
						:password="!showConfirmPassword"
						placeholder="请确认新支付密码" 
						placeholder-style="color: #999;"
						class="input-field"
					/>
					<view class="eye-icon" @tap="toggleConfirmPassword">
						<uv-icon :name="showConfirmPassword ? 'eye-fill' : 'eye-off'" size="20" color="#b9b9b9"></uv-icon>
					</view>
				</view>
			</view>
			
			<!-- 重置密码按钮 -->
			<uv-button
				type="primary"
				shape="circle"
				size="large"
				@click="handleResetPayPassword"
				class="reset-btn"
				:loading="loading"
				:disabled="loading"
			>
				{{ loading ? '重置中...' : '重置支付密码' }}
			</uv-button>
			
			<!-- 返回设置链接 -->
			<view class="login-section">
				<text class="login-text">想起支付密码了？</text>
				<text class="login-link" @tap="goToSettings">返回设置</text>
			</view>
		</view>
	</view>
</template>

<script>
import { resetPayPassword, sendEmailCode } from '@/api/user.js';
import authMixin from '@/mixins/auth.js';

export default {
	mixins: [authMixin],
	data() {
		return {
			formData: {
				email: '',
				code: '',
				password: '',
				confirmPassword: ''
			},
			showPassword: false,
			showConfirmPassword: false,
			loading: false,
			emailCodeSending: false,
			emailCountdown: 0,
			countdownTimer: null
		}
	},
	methods: {
		// 切换密码显示
		togglePassword() {
			this.showPassword = !this.showPassword;
		},
		
		// 切换确认密码显示
		toggleConfirmPassword() {
			this.showConfirmPassword = !this.showConfirmPassword;
		},
		
		// 发送找回支付密码验证码
		async sendResetPayPasswordCode(email) {
			try {
				const result = await sendEmailCode(email, 'reset_pay_password');
				return result;
			} catch (error) {
				throw error;
			}
		},
		
		// 发送邮箱验证码
		async sendEmailCode() {
			if (this.emailCodeSending || this.emailCountdown > 0) {
				return;
			}
			
			if (!this.formData.email) {
				uni.showToast({
					title: '请输入邮箱地址',
					icon: 'none'
				});
				return;
			}
			
			// 验证邮箱格式
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(this.formData.email)) {
				uni.showToast({
					title: '邮箱格式不正确',
					icon: 'none'
				});
				return;
			}
			
			this.emailCodeSending = true;
			
			try {
				// 发送找回支付密码类型的验证码
				const result = await this.sendResetPayPasswordCode(this.formData.email);
				if (result.code === 1) {
					uni.showToast({
						title: result.msg || '验证码发送成功',
						icon: 'success'
					});
					
					// 自动填充验证码
					if (result.data && result.data.code) {
						this.formData.code = result.data.code;
					}
					
					// 开始倒计时
					this.emailCountdown = 60;
					this.countdownTimer = setInterval(() => {
						this.emailCountdown--;
						if (this.emailCountdown <= 0) {
							clearInterval(this.countdownTimer);
							this.countdownTimer = null;
						}
					}, 1000);
				} else {
					uni.showToast({
						title: result.msg || '发送失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('发送验证码失败:', error);
				uni.showToast({
					title: '发送失败，请重试',
					icon: 'none'
				});
			} finally {
				this.emailCodeSending = false;
			}
		},
		
		// 处理重置支付密码
		async handleResetPayPassword() {
			// 表单验证
			if (!this.validateForm()) {
				return;
			}
			
			this.loading = true;
			
			try {
					// 调用重置支付密码接口
					const response = await resetPayPassword({
						email: this.formData.email,
						code: this.formData.code,
						payPassword: this.formData.password
					});
				
				if (response.code === 1) {
						uni.showToast({
							title: '支付密码重置成功',
							icon: 'success'
						});
					
					// 重置成功后返回设置页面
					setTimeout(() => {
						uni.navigateBack();
					}, 1500);
				} else {
					uni.showToast({
						title: response.msg || '重置失败',
						icon: 'none'
					});
				}
			} catch (error) {
					console.error('重置支付密码失败:', error);
					uni.showToast({
						title: error.msg || '重置失败，请重试',
						icon: 'none'
					});
			} finally {
				this.loading = false;
			}
		},
		
		// 表单验证
		validateForm() {
			// 验证邮箱
			if (!this.formData.email.trim()) {
				uni.showToast({
					title: '请输入邮箱地址',
					icon: 'none'
				});
				return false;
			}
			
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(this.formData.email)) {
				uni.showToast({
					title: '邮箱格式不正确',
					icon: 'none'
				});
				return false;
			}
			
			// 验证邮箱验证码
			if (!this.formData.code.trim()) {
				uni.showToast({
					title: '请输入邮箱验证码',
					icon: 'none'
				});
				return false;
			}
			
			if (this.formData.code.length !== 6) {
				uni.showToast({
					title: '验证码应为6位数字',
					icon: 'none'
				});
				return false;
			}
			
			// 验证新支付密码
			if (!this.formData.password.trim()) {
				uni.showToast({
					title: '请输入新支付密码',
					icon: 'none'
				});
				return false;
			}
			
			if (this.formData.password.length !== 6 || !/^\d{6}$/.test(this.formData.password)) {
				uni.showToast({
					title: '支付密码必须是6位数字',
					icon: 'none'
				});
				return false;
			}
			
			// 验证确认支付密码
			if (!this.formData.confirmPassword.trim()) {
				uni.showToast({
					title: '请确认新支付密码',
					icon: 'none'
				});
				return false;
			}
			
			if (this.formData.password !== this.formData.confirmPassword) {
				uni.showToast({
					title: '两次输入的支付密码不一致',
					icon: 'none'
				});
				return false;
			}
			
			return true;
		},
		
		// 跳转到设置页面
		goToSettings() {
			uni.navigateBack();
		}
	},
	
	// 页面加载时自动填充邮箱
	onLoad() {
		// 检查登录状态
		if (!this.checkLoginStatus()) {
			return;
		}
		
		// 自动填充用户邮箱
		const userInfo = uni.getStorageSync('userInfo');
		if (userInfo && userInfo.email) {
			this.formData.email = userInfo.email;
		}
	},
	
	// 页面显示时检查登录状态
	onShow() {
		this.checkLoginStatus();
	},
	
	// 页面销毁时清理定时器
	beforeDestroy() {
		if (this.countdownTimer) {
			clearInterval(this.countdownTimer);
			this.countdownTimer = null;
		}
	}
}
</script>

<style lang="scss">
.container {
	background-color: #f8f9fa;
	position: relative;
	/* #ifdef APP-PLUS */
	padding-top: var(--status-bar-height);
	/* #endif */
	/* #ifdef H5 */
	padding-top: env(safe-area-inset-top);
	/* #endif */
}

.welcome-text {
	font-size: 36rpx;
	font-weight: 500;
	color: #333;
}

.form-section {
	padding: 60rpx;
	/* #ifdef APP-PLUS */
	padding-left: calc(env(safe-area-inset-left) + 60rpx);
	padding-right: calc(env(safe-area-inset-right) + 60rpx);
	/* #endif */
}

.input-group {
	margin-bottom: 30rpx;
}

.input-wrapper {
	display: flex;
	align-items: center;
	background-color: #fff;
	border: 2rpx solid #e9ecef;
	border-radius: 20rpx;
	padding: 0 30rpx;
	height: 100rpx;
	gap: 20rpx;
}

.input-wrapper.disabled {
	background-color: #f8f9fa;
	border-color: #dee2e6;
	opacity: 0.8;
}

.input-field {
	flex: 1;
	font-size: 28rpx;
	color: #333;
	height: 100%;
	background-color: transparent;
}

.code-input {
	flex: 1;
	margin-right: 20rpx;
}

.code-btn {
	padding: 10rpx 20rpx;
	border-radius: 8rpx;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	min-width: 160rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.code-btn-text {
	font-size: 24rpx;
	color: #fff;
	font-weight: 500;
}

.code-btn-text.disabled {
	color: #ccc;
}

.eye-icon {
	padding: 10rpx;
}

.email-tip {
	padding: 5rpx 15rpx;
	background-color: #e9ecef;
	border-radius: 8rpx;
}

.tip-text {
	font-size: 20rpx;
	color: #999;
}

.reset-btn {
	width: 100%;
	margin-top: 60rpx;
}

.login-section {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
	margin-top: 40rpx;
}

.login-text {
	font-size: 26rpx;
	color: #666;
}

.login-link {
	font-size: 26rpx;
	color: orangered;
	font-weight: 500;
}

/* 全局输入框样式覆盖 */
.uni-input-input {
	color: #333 !important;
}
</style>