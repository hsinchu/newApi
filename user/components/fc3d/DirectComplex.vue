<template>
	<view class="direct-complex">
		
		<!-- 百位 -->
		<view class="position-container">
			<view class="position-row">
				<view class="position-label-container">
					<view class="position-label">百位</view>
					<view v-if="selectedNumbers.bai.length > 0" class="clear-section">
						<view class="clear-btn" @click="clearPosition('bai')">
							<uv-icon name="close-circle-fill" color="#ff6b35" size="20"></uv-icon>
							<text class="clear-text">清</text>
						</view>
					</view>
				</view>
				<view class="number-grid-two-rows">
					<view class="number-row">
						<view 
							v-for="num in 5" 
							:key="'bai-' + (num-1)" 
							class="number-ball" 
							:class="{ 'selected': selectedNumbers.bai.includes(num-1) }"
							@click="selectNumber('bai', num-1)"
						>
							{{ num-1 }}
						</view>
					</view>
					<view class="number-row">
						<view 
							v-for="num in 5" 
							:key="'bai-' + (num+4)" 
							class="number-ball" 
							:class="{ 'selected': selectedNumbers.bai.includes(num+4) }"
							@click="selectNumber('bai', num+4)"
						>
							{{ num+4 }}
						</view>
					</view>
				</view>
				<view class="right-space"></view>
			</view>
		</view>
		
		<!-- 十位 -->
		<view class="position-container">
			<view class="position-row">
				<view class="position-label-container">
					<view class="position-label">十位</view>
					<view v-if="selectedNumbers.shi.length > 0" class="clear-section">
						<view class="clear-btn" @click="clearPosition('shi')">
							<uv-icon name="close-circle-fill" color="#ff6b35" size="20"></uv-icon>
							<text class="clear-text">清</text>
						</view>
					</view>
				</view>
				<view class="number-grid-two-rows">
					<view class="number-row">
						<view 
							v-for="num in 5" 
							:key="'shi-' + (num-1)" 
							class="number-ball" 
							:class="{ 'selected': selectedNumbers.shi.includes(num-1) }"
							@click="selectNumber('shi', num-1)"
						>
							{{ num-1 }}
						</view>
					</view>
					<view class="number-row">
						<view 
							v-for="num in 5" 
							:key="'shi-' + (num+4)" 
							class="number-ball" 
							:class="{ 'selected': selectedNumbers.shi.includes(num+4) }"
							@click="selectNumber('shi', num+4)"
						>
							{{ num+4 }}
						</view>
					</view>
				</view>
				<view class="right-space"></view>
			</view>
		</view>
		
		<!-- 个位 -->
		<view class="position-container">
			<view class="position-row">
				<view class="position-label-container">
					<view class="position-label">个位</view>
					<view v-if="selectedNumbers.ge.length > 0" class="clear-section">
						<view class="clear-btn" @click="clearPosition('ge')">
							<uv-icon name="close-circle-fill" color="#ff6b35" size="20"></uv-icon>
							<text class="clear-text">清</text>
						</view>
					</view>
				</view>
				<view class="number-grid-two-rows">
					<view class="number-row">
						<view 
							v-for="num in 5" 
							:key="'ge-' + (num-1)" 
							class="number-ball" 
							:class="{ 'selected': selectedNumbers.ge.includes(num-1) }"
							@click="selectNumber('ge', num-1)"
						>
							{{ num-1 }}
						</view>
					</view>
					<view class="number-row">
						<view 
							v-for="num in 5" 
							:key="'ge-' + (num+4)" 
							class="number-ball" 
							:class="{ 'selected': selectedNumbers.ge.includes(num+4) }"
							@click="selectNumber('ge', num+4)"
						>
							{{ num+4 }}
						</view>
					</view>
				</view>
				<view class="right-space"></view>
			</view>
		</view>

		<view class="game-desc">
			<text class="content-desc">{{ gameDesc }}</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'DirectComplex',
	props: {
		gameDesc: {
			type: String,
			default: ''
		},
		betType: {
			type: String,
			default: 'zhixuan_fushi'
		}
	},
	data() {
		return {
			// 选中的数字
			selectedNumbers: {
				bai: [], // 百位选中的数字
				shi: [], // 十位选中的数字
				ge: []   // 个位选中的数字
			},
			// 选中的注数
			selectedCount: 0
		}
	},
	methods: {
		// 选择数字球
		selectNumber(position, number) {
			const index = this.selectedNumbers[position].indexOf(number);
			if (index > -1) {
				// 如果已选中，则取消选中
				this.selectedNumbers[position].splice(index, 1);
			} else {
				// 如果未选中，则添加到选中列表（复式允许多选）
				this.selectedNumbers[position].push(number);
			}
			this.calculateSelectedCount();
		},
		
		// 计算选中的注数
		calculateSelectedCount() {
			// 直选复式：注数 = 百位选中数 × 十位选中数 × 个位选中数
			const baiCount = this.selectedNumbers.bai.length || 0;
			const shiCount = this.selectedNumbers.shi.length || 0;
			const geCount = this.selectedNumbers.ge.length || 0;
			
			// 只有当三个位置都有选择时才计算注数
			if (baiCount > 0 && shiCount > 0 && geCount > 0) {
				this.selectedCount = baiCount * shiCount * geCount;
			} else {
				this.selectedCount = 0;
			}
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.selectedNumbers);
			this.$emit('betTypeChange', this.betType);
		},
		
		// 清除某个位置的选择
		clearPosition(position) {
			this.selectedNumbers[position] = [];
			this.calculateSelectedCount();
		},
		
		// 清空所有选择
		clearAllSelections() {
			this.selectedNumbers = {
				bai: [],
				shi: [],
				ge: []
			};
			this.selectedCount = 0;
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.selectedNumbers);
			this.$emit('betTypeChange', this.betType);
		},
		
		// clearAll方法别名
		clearAll() {
			this.clearAllSelections();
		},
		
		// 获取选中的数据 - 为welfare.vue提供统一接口
		getSelectedData() {
			return {
				numbers: this.selectedNumbers,
				count: this.selectedCount
			};
		}
	}
}
</script>

<style scoped>
/* 直选复式样式 */
.direct-complex {
	
}

.game-desc {
	text-align:left;
	padding:25rpx;
	background-color: rgb(255, 249, 246);
	flex: 1;
}

.content-desc {
	font-size: 25rpx;
	color: #666;
	line-height: 1.6;
}

.position-container {
	margin-bottom: 30rpx;
	padding: 0 20rpx;
}

.position-row {
	display: flex;
	align-items: flex-start;
}

.position-label-container {
	width: 80rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10rpx;
}

.position-label {
	font-size: 25rpx;
	color: #666666;
	font-weight: 500;
	text-align: center;
}

.number-grid-two-rows {
	flex: 1;
	margin: 0 20rpx;
}

.number-row {
	display: flex;
	gap: 15rpx;
	margin-bottom: 15rpx;
}

.number-row:last-child {
	margin-bottom: 0;
}

.number-ball {
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
	margin-top: 10rpx;
}

.clear-btn {
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

.right-space {
	width: 25rpx;
}
</style>