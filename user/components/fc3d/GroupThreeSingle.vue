<template>
	<view class="group-three-single">
		<!-- 重号选择区域 -->
		<view class="selection-container">
			<view class="selection-label">选择重号（前两位相同数字）</view>
			<view class="number-grid">
				<view 
					v-for="num in [0,1,2,3,4,5,6,7,8,9]" 
					:key="'double-' + num"
					class="number-btn"
					:class="{ 'selected': selectedDouble === num, 'disabled': selectedSingle === num }"
					@click="selectDouble(num)"
				>
					{{ num }}
				</view>
			</view>
		</view>

		<!-- 单号选择区域 -->
		<view class="selection-container">
			<view class="selection-label">选择单号（第三位不同数字）</view>
			<view class="number-grid">
				<view 
					v-for="num in [0,1,2,3,4,5,6,7,8,9]" 
					:key="'single-' + num"
					class="number-btn"
					:class="{ 'selected': selectedSingle === num, 'disabled': selectedDouble === num }"
					@click="selectSingle(num)"
				>
					{{ num }}
				</view>
			</view>
		</view>

		<!-- 当前选择显示 -->
		<view v-if="selectedDouble !== null && selectedSingle !== null" class="current-selection">
			<view class="selection-display">
				<text class="selection-text">当前选择：</text>
				<view class="selected-number">
					{{ selectedDouble }}{{ selectedDouble }}{{ selectedSingle }}
				</view>
			</view>
		</view>

		<!-- 清除按钮 -->
		<view v-if="selectedDouble !== null || selectedSingle !== null" class="clear-section">
			<view class="clear-btn" @click="clearAllSelections">
				<uv-icon name="close-circle-fill" color="#ff6b35" size="20"></uv-icon>
				<text class="clear-text">清空选择</text>
			</view>
		</view>

		<!-- 游戏说明 -->
		<view class="game-desc">
			<text class="content-desc">{{ gameDesc }}</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'GroupThreeSingle',
	props: {
		gameDesc: {
			type: String,
			default: ''
		},
		betType: {
			type: String,
			default: 'zusan_danshi'
		}
	},
	data() {
		return {
			// 选中的重号（前两位相同数字）
			selectedDouble: null,
			// 选中的单号（第三位不同数字）
			selectedSingle: null,
			// 选中的注数
			selectedCount: 0
		}
	},
	computed: {
		// 当前选择的号码数组
		validNumbers() {
			if (this.selectedDouble !== null && this.selectedSingle !== null) {
				return [`${this.selectedDouble}${this.selectedDouble}${this.selectedSingle}`];
			}
			return [];
		}
	},
	watch: {
		// 监听选择变化
		validNumbers: {
			handler() {
				this.calculateSelectedCount();
			},
			immediate: true
		}
	},
	methods: {
		// 选择重号
		selectDouble(num) {
			if (num === this.selectedSingle) {
				return; // 不能选择与单号相同的数字
			}
			this.selectedDouble = this.selectedDouble === num ? null : num;
		},
		
		// 选择单号
		selectSingle(num) {
			if (num === this.selectedDouble) {
				return; // 不能选择与重号相同的数字
			}
			this.selectedSingle = this.selectedSingle === num ? null : num;
		},
		
		// 计算选中的注数
		calculateSelectedCount() {
			// 组三单式：有效号码数量就是注数
			this.selectedCount = this.validNumbers.length;
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.validNumbers);
			this.$emit('betTypeChange', this.betType);
		},
		
		// 清空所有选择
		clearAllSelections() {
			this.selectedDouble = null;
			this.selectedSingle = null;
			this.selectedCount = 0;
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.validNumbers);
			this.$emit('betTypeChange', this.betType);
		},
		
		// clearAll方法别名
		clearAll() {
			this.clearAllSelections();
		},
		
		// 获取选中的数据 - 为welfare.vue提供统一接口
		getSelectedData() {
			return {
				numbers: this.validNumbers,
				count: this.selectedCount
			};
		}
	}
}
</script>

<style scoped>
.group-three-single {
	padding: 20rpx;
}

.selection-container {
	margin-bottom: 30rpx;
}

.selection-label {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;
	margin-bottom: 20rpx;
	padding: 0 10rpx;
}

.number-grid {
	display: flex;
	flex-wrap: wrap;
	gap: 15rpx;
	justify-content: flex-start;
	padding: 0 10rpx;
}

.number-btn {
	cursor: pointer;
	display: inline-block;
	width: 60rpx;
	height: 60rpx;
	font-size: 25rpx;
	font-weight: 600;
	line-height: 1.9rem;
	margin: 8rpx;
	border-radius: 50%;
	color: #424242;
	-webkit-box-shadow: 0 .2rem .2rem 0 rgba(0, 0, 0, .2);
	box-shadow: 0 .2rem .2rem 0 rgba(0, 0, 0, .2);
	font-family: DINAlternate;
	background-color: var(--cp-color175);
	display: flex;
	align-items: center;
	justify-content: center;
}

.number-btn.selected {
	background-image: url('/static/images/ball.png');
	background-size: cover;
	background-position: center;
	background-color: transparent;
	color: #424242;
	transform: scale(1.1);
}

.number-btn:active {
	transform: scale(0.95);
}

.number-btn.disabled {
	background-color: #f5f5f5;
	color: #ccc;
	border-color: #eee;
	cursor: not-allowed;
}

.current-selection {
	margin-bottom: 30rpx;
	padding: 20rpx;
	background-color: #f8f9fa;
	border-radius: 8rpx;
}

.selection-display {
	display: flex;
	align-items: center;
	gap: 10rpx;
}

.selection-text {
	font-size: 26rpx;
	color: #666;
}

.selected-number {
	font-size: 30rpx;
	color: #4cd964;
	font-weight: bold;
	padding: 10rpx 20rpx;
	background-color: #fff;
	border-radius: 12rpx;
	border: 2rpx solid #4cd964;
	font-family: DINAlternate;
}

.clear-section {
	display: flex;
	justify-content: center;
	margin-bottom: 30rpx;
}

.clear-btn {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 12rpx 24rpx;
	background-color: #fff;
	border: 2rpx solid #ff6b35;
	border-radius: 25rpx;
	cursor: pointer;
	transition: all 0.2s ease;
}

.clear-btn:active {
	background-color: #ff6b35;
}

.clear-btn:active .clear-text {
	color: #fff;
}

.clear-text {
	font-size: 26rpx;
	color: #ff6b35;
	font-weight: 500;
}

.game-desc {
	text-align: left;
	padding: 25rpx;
	background-color: rgb(255, 249, 246);
	border-radius: 12rpx;
}

.content-desc {
	font-size: 26rpx;
	color: #666;
	line-height: 1.6;
}
</style>