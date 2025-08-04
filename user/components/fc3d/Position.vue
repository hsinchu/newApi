<template>
	<view class="position">
		<!-- 一码定位 -->
		<view v-if="betType === 'zuxuan_yima_dingwei'" class="position-mode">
			<!-- 位置选择 -->
			<view class="position-selector">
				<view class="selector-label">选择位置</view>
				<view class="position-tabs">
					<view 
						v-for="(pos, index) in positions" 
						:key="'pos-' + index"
						class="position-tab"
						:class="{ 'active': selectedPosition === pos.key }"
						@click="selectPosition(pos.key)"
					>
						{{ pos.name }}
					</view>
				</view>
			</view>
			
			<!-- 号码选择 -->
			<view v-if="selectedPosition" class="number-container">
				<view class="number-label">选择{{ getPositionName(selectedPosition) }}号码</view>
				<view class="number-grid">
					<view 
						v-for="num in 10" 
						:key="'num-' + (num-1)" 
						class="number-ball" 
						:class="{ 'selected': selectedNumbers.includes(num-1) }"
						@click="selectNumber(num-1)"
					>
						{{ num-1 }}
					</view>
				</view>
			</view>
		</view>
		
		<!-- 两码定位 -->
		<view v-else-if="betType === 'zuxuan_liangma_dingwei'" class="position-mode">
			<!-- 位置选择 -->
			<view class="position-selector">
				<view class="selector-label">选择位置组合</view>
				<view class="position-tabs">
					<view 
						v-for="(combo, index) in positionCombos" 
						:key="'combo-' + index"
						class="position-tab"
						:class="{ 'active': selectedPositionCombo === combo.key }"
						@click="selectPositionCombo(combo.key)"
					>
						{{ combo.name }}
					</view>
				</view>
			</view>
			
			<!-- 号码选择 -->
			<view v-if="selectedPositionCombo" class="two-position-container">
				<view 
					v-for="(pos, index) in getTwoPositions()" 
					:key="'twopos-' + index"
					class="single-position"
				>
					<view class="position-label">{{ pos.name }}</view>
					<view class="number-grid">
						<view 
							v-for="num in 10" 
							:key="pos.key + '-' + (num-1)" 
							class="number-ball" 
							:class="{ 'selected': twoPositionNumbers[pos.key] && twoPositionNumbers[pos.key].includes(num-1) }"
							@click="selectTwoPositionNumber(pos.key, num-1)"
						>
							{{ num-1 }}
						</view>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 一码不定位 -->
		<view v-else-if="betType === 'zuxuan_yima_budingwei'" class="position-mode">
			<!-- 号码选择 -->
			<view class="number-container">
				<view class="number-label">选择号码（开奖号码任意位置包含即中奖）</view>
				<view class="number-grid">
					<view 
						v-for="num in 10" 
						:key="'unfixed-' + (num-1)" 
						class="number-ball" 
						:class="{ 'selected': selectedNumbers.includes(num-1) }"
						@click="selectNumber(num-1)"
					>
						{{ num-1 }}
					</view>
				</view>
			</view>
		</view>

		<!-- 清除按钮 -->
		<view v-if="hasSelection" class="clear-section">
			<view class="clear-btn" @click="clearAllSelections">
				<uv-icon name="close-circle-fill" color="#ff6b35" size="20"></uv-icon>
				<text class="clear-text">清空</text>
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
	name: 'Position',
	props: {
		gameDesc: {
			type: String,
			default: ''
		},
		betType: {
			type: String,
			default: 'zuxuan_yima_dingwei'
		}
	},
	data() {
		return {
			// 位置选项
			positions: [
				{ key: 'bai', name: '百位' },
				{ key: 'shi', name: '十位' },
				{ key: 'ge', name: '个位' }
			],
			// 两码定位组合
			positionCombos: [
				{ key: 'bai_shi', name: '百十位' },
				{ key: 'bai_ge', name: '百个位' },
				{ key: 'shi_ge', name: '十个位' }
			],
			// 选中的位置（一码定位）
			selectedPosition: '',
			// 选中的位置组合（两码定位）
			selectedPositionCombo: '',
			// 选中的号码（一码定位、一码不定位）
			selectedNumbers: [],
			// 两码定位选中的号码
			twoPositionNumbers: {
				bai: [],
				shi: [],
				ge: []
			},
			// 选中的注数
			selectedCount: 0
		}
	},
	computed: {
		// 是否有选择
		hasSelection() {
			if (this.betType === 'zuxuan_yima_dingwei') {
				return this.selectedPosition && this.selectedNumbers.length > 0;
			} else if (this.betType === 'zuxuan_liangma_dingwei') {
				const positions = this.getTwoPositions();
				return positions.some(pos => this.twoPositionNumbers[pos.key] && this.twoPositionNumbers[pos.key].length > 0);
			} else if (this.betType === 'zuxuan_yima_budingwei') {
				return this.selectedNumbers.length > 0;
			}
			return false;
		}
	},
	methods: {
		// 选择位置（一码定位）
		selectPosition(position) {
			this.selectedPosition = position;
			this.selectedNumbers = [];
			this.calculateSelectedCount();
		},
		
		// 选择位置组合（两码定位）
		selectPositionCombo(combo) {
			this.selectedPositionCombo = combo;
			this.twoPositionNumbers = {
				bai: [],
				shi: [],
				ge: []
			};
			this.calculateSelectedCount();
		},
		
		// 选择号码（一码定位、一码不定位）
		selectNumber(number) {
			const index = this.selectedNumbers.indexOf(number);
			if (index > -1) {
				this.selectedNumbers.splice(index, 1);
			} else {
				this.selectedNumbers.push(number);
			}
			this.calculateSelectedCount();
		},
		
		// 选择两码定位号码
		selectTwoPositionNumber(position, number) {
			if (!this.twoPositionNumbers[position]) {
				this.twoPositionNumbers[position] = [];
			}
			
			const index = this.twoPositionNumbers[position].indexOf(number);
			if (index > -1) {
				this.twoPositionNumbers[position].splice(index, 1);
			} else {
				this.twoPositionNumbers[position].push(number);
			}
			this.calculateSelectedCount();
		},
		
		// 获取位置名称
		getPositionName(key) {
			const position = this.positions.find(p => p.key === key);
			return position ? position.name : '';
		},
		
		// 获取两码定位的位置
		getTwoPositions() {
			if (!this.selectedPositionCombo) return [];
			
			switch (this.selectedPositionCombo) {
				case 'bai_shi':
					return [{ key: 'bai', name: '百位' }, { key: 'shi', name: '十位' }];
				case 'bai_ge':
					return [{ key: 'bai', name: '百位' }, { key: 'ge', name: '个位' }];
				case 'shi_ge':
					return [{ key: 'shi', name: '十位' }, { key: 'ge', name: '个位' }];
				default:
					return [];
			}
		},
		
		// 计算选中的注数
		calculateSelectedCount() {
			if (this.betType === 'zuxuan_yima_dingwei') {
				// 一码定位：必须选择位置且选中号码
				if (this.selectedPosition && this.selectedNumbers.length > 0) {
					this.selectedCount = this.selectedNumbers.length;
				} else {
					// 如果只选择了号码但没选位置，或只选了位置没选号码，注数为0但仍要通知父组件有选择
					this.selectedCount = 0;
				}
			} else if (this.betType === 'zuxuan_liangma_dingwei') {
				// 两码定位：必须选择位置组合且两个位置都有选中号码
				const positions = this.getTwoPositions();
				if (positions.length === 2 && this.selectedPositionCombo) {
					const count1 = this.twoPositionNumbers[positions[0].key] ? this.twoPositionNumbers[positions[0].key].length : 0;
					const count2 = this.twoPositionNumbers[positions[1].key] ? this.twoPositionNumbers[positions[1].key].length : 0;
					if (count1 > 0 && count2 > 0) {
						this.selectedCount = count1 * count2;
					} else {
						// 如果选择了位置组合但号码不完整，注数为0但仍要通知父组件有选择
						this.selectedCount = 0;
					}
				} else {
					this.selectedCount = 0;
				}
			} else if (this.betType === 'zuxuan_yima_budingwei') {
				// 一码不定位：选中号码数量
				this.selectedCount = this.selectedNumbers.length;
			} else {
				this.selectedCount = 0;
			}
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.getSelectedNumbersData());
			this.$emit('betTypeChange', this.betType);
		},
		
		// 获取选中号码数据
		getSelectedNumbersData() {
			if (this.betType === 'zuxuan_yima_dingwei') {
				// 一码定位：必须选择位置且有选中号码才返回数据
				if (this.selectedPosition && this.selectedNumbers.length > 0) {
					return {
						position: this.selectedPosition,
						numbers: this.selectedNumbers,
						// 为后端提供格式化的数据
						formatted: {
							position: this.selectedPosition,
							numbers: this.selectedNumbers
						}
					};
				} else {
					return {
						position: '',
						numbers: [],
						formatted: {
							position: '',
							numbers: []
						}
					};
				}
			} else if (this.betType === 'zuxuan_liangma_dingwei') {
				// 两码定位：必须选择位置组合且至少一个位置有选中号码才返回数据
				if (this.selectedPositionCombo) {
					const positions = this.getTwoPositions();
					const hasAnyNumbers = positions.some(pos => 
						this.twoPositionNumbers[pos.key] && this.twoPositionNumbers[pos.key].length > 0
					);
					if (hasAnyNumbers) {
						return {
							positionCombo: this.selectedPositionCombo,
							numbers: this.twoPositionNumbers,
							// 为后端提供格式化的数据
							formatted: {
								positionCombo: this.selectedPositionCombo,
								positions: positions.map(pos => pos.key),
								numbers: this.twoPositionNumbers
							}
						};
					}
				}
				return {
					positionCombo: '',
					numbers: { bai: [], shi: [], ge: [] },
					formatted: {
						positionCombo: '',
						positions: [],
						numbers: { bai: [], shi: [], ge: [] }
					}
				};
			} else if (this.betType === 'zuxuan_yima_budingwei') {
				return {
					numbers: this.selectedNumbers,
					// 一码不定位不需要位置参数
					formatted: {
						numbers: this.selectedNumbers
					}
				};
			}
			return {};
		},
		
		// 清空所有选择
		clearAllSelections() {
			this.selectedPosition = '';
			this.selectedPositionCombo = '';
			this.selectedNumbers = [];
			this.twoPositionNumbers = {
				bai: [],
				shi: [],
				ge: []
			};
			this.selectedCount = 0;
			
			// 发射事件给父组件
			this.$emit('selectedCountChange', this.selectedCount);
			this.$emit('selectedNumbersChange', this.getSelectedNumbersData());
			this.$emit('betTypeChange', this.betType);
		},
		
		// clearAll方法别名
		clearAll() {
			this.clearAllSelections();
		},
		
		// 获取选中的数据 - 为welfare.vue提供统一接口
		getSelectedData() {
			return {
				numbers: this.getSelectedNumbersData(),
				count: this.selectedCount
			};
		},
		
		// 获取格式化的投注数据 - 用于提交到后端
		getFormattedBetData(multiplier, betAmount) {
			const numbersData = this.getSelectedNumbersData();
			
			// 检查是否有有效数据
			if (this.selectedCount === 0) return null;
			
			// 简化投注数据，只保留numbers的内容
			let simplifiedNumbers;
			if (this.betType === 'zuxuan_yima_dingwei') {
				// 一码定位：保留position和numbers
				simplifiedNumbers = {
					position: this.selectedPosition,
					numbers: this.selectedNumbers
				};
			} else if (this.betType === 'zuxuan_liangma_dingwei') {
				// 两码定位：保留positionCombo和numbers
				simplifiedNumbers = {
					positionCombo: this.selectedPositionCombo,
					numbers: this.twoPositionNumbers
				};
			} else if (this.betType === 'zuxuan_yima_budingwei') {
				// 一码不定位：只保留numbers
				simplifiedNumbers = {
					numbers: this.selectedNumbers
				};
			}
			
			// 构造投注信息
			return {
				type_key: this.betType,
				type_name: this.getTypeName(),
				numbers: simplifiedNumbers,
				note: this.selectedCount,
				money: betAmount,
				multiplier: multiplier,
				calculated_amount: this.selectedCount * multiplier * betAmount
			};
		},
		
		// 获取投注类型名称
		getTypeName() {
			const typeMap = {
				'zuxuan_yima_dingwei': '一码定位',
				'zuxuan_liangma_dingwei': '两码定位',
				'zuxuan_yima_budingwei': '一码不定位'
			};
			return typeMap[this.betType] || this.betType;
		}
	}
}
</script>

<style scoped>
.position {
	padding: 20rpx;
}

.position-mode {
	margin-bottom: 30rpx;
}

.mode-title {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
	margin-bottom: 25rpx;
	text-align: center;
}

.position-selector {
	margin-bottom: 25rpx;
}

.selector-label {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;
	margin-bottom: 15rpx;
	padding: 0 10rpx;
}

.position-tabs {
	display: flex;
	gap: 15rpx;
	padding: 0 10rpx;
}

.position-tab {
	flex: 1;
	padding: 15rpx 20rpx;
	background-color: #f8f9fa;
	border: 2rpx solid #e9ecef;
	border-radius: 25rpx;
	text-align: center;
	font-size: 26rpx;
	color: #666;
	cursor: pointer;
	transition: all 0.2s ease;
}

.position-tab.active {
	background-color: #007aff;
	border-color: #007aff;
	color: #fff;
	font-weight: 600;
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
	gap: 12rpx;
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
	font-size: 24rpx;
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

.two-position-container {
	display: flex;
	flex-direction: column;
	gap: 25rpx;
}

.single-position {
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
	font-size: 26rpx;
	color: #666;
	line-height: 1.6;
}
</style>