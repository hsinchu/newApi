<template>
	<view class="container">
		<!-- 会员基本信息 -->
		<view class="member-card">
			<view class="member-header">
				<uv-avatar src="/static/images/avatar.jpg" size="60" shape="circle" bgColor="#c5c5c5"></uv-avatar>
				<view class="member-basic">
					<view class="member-name-row">
						<text class="member-name">{{memberInfo.nickname || memberInfo.username}}</text>
						<uv-icon :name="memberInfo.agent_favorite === 1 ? 'star-fill' : 'star'" :color="memberInfo.agent_favorite === 1 ? '#ff934a' : '#a0a0a0'" size="20" @tap="toggleFavorite"></uv-icon>
					</view>
					<text class="member-id">ID: {{memberInfo.id}}</text>
					<view class="member-status">
						<text class="status-text" :class="memberInfo.status === 1 ? 'active' : 'inactive'">{{memberInfo.status === 1 ? '正常' : '禁用'}}</text>
						<text class="last-login" v-if="memberInfo.last_bet_time">最近投注: {{formatTime(memberInfo.last_bet_time)}}</text>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 资金信息 -->
		<view class="info-section content-start">
			<view class="section-title">
				<uv-icon name="list" color="#ff934a" size="18"></uv-icon>
				<text class="title-text">资金信息</text>
			</view>
			<view class="info-grid">
				<view class="info-item">
					<text class="info-label">账户余额</text>
					<text class="info-value balance">¥{{memberInfo.money}}</text>
				</view>
				<view class="info-item">
					<text class="info-label">不可提现金额</text>
					<text class="info-value frozen">¥{{memberInfo.unwith_money}}</text>
				</view>
				<view class="info-item">
					<text class="info-label">总投注</text>
					<text class="info-value">¥{{memberInfo.total_bet_amount}}</text>
				</view>
				<view class="info-item">
					<text class="info-label">总中奖</text>
					<text class="info-value">¥{{memberInfo.total_prize_amount}}</text>
				</view>
			</view>
		</view>
		
		<!-- 本月统计 -->
		<view class="info-section">
			<view class="section-title">
				<uv-icon name="calendar" color="#ff934a" size="18"></uv-icon>
				<text class="title-text">本月统计</text>
			</view>
			<view class="info-grid">
				<view class="info-item">
					<text class="info-label">本月投注</text>
					<text class="info-value">{{memberInfo.bet_count}}次/¥{{memberInfo.month_bet_amount}}</text>
				</view>
				<view class="info-item">
					<text class="info-label">本月中奖</text>
					<text class="info-value">¥{{memberInfo.month_prize_amount}}</text>
				</view>
				<view class="info-item">
					<text class="info-label">投注返佣比例</text>
					<text class="info-value rebate">{{displayRebateRate}}%</text>
					<text class="default-tip" v-if="memberInfo.rebate_rate == -1">(代理默认)</text>
				</view>
				<view class="info-item">
					<text class="info-label">不中奖返佣比例</text>
					<text class="info-value rebate">{{displayNowinRate}}%</text>
					<text class="default-tip" v-if="memberInfo.nowin_rate == -1">(代理默认)</text>
				</view>
			</view>
		</view>
		
		<!-- 操作按钮 -->
		<view class="action-section">
			<view class="action-buttons">
				<view class="action-btn" @tap="viewFundRecords">
					<uv-icon name="file-text" size="20"></uv-icon>
					<text class="btn-text">资金记录</text>
				</view>
				<view class="action-btn" @tap="setRebateRate">
					<uv-icon name="setting" size="20"></uv-icon>
					<text class="btn-text">投注返佣</text>
				</view>
				<view class="action-btn" @tap="setNowinRate">
					<uv-icon name="setting" size="20"></uv-icon>
					<text class="btn-text">不中奖返佣</text>
				</view>
			</view>
		</view>
		
		<!-- 操作按钮 -->
		<view class="action-section">
			<view class="action-buttons">
				<view class="action-btn add-money" @tap="showAddMoneyModal">
					<uv-icon name="plus" color="#fff" size="20"></uv-icon>
					<text class="btn-text">给该会员加款</text>
				</view>
				<view class="action-btn reduce-money" @tap="showReduceMoneyModal">
					<uv-icon name="minus" color="#fff" size="20"></uv-icon>
					<text class="btn-text">给该会员减款</text>
				</view>
			</view>
		</view>
		
		<!-- 加载状态 -->
		<view class="loading-state" v-if="loading">
			<uv-loading-icon mode="circle" color="#ff934a"></uv-loading-icon>
			<text class="loading-text">加载中...</text>
		</view>
		
		<!-- 返佣比例设置弹窗 -->
		<uv-popup ref="rebatePopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">设置投注返佣比例</view>
				<view class="popup-body">
					<view class="rebate-display">
						<text class="rebate-label">投注返佣比例</text>
						<text class="rebate-value">{{editData.rebate_rate}}%</text>
					</view>
					<uv-slider 
						v-model="editData.rebate_rate" 
						:min="0"
						:max="Number(memberInfo.agent_rebate_rate) || 50"
						:step="0.1"
						activeColor="#ff934a"
						inactiveColor="#333"
						blockColor="#ffffff"
						:blockSize="20"
						:showValue="false"
					></uv-slider>
				</view>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="saveRebateRate" type="primary" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 未中奖返佣比例设置弹窗 -->
		<uv-popup ref="nowinPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">设置未中奖返佣比例</view>
				<view class="popup-body">
					<view class="rebate-display">
						<text class="rebate-label">未中奖返佣比例</text>
						<text class="rebate-value">{{editData.nowin_rate}}%</text>
					</view>
					<uv-slider 
						v-model="editData.nowin_rate" 
						:min="0"
						:max="Number(memberInfo.agent_nowin_rate) || 50"
						:step="0.1"
						activeColor="#ff934a"
						inactiveColor="#333"
						blockColor="#ffffff"
						:blockSize="20"
						:showValue="false"
					></uv-slider>
				</view>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="saveNowinRate" type="primary" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 加款弹窗 -->
		<uv-popup 
			ref="addMoneyPopup" 
			mode="bottom" 
			border-radius="20"
			:custom-style="{ backgroundColor: '#fff' }"
			@close="closeMoneyPopup"
		>
			<view class="popup-container">
				<view class="popup-header">
					<text class="popup-title">给用户加款</text>
					<view class="close-btn" @tap="closeMoneyPopup">
						<uv-icon name="close" size="20" color="#666"></uv-icon>
					</view>
				</view>
				
				<view class="form-content">					
					<!-- 余额信息 -->
					<view class="balance-info">
						<view class="balance-item">
							<text class="balance-label">您的可操作余额：</text>
							<text class="balance-value">¥{{ agentBalance || '0.00' }}</text>
						</view>
						<view class="balance-item">
							<text class="balance-label">会员可操作余额：</text>
							<text class="balance-value text-red">¥{{ memberAvailableBalance || '0.00' }}</text>
						</view>
					</view>

					<view class="form-item">
						<text class="item-label">加款金额</text>
						<view class="input-wrapper">
							<text class="currency-symbol">¥</text>
							<input 
								v-model="moneyForm.amount" 
								class="form-input" 
								type="digit"
								placeholder="请输入加款金额"
								placeholder-style="color: #666;"
							/>
						</view>
					</view>
					
					<view class="form-item">
						<text class="item-label">备注说明</text>
						<view class="textarea-wrapper">
							<textarea 
								v-model="moneyForm.remark" 
								class="form-textarea" 
								placeholder="请输入备注说明"
								placeholder-style="color: #666;"
								maxlength="200"
							></textarea>
						</view>
					</view>
				</view>
				
				<!-- 操作按钮 -->
				<view class="popup-actions">
					<uv-button 
						type="info" 
						@click="closeMoneyPopup"
						class="action-btn cancel-btn"
					>
						取消
					</uv-button>
					<uv-button 
						type="primary" 
						@click="confirmAddMoney"
						class="action-btn save-btn"
						:disabled="!canSaveMoney"
					>
						确定加款
					</uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 减款弹窗 -->
		<uv-popup 
			ref="reduceMoneyPopup" 
			mode="bottom" 
			border-radius="20"
			:custom-style="{ backgroundColor: '#fff' }"
			@close="closeMoneyPopup"
		>
			<view class="popup-container">
				<view class="popup-header">
					<text class="popup-title">给用户减款</text>
					<view class="close-btn" @tap="closeMoneyPopup">
						<uv-icon name="close" size="20" color="#666"></uv-icon>
					</view>
				</view>
				
				<view class="form-content">					
					<!-- 余额信息 -->
					<view class="balance-info">
						<view class="balance-item">
							<text class="balance-label">您的可用余额：</text>
							<text class="balance-value">¥{{ agentBalance || '0.00' }}</text>
						</view>
						<view class="balance-item">
							<text class="balance-label">会员可用余额：</text>
							<text class="balance-value text-red">¥{{ memberAvailableBalance || '0.00' }}</text>
						</view>
					</view>
					
					<view class="form-item">
						<text class="item-label">减款金额</text>
						<view class="input-wrapper">
							<text class="currency-symbol">¥</text>
							<input 
								v-model="moneyForm.amount" 
								class="form-input" 
								type="digit"
								placeholder="请输入减款金额"
								placeholder-style="color: #666;"
							/>
						</view>
					</view>
					
					<view class="form-item">
						<text class="item-label">备注说明</text>
						<view class="textarea-wrapper">
							<textarea 
								v-model="moneyForm.remark" 
								class="form-textarea" 
								placeholder="请输入备注说明"
								placeholder-style="color: #666;"
								maxlength="200"
							></textarea>
						</view>
					</view>
				</view>
				
				<!-- 操作按钮 -->
				<view class="popup-actions">
					<uv-button 
						type="info" 
						@click="closeMoneyPopup"
						class="action-btn cancel-btn"
					>
						取消
					</uv-button>
					<uv-button 
						type="primary" 
						@click="confirmReduceMoney"
						class="action-btn save-btn"
						:disabled="!canSaveMoney"
					>
						确定减款
					</uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 支付密码验证弹窗 -->
		<uv-popup 
			ref="payPasswordPopup" 
			mode="center" 
			border-radius="20"
			:custom-style="{ backgroundColor: '#fff' }"
			@close="closePayPasswordPopup"
		>
			<view class="popup-container pay-password-container">
				<view class="popup-header">
					<text class="popup-title">验证支付密码</text>
					<view class="close-btn" @tap="closePayPasswordPopup">
						<uv-icon name="close" size="20" color="#666"></uv-icon>
					</view>
				</view>
				
				<view class="form-content">
					<view class="pay-password-tip">
						<uv-icon name="lock" color="#ff934a" size="24"></uv-icon>
						<text class="tip-text">请输入您的支付密码以确认操作</text>
					</view>
					
					<view class="form-item">
						<text class="item-label">支付密码</text>
						<view class="input-wrapper">
							<input 
								v-model="payPasswordForm.password" 
								class="form-input" 
								type="password"
								placeholder="请输入支付密码"
								placeholder-style="color: #666;"
								maxlength="6"
							/>
						</view>
					</view>
				</view>
				
				<!-- 操作按钮 -->
				<view class="popup-actions">
					<uv-button 
						type="info" 
						@click="closePayPasswordPopup"
						class="action-btn cancel-btn"
					>
						取消
					</uv-button>
					<uv-button 
						type="primary" 
						@click="confirmPayPassword"
						class="action-btn save-btn"
						:disabled="!payPasswordForm.password"
					>
						确认
					</uv-button>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
