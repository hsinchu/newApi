<template>
	<view class="guide-page">
		<!-- 背景装饰 -->
		<view class="bg-decoration">
			<view class="circle circle-1"></view>
			<view class="circle circle-2"></view>
			<view class="circle circle-3"></view>
		</view>
		
		<!-- 主要内容区域 -->
		<view class="content-wrapper">			
			<!-- 加载状态 -->
			<view class="loading-section" v-if="!showVerification">
				<view class="loading-container">
					<view class="loading-spinner">
						<view class="spinner-dot" v-for="i in 8" :key="i" :style="{animationDelay: (i-1) * 0.1 + 's'}"></view>
					</view>
					<text class="loading-text">{{ loadingText }}</text>
				</view>
				<view class="progress-bar">
					<view class="progress-fill" :style="{width: progress + '%'}"></view>
				</view>
			</view>
			
			<!-- 点击验证 -->
			<view class="verification-section" v-if="showVerification">
				<view class="verification-container">
					<view class="verification-title">安全验证</view>
					<view class="verification-subtitle">请点击下方按钮完成验证</view>
					
					<!-- 点击验证按钮 -->
					<view class="click-verify" v-if="!isVerified">
						<view class="verify-button" @click="startVerification" :class="{loading: isVerifying}">
							<view class="button-icon" v-if="!isVerifying">🔒</view>
							<view class="loading-dots" v-if="isVerifying">
								<view class="dot" v-for="i in 3" :key="i"></view>
							</view>
							<text class="button-text">{{ verifyButtonText }}</text>
						</view>
					</view>
					
					<!-- 验证成功提示 -->
					<view class="success-message" v-if="isVerified">
						<view class="success-icon">✓</view>
						<text class="success-text">验证成功，正在进入...</text>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 底部信息 -->
		<view class="footer-info">
			<text class="copyright">© 2025 BNB娱乐大厅 版权所有</text>
			<text class="version">Version 1.8.8</text>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			// 加载状态
			progress: 0,
			loadingText: '正在初始化...',
			showVerification: false,
			
			// 点击验证
			isVerified: false,
			isVerifying: false,
			verifyButtonText: '点击验证',
			
			// 加载步骤
			loadingSteps: [
				{ text: '正在初始化...', progress: 20 },
				{ text: '加载系统配置...', progress: 40 },
				{ text: '连接服务器...', progress: 60 },
				{ text: '准备用户界面...', progress: 80 },
				{ text: '加载完成', progress: 100 }
			],
			currentStep: 0
		}
	},
	
	onLoad() {
		// 开始加载流程
		this.startLoading();
	},
	
	onReady() {
		// 页面准备完成
	},
	
	methods: {
		// 开始加载流程
		startLoading() {
			const loadStep = () => {
				if (this.currentStep < this.loadingSteps.length) {
					const step = this.loadingSteps[this.currentStep];
					this.loadingText = step.text;
					
					// 动画更新进度
					const targetProgress = step.progress;
					const currentProgress = this.progress;
					const increment = (targetProgress - currentProgress) / 20;
					
					const updateProgress = () => {
						if (this.progress < targetProgress) {
							this.progress += increment;
							if (this.progress > targetProgress) {
								this.progress = targetProgress;
							}
							setTimeout(updateProgress, 50);
						} else {
							this.currentStep++;
							setTimeout(loadStep, 300);
						}
					};
					
					updateProgress();
				} else {
					// 加载完成，显示验证
					setTimeout(() => {
						this.showVerification = true;
					}, 500);
				}
			};
			
			loadStep();
		},
		
		// 开始验证
		startVerification() {
			if (this.isVerifying || this.isVerified) return;
			
			this.isVerifying = true;
			this.verifyButtonText = '验证中...';
			
			// 模拟验证过程
			setTimeout(() => {
				this.isVerifying = false;
				this.isVerified = true;
				
				// 延迟跳转到首页
				setTimeout(() => {
					uni.reLaunch({
						url: '/pages/index/index'
					});
				}, 1333);
			}, 1666);
		}
	}
}
</script>

<style scoped>
/* 保持原有样式不变 */
.guide-page {
	width: 100vw;
	height: 100vh;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	position: relative;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	overflow: hidden;
}

/* 背景装饰 */
.bg-decoration {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	pointer-events: none;
}

.circle {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.1);
	animation: float 6s ease-in-out infinite;
}

.circle-1 {
	width: 200rpx;
	height: 200rpx;
	top: 10%;
	left: 10%;
	animation-delay: 0s;
}

.circle-2 {
	width: 150rpx;
	height: 150rpx;
	top: 60%;
	right: 15%;
	animation-delay: 2s;
}

.circle-3 {
	width: 100rpx;
	height: 100rpx;
	bottom: 20%;
	left: 20%;
	animation-delay: 4s;
}

@keyframes float {
	0%, 100% { transform: translateY(0px) rotate(0deg); }
	50% { transform: translateY(-20px) rotate(180deg); }
}

/* 主要内容区域 */
.content-wrapper {
	width: 90%;
	max-width: 600rpx;
	z-index: 10;
}

/* 品牌区域 */
.brand-section {
	text-align: center;
	margin-bottom: 100rpx;
}

