<template>
	<view class="container" :style="{ paddingTop: statusBarHeight + 'px', paddingBottom: safeAreaBottom + 'px' }">
		
		<scroll-view class="scroll-container" scroll-y="true">
			<!-- 红包基本信息 -->
			<view class="form-section">
				<view class="section-title">
					<uv-icon name="gift" color="#ff934a" size="20"></uv-icon>
					<text class="title-text">红包信息</text>
				</view>
				
				<view class="form-item" :class="{ 'has-error': errors.title }">
					<text class="form-label required">红包标题</text>
					<uv-input 
						v-model="formData.title" 
						placeholder="请输入红包标题（2-50字符）"
						maxlength="50"
						bgColor="#333"
						color="#555"
						placeholderStyle="color: #999"
						customStyle="border-radius: 12rpx; padding: 0 20rpx;"
						@input="validateField('title')"
						@blur="validateField('title')"
					></uv-input>
					<text class="char-count">{{ formData.title.length }}/50</text>
					<text class="error-text" v-if="errors.title">{{ errors.title }}</text>
				</view>
				
				<view class="form-item" :class="{ 'has-error': errors.blessing }">
					<text class="form-label required">祝福语</text>
					<uv-textarea 
						v-model="formData.blessing" 
						placeholder="请输入祝福语，让红包更有温度（2-100字符）"
						maxlength="100"
						:count="true"
						bgColor="#333"
						color="#555"
						placeholderStyle="color: #999"
						customStyle="padding: 20rpx;"
						height="50"
						@input="validateField('blessing')"
						@blur="validateField('blessing')"
					></uv-textarea>
					<text class="error-text" v-if="errors.blessing">{{ errors.blessing }}</text>
				</view>
				
				<view class="form-item" :class="{ 'has-error': errors.type }">
					<text class="form-label required">红包类型</text>
					<view class="type-selector">
						<view 
							v-for="typeOption in typeOptions" 
							:key="typeOption.value"
							class="type-option" 
							:class="{ active: formData.type === typeOption.value }"
							@click="selectType(typeOption.value)"
						>
							<text class="option-title">{{ typeOption.title }}</text>
							<text class="option-desc">{{ typeOption.desc }}</text>
						</view>
					</view>
					<text class="error-text" v-if="errors.type">{{ errors.type }}</text>
				</view>
			</view>
			
			<!-- 红包金额设置 -->
			<view class="form-section">
				<view class="section-title">
					<uv-icon name="gift" color="#ff934a" size="20"></uv-icon>
					<text class="title-text">金额设置</text>
				</view>
				
				<view class="form-item">
					<text class="form-label">总金额 (元)</text>
					<uv-input 
						v-model="formData.totalAmount" 
						placeholder="请输入总金额"
						type="number"
						bgColor="#333"
						color="#555"
						placeholderStyle="color: #999"
						customStyle="border-radius: 12rpx; padding: 0 20rpx;"
						@input="onAmountInput"
					></uv-input>
				</view>
				
				<view class="form-item">
					<text class="form-label">红包个数</text>
					<uv-input 
						v-model="formData.totalCount" 
						placeholder="请输入红包个数"
						type="number"
						bgColor="#333"
						color="#555"
						placeholderStyle="color: #999"
						customStyle="border-radius: 12rpx; padding: 0 20rpx;"
						@input="onCountInput"
					></uv-input>
				</view>
				
				<view class="amount-info" v-if="formData.totalAmount && formData.totalCount">
					<text class="info-text">平均每个红包: ¥{{averageAmount}}</text>
				</view>
			</view>		

			<!-- 领取条件 -->
			<view class="form-section" style="margin-bottom:115rpx;">
				<view class="section-title">
					<uv-icon name="setting" color="#ff934a" size="20"></uv-icon>
					<text class="title-text">领取条件</text>
				</view>
				
				<view class="form-item">
					<uv-radio-group v-model="formData.conditionType" direction="column">
						<uv-radio 
							v-for="(item, index) in conditionOptions" 
							:key="index" 
							:name="item.value" 
							:label="item.label"
							activeColor="#ff934a"
							labelcolor="#555"
							customStyle="margin:0 20rpx 20rpx 0;"
						></uv-radio>
					</uv-radio-group>
				</view>
				
				<view class="form-item" v-if="formData.conditionType !== 'NONE'">
					<text class="form-label">{{getConditionLabel()}}</text>
					<uv-input 
						v-model="formData.conditionValue" 
						:placeholder="getConditionPlaceholder()"
						type="number"
						bgColor="#333"
						color="#555"
						placeholderStyle="color: #999"
						customStyle="border-radius: 12rpx; padding: 0 20rpx;"
					></uv-input>
				</view>
			</view>
		</scroll-view>
		
		<!-- 固定在底部的提交按钮 -->
		<view class="fixed-submit-section">
			<uv-button 
				type="primary" 
				text="发放红包" 
				@click="submitForm"
				:loading="submitting"
				customStyle="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #8B0000; border: none; border-radius: 40rpx; height: 88rpx; font-size: 32rpx; font-weight: 600; box-shadow: 0 8rpx 25rpx rgba(255, 215, 0, 0.4); text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.2);"
			></uv-button>
		</view>	
	</view>
