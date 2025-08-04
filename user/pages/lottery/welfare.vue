<template>
	<view class="page-container">
		<!-- 顶部开奖信息区域 -->
		<view v-if="showHeader" class="lottery-header">
			<view class="header-content">
				<view class="lottery-info">
					<view class="lottery-title">
						<text class="title-text">{{ gameInfo.type_name || '--' }}</text>
						<text class="period-text">{{ currentPeriodInfo.period_number }}期</text>
					</view>
					<view class="countdown-info">
						<text class="countdown-label">{{ getStatusText() }}</text>
						<text class="countdown-time">{{ countdown }}</text>
					</view>
				</view>
				<view class="winning-numbers">
					<text class="period-number">{{ currentPeriodInfo.last_open_period_no }} 期</text>
					<view class="number-balls">
						<view 
							v-for="(number, index) in getLastOpenNumbers()" 
							:key="index" 
							class="number-ball"
						>
							<text class="ball-number">{{ number }}</text>
						</view>
					</view>

				</view>
			</view>
		</view>	
		
		<view class="container">
			<view class="content">
				<view class="lottery-tabs-container">
					<uv-vtabs 
						:current="activeTab" 
						:list="tabList" 
						@change="onTabChange"
						:chain="false"
						barWidth="128rpx"
						:hdHeight="dynamicHeaderHeight"
						barBgColor="#e2d5cf"
						:barItemStyle="{
							textAlign: 'center',
							padding: '15rpx',
							color: '#424242',
						}"
						:barItemActiveStyle="{
							backgroundColor: '#f7e9de',
							color: '#ff6b35',
							padding: '15rpx',
							fontWeight: '550',
							textAlign: 'center',	
						}"
						:barItemActiveLineStyle="{
							backgroundColor: '#ff6b35',
							width: '0'
						}"
					></uv-vtabs>
					
					<!-- 右侧内容区域 -->
					<view class="right-content">
						<!-- 二级选项卡 -->
						<view class="sub-tabs">
							<uv-tabs 
								:current="Number(activeSubTab)" 
								:list="subTabList" 
								@change="onSubTabChange"
								:scrollable="false"
								:activeStyle="{
									color: '#ff6b35'
								}"
								lineColor="#ff6b35"
							></uv-tabs>
						</view>
						
						<!-- 内容区域 -->
						<view class="tab-content">
							
							<!-- 奖金区间显示 -->
							<view class="bonus-info" v-if="minBonus != maxBonus">
								<text>最小奖金：{{ minBonus }}元，最大奖金：{{ maxBonus }}元</text>
							</view>
							<view class="bonus-info" v-if="minBonus == maxBonus">
								<text>奖金：{{ maxBonus }}元</text>
							</view>
							
							<!-- 直选类组件 -->
					<!-- 直选定位 -->
					<DirectSelect 
						v-if="activeTab === 0 && activeSubTab === 0" 
						:gameDesc="currentContent.desc"
						:betType="currentBetType"
						@selectedCountChange="onSelectedCountChange"
						@selectedNumbersChange="onSelectedNumbersChange"
						@betTypeChange="onBetTypeChange"
						ref="directSelect"
					/>
					
					<!-- 和值 -->
					<SumValue 
						v-if="activeTab === 0 && activeSubTab === 1" 
						:gameDesc="currentContent.desc"
						:betType="currentBetType"
						@selectedCountChange="onSelectedCountChange"
						@selectedNumbersChange="onSelectedNumbersChange"
						@betTypeChange="onBetTypeChange"
						ref="sumValue"
					/>
					
					<!-- 跨度 -->
					<Span 
						v-if="activeTab === 0 && activeSubTab === 2" 
						:gameDesc="currentContent.desc"
						:betType="currentBetType"
						@selectedCountChange="onSelectedCountChange"
						@selectedNumbersChange="onSelectedNumbersChange"
						@betTypeChange="onBetTypeChange"
						ref="span"
					/>
							
							<!-- 组三类组件 -->
						<!-- 组三单式 -->
						<GroupThreeSingle 
							v-if="activeTab === 1 && activeSubTab === 0" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="groupThreeSingle"
						/>
						
						<!-- 组三复式 -->
						<GroupThree 
							v-if="activeTab === 1 && activeSubTab === 1" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="groupThree"
						/>
						

						
						<!-- 组六类组件 -->
						<!-- 组六复式 -->
						<GroupSix 
							v-if="activeTab === 2 && activeSubTab === 0" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="groupSix"
						/>
						

							
							<!-- 定位类组件 -->
						<!-- 一码定位 -->
						<Position 
							v-if="activeTab === 3 && activeSubTab === 0" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="positionOne"
						/>
						
						<!-- 两码定位 -->
						<Position 
							v-if="activeTab === 3 && activeSubTab === 1" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="positionTwo"
						/>
						
						<!-- 一码不定位 -->
						<Position 
							v-if="activeTab === 3 && activeSubTab === 2" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="positionUnfixed"
						/>
							
							<!-- 形态类组件 -->
						<!-- 大小 -->
						<Form 
							v-if="activeTab === 4 && activeSubTab === 0" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="formBig"
						/>
						
						<!-- 单双 -->
						<Form 
							v-if="activeTab === 4 && activeSubTab === 1" 
							:gameDesc="currentContent.desc"
							:betType="currentBetType"
							@selectedCountChange="onSelectedCountChange"
							@selectedNumbersChange="onSelectedNumbersChange"
							@betTypeChange="onBetTypeChange"
							ref="formOdd"
						/>
						

						</view>
					</view>
				</view>
			</view>
			
			<!-- 底部固定操作栏 -->
			<view class="bottom-action-bar">
				<view class="quick-selection-section">
					<!-- 清除选号区 -->
					<view class="clear-section" :class="{ 'has-selection': selectedCount > 0, 'disabled': selectedCount === 0 }" @click="clearAllSelections">
						<uv-icon name="trash" size="28rpx" :color="selectedCount > 0 ? '#ff6b35' : '#787878'"></uv-icon>
						<text class="clear-text" :class="{ 'active-text': selectedCount > 0, 'disabled-text': selectedCount === 0 }">清除</text>
					</view>
					
					<!-- 注数和金额汇总 -->
					<view class="summary-info">
						<text>已选{{ selectedCount }}注/金额{{ selectedCount * multiplier * betAmount }}元</text>
					</view>
					<!-- 倍数选择 -->
					<view class="multiplier-section">
						<text class="label">倍数</text>
						<uv-number-box v-model="multiplier" :min="1" :max="99" @change="onMultiplierChange" />
					</view>
				</view>
				<view class="bottom-buttons">
					<view class="bottom-btn" @click="showRecentBets">
						<uv-icon name="clock" size="32rpx" color="#666"></uv-icon>
						<text class="btn-text">近期投注</text>
					</view>
					<view class="bottom-btn basket-btn" :class="{ 'has-content': betList.length > 0 }" @click="showBetBasket">
						<uv-icon name="shopping-cart" size="32rpx" :color="betList.length > 0 ? '#ff6b35' : '#666'"></uv-icon>
						<text class="btn-text" :class="{ 'active-text': betList.length > 0 }">购彩篮</text>
						<view v-if="betList.length > 0" class="bet-count-badge">
							<text class="badge-text">{{ betList.length }}</text>
						</view>
					</view>
					<view class="bottom-btn add-btn" :class="{ 'can-add': canAddNumber, 'disabled': !canAddNumber }" @click="addNumber">
						<uv-icon name="plus-circle" size="32rpx" :color="canAddNumber ? '#ff6b35' : '#4e4e4e'"></uv-icon>
						<text class="btn-text" :class="{ 'active-text': canAddNumber, 'disabled-text': !canAddNumber }">添加选号</text>
					</view>
					<view class="bottom-btn submit-btn" :class="{ 'disabled': totalBetCount === 0 }" @click="submitBet">
						<view class="balance-info">
						<text class="balance-label">立即投注</text>
						<text class="balance-amount">余额:{{ userInfo.balance }}</text>
					</view>
					</view>
				</view>
			</view>
			
		</view>
		
		<!-- 购彩篮弹窗 -->
		<uv-popup ref="betBasketPopup" mode="bottom" :show="showBetBasketPopup" @close="closeBetBasketPopup" round="20">
			<view class="bet-basket-popup">
				<view class="popup-header">
					<text class="popup-title">购彩篮</text>
					<uv-icon name="close" size="40rpx" color="#999" @click="closeBetBasketPopup"></uv-icon>
				</view>
				<view class="basket-content">
					<view v-if="betList.length === 0" class="empty-basket">
						<text class="empty-text">购彩篮为空</text>
					</view>
					<view v-else class="bet-list">
						<view v-for="(bet, index) in betList" :key="bet.id" class="bet-item">
							<view class="bet-info">
								<text class="bet-type">{{ bet.type }}</text>
								<text class="bet-numbers">{{ formatBetNumbers(bet.numbers) }}</text>
								<text class="bet-count">{{ bet.count }}注</text>
							</view>
							<view class="bet-controls">
								<view class="multiplier-control">
									<text class="control-label">倍数:</text>
									<uv-number-box 
										v-model="bet.multiplier" 
										:min="1" 
										:max="999" 
										@change="(e) => onBetMultiplierChange(index, e.value)"
										size="small"
									/>
								</view>
								<view class="bet-amount">
									<text class="amount-text">{{ bet.amount }}元</text>
								</view>
								<uv-icon name="trash" size="32rpx" color="#ff4757" @click="removeBetItem(index)"></uv-icon>
							</view>
						</view>
					</view>
				</view>
				<view v-if="betList.length > 0" class="basket-footer">
					<view class="total-info">
					<text class="total-text">共{{ betList.length }}项，{{ basketBetCount }}注，{{ basketTotalAmount }}元</text>
				</view>
					<view class="footer-buttons">
						<uv-button type="info" size="small" @click="clearBetBasket">清空</uv-button>
						<uv-button type="primary" size="small" @click="submitBasketBet">立即投注</uv-button>
					</view>
				</view>
			</view>
		</uv-popup>
		
		<!-- 近期投注弹窗 -->
		<uv-popup ref="recentBetsPopup" mode="bottom" :show="showRecentBetsPopup" @close="closeRecentBetsPopup" round="20">
			<view class="recent-bets-popup">
				<view class="popup-header">
					<text class="popup-title">近期投注</text>
					<uv-icon name="close" size="40rpx" color="#999" @click="closeRecentBetsPopup"></uv-icon>
				</view>
				<view class="recent-content">
					<view v-if="loading.orders" class="loading-container">
						<uv-loading-icon mode="spinner"></uv-loading-icon>
						<text class="loading-text">加载中...</text>
					</view>
					<view v-else-if="recentOrders.length === 0" class="empty-orders">
						<text class="empty-text">暂无投注记录</text>
					</view>
					<view v-else class="orders-list">
						<view v-for="order in recentOrders" :key="order.id" class="order-item">
							<view class="order-header">
								<text class="order-number">{{ order.order_no }}</text>
								<text class="order-status" :class="getOrderStatusClass(order.status)">{{ order.status_text || getOrderStatusText(order.status) }}</text>
							</view>
							<view class="order-info">
								<text class="order-game">{{ order.typename || '--' }}</text>
								<text class="order-period">{{ order.period_no }}期</text>
							</view>
							<view class="order-details">
								<text class="order-numbers">{{ formatOrderBetContent(order.bet_content) }}</text>
								<text class="order-amount">{{ order.total_amount }}元</text>
							</view>
							<view class="order-time">
								<text class="time-text">{{ order.create_time_formatted }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
import { getGameInfo, getCurrentPeriod, getGameOdds } from '@/api/lottery/lottery.js'
import { getBetOrders, submitBet } from '@/api/bet/bet.js'
import { getUserInfo } from '@/api/user.js'
import { formatOrderBetContent } from '@/api/order.js'
import { formatTime } from '@/utils/common.js'
import DirectSelect from '@/components/fc3d/DirectSelect.vue'

import DirectSpan from '@/components/fc3d/DirectSpan.vue'
import GroupThree from '@/components/fc3d/GroupThree.vue'
import GroupThreeSingle from '@/components/fc3d/GroupThreeSingle.vue'
import GroupSix from '@/components/fc3d/GroupSix.vue'
import Position from '@/components/fc3d/Position.vue'
import Form from '@/components/fc3d/Form.vue'
import SumValue from '@/components/fc3d/SumValue.vue'
import Span from '@/components/fc3d/Span.vue'
export default { 
	components: {
		DirectSelect,
		DirectSpan,
		GroupThree,
		GroupThreeSingle,
		GroupSix,
		Position,
		Form,
		SumValue,
		Span
	},
	data() {
			return {
				// 页面类型参数
				gameType: '3d',
				
				// 头部显示控制
				showHeader: true, // 控制头部是否显示
				
				// 倒计时相关
				countdown: '00:00:00',
				countdownTimer: null,
				
				// 游戏信息
				gameInfo: {
					is_enabled: 1,
					type_name: '--',
					min_bet_amount: '2.00',
					max_bet_amount: '10000.00',
					bonus_list: []
				},
				
				// 期号信息
		currentPeriodInfo: {
			period_number: '',
			closing_time: '',
			draw_time_end: '',
			next_issue_start_time: '',
			last_open_period_no: '',
			last_open_code: '',
			status: 'normal',
			remaining_minutes: 0
		},
				

				
				// 投注记录
				pendingOrders: [],
				
				// 用户信息
				userInfo: {
					balance: 0,
					username: '',
					mobile: ''
				},
				
				// 加载状态
				loading: {
					period: false,
					history: false,
					orders: false,
					userInfo: false
				},
			
			// 左侧垂直选项卡
			activeTab: 0,
			tabList: [
				{ name: '直选' },
				{ name: '组三' },
				{ name: '组六' },
				{ name: '定位' },
				{ name: '形态' }
			],
			
			// 右侧二级选项卡
			activeSubTab: 0,
			subTabList: [],
			
			// 底部操作栏相关
			multiplier: 1, // 用于 uv-number-box
			minBonus: 1040, // 示例奖金
			maxBonus: 1040, // 示例奖金
			selectedCount: 0,
			totalAmount: 0,
			betAmount: 2, // 投注金额，将从当前玩法的bonus_data中的min_price获取
			
			// 选号列表
			betList: [],
			
			// 弹窗控制
			showBetBasketPopup: false,
			showRecentBetsPopup: false,
			
			// 近期投注订单
			recentOrders: [],
			
			// 选中的数字（从组件传递过来）
			selectedNumbers: {
				bai: [], // 百位选中的数字
				shi: [], // 十位选中的数字
				ge: []   // 个位选中的数字
			},
			
			// 当前投注类型
			currentBetType: 'zhixuan_fushi',
			
			// 游戏赔率数据
			gameOdds: [],
			
			// 选项卡内容配置
			tabConfig: {
				0: { // 直选
					subTabs: [
						{ name: '复式' },
						{ name: '和值' },
						{ name: '跨度' }
					],
					content: {
						0: { title: '', desc: '' },
						1: { title: '', desc: '' },
						2: { title: '', desc: '' }
					}
				},
				1: { // 组三
					subTabs: [
						{ name: '单式' },
						{ name: '复式' }
					],
					content: {
						0: { title: '', desc: '' },
						1: { title: '组三复式', desc: '' }
					}
				},
				2: { // 组六
					subTabs: [
						{ name: '复式' }
					],
					content: {
						0: { title: '组六复式', desc: '' }
					}
				},
				3: { // 定位
					subTabs: [
						{ name: '一码定位' },
						{ name: '两码定位' },
						{ name: '一码不定位' }
					],
					content: {
						0: { title: '一码定位', desc: '' },
						1: { title: '两码定位', desc: '' },
						2: { title: '一码不定位', desc: '' }
					}
				},
				4: { // 形态
					subTabs: [
						{ name: '大小' },
						{ name: '单双' }
					],
					content: {
						0: { title: '大小', desc: '' },
						1: { title: '单双', desc: '' }
					}
				},

			}
		}
	},

	computed: {
		currentContent() {
			const config = this.tabConfig[this.activeTab];
			if (config && config.content && config.content[this.activeSubTab]) {
				return config.content[this.activeSubTab];
			}
			return { title: '暂无内容', desc: '请选择其他选项' };
		},
		
		// 动态计算头部高度
		dynamicHeaderHeight() {
			return this.showHeader ? '200rpx' : '0rpx';
		},
		
		// 判断是否可以添加选号
		canAddNumber() {
			// 统一使用selectedCount检查
			return this.selectedCount > 0;
		},
		
		// 计算总注数
	totalBetCount() {
		// 当前选择的注数 + 已添加选号列表的注数
		const betListCount = this.betList.reduce((total, bet) => total + bet.count, 0);
		return this.selectedCount + betListCount;
	},
	
	// 计算购彩篮注数
	basketBetCount() {
		// 只计算购彩篮列表中的注数
		return this.betList.reduce((total, bet) => total + bet.count, 0);
	},
	
	// 计算购彩篮总金额
	basketTotalAmount() {
		// 只计算购彩篮列表中的金额总和
		return this.betList.reduce((total, bet) => total + bet.amount, 0);
	}
	},

	async mounted() {
			// 基本初始化
			this.gameInfo.type_name = '--';
			this.updateSubTabs();
			
			// 先加载游戏赔率，然后设置投注类型和金额
			await this.loadGameOdds(); // 加载游戏赔率
			this.updateBetType(); // 初始化投注类型（会调用updateBetAmount设置默认金额）
			this.updateBonus(); // 初始化奖金
			this.calculateTotalAmount(); // 初始化总金额计算
			
			// 读取缓存的投注数据
			this.loadCachedBetData();
			
			// 确保默认加载第一个玩法组件
			this.$nextTick(() => {
				this.$forceUpdate();
			});
		},

	methods: {
		// 格式化投注内容显示
		formatOrderBetContent,
		
		// 主选项卡切换
		onTabChange(index) {
			console.log('切换主选项卡:', index);
			if (typeof index === 'object' && index.index !== undefined) {
				this.activeTab = index.index;
			} else {
				this.activeTab = index;
			}
			this.activeSubTab = 0;
			this.clearAllSelectionsForTabChange(); // 清空所有选择
			this.updateSubTabs();
			this.updateBonus(); // 玩法切换时更新奖金
			this.updateBetType(); // 更新投注类型
			
			// 读取当前玩法的缓存数据
			this.loadCachedBetData();
			
			// 重置组件状态
			if (this.$refs.directSelect) {
				this.$refs.directSelect.clearAllSelections();
			}
			this.$nextTick(() => {
				this.$forceUpdate();
			});
		},

		// 处理金额输入
		onAmountInput(e) {
			const value = parseFloat(e.detail.value) || 0;
			if (value < 0) {
				this.betAmount = 0;
			} else if (value > 10000) {
				this.betAmount = 10000;
			} else {
				this.betAmount = value;
			}
			// 重新计算总金额
			this.calculateTotalAmount();
		},
		
		// 处理选中数量变化
		onSelectedCountChange(count) {
			this.selectedCount = count;
			this.calculateTotalAmount();
		},

		// 处理选中号码变化
		onSelectedNumbersChange(numbers) {
			this.selectedNumbers = numbers;
		},
		
		// 处理投注类型变化
		onBetTypeChange(betType) {
			// 只有当子组件传递的betType与父组件设置的不一致时才更新
			// 这样可以避免子组件覆盖父组件的正确设置
			console.log('子组件传递的betType:', betType, '当前父组件的currentBetType:', this.currentBetType);
			// 注释掉直接覆盖的逻辑，让父组件的updateBetType方法控制投注类型
			// this.currentBetType = betType;
		},
		
		// 子选项卡切换
		onSubTabChange(index) {
			console.log('切换子选项卡:', index);
			if (typeof index === 'object' && index.index !== undefined) {
				this.activeSubTab = index.index;
			} else {
				this.activeSubTab = index;
			}
			this.clearAllSelectionsForTabChange(); // 清空所有选择
			this.updateBonus(); // 玩法切换时更新奖金
			this.updateBetType(); // 更新投注类型
			
			// 读取当前玩法的缓存数据
			this.loadCachedBetData();
			
			// 重置组件状态
			if (this.$refs.directSelect) {
				this.$refs.directSelect.clearAllSelections();
			}
			this.$nextTick(() => {
				this.$forceUpdate();
			});
		},
		
		// 更新子选项卡列表
		updateSubTabs() {
			const config = this.tabConfig[this.activeTab];
			if (config && config.subTabs) {
				this.subTabList = config.subTabs;
			} else {
				this.subTabList = [];
			}
		},
		
		// 倍数变化处理
		onMultiplierChange(e) {
			this.multiplier = e.value;
			this.calculateTotalAmount();
		},
		
		// 显示近期投注
		showRecentBets() {
			this.$refs.recentBetsPopup.open();
			this.loadRecentOrders();
		},
		
		// 关闭近期投注弹窗
		closeRecentBetsPopup() {
			this.$refs.recentBetsPopup.close();
		},		
		
		// 显示购彩篮
		showBetBasket() {
			this.$refs.betBasketPopup.open();
		},
		
		// 关闭购彩篮弹窗
		closeBetBasketPopup() {
			this.$refs.betBasketPopup.close();
		},
		// 购彩篮中调整倍数
		onBetMultiplierChange(index, value) {
			this.betList[index].multiplier = value;
			this.betList[index].amount = this.betList[index].count * value * this.betList[index].betAmount;
			this.calculateTotalAmount();
			// 更新缓存
			this.saveBetDataToCache();
		},
		
		// 删除购彩篮中的项目
		removeBetItem(index) {
			this.betList.splice(index, 1);
			this.calculateTotalAmount();
			// 更新缓存
			this.saveBetDataToCache();
			uni.showToast({
				title: '已删除',
				icon: 'success'
			});
		},
		
		// 清空购彩篮
		clearBetBasket() {
			uni.showModal({
				title: '确认清空',
				content: '确定要清空购彩篮吗？',
				success: (res) => {
					if (res.confirm) {
						this.betList = [];
						this.calculateTotalAmount();
						// 更新缓存
						this.saveBetDataToCache();
						uni.showToast({
							title: '已清空',
							icon: 'success'
						});
					}
				}
			});
		},
		
		// 统一投注提交方法
		async doSubmitBet(betData, totalAmount, successCallback) {
			try {
				uni.showLoading({ title: '投注中...' });
				
				// 调用投注API
				const response = await submitBet({
					lottery_code: this.gameType,
					period_no: this.currentPeriodInfo.period_number,
					bet_data: betData,
					total_amount: totalAmount
				});
				
				uni.hideLoading();
				
				if (response.code === 1) {
					this.multiplier = 1;
					
					// 投注成功
					uni.showToast({
						title: '投注成功',
						icon: 'success',
						duration: 2000
					});
					
					// 更新用户余额
					this.userInfo.balance -= totalAmount;
					
					// 执行成功回调
					if (successCallback) {
						successCallback();
					}
					
				} else {
					// 投注失败
					uni.showToast({
						title: response.msg || '投注失败，请重试',
						icon: 'none',
						duration: 3000
					});
				}
			} catch (error) {
				uni.hideLoading();
				console.error('投注失败:', error);
				
				// 区分业务错误和网络错误
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
						title: '网络异常，请重试',
						icon: 'none',
						duration: 3000
					});
				}
			}
		},
		
		// 从购彩篮提交投注
		async submitBasketBet() {
			if (this.betList.length === 0) {
				uni.showToast({
					title: '购彩篮为空',
					icon: 'none'
				});
				return;
			}
			
			// 计算购彩篮总金额
			const basketTotalAmount = this.betList.reduce((total, bet) => total + bet.amount, 0);
			
			// 检查余额
			if (this.userInfo.balance < basketTotalAmount) {
				uni.showToast({
					title: '余额不足',
					icon: 'none'
				});
				return;
			}
			
			// 构造投注数据
			const betData = this.betList.map(bet => ({
				type_key: bet.betType || this.currentBetType,
				type_name: bet.typeName || this.getBackendTypeName(bet.betType || this.currentBetType),
				numbers: bet.numbers,
				note: bet.count,
				money: bet.betAmount,
				multiplier: bet.multiplier || this.multiplier
				// bonus参数在开奖时处理，不在投注时传递
			}));
			
			// 计算总金额
			const totalAmount = betData.reduce((total, bet) => {
				return total + (bet.money * bet.multiplier * bet.note);
			}, 0);
			
			// 调用统一投注方法
			await this.doSubmitBet(betData, totalAmount, () => {
				// 清空购彩篮
				this.betList = [];
				// 重置倍数为默认值
				this.multiplier = 1;
				this.calculateTotalAmount();
				// 清空当前玩法的缓存
				this.clearCachedBetData();
				// 关闭购彩篮弹窗
				this.closeBetBasketPopup();
			});
		},
		
		// 格式化投注号码显示
		formatBetNumbers(numbers) {
			if (!numbers) return '';
			
			// 检查是否为Form组件的和值选项数据结构
			if (numbers.type && numbers.sumOptions && Array.isArray(numbers.sumOptions)) {
				const typeMap = {
					'daxiao': '和值大小',
					'danshuang': '和值单双'
				};
				const typeName = typeMap[numbers.type] || numbers.type;
				const optionMap = {
					'big': '大',
					'small': '小',
					'odd': '单',
					'even': '双'
				};
				const options = numbers.sumOptions.map(opt => optionMap[opt] || opt).join(',');
				return `${typeName}: ${options}`;
			}
			

			
			// 检查是否为GroupThreeSingle组件的数组数据结构
			if (Array.isArray(numbers)) {
				return numbers.join(',');
			}
			
			const { bai, shi, ge } = numbers;
			
			// 检查是否为直选类型的数据结构
			if (bai && shi && ge && Array.isArray(bai) && Array.isArray(shi) && Array.isArray(ge)) {
				return `${bai.join(',')} | ${shi.join(',')} | ${ge.join(',')}`;
			}
			
			// 检查是否为其他类型的数据结构（如和值、跨度等）
			if (numbers.selected && Array.isArray(numbers.selected)) {
				return numbers.selected.join(',');
			}
			
			// 如果是其他格式，尝试转换为字符串
			if (typeof numbers === 'object') {
				return JSON.stringify(numbers);
			}
			
			return numbers.toString();
		},
		
		// 加载近期投注订单
		async loadRecentOrders() {
			try {
				this.loading.orders = true;
				const response = await getBetOrders({
					page: 1,
					limit: 10,
					game_type: this.gameType
				});
				
				if (response.code === 1) {
					this.recentOrders = response.data.data || [];
				} else {
					uni.showToast({
						title: response.msg || '加载失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('加载近期投注失败:', error);
				uni.showToast({
					title: '加载失败',
					icon: 'none'
				});
			} finally {
				this.loading.orders = false;
			}
		},
		
		// 获取订单状态文本
		getOrderStatusText(status) {
			const statusMap = {
				'CONFIRMED': '待开奖',
				'WINNING': '已中奖',
				'PAID': '已派奖',
				'LOSING': '未中奖',
				'CANCELLED': '已取消',
			};
			return statusMap[status] || '未知';
		},
		
		// 获取订单状态样式类
		getOrderStatusClass(status) {
			return {
				'status-pending': status === 'pending' || status === 'CONFIRMED',
				'status-winning': status === 'winning' || status === 'WINNING',
				'status-losing': status === 'losing' || status === 'LOSING',
				'status-cancelled': status === 'cancelled' || status === 'CANCELLED'
			};
		},
		
		// 格式化时间
		formatTime(timeStr) {
			return formatTime(timeStr);
		},
		
		// 清空当前选择
		clearAllSelections() {
			if (this.selectedCount === 0) {
				uni.showToast({
					title: '当前没有选号',
					icon: 'none'
				});
				return;
			}
			
			this.clearCurrentGameComponent();
			this.calculateTotalAmount();
		},
		

		
		// 添加选号 - 通用方法，支持多种玩法组件
		addNumber() {
			if (!this.canAddNumber) {
				uni.showToast({
					title: '请先选择号码',
					icon: 'none'
				});
				return;
			}
			
			const componentRef = this.getCurrentGameComponentRef();
			if (!componentRef) {
				uni.showToast({
					title: '获取组件引用失败',
					icon: 'none'
				});
				return;
			}
			
			let betInfo;
			
			// 优先使用组件的格式化方法
			if (typeof componentRef.getFormattedBetData === 'function') {
				betInfo = componentRef.getFormattedBetData(this.multiplier, this.betAmount);
				if (!betInfo) {
					uni.showToast({
						title: '请选择号码',
						icon: 'none'
					});
					return;
				}
				// 添加额外的字段用于界面显示
				betInfo.id = Date.now();
				betInfo.type = this.getBetTypeName();
				betInfo.count = betInfo.note;
				betInfo.betAmount = this.betAmount;
				betInfo.amount = betInfo.calculated_amount;
				betInfo.betType = this.currentBetType;
				betInfo.typeName = this.getBackendTypeName(this.currentBetType);
			} else {
				// 兼容旧组件的通用处理
				const gameComponentData = this.getCurrentGameComponentData();
				if (!gameComponentData) {
					uni.showToast({
						title: '获取选号数据失败',
						icon: 'none'
					});
					return;
				}
				
				let numbersData;
				try {
					numbersData = JSON.parse(JSON.stringify(gameComponentData.numbers));
				} catch (error) {
					console.error('深拷贝选号数据失败:', error);
					numbersData = gameComponentData.numbers;
				}
				
				betInfo = {
					id: Date.now(),
					type: this.getBetTypeName(),
					numbers: numbersData,
					count: gameComponentData.count,
					multiplier: this.multiplier,
					betAmount: this.betAmount,
					amount: gameComponentData.count * this.multiplier * this.betAmount,
					betType: this.currentBetType,
					typeName: this.getBackendTypeName(this.currentBetType)
				};
			}
			
			// 添加到选号列表
			this.betList.push(betInfo);
			
			// 保存投注数据到缓存
			this.saveBetDataToCache();
			
			// 清空当前玩法组件的选择
			this.clearCurrentGameComponent();
			
			// 重置当前选择状态
			this.resetCurrentSelection();
			
			uni.showToast({
				title: `已添加 ${betInfo.count} 注选号`,
				icon: 'success'
			});
		},
		
		// 提交投注 - 通用方法，支持多种玩法组件
		async submitBet() {
			if (this.totalBetCount === 0) {
				uni.showToast({
					title: '请先选择号码',
					icon: 'none'
				});
				return;
			}
			
			// 如果有当前选择但未添加到列表，提示用户
			if (this.selectedCount > 0) {
				uni.showModal({
					title: '提示',
					content: '您还有未添加的选号，是否先添加到选号列表？',
					success: (res) => {
						if (res.confirm) {
							this.addNumber();
						}
					}
				});
				return;
			}
			
			// 检查余额
			if (this.userInfo.balance < this.totalAmount) {
				uni.showToast({
					title: '余额不足',
					icon: 'none'
				});
				return;
			}
			
			// 构造投注数据
			const betData = this.betList.map(bet => ({
				type_key: bet.betType || this.currentBetType,
				type_name: bet.typeName || this.getBackendTypeName(bet.betType || this.currentBetType),
				numbers: bet.numbers,
				note: bet.count,
				money: bet.betAmount,
				multiplier: bet.multiplier || this.multiplier
				// bonus参数在开奖时处理，不在投注时传递
			}));
			
			// 计算总金额
			const totalAmount = betData.reduce((total, bet) => {
				return total + (bet.money * bet.multiplier * bet.note);
			}, 0);
			
			// 调用统一投注方法
			await this.doSubmitBet(betData, totalAmount, () => {
				// 清空选号列表
				this.betList = [];
				// 重置倍数为默认值
				this.multiplier = 1;
				this.calculateTotalAmount();
				// 清空当前玩法的缓存
				this.clearCachedBetData();
				// 关闭购彩篮弹窗
				this.showBetBasketPopup = false;
			});
		},
		
		// 计算总金额
		calculateTotalAmount() {
			// 当前选择的金额
			const currentAmount = this.selectedCount * this.multiplier * this.betAmount;
			
			// 已添加选号列表的总金额
			const betListAmount = this.betList.reduce((total, bet) => total + bet.amount, 0);
			
			// 总金额 = 当前选择金额 + 已添加选号金额
			this.totalAmount = currentAmount + betListAmount;
			
			console.log(`计算总金额: 当前选择${this.selectedCount}注 * ${this.multiplier}倍 * ${this.betAmount}元 = ${currentAmount}元, 购彩篮${betListAmount}元, 总计${this.totalAmount}元`);
		},
		
		// 更新奖金 - 根据当前投注类型动态计算
		updateBonus() {
			// 优先从服务器获取的赔率数据中查找
			const oddsItem = this.gameOdds.find(item => item.type_key === this.currentBetType);
			if (oddsItem && oddsItem.bonus_json) {
				// 检查bonus_json是否为对象格式（如组三复式、组六复式）
				if (typeof oddsItem.bonus_json === 'object' && !Array.isArray(oddsItem.bonus_json)) {
					// 对象格式，提取所有奖金值
					const bonusValues = Object.values(oddsItem.bonus_json).map(bonus => parseFloat(bonus));
					if (bonusValues.length === 1) {
						this.minBonus = bonusValues[0];
						this.maxBonus = bonusValues[0];
					} else {
						this.minBonus = Math.min(...bonusValues);
						this.maxBonus = Math.max(...bonusValues);
					}
				}
				// 数组格式
				else if (Array.isArray(oddsItem.bonus_json) && oddsItem.bonus_json.length > 0) {
					if (oddsItem.bonus_json.length === 1) {
						// 只有一个奖金值
						const bonus = parseFloat(oddsItem.bonus_json[0]);
						this.minBonus = bonus;
						this.maxBonus = bonus;
					} else {
						// 多个奖金值，显示区间
						const bonuses = oddsItem.bonus_json.map(bonus => parseFloat(bonus));
						this.minBonus = Math.min(...bonuses);
						this.maxBonus = Math.max(...bonuses);
					}
				}
				else {
					// 如果bonus_json格式不正确，使用默认值
					const bonus = this.getBonusForBetType(this.currentBetType);
					this.minBonus = bonus;
					this.maxBonus = bonus;
				}
			} else {
				// 如果服务器数据中没有找到，使用默认值
				const bonus = this.getBonusForBetType(this.currentBetType);
				this.minBonus = bonus;
				this.maxBonus = bonus;
			}
		},
		
		// 更新投注类型
		updateBetType() {
			// 根据当前选择的选项卡和子选项卡设置投注类型
			if (this.activeTab === 0) { // 直选
				if (this.activeSubTab === 0) {
					this.currentBetType = 'zhixuan_fushi'; // 直选复式
				} else if (this.activeSubTab === 1) {
					this.currentBetType = 'zhixuan_hezhi'; // 直选和值
				} else if (this.activeSubTab === 2) {
					this.currentBetType = 'zhixuan_kuadu'; // 直选跨度
				}
			} else if (this.activeTab === 1) { // 组三
				if (this.activeSubTab === 0) {
					this.currentBetType = 'zusan_danshi'; // 组三单式
				} else if (this.activeSubTab === 1) {
					this.currentBetType = 'zusan_fushi'; // 组三复式
				}
			} else if (this.activeTab === 2) { // 组六
				if (this.activeSubTab === 0) {
					this.currentBetType = 'zuliu_fushi'; // 组六复式
				}
			} else if (this.activeTab === 3) { // 定位
				if (this.activeSubTab === 0) {
					this.currentBetType = 'zuxuan_yima_dingwei'; // 一码定位
				} else if (this.activeSubTab === 1) {
					this.currentBetType = 'zuxuan_liangma_dingwei'; // 两码定位
				} else if (this.activeSubTab === 2) {
					this.currentBetType = 'zuxuan_yima_budingwei'; // 一码不定位
				}
			} else if (this.activeTab === 4) { // 形态
				if (this.activeSubTab === 0) {
					this.currentBetType = 'hezhi_daxiao'; // 和值大小
				} else if (this.activeSubTab === 1) {
					this.currentBetType = 'hezhi_danshuang'; // 和值单双
				}
			}
			
			// 投注类型变化后，更新单注金额和奖金信息
			this.updateBetAmount();
			this.updateBonus();
		},
		
		// 更新单注金额
		updateBetAmount() {
			console.log('updateBetAmount被调用，当前投注类型:', this.currentBetType, '游戏赔率数据:', this.gameOdds);
			// 从游戏赔率数据中查找当前投注类型对应的最小金额
			const oddsItem = this.gameOdds.find(item => item.type_key === this.currentBetType);
			if (oddsItem && oddsItem.min_price) {
				this.betAmount = parseFloat(oddsItem.min_price);
				console.log(`投注类型 ${this.currentBetType} 的单注金额从bonus_data的min_price设置为: ${this.betAmount}元`);
			} else {
				// 如果找不到对应的投注类型，使用默认值
				this.betAmount = 2;
				console.log(`投注类型 ${this.currentBetType} 未找到对应配置，使用默认单注金额: ${this.betAmount}元`);
			}
			
			// 强制更新界面显示
			this.$forceUpdate();
			
			// 重新计算总金额
			this.calculateTotalAmount();
		},
		
		// 获取投注类型显示名称
		getBetTypeName() {
			const typeMap = {
				'zhixuan_fushi': '直选复式',
				'zhixuan_hezhi': '直选和值',
				'zhixuan_kuadu': '直选跨度',
				'zusan_danshi': '组三单式',
				'zusan_fushi': '组三复式',
				'zuliu_fushi': '组六复式',
				'zuxuan_yima_dingwei': '一码定位',
				'zuxuan_liangma_dingwei': '两码定位',
				'zuxuan_yima_budingwei': '一码不定位',
				'hezhi_daxiao': '和值大小',
				'hezhi_danshuang': '和值单双',

			};
			return typeMap[this.currentBetType] || '未知类型';
		},
		
		// 获取后端期望的类型名称
		getBackendTypeName(betType) {
			const backendTypeMap = {
				'zhixuan_fushi': '直选复式',
				'zhixuan_hezhi': '直选和值',
				'zhixuan_kuadu': '直选跨度',
				'zusan_danshi': '组三单式',
				'zusan_fushi': '组三复式',
				'zuliu_fushi': '组六复式',
				'zuxuan_yima_dingwei': '一码定位',
				'zuxuan_liangma_dingwei': '两码定位',
				'zuxuan_yima_budingwei': '一码不定位',
				'hezhi_daxiao': '和值大小',
				'hezhi_danshuang': '和值单双',

				'daxiaodanshuang': '大小单双'
			};
			return backendTypeMap[betType] || betType;
		},
		
		// 获取当前活动的玩法组件数据
		getCurrentGameComponentData() {
			const componentRef = this.getCurrentGameComponentRef();
			if (!componentRef) {
				console.warn('未找到当前玩法组件引用');
				return null;
			}
			
			// 统一使用getSelectedData方法获取数据
			if (typeof componentRef.getSelectedData === 'function') {
				const data = componentRef.getSelectedData();
				if (!data || data.count === 0) {
					return null;
				}
				return data;
			}
			
			console.warn('组件没有可用的数据获取方法');
			return null;
		},
		
		// 获取当前活动的玩法组件引用
		getCurrentGameComponentRef() {
			// 根据当前的 activeTab 和 activeSubTab 返回对应的组件引用
			if (this.activeTab === 0) { // 直选
				if (this.activeSubTab === 0) {
					return this.$refs.directSelect;
				} else if (this.activeSubTab === 1) {
					return this.$refs.sumValue;
				} else if (this.activeSubTab === 2) {
					return this.$refs.span;
				}
			} else if (this.activeTab === 1) { // 组三
				if (this.activeSubTab === 0) {
					return this.$refs.groupThreeSingle;
				} else if (this.activeSubTab === 1) {
					return this.$refs.groupThree;
				} else if (this.activeSubTab === 2) {
					return this.$refs.groupThreeDrag;
				}
			} else if (this.activeTab === 2) { // 组六
				if (this.activeSubTab === 0) {
					return this.$refs.groupSix;
				} else if (this.activeSubTab === 1) {
					return this.$refs.groupSixDrag;
				}
			} else if (this.activeTab === 3) { // 定位
				if (this.activeSubTab === 0) {
					return this.$refs.positionOne;
				} else if (this.activeSubTab === 1) {
					return this.$refs.positionTwo;
				} else if (this.activeSubTab === 2) {
					return this.$refs.positionUnfixed;
				}
			} else if (this.activeTab === 4) { // 形态
				if (this.activeSubTab === 0) {
					return this.$refs.formBig;
				} else if (this.activeSubTab === 1) {
					return this.$refs.formOdd;
				} else if (this.activeSubTab === 2) {
					return this.$refs.formPair;
				}
			}
			
			return null;
		},
		
		// 清空当前玩法组件的选择
		clearCurrentGameComponent() {
			const componentRef = this.getCurrentGameComponentRef();
			if (componentRef) {
				// 如果组件有清空方法，调用它
				if (typeof componentRef.clearAllSelections === 'function') {
					componentRef.clearAllSelections();
				} else if (typeof componentRef.clearAll === 'function') {
					componentRef.clearAll();
				} else if (typeof componentRef.clear === 'function') {
					componentRef.clear();
				}
			}
		},
		
		// 重置当前选择状态
		resetCurrentSelection() {
			this.selectedCount = 0;
			this.totalAmount = 0;
			// 重置选号数据为默认结构（适用于直选定位）
			this.selectedNumbers = {
				bai: [],
				shi: [],
				ge: []
			};
		},
		
		// 单次投注
		submitSingleBet() {
			if (!this.canAddNumber) {
				uni.showToast({
					title: '请先选择号码',
					icon: 'none'
				});
				return;
			}
			
			const componentRef = this.getCurrentGameComponentRef();
			if (!componentRef) {
				uni.showToast({
					title: '获取组件引用失败',
					icon: 'none'
				});
				return;
			}
			
			let betData;
			if (typeof componentRef.getFormattedBetData === 'function') {
				betData = componentRef.getFormattedBetData(this.multiplier, this.betAmount);
			} else {
				const gameComponentData = this.getCurrentGameComponentData();
				if (!gameComponentData) {
					uni.showToast({
						title: '获取选号数据失败',
						icon: 'none'
					});
					return;
				}
				betData = {
					type_key: this.currentBetType,
					type_name: this.getBackendTypeName(this.currentBetType),
					numbers: gameComponentData.numbers,
					note: gameComponentData.count,
					money: this.betAmount,
					multiplier: this.multiplier,
					calculated_amount: gameComponentData.count * this.multiplier * this.betAmount
				};
			}
			
			if (!betData) {
				uni.showToast({
					title: '请选择号码',
					icon: 'none'
				});
				return;
			}
			
			// 检查余额
			if (this.userInfo.balance < betData.calculated_amount) {
				uni.showToast({
					title: '余额不足',
					icon: 'none'
				});
				return;
			}
			
			console.log('提交单次投注数据:', betData);
			
			uni.showToast({
				title: '投注成功',
				icon: 'success'
			});
			
			this.clearCurrentGameComponent();
			this.resetCurrentSelection();
			this.multiplier = 1;
			this.clearCachedBetData();
		},
		
		// 加载游戏赔率
		async loadGameOdds() {
			try {
				const response = await getGameOdds(this.gameType, this.currentBetType);
				if (response.code === 1 && response.data) {
					this.gameOdds = response.data;
					console.log('游戏赔率加载成功:', this.gameOdds);
					
					// 根据当前投注类型设置单注金额
					this.updateBetAmount();
				} else {
					console.error('获取游戏赔率失败:', response.msg);
					// 使用默认赔率
					this.gameOdds = [];
				}
			} catch (error) {
				console.error('加载游戏赔率异常:', error);
				// 使用默认赔率
				this.gameOdds = [];
			}
		},
		
		// 根据投注类型获取奖金
		getBonusForBetType(betType) {
			// 优先从服务器获取的赔率数据中查找
			const oddsItem = this.gameOdds.find(item => item.type_key === betType);
			if (oddsItem && oddsItem.bonus_json) {
				// 检查bonus_json是否为对象格式（如组三复式、组六复式）
				if (typeof oddsItem.bonus_json === 'object' && !Array.isArray(oddsItem.bonus_json)) {
					// 对象格式，提取所有奖金值
					const bonusValues = Object.values(oddsItem.bonus_json).map(bonus => parseFloat(bonus));
					if (bonusValues.length === 1) {
						return bonusValues[0];
					}
					// 如果有多个奖金值，返回最小值
					return Math.min(...bonusValues);
				}
				// 数组格式
				else if (Array.isArray(oddsItem.bonus_json) && oddsItem.bonus_json.length > 0) {
					// 如果只有一个奖金值，返回该值
					if (oddsItem.bonus_json.length === 1) {
						return parseFloat(oddsItem.bonus_json[0]);
					}
					// 如果有多个奖金值，返回最小值（也可以根据需要返回最大值或平均值）
					return Math.min(...oddsItem.bonus_json.map(bonus => parseFloat(bonus)));
				}
			}
			
			// 如果服务器数据中没有找到，使用默认值
			const bonusMap = {
				'zhixuan_dingwei': 1800,
				'zhixuan_hezhi': 1040,
				'zhixuan_kuadu': 1040,
				'zusan_danshi': 346,
				'zusan_fushi': 346,
				'zusan_tuodan': 346,
				'zuliu_fushi': 173,

				'zuxuan_yima_dingwei': 10,
				'zuxuan_liangma_dingwei': 100,
				'zuxuan_yima_budingwei': 3.33,
				'hezhi_daxiao': 2,
				'hezhi_danshuang': 2,

				'daxiaodanshuang': 4,
				'hezhi': 1040,
				'kuadu': 1040
			};
			return bonusMap[betType] || 1800;
		},
		

		
		// 切换选项卡时清空所有选择
		clearAllSelectionsForTabChange() {
			this.clearCurrentGameComponent();
			this.betList = [];
			this.calculateTotalAmount();
		},
		
		// 头部显示控制
		showHeaderArea() {
			this.showHeader = true;
		},
		hideHeaderArea() {
			this.showHeader = false;
		},
		toggleHeaderArea() {
			this.showHeader = !this.showHeader;
		},
		
		// 获取头部实际高度
		getHeaderHeight() {
			if (!this.showHeader) return 0;
			
			// 使用uni.createSelectorQuery获取实际高度
			return new Promise((resolve) => {
				this.$nextTick(() => {
					const query = uni.createSelectorQuery().in(this);
					query.select('.lottery-header').boundingClientRect((data) => {
						if (data) {
							resolve(data.height);
						} else {
							// 如果获取失败，使用默认值
							resolve(200);
						}
					}).exec();
				});
			});
		},
		
		// 获取彩种详情
		async loadGameInfo() {
			try {
				const response = await getGameInfo(this.gameType);
				
				if (response.code === 1 && response.data) {
					// 检查彩种是否启用
					if (response.data.is_enabled !== 1) {
						uni.showToast({
							title: '该彩种暂未开放',
							icon: 'none',
							duration: 2000
						});
						// 返回上一页
						setTimeout(() => {
							uni.navigateBack();
						}, 2000);
						return;
					}
					
					// 保存游戏信息
					this.gameInfo = response.data;
					uni.setNavigationBarTitle({
						title: response.data.type_name, 
					});
					
					// 动态更新玩法描述
					this.updateTabConfigWithBonusInfo();
					
					console.log('彩种信息加载成功:', this.gameInfo);
				} else {
					uni.showToast({
						title: response.msg || '获取彩种信息失败',
						icon: 'none',
						duration: 2000
					});
				}
			} catch (error) {
				console.error('获取彩种详情异常:', error);
				uni.showToast({
					title: '网络异常，请稍后重试',
					icon: 'none',
					duration: 2000
				});
			}
		},
		
		// 动态更新玩法描述
		updateTabConfigWithBonusInfo() {
			if (!this.gameInfo || !this.gameInfo.bonus_list) {
				return;
			}
			
			// 创建type_key到bonus_info的映射
			const bonusInfoMap = {};
			this.gameInfo.bonus_list.forEach(item => {
				bonusInfoMap[item.type_key] = item.bonus_info;
			});
			
			// 更新tabConfig中的desc字段
			// 直选类
			if (bonusInfoMap['zhixuan_fushi']) {
				this.tabConfig[0].content[0].desc = bonusInfoMap['zhixuan_fushi'];
			}
			if (bonusInfoMap['zhixuan_hezhi']) {
				this.tabConfig[0].content[1].desc = bonusInfoMap['zhixuan_hezhi'];
			}
			if (bonusInfoMap['zhixuan_kuadu']) {
				this.tabConfig[0].content[2].desc = bonusInfoMap['zhixuan_kuadu'];
			}
			
			// 组三类
			if (bonusInfoMap['zusan_danshi']) {
				this.tabConfig[1].content[0].desc = bonusInfoMap['zusan_danshi'];
			}
			if (bonusInfoMap['zusan_fushi']) {
				this.tabConfig[1].content[1].desc = bonusInfoMap['zusan_fushi'];
			}

			
			// 组六类
			if (bonusInfoMap['zuliu_fushi']) {
				this.tabConfig[2].content[0].desc = bonusInfoMap['zuliu_fushi'];
			}

			
			// 定位类
			if (bonusInfoMap['zuxuan_yima_dingwei']) {
				this.tabConfig[3].content[0].desc = bonusInfoMap['zuxuan_yima_dingwei'];
			}
			if (bonusInfoMap['zuxuan_liangma_dingwei']) {
				this.tabConfig[3].content[1].desc = bonusInfoMap['zuxuan_liangma_dingwei'];
			}
			if (bonusInfoMap['zuxuan_yima_budingwei']) {
				this.tabConfig[3].content[2].desc = bonusInfoMap['zuxuan_yima_budingwei'];
			}
			
			// 形态类
			if (bonusInfoMap['hezhi_daxiao']) {
				this.tabConfig[4].content[0].desc = bonusInfoMap['hezhi_daxiao'];
			}
			if (bonusInfoMap['hezhi_danshuang']) {
				this.tabConfig[4].content[1].desc = bonusInfoMap['hezhi_danshuang'];
			}

			
			console.log('玩法描述已动态更新:', this.tabConfig);
		},
		
		// 获取当前期号信息
		async loadCurrentPeriod() {
			try {
				this.loading.period = true;
				const response = await getCurrentPeriod(this.gameType);
				
				if (response.code === 1 && response.data) {
					const data = response.data;
					
					// 更新完整的期号信息
					this.currentPeriodInfo = {
						...this.currentPeriodInfo,
						period_number: data.period_number,
						closing_time: data.closing_time,
						draw_time_end: data.draw_time_end,
						next_issue_start_time: data.next_issue_start_time,
						last_open_period_no: data.last_open_period_no,
						last_open_code: data.last_open_code,
						status: data.status,
						remaining_minutes: data.remaining_minutes
					};
					
					// 启动倒计时
					this.startCountdown();
					
					console.log('期号信息加载成功:', this.currentPeriodInfo);
				} else {
					console.error('获取期号信息失败:', response.msg);
				}
			} catch (error) {
				console.error('获取期号信息异常:', error);
			} finally {
				this.loading.period = false;
			}
		},
		
		// 获取用户信息
		async loadUserInfo() {
			try {
				this.loading.userInfo = true;
				const response = await getUserInfo();
				if (response.code === 1 && response.data) {
					this.userInfo = {
						balance: parseFloat(response.data.money || 0),
						username: response.data.username || '',
						mobile: response.data.mobile || ''
					};
					
					console.log('用户信息加载成功:', this.userInfo);
				} else {
					console.error('获取用户信息失败:', response.msg);
				}
			} catch (error) {
				console.error('获取用户信息失败:', error);
			} finally {
				this.loading.userInfo = false;
			}
		},
		
		// 获取状态文本
		getStatusText() {
			if (this.currentPeriodInfo.status === 'normal') {
				return '投注中';
			} else if (this.currentPeriodInfo.status === 'closed') {
				return '已封盘';
			} else if (this.currentPeriodInfo.remaining_minutes <= 0) {
				return '开奖中';
			} else {
				return '投注中';
			}
		},
		
		// 获取历史开奖号码数组
		getLastOpenNumbers() {
			if (!this.currentPeriodInfo.last_open_code) return [];
			return this.currentPeriodInfo.last_open_code.split(',');
		},
		
		// 启动倒计时
		startCountdown() {
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer)
			}
			
			this.updateCountdown()
			this.countdownTimer = setInterval(() => {
				this.updateCountdown()
			}, 1000)
		},
		
		// 更新倒计时显示
		updateCountdown() {
			if (this.currentPeriodInfo.status !== 'normal') {
				this.countdown = '00:00:00'
				return
			}
			
			// 计算剩余时间
			const now = new Date()
			const today = now.toISOString().split('T')[0]
			let closingTime = new Date(`${today} ${this.currentPeriodInfo.closing_time}`)
			
			// 如果当前时间大于今天的封盘时间，则使用第二天的封盘时间
			if (now > closingTime) {
				closingTime.setDate(closingTime.getDate() + 1)
			}
			
			// 显示到封盘的时间
			const diff = closingTime - now
			this.countdown = this.formatTime(Math.max(0, Math.floor(diff / 1000)))
		},
		
		// 格式化时间显示
		formatTime(seconds) {
			const hours = Math.floor(seconds / 3600)
			const minutes = Math.floor((seconds % 3600) / 60)
			const secs = seconds % 60
			return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
		},
		
		// ==================== 缓存相关方法 ====================
		
		// 获取缓存键名 - 统一保存不区分玩法
		getCacheKey() {
			return `fc3d_bet_cache_unified`;
		},
		
		// 保存投注数据到缓存
		saveBetDataToCache() {
			try {
				const cacheKey = this.getCacheKey();
				const cacheData = {
				betList: this.betList,
				multiplier: this.multiplier,
				// 不再缓存betAmount，因为它应该根据当前玩法的bonus_data.min_price动态设置
				// betAmount: this.betAmount,
				currentBetType: this.currentBetType,
				timestamp: Date.now() // 添加时间戳，用于判断缓存是否过期
			};
				
				uni.setStorageSync(cacheKey, JSON.stringify(cacheData));
				console.log('投注数据已保存到缓存:', cacheKey, cacheData);
			} catch (error) {
				console.error('保存投注数据到缓存失败:', error);
			}
		},
		
		// 从缓存读取投注数据
		loadCachedBetData() {
			try {
				const cacheKey = this.getCacheKey();
				const cachedDataStr = uni.getStorageSync(cacheKey);
				
				// 严格检查缓存数据是否存在且有效
				if (!cachedDataStr || typeof cachedDataStr !== 'string' || cachedDataStr.trim() === '') {
					console.log('没有找到有效的缓存数据:', cacheKey);
					return;
				}
				
				let cachedData;
				try {
					cachedData = JSON.parse(cachedDataStr);
				} catch (parseError) {
					console.error('解析缓存数据失败:', parseError, '原始数据:', cachedDataStr);
					// 清除损坏的缓存
					this.clearCachedBetData();
					return;
				}
				
				// 检查缓存是否过期（24小时）
				const now = Date.now();
				const cacheAge = now - (cachedData.timestamp || 0);
				const maxAge = 24 * 60 * 60 * 1000; // 24小时
				
				if (cacheAge > maxAge) {
					console.log('缓存已过期，清除缓存:', cacheKey);
					this.clearCachedBetData();
					return;
				}
				
				// 恢复投注数据
				if (cachedData.betList && Array.isArray(cachedData.betList)) {
					this.betList = cachedData.betList;
				}
				
				if (cachedData.multiplier) {
					this.multiplier = cachedData.multiplier;
				}
				
				// 注释：不再从缓存恢复betAmount，因为betAmount应该根据当前玩法的bonus_data.min_price设置
				// if (cachedData.betAmount) {
				// 	this.betAmount = cachedData.betAmount;
				// }
				
				// 重新计算总金额
				this.calculateTotalAmount();
				
				console.log('从缓存恢复投注数据:', cacheKey, cachedData);
				
			} catch (error) {
				console.error('从缓存读取投注数据失败:', error);
				// 如果读取失败，清除可能损坏的缓存
				this.clearCachedBetData();
			}
		},
		
		// 清除当前玩法的缓存数据
		clearCachedBetData() {
			try {
				const cacheKey = this.getCacheKey();
				uni.removeStorageSync(cacheKey);
				console.log('已清除缓存数据:', cacheKey);
			} catch (error) {
				console.error('清除缓存数据失败:', error);
			}
		},
		
		// 清除所有玩法的缓存数据
		clearAllCachedBetData() {
			try {
				// 清除统一缓存键
				const cacheKey = 'fc3d_bet_cache_unified';
				uni.removeStorageSync(cacheKey);
				
				// 兼容清除旧版本的分玩法缓存
				for (let tab = 0; tab < 5; tab++) {
					for (let subTab = 0; subTab < 3; subTab++) {
						const oldCacheKey = `fc3d_bet_cache_${tab}_${subTab}`;
						uni.removeStorageSync(oldCacheKey);
					}
				}
				console.log('已清除所有缓存数据');
			} catch (error) {
				console.error('清除所有缓存数据失败:', error);
			}
		}
		

	},

	onLoad(options) {
		// 获取页面参数
		this.gameType = options.type || '3d';
		
		// 加载数据
		this.loadUserInfo();
		this.loadGameInfo();
		this.loadCurrentPeriod();
	},

	beforeDestroy() {
		if (this.countdownTimer) {
			clearInterval(this.countdownTimer);
			this.countdownTimer = null;
		}
	}
}</script>