.logo-container {
	width: 120rpx;
	height: 120rpx;
	margin: 0 auto 40rpx;
	border-radius: 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	backdrop-filter: blur(10px);
}

.logo-icon {
	font-size: 60rpx;
}

.brand-title {
	font-size: 48rpx;
	font-weight: bold;
	color: #fff;
	display: block;
	margin-bottom: 20rpx;
	text-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.3);
}

.brand-subtitle {
	font-size: 28rpx;
	color: rgba(255, 255, 255, 0.8);
	display: block;
	letter-spacing: 4rpx;
}

/* 加载区域 */
.loading-section {
	text-align: center;
}

.loading-container {
	margin-bottom: 60rpx;
}

.loading-spinner {
	width: 80rpx;
	height: 80rpx;
	margin: 0 auto 40rpx;
	position: relative;
}

.spinner-dot {
	position: absolute;
	width: 12rpx;
	height: 12rpx;
	background: #fff;
	border-radius: 50%;
	animation: spinner 1.2s linear infinite;
}

.spinner-dot:nth-child(1) { top: 0; left: 50%; margin-left: -6rpx; }
.spinner-dot:nth-child(2) { top: 14rpx; right: 14rpx; }
.spinner-dot:nth-child(3) { right: 0; top: 50%; margin-top: -6rpx; }
.spinner-dot:nth-child(4) { bottom: 14rpx; right: 14rpx; }
.spinner-dot:nth-child(5) { bottom: 0; left: 50%; margin-left: -6rpx; }
.spinner-dot:nth-child(6) { bottom: 14rpx; left: 14rpx; }
.spinner-dot:nth-child(7) { left: 0; top: 50%; margin-top: -6rpx; }
.spinner-dot:nth-child(8) { top: 14rpx; left: 14rpx; }

@keyframes spinner {
	0% { opacity: 1; }
	100% { opacity: 0; }
}

.loading-text {
	font-size: 32rpx;
	color: #fff;
	display: block;
	margin-bottom: 20rpx;
}

.progress-bar {
	width: 100%;
	height: 8rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 4rpx;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0.8));
	border-radius: 4rpx;
	transition: width 0.3s ease;
}

/* 验证区域 */
.verification-section {
	text-align: center;
	animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(30px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.verification-container {
	background: rgba(255, 255, 255, 0.1);
	border-radius: 55rpx;
	padding: 60rpx 40rpx;
	backdrop-filter: blur(10px);
	/* border: 2rpx solid rgba(255, 255, 255, 0.2); */
}

.verification-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #fff;
	margin-bottom: 20rpx;
}

.verification-subtitle {
	font-size: 28rpx;
	color: rgba(255, 255, 255, 0.8);
	margin-bottom: 60rpx;
}

/* 点击验证 */
.click-verify {
	margin-bottom: 40rpx;
}

.verify-button {
	width: 100%;
	height: 100rpx;
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
	border-radius: 50rpx;
	border: 2rpx solid rgba(255, 255, 255, 0.3);
	display: flex;
	align-items: center;
	justify-content: center;
	backdrop-filter: blur(10px);
	cursor: pointer;
	transition: all 0.3s ease;
	position: relative;
	overflow: hidden;
}

.verify-button:hover {
	background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.2));
	transform: translateY(-2rpx);
	box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.2);
}

.verify-button.loading {
	pointer-events: none;
}

.button-icon {
	font-size: 40rpx;
	margin-right: 20rpx;
	animation: pulse 2s infinite;
}

@keyframes pulse {
	0%, 100% { transform: scale(1); }
	50% { transform: scale(1.1); }
}

.loading-dots {
	display: flex;
	align-items: center;
	margin-right: 20rpx;
}

.loading-dots .dot {
	width: 8rpx;
	height: 8rpx;
	background: #fff;
	border-radius: 50%;
	margin: 0 4rpx;
	animation: loading-bounce 1.4s infinite ease-in-out;
}

.loading-dots .dot:nth-child(1) { animation-delay: -0.32s; }
.loading-dots .dot:nth-child(2) { animation-delay: -0.16s; }
.loading-dots .dot:nth-child(3) { animation-delay: 0s; }

@keyframes loading-bounce {
	0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
	40% { transform: scale(1.2); opacity: 1; }
}

.button-text {
	font-size: 32rpx;
	color: #fff;
	font-weight: bold;
	text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.3);
}

/* 成功消息 */
.success-message {
	display: flex;
	align-items: center;
	justify-content: center;
	animation: fadeInUp 0.5s ease;
}

.success-icon {
	width: 40rpx;
	height: 40rpx;
	background: #4CAF50;
	color: #fff;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24rpx;
	font-weight: bold;
	margin-right: 20rpx;
}

.success-text {
	font-size: 28rpx;
	color: #4CAF50;
	font-weight: bold;
}

/* 底部信息 */
.footer-info {
	position: absolute;
	bottom: 60rpx;
	left: 50%;
	transform: translateX(-50%);
	text-align: center;
	z-index: 10;
}

.copyright {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.6);
	display: block;
	margin-bottom: 10rpx;
}

.version {
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.4);
	display: block;
}
</style>
