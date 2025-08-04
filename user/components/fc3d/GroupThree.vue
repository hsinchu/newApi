<template>
	<view class="group-three">
		<!-- 选号区域 -->
		<view class="number-container">
			<view class="number-label">选择号码（至少选择2个号码）</view>
			<view class="number-grid">
				<view 
					v-for="num in 10" 
					:key="'group-three-' + (num-1)" 
					class="number-ball" 
					:class="{ 'selected': selectedNumbers.includes(num-1) }"
					@click="selectNumber(num-1)"
				>
					{{ num-1 }}
				</view>
			</view>
			
			<!-- 清除按钮 -->
			<view v-if="selectedNumbers.length > 0" class="clear-section">
				<view class="clear-btn" @click="clearAllSelections">
					<uv-icon name="close-circle-fill" color="#ff6b35" size="20"></uv-icon>
					<text class="clear-text">清</text>
				</view>
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
	name: 'GroupThree',
	props: {
		gameDesc: {
			type: String,
			default: ''
		},
		betType: {
			type: String,
			default: 'zusan_fushi'
		}
	},
	data() {
		return {
			// 选中的数字
			selectedNumbers: [],
			// 选中的注数
			selectedCount: 0
		}
	},
	methods: {
		// 选择数字球
		selectNumber(number) {
			const index = this.selectedNumbers.indexOf(number);
			if (index > -1) {
				// 如果已选中，则取消选中
				this.selectedNumbers.splice(index, 1);
			} else {
				// 如果未选中，则添加到选中列表
				this.selectedNumbers.push(number);
			}
			this.calculateSelectedCount();
		},
		
		// 计算选中的注数
		calculateSelectedCount() {
			const count = this.selectedNumbers.length;
			
			// 组三复式：不管选几个数都是一注，奖金会随着数量增多而变化
			if (count >= 2) {
				this.selectedCount = 1;
			} else {
				this.selectedCount = 0;
			}
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', { selected: this.selectedNumbers });
			this.$emit('betTypeChange', this.betType);
		},
		
		// 清空所有选择
		clearAllSelections() {
			this.selectedNumbers = [];
			this.selectedCount = 0;
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', { selected: this.selectedNumbers });
			this.$emit('betTypeChange', this.betType);
		},
		
		// clearAll方法别名
		clearAll() {
			this.clearAllSelections();
		},
		
		// 获取选中的数据 - 为welfare.vue提供统一接口
		getSelectedData() {
			return {
				numbers: { selected: this.selectedNumbers },
				count: this.selectedCount
			};
		}
	}
}
</script>

<style scoped>
.group-three {
	padding: 20rpx;
}

.number-container {
	margin-bottom: 30rpx;
}

.number-label {
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
	padding: 20rpx;
	background-color: #f8f9fa;
	border-radius: 45rpx;
}

.number-ball {
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 60rpx;
	height: 60rpx;
	font-size: 28rpx;
	font-weight: 600;
	border-radius: 50%;
	color: #424242;
	box-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.1);
	font-family: DINAlternate;
	background-color: var(--cp-color175);
	transition: all 0.2s ease;
}

.number-ball.selected {
	background-image: url('/static/images/ball.png');
	background-size: cover;
	background-position: center;
	background-color: transparent;
	color: #424242;
	transform: scale(1.1);
}

.number-ball:active {
	transform: scale(0.95);
}

.clear-section {
	display: flex;
	justify-content: center;
	margin-top: 20rpx;
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
	font-size: 25rpx;
	color: #666;
	line-height: 1.6;
}
</style>