<style scoped>

.page-container {
	height: 100vh;
	display: flex;
	flex-direction: column;
}

/* 顶部开奖信息区域样式 */
.lottery-header {
	background: linear-gradient(135deg, #ff6b35, #f7931e);
	padding: 20rpx 30rpx;
	position: relative;
	/* #ifdef APP-PLUS */
	padding-top: var(--status-bar-height);
	/* #endif */
}

.header-content {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.lottery-info {
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}

.lottery-title {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.title-text {
	font-size: 35rpx;
	color: #fff;
	font-weight: 600;
}

.period-text {
	font-size: 25rpx;
	color: rgba(255, 255, 255, 0.9);
	font-weight: 500;
}

.countdown-info {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.countdown-label {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.9);
}

.countdown-time {
	font-size: 28rpx;
	color: #fff;
	font-weight: 600;
	letter-spacing: 1rpx;
}

.winning-numbers {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 12rpx;
}

.period-number {
	font-size: 25rpx;
	color: rgba(255, 255, 255, 0.8);
}

.number-balls {
	display: flex;
	gap: 12rpx;
	align-items: center;
}

.number-ball {
	width: 60rpx;
	height: 60rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.9);
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.ball-number {
	font-size: 28rpx;
	color: #ff6b35;
	font-weight: 600;
}

.container {
	flex: 1;
	display: flex;
	flex-direction: column;
}



.content {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.lottery-tabs-container {
	flex: 1;
	display: flex;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
}

.left-vtabs {

}

.right-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	padding-bottom: 180rpx;
}

.sub-tabs {
	padding: 20rpx;
	border-bottom: 1rpx solid #e9ecef;
	background-color: #fff;
}

.tab-content {
	flex: 1;
	padding: 25rpx 0;
	background-color: #fff;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.content-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #333;
	margin-bottom: 20rpx;
}

.content-desc {
	font-size: 28rpx;
	color: #666;
	line-height: 1.6;
	max-width: 500rpx;
}

/* 底部固定操作栏样式 */
.bottom-action-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: #fff;
	border-top: 1rpx solid #eee;
	padding: 20rpx;
	z-index: 990;
	box-shadow: 0 -2rpx 10rpx rgba(0, 0, 0, 0.1);
}

.bottom-buttons {
	display: flex;
	align-items: center;
	height: 80rpx;
	gap: 20rpx;
	margin-top: 20rpx;
}

.bottom-btn {
	flex: 1;
	height: 80rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	background: #f8f8f8;
	border-radius: 12rpx;
	position: relative;
	transition: all 0.3s ease;
	gap: 8rpx;
}

.bottom-btn:active {
	transform: scale(0.95);
	background: #e8e8e8;
}

.btn-text {
	font-size: 22rpx;
	color: #666;
	font-weight: 500;
}

/* 购彩篮按钮样式 */
.basket-btn {
	position: relative;
	transition: all 0.3s ease;
}

.basket-btn.has-content {
	background: rgba(255, 107, 53, 0.1);
	border: 1rpx solid rgba(255, 107, 53, 0.3);
}

.bet-count-badge {
	position: absolute;
	top: -8rpx;
	right: -8rpx;
	background: #ff4757;
	border-radius: 50%;
	width: 32rpx;
	height: 32rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2rpx solid #fff;
}

.badge-text {
	font-size: 18rpx;
	color: #fff;
	font-weight: 600;
}

/* 添加选号按钮样式 */
.add-btn {
	transition: all 0.3s ease;
}

.add-btn.can-add {
	background: rgba(255, 107, 53, 0.1);
	border: 1rpx solid rgba(255, 107, 53, 0.3);
}

.add-btn.disabled {
	background: #f0f0f0;
	border: 1rpx solid #e0e0e0;
	opacity: 0.6;
}

/* 文字样式 */
.btn-text.active-text {
	color: #ff6b35;
	font-weight: 600;
}

.btn-text.disabled-text {
	color: #4e4e4e;
}

.submit-btn {
	background: linear-gradient(135deg, #ff6b35, #f7931e);
	box-shadow: 0 4rpx 12rpx rgba(255, 107, 53, 0.3);
	transition: all 0.3s ease;
}

.submit-btn.disabled {
	background: #939393;
	box-shadow: none;
	opacity: 0.6;
}

.submit-btn .balance-info {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4rpx;
}

.balance-label {
	font-size: 26rpx;
	color: #fff;
	font-weight: 600;
}

.balance-amount {
	font-size: 20rpx;
	color: rgba(255, 255, 255, 0.9);
}

/* 快捷选择区域样式 */
.quick-selection-section {
	display: flex;
	align-items: center;
	gap: 16rpx;
	height: 60rpx;
}

.clear-section {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 0 16rpx;
	height: 60rpx;
	background: #f5f5f5;
	border: 1rpx solid #ddd;
	border-radius: 8rpx;
	min-width: 100rpx;
	transition: all 0.3s ease;
}

.clear-section.has-selection {
	background: rgba(255, 107, 53, 0.1);
	border: 1rpx solid rgba(255, 107, 53, 0.3);
}

.clear-section.disabled {
	background: #f0f0f0;
	border: 1rpx solid #e0e0e0;
	opacity: 0.6;
}

.clear-section:active {
	background: #e8e8e8;
}

.clear-section.has-selection:active {
	background: rgba(255, 107, 53, 0.2);
}

.clear-text {
	font-size: 24rpx;
	color: #666;
	font-weight: 500;
}

.bet-amount-input {
	height: 60rpx;
	border: 1rpx solid #ddd;
	border-radius: 8rpx;
	min-width: 120rpx;
	display: flex;
	align-items: center;
	background: #fff;
}

.amount-input {
	flex: 1;
	height: 100%;
	border: none;
	outline: none;
	background: transparent;
	font-size: 26rpx;
	color: #333;
	text-align: center;
	padding: 0 16rpx;
}

/* 倍数选择器样式 */
.multiplier-section {
	display: flex;
	align-items: center;
	gap: 10rpx;
}
.label {
	width:45rpx;
	font-size: 24rpx;
	color: #666;
}

/* 弹窗样式 */
.popup-content {
	padding: 40rpx;
	min-height: 500rpx;
}

.amount-inputs {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20rpx;
	margin-bottom: 60rpx;
}

.amount-input-item {
	height: 80rpx;
	border: 2rpx solid #e0e0e0;
	border-radius: 12rpx;
	padding: 0 20rpx;
	display: flex;
	align-items: center;
}

.amount-input-field {
	flex: 1;
	height: 100%;
	border: none;
	outline: none;
	background: transparent;
	font-size: 28rpx;
	color: #333;
	text-align: center;
}

.popup-actions {
	display: flex;
	gap: 20rpx;
}

.action-btn {
	flex: 1;
	height: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 12rpx;
	transition: all 0.3s ease;
}

.confirm-btn {
	background: #ff6b6b;
}

.save-btn {
	background: #ff6b35;
}

.action-text {
	font-size: 28rpx;
	color: #fff;
	font-weight: 600;
}

/* 奖金区间显示样式 */
.bonus-info {
	padding: 10rpx 25rpx;
	color: #ff6b35;
	font-size: 26rpx;
}

.summary-info {
	width: 100%;
	margin: 0 0 16rpx 0;
	padding: 12rpx 0;
	background: #fff7f2;
	border-radius: 12rpx;
	text-align: center;
	color: #ff6b35;
	font-size: 25rpx;
	letter-spacing: 2rpx;
	box-shadow: 0 2rpx 8rpx rgba(255,107,53,0.06);
}

/* 购彩篮弹窗样式 */
.bet-basket-popup {
	width: 100vw;
	max-height: 80vh;
	background: #fff;
	border-radius: 20rpx 20rpx 0 0;
	padding: 0;
	overflow: hidden;
}

.popup-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 30rpx 40rpx;
	border-bottom: 1rpx solid #f0f0f0;
	background: #fff;
}

.popup-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
}

.basket-content {
	max-height: 50vh;
	overflow-y: auto;
	padding: 20rpx;
}

.empty-basket {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 200rpx;
}

.empty-text {
	font-size: 28rpx;
	color: #999;
}

.bet-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.bet-item {
	background: #f8f9fa;
	border-radius: 12rpx;
	padding: 20rpx;
	border: 1rpx solid #e9ecef;
}

.bet-info {
	display: flex;
	align-items: center;
	gap: 20rpx;
	margin-bottom: 15rpx;
}

.bet-type {
	font-size: 24rpx;
	color: #ff6b35;
	background: rgba(255, 107, 53, 0.1);
	padding: 4rpx 12rpx;
	border-radius: 8rpx;
}

.bet-numbers {
	flex: 1;
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}

.bet-count {
	font-size: 24rpx;
	color: #666;
}

.bet-controls {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 20rpx;
}

.multiplier-control {
	display: flex;
	align-items: center;
	gap: 10rpx;
}

.control-label {
	font-size: 24rpx;
	color: #666;
}

.bet-amount {
	display: flex;
	align-items: center;
}

.amount-text {
	font-size: 26rpx;
	color: #ff6b35;
	font-weight: 600;
}

.basket-footer {
	padding: 20rpx 40rpx 40rpx;
	border-top: 1rpx solid #f0f0f0;
	background: #fff;
}

.total-info {
	text-align: center;
	margin-bottom: 20rpx;
}

.total-text {
	font-size: 28rpx;
	color: #333;
	font-weight: 600;
}

.footer-buttons {
	gap: 20rpx;
}

.footer-buttons .uv-button {
	flex: 1;
	width: 50%;
}

/* 近期投注弹窗样式 */
.recent-bets-popup {
	max-height: 80vh;
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
}

.recent-content {
	max-height: 60vh;
	overflow-y: auto;
	padding: 20rpx;
}

.loading-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 200rpx;
	gap: 20rpx;
}

