<template>
	<view class="page-container">
		<view class="container">
		    <NavBar :title="countdown" :showBack="true" :rightText="gameInfo.type_name || ''" />
			<view style="height: var(--status-bar-height);padding-top: 80rpx"></view>
			
			<!-- 奖池金额显示区域 -->
			<view class="prize-pool-section">
				<view class="prize-pool-container">
					<text class="prize-pool-label">当前奖池</text>
					<view class="prize-pool-amount">
						<view class="custom-count-display">
							<text 
								v-for="(digit, index) in formattedPrizePool" 
								:key="index" 
								class="prize-digit" 
								:class="{animated: digit.isAnimated}">
								{{digit.value}}
							</text>
						</view>
						<text class="prize-pool-unit">元</text>
					</view>
					<text class="prize-pool-desc">实时更新中...</text>
				</view>
			</view>
			
			<!-- 当期开奖信息 -->
			<view class="current-draw-inline">
				<text class="period-text">{{currentDraw.period}}期</text>
				<view class="draw-balls">
					<view v-for="(ball, index) in currentDraw.numbers" :key="index" class="lottery-ball" :class="`lottery-ball-num-${ball}`">
						<text class="ball-text">{{ball}}</text>
					</view>
				</view>
				<view class="draw-result">
					<text class="result-label">和：</text>
					<view class="result-tag" :class="getSumTypeClass(currentDraw.sumType)">
						<text class="result-text">{{currentDraw.sumType}}</text>
					</view>
				</view>
				<view class="history-icon" @click="openHistoryPopup">
					<uv-icon name="clock" size="20" color="#999"></uv-icon>
				</view>
			</view>

		<view class="main-content">
			<view class="content">
				<!-- 大小和 -->
				<view class="play-content">
					<view class="size-play">
						<view class="play-header">
							<text class="play-title">{{gameInfo.remark}}</text>
						</view>
						<uv-subsection 
						:list="sizeOptionList" 
						:current="currentSizeIndex" 
						@change="onSizeOptionChange"
						mode="button"
						activeColor="#fff"
						inactiveColor="#e1e1e1"
						bgColor="#333"
						fontSize="30"
						:customStyle="{borderRadius: '222rpx', height: '222rpx'}"
						:customItemStyle="{background: 'linear-gradient(135deg, orangered 0%, #ff4500 100%)', borderRadius: '222rpx'}"
					></uv-subsection>
						
						<!-- 赔率选择 -->
						<view v-if="selectedOption && bonusOptions.length > 0" class="bonus-selection">
							<view class="bonus-header">
								<text class="bonus-title">选择赔率</text>
							</view>
							<uv-subsection 
								:list="bonusOptions.map(item => item.label)" 
								:current="selectedBonusIndex" 
								@change="onBonusOptionChange"
								mode="button"
								activeColor="#f1f1f1"
								inactiveColor="#eee"
								bgColor="#252525"
								fontSize="15"
								:customStyle="{borderRadius: '35rpx', height: '80rpx', marginTop: '20rpx'}"
								:customItemStyle="{background: 'linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%)', borderRadius: '35rpx'}"
							></uv-subsection>
						</view>
						
						<!-- 限额和中奖金额信息 -->
						<view v-if="(selectedBonusIndex >= 0 && currentBetLimits.min > 0) || winningAmount > 0" class="info-row">
							<view v-if="selectedBonusIndex >= 0 && currentBetLimits.min > 0" class="bet-limits-info">
								<text class="limits-text">限额: {{currentBetLimits.min}}元 - {{currentBetLimits.max > 0 ? currentBetLimits.max + '元' : ''}}</text>
							</view>
							<view v-if="selectedBonusIndex >= 0 && bonusOptions.length > 0" class="bonus-info">
								<text class="bonus-text">赔率1：{{currentOdds}}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 未开奖订单列表 -->
		<view class="pending-orders-list">
			<view class="pending-orders-header">
				<text class="pending-orders-title">本期投注</text>
			</view>
			<scroll-view class="pending-orders-content" scroll-y="true" :show-scrollbar="false">
				<!-- 有投注记录时显示列表 -->
				<view class="pending-order-item" v-for="(order, index) in pendingOrders" :key="index" v-if="pendingOrders.length > 0">
					<view class="order-info">
						<view class="order-header">
							<text class="order-lottery-code">{{order.typename}}</text>
							<text class="order-period">第{{order.period}}期</text>

						</view>
						<view class="order-details">
							<text class="order-bet">投注：{{order.betType}}</text>
							<text class="order-amount">金额：{{order.amount}}元</text>
							<text class="order-time-text">{{formatTime(order.createTime)}}</text>
						</view>
					</view>
				</view>
				
				<!-- 无投注记录时显示空状态 -->
				<view class="empty-state" v-if="pendingOrders.length === 0">
					<view class="empty-icon">
						<uv-icon name="file-text" size="60" color="#666"></uv-icon>
					</view>
					<text class="empty-text">暂无投注</text>
					<text class="empty-desc">快来选择您的幸运号码吧</text>
				</view>
			</scroll-view>
		</view>
		
		<!-- 底部间距 -->
		<view style="height: 255rpx;"></view>
		

		
		<!-- 提交栏 -->
		<view class="submit">
			<view class="submit-top">
				<view class="period-info">
					<text class="period-text">第<text class="period-number">{{designatedTime}}</text>期 {{daelDateArr.deyStr}}<text class="deadline-time">{{daelDateArr.daelHours}}</text>截止</text>
				</view>
				<view class="quick-amount-section">
					<uv-subsection 
						:list="quickAmountLabelsOnly" 
						:current="currentQuickIndex" 
						@change="onQuickAmountChange" 
						mode="button" 
						active-color="#e1e1e1" 
						inactive-color="#ccc" 
						bg-color="#2f2f2f" 
						font-size="13" 
						:bold="true" 
						:custom-style="{
							borderRadius: '25rpx',
							background: '#fff',
						}" 
						:custom-item-style="{
							background: '#4f46e5',
							borderRadius: '20rpx',
						}">
					</uv-subsection>
					<view class="setting-button" @click="openQuickSettingPopup">
						<uv-icon name="setting" size="22" color="#999"></uv-icon>
					</view>
				</view>
			</view>
			<view class="submit-bottom">
				<view style="font-size: 26rpx;" class="amount-info">
					<view class="bet-amount-section">
						<text>投注金额：</text>
						<input 
							v-model="customAmount" 
							type="number" 
							placeholder="自定义金额"
							class="custom-amount-input" 
							@input="onCustomAmountInput" 
							style="color: orangered;" />

						<text>元</text>
					</view>
				</view>
				<view class="bottom-button bet-confirm-button" :class="{disabled: !(selectedOption && selectedBonusIndex >= 0 && bonusOptions.length > 0 && customAmount && parseFloat(customAmount) >= (currentBetLimits.min || 2))}">
					<view class="bottom-button02" @click="handleBetClick">确认投注</view>
				</view>
			</view>
		</view>

		<!-- 快捷金额设置弹窗 -->
		<uv-popup 
			ref="quickSettingPopup" 
			mode="center" 
			:round="25" 
			:overlay="true" 
			:close-on-click-overlay="true">
			<view class="simple-popup">
				<view class="simple-popup-content">
					<view class="simple-input-group" v-for="(amount, index) in quickAmounts" :key="index">
						<text class="simple-input-label">金额{{index + 1}}：</text>
						<input 
							v-model="tempQuickAmounts[index]" 
							type="number" 
							placeholder="金额" 
							class="simple-input" />
					</view>
				</view>
				<view class="simple-popup-footer">
					<view class="simple-btn-row">
						<view class="simple-btn simple-btn-cancel" @click="closeQuickSettingPopup">取消</view>
						<view class="simple-btn simple-btn-confirm" @click="saveQuickSettings">保存</view>
					</view>
				</view>
			</view>
		</uv-popup>

		<!-- 投注确认弹窗 -->
		<uv-popup 
			ref="betConfirmPopup"
			mode="center" 
			:round="20" 
			:overlay="true" 
			:close-on-click-overlay="false"
			@change="onBetConfirmChange">
			<view class="bet-confirm-popup">
				<view class="bet-confirm-header">
					<view class="confirm-icon">
						<text class="confirm-icon-text">💰</text>
					</view>
					<text class="bet-confirm-title">确认投注</text>
				</view>
				<view class="bet-confirm-content">
					<view class="bet-detail-row">
						<text class="detail-label">游戏类型：</text>
						<text class="detail-value">{{gameType.toUpperCase()}}</text>
					</view>
					<view class="bet-detail-row">
						<text class="detail-label">投注期号：</text>
						<text class="detail-value">第{{designatedTime}}期</text>
					</view>
					<view class="bet-detail-row">
						<text class="detail-label">投注选项：</text>
						<text class="detail-value highlight">{{selectedOption}}</text>
					</view>
					<view class="bet-detail-row">
						<text class="detail-label">投注金额：</text>
						<text class="detail-value amount">{{customAmount}}元</text>
					</view>
					<view class="bet-detail-row">
						<text class="detail-label">当前余额：</text>
						<text class="detail-value amount">{{(userInfo.balance || 0).toFixed(2)}}元</text>
					</view>
					<view class="bet-detail-row">
						<text class="detail-label">赔率：</text>
						<text class="detail-value odds">{{currentOdds}}倍</text>
					</view>
					<view class="bet-detail-row">
						<text class="detail-label">预计奖金：</text>
						<text class="detail-value bonus">{{estimatedBonus}}元</text>
					</view>
				</view>
				<view class="bet-confirm-footer">
					<view class="confirm-btn cancel-btn" @click="closeBetConfirmDialog">
						<text class="btn-text">取消</text>
					</view>
					<view class="confirm-btn submit-btn" @click="confirmBet">
						<text class="btn-text">确认投注</text>
					</view>
				</view>
			</view>
		</uv-popup>

		<!-- 历史记录弹窗 -->
		<uv-popup 
			ref="historyPopup" 
			mode="top" 
			:round="5" 
			:overlay="true" 
			:close-on-click-overlay="true">
			<view class="history-popup">
				<view class="history-popup-header">
					<text class="history-popup-title">历史开奖</text>
					<view class="history-close" @click="$refs.historyPopup.close()">
						<uv-icon name="close" size="18" color="#999"></uv-icon>
					</view>
				</view>
				<scroll-view class="history-popup-content" scroll-y="true" :show-scrollbar="false">
					<view class="history-popup-item" v-for="(item, index) in historyDrawList" :key="index">
						<view class="history-popup-period">{{item.period}}期</view>
						<view class="history-popup-balls">
							<view v-for="(ball, ballIndex) in item.numbers" :key="ballIndex" class="lottery-ball" :class="`lottery-ball-num-${ball}`">
								<text class="ball-text">{{ball}}</text>
							</view>
						</view>
						<view class="history-popup-sum">
							<text class="history-sum-text">和:</text>
							<view class="history-sum-tag" :class="getSumTypeClass(item.sumType)">
								<text class="history-sum-tag-text">{{item.sum}}</text>
							</view>
							<view class="history-type-tag" :class="getSumTypeClass(item.sumType)">
								<text class="history-type-tag-text">{{item.sumType}}</text>
							</view>
						</view>
					</view>
				</scroll-view>
			</view>
		</uv-popup>

		</view>
	</view>