</template>

<script>
import authMixin from '@/mixins/auth.js';
import { createRedPacket } from '@/api/redpacket.js';

export default {
	mixins: [authMixin],
	data() {
		return {
			statusBarHeight: 0,
			safeAreaBottom: 0,
			formData: {
				title: '',
				blessing: '',
				type: 'RANDOM',
				totalAmount: '',
				totalCount: '',
				conditionType: 'NONE',
				conditionValue: '',
				expireTime: 0
			},
			typeOptions: [
				{ 
					title: '随机红包', 
					value: 'RANDOM',
					icon: '🎲',
					desc: '金额随机分配，更有惊喜'
				},
				{ 
					title: '固定红包', 
					value: 'FIXED',
					icon: '💰',
					desc: '每个红包金额相同'
				}
			],
			errors: {},

			conditionOptions: [
				{ label: '无条件', value: 'NONE' },
				{ label: '领取当日最低投注金额', value: 'MIN_BET' }
			],
			submitting: false
		}
	},
	
	onLoad() {
		// 获取系统信息
		const systemInfo = uni.getSystemInfoSync();
		this.statusBarHeight = systemInfo.statusBarHeight || 0;
		this.safeAreaBottom = systemInfo.safeAreaInsets ? systemInfo.safeAreaInsets.bottom : 0;
	},
	computed: {
		// 平均金额
		averageAmount() {
			if (!this.formData.totalAmount || !this.formData.totalCount) return '0.00';
			const avg = parseFloat(this.formData.totalAmount) / parseInt(this.formData.totalCount);
			return avg.toFixed(2);
		},
		

	},
	methods: {
		// 金额输入处理
		onAmountInput(value) {
			// 限制小数点后两位
			if (value.includes('.')) {
				const parts = value.split('.');
				if (parts[1] && parts[1].length > 2) {
					this.formData.totalAmount = parts[0] + '.' + parts[1].substring(0, 2);
				}
			}
		},
		
		// 个数输入处理
		onCountInput(value) {
			// 只允许整数
			this.formData.totalCount = value.replace(/[^0-9]/g, '');
			this.validateField('totalCount');
		},
		
		// 选择红包类型
		selectType(type) {
			this.formData.type = type;
			this.validateField('type');
		},
		
		// 验证单个字段
		validateField(field) {
			this.$set(this.errors, field, '');
			
			switch (field) {
				case 'title':
					if (!this.formData.title.trim()) {
						this.$set(this.errors, 'title', '请输入红包标题');
					} else if (this.formData.title.trim().length < 2) {
						this.$set(this.errors, 'title', '标题至少需要2个字符');
					}
					break;
				case 'blessing':
					if (!this.formData.blessing.trim()) {
						this.$set(this.errors, 'blessing', '请输入祝福语');
					} else if (this.formData.blessing.trim().length < 2) {
						this.$set(this.errors, 'blessing', '祝福语至少需要2个字符');
					}
					break;
				case 'totalAmount':
					if (!this.formData.totalAmount || parseFloat(this.formData.totalAmount) <= 0) {
						this.$set(this.errors, 'totalAmount', '请输入有效的总金额');
					} else if (parseFloat(this.formData.totalAmount) < 0.01) {
						this.$set(this.errors, 'totalAmount', '总金额不能少于0.01元');
					}
					break;
				case 'totalCount':
					if (!this.formData.totalCount || parseInt(this.formData.totalCount) < 2) {
						this.$set(this.errors, 'totalCount', '红包个数最少2个');
					} else if (parseInt(this.formData.totalCount) > 200) {
						this.$set(this.errors, 'totalCount', '红包个数不能超过200个');
					}
					break;
			}
		},
		
		// 验证所有字段
		validateAllFields() {
			this.validateField('title');
			this.validateField('blessing');
			this.validateField('totalAmount');
			this.validateField('totalCount');
			
			return Object.keys(this.errors).every(key => !this.errors[key]);
		},
		
		// 获取条件标签
		getConditionLabel() {
			const labelMap = {
				'MIN_BET': '领取当日最低投注金额 (元)'
			};
			return labelMap[this.formData.conditionType] || '';
		},
		
		// 获取条件占位符
		getConditionPlaceholder() {
			const placeholderMap = {
				'MIN_BET': '请输入领取当日最低投注金额'
			};
			return placeholderMap[this.formData.conditionType] || '';
		},
		

		
		// 表单验证
		validateForm() {
			if (!this.validateAllFields()) {
				// 找到第一个错误并显示
				const firstError = Object.values(this.errors).find(error => error);
				if (firstError) {
					uni.showToast({
						title: firstError,
						icon: 'none'
					});
				}
				return false;
			}
			
			if (parseInt(this.formData.totalCount) < 2) {
				uni.showToast({
					title: '红包个数最少2个',
					icon: 'none'
				});
				return false;
			}
			
			if (parseFloat(this.formData.totalAmount) < parseInt(this.formData.totalCount) * 0.01) {
				uni.showToast({
					title: '总金额不能少于红包个数的1分钱',
					icon: 'none'
				});
				return false;
			}
			

			
			if (this.formData.conditionType !== 'NONE' && !this.formData.conditionValue.trim()) {
				uni.showToast({
					title: '请输入领取条件值',
					icon: 'none'
				});
				return false;
			}
			
			return true;
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 重置表单数据
			this.formData = {
				title: '',
				blessing: '',
				type: 'RANDOM',
				totalAmount: '',
				totalCount: '',
				conditionType: 'NONE',
				conditionValue: ''
			};
			
			// 清空错误信息
			this.errors = {};
			
			// 停止下拉刷新
			setTimeout(() => {
				uni.stopPullDownRefresh();
				uni.showToast({
					title: '表单已重置',
					icon: 'success',
					duration: 1000
				});
			}, 500);
		},
		
		// 提交表单
		async submitForm() {
			if (!this.validateForm()) return;
			
			this.submitting = true;
			
			try {
				const data = {
					title: this.formData.title.trim(),
					blessing: this.formData.blessing.trim(),
					type: this.formData.type,
					total_amount: parseFloat(this.formData.totalAmount),
					total_count: parseInt(this.formData.totalCount),
					target_type: 2, // 固定为普通用户
					condition_type: this.formData.conditionType,
					condition_value: this.formData.conditionValue.trim(),
					expire_time: Math.floor(Date.now() / 1000) + 24 * 60 * 60 // 24小时后过期
				};
				
				const response = await createRedPacket(data);
				
				if (response.code === 1) {
					uni.showToast({
						title: '红包发放成功',
						icon: 'success'
					});
					
					// 触发事件通知列表页刷新
					uni.$emit('redpacketCreated');
					
					setTimeout(() => {
						uni.navigateBack();
					}, 1500);
				} else {
					uni.showToast({
						title: response.msg || '发放失败',
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
						title: '非法操作',
						icon: 'none',
						duration: 3000
					});
				}
			} finally {
				this.submitting = false;
			}
		}
	}
}
</script>

