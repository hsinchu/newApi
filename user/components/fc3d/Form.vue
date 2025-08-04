<template>
	<view class="form">
		<!-- 和值大小玩法 -->
		<view v-if="betType === 'hezhi_daxiao'" class="form-mode">
			<view class="sum-container">
				<view class="sum-label">选择和值大小（和值0-27）</view>
				<view class="option-grid">
					<view 
					v-for="option in sumBigSmallOptions" 
					:key="'sum-' + option.key" 
					class="option-ball" 
					:class="{ 'selected': selectedSumOptions.includes(option.label) }"
					@click="selectSumOption(option.key)"
				>
					{{ option.label }}
				</view>
				</view>
				<view class="sum-desc">
					<text class="desc-text">小：和值0-13，大：和值14-27</text>
				</view>
			</view>
		</view>
		
		<!-- 和值单双玩法 -->
		<view v-else-if="betType === 'hezhi_danshuang'" class="form-mode">
			<view class="sum-container">
				<view class="sum-label">选择和值单双（和值0-27）</view>
				<view class="option-grid">
					<view 
					v-for="option in sumOddEvenOptions" 
					:key="'sum-' + option.key" 
					class="option-ball" 
					:class="{ 'selected': selectedSumOptions.includes(option.label) }"
					@click="selectSumOption(option.key)"
				>
					{{ option.label }}
				</view>
				</view>
				<view class="sum-desc">
					<text class="desc-text">单：和值为奇数，双：和值为偶数</text>
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
	name: 'Form',
	props: {
		betType: {
			type: String,
			default: 'hezhi_daxiao'
		},
		gameDesc: {
			type: String,
			default: '选择和值大小、单双进行投注，选择正确即中奖。'
		}
	},
	data() {
		return {
			// 和值大小选项
			sumBigSmallOptions: [
				{ key: 'big', label: '大' },
				{ key: 'small', label: '小' }
			],
			// 和值单双选项
			sumOddEvenOptions: [
				{ key: 'odd', label: '单' },
				{ key: 'even', label: '双' }
			],
			// 选中的和值选项
			selectedSumOptions: [],
			// 选中的对子
			selectedPairs: [],
			// 选中注数
			selectedCount: 0
		};
	},
	computed: {
		// 是否有选择
		hasSelection() {
			return this.selectedSumOptions.length > 0;
		}
	},
	watch: {
		// 监听betType变化，重置选择
		betType() {
			this.clearAllSelections();
		}
	},
	methods: {
		// 选择和值选项（大小、单双）- 单选模式
		selectSumOption(option) {
			// 转换选项值为中文
			let selectedValue = '';
			if (this.betType === 'hezhi_daxiao') {
				selectedValue = option === 'big' ? '大' : '小';
			} else if (this.betType === 'hezhi_danshuang') {
				selectedValue = option === 'odd' ? '单' : '双';
			}
			
			if (this.selectedSumOptions.includes(selectedValue)) {
				// 如果已选中，则取消选择
				this.selectedSumOptions = [];
			} else {
				// 单选模式：清空之前的选择，只保留当前选择
				this.selectedSumOptions = [selectedValue];
			}
			this.calculateSelectedCount();
		},
		
		// 计算选中注数
		calculateSelectedCount() {
			// 和值大小、单双玩法：每个选中的选项为1注
            this.selectedCount = this.selectedSumOptions.length;
			
			// 发送事件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.getSelectedData());
			this.$emit('betTypeChange', this.betType);
		},
		
		// 获取选中数据
		getSelectedData() {
			return {
                type: this.betType || '',
                sumOptions: this.selectedSumOptions || [],
                numbers: this.selectedSumOptions || [],
                count: this.selectedCount
            };
		},
		
		// 清空所有选择
		clearAllSelections() {
			this.selectedSumOptions = [];
			this.selectedPairs = [];
			this.calculateSelectedCount();
		},
		
		// clearAll方法别名
		clearAll() {
			this.clearAllSelections();
		}
	}
}
</script>

<style scoped>
.form {
	padding: 20rpx;
}

.form-mode {
	margin-bottom: 30rpx;
}

.mode-title {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
	margin-bottom: 25rpx;
	text-align: center;
}

.sum-container {
	margin-bottom: 25rpx;
	padding: 20rpx;
	background-color: #fff;
	border-radius: 45rpx;
	border: 2rpx solid #e9ecef;
}

.sum-label {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
	margin-bottom: 15rpx;
	text-align: center;
}

.sum-desc {
	margin-top: 20rpx;
	padding: 20rpx;
	background-color: #f8f9fa;
	border-radius: 8rpx;
}

.desc-text {
	font-size: 24rpx;
	color: #666;
	line-height: 1.5;
}

.position-container {
	margin-bottom: 25rpx;
	padding: 20rpx;
	background-color: #fff;
	border-radius: 12rpx;
	border: 2rpx solid #e9ecef;
}

.position-label {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
	margin-bottom: 15rpx;
	text-align: center;
}

.pair-container {
	padding: 20rpx;
	background-color: #fff;
	border-radius: 12rpx;
	border: 2rpx solid #e9ecef;
}

.pair-label {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;
	margin-bottom: 20rpx;
	text-align: center;
}

.pair-desc {
	margin-top: 20rpx;
	padding: 20rpx;
	background-color: #f8f9fa;
	border-radius: 8rpx;
}

.option-grid {
	display: flex;
	justify-content: center;
	gap: 20rpx;
	flex-wrap: wrap;
}

.option-ball {
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 80rpx;
	height: 80rpx;
	font-size: 26rpx;
	font-weight: 600;
	border-radius: 50%;
	color: #424242;
	box-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.1);
	background-color: var(--cp-color175);
	transition: all 0.2s ease;
	border: 2rpx solid transparent;
}

.pair-ball {
	width: 100rpx;
	height: 100rpx;
	border-radius:50%;
	font-size: 24rpx;
}

.option-ball.selected {
	background-color: orangered;
	border-color: orangered;
	color: #fff;
	transform: scale(1.05);
}

.option-ball:active {
	transform: scale(0.95);
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