</template>

<script>
import NavBar from '@/components/TabBar/NavBar.vue';
import { submitBet, getBetOrders } from '@/api/bet/bet.js'
import { getCurrentPeriod, getHistoryDraw, getGameInfo, getBonusPool, getMaxBetAmount } from '@/api/lottery/lottery.js'
import { getUserInfo } from '@/api/user.js'
import { formatTime } from '@/utils/common.js'
export default {
	components: {
		NavBar
	},
	data() {
			return {
				// 往期数据
			wqsj: {
				code: '',
				lotteryDrawResult: [],
				list: []
			},
			// 加载状态
			loading: {
				period: false,
				history: false
			},
			acceptShow: true,
			showHistory: false,
			

			
			// 大小和选择（单选）
			selectedOption: '',
			
			// 页面类型参数
			gameType: 'ff3d',
			
			// uv-subsection 分段器数据
			sizeOptionList: [],
			sizeOptionKeys: [], // 对应的key值数组
			currentSizeIndex: -1, // -1表示未选择
			
			// 赔率选择相关
			bonusOptions: [], // 当前选中玩法的赔率选项
			selectedBonusIndex: 0, // 选中的赔率索引
			currentBetLimits: { // 当前投注限额
				min: 0,
				max: 0
			},	
			
			// 投注数据
			data: {
				note: 0,
				money: 0,
				beilv: 1,
				name: "",
				yeimian: '',
				yeimianIndex: 0,
				xuanze: 0,
				type: '',
				data: []
			},
			
			// 期数和时间
			designatedTime: '',
			daelDateArr: {
				deyStr: '',
				daelDate: '',
				daelHours: ''
			},
			bouttmBoole: true,
			// 倒计时相关
			countdown: '00:00:00',
			countdownTimer: null,
			
			// 当前期号时间信息
			currentPeriodInfo: {
				closing_time: '',
				draw_time_end: '',
				next_issue_start_time: ''
			},	
			// 奖金信息
			bonusInfo: {
				bonusmax: 0,
				bonusmin: 0,
				note: 0
			},
			// 奖金提示框相关变量
			showTooltip: false,
			tooltipAmount: 0,
			tooltipStyle: {
				top: '0px',
				left: '0px'
			},
			// 快捷金额设置
			quickAmounts: [10, 20, 50, 100, 500], // 默认快捷金额
			tempQuickAmounts: [10, 20, 50, 100, 500], // 临时编辑的快捷金额
			currentQuickIndex: -1, // 当前选中的快捷金额索引
			customAmount: '', // 自定义金额输入
			
			// 奖池金额
			prizePoolAmount: 0.00, // 奖池金额
			previousPrizePool: 10000.00, // 上一次的奖池金额
			prizePoolTimer: null, // 奖池更新定时器
			
			// 页面状态
			pageVisible: true, // 页面是否可见
			isFirstLoad: false, // 是否首次加载
			onShowDebounceTimer: null, // onShow防抖定时器
			
			// 当期开奖数据
			currentDraw: {
				period: '',
				numbers: [],
				sumType: ''
			},
			
			// 历史开奖数据
			historyDrawList: [],
			
			// 未开奖订单数据
			pendingOrders: [],
			

			
			// 游戏信息
			gameInfo: {
				is_enabled: 1,
				min_bet_amount: '2.00',
				system_max_bet: '0',
				daily_limit: '0.00',
				bonus_list: []
			},
			
			// 中奖金额
			winningAmount: 0,
			
			// 用户信息
			userInfo: {
				balance: 0, // 用户余额
				username: '',
				mobile: ''
			},
			
			// 投注确认弹窗显示状态
			showBetConfirm: false

		}
	},

	computed: {
		// 快捷金额标签数组
		quickAmountLabels() {
			return this.quickAmounts.map(amount => `${amount}元`);
		},
		// 快捷金额标签数组（不包含设置按钮）
		quickAmountLabelsOnly() {
			return this.quickAmounts.map(amount => `${amount}元`);
		},
		
		// 投注确认弹窗相关计算属性
		// 选中选项的显示名称
		selectedOptionDisplay() {
			if (!this.selectedOption) return '';
			return this.selectedOption;
		},
		
		// 当前选中的赔率
		currentOdds() {
			if (this.selectedBonusIndex < 0 || !this.bonusOptions[this.selectedBonusIndex]) {
				return 0;
			}
			return parseFloat(this.bonusOptions[this.selectedBonusIndex].value || 0);
		},
		
		// 预计奖金
		estimatedBonus() {
			const amount = parseFloat(this.customAmount) || 0;
			const odds = this.currentOdds;
			return (amount * odds).toFixed(2);
		},
		

		
		// 格式化奖池金额，只对变化的数字添加动画
		formattedPrizePool() {
			const current = this.prizePoolAmount.toFixed(2);
			const previous = this.previousPrizePool.toFixed(2);
			const result = [];
			
			// 添加千分位分隔符
			const formatNumber = (num) => {
				return num.replace(/(\d)(?=(\d{3})+\.)/g, '$1,');
			};
			
			const formattedCurrent = formatNumber(current);
			const formattedPrevious = formatNumber(previous);
			
			// 比较每个字符，标记变化的位置
			for (let i = 0; i < formattedCurrent.length; i++) {
				const char = formattedCurrent[i];
				const isAnimated = i < formattedPrevious.length ? 
					char !== formattedPrevious[i] : true;
				
				result.push({
					value: char,
					isAnimated: isAnimated && /\d/.test(char) // 只对数字字符添加动画
				});
			}
			
			return result;
		},
		
		// 格式化时间戳
		formatTime() {
			return (timestamp) => {
				return formatTime(timestamp);
			};
		}
	},

	methods: {
		// 切换历史记录显示
		toggleHistory() {
			this.showHistory = !this.showHistory;
		},
		
		// 选择大小和选项（单选）
		selectSizeOption(option) {
			this.selectedOption = option;
			// 同步更新分段器索引
			this.currentSizeIndex = this.sizeOptionList.indexOf(option);
			this.calculateBets();
		},
		
		// uv-subsection 分段器变化事件
		async onSizeOptionChange(index) {
			this.currentSizeIndex = index;
			this.selectedOption = this.sizeOptionList[index];
			
			// 根据选择的玩法生成赔率选项（会自动选择第一个赔率）
			await this.generateBonusOptions();
			
			// 注释掉重置赔率选择，因为generateBonusOptions已经自动选择了第一个
			// this.selectedBonusIndex = -1;
			
			this.calculateBets();
		},
		
		// 生成赔率选项
		async generateBonusOptions() {
			if (!this.selectedOption || !this.gameInfo.bonus_list) {
				this.bonusOptions = [];
				return;
			}
			
			// 根据选中的选项（大、和、小）找到对应的玩法
			const selectedPlay = this.gameInfo.bonus_list.find(item => item.type_name === this.selectedOption);
			if (selectedPlay && selectedPlay.bonus_json && Array.isArray(selectedPlay.bonus_json)) {
				// bonus_json是数组格式，包含多个赔率值
				this.bonusOptions = selectedPlay.bonus_json.map((bonus, index) => ({
					label: `${bonus}倍`,
					value: parseFloat(bonus),
					index: index,
					type_key: selectedPlay.type_key,
					type_name: selectedPlay.type_name
				}));
				
				// 自动选择第一个赔率
				if (this.bonusOptions.length > 0) {
					this.selectedBonusIndex = 0;
					await this.updateBetLimits();
					this.calculateBets();
					this.calculateWinningAmount();
				}
			} else {
				this.bonusOptions = [];
				this.selectedBonusIndex = -1;
			}
		},
		
		// 选择赔率
		async onBonusOptionChange(index) {
			this.selectedBonusIndex = index;
			await this.updateBetLimits();
			this.calculateBets();
			// 计算中奖金额
			this.calculateWinningAmount();
		},
		
		// 更新投注限额
		async updateBetLimits() {
			if (!this.selectedOption || !this.gameInfo.bonus_list) {
				return;
			}
			
			// 根据选中的选项找到对应的玩法
			const selectedPlay = this.gameInfo.bonus_list.find(item => item.type_name === this.selectedOption);
			if (selectedPlay) {
				// 最小限额：取min_price和min_bet_amount中较大的值
				const playMinPrice = parseFloat(selectedPlay.min_price || 0);
				const gameMinBet = parseFloat(this.gameInfo.min_bet_amount || 0);
				this.currentBetLimits.min = Math.max(playMinPrice, gameMinBet);
				
				// 最大限额：取max_price和max_bet_amount中较小的值（0表示不限制）
				const playMaxPrice = parseFloat(selectedPlay.max_price || 0);
				const gameMaxBet = parseFloat(this.gameInfo.system_max_bet || 0);
				
				if (playMaxPrice === 0 && gameMaxBet === 0) {
					this.currentBetLimits.max = 0; // 都不限制
				} else if (playMaxPrice === 0) {
					this.currentBetLimits.max = gameMaxBet; // 玩法不限制，使用游戏限制
				} else if (gameMaxBet === 0) {
					this.currentBetLimits.max = playMaxPrice; // 游戏不限制，使用玩法限制
				} else {
					this.currentBetLimits.max = Math.min(playMaxPrice, gameMaxBet); // 都有限制，取较小值
				}
				
				// 获取动态最大投注额
				await this.fetchMaxBetAmount();
			} else {
				// 如果找不到对应玩法，使用游戏默认限制
				this.currentBetLimits.min = parseFloat(this.gameInfo.min_bet_amount || 0);
				this.currentBetLimits.max = parseFloat(this.gameInfo.system_max_bet || 0);
			}
		},
		
		// 获取最大投注额
		async fetchMaxBetAmount() {
			if (!this.selectedOption || this.selectedBonusIndex < 0 || !this.bonusOptions.length || !this.designatedTime) {
				return;
			}
			
			try {
				const selectedPlay = this.gameInfo.bonus_list.find(item => item.type_name === this.selectedOption);
				if (!selectedPlay) return;
				
				const currentOdds = this.bonusOptions[this.selectedBonusIndex].value;
				
				// 映射玩法类型
				const playTypeMap = {
					'大': 'da',
					'小': 'xiao', 
					'和': 'he'
				};
				
				const playType = playTypeMap[this.selectedOption];
				if (!playType) return;
				
				const response = await getMaxBetAmount({
					lottery_code: this.gameType,
					period: this.designatedTime,
					play_type: playType,
					odds: currentOdds
				});
				
				if (response.code === 1 && response.data) {
					// 去除逗号分隔符后再转换为数字
					const dynamicMaxBet = parseFloat(response.data.system_max_bet.toString().replace(/,/g, ''));
					
					// 直接使用返回的max_bet_amount作为该赔率的最大限额
					this.currentBetLimits.max = dynamicMaxBet;
					
					console.log('动态最大投注额:', {
						system_max: response.data.system_max_bet,
						user_max: response.data.user_max_bet,
						final_max: response.data.system_max_bet,
						current_limit: this.currentBetLimits.max
					});
				}
			} catch (error) {
				console.error('获取最大投注额失败:', error);
			}
		},
		
		// 初始化数据格式
		async init() {
			this.data = {
				note: 0,
				money: 0,
				beilv: 1,
				name: "福彩3D",
				yeimian: 'ff3d',
				yeimianIndex: this.current,
				type: '',
				data: []
			};
			// 清空所有选号
			this.selectedOption = '';
			// 重置分段器索引
			this.currentSizeIndex = -1;
			// 清空奖金信息
			this.bonusInfo = {
				bonusmax: 0,
				bonusmin: 0,
				note: 0
			};
			
			// 获取彩种详情
			await this.loadGameInfo();
			
			// 获取当前期号信息
			await this.loadCurrentPeriod();
			
			// 获取历史开奖记录
			await this.loadHistoryData();
			
			// 获取待开奖订单
			await this.loadPendingOrders();
			
			// 启动奖池定时更新（确保有期号信息后再启动）
			this.startPrizePoolUpdate();
		},
		
		// 投注成功后重置（重置赔率选项，保留玩法选择）
		resetAfterBet() {
			// 重置投注金额和倍率
			this.data.money = 0;
			this.data.beilv = 1;
			this.data.note = 0;
			
			// 清空自定义金额输入
			this.customAmount = '';
			
			// 重置快捷金额选择
			this.currentQuickIndex = -1;
			
			// 清空奖金信息
			this.bonusInfo = {
				bonusmax: 0,
				bonusmin: 0,
				note: 0
			};
			
			// 重置中奖金额
			this.winningAmount = 0;
			
			// 重置赔率选择到第一个选项
			if (this.bonusOptions.length > 0) {
				this.selectedBonusIndex = 0;
				this.updateBetLimits();
				this.calculateWinningAmount();
			}
			
			// 保留玩法选择：selectedOption 和 currentSizeIndex 不重置
		},
		
		// 初始化倒计时
		initCountdown() {
			// 设置截止时间（今天21:25）
			const today = new Date();
			const endTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 21, 25, 0);
			
			// 如果当前时间已过今天的截止时间，则设置为明天的截止时间
			if (new Date() > endTime) {
				endTime.setDate(endTime.getDate() + 1);
				this.daelDateArr.deyStr = '明天';
			}
			
			this.updateCountdown(endTime);
			
			// 每秒更新倒计时
			this.countdownTimer = setInterval(() => {
				this.updateCountdown(endTime);
			}, 1000);
		},
		
		// 更新倒计时显示
		updateCountdown(endTime) {
			const now = new Date();
			const diff = endTime - now;
			
			if (diff <= 0) {
				this.countdown = '00:00:00';
				if (this.countdownTimer) {
					clearInterval(this.countdownTimer);
					this.countdownTimer = null;
				}
				return;
			}
			
			const hours = Math.floor(diff / (1000 * 60 * 60));
			const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((diff % (1000 * 60)) / 1000);
			
			this.countdown = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
		},
		
		// 更新精确倒计时显示（精确到秒）
		updatePreciseCountdown(endTime) {
			const now = new Date();
			const diff = endTime - now;
			
			// 检查倒计时是否超过issue_time_interval，如果超过则重置计时器
			if (this.currentPeriod && this.currentPeriod.issue_time_interval) {
				const maxCountdownTime = this.currentPeriod.issue_time_interval * 1000; // 转换为毫秒
				if (diff > maxCountdownTime) {
					console.log('倒计时时间超过issue_time_interval，重置计时器');
					this.clearAllTimers();
					this.getCurrentPeriod(); // 重新获取当前期数信息
					return;
				}
			}
			
			if (diff <= 0) {
				this.countdown = '00:00:00';
				this.bouttmBoole = false; // 设置为截止状态
				if (this.countdownTimer) {
					clearInterval(this.countdownTimer);
					this.countdownTimer = null;
				}
				// 倒计时结束后，检查当前状态并处理
				this.handleCountdownEnd();
				return;
			}
			
			const hours = Math.floor(diff / (1000 * 60 * 60));
			const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
			const seconds = Math.floor((diff % (1000 * 60)) / 1000);
			
			this.countdown = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
			this.bouttmBoole = true; // 确保在截止时间前可以投注
		},
		
		// 从时间戳更新倒计时
		updateCountdownFromTimestamp(timestamp) {
			// 清除之前的定时器
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
			
			const endTime = new Date(timestamp * 1000);
			this.updateCountdown(endTime);
			
			// 每秒更新倒计时
			this.countdownTimer = setInterval(() => {
				this.updateCountdown(endTime);
			}, 1000);
		},
		
		// 处理倒计时结束后的状态
		async handleCountdownEnd() {
			try {
				// 使用已保存的期号时间信息
				if (this.currentPeriodInfo.closing_time && this.currentPeriodInfo.draw_time_end && this.currentPeriodInfo.next_issue_start_time) {
					const now = new Date();
					
					// 解析时间
					const today = new Date();
					const [closeHours, closeMinutes, closeSeconds] = this.currentPeriodInfo.closing_time.split(':').map(Number);
					const [drawEndHours, drawEndMinutes, drawEndSeconds] = this.currentPeriodInfo.draw_time_end.split(':').map(Number);
					const [nextStartHours, nextStartMinutes, nextStartSeconds] = this.currentPeriodInfo.next_issue_start_time.split(':').map(Number);
					
					const closingTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), closeHours, closeMinutes, closeSeconds);
					const drawEndTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), drawEndHours, drawEndMinutes, drawEndSeconds);
					const nextStartTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), nextStartHours, nextStartMinutes, nextStartSeconds);
					
					// 如果下期开始时间小于当前时间，说明是第二天
					if (nextStartTime < now) {
						nextStartTime.setDate(nextStartTime.getDate() + 1);
					}
					
					// 判断当前处于哪个时间段
					if (now >= closingTime && now <= drawEndTime) {
						// 封盘中状态
						this.countdown = '开奖中';
						this.bouttmBoole = false;
						
						// 设置定时器等待下期开始
						this.waitForNextIssue(nextStartTime);
					} else if (now >= nextStartTime) {
						// 新的一期已经开始
						this.startNewIssue();
					} else {
						// 等待下期开始
						this.waitForNextIssue(nextStartTime);
					}
				} else {
					// 如果没有保存的时间信息，重新获取
					const response = await getCurrentPeriod('ff3d');
					
					if (response.code === 1 && response.data) {
						// 保存时间信息并重新处理
						this.currentPeriodInfo = {
							closing_time: response.data.closing_time,
							draw_time_end: response.data.draw_time_end,
							next_issue_start_time: response.data.next_issue_start_time
						};
						// 递归调用自己重新处理
						this.handleCountdownEnd();
					} else {
						console.error('获取期号信息失败:', response.msg);
						// 默认显示封盘中
						this.countdown = '开奖中';
						this.bouttmBoole = false;
					}
				}
			} catch (error) {
				console.error('处理倒计时结束状态异常:', error);
				// 默认显示封盘中
				this.countdown = '开奖中';
				this.bouttmBoole = false;
			}
		},
		
		// 等待下期开始
		waitForNextIssue(nextStartTime) {
			// 清除现有定时器
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
			
			// 设置定时器检查是否到了下期开始时间
			this.countdownTimer = setInterval(() => {
				const now = new Date();
				if (now >= nextStartTime) {
					// 时间到了，开始新的一期
					this.startNewIssue();
				}
			}, 1000);
		},
		
		// 开始新的一期
		async startNewIssue() {
			// 清除定时器
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
			
			// 显示提示
			uni.showToast({
				title: '新的一期开始了',
				icon: 'none',
				duration: 2000
			});
			
			// 重新获取期号数据并启动倒计时
			await this.loadCurrentPeriod();
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
					
					// 初始化奖池金额为彩种的默认奖池金额
					if (response.data.default_pond) {
						this.prizePoolAmount = parseFloat(response.data.default_pond);
						this.previousPrizePool = parseFloat(response.data.default_pond);
					}
					
					// 更新投注数据中的游戏信息
					this.data.name = response.data.type_name;
					this.data.yeimian = response.data.type_code;
					
					// 根据bonus_list生成选项列表
					if (response.data.bonus_list && response.data.bonus_list.length > 0) {
						// 直接从bonus_list的type_name字段生成选项列表
						this.sizeOptionList = response.data.bonus_list.map(item => item.type_name);
						this.sizeOptionKeys = response.data.bonus_list.map(item => item.type_key);
					} else {
						// 如果没有bonus_list，使用默认选项
						this.sizeOptionList = ['大', '和', '小'];
						this.sizeOptionKeys = ['da', 'xiao', 'he'];
					}
					
					// 更新最小投注金额到bonusInfo
				this.bonusInfo.money = parseFloat(response.data.min_bet_amount);
	
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
		

		
		// 计算注数和金额
		calculateBets() {
			let notes = 0;
			let playType = '';
			
			if (this.selectedOption) {
				notes = 1; // 注数永远是一注
				// 根据选中的选项找到对应的玩法
				const selectedPlay = this.gameInfo.bonus_list.find(item => item.type_name === this.selectedOption);
				if (selectedPlay) {
					playType = selectedPlay.type_key; // 使用玩法的type_key
				}
			}
			
			this.data.note = notes;
			if (playType && notes > 0) {
				this.getBonusInfo(playType);
				// 计算中奖金额
				this.calculateWinningAmount();
			} else {
				// 如果没有选号，清空money和奖金信息
				this.data.money = 0;
				this.bonusInfo = {
					bonusmax: 0,
					bonusmin: 0,
					note: 0
				};
				this.winningAmount = 0;
			}
		},
		
		// 组合计算
		combination(n, r) {
			if (r > n) return 0;
			let result = 1;
			for (let i = 0; i < r; i++) {
				result = result * (n - i) / (i + 1);
			}
			return Math.round(result);
		},
		
		// 计算中奖金额
		calculateWinningAmount() {
			// 检查是否选择了选项、赔率和有投注金额
			if (this.selectedOption && this.selectedBonusIndex >= 0 && this.bonusOptions.length > 0) {
				const betAmount = parseFloat(this.customAmount) || 0;
				const selectedBonus = this.bonusOptions[this.selectedBonusIndex];
				const odds = selectedBonus ? selectedBonus.value : 0;
				
				if (odds > 0 && betAmount > 0) {
					// 中奖金额 = 投注金额 × 赔率
					this.winningAmount = (betAmount * odds).toFixed(2);
				} else {
					this.winningAmount = 0;
				}
			} else {
				this.winningAmount = 0;
			}
			// 计算完成
		},
		
		// 提交
		async messageToggle() {
			if (!this.bouttmBoole) {
				uni.showToast({ title: '本期已截止、请等待下期开启后在进行投注~', icon: 'none', position: 'center' });
				return;
			}
			
			// 检查是否选择了投注选项
			if (!this.selectedOption) {
				uni.showToast({ title: '请选择投注选项', icon: 'none', duration: 2000 });
				return;
			}
			
			// 赔率检查已移除，因为每个选项都有固定赔率
			
			if (!this.customAmount) {
				uni.showToast({ title: '请输入投注金额', icon: 'none', duration: 2000 });
				return;
			}
			
			// 准备投注数据
				const betAmount = parseFloat(this.customAmount);
				
				// 使用当前选择的投注限额进行验证
				const minLimit = this.currentBetLimits.min;
				const maxLimit = this.currentBetLimits.max;
				
				// 检查最小投注金额
				if (betAmount < minLimit) {
					uni.showToast({
						title: `投注金额不能少于${minLimit}元`,
						icon: 'none',
						duration: 2000
					});
					return;
				}
				
				// 检查最大投注金额（0表示不限制）
				if (maxLimit > 0 && betAmount > maxLimit) {
					uni.showToast({
						title: `投注金额不能超过${maxLimit}元`,
						icon: 'none',
						duration: 2000
					});
					return;
				}
				
				// 显示加载提示
				uni.showLoading({ title: '提交中...' });
				
				// 获取当前选择的玩法和赔率
				const selectedPlay = this.gameInfo.bonus_list.find(item => item.type_name === this.selectedOption);
				if (!selectedPlay) {
					uni.hideLoading();
					uni.showToast({ title: '玩法信息错误，请重新选择', icon: 'none', duration: 2000 });
					return;
				}
				
				const betData = [{
					type_key: selectedPlay.type_key,
					type_name: selectedPlay.type_name,
					numbers: this.selectedOption,
					note: 1, // 注数永远是一注
					money: betAmount,
					multiplier: 1, // 倍数
					bonus_index: this.selectedBonusIndex // 添加赔率索引
				}];
				
				// 计算总金额
				const totalAmount = betData.reduce((total, bet) => {
					return total + (bet.money * bet.multiplier * bet.note);
				}, 0);
				
				// 调用投注API，使用动态的gameType
				const response = await submitBet({
					lottery_code: this.gameType,
					period_no: this.designatedTime,
					bet_data: betData,
					total_amount: totalAmount
				});
					
				uni.hideLoading();
					
			if (response.code === 1) {
			// 投注成功
				uni.showToast({ 
					title: `投注成功`, 
					icon: 'success',
					duration: 2000
				});
				
				// 使用服务器返回的实际投注金额和余额
				const actualAmount = response.data.total_amount || betAmount;
				if (response.data.remaining_balance !== undefined) {
					// 直接使用服务器返回的剩余余额
					this.userInfo.balance = response.data.remaining_balance;
				} else {
					// 如果没有返回剩余余额，则使用实际投注金额计算
					this.userInfo.balance -= actualAmount;
				}
				
				// 添加新订单到列表顶部
				const newOrder = {
					period: this.designatedTime,
					betType: `${this.selectedOption}`,
					amount: actualAmount,
					typename: this.gameInfo.type_name,
					createTime: new Date().getTime()
				};
				this.pendingOrders.unshift(newOrder);

					// 只重置赔率，不重置玩法
					this.resetAfterBet();
				} else {
					// 投注失败
					uni.showToast({ 
						title: response.msg || '投注失败，请重试', 
						icon: 'none',
						duration: 3000
					});
				}
		},
		
		// 返回按钮
		back() {
			uni.navigateBack({ delta: 1 });
		},
		
		// 大小和机选
		machineSelectDaxiao() {
			// 随机选择大、和、小中的一个
			const randomIndex = Math.floor(Math.random() * this.sizeOptionList.length);
			this.currentSizeIndex = randomIndex;
			this.selectedOption = this.sizeOptionList[randomIndex];
			this.calculateBets();
		},
		
		// 获取奖金信息
		async getBonusInfo(playType) {
			try {
				if (this.selectedOption && this.selectedBonusIndex >= 0 && this.bonusOptions.length > 0) {
					// 获取当前选择的赔率
					const selectedBonus = this.bonusOptions[this.selectedBonusIndex];
					const odds = parseFloat(selectedBonus.odds);
					
					// 使用当前投注限额的最小值作为基础投注金额
					const baseAmount = this.currentBetLimits.min || 1;
					
					// 计算奖金（投注金额 * 赔率）
					const bonus = baseAmount * odds;
					
					this.bonusInfo = {
						bonusmax: bonus,
						bonusmin: bonus,
						note: 1,
						money: baseAmount
					};
					// 设置最低投注金额
					this.data.money = baseAmount;
				} else {
					// 清空奖金信息
					this.bonusInfo = {
						bonusmax: 0,
						bonusmin: 0,
						note: 0,
						money: 0
					};
					this.data.money = 0;
				}
			} catch (error) {
	
				this.bonusInfo = {
					bonusmax: 0,
					bonusmin: 0,
					note: 0,
					money: 0
				};
				this.data.money = 0;
			}
		},
		
		// 快捷金额分段器变化事件
		onQuickAmountChange(index) {
			this.currentQuickIndex = index;
			// 直接是快捷金额按钮
			const amount = this.quickAmounts[index];
			this.quickBet(amount);
			// 同步到自定义金额输入框
			this.customAmount = amount.toString();
			// 计算中奖金额
			this.calculateWinningAmount();
		},
		
		// 自定义金额输入事件
		onCustomAmountInput() {
			// 如果输入为空或无效，重置金额
			if (!this.customAmount || this.customAmount.trim() === '') {
				this.data.money = 0;
				this.data.beilv = 1;
				this.currentQuickIndex = -1;
				this.calculateWinningAmount();
				return;
			}
			
			let amount = parseFloat(this.customAmount);
			
			// 自动转换为整数（投注金额只能是整数）
			if (!isNaN(amount)) {
				amount = Math.floor(amount); // 向下取整
				this.customAmount = amount.toString(); // 更新输入框显示
			}
			
			const minAmount = this.currentBetLimits.min || 1;
			const maxAmount = this.currentBetLimits.max;

			// 注数永远是一注，金额变动不影响注数
			this.data.beilv = 1;
			// 清空快捷金额选择
			this.currentQuickIndex = -1;
			
			if (!isNaN(amount) && amount > 0) {
				// 检查金额是否超出限制
				if (amount < minAmount) {
					// 自动设置为最低金额
					this.customAmount = minAmount.toString();
					this.data.money = minAmount;
					uni.showToast({
						title: `最少下注${minAmount}元`,
						icon: 'none',
						duration: 2000
					});
				} else if (maxAmount > 0 && amount > maxAmount) {
					// 自动设置为最高金额
					this.customAmount = maxAmount.toString();
					this.data.money = maxAmount;
					uni.showToast({
						title: `最多下注${maxAmount}元`,
						icon: 'none',
						duration: 2000
					});
				} else {
					// 金额在合理范围内
					this.data.money = amount;
				}
				// 计算中奖金额
				this.calculateWinningAmount();
			} else {
				this.data.money = 0;
				this.calculateWinningAmount();
			}
		},
		
		// 打开历史记录弹窗
		openHistoryPopup() {
			this.$refs.historyPopup.open();
		},
		
		// 处理投注按钮点击
		handleBetClick() {
			// 检查投注条件
			const hasValidBonus = this.selectedBonusIndex >= 0 && this.bonusOptions.length > 0;
			const condition = this.selectedOption && hasValidBonus && this.customAmount && parseFloat(this.customAmount) >= (this.currentBetLimits.min || 2);
			
			if (condition) {
				this.showBetConfirmDialog();
			} else {
				// 提供更具体的错误提示
				if (!this.selectedOption) {
					uni.showToast({ title: '请选择投注选项', icon: 'none', duration: 2000 });
				} else if (!hasValidBonus) {
					uni.showToast({ title: '请选择赔率', icon: 'none', duration: 2000 });
				} else if (!this.customAmount) {
					uni.showToast({ title: '请输入投注金额', icon: 'none', duration: 2000 });
				} else if (parseFloat(this.customAmount) < (this.currentBetLimits.min || 2)) {
					uni.showToast({ title: `投注金额不能少于${this.currentBetLimits.min || 2}元`, icon: 'none', duration: 2000 });
				}
			}
		},
		
		// 显示投注确认弹窗
		showBetConfirmDialog() {
			// 再次验证投注条件
			if (!this.selectedOption) {
				uni.showToast({ title: '请选择投注选项', icon: 'none', duration: 2000 });
				return;
			}
			
			// 检查赔率选项是否存在且已选择
			if (!this.bonusOptions || this.bonusOptions.length === 0) {
				uni.showToast({ title: '赔率选项未加载，请重新选择投注选项', icon: 'none', duration: 2000 });
				return;
			}
			
			if (this.selectedBonusIndex < 0 || this.selectedBonusIndex >= this.bonusOptions.length) {
				// 自动选择第一个赔率
				this.selectedBonusIndex = 0;
				this.updateBetLimits();
				this.calculateBets();
				this.calculateWinningAmount();
			}
			
			if (!this.customAmount) {
				uni.showToast({ title: '请输入投注金额', icon: 'none', duration: 2000 });
				return;
			}
			
			const betAmount = parseFloat(this.customAmount);
			const minLimit = this.currentBetLimits.min;
			const maxLimit = this.currentBetLimits.max;
			
			// 检查投注金额范围
			if (betAmount < minLimit) {
				uni.showToast({
					title: `投注金额不能少于${minLimit}元`,
					icon: 'none',
					duration: 2000
				});
				return;
			}
			
			if (maxLimit > 0 && betAmount > maxLimit) {
				uni.showToast({
					title: `投注金额不能超过${maxLimit}元`,
					icon: 'none',
					duration: 2000
				});
				return;
			}
			
			// 显示确认弹窗
			this.showBetConfirm = true;
			this.$refs.betConfirmPopup.open();
		},
		
		// 关闭投注确认弹窗
		closeBetConfirmDialog() {
			this.showBetConfirm = false;
			this.$refs.betConfirmPopup.close();
		},
		
		// 处理投注确认弹窗change事件
		onBetConfirmChange(e) {
			if (!e.show) {
				this.showBetConfirm = false;
			}
		},
		
		// 确认投注
		confirmBet() {
			// 关闭确认弹窗
			this.closeBetConfirmDialog();
			// 执行投注
			this.messageToggle();
		},
		
		// 获取用户信息
		async loadUserInfo() {
			try {
				const response = await getUserInfo();
				if (response.code === 1 && response.data) {
					this.userInfo = {
						balance: parseFloat(response.data.money || 0),
						username: response.data.username || '',
						mobile: response.data.mobile || ''
					};
				}
			} catch (error) {
				console.error('获取用户信息失败:', error);
			}
		},
		

		
		// 生成随机用户名
		generateRandomUserName() {
			const randomNum1 = Math.floor(Math.random() * 10);
			const randomNum2 = Math.floor(Math.random() * 10);
			return `${randomNum1}***${randomNum2}`;
		},
		
		// 根据金额获取提示类型
		getTipTypeByAmount(amount) {
			if (amount >= 1000) {
				return 'vip';
			} else if (amount >= 500) {
				return 'premium';
			} else if (amount >= 100) {
				return 'gold';
			} else {
				return 'normal';
			}
		},
		
		// 获取总和类型的样式类
		getSumTypeClass(sumType) {
			switch(sumType) {
				case '大':
					return 'sum-type-big';
				case '小':
					return 'sum-type-small';
				case '和':
					return 'sum-type-middle';
				default:
					return '';
			}
		},
		
		// 停止奖池金额更新
		stopPrizePoolUpdate() {
			if (this.prizePoolTimer) {
				clearInterval(this.prizePoolTimer);
				this.prizePoolTimer = null;
			}
		},

		// 启动奖池金额定时更新
		startPrizePoolUpdate() {
			// 先清除现有定时器
			this.stopPrizePoolUpdate();
			
			// 立即获取一次奖池数据
			this.fetchPrizePoolFromPeriod();
			
			// 设置定时器，每5秒获取一次（降低频率，避免过于频繁的请求）
			this.prizePoolTimer = setInterval(() => {
				this.fetchPrizePoolFromPeriod();
			}, 1333);
		},

		// 使用getBonusPool接口获取奖池数据
		async fetchPrizePoolFromPeriod() {
			try {
				// 确保有期号信息
				if (!this.designatedTime) {
					return;
				}
				
				const response = await getBonusPool(this.designatedTime, this.gameType);
				
				if (response.code === 1 && response.data) {
					const data = response.data;
					
					// 保存上一次的奖池金额用于动画
					this.previousPrizePool = this.prizePoolAmount;
					
					// 使用接口返回的总奖池金额
					if (data.total_bonus_pool !== undefined) {
						// 解析奖池金额（去除逗号分隔符）
						this.prizePoolAmount = parseFloat(data.total_bonus_pool.toString().replace(/,/g, '')) || 0;
					}
				}
			} catch (error) {
				console.error('获取奖池数据失败:', error);
			}
		},
		
		// 快捷设置金额
		quickBet(amount) {
			const minAmount = this.currentBetLimits.min || 1;
			const maxAmount = this.currentBetLimits.max;
			// 注数永远是一注，金额变动不影响注数
			this.data.beilv = 1;
			
			if (amount < minAmount) {
				// 自动设置为最低金额
				this.customAmount = minAmount.toString();
				this.data.money = minAmount;
				uni.showToast({
					title: `最少下注${minAmount}元`,
					icon: 'none',
					duration: 2000
				});
				return;
			}
			
			if (maxAmount > 0 && amount > maxAmount) {
				// 自动设置为最高金额
				this.customAmount = maxAmount.toString();
				this.data.money = maxAmount;
				uni.showToast({
					title: `最多下注${maxAmount}元`,
					icon: 'none',
					duration: 2000
				});
				return;
			}
			
			// 金额在合理范围内
			this.data.money = amount;
			this.customAmount = amount.toString();
			// 计算中奖金额
			this.calculateWinningAmount();
		},
		
		// 打开快捷金额设置弹窗
		openQuickSettingPopup() {
			// 复制当前快捷金额到临时数组
			this.tempQuickAmounts = [...this.quickAmounts];
			this.$refs.quickSettingPopup.open();
		},
		
		// 关闭快捷金额设置弹窗
		closeQuickSettingPopup() {
			this.$refs.quickSettingPopup.close();
		},
		
		// 保存快捷金额设置
		saveQuickSettings() {
			const minAmount = this.currentBetLimits.min || 1;
			const maxAmount = this.currentBetLimits.max;
			// 验证输入
			for (let i = 0; i < this.tempQuickAmounts.length; i++) {
				const amount = parseInt(this.tempQuickAmounts[i]);
				if (isNaN(amount) || amount < minAmount) {
					uni.showToast({
						title: `按钮${i + 1}金额不能少于${minAmount}元`,
						icon: 'none',
						duration: 2000
					});
					return;
				}
				if (maxAmount > 0 && amount > maxAmount) {
					uni.showToast({
						title: `按钮${i + 1}金额不能超过${maxAmount}元`,
						icon: 'none',
						duration: 2000
					});
					return;
				}
				this.tempQuickAmounts[i] = amount;
			}
			
			// 保存到本地存储
			this.quickAmounts = [...this.tempQuickAmounts];
			uni.setStorageSync(`${this.gameType}_quick_amounts`, this.quickAmounts);
			
			uni.showToast({
				title: '保存成功',
				icon: 'success',
				duration: 1500
			});
			
			this.closeQuickSettingPopup();
		},
		
		// 加载快捷金额设置
		loadQuickSettings() {
			try {
				const savedAmounts = uni.getStorageSync(`${this.gameType}_quick_amounts`);
				if (savedAmounts && Array.isArray(savedAmounts) && savedAmounts.length === 5) {
					this.quickAmounts = savedAmounts;
					this.tempQuickAmounts = [...savedAmounts];
				}
			} catch (error) {
	
			}
		},
		
		// 获取当前期号信息
		async loadCurrentPeriod() {
			try {
				this.loading.period = true;
				const response = await getCurrentPeriod(this.gameType);
				
				if (response.code === 1 && response.data) {
					const data = response.data;
					
					// 更新期号信息
					this.designatedTime = data.period_number;
					
					// 更新当期开奖数据
					if (data.last_open_period_no && data.last_open_code) {
						const numbers = data.last_open_code.split(',');
						const sum = numbers.reduce((total, num) => total + parseInt(num), 0);
						let sumType = '和';
						if (sum > 18) {
							sumType = '大';
						} else if (sum < 9) {
							sumType = '小';
						}
						
						this.currentDraw = {
							period: data.last_open_period_no,
							numbers: numbers,
							sumType: sumType
						};
					}
					
					// 保存当前期号时间信息
					this.currentPeriodInfo = {
						closing_time: data.closing_time,
						draw_time_end: data.draw_time_end,
						next_issue_start_time: data.next_issue_start_time
					};
					
					// 更新截止时间信息（精确到秒）
					this.daelDateArr = {
						deyStr: '今天',
						daelDate: data.current_date,
						daelHours: data.closing_time // HH:MM:SS格式
					};
					
					// 启动精确倒计时
					this.startPreciseCountdown(data.closing_time, data.current_time);
					
					// 加载历史开奖数据
					this.loadHistoryData();
					
					// 加载待开奖订单
					this.loadPendingOrders();
					
	
				} else {
	
					// 使用默认倒计时
					this.initCountdown();
				}
			} catch (error) {
				console.error('获取期号信息异常:', error);
				// 使用默认倒计时
				this.initCountdown();
			} finally {
				this.loading.period = false;
			}
		},
		
		// 加载历史开奖数据
		async loadHistoryData() {
			try {
				this.loading.history = true;
				const response = await getHistoryDraw({
					lottery_code: this.gameType,
					page: 1,
					limit: 10
				});
				
				if (response.code === 1 && response.data && response.data.list) {
					this.historyDrawList = response.data.list.map(item => {
						const numbers = item.open_code ? item.open_code.split(',') : ['0', '0', '0'];
						const sum = numbers.reduce((total, num) => total + parseInt(num), 0);
						let sumType = '和';
						if (sum > 18) {
							sumType = '大';
						} else if (sum < 9) {
							sumType = '小';
						}
						
						return {
							period: item.period_no,
							numbers: numbers,
							sum: sum,
							sumType: sumType
						};
					});
	
				} else {
	
				}
			} catch (error) {
	
			} finally {
				this.loading.history = false;
			}
		},
		
		// 加载待开奖订单（只显示当期投注）
		async loadPendingOrders() {
			try {
				const response = await getBetOrders({
					lottery_code: this.gameType,
					status: 0, // 待开奖状态
					page: 1,
					limit: 10
				});
				
				console.log('获取订单响应:', response);
				console.log('当前期号:', this.designatedTime);
				
				if (response.code === 1 && response.data && response.data.data) {
					// 过滤只显示当期的投注
					const currentPeriodNo = this.designatedTime; // 使用正确的当前期号变量
					const filteredOrders = response.data.data.filter(item => {
						// 只显示当期的投注
						console.log('订单期号:', item.period_no, '当前期号:', currentPeriodNo);
						return currentPeriodNo && item.period_no === currentPeriodNo;
					});
					
					console.log('过滤后的订单:', filteredOrders);
					
					this.pendingOrders = filteredOrders.map(item => {
						let betTypeDisplay = '未知';
						
						// 解析bet_content，组合type和numbers
						if (item.bet_content) {
							try {
								const betContent = typeof item.bet_content === 'string' ? JSON.parse(item.bet_content) : item.bet_content;
								if (betContent.numbers) {
									betTypeDisplay = `${betContent.numbers}`;
								} else {
									betTypeDisplay = item.bet_content;
								}
							} catch (e) {
								betTypeDisplay = item.bet_content;
							}
						}
						
						return {
							period: item.period_no,
							betType: betTypeDisplay,
							amount: item.bet_amount,
							lottery_code: item.lottery_code,
							typename: item.typename,
							createTime: item.create_time
						};
					});
					
					console.log('最终待开奖订单:', this.pendingOrders);
	
				} else {
					console.log('获取订单失败或无数据:', response.msg);
				}
			} catch (error) {
				console.error('加载待开奖订单异常:', error);
			}
		},
		
		// 启动精确倒计时
		startPreciseCountdown(closingTime, currentTime) {
			// 清除现有定时器
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
			
			// 计算截止时间（精确到秒）
			const today = new Date();
			const [hours, minutes, seconds] = closingTime.split(':').map(Number);
			const endTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), hours, minutes, seconds || 0);
			
			// 如果截止时间已过，设置为明天的截止时间
			if (new Date() > endTime) {
				endTime.setDate(endTime.getDate() + 1);
				this.daelDateArr.deyStr = '明天';
			}
			
			this.updatePreciseCountdown(endTime);
			
			// 每秒更新倒计时
			this.countdownTimer = setInterval(() => {
				this.updatePreciseCountdown(endTime);
			}, 1000);
		},
		
		// 清理所有定时器
		clearAllTimers() {
			// 清理倒计时定时器
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
			
			// 清理奖池定时器
			if (this.prizePoolTimer) {
				clearInterval(this.prizePoolTimer);
				this.prizePoolTimer = null;
			}
			
			// 清理onShow防抖定时器
			if (this.onShowDebounceTimer) {
				clearTimeout(this.onShowDebounceTimer);
				this.onShowDebounceTimer = null;
			}
		},
		
		// 停止所有异步操作
		stopAllAsyncOperations() {
			// 标记页面为不可见状态，阻止新的异步操作
			this.pageVisible = false;
			
			// 重置加载状态
			this.loading = {
				period: false,
				history: false,
				userInfo: false
			};
			
			console.log('已停止所有异步操作');
		},
		
		// 重置页面数据
		resetPageData() {
			// 清空当前期数信息
			this.currentPeriod = null;
			this.designatedTime = '';
			
			// 清空历史开奖数据
			this.historyDrawList = [];
			
			// 清空待开奖订单
			this.pendingOrders = [];
			
			// 重置当期开奖信息
			this.currentDraw = {
				period: '',
				numbers: [],
				sumType: ''
			};
			
			// 重置倒计时
			this.countdown = '00:00:00';
			
			// 重置奖池金额
			this.prizePoolAmount = 0.00;
			this.previousPrizePool = 0.00;
			
			console.log('已重置页面数据');
		},
		

	},
	mounted() {
		// 获取用户信息
		this.loadUserInfo();
		// 加载快捷金额设置
		this.loadQuickSettings();

	},
	beforeDestroy() {
		// 清理定时器
		if (this.countdownTimer) {
			clearInterval(this.countdownTimer);
			this.countdownTimer = null;
		}
		// 清理奖池定时器
		this.stopPrizePoolUpdate();
	},
	onLoad(options) {
		// 获取页面参数
		this.gameType = options.type || 'ff3d';
		// 设置页面为可见状态
		this.pageVisible = true;
		// 标记为首次加载
		this.isFirstLoad = true;
		// 初始化页面数据
		this.init();
	},
	onShow(){
		// 设置页面为可见状态
		this.pageVisible = true;
		
		// 如果是首次加载，跳过onShow的API调用（避免与onLoad重复）
		if (this.isFirstLoad) {
			this.isFirstLoad = false;
			console.log('onShow: 首次加载，跳过API调用');
			return;
		}
		
		// 防止重复调用API - 添加防抖机制
		if (this.onShowDebounceTimer) {
			clearTimeout(this.onShowDebounceTimer);
		}
		
		this.onShowDebounceTimer = setTimeout(() => {
			// 检查是否需要重新加载数据
			const needsFullReload = !this.designatedTime || 
									!this.gameInfo.type_name || 
									this.historyDrawList.length === 0;
			
			if (this.gameType && needsFullReload) {
				// 只有当关键数据缺失时才重新加载所有数据
				console.log('onShow: 重新加载所有数据');
				this.loadCurrentPeriod();
				this.loadHistoryData();
				this.loadPendingOrders();
				this.startPrizePoolUpdate();
				this.loadUserInfo();
			} else if (this.gameType) {
				// 如果已有基础数据，只更新必要信息
				console.log('onShow: 只更新必要信息');
				
				// 始终更新用户信息（余额可能变化）
				this.loadUserInfo();
				
				// 检查是否需要更新待开奖订单（避免重复加载）
				const lastOrderTime = this.pendingOrders.length > 0 ? 
					this.pendingOrders[0].createTime : 0;
				const timeSinceLastOrder = Date.now() - lastOrderTime;
				
				// 如果没有订单或最后一个订单超过30秒，则重新加载
				if (this.pendingOrders.length === 0 || timeSinceLastOrder > 30000) {
					this.loadPendingOrders();
				}
				
				// 确保奖池更新正常运行
				if (!this.prizePoolTimer) {
					this.startPrizePoolUpdate();
				}
			}
		}, 150); // 增加防抖延迟到150ms
	},
	
	onHide() {
		// 页面隐藏时取消所有异步加载
		
		// 设置页面为不可见状态
		this.pageVisible = false;
		// 清理所有定时器
		this.clearAllTimers();
		// 停止所有异步操作
		this.stopAllAsyncOperations();
		// 清空数据，确保重新进入时重新加载
		this.resetPageData();
	},
	
	onUnload() {
		// 页面卸载时清理所有资源
		
		// 设置页面为不可见状态
		this.pageVisible = false;
		// 清理所有定时器
		this.clearAllTimers();
		// 停止所有异步操作
		this.stopAllAsyncOperations();
		// 清空数据
		this.resetPageData();
	}

}
</script>