.loading-text {
	font-size: 28rpx;
	color: #999;
}

.empty-orders {
	display: flex;
	align-items: center;
	justify-content: center;
	height: 200rpx;
}

.orders-list {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.order-item {
	background: #f8f9fa;
	border-radius: 12rpx;
	padding: 20rpx;
	border: 1rpx solid #e9ecef;
}

.order-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10rpx;
}

.order-number {
	font-size: 26rpx;
	color: #333;
	font-weight: 600;
}

.order-status {
	font-size: 24rpx;
	background:#2c9056;
	padding: 4rpx 12rpx;
	border-radius: 8rpx;
	font-weight: 500;
}

.status-pending {
	color: #f39c12;
	background: rgba(243, 156, 18, 0.1);
}

.status-winning {
	color: #27ae60;
	background: rgba(39, 174, 96, 0.1);
}

.status-losing {
	color: #e74c3c;
	background: rgba(231, 76, 60, 0.1);
}

.status-cancelled {
	color: #95a5a6;
	background: rgba(149, 165, 166, 0.1);
}

.order-info {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10rpx;
}

.order-game {
	font-size: 24rpx;
	color: #666;
}

.order-period {
	font-size: 24rpx;
	color: #666;
}

.order-details {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10rpx;
}

.order-numbers {
	flex: 1;
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
	margin-right: 20rpx;
}

.order-amount {
	font-size: 26rpx;
	color: #ff6b35;
	font-weight: 600;
}

.order-time {
	text-align: right;
}

.time-text {
	font-size: 22rpx;
	color: #999;
}

	/* 占位符内容样式 */
	.placeholder-content {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 300rpx;
		background-color: #f8f9fa;
		border-radius: 16rpx;
		margin: 20rpx;
	}
	
	.placeholder-text {
		font-size: 28rpx;
		color: #999;
		font-weight: 500;
	}
</style>