<style scoped>
.container {
	background-color: #f8f9fa;
}

.scroll-container {
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
	padding-bottom: 20rpx;
}

/* 表单区域 */
.form-section {
	margin: 15rpx 20rpx;
	background-color: #fff;
	border-radius: 20rpx;
	padding: 30rpx;
	border: 1px solid #e9ecef;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
}

.section-title {
	display: flex;
	align-items: center;
	margin-bottom: 30rpx;
}

.title-text {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;
	margin-left: 12rpx;
}

.form-item {
	margin-bottom: 30rpx;
}

.form-item:last-child {
	margin-bottom: 0;
}

.form-label {
	font-size: 26rpx;
	color: #333;
	margin-bottom: 16rpx;
	display: block;
}

/* 金额信息 */
.amount-info {
	padding: 20rpx;
	background-color: rgba(255, 147, 74, 0.1);
	border-radius: 12rpx;
	border: 1px solid rgba(255, 147, 74, 0.3);
	margin-top: 20rpx;
}

.info-text {
	font-size: 24rpx;
	color: #ff934a;
	text-align: center;
	display: block;
}

/* 日期时间选择器 */
.datetime-picker {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 20rpx;
	background-color: #f8f9fa;
	border-radius: 12rpx;
	border: 1px solid #e9ecef;
}