<style scoped lang="scss">
/* 通用过渡效果 */
.transition-ease {
	transition: all 0.3s ease;
}

/* 容器 */
.page-container,
.container {
	background: linear-gradient(180deg, #1a1a1a 0%, #0d0d0d 100%);
	color: #e1e1e1;
	position: relative;
}

.statusBar {
	width: 100%;
	height: var(--status-bar-height);
	background-color: #000;
}

/* 导航 */
.navigation {
	width: 100%;
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
	position: fixed;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	z-index: 1;
	box-shadow: 0 5rpx 15rpx -8rpx rgba(0, 0, 0, 0.3);
}

/* 奖池金额显示区域 */
.prize-pool-section {
	width: 94%;
	margin:0 3% 15rpx 3%;
	padding:25rpx 0;
	background: linear-gradient(180deg, #000000 0%, #252525 100%);;
	border-bottom: 1rpx solid #333;
	border-radius:0 0 55rpx 55rpx;
}

.prize-pool-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	text-align: center;
}

.prize-pool-label {
	color: #999;
	font-size: 24rpx;
	margin-bottom: 10rpx;
	letter-spacing: 1rpx;
}

.prize-pool-amount {
	display: flex;
	align-items: baseline;
	justify-content: center;
	margin-bottom: 8rpx;
}

.prize-pool-unit {
	color: #ff6b35;
	font-size: 28rpx;
	font-weight: bold;
	margin-left: 8rpx;
}

.prize-pool-desc {
	color: #666;
	font-size: 20rpx;
	opacity: 0.8;
}

/* 自定义奖池数字显示 */
.custom-count-display {
	display: inline-flex;
	align-items: center;
}

.prize-digit {
	color: #ff6b35;
	font-size: 55rpx;
	font-weight: bold;
	letter-spacing: 3rpx;
	transition: all 0.3s ease;
}

.prize-digit.animated {
	animation: digitBounce 0.6s ease-out;
	color: #ff4500;
	transform: scale(1.2);
}

@keyframes digitBounce {
	0% {
		transform: scale(1) translateY(0);
		color: #ff6b35;
	}
	30% {
		transform: scale(1.3) translateY(-8rpx);
		color: #ff4500;
		text-shadow: 0 0 20rpx rgba(255, 69, 0, 0.8);
	}
	60% {
		transform: scale(1.2) translateY(-4rpx);
		color: #ff6b35;
	}
	100% {
		transform: scale(1) translateY(0);
		color: #ff6b35;
		text-shadow: none;
	}
}

/* 当期开奖区域 */
.current-draw-section {
	background-color: #1a1a1a;
	border-radius: 95rpx;
	margin: 16rpx;
	padding: 30rpx;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.current-draw-header {
	text-align: center;
	margin-bottom: 20rpx;
}

.current-draw-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 20rpx;
}

.current-draw-inline {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 20rpx;
	padding: 20rpx;
	margin:0 3%;
	background: #252525;
	border-radius: 45rpx;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.3);
}