import authMixin from '@/mixins/auth.js';
import { getMemberDetail, toggleMemberFavorite, setMemberRebate, setUserMoney, verifyPayPassword } from '@/api/agent.js';
import { getUserInfo } from '@/api/user.js';

export default {
	mixins: [authMixin],
	data() {
			return {
				memberId: '',
				memberInfo: {},
				loading: false,
				editData: {
					rebate_rate: 0,
					nowin_rate: 0
				},
				moneyForm: {
					amount: '',
					remark: ''
				},
				moneyType: '', // 'add' 或 'reduce'
				agentBalance: '0.00',
				memberAvailableBalance: '0.00',
				payPasswordForm: {
					password: ''
				},
				showPayPasswordModal: false,
				pendingMoneyOperation: null // 存储待执行的资金操作
			}
		},
	
	computed: {
		// 是否可以保存资金操作
		canSaveMoney() {
			return this.moneyForm.amount && this.moneyForm.remark.trim() && 
				   parseFloat(this.moneyForm.amount) > 0;
		},
		// 显示的投注返佣比例
		displayRebateRate() {
			return this.memberInfo.rebate_rate || 0;
		},
		// 显示的未中奖返佣比例
		displayNowinRate() {
			return this.memberInfo.nowin_rate || 0;
		}
	},
	
	onLoad(options) {
		if (options.id) {
			this.memberId = options.id;
			this.loadMemberDetail();
		}
	},
	
	// 下拉刷新
	onPullDownRefresh() {
		// 重新加载会员详情
		this.loadMemberDetail();
		// 延迟停止下拉刷新动画
		setTimeout(() => {
			uni.stopPullDownRefresh();
		}, 1000);
	},
	
	methods: {

		// 加载会员详情
		async loadMemberDetail() {
			if (!this.memberId) return;
			
			this.loading = true;
			
			try {
				const response = await getMemberDetail(this.memberId);
				
				if (response.code === 1) {
					// 合并会员信息和统计数据
					this.memberInfo = {
						...response.data.member,
						...response.data.stats
					};
				} else {
					uni.showToast({
						title: response.msg || '加载失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('加载会员详情失败:', error);
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				this.loading = false;
			}
		},
		
		// 切换收藏状态
		async toggleFavorite() {
			try {
				const response = await toggleMemberFavorite({
					member_id: this.memberId
				});
				
				if (response.code === 1) {
					this.memberInfo.agent_favorite = response.data.agent_favorite;
					uni.showToast({
						title: response.msg,
						icon: 'none'
					});
				} else {
					uni.showToast({
						title: response.msg || '操作失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('切换收藏状态失败:', error);
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			}
		},
		
		// 查看投注记录
		viewBetRecords() {
			uni.navigateTo({
				url: `/pages/user/bet-records?id=${this.memberId}`
			});
		},
		
		// 查看资金记录
		viewFundRecords() {
			uni.navigateTo({
				url: `/pages/user/moneylog?member_id=${this.memberId}&username=${this.memberInfo.nickname}`
			});
		},
		
		// 设置返佣比例
		setRebateRate() {
			// 获取当前显示的返佣值作为初始值（-1时显示代理默认值）
			this.editData.rebate_rate = this.memberInfo.rebate_rate == -1 ? 
				(this.memberInfo.agent_rebate_rate || 0) : 
				(this.memberInfo.rebate_rate || 0);
			this.$refs.rebatePopup.open();
		},
		
		// 设置未中奖返佣比例
		setNowinRate() {
			// 获取当前显示的返佣值作为初始值（-1时显示代理默认值）
			this.editData.nowin_rate = this.memberInfo.nowin_rate == -1 ? 
				(this.memberInfo.agent_nowin_rate || 0) : 
				(this.memberInfo.nowin_rate || 0);
			this.$refs.nowinPopup.open();
		},
		
		// 关闭弹窗
		closePopup() {
			this.$refs.rebatePopup?.close();
			this.$refs.nowinPopup?.close();
		},
		
		// 保存返佣比例
		async saveRebateRate() {
			const rebateRate = Number(this.editData.rebate_rate);
			const maxRebateRate = Number(this.memberInfo.agent_rebate_rate) || 50;
			
			if (rebateRate < 0 || rebateRate > maxRebateRate) {
				uni.showToast({
					title: `返佣比例范围为0%到${maxRebateRate}%`,
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '保存中...'
				});
				
				const response = await setMemberRebate({
					member_id: this.memberId,
					rebate_rate: rebateRate
				});
				
				if (response.code === 1) {
					// 更新本地数据
					this.memberInfo.rebate_rate = response.data.rebate_rate;
					this.closePopup();
					
					uni.showToast({
						title: response.msg || '返佣比例设置成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: response.msg || '设置失败',
						icon: 'none'
					});
				}
				
			} catch (error) {
				console.error('设置返佣比例失败:', error);
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
		}
	},
	
	// 保存未中奖返佣比例
	async saveNowinRate() {
		const nowinRate = Number(this.editData.nowin_rate);
		const maxNowinRate = Number(this.memberInfo.nowin_rate) || 50;
		
		if (nowinRate < 0 || nowinRate > maxNowinRate) {
			uni.showToast({
				title: `未中奖返佣比例范围为0%到${maxNowinRate}%`,
				icon: 'none'
			});
			return;
		}
		
		try {
			uni.showLoading({
				title: '保存中...'
			});
			
			const response = await setMemberRebate({
			member_id: this.memberId,
			nowin_rate: nowinRate
		});
			
			if (response.code === 1) {
				// 更新本地数据
				this.memberInfo.nowin_rate = response.data.nowin_rate;
				this.closePopup();
				
				uni.showToast({
					title: response.msg || '未中奖返佣比例设置成功',
					icon: 'success'
				});
			} else {
				uni.showToast({
					title: response.msg || '设置失败',
					icon: 'none'
				});
			}
			
		} catch (error) {
			console.error('设置未中奖返佣比例失败:', error);
			uni.showToast({
				title: '网络错误，请重试',
				icon: 'none'
			});
		} finally {
			uni.hideLoading();
		}
	},
		
		// 显示加款弹窗
		showAddMoneyModal() {
			this.moneyType = 'add';
			this.resetMoneyForm();
			this.loadBalanceInfo();
			this.$refs.addMoneyPopup.open();
		},
		
		// 显示减款弹窗
		showReduceMoneyModal() {
			this.moneyType = 'reduce';
			this.resetMoneyForm();
			this.loadBalanceInfo();
			this.$refs.reduceMoneyPopup.open();
		},
		
		// 关闭资金操作弹窗
		closeMoneyPopup() {
			this.$refs.addMoneyPopup?.close();
			this.$refs.reduceMoneyPopup?.close();
			this.resetMoneyForm();
		},
		
		// 重置资金表单
		resetMoneyForm() {
			this.moneyForm = {
				amount: '',
				remark: ''
			};
		},
		
		// 加载余额信息
		async loadBalanceInfo() {
			try {
				// 通过API获取最新的代理商余额信息
				const response = await getUserInfo();
				if (response.code === 1) {
					const userInfo = response.data;
					const agentMoney = parseFloat(userInfo.money || 0);
					const agentFrozenMoney = parseFloat(userInfo.unwith_money || 0);
					this.agentBalance = (agentMoney - agentFrozenMoney).toFixed(2);
					
					// 同时更新本地存储的用户信息
					uni.setStorageSync('userInfo', userInfo);
				} else {
					console.error('获取用户信息失败:', response.msg);
				}
			} catch (error) {
				console.error('获取用户信息异常:', error);
				// 如果API调用失败，降级使用本地存储
				const userInfo = uni.getStorageSync('userInfo');
				const agentMoney = parseFloat(userInfo?.money || 0);
				const agentFrozenMoney = parseFloat(userInfo?.unwith_money || 0);
				this.agentBalance = (agentMoney - agentFrozenMoney).toFixed(2);
			}
			
			// 更新会员可用余额（总余额减去不可提现金额）
			const memberMoney = parseFloat(this.memberInfo?.money || 0);
			const frozenMoney = parseFloat(this.memberInfo?.unwith_money || 0);
			this.memberAvailableBalance = (memberMoney - frozenMoney).toFixed(2);
		},
		
		// 确认加款
		async confirmAddMoney() {
			const amount = parseFloat(this.moneyForm.amount);
			
			if (!amount || amount <= 0) {
				uni.showToast({
					title: '请输入有效的加款金额',
					icon: 'none'
				});
				return;
			}
			
			// 检查代理商余额是否充足
			const agentAvailableBalance = parseFloat(this.agentBalance || 0);
			if (amount > agentAvailableBalance) {
				uni.showToast({
					title: `加款金额不能超过您的可操作余额(¥${agentAvailableBalance.toFixed(2)})`,
					icon: 'none'
				});
				return;
			}
			
			if (!this.moneyForm.remark.trim()) {
				uni.showToast({
					title: '请输入备注说明',
					icon: 'none'
				});
				return;
			}
			
			// 存储待执行的操作并显示支付密码验证弹窗
			this.pendingMoneyOperation = {
				type: 'add',
				amount: amount,
				remark: this.moneyForm.remark
			};
			this.showPayPasswordModal = true;
			this.$refs.payPasswordPopup.open();
		},
		
		// 执行加款操作
		async executeAddMoney() {
			const { amount, remark } = this.pendingMoneyOperation;
			
			try {
				uni.showLoading({
					title: '处理中...'
				});
				
				// 调用加款API
				const response = await setUserMoney({
					member_id: this.memberId,
					amount: amount,
					remark: remark,
					type: 'add',
					pay_password: this.payPasswordForm.password
				});
				
				uni.hideLoading();
				
				if (response.code === 1) {
					// 更新本地余额
					this.memberInfo.money = response.data.new_balance;
					// 更新代理商余额
					if (response.data.agent_balance !== undefined) {
						const userInfo = uni.getStorageSync('userInfo');
						userInfo.money = response.data.agent_balance;
						uni.setStorageSync('userInfo', userInfo);
						this.agentBalance = response.data.agent_balance;
					}
					
					this.closeMoneyPopup();
					
					uni.showToast({
						title: response.msg || '加款成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: response.msg || '加款失败',
						icon: 'none'
					});
				}
				
			} catch (error) {
				console.error('加款失败:', error);
				uni.hideLoading();
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			}
		},
		
		// 确认减款
		async confirmReduceMoney() {
			const amount = parseFloat(this.moneyForm.amount);
			
			if (!amount || amount <= 0) {
				uni.showToast({
					title: '请输入有效的减款金额',
					icon: 'none'
				});
				return;
			}
			
			const memberMoney = parseFloat(this.memberInfo.money || 0);
			const frozenMoney = parseFloat(this.memberInfo.unwith_money || 0);
			const availableBalance = memberMoney - frozenMoney;
			
			if (amount > availableBalance) {
				uni.showToast({
					title: `减款金额不能超过用户可操作余额(¥${availableBalance.toFixed(2)})`,
					icon: 'none'
				});
				return;
			}
			
			if (!this.moneyForm.remark.trim()) {
				uni.showToast({
					title: '请输入备注说明',
					icon: 'none'
				});
				return;
			}
			
			// 存储待执行的操作并显示支付密码验证弹窗
			this.pendingMoneyOperation = {
				type: 'reduce',
				amount: amount,
				remark: this.moneyForm.remark
			};
			this.showPayPasswordModal = true;
			this.$refs.payPasswordPopup.open();
		},
		
		// 执行减款操作
		async executeReduceMoney() {
			const { amount, remark } = this.pendingMoneyOperation;
			
			try {
				uni.showLoading({
					title: '处理中...'
				});
				
				// 调用减款API
				const response = await setUserMoney({
					member_id: this.memberId,
					amount: amount,
					remark: remark,
					type: 'reduce',
					pay_password: this.payPasswordForm.password
				});
				
				uni.hideLoading();
				
				if (response.code === 1) {
					// 更新本地余额
					this.memberInfo.money = response.data.new_balance;
					// 更新代理商余额
					if (response.data.agent_balance !== undefined) {
						const userInfo = uni.getStorageSync('userInfo');
						userInfo.money = response.data.agent_balance;
						uni.setStorageSync('userInfo', userInfo);
						this.agentBalance = response.data.agent_balance;
					}
					
					this.closeMoneyPopup();
					
					uni.showToast({
						title: response.msg || '减款成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: response.msg || '减款失败',
						icon: 'none'
					});
				}
				
			} catch (error) {
				console.error('减款失败:', error);
				uni.hideLoading();
				uni.showToast({
					title: '网络错误，请重试',
					icon: 'none'
				});
			}
		},
		
		// 格式化时间
		formatTime(timestamp) {
			if (!timestamp) return '';
			
			const now = Date.now() / 1000;
			const diff = now - timestamp;
			const date = new Date(timestamp * 1000);
			
			// #ifdef APP-PLUS
			// App环境下显示完整日期格式
			if (diff < 60) {
				return '刚刚';
			} else if (diff < 3600) {
				return Math.floor(diff / 60) + '分钟前';
			} else if (diff < 86400) {
				return Math.floor(diff / 3600) + '小时前';
			} else {
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const day = String(date.getDate()).padStart(2, '0');
				const hours = String(date.getHours()).padStart(2, '0');
				const minutes = String(date.getMinutes()).padStart(2, '0');
				return `${year}-${month}-${day} ${hours}:${minutes}`;
			}
			// #endif
			
			// #ifndef APP-PLUS
			// 其他环境保持原有格式
			if (diff < 60) {
				return '刚刚';
			} else if (diff < 3600) {
				return Math.floor(diff / 60) + '分钟前';
			} else if (diff < 86400) {
				return Math.floor(diff / 3600) + '小时前';
			} else if (diff < 2592000) {
				return Math.floor(diff / 86400) + '天前';
			} else {
				return `${date.getMonth() + 1}月${date.getDate()}日`;
			}
			// #endif
		},
		
		// 关闭支付密码弹窗
		closePayPasswordPopup() {
			this.$refs.payPasswordPopup?.close();
			this.payPasswordForm.password = '';
			this.showPayPasswordModal = false;
			this.pendingMoneyOperation = null;
		},
		
		// 确认支付密码
		async confirmPayPassword() {
			if (!this.payPasswordForm.password) {
				uni.showToast({
					title: '请输入支付密码',
					icon: 'none'
				});
				return;
			}
			
			// 先关闭弹窗但不清空密码
			this.$refs.payPasswordPopup?.close();
			this.showPayPasswordModal = false;
			
			// 执行对应的资金操作
			if (this.pendingMoneyOperation?.type === 'add') {
				await this.executeAddMoney();
			} else if (this.pendingMoneyOperation?.type === 'reduce') {
				await this.executeReduceMoney();
			}
			
			// 操作完成后清空密码和待执行操作
			this.payPasswordForm.password = '';
			this.pendingMoneyOperation = null;
		}
	}
}
</script>

<style lang="scss" scoped>

/* 会员卡片 */
.member-card {
	margin: 25rpx 25rpx 0;
	background-color: #fff;
	border-radius: 55rpx 55rpx 0 0;
	padding: 30rpx;
	border: 1px solid #e9ecef;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
}

.member-header {
	display: flex;
	align-items: center;
	gap: 24rpx;
}

.member-basic {
	flex: 1;
}

.member-name-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12rpx;
}

.member-name {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
}

.member-id {
	font-size: 24rpx;
	color: #666;
	margin-bottom: 12rpx;
}

.member-status {
	display: flex;
	align-items: center;
	gap: 20rpx;
}

.status-text {
	font-size: 22rpx;
	padding: 6rpx 16rpx;
	border-radius: 20rpx;
}

.status-text.active {
	background-color: rgba(82, 196, 26, 0.2);
	color: #52c41a;
}

.status-text.inactive {
	background-color: rgba(255, 77, 79, 0.2);
	color: #ff4d4f;
}

.last-login {
	font-size: 20rpx;
	color: #999;
}

/* 信息区域 */
.info-section {
		margin: 20rpx 25rpx;
		background-color: #fff;
		padding: 30rpx;
		border: 1px solid #e9ecef;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
	}

.section-title {
	display: flex;
	align-items: center;
	gap: 12rpx;
	margin-bottom: 24rpx;
}

.title-text {
	font-size: 28rpx;
	color: #333;
	font-weight: 600;
}

.info-grid {
	/* #ifdef H5 || APP-PLUS */
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 24rpx;
	/* #endif */
	/* #ifdef MP */
	display: flex;
	flex-wrap: wrap;
	gap: 24rpx;
	/* #endif */
	/* #ifndef H5 || APP-PLUS || MP */
	display: flex;
	flex-wrap: wrap;
	gap: 24rpx;
	/* #endif */
}

.info-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 20rpx;
	background-color: #f8f9fa;
	border: 1px solid #e9ecef;
	border-radius: 35rpx;
	/* #ifdef MP */
	width: calc(50% - 12rpx);
	flex-shrink: 0;
	/* #endif */
	/* #ifndef H5 || APP-PLUS || MP */
	width: calc(50% - 12rpx);
	flex-shrink: 0;
	/* #endif */
}

.info-label {
	font-size: 22rpx;
	color: #666;
	margin-bottom: 8rpx;
}

.info-value {
	font-size: 26rpx;
	color: #333;
	font-weight: 600;
}

.info-value.balance {
	color: #52c41a;
}

.info-value.frozen {
	color: #ff934a;
}

.info-value.rebate {
	color: #1890ff;
}

.default-tip {
	font-size: 12px;
	color: #999;
	margin-left: 8px;
}

.tip-text {
	font-size: 12px;
	color: #999;
	margin-top: 10px;
	text-align: center;
}

/* 操作区域 */
.action-section {
	margin: 20rpx 25rpx;
}

.action-buttons {
	display: flex;
	gap: 20rpx;
	/* #ifdef MP-WEIXIN */
	flex-wrap: wrap;
	/* #endif */
}

.action-btn {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 30rpx 20rpx;
	background-color: #fff;
	border-radius: 16rpx;
	// border: 1px solid #e9ecef;
	// box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
	/* #ifdef MP */
	min-width: 200rpx;
	/* #endif */
}

.action-btn:active {
	background-color: #f8f9fa;
	transform: scale(0.98);
}

.btn-text {
	font-size: 24rpx;
	color: #333;
	margin-top: 12rpx;
}

/* 加款按钮样式 */
.action-btn.add-money {
	background: linear-gradient(135deg, #52c41a, #389e0d);
	border: 1px solid #52c41a;
}

.action-btn.add-money .btn-text {
	color: #fff;
}

/* 减款按钮样式 */
.action-btn.reduce-money {
	background: linear-gradient(135deg, #ff4d4f, #cf1322);
	border: 1px solid #ff4d4f;
}

.action-btn.reduce-money .btn-text {
	color: #fff;
}

/* 加载状态 */
.loading-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 100rpx 40rpx;
}

.loading-text {
	font-size: 26rpx;
	color: #999;
	margin-top: 20rpx;
}

/* 弹窗样式 */
.popup-content {
	min-width: 600rpx;
	background-color: #fff;
	border-radius: 20rpx;
	padding: 40rpx;
	border: 1px solid #e9ecef;
}

.popup-title {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
	text-align: center;
	margin-bottom: 30rpx;
}

.popup-body {
	padding: 20rpx 0;
}

.rebate-display {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 30rpx;
}

.rebate-label {
	font-size: 28rpx;
	color: #999;
}

.rebate-value {
	font-size: 32rpx;
	color: #ff934a;
	font-weight: bold;
}

.popup-buttons {
	display: flex;
	gap: 20rpx;
	margin-top: 30rpx;
}

.popup-cancel-btn {
	flex: 1;
	background-color: #f5f5f5 !important;
	color: #333 !important;
	border: 1px solid #e9ecef !important;
}

.popup-confirm-btn {
	flex: 1;
}

.tip-text {
	font-size: 24rpx;
	color: #999;
	margin-top: 15rpx;
	text-align: center;
}

// 弹窗样式
.popup-container {
	padding: 40rpx;
	max-height: 80vh;
	
	.popup-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 40rpx;
		
		.popup-title {
			color: #333;
			font-size: 36rpx;
			font-weight: bold;
		}
		
		.close-btn {
			width: 60rpx;
			height: 60rpx;
			border-radius: 50%;
			background-color: #f5f5f5;
			display: flex;
			align-items: center;
			justify-content: center;
		}
	}
	
	.form-content {
		.form-item {
			margin-bottom: 40rpx;
			
			.item-label {
				color: #333;
				font-size: 28rpx;
				display: block;
				margin-bottom: 20rpx;
			}
			
			.input-wrapper {
				display: flex;
				align-items: center;
				background-color: #f5f5f5;
				border-radius: 12rpx;
				padding: 0 20rpx;
				height: 88rpx;
				
				.currency-symbol {
					color: #999;
					font-size: 28rpx;
					margin-right: 10rpx;
				}
				
				.form-input {
					flex: 1;
					color: #333;
					font-size: 28rpx;
					height: 100%;
					border: none;
					background: transparent;
				}
			}
			
			.textarea-wrapper {
				background-color: #f5f5f5;
				border-radius: 12rpx;
				padding: 20rpx;
				height:115rpx;
				
				.form-textarea {
					width: 100%;
					color: #333;
					font-size: 28rpx;
					border: none;
					background: transparent;
					resize: none;
				}
			}
		}
		
		.preview-section {
			background-color: rgba(0, 122, 255, 0.1);
			border-radius: 12rpx;
			padding: 30rpx;
			margin-top: 20rpx;
			
			.preview-title {
				color: #007AFF;
				font-size: 26rpx;
				font-weight: bold;
				display: block;
				margin-bottom: 15rpx;
			}
			
			.preview-content {
				.preview-text {
					color: #333;
					font-size: 28rpx;
					display: block;
					margin-bottom: 10rpx;
				}
				
				.preview-rate {
					color: #ff7c4d;
					font-size: 24rpx;
					display: block;
				}
			}
		}
	}
	
	.popup-actions {
		display: flex;
		gap: 20rpx;
		margin-top: 40rpx;
		
		.action-btn {
			flex: 1;
			height: 88rpx;
		}
	}
}

// 独立的余额信息样式
.balance-info {
	background-color: #f8f9fa;
	border-radius: 55rpx;
	padding: 25rpx;
	margin-bottom:25rpx;
	
	.balance-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 15rpx;
		
		&:last-child {
			margin-bottom: 0;
		}
		
		.balance-label {
			color: #333;
			font-size: 26rpx;
			font-weight: 500;
		}
		
		.balance-value {
			color: #52c41a;
			font-size: 28rpx;
			font-weight: bold;
		}
		
		.text-red {
			color: #ff4d4f;
		}
	}
}

// 支付密码弹窗样式
.pay-password-container {
	width: 600rpx;
	
	.pay-password-tip {
		display: flex;
		align-items: center;
		gap: 16rpx;
		margin-bottom: 40rpx;
		padding: 24rpx;
		background-color: rgba(255, 147, 74, 0.1);
		border-radius: 12rpx;
		border: 1px solid rgba(255, 147, 74, 0.3);
		
		.tip-text {
			color: #333;
			font-size: 26rpx;
			line-height: 1.4;
		}
	}
}
</style>