.datetime-text {
	font-size: 26rpx;
	color: #333;
}

.datetime-text.placeholder {
	color: #666;
}



/* 表单项状态 */
.form-item.has-error .form-input,
.form-item.has-error .form-textarea {
	border-color: #ff4757 !important;
}

.form-label.required::after {
	content: ' *';
	color: #ff4757;
}

.char-count {
	font-size: 20rpx;
	color: #666;
	text-align: right;
	display: block;
	margin-top: 8rpx;
}

.error-text {
	font-size: 22rpx;
	color: #ff4757;
	display: block;
	margin-top: 8rpx;
}

/* 红包类型选择器 */
.type-selector {
	display: flex;
	gap: 20rpx;
	margin-top: 16rpx;
}

.type-option {
	flex: 1;
	padding: 24rpx 16rpx;
	background-color: #f8f9fa;
	border: 2rpx solid #e9ecef;
	border-radius: 55rpx;
	text-align: center;
	transition: all 0.3s ease;
}

.type-option.active {
	border-color: #ff934a;
	background-color: rgba(255, 147, 74, 0.1);
}

.option-icon {
	font-size: 32rpx;
	margin-bottom: 8rpx;
	display: block;
}

.option-title {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;
	display: block;
	margin-bottom: 4rpx;
}

.option-desc {
	font-size: 20rpx;
	color: #666;
	display: block;
}

.type-option.active .option-title {
	color: #ff934a;
}

.type-option.active .option-desc {
	color: #ff934a;
	opacity: 0.8;
}

/* 固定在底部的提交区域 */
.fixed-submit-section {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	z-index: 999;
	background: linear-gradient(to top, #fff 0%, #fff 80%, rgba(255, 255, 255, 0.95) 90%, rgba(255, 255, 255, 0.8) 100%);
	padding: 20rpx 30rpx;
	padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
	border-top: 1rpx solid #e9ecef;
	box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.1);
	
	/* #ifdef APP-PLUS */
	/* 安卓APP特殊优化 */
	padding: 25rpx 40rpx;
	padding-bottom: calc(30rpx + env(safe-area-inset-bottom));
	background: linear-gradient(to top, #fff 0%, #fff 85%, rgba(255, 255, 255, 0.98) 95%, rgba(255, 255, 255, 0.9) 100%);
	border-top: 2rpx solid #e9ecef;
	box-shadow: 0 -6rpx 25rpx rgba(0, 0, 0, 0.15);
	/* #ifdef APP-PLUS-NVUE */
	padding: 30rpx 45rpx;
	padding-bottom: calc(35rpx + env(safe-area-inset-bottom));
	box-shadow: 0 -8rpx 30rpx rgba(0, 0, 0, 0.5);
	/* #endif */
	/* #endif */
	
	/* #ifdef H5 */
	padding: 18rpx 25rpx;
	padding-bottom: calc(18rpx + env(safe-area-inset-bottom));
	background: linear-gradient(to top, #fff 0%, #fff 75%, rgba(255, 255, 255, 0.92) 90%, rgba(255, 255, 255, 0.75) 100%);
	box-shadow: 0 -3rpx 15rpx rgba(0, 0, 0, 0.1);
	/* #endif */
	
	/* #ifdef MP */
	padding: 22rpx 35rpx;
	padding-bottom: calc(25rpx + 20rpx);
	background: linear-gradient(to top, #fff 0%, #fff 80%, rgba(255, 255, 255, 0.95) 100%);
	box-shadow: 0 -4rpx 18rpx rgba(0, 0, 0, 0.1);
	/* #endif */
}
</style>