.draw-balls {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 15rpx;
}

.draw-result {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10rpx;
}

.result-label {
	color: #999;
	font-size: 24rpx;
}

.result-tag {
	padding: 3rpx 16rpx 8rpx;
	border-radius: 20rpx;
	font-size: 22rpx;
	font-weight: bold;
}

.sum-type-big {
	background: linear-gradient(135deg, #ff6b35 0%, #ff4500 100%);
	color: #fff;
}

.sum-type-small {
	background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
	color: #fff;
}

.sum-type-middle {
	background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
	color: #fff;
}

/* 历史记录 */
.history-section {
	background-color: #1a1a1a;
	border-radius: 38rpx;
	margin: 16rpx;
	overflow: hidden;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.history-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 40rpx;
	border-bottom: 1px solid #2a2a2a;
}

.history-info {
	display: flex;
	align-items: center;
}

.lottery-ball {
	width: 50rpx;
	height: 50rpx;
	background: radial-gradient(circle at 30% 30%, #ff6b35, orangered 40%, #cc3300 70%, #990000);
	border-radius: 50rpx;
	line-height: 50rpx;
	display: flex;
	margin-left: 10rpx;
	box-shadow: 
		0 4rpx 12rpx rgba(255, 69, 0, 0.4),
		0 2rpx 4rpx rgba(0, 0, 0, 0.3),
		inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3),
		inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
	position: relative;
	transform-style: preserve-3d;
}

/* 数字球样式 - 简化版 */
.lottery-ball-num-0,
.lottery-ball-num-1,
.lottery-ball-num-2,
.lottery-ball-num-3,
.lottery-ball-num-4,
.lottery-ball-num-5,
.lottery-ball-num-6,
.lottery-ball-num-7,
.lottery-ball-num-8,
.lottery-ball-num-9 {
	background: radial-gradient(circle at 30% 30%, #ff6b35, orangered 40%, #cc3300 70%, #990000);
	box-shadow: 0 4rpx 12rpx rgba(255, 69, 0, 0.4), 0 2rpx 4rpx rgba(0, 0, 0, 0.3), inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3), inset 0 -1rpx 2rpx rgba(0, 0, 0, 0.2);
}

.ball-text {
	display: block;
	margin: auto;
	color: #fff;
	font-weight: bold;
	font-size: 24rpx;
}

.history-list {
	padding: 20rpx 0;
}

.history-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 40rpx;
	border-bottom: 1px solid #2a2a2a;
}

