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
				:bar-bg-color="'#f8f9fa'"
			:bar-item-style="barItemStyle"
			:bar-item-active-style="barItemActiveStyle"
			:content-style="contentStyle"
			>
				<uv-vtabs-item :index="0">
					<view class="recharge-info">
						<!-- 余额显示区域 -->
						<view class="balance-section">
							<view class="balance-card">
								<view class="balance-header">
									<text class="balance-label">可提现余额</text>
									<view class="refresh-btn" @tap="refreshBalance">
										<uv-icon name="reload" size="16" color="#333" :class="{ 'rotating': refreshing }"></uv-icon>
									</view>
									<view class="moneylog-btn" @tap="goToWithdrawLog">
										<uv-icon name="list" size="16" color="#333"></uv-icon>
									</view>
								</view>
								<text class="balance-amount">¥{{ balance }}</text>
								<view class="non-withdrawable-section">
									<text class="non-withdrawable-label">不可提现金额</text>
									<text class="non-withdrawable-amount">¥{{ nonWithdrawableAmount }}</text>
								</view>
							</view>
						</view>
						<!-- 账号信息管理 -->
						<view class="content-section">
							<text class="section-title">我的账号信息</text>
							<view class="account-list">
								<!-- 空状态提示 -->
								<view v-if="filteredAccounts.length === 0 && selectedPaymentMethod" class="empty-accounts">
									<uv-icon name="info-circle" size="24" color="#666"></uv-icon>
									<text class="empty-text">暂无{{ getCurrentTypeName() }}账号，请先添加</text>
								</view>
								
								<!-- 已绑定账号 -->
								<view 
									v-for="(account, index) in filteredAccounts" 
									:key="index"
									class="account-item"
									:class="{ 'active': selectedAccount === account.id }"
									@tap="selectAccount(account)"
								>
									<view class="account-info">
										<view class="account-type">
											<uv-icon :name="getAccountIcon(account.type)" size="20" color="#007AFF"></uv-icon>
											<text class="type-name">{{ account.typeName }}</text>
											<text v-if="account.isDefault" class="default-tag">默认</text>
										</view>
										<text class="account-number">{{ account.accountNumber }}</text>
										<text class="account-name">{{ account.accountName }}</text>
										<text v-if="account.bankName" class="bank-name">{{ account.bankName }}</text>
									</view>
									<view class="account-actions">
									<view class="edit-btn" @tap.stop="editAccount(account)">
										<uv-icon name="edit-pen" size="16" color="#666"></uv-icon>
									</view>
									<view class="delete-btn" @tap.stop="deleteAccount(account)">
										<uv-icon name="trash" size="16" color="#ff4757"></uv-icon>
									</view>
								</view>
								</view>
								
								<!-- 添加新账号 -->
								<view class="add-account-item" @tap="showAddAccountPopup">
									<view class="add-icon">
										<uv-icon name="plus" size="24" color="#007AFF"></uv-icon>
									</view>
									<text class="add-text">添加新账号</text>
								</view>
							</view>
						</view>			
						<!-- 提现金额输入 -->
						<view class="content-section">
							<text class="section-title">提现金额</text>
							<view class="amount-input-wrapper">
								<text class="currency-symbol">¥</text>
								<input 
								v-model="inputAmount" 
								type="digit" 
								placeholder="请输入提现金额" 
								placeholder-style="color: #8f8f8f;"
								class="amount-input"
								@input="onAmountInput"
							/>
							</view>
							<view class="amount-tips">
								<text class="tip-text">最低提现金额: ¥{{ minWithdrawAmount }}</text>
								<text class="tip-text">最高提现金额: ¥{{ maxWithdrawAmount }}</text>
								<!-- <text class="tip-text">手续费: {{ withdrawFeeRate }}%</text> -->
							</view>
						</view>
						<!-- 提现费用预览 -->
						<view class="content-section" v-if="finalAmount > 0">
							<text class="section-title">费用预览</text>
							<view class="fee-preview">
								<view class="fee-item">
									<text class="fee-label">提现金额</text>
									<text class="fee-value">¥{{ finalAmount }}</text>
								</view>
								<view class="fee-item">
									<text class="fee-label">手续费</text>
									<text class="fee-value">¥{{ withdrawFee }}</text>
								</view>
								<view class="fee-item total">
									<text class="fee-label">实际到账</text>
									<text class="fee-value">¥{{ actualAmount }}</text>
								</view>
							</view>
						</view>
						<!-- 提交提现按钮 -->
						<view class="content-section">
							<uv-button
								type="primary"
								shape="circle"
								size="large"
								@click="submitWithdraw"
								class="submit-btn"
								:loading="submitting"
								:disabled="!canSubmit || submitting"
							>
								{{ submitting ? '处理中...' : `立即提现 ¥${finalAmount}` }}
							</uv-button>
						</view>
						<!-- 提现说明 -->
						<view class="content-section">
							<text class="section-title">提现说明</text>
							<view class="notice-content">
								<view class="notice-item">
									<text class="notice-text">• 提现申请提交后，将在1个工作日内到账</text>
								</view>
								<view class="notice-item">
									<text class="notice-text">• 请确保账号信息准确无误，错误信息可能导致提现失败</text>
								</view>
								<view class="notice-item">
									<text class="notice-text">• 提现手续费将从提现金额中扣除</text>
								</view>
								<view class="notice-item">
									<text class="notice-text">• 提现记录可点击上方提现按钮查看</text>
								</view>
							</view>
						</view>
					</view>
					
				</uv-vtabs-item>
			</uv-vtabs>
		</view>
		
		<!-- 添加账号弹窗 -->
		<uv-popup 
			ref="addAccountPopup"
			mode="bottom" 
			border-radius="20"
			:custom-style="{ backgroundColor: '#fff' }"
			@close="closeAddPopup"
		>
			<view class="popup-container">
				<view class="popup-header">
					<text class="popup-title">{{ isEditMode ? '修改' : '添加' }}提现账号</text>
					<view class="close-btn" @tap="closeAddPopup">
						<uv-icon name="close" size="20" color="#666"></uv-icon>
					</view>
				</view>
				
				<!-- 当前账号类型显示 -->
				<view class="form-section">
					<text class="form-label">账号类型</text>
					<view class="current-type">
						<uv-icon :name="getCurrentTypeIcon()" size="20" color="#007AFF"></uv-icon>
						<text class="type-name active">{{ getCurrentTypeName() }}</text>
					</view>
				</view>
				
				<!-- 支付宝表单 -->
				<view v-if="getCurrentAccountType() === 'alipay'" class="form-content">
					<view class="form-item">
						<text class="item-label">真实姓名</text>
						<input 
							v-model="formData.realName" 
							class="form-input" 
							placeholder="请输入真实姓名"
							placeholder-style="color: #666;"
						/>
					</view>
					<view class="form-item">
						<text class="item-label">支付宝账号</text>
						<input 
							v-model="formData.alipayAccount" 
							class="form-input" 
							placeholder="请输入支付宝账号/手机号"
							placeholder-style="color: #666;"
						/>
					</view>
					<!-- <view class="form-item">
						<text class="item-label">收款码</text>
						<view class="upload-container">
							<view v-if="!formData.alipayQrCode" class="upload-btn" @tap="uploadQrCode('alipay')">
								<uv-icon name="camera" size="24" color="#007AFF"></uv-icon>
								<text class="upload-text">上传支付宝收款码</text>
							</view>
							<view v-else class="uploaded-image" @tap="uploadQrCode('alipay')">
								<image :src="formData.alipayQrCode" class="qr-image"></image>
								<view class="change-btn">
									<text class="change-text">点击更换</text>
								</view>
							</view>
						</view>
					</view> -->
				</view>
				
				<!-- 微信表单 -->
				<view v-if="getCurrentAccountType() === 'wechat'" class="form-content">
					<view class="form-item">
						<text class="item-label">真实姓名</text>
						<input 
							v-model="formData.realName" 
							class="form-input" 
							placeholder="请输入真实姓名"
							placeholder-style="color: #666;"
						/>
					</view>
					<view class="form-item">
						<text class="item-label">微信号</text>
						<input 
							v-model="formData.wechatAccount" 
							class="form-input" 
							placeholder="请输入微信号"
							placeholder-style="color: #666;"
						/>
					</view>
					<!-- <view class="form-item">
						<text class="item-label">收款码</text>
						<view class="upload-container">
							<view v-if="!formData.wechatQrCode" class="upload-btn" @tap="uploadQrCode('wechat')">
								<uv-icon name="camera" size="24" color="#007AFF"></uv-icon>
								<text class="upload-text">上传微信收款码</text>
							</view>
							<view v-else class="uploaded-image" @tap="uploadQrCode('wechat')">
								<image :src="formData.wechatQrCode" class="qr-image"></image>
								<view class="change-btn">
									<text class="change-text">点击更换</text>
								</view>
							</view>
						</view>
					</view> -->
				</view>
				
				<!-- 银行卡表单 -->
				<view v-if="getCurrentAccountType() === 'bank'" class="form-content">
					<view class="form-item">
						<text class="item-label">银行名称</text>
						<input 
							v-model="formData.bankName" 
							class="form-input" 
							placeholder="请输入银行名称"
							placeholder-style="color: #666;"
						/>
					</view>
					<view class="form-item">
						<text class="item-label">持卡人姓名</text>
						<input 
							v-model="formData.cardHolderName" 
							class="form-input" 
							placeholder="请输入持卡人姓名"
							placeholder-style="color: #666;"
						/>
					</view>
					<view class="form-item">
						<text class="item-label">银行卡号</text>
						<input 
							v-model="formData.bankCardNumber" 
							class="form-input" 
							placeholder="请输入银行卡号"
							placeholder-style="color: #666;"
						/>
					</view>
				</view>
				
				<!-- 操作按钮 -->
				<view class="popup-actions">
					<uv-button 
						type="info" 
						@click="closeAddPopup"
						class="action-btn cancel-btn"
					>
						取消
					</uv-button>
					<uv-button 
						type="primary" 
						@click="isEditMode ? updateAccount() : saveAccount()"
						class="action-btn save-btn"
						:disabled="!canSaveAccount"
					>
						{{ isEditMode ? '更新' : '保存' }}
					</uv-button>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
import { getUserInfo } from '@/api/user.js';
import { getWithdrawAccountList, deleteWithdrawAccount, submitWithdrawApply, addWithdrawAccount, updateWithdrawAccount } from '@/api/charge.js';
export default {
	data() {
		return {
			// 余额相关
			balance: '0.00',
			nonWithdrawableAmount: '0.00',
			refreshing: false,
			
			// 提现方式
			paymentMethods: [
				{ id: 'alipay', name: '支付宝', icon: 'checkmark-circle' },
				// { id: 'wechat', name: '微信支付', icon: 'checkmark-circle' },
				{ id: 'bank', name: '银行卡', icon: 'checkmark-circle' }
			],
			selectedPaymentMethod: '',
			currentPaymentIndex: 0,
			
			// 绑定账号
			boundAccounts: [],
			selectedAccount: '',
			barItemStyle: {
				backgroundColor: 'transparent',
				color: '#666',
				borderRadius: '0',
				textAlign: 'center',
				padding: '20rpx 15rpx',
			},
			barItemActiveStyle: {
				backgroundColor: '#3c9cff',
				padding: '20rpx 15rpx',
				fontWeight: 'bold',
				textAlign: 'center',
				color: '#fff'
			},
			contentStyle: {
				backgroundColor: '#fff',
			},
			

			
			// 输入金额
			inputAmount: '',
			minWithdrawAmount: 50,
			maxWithdrawAmount: 10000,
			
			// 提现费率
			withdrawFeeRate: 0,
			
			// 提交状态
			submitting: false,
			
			// 弹窗相关
			isEditMode: false,
			editingAccountId: '',
			accountTypes: [
				{ id: 'alipay', name: '支付宝', icon: 'checkmark-circle' },
				{ id: 'wechat', name: '微信支付', icon: 'checkmark-circle' },
				{ id: 'bank', name: '银行卡', icon: 'checkmark-circle' }
			],
			formData: {
				// 支付宝
				alipayAccount: '',
				alipayQrCode: '',
				// 微信
				wechatAccount: '',
				wechatQrCode: '',
				phoneNumber: '',
				// 银行卡
				bankName: '',
				bankCardNumber: '',
				cardHolderName: '',
				bankBranch: '',
				// 通用
				realName: ''
			}
		}
	},
	
	computed: {
		// 最终提现金额
		finalAmount() {
			return parseFloat(this.inputAmount) || 0;
		},
		
		// 提现手续费
		withdrawFee() {
			if (this.finalAmount <= 0) return '0.00';
			return (this.finalAmount * this.withdrawFeeRate / 100).toFixed(2);
		},
		
		// 实际到账金额
		actualAmount() {
			if (this.finalAmount <= 0) return '0.00';
			return (this.finalAmount - parseFloat(this.withdrawFee)).toFixed(2);
		},
		
		// 是否可以提交
		canSubmit() {
			return this.selectedAccount && 
				   this.finalAmount >= this.minWithdrawAmount && 
				   this.finalAmount <= this.maxWithdrawAmount &&
				   this.finalAmount <= parseFloat(this.balance);
		},
		
		// 是否可以保存账号
		canSaveAccount() {
			const accountType = this.getCurrentAccountType();
			if (accountType === 'alipay') {
				return this.formData.alipayAccount && this.formData.realName;
			} else if (accountType === 'wechat') {
				return this.formData.wechatAccount && this.formData.realName;
			} else if (accountType === 'bank') {
				return this.formData.bankName && this.formData.bankCardNumber && 
					   this.formData.cardHolderName;
			}
			return false;
		},
		
		// 根据选择的支付方式过滤账号
		filteredAccounts() {
			if (!this.selectedPaymentMethod) {
				return this.boundAccounts;
			}
			return this.boundAccounts.filter(account => account.type === this.selectedPaymentMethod);
		}
	},
	
	onLoad() {
			this.loadUserBalance();
			this.loadWithdrawAccounts();
			this.initPaymentMethod();
		},
	
	methods: {
		// 返回上一页
		goBack() {
			uni.navigateBack();
		},
		
		// 跳转到提现记录页面
		goToWithdrawLog() {
			uni.navigateTo({
				url: '/pages/users/withdrawlog'
			});
		},
		
		// 加载用户余额
		async loadUserBalance() {
			try {
				const response = await getUserInfo();
				if (response.code === 1 && response.data) {
					this.nonWithdrawableAmount = parseFloat(response.data.unwith_money || 0).toFixed(2);
					this.balance = parseFloat(response.data.money-this.nonWithdrawableAmount || 0).toFixed(2);
				}
			} catch (error) {
				console.error('获取余额失败:', error);
			}
		},
		
		// 加载提现账户列表
		async loadWithdrawAccounts() {
			try {
				const response = await getWithdrawAccountList();
				if (response.code === 1 && response.data) {
					// 处理账户数据，添加类型名称
					this.boundAccounts = response.data.map(account => {
						let typeName = '';
						switch(account.type) {
							case 'alipay':
								typeName = '支付宝';
								break;
							case 'wechat':
								typeName = '微信支付';
								break;
							case 'bank':
								typeName = account.bank_name || '银行卡';
								break;
							default:
								typeName = account.type;
						}
						return {
							id: account.id,
							type: account.type,
							typeName: typeName,
							accountNumber: account.account_number,
							accountName: account.account_name,
							bankName: account.bank_name
						};
					});
				}
			} catch (error) {
				console.error('获取提现账户失败:', error);
			}
		},
		
		// 删除账户
		async deleteAccount(account) {
			uni.showModal({
				title: '确认删除',
				content: `确定要删除${account.typeName}账户 ${account.accountNumber} 吗？`,
				success: async (res) => {
					if (res.confirm) {
						try {
							const response = await deleteWithdrawAccount(account.id);
							if (response.code === 1) {
								uni.showToast({
									title: '删除成功',
									icon: 'success'
								});
								// 重新加载账户列表
								await this.loadWithdrawAccounts();
								// 如果删除的是当前选中的账户，清空选择
								if (this.selectedAccount === account.id) {
									this.selectedAccount = '';
								}
							} else {
								uni.showToast({
									title: response.msg || '删除失败',
									icon: 'none'
								});
							}
						} catch (error) {
							console.error('删除账户失败:', error);
							uni.showToast({
								title: error.msg || '删除失败，请重试',
								icon: 'none'
							});
						}
					}
				}
			});
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
		
		// 初始化支付方式
		initPaymentMethod() {
			// 默认选择第一个支付方式
			if (this.paymentMethods.length > 0) {
				this.selectPaymentMethod(this.paymentMethods[0]);
			}
		},
		
		// 选择账号
		selectAccount(account) {
			this.selectedAccount = account.id;
		},
		
		// 获取账号图标
		getAccountIcon(type) {
			const iconMap = {
				alipay: 'checkmark-circle',
				wechat: 'checkmark-circle',
				bank: 'checkmark-circle'
			};
			return iconMap[type] || 'card';
		},
		
		// 编辑账号
		editAccount(account) {
			this.isEditMode = true;
			this.editingAccountId = account.id;
			
			// 设置当前编辑的账户类型
			const typeIndex = this.paymentMethods.findIndex(method => method.id === account.type);
			if (typeIndex !== -1) {
				this.currentPaymentIndex = typeIndex;
				this.selectedPaymentMethod = account.type;
			}
			
			// 根据账号类型填充表单数据
			this.resetFormData();
			if (account.type === 'alipay') {
				// 支付宝账户，需要从后端获取完整信息
				this.formData.alipayAccount = account.accountNumber; // 后端返回的是脱敏后的账号
				this.formData.realName = account.accountName;
				this.formData.alipayQrCode = '';
			} else if (account.type === 'wechat') {
				// 微信账户
				this.formData.wechatAccount = account.accountNumber;
				this.formData.realName = account.accountName;
				this.formData.phoneNumber = '';
				this.formData.wechatQrCode = '';
			} else if (account.type === 'bank') {
				// 银行卡账户
				this.formData.bankName = account.bankName || '';
				this.formData.bankCardNumber = account.accountNumber;
				this.formData.cardHolderName = account.accountName;
				this.formData.bankBranch = '';
			}
			
			this.$refs.addAccountPopup.open();
		},
		
		// 显示添加账号弹窗
		showAddAccountPopup() {
			this.isEditMode = false;
			this.editingAccountId = '';
			this.resetFormData();
			this.$refs.addAccountPopup.open();
		},
		
		// 关闭添加账号弹窗
		closeAddPopup() {
			this.$refs.addAccountPopup.close();
			this.isEditMode = false;
			this.editingAccountId = '';
			this.resetFormData();
		},
		
		// 获取当前账号类型
		getCurrentAccountType() {
			return this.selectedPaymentMethod;
		},
		
		// 获取当前类型名称
		getCurrentTypeName() {
			const method = this.paymentMethods.find(m => m.id === this.selectedPaymentMethod);
			return method ? method.name : '';
		},
		
		// 获取当前类型图标
		getCurrentTypeIcon() {
			const method = this.paymentMethods.find(m => m.id === this.selectedPaymentMethod);
			return method ? method.icon : 'checkmark-circle';
		},
		
		// 重置表单数据
		resetFormData() {
			this.formData = {
				alipayAccount: '',
				alipayQrCode: '',
				wechatAccount: '',
				wechatQrCode: '',
				phoneNumber: '',
				bankName: '',
				bankCardNumber: '',
				cardHolderName: '',
				bankBranch: '',
				realName: ''
			};
		},
		
		// 上传收款码
		uploadQrCode(type) {
			uni.chooseImage({
				count: 1,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: (res) => {
					const tempFilePath = res.tempFilePaths[0];
					
					// 这里应该上传到服务器，现在先直接使用本地路径
					if (type === 'alipay') {
						this.formData.alipayQrCode = tempFilePath;
					} else if (type === 'wechat') {
						this.formData.wechatQrCode = tempFilePath;
					}
					
					uni.showToast({
						title: '图片上传成功',
						icon: 'success'
					});
				},
				fail: (error) => {
					console.error('选择图片失败:', error);
					uni.showToast({
						title: '选择图片失败',
						icon: 'none'
					});
				}
			});
		},
		
		// 保存账号
		async saveAccount() {
			if (!this.canSaveAccount) {
				uni.showToast({
					title: '请完善账号信息',
					icon: 'none'
				});
				return;
			}
			
			try {
				const accountType = this.getCurrentAccountType();
				// 构建账号数据
				const accountData = {
					type: accountType,
					accountName: this.formData.realName || this.formData.cardHolderName
				};
				
				// 根据类型设置不同字段
				if (accountType === 'alipay') {
					accountData.alipayAccount = this.formData.alipayAccount;
					accountData.alipayQrCode = this.formData.alipayQrCode;
				} else if (accountType === 'wechat') {
					accountData.wechatAccount = this.formData.wechatAccount;
					accountData.phoneNumber = this.formData.phoneNumber;
					accountData.wechatQrCode = this.formData.wechatQrCode;
				} else if (accountType === 'bank') {
					accountData.bankCardNumber = this.formData.bankCardNumber;
					accountData.bankName = this.formData.bankName;
					accountData.bankBranch = this.formData.bankBranch;
				}
				
				// 调用API添加账户
				const response = await addWithdrawAccount(accountData);
				
				if (response.code === 1) {
					uni.showToast({
						title: '账号添加成功',
						icon: 'success'
					});
					
					// 重新加载账户列表
					await this.loadWithdrawAccounts();
					
					this.closeAddPopup();
				} else {
					uni.showToast({
						title: response.msg || '添加失败',
						icon: 'none'
					});
				}
				
			} catch (error) {
				console.error('保存账号失败:', error);
				uni.showToast({
					title: error.msg || '保存失败，请重试',
					icon: 'none'
				});
			}
		},
		
		// 更新账号
		async updateAccount() {
			if (!this.canSaveAccount) {
				uni.showToast({
					title: '请完善账号信息',
					icon: 'none'
				});
				return;
			}
			
			try {
				const accountType = this.getCurrentAccountType();
				// 构建更新数据
				const updateData = {
					id: this.editingAccountId,
					accountName: this.formData.realName || this.formData.cardHolderName
				};
				
				// 根据类型设置不同字段
				if (accountType === 'alipay') {
					updateData.alipayAccount = this.formData.alipayAccount;
					if (this.formData.alipayQrCode) {
						updateData.alipayQrCode = this.formData.alipayQrCode;
					}
				} else if (accountType === 'wechat') {
					updateData.wechatAccount = this.formData.wechatAccount;
					if (this.formData.phoneNumber) {
						updateData.phoneNumber = this.formData.phoneNumber;
					}
					if (this.formData.wechatQrCode) {
						updateData.wechatQrCode = this.formData.wechatQrCode;
					}
				} else if (accountType === 'bank') {
					updateData.bankCardNumber = this.formData.bankCardNumber;
					updateData.bankName = this.formData.bankName;
					if (this.formData.bankBranch) {
						updateData.bankBranch = this.formData.bankBranch;
					}
				}
				
				// 调用API更新账户
				const response = await updateWithdrawAccount(updateData);
				
				if (response.code === 1) {
					uni.showToast({
						title: '账号更新成功',
						icon: 'success'
					});
					
					// 重新加载账户列表
					await this.loadWithdrawAccounts();
					
					this.closeAddPopup();
				} else {
					uni.showToast({
						title: response.msg || '更新失败',
						icon: 'none'
					});
				}
				
			} catch (error) {
				console.error('更新账号失败:', error);
				uni.showToast({
					title: error.msg || '更新失败，请重试',
					icon: 'none'
				});
			}
		},
		
		// 获取账号号码
		getAccountNumber() {
			const accountType = this.getCurrentAccountType();
			if (accountType === 'alipay') {
				return this.maskAccount(this.formData.alipayAccount);
			} else if (accountType === 'wechat') {
				return this.maskAccount(this.formData.wechatAccount);
			} else if (accountType === 'bank') {
				return this.maskBankCard(this.formData.bankCardNumber);
			}
			return '';
		},
		
		// 遮盖账号信息
		maskAccount(account) {
			if (!account) return '';
			if (account.length <= 4) return account;
			return account.substring(0, 3) + '****' + account.substring(account.length - 4);
		},
		
		// 遮盖银行卡号
		maskBankCard(cardNumber) {
			if (!cardNumber) return '';
			if (cardNumber.length <= 8) return cardNumber;
			return cardNumber.substring(0, 4) + '****' + cardNumber.substring(cardNumber.length - 4);
		},
		

		
		// 金额输入处理
		onAmountInput(e) {
			this.inputAmount = e.detail.value;
		},
		

		
		// 提交提现
		async submitWithdraw() {
			if (!this.canSubmit) {
				uni.showToast({
					title: '请完善提现信息',
					icon: 'none'
				});
				return;
			}
			
			// 确认提现
			uni.showModal({
				title: '确认提现',
				content: `确认提现 ¥${this.finalAmount}，手续费 ¥${this.withdrawFee}，实际到账 ¥${this.actualAmount} 吗？`,
				success: (res) => {
					if (res.confirm) {
						this.processWithdraw();
					}
				}
			});
		},
		
		// 处理提现
		async processWithdraw() {
			this.submitting = true;
			
			try {
				// 构建提现参数
				const withdrawData = {
					amount: this.finalAmount,
					accountId: this.selectedAccount
				};
				
				// 调用提现API
				const response = await submitWithdrawApply(withdrawData);
				
				if (response.code === 1) {
					uni.showToast({
						title: '提现申请已提交',
						icon: 'success'
					});
					
					// 刷新余额
					await this.loadUserBalance();
					
					// 重置表单
					this.resetForm();
				} else {
					uni.showToast({
						title: response.msg || '提现失败',
						icon: 'none'
					});
				}
				
			} catch (error) {
				console.error('提现失败:', error);
				uni.showToast({
					title: error.msg || '提现失败，请重试',
					icon: 'none'
				});
			} finally {
				this.submitting = false;
			}
		},
		
		// 重置表单
		resetForm() {
			this.inputAmount = '';
		},
		
		// vtabs支付方式切换
		onPaymentMethodChange(index) {
			this.currentPaymentIndex = index;
			if (this.paymentMethods[index]) {
				this.selectPaymentMethod(this.paymentMethods[index]);
			}
		},
		
		// 选择支付方式
		selectPaymentMethod(method) {
			this.selectedPaymentMethod = method.id;
			// 根据选择的支付方式过滤对应的账号
			this.filterAccountsByPaymentMethod(method.id);
			// 自动选择第一个匹配的账号
			this.autoSelectFirstAccount();
		},
		
		// 根据支付方式过滤账号
		filterAccountsByPaymentMethod(methodId) {
			// 这里可以根据需要实现账号过滤逻辑
			// 目前显示所有账号，但可以根据 methodId 进行过滤
		},
		
		// 自动选择第一个账号
		autoSelectFirstAccount() {
			if (this.boundAccounts.length > 0) {
				// 根据当前选择的支付方式找到匹配的账号
				const matchingAccount = this.boundAccounts.find(account => account.type === this.selectedPaymentMethod);
				if (matchingAccount) {
					this.selectedAccount = matchingAccount.id;
				} else {
					// 如果没有匹配的账号，清空选择
					this.selectedAccount = '';
				}
			}
		},
	}
}
</script>

<style lang="scss">
.recharge-info {
	padding:0 25rpx 25rpx 25rpx;
}

// 余额区域
.balance-section {
	
}

.balance-card {
	margin-top:15rpx;
	background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
	border: 1px solid #e9ecef;
	border-radius: 20rpx;
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
	color: #666;
}

.refresh-btn, .moneylog-btn {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	background-color: rgba(0, 0, 0, 0.05);
	transition: all 0.3s ease;

	&:active {
		background-color: rgba(0, 0, 0, 0.1);
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
	font-size: 30rpx;
	font-weight: bold;
	color: #ff6232;
}

.non-withdrawable-section {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 20rpx;
	padding-top: 20rpx;
	border-top: 1rpx solid rgba(255, 255, 255, 0.1);
}

.non-withdrawable-label {
	font-size: 26rpx;
	color: #8f8f8f;
}

.non-withdrawable-amount {
	font-size: 28rpx;
	color: #ff7b7b;
	font-weight: 500;
}

// 内容区域
.content-section {
	margin-bottom: 20rpx;

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

// 支付方式提示
.payment-method-tip {
	display: flex;
	align-items: center;
	gap: 12rpx;
	margin-bottom: 20rpx;
	padding: 16rpx 20rpx;
	background: rgba(0, 122, 255, 0.1);
	border-radius: 8rpx;
	border-left: 4rpx solid #007AFF;
}

.tip-label {
	font-size: 24rpx;
	color: #666;
}

.tip-value {
	font-size: 24rpx;
	color: #007AFF;
	font-weight: 500;
}

// 账号列表
.account-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.account-item {
	background: #ffffff;
	border: 2rpx solid rgb(70, 70, 255);
	border-radius: 12rpx;
	padding: 24rpx;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: space-between;

	&.active {
		border-color: #007AFF;
		background: rgba(0, 122, 255, 0.1);
	}
}

.account-info {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8rpx;
}

.account-type {
	display: flex;
	align-items: center;
	gap: 12rpx;
}

.type-name {
	font-size: 28rpx;
	color: #8f8f8f;
	font-weight: 500;
}

.account-number {
	font-size: 26rpx;
	color: #818181;
}

.account-name {
	font-size: 24rpx;
	color: #666;
}

.account-actions {
	display: flex;
	align-items: center;
	gap: 10rpx;
}

.edit-btn, .delete-btn {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	background-color: rgba(0, 0, 0, 0.05);
	transition: background-color 0.3s ease;

	&:active {
		background-color: rgba(0, 0, 0, 0.1);
	}
}

.delete-btn {
	background-color: rgba(255, 71, 87, 0.1);

	&:active {
		background-color: rgba(255, 71, 87, 0.2);
	}
}

.default-tag {
	font-size: 20rpx;
	color: #007AFF;
	background-color: rgba(0, 122, 255, 0.1);
	padding: 4rpx 8rpx;
	border-radius: 8rpx;
	margin-left: 10rpx;
}

.bank-name {
	font-size: 24rpx;
	color: #999;
	margin-top: 8rpx;
}

.add-account-item {
	background: #fff;
	border: 2rpx dashed #dee2e6;
	border-radius: 12rpx;
	padding: 25rpx 24rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 16rpx;
	transition: all 0.3s ease;

	&:active {
		border-color: #007AFF;
		background: rgba(0, 122, 255, 0.05);
	}
}

.add-icon {
	width: 80rpx;
	height: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	background: rgba(0, 122, 255, 0.1);
}

.add-text {
	font-size: 26rpx;
	color: #007AFF;
	font-weight: 500;
}

// 空状态
.empty-accounts {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 60rpx 20rpx;
	background: #f8f9fa;
	border: 1px solid #e9ecef;
	border-radius: 12rpx;
	margin-bottom: 16rpx;
}

.empty-text {
	font-size: 26rpx;
	color: #666;
}

/* 上传收款码样式 */
.upload-container {
	margin-top: 20rpx;
}

.upload-btn {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 200rpx;
	border: 2rpx dashed #007AFF;
	border-radius: 12rpx;
	background-color: rgba(0, 122, 255, 0.05);
}

.upload-text {
	color: #007AFF;
	font-size: 28rpx;
	margin-top: 10rpx;
}

.uploaded-image {
	position: relative;
	height: 200rpx;
	border-radius: 12rpx;
	overflow: hidden;
}

.qr-image {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.change-btn {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 60rpx;
	background: rgba(0, 0, 0, 0.6);
	display: flex;
	align-items: center;
	justify-content: center;
}

.change-text {
		color: #fff;
		font-size: 24rpx;
	}
	
	/* 当前账号类型显示样式 */
	.current-type {
		display: flex;
		align-items: center;
		padding: 20rpx;
		background-color: rgba(0, 122, 255, 0.1);
		border-radius: 12rpx;
		margin-top: 20rpx;
	}
	
	.current-type .type-name {
		margin-left: 15rpx;
		color: #007AFF;
		font-size: 30rpx;
		font-weight: 500;
	}
	
	/* 操作按钮样式 */
	.popup-actions {
		display: flex;
		gap: 20rpx;
		margin-top: 40rpx;
	}
	
	.action-btn {
		width: 50%;
		height: 88rpx;
		border-radius: 0;
		font-size: 32rpx;
		font-weight: 500;
	}
	
	.cancel-btn {
	background-color: #f8f9fa !important;
	border: 2rpx solid #e9ecef !important;
	color: #666 !important;
}
	
	.save-btn {
		background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%) !important;
		box-shadow: 0 8rpx 20rpx rgba(0, 122, 255, 0.3);
	}



// 金额输入
.amount-input-wrapper {
	display: flex;
	align-items: center;
	border-radius: 12rpx;
	padding: 0 25rpx;
	height: 100rpx;
	border: 2rpx solid #e9ecef;
	transition: border-color 0.3s ease;
	background-color: #fff;

	&:focus-within {
		border-color: #007AFF;
		background-color: #f8f9fa;
	}
}

.currency-symbol {
	font-size: 32rpx;
	color: #666;
	margin-right: 15rpx;
	font-weight: 500;
}

.amount-input {
	flex: 1;
	font-size: 32rpx;
	color: #333;
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
	color: #999;
}

// 费用预览
.fee-preview {
	background: #f8f9fa;
	border-radius: 12rpx;
	padding: 24rpx;
	border: 1rpx solid #e9ecef;
}

.fee-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 16rpx;

	&:last-child {
		margin-bottom: 0;
		padding-top: 16rpx;
		border-top: 1rpx solid #e9ecef;
	}
}

.fee-label {
	font-size: 26rpx;
	color: #666;
}

.fee-value {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;

	&.highlight {
		color: #007AFF;
		font-weight: 600;
	}

	&.fee {
		color: #FF6B6B;
	}
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
		background: #ccc !important;
		color: #999 !important;
	}
}

// 支付说明
.notice-content {
	background-color: #f8f9fa;
	border-radius: 12rpx;
	padding: 25rpx;
	border: 1rpx solid #e9ecef;
}

.notice-item {
	margin-bottom: 15rpx;

	&:last-child {
		margin-bottom: 0;
	}
}

.notice-text {
	font-size: 26rpx;
	color: #666;
	line-height: 1.6;
}

// 弹窗样式
.popup-container {
	background: #fff;
	border-radius: 20rpx;
	padding: 40rpx;
}

.popup-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 30rpx;
}

.popup-title {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
}

.close-btn {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.1);
	transition: all 0.3s ease;

	&:active {
		background: rgba(255, 255, 255, 0.2);
	}
}

.form-section {
	margin-bottom: 30rpx;
}

.form-label {
	font-size: 28rpx;
	color: #333;
	font-weight: 500;
	margin-bottom: 20rpx;
	display: block;
}

.account-types {
	display: flex;
	gap: 20rpx;
}

.type-item {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	padding: 20rpx;
	border: 2rpx solid #444;
	border-radius: 12rpx;
	background: #333;
	transition: all 0.3s ease;

	&.active {
		border-color: #007AFF;
		background: rgba(0, 122, 255, 0.1);
	}
}

.type-name {
	font-size: 24rpx;
	color: #8d8d8d;
	transition: color 0.3s ease;

	&.active {
		color: #007AFF;
		font-weight: 500;
	}
}

.form-content {
	margin-bottom: 30rpx;
}

.form-item {
	margin-bottom: 24rpx;

	&:last-child {
		margin-bottom: 0;
	}
}

.form-label {
	font-size: 26rpx;
	color: #333;
	margin-bottom: 12rpx;
	display: block;
}

.form-input {
	width: 100%;
	height: 80rpx;
	text-indent:15rpx;
	background: #fff;
	border: 2rpx solid #e9ecef;
	border-radius: 12rpx;
	font-size: 28rpx;
	color: #333;
	transition: border-color 0.3s ease;

	&:focus {
		border-color: #007AFF;
	}
}

.popup-actions {
	display: flex;
	gap: 20rpx;
	margin-top: 40rpx;
}

.cancel-btn, .save-btn {
	flex: 1;
	height: 80rpx;
}

.cancel-btn {
	background: #f8f9fa !important;
	color: #666 !important;
}
</style>