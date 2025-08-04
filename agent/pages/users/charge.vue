<template>
	<view class="charge-container">				
		<!-- 使用uv-vtabs垂直选项卡 -->
		<view class="vtabs-container">
			<uv-vtabs 
				:list="paymentMethods" 
				:current="currentPaymentIndex"
				@change="onPaymentMethodChange"
				:chain="false"
				key-name="name"
				bar-width="220rpx"
				:bar-bg-color="'#333'"
				:bar-item-style="barItemStyle"
				:bar-item-active-style="barItemActiveStyle"
				:content-style="contentStyle"
			>
				<uv-vtabs-item :index="0">
					<view class="recharge-info">
						<view class="balance-section">
							<view class="balance-card">
								<view class="balance-header">
									<text class="balance-label">当前余额</text>
									<view class="header-actions">
										<view class="refresh-btn" @tap="refreshBalance">
											<uv-icon name="reload" size="16" color="#e1e1e1" :class="{ 'rotating': refreshing }"></uv-icon>
										</view>
										<view class="moneylog-btn" @tap="goToMoneyLog">
											<uv-icon name="list" size="16" color="#e1e1e1"></uv-icon>
										</view>
									</view>
								</view>
								<text class="balance-amount">¥{{ balance }}</text>
							</view>
						</view>
						<!-- 支付通道选择 -->
						<view class="content-section" v-if="paymentChannels.length > 0">
							<view class="channel-list">
								<view class="channels-grid">
								<view 
									v-for="(channel, index) in paymentChannels" 
									:key="index"
									class="channel-item"
									:class="{ 'active': selectedChannel === channel.id }"
									@tap="selectChannel(channel)"
								>
									<view class="channel-content">
										<text class="channel-name">{{ channel.name }}</text>
									</view>
								</view>
							</view>
							</view>
						</view>
						
						<!-- 支付金额输入 -->
						<view class="content-section">
							<text class="section-title">充值金额</text>
							<view class="amount-input-wrapper">
								<text class="currency-symbol">¥</text>
								<input 
									v-model="inputAmount" 
									type="digit" 
									placeholder="请输入充值金额" 
									placeholder-style="color: #666;"
									class="amount-input"
									@input="onAmountInput"
									@focus="clearSelectedAmount"
								/>
							</view>
							<view class="amount-tips">
								<text class="tip-text">最低充值: ¥{{ currentMinAmount }}</text>
								<text class="tip-text">最高充值: ¥{{ currentMaxAmount }}</text>
							</view>
						</view>
						
						<!-- 提交支付按钮 -->
						<view class="content-section">
							<uv-button
								type="primary"
								shape="circle"
								size="large"
								@click="submitPayment"
								class="submit-btn"
								:loading="submitting"
								:disabled="!canSubmit || submitting"
							>
								{{ submitting ? '处理中...' : `立即充值 ¥${finalAmount}` }}
							</uv-button>
						</view>
						
						<!-- 支付说明 -->
						<view class="content-section">
							<text class="section-title">支付说明</text>
							<view class="notice-content">
								<view class="notice-item">
									<text class="notice-text">• 充值金额将实时到账，请确认充值金额无误</text>
								</view>
								<view class="notice-item">
									<text class="notice-text">• 支付过程中请勿关闭页面或重复提交</text>
								</view>
								<view class="notice-item">
									<text class="notice-text">• 如遇支付问题，请联系客服处理</text>
								</view>
								<view class="notice-item">
									<text class="notice-text">• 充值记录可在个人中心查看</text>
								</view>
							</view>
						</view>
					</view>
				</uv-vtabs-item>
			</uv-vtabs>
		</view>
	</view>
</template>

<script>
import { getUserInfo } from '@/api/user.js';
import { getPayType, mockPaySuccess } from '@/api/charge.js';
export default {
	data() {
		return {
			// 余额相关
			balance: '0.00',
			refreshing: false,
			
			// 支付方式
			paymentMethods: [],
			selectedPaymentMethod: '',
			currentPaymentIndex: 0,
			barItemStyle: {
				backgroundColor: '#333',
				color: '#e1e1e1',
				borderRadius: '0',
				padding: '15rpx 15rpx'
			},
			barItemActiveStyle: {
				backgroundColor: '#007AFF',
				padding: '15rpx 15rpx',
				fontWeight: 'bold',
				color: '#ffffff'
			},
			contentStyle: {
				backgroundColor: '#252525',
			},
			
			// 支付通道
			paymentChannels: [],
			selectedChannel: '',
			
			// 金额限制
			minAmount: 1,
			maxAmount: 50000,
			currentMinAmount: 1,
			currentMaxAmount: 50000,
			currentFeeRate: 0,
			
			// 快捷金额
			quickAmounts: [],
			selectedAmount: 0,
			
			// 输入金额
			inputAmount: '',
			
			// 提交状态
			submitting: false
		}
	},
	
	computed: {
		// 最终充值金额
		finalAmount() {
			if (this.inputAmount) {
				return parseFloat(this.inputAmount) || 0;
			}
			return this.selectedAmount;
		},
		
		// 是否可以提交
		canSubmit() {
			return this.selectedPaymentMethod && 
				   this.selectedChannel && 
				   this.finalAmount >= this.currentMinAmount && 
				   this.finalAmount <= this.currentMaxAmount;
		}
	},
	
	onLoad() {
		this.loadUserBalance();
		this.loadPaymentMethods();
	},
	
	methods: {
		// 返回上一页
		goBack() {
			uni.navigateBack();
		},
		
		// 跳转到账变记录
		goToMoneyLog() {
			uni.navigateTo({
				url: '/pages/users/moneylog'
			});
		},
		
		// 加载用户余额
		async loadUserBalance() {
			try {
				const response = await getUserInfo();
				if (response.code === 1 && response.data) {
					this.balance = parseFloat(response.data.money || 0).toFixed(2);
				}
			} catch (error) {
				console.error('获取余额失败:', error);
			}
		},
		
		// 刷新余额
		async refreshBalance() {
			if (this.refreshing) return;
			
			this.refreshing = true;
			try {
				await this.loadUserBalance();
			} catch (error) {
				uni.showToast({
					title: '刷新失败',
					icon: 'none'
				});
			} finally {
				setTimeout(() => {
					this.refreshing = false;
				}, 1000);
			}
		},
		
		// 加载支付方式
		async loadPaymentMethods() {
			try {
				const response = await getPayType();
				if (response.code === 1 && response.data) {
					this.paymentMethods = response.data.map(method => ({
						id: method.id,
						name: method.name,
						icon: method.icon || 'checkmark-circle',
						description: method.description,
						channels: method.channels
					}));
					
					// 默认选择第一个支付方式
					if (this.paymentMethods.length > 0) {
						this.selectPaymentMethod(this.paymentMethods[0]);
					}
				} else {
					uni.showToast({
						title: response.msg || '获取支付方式失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('获取支付方式失败:', error);
				uni.showToast({
					title: '获取支付方式失败',
					icon: 'none'
				});
			}
		},
		
		// 选择支付方式
		selectPaymentMethod(method) {
			this.selectedPaymentMethod = method.id;
			
			// 根据支付方式加载对应的支付通道
			this.loadPaymentChannels(method);
		},
		
		// 加载支付通道
		loadPaymentChannels(method) {
			// 从选中的支付方式中获取通道数据
			this.paymentChannels = method.channels || [];
			
			// 更新金额限制
			if (this.paymentChannels.length > 0) {
				// 取所有通道中的最小和最大金额限制
				this.minAmount = Math.min(...this.paymentChannels.map(c => c.min_amount));
				this.maxAmount = Math.max(...this.paymentChannels.map(c => c.max_amount));
				
				// 默认选择第一个通道并更新当前限额
				this.selectChannel(this.paymentChannels[0]);
			} else {
				this.selectedChannel = '';
				this.minAmount = 1;
				this.maxAmount = 50000;
				this.currentMinAmount = 1;
				this.currentMaxAmount = 50000;
				this.currentFeeRate = 0;
			}
		},
		
		// 选择支付通道
		selectChannel(channel) {
			this.selectedChannel = channel.id;
			this.currentMinAmount = channel.min_amount || this.minAmount;
			this.currentMaxAmount = channel.max_amount || this.maxAmount;
			this.currentFeeRate = channel.fee_rate || 0;
		},
		
		// 获取当前选中通道名称
		getCurrentChannelName() {
			if (!this.selectedChannel || !this.paymentChannels) {
				return '';
			}
			const channel = this.paymentChannels.find(ch => ch.id === this.selectedChannel);
			return channel ? channel.name : '';
		},
		
		// 选择快捷金额
		selectAmount(amount) {
			// 检查金额是否超出当前通道限制
			if (amount > this.currentMaxAmount) {
				uni.showToast({
					title: `该金额超出当前通道限制¥${this.currentMaxAmount}`,
					icon: 'none'
				});
				return;
			}
			
			this.selectedAmount = amount;
			this.inputAmount = ''; // 清空输入框
		},
		
		// 金额输入处理
		onAmountInput(e) {
			this.inputAmount = e.detail.value;
		},
		
		// 清空选中的快捷金额
		clearSelectedAmount() {
			this.selectedAmount = 0;
		},
		
		// 提交支付
		async submitPayment() {
			if (!this.canSubmit) {
				uni.showToast({
					title: '请完善充值信息',
					icon: 'none'
				});
				return;
			}
			
			// 验证金额限制
			const amount = parseFloat(this.finalAmount);
			if (amount < this.currentMinAmount) {
				uni.showToast({
					title: `充值金额不能小于¥${this.currentMinAmount}`,
					icon: 'none'
				});
				return;
			}
			
			if (amount > this.currentMaxAmount) {
				uni.showToast({
					title: `充值金额不能大于¥${this.currentMaxAmount}`,
					icon: 'none'
				});
				return;
			}
			
			// 确认充值
			uni.showModal({
				title: '确认充值',
				content: `确认充值 ¥${this.finalAmount} 吗？`,
				success: (res) => {
					if (res.confirm) {
						this.processPayment();
					}
				}
			});
		},
		
		// 处理支付
		async processPayment() {
			this.submitting = true;
			
			try {
				// 构建支付参数
				const paymentData = {
					amount: this.finalAmount,
					payment_method: this.selectedPaymentMethod,
					payment_channel: this.selectedChannel
				};
				
				// 调用充值API
				const response = await mockPaySuccess(paymentData);
				
				if (response.code === 1) {
				// 先显示成功提示
				uni.showToast({
					title: '充值成功',
					icon: 'success',
					duration: 2000
				});
				
				// 延迟一下再刷新余额，确保提示能显示
				setTimeout(async () => {
					// 刷新余额
					await this.loadUserBalance();
					
					// 重置表单
					this.resetForm();
				}, 1000);
			} else {
				throw new Error(response.msg || '充值失败');
			}
				
			} catch (error) {
				console.error('充值失败:', error);
				uni.showToast({
					title: error.message || '充值失败，请重试',
					icon: 'none'
				});
			} finally {
				this.submitting = false;
			}
		},
		
		// 重置表单
		resetForm() {
			this.inputAmount = '';
			this.selectedAmount = 0;
		},
		
		// vtabs支付方式切换
		onPaymentMethodChange(index) {
			this.currentPaymentIndex = index;
			if (this.paymentMethods[index]) {
				this.selectPaymentMethod(this.paymentMethods[index]);
			}
		},
	}
}
</script>

<style lang="scss">
.charge-container {
	background-color: #252525;
	color: #e1e1e1;
}

.recharge-info {
	padding:0 25rpx 25rpx 25rpx;
}

.nav-left, .nav-right {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.nav-title {
	font-size: 32rpx;
	font-weight: 500;
	color: #e1e1e1;
}

// 余额区域
.balance-section {
	padding:15rpx 0;
}

.balance-card {
	background: linear-gradient(135deg, #333 0%, #444 100%);
	border-radius: 0 55rpx 0 0;
	padding: 30rpx 20rpx;
	position: relative;
}

.balance-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 20rpx;
}

.balance-label {
	font-size: 28rpx;
	color: #999;
}

.header-actions {
	display: flex;
	align-items: center;
	gap: 15rpx;
}

.refresh-btn, .moneylog-btn {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	background-color: rgba(255, 255, 255, 0.1);
	transition: all 0.3s ease;

	&:active {
		background-color: rgba(255, 255, 255, 0.2);
		transform: scale(0.95);
	}
}

.rotating {
	animation: rotate 1s linear infinite;
}

@keyframes rotate {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.balance-amount {
	font-size: 48rpx;
	font-weight: bold;
	color: #e1e1e1;
}
// 内容区域
.content-section {

	&:last-child {
		margin-bottom: 0;
	}
}

.section-title {
	font-size: 25rpx;
	font-weight: 500;
	color: #9d9d9d;
	margin: 25rpx 0;
	display: block;
}

.channels-grid {
	display: flex;
	gap: 16rpx;
	justify-content: space-between;
}

.channel-item {
	position: relative;
	background: #2a2a2a;
	border: 1px solid #444;
	padding: 24rpx;
	width: calc(50% - 25rpx);
	transition: all 0.3s ease;

	&.active {
		border-color: #007AFF;
		background: rgba(0, 122, 255, 0.1);
	}
}

.channel-content {
	display: flex;
	justify-content: center;
	align-items: center;
	width: 100%;
}

.channel-name {
	font-size: 28rpx;
	color: #ffffff;
	font-weight: 500;
	text-align: center;
}

.channel-check {
	width: 32rpx;
	height: 32rpx;
	border-radius: 50%;
	background: #007AFF;
	display: flex;
	align-items: center;
	justify-content: center;
}

.channel-tag {
	position: absolute;
	top: -18rpx;
	right: -18rpx;
	background: linear-gradient(135deg, #FF6B6B, #FF8E53);
	border-radius: 20rpx;
	padding: 6rpx 16rpx;
	min-width: 80rpx;
	height: 36rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 2rpx 8rpx rgba(255, 107, 107, 0.3);
	z-index: 10;
}

.tag-text {
	font-size: 20rpx;
	color: #ffffff;
	font-weight: 600;
	text-align: center;
}

// 通道信息
.channel-info {
	background-color: #2a2a2a;
	border-radius: 12rpx;
	padding: 20rpx;
	margin-top: 20rpx;
	border: 1rpx solid #444;
}

.info-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15rpx;
	
	&:last-child {
		margin-bottom: 0;
	}
}

.info-label {
	font-size: 26rpx;
	color: #999;
}

.info-value {
	font-size: 26rpx;
	color: #e1e1e1;
	font-weight: 500;
}

// 快捷金额
.quick-amounts {
	display: flex;
	flex-wrap: wrap;
	gap: 15rpx;
}

.amount-item {
	flex: 1;
	min-width: 140rpx;
	height: 100rpx;
	background-color: #444;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	border: 2rpx solid #555;
	transition: all 0.3s ease;

	&.active {
		border-color: #007AFF;
		background-color: rgba(0, 122, 255, 0.15);
	}

	&.disabled {
		background-color: #333;
		border-color: #444;
		opacity: 0.5;
		pointer-events: none;
		
		.amount-text {
			color: #666;
		}
		
		.gift-text {
			color: #666;
		}
	}
}

.amount-text {
	font-size: 28rpx;
	color: #e1e1e1;
	font-weight: 500;
	margin-bottom: 5rpx;
}

.gift-text {
	font-size: 22rpx;
	color: #FF6B6B;
	font-weight: 500;
}

// 金额输入
.amount-input-wrapper {
	display: flex;
	align-items: center;
	background-color: #444;
	border-radius: 12rpx;
	padding: 0 25rpx;
	height: 100rpx;
	border: 2rpx solid #555;
	transition: border-color 0.3s ease;

	&:focus-within {
		border-color: #007AFF;
		background-color: #333;
	}
}

.currency-symbol {
	font-size: 32rpx;
	color: #e1e1e1;
	margin-right: 15rpx;
	font-weight: 500;
}

.amount-input {
	flex: 1;
	font-size: 32rpx;
	color: #e1e1e1;
	height: 100%;
	background-color: transparent;
	border: none;
}

.amount-tips {
	display: flex;
	justify-content: space-between;
	margin-top: 15rpx;
	padding: 0 5rpx;
}

.tip-text {
	font-size: 24rpx;
	color: #e1e1e1;
}

// 提交按钮
.submit-btn {
	width: 100%;
	height: 100rpx;
	background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
	border-radius: 50rpx;
	font-size: 32rpx;
	font-weight: 500;
	margin-top: 20rpx;

	&[disabled] {
		background: #666 !important;
		color: #999 !important;
	}
}

// 支付说明
.notice-content {
	background-color: #444;
	border-radius: 12rpx;
	padding: 25rpx;
	border: 1rpx solid #555;
}

.notice-item {
	margin-bottom: 15rpx;

	&:last-child {
		margin-bottom: 0;
	}
}

.notice-text {
	font-size: 26rpx;
	color: #999;
	line-height: 1.6;
}
</style>