.history-item:last-child {
	border-bottom: none;
}

.history-period {
	color: #999;
	font-size: 24rpx;
	width: 120rpx;
}

.history-balls {
	display: flex;
	align-items: center;
}

.history-ball {
	width: 40rpx;
	height: 40rpx;
	background: radial-gradient(circle at 30% 30%, #999, #666 40%, #444 70%, #222);
	border-radius: 50rpx;
	line-height: 40rpx;
	display: flex;
	margin-left: 8rpx;
	box-shadow: 
		0 3rpx 8rpx rgba(0, 0, 0, 0.4),
		0 1rpx 3rpx rgba(0, 0, 0, 0.3),
		inset 0 1rpx 1rpx rgba(255, 255, 255, 0.2),
		inset 0 -1rpx 1rpx rgba(0, 0, 0, 0.3);
	position: relative;
	transform-style: preserve-3d;
}

.history-ball-text {
	display: block;
	margin: auto;
	color: #fff;
	font-size: 20rpx;
}

/* 主要内容 */
.main-content {
	padding: 0 16rpx;
}

/* 分段控制器 */
.segment-control {
	display: flex;
	justify-content: center;
	background-color: #1a1a1a;
	border-radius: 38rpx;
	margin: 16rpx 0;
	padding: 8rpx;
	overflow-x: auto;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.segment-item {
	flex: 1;
	min-width: 120rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 16rpx;
	transition: all 0.3s ease;
}

.segment-item-active {
	flex: 1;
	min-width: 120rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 16rpx;
	background: linear-gradient(135deg, orangered 0%, #ff4500 100%);
	box-shadow: 0 2rpx 8rpx rgba(255, 69, 0, 0.3);
	transition: all 0.3s ease;
}

.segment-text {
	color: #999;
	font-size: 26rpx;
	transition: color 0.3s ease;
}

.segment-text-active {
	color: #fff;
	font-size: 26rpx;
	font-weight: bold;
	transition: color 0.3s ease;
}



/* 玩法内容 */
.play-content {
	background-color: #1a1a1a;
	border-radius: 38rpx;
	margin: 16rpx 0;
	padding: 30rpx;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.play-title {
	color: #7b7b7b;
	font-size: 25rpx;
	font-weight: 450;
	margin-bottom: 30rpx;
	display: block;
}
.play-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 20rpx;
}

.play-actions {
	display: flex;
	align-items: center;
	gap: 15rpx;
}

.play-desc {
	color: #999;
	font-size: 24rpx;
	margin-bottom: 30rpx;
	display: block;
}



/* 提交栏 */
.submit {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: #252525;
	border-top: 1px solid #333;
	padding: 20rpx;
	box-shadow: 0 -5rpx 15rpx -8rpx rgba(0, 0, 0, 0.3);
}

.submit-top {
	text-align: center;
	color: #999;
	font-size: 24rpx;
	margin-bottom: 15rpx;
}

.submit-top-content {
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}

/* 投注金额区域 */
.bet-amount-section {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 18rpx;
	flex-wrap: wrap;
	font-size: 28rpx;
	color: #a5a0ff ;
	font-weight: 500;
}

.custom-amount-input {
	width: 140rpx;
	height: 60rpx;
	padding: 0 18rpx;
	background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
	border-radius: 12rpx;
	font-size: 26rpx;
	text-align: center;
	transition: all 0.3s ease;
	box-shadow: inset 0 2rpx 4rpx rgba(0, 0, 0, 0.2);
}

.custom-amount-input:focus {
	border-color: #837dff;
	outline: none;
	box-shadow: 0 0 0 3rpx rgba(79, 70, 229, 0.2), inset 0 2rpx 4rpx rgba(0, 0, 0, 0.2);
}

.custom-amount-input::placeholder {
	color: #adb5bd;
	font-size: 24rpx;
}

.bet-amount-text {
	color: #999;
	font-size: 24rpx;
}

.bet-amount-value {
	color: orangered;
	font-size: 28rpx;
	font-weight: bold;
}

.history-icon {
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	box-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.3);
}

/* 期号信息区域 */
.period-info {
	display: flex;
	justify-content: center;
	align-items: center;
	margin-bottom: 20rpx;
}

.period-text {
	color: #f8f9fa;
	font-size: 25rpx;
	text-align: center;
	line-height: 1.4;
}

.period-number {
	color: #9d98ff;
	font-weight: 700;
	font-size: 25rpx;
}

.deadline-time {
	color: #dc3545;
	font-weight: 700;
	font-size: 25rpx;
}

/* 快捷金额分段器区域 */
.quick-amount-section {
	display: flex;
	align-items: center;	
	justify-content: center;
	gap: 15rpx;
	width: 94%;
	margin-left:3%;
	flex: 1;
}

.setting-button,
.quick-setting-btn {
	width: 55rpx;
	height: 55rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
	border: 1rpx solid #555;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	box-shadow: 0 3rpx 8rpx rgba(0, 0, 0, 0.3);
}

.setting-button:active,
.quick-setting-btn:active {
	transform: scale(0.95);
	background: linear-gradient(135deg, orangered 0%, #ff4500 100%);
	border-color: orangered;
	box-shadow: 0 3rpx 12rpx rgba(255, 69, 0, 0.4);
}

/* 快捷金额设置弹窗 */
.quick-setting-popup {
	width: 650rpx;
	max-width: 90vw;
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
	border-radius: 25rpx;
	overflow: hidden;
	box-shadow: 0 15rpx 40rpx rgba(0, 0, 0, 0.6);
	border: 1rpx solid #333;
}

.popup-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 40rpx;
	border-bottom: 1rpx solid #333;
	background: linear-gradient(135deg, #2a2a2a 0%, #333 100%);
}

.popup-title {
	color: #e1e1e1;
	font-size: 32rpx;
	font-weight: bold;
}

.popup-close {
	padding: 10rpx;
	border-radius: 50%;
	transition: background-color 0.3s ease;
}

.popup-close:active {
	background-color: rgba(255, 255, 255, 0.1);
}

.quick-setting-content {
	padding: 50rpx 40rpx;
}

.setting-item {
	display: flex;
	align-items: center;
	margin-bottom: 35rpx;
	gap: 25rpx;
	padding: 20rpx;
	background: rgba(255, 255, 255, 0.03);
	border-radius: 15rpx;
	border: 1rpx solid #333;
}

.setting-item:last-child {
	margin-bottom: 0;
}

.setting-label {
	color: #e1e1e1;
	font-size: 28rpx;
	min-width: 130rpx;
	flex-shrink: 0;
	font-weight: 500;
}

.setting-input {
	flex: 1;
}

.popup-footer {
	display: flex;
	padding: 30rpx 40rpx;
	gap: 20rpx;
	border-top: 1rpx solid #333;
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
}

.popup-btn {
	flex: 1;
	height: 80rpx;
	border-radius: 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
}

.popup-btn-cancel {
	background: linear-gradient(135deg, #333 0%, #444 100%);
	border: 1rpx solid #555;
}

.popup-btn-cancel:active {
	background: linear-gradient(135deg, #444 0%, #555 100%);
	transform: scale(0.98);
}

.popup-btn-confirm {
	background: linear-gradient(135deg, orangered 0%, #ff4500 100%);
	box-shadow: 0 2rpx 8rpx rgba(255, 69, 0, 0.3);
}

.popup-btn-confirm:active {
	background: linear-gradient(135deg, #ff4500 0%, orangered 100%);
	transform: scale(0.98);
}

.popup-btn-text {
	color: #e1e1e1;
	font-size: 28rpx;
	font-weight: 500;
}



.submit-bottom {
	display: flex;
	align-items: center;
	justify-content: space-between;
	color: #e1e1e1;
}

.bottom-button {
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 40rpx;
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	box-shadow: 0 6rpx 20rpx rgba(79, 70, 229, 0.4);
	transition: all 0.3s ease;
	overflow: hidden;
}

.bottom-button:active:not(.disabled) {
	transform: scale(0.98);
	box-shadow: 0 3rpx 15rpx rgba(79, 70, 229, 0.3);
}

.bottom-button.disabled {
	background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
	box-shadow: 0 2rpx 8rpx rgba(108, 117, 125, 0.2);
	cursor: not-allowed;
	opacity: 0.7;
}

.bottom-button02 {
	color: #fff;
	font-weight: 600;
	text-align: center;
	font-size: 32rpx;
	transition: color 0.3s ease;
}

.bottom-button.disabled .bottom-button02 {
	color: #dee2e6;
}



/* 奖金信息样式 */
.bonus-info {
	width:80%;
	margin: 20rpx auto;
	padding: 15rpx 20rpx;
	background-color: #2a2a2a;
	border-radius: 55rpx;
	border: 1rpx solid #333;
	text-align: center;
}

.bonus-text {
	color: orangered;
	font-size: 24rpx;
	font-weight: bold;
}

/* 奖金提示框样式 */
.prize-tooltip {
	position: fixed;
	z-index: 9999;
	background-color: rgba(0, 0, 0, 0.8);
	color: #fff;
	padding: 10rpx 20rpx;
	border-radius: 10rpx;
	font-size: 24rpx;
	white-space: nowrap;
	pointer-events: none;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.3);
}

.prize-tooltip::after {
	content: '';
	position: absolute;
	top: 100%;
	left: 50%;
	transform: translateX(-50%);
	border: 8rpx solid transparent;
	border-top-color: rgba(0, 0, 0, 0.8);
}

/* 历史记录弹窗样式 */
.history-popup {
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
	overflow: hidden;
	box-shadow: 0 15rpx 40rpx rgba(0, 0, 0, 0.6);
	display: flex;
	flex-direction: column;
}

.history-popup-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 30rpx;
	border-bottom: 1rpx solid #333;
	background: linear-gradient(135deg, #2a2a2a 0%, #333 100%);
	flex-shrink: 0;
	/* #ifdef APP-PLUS */
	margin-top:80rpx;
	/* #endif */
}

.history-popup-title {
	color: #e1e1e1;
	font-size: 25rpx;
	font-weight: bold;
}

.history-close {
	width: 50rpx;
	height: 50rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: background-color 0.3s ease;
}

.history-close:active {
	background-color: rgba(255, 255, 255, 0.1);
}

.history-popup-content {
	flex: 1;
	min-height: 0;
	padding: 20rpx 0;
}

.history-popup-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 40rpx;
	border-bottom: 1rpx solid #333;
}

.history-popup-item:last-child {
	border-bottom: none;
}

.history-popup-period {
	color: #999;
	font-size: 24rpx;
	width: 188rpx;
}

.history-popup-balls {
	display: flex;
	align-items: center;
	gap: 8rpx;
	flex: 1;
	justify-content: center;
}

.history-popup-ball {
	width: 45rpx;
	height: 45rpx;
	background: linear-gradient(135deg, orangered 0%, #ff4500 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4rpx 12rpx rgba(255, 69, 0, 0.4), inset 0 2rpx 4rpx rgba(255, 255, 255, 0.2);
	position: relative;
}



.history-popup-ball::before {
	content: '';
	position: absolute;
	top: 8rpx;
	left: 12rpx;
	width: 12rpx;
	height: 8rpx;
	background: rgba(255, 255, 255, 0.3);
	border-radius: 50%;
	filter: blur(1rpx);
}

.history-popup-ball-text {
	color: #fff;
	font-size: 22rpx;
	font-weight: bold;
	text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
}

.history-popup-sum {
	display: flex;
	align-items: center;
	gap: 8rpx;
	width: 140rpx;
	justify-content: flex-end;
}

.history-sum-text {
	color: #999;
	font-size: 20rpx;
}

.history-sum-tag {
	padding: 4rpx 8rpx;
	border-radius: 8rpx;
	font-size: 20rpx;
	font-weight: bold;
	min-width: 30rpx;
	text-align: center;
	background: #333;
	color: #fff;
}

.history-type-tag {
	padding: 4rpx 8rpx;
	border-radius: 8rpx;
	font-size: 18rpx;
	font-weight: bold;
	min-width: 24rpx;
	text-align: center;
}

.history-sum-tag-text,
.history-type-tag-text {
	color: #fff;
}

/* 历史记录分页样式 */
.history-popup-pagination {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20rpx 40rpx;
	border-top: 1rpx solid #333;
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
	gap: 20rpx;
	flex-shrink: 0;
}

.pagination-btn {
	width: 60rpx;
	height: 60rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #2a2a2a 0%, #3a3a3a 100%);
	border: 1rpx solid #555;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	box-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.3);
}

.pagination-btn:active:not(.disabled) {
	transform: scale(0.95);
	background: linear-gradient(135deg, orangered 0%, #ff4500 100%);
	border-color: orangered;
	box-shadow: 0 2rpx 8rpx rgba(255, 69, 0, 0.4);
}

.pagination-btn.disabled {
	opacity: 0.3;
	cursor: not-allowed;
}

.pagination-text {
	color: #e1e1e1;
	font-size: 26rpx;
	font-weight: 500;
	min-width: 80rpx;
	text-align: center;
}

/* 简化弹窗样式 - 优化版本 */
.simple-popup {
	width: 500rpx;
	max-width: 85vw;
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
	border-radius: 20rpx;
	padding: 15rpx;
	overflow: hidden;
	box-shadow: 0 15rpx 40rpx rgba(0, 0, 0, 0.6);
	border: 1rpx solid #333;
}

.simple-popup-header {
	padding: 25rpx 30rpx;
	border-bottom: 1rpx solid #333;
	text-align: center;
	background: rgba(79, 70, 229, 0.05);
}

.simple-popup-title {
	color: #e1e1e1;
	font-size: 28rpx;
	font-weight: bold;
	letter-spacing: 1rpx;
}

.simple-popup-content {
	padding: 30rpx;
}

.simple-input-group {
	margin-bottom: 25rpx;
	display: flex;
	align-items: center;
	gap: 15rpx;
}

.simple-input-label {
	color: #999;
	font-size: 24rpx;
	font-weight: 500;
	min-width: 60rpx;
}

.simple-input {
	flex: 1;
	height: 70rpx;
	padding: 0 20rpx;
	background: #2a2a2a;
	border: 1rpx solid #444;
	border-radius: 10rpx;
	color: #e1e1e1;
	font-size: 26rpx;
	box-sizing: border-box;
	transition: all 0.3s ease;
}

.simple-input:focus {
	border-color: #4f46e5;
	outline: none;
	box-shadow: 0 0 0 2rpx rgba(79, 70, 229, 0.2);
}

.simple-popup-footer {
	padding: 25rpx 30rpx;
	border-top: 1rpx solid #333;
	background: rgba(255, 255, 255, 0.02);
}

.simple-btn-row {
	display: flex;
	gap: 15rpx;
}

.simple-btn {
	flex: 1;
	height: 70rpx;
	border-radius: 15rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	border: none;
	font-size: 28rpx;
	font-weight: 600;
	position: relative;
	overflow: hidden;
}



.simple-btn-cancel {
	background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
	color: #e1e1e1;
	border: 1rpx solid #6c757d;
	box-shadow: 0 4rpx 15rpx rgba(108, 117, 125, 0.3);
}

.simple-btn-cancel:active {
	transform: scale(0.98);
	box-shadow: 0 2rpx 10rpx rgba(108, 117, 125, 0.2);
}

.simple-btn-confirm {
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	color: #fff;
	box-shadow: 0 6rpx 20rpx rgba(79, 70, 229, 0.4);
}

.simple-btn-confirm:active {
	transform: scale(0.98);
	box-shadow: 0 3rpx 15rpx rgba(79, 70, 229, 0.3);
}

/* 未开奖订单列表样式 */
.pending-orders-list {
	background-color: #1a1a1a;
	border-radius: 45rpx;
	margin: 16rpx;
	overflow: hidden;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.pending-orders-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 40rpx;
	font-size:25rpx;
	border-bottom: 1px solid #2a2a2a;
}

.pending-orders-title {
	color: #e1e1e1;
	font-size: 25rpx;
	font-weight: bold;
}

.pending-orders-content {
	max-height: 500rpx;
	padding: 20rpx 0;
}

.pending-order-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 40rpx;
	border-bottom: 1px solid #2a2a2a;
	transition: all 0.3s ease;
}

.pending-order-item:last-child {
	border-bottom: none;
}

.pending-order-item:active {
	background-color: rgba(255, 255, 255, 0.03);
	transform: scale(0.98);
}

.order-info {
	display: flex;
	flex-direction: column;
	gap: 8rpx;
	flex: 1;
}

.order-header {
	display: flex;
	align-items: center;
	gap: 15rpx;
	margin-bottom: 5rpx;
}

.order-lottery-code {
	padding: 4rpx 12rpx;
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	color: #fff;
	font-size: 20rpx;
	font-weight: bold;
	border-radius: 12rpx;
	box-shadow: 0 2rpx 6rpx rgba(79, 70, 229, 0.3);
	text-transform: uppercase;
	letter-spacing: 1rpx;
}

.order-period {
	color: #e1e1e1;
	font-size: 24rpx;
	font-weight: 500;
}

.order-status {
	padding: 0rpx 12rpx 8rpx;
	background: linear-gradient(135deg, orangered 0%, #ff4500 100%);
	color: #fff;
	font-size: 20rpx;
	font-weight: bold;
	border-radius: 12rpx;
	box-shadow: 0 2rpx 6rpx rgba(255, 69, 0, 0.3);
}

.order-details {
	display: flex;
	align-items: center;
	gap: 20rpx;
}

.order-bet {
	color: #999;
	font-size: 24rpx;
}

.order-amount {
	color: orangered;
	font-size: 22rpx;
	font-weight: bold;
}

.order-time {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	min-width: 140rpx;
}

.order-time-text {
	color: #666;
	font-size: 20rpx;
}

/* 空状态样式 */
.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 50rpx 40rpx;
	min-height: 125rpx;
}

.empty-icon {
	margin-bottom: 20rpx;
	opacity: 0.6;
	transform: scale(1);
	transition: all 0.3s ease;
}

.empty-icon:hover {
	transform: scale(1.1);
	opacity: 0.8;
}

.empty-text {
	color: #999;
	font-size: 28rpx;
	font-weight: 500;
	margin-bottom: 10rpx;
	letter-spacing: 1rpx;
}

.empty-desc {
	color: #666;
	font-size: 24rpx;
	opacity: 0.8;
	text-align: center;
	line-height: 1.4;
}

.bonus-title {
	margin-bottom: 20rpx;
	text-align: center;
}

.bonus-title-text {
	color: #e1e1e1;
	font-size: 28rpx;
	font-weight: bold;
	letter-spacing: 1rpx;
}

/* 信息行样式 */
.info-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin: 20rpx 0;
	gap: 20rpx;
}

/* 投注限额提示样式 */
.bet-limits-info {
	padding: 15rpx 25rpx;
	background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(69, 160, 73, 0.1) 100%);
	border-radius: 15rpx;
	// border: 1rpx solid rgba(76, 175, 80, 0.3);
	flex: 1;
}

.limits-text {
	color: #4CAF50;
	font-size: 24rpx;
	font-weight: 500;
	letter-spacing: 1rpx;
}

/* 中奖金额信息样式 */
.bonus-info {
	padding: 15rpx 25rpx;
	background: linear-gradient(135deg, rgba(255, 165, 0, 0.1) 0%, rgba(255, 140, 0, 0.1) 100%);
	border-radius: 15rpx;
	// border: 1rpx solid rgba(255, 165, 0, 0.3);
	flex: 1;
}

.bonus-text {
	color: #FFA500;
	font-size: 24rpx;
	font-weight: 500;
	letter-spacing: 1rpx;
}



/* 投注确认弹窗样式 */
.bet-confirm-popup {
	width: 600rpx;
	background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
	border-radius: 25rpx;
	padding: 0;
	overflow: hidden;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.6);
	border: 1rpx solid #333;
	position: relative;
}

.bet-confirm-popup::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4rpx;
	background: linear-gradient(90deg, #4f46e5 0%, #7c3aed 50%, #f59e0b 100%);
}

.bet-confirm-header {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40rpx 30rpx 30rpx;
	background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
	border-bottom: 1rpx solid #333;
}

.confirm-icon {
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 20rpx;
	box-shadow: 0 8rpx 25rpx rgba(79, 70, 229, 0.4);
}

.confirm-icon-text {
	font-size: 36rpx;
	filter: drop-shadow(0 2rpx 4rpx rgba(0, 0, 0, 0.3));
}

.bet-confirm-title {
	color: #e1e1e1;
	font-size: 32rpx;
	font-weight: bold;
	letter-spacing: 2rpx;
}

.bet-confirm-content {
	padding: 30rpx;
}

.bet-detail-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx 0;
	border-bottom: 1rpx solid rgba(255, 255, 255, 0.05);
}

.bet-detail-row:last-child {
	border-bottom: none;
	padding-bottom: 0;
}

.detail-label {
	color: #999;
	font-size: 26rpx;
	font-weight: 500;
}

.detail-value {
	color: #e1e1e1;
	font-size: 26rpx;
	font-weight: 600;
}

.detail-value.highlight {
	color: #817aff;
	font-weight: bold;
	padding: 8rpx 16rpx;
	background: rgba(79, 70, 229, 0.1);
	border-radius: 15rpx;
	border: 1rpx solid rgba(79, 70, 229, 0.3);
}

.detail-value.amount {
	color: #f59e0b;
	font-weight: bold;
	font-size: 28rpx;
}

.detail-value.odds {
	color: #10b981;
	font-weight: bold;
}

.detail-value.bonus {
	color: #ef4444;
	font-weight: bold;
	font-size: 28rpx;
	text-shadow: 0 2rpx 4rpx rgba(239, 68, 68, 0.3);
}

.bet-confirm-footer {
	display: flex;
	gap: 20rpx;
	padding: 30rpx;
	background: rgba(255, 255, 255, 0.02);
	border-top: 1rpx solid #333;
}

.confirm-btn {
	flex: 1;
	height: 80rpx;
	border-radius: 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	position: relative;
	overflow: hidden;
}



.cancel-btn {
	background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
	border: 1rpx solid #6c757d;
	box-shadow: 0 4rpx 15rpx rgba(108, 117, 125, 0.3);
}

.cancel-btn:active,
.submit-btn:active {
	transform: scale(0.98);
}

.cancel-btn:active {
	box-shadow: 0 2rpx 10rpx rgba(108, 117, 125, 0.2);
}

.submit-btn {
	background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
	border: 1rpx solid #4f46e5;
	box-shadow: 0 6rpx 20rpx rgba(79, 70, 229, 0.4);
}

.submit-btn:active {
	box-shadow: 0 3rpx 15rpx rgba(79, 70, 229, 0.3);
}

.btn-text {
	color: #fff;
	font-size: 28rpx;
	font-weight: bold;
	letter-spacing: 1rpx;
	z-index: 1;
	position: relative;
}

/* 赔率选择样式 */
.bonus-selection {
	margin-top: 30rpx;
	padding: 20rpx;
}

.bonus-header {
	margin-bottom: 20rpx;
}

.bonus-title {
	color: #e1e1e1;
	font-size: 28rpx;
	font-weight: 600;
	letter-spacing: 1rpx;
}

/* 投注确认按钮优化样式 */
.bet-confirm-button {
	height: 75rpx;
	width: 200rpx;
	margin-right: 10rpx;
	font-size: 30rpx;
}
</style>