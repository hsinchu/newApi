<template>
	<view class="container">
		
		<scroll-view class="scroll-container">
			
			<!-- 基本信息 -->
			<view class="section">
				<view class="section-title">基本信息</view>
				
				<!-- 用户名显示 -->
				<view class="setting-item">
					<text class="item-label">用户名</text>
					<view class="item-content">
						<text class="item-value">{{userInfo.username}}</text>
					</view>
				</view>
				
				<!-- 代理商头像 -->
				<view class="avatar-section">
					<text class="item-label">代理商头像</text>
					<view class="avatar-container" @tap="selectAvatar">
						<uv-avatar :src="userInfo.avatar" size="60" shape="circle"></uv-avatar>
					</view>
				</view>
				
				<!-- 代理商昵称 -->
				<view class="setting-item" @tap="editNickname">
					<text class="item-label">代理商昵称</text>
					<view class="item-content">
						<text class="item-value">{{userInfo.nickname}}</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
				
				<!-- 实名认证 -->
				<view class="setting-item" @tap="editRealName">
					<text class="item-label">实名认证</text>
					<view class="item-content">
						<text class="item-value" :class="userInfo.is_verified === 1 ? 'certified' : userInfo.is_verified === 2 ? 'reviewing' : 'uncertified'">
							{{userInfo.is_verified === 1 ? '已认证' : userInfo.is_verified === 2 ? '审核中' : '未认证'}}
						</text>
						<uv-icon v-if="userInfo.is_verified == 0" name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
				
				<!-- 绑定手机 -->
				<!-- <view class="setting-item" @tap="editPhone">
					<text class="item-label">{{userInfo.mobile ? '更换手机' : '绑定手机'}}</text>
					<view class="item-content">
						<text class="item-value">{{userInfo.mobile || '未绑定'}}</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view> -->
				
				<!-- 修改密码 -->
				<view class="setting-item" @tap="editPassword">
					<text class="item-label">修改密码</text>
					<view class="item-content">
						<text class="item-value">修改登录密码</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
				
				<!-- 修改支付密码 -->
				<view class="setting-item" @tap="editPayPassword">
					<text class="item-label">{{userInfo.has_pay_password ? '修改支付密码' : '设置支付密码'}}</text>
					<view class="item-content">
						<text class="item-value">{{userInfo.has_pay_password ? '修改支付密码' : '设置支付密码'}}</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
				
				<!-- 找回支付密码 -->
				<view class="setting-item" v-if="userInfo.has_pay_password" @tap="findPayPassword">
					<text class="item-label">找回支付密码</text>
					<view class="item-content">
						<text class="item-value">忘记支付密码</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
				
				<!-- 用户默认投注返佣 -->
				<view class="setting-item" @tap="editCommission">
					<text class="item-label">用户默认投注返佣</text>
					<view class="item-content">
						<text class="item-value">{{userInfo.default_rebate_rate}}%</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
				
				<!-- 用户默认未中奖返佣 -->
				<view class="setting-item" @tap="editNowinCommission">
					<text class="item-label">用户默认未中奖返佣</text>
					<view class="item-content">
						<text class="item-value">{{userInfo.default_nowin_rate}}%</text>
						<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
					</view>
				</view>
			</view>
			

			
			<!-- 退出登录 -->
			<view class="logout-section">
				<view class="logout-btn" @tap="logout">
					<text class="logout-text">退出登录/切换账号</text>
				</view>
			</view>
			
		</scroll-view>
		
		<!-- 昵称编辑弹窗 -->
		<uv-popup ref="nicknamePopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">修改昵称</view>
		<uv-input 
					v-model="editData.nickname" 
					placeholder="请输入昵称"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
				></uv-input>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="saveNickname" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 手机号编辑弹窗 -->
		<uv-popup ref="phonePopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">修改手机号</view>
				<uv-input 
					v-model="editData.mobile" 
					placeholder="请输入手机号"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
				></uv-input>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="savePhone" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
 		

		
		<!-- 实名认证弹窗 -->
		<uv-popup ref="realNamePopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">实名认证</view>
				<uv-input 
					v-model="editData.realName" 
					placeholder="请输入真实姓名"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
					customStyle="margin-bottom: 20rpx;"
				></uv-input>
				<uv-input 
					v-model="editData.idCard" 
					placeholder="请输入身份证号码"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
					maxlength="18"
				></uv-input>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="saveRealName" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 返佣比例编辑弹窗 -->
		<uv-popup ref="commissionPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">修改返佣比例</view>
			<view style="padding: 20rpx 0;">
					<view style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20rpx;">
						<text style="color: #666; font-size: 28rpx;">返佣比例</text>
						<text style="color: #f06703; font-size: 32rpx; font-weight: bold;">{{editData.default_rebate_rate}}%</text>
					</view>
					<uv-slider 
						v-model="editData.default_rebate_rate" 
						:min="0"
						:max="userInfo.rebate_rate"
						:step="0.1"
						activeColor="#f06703"
						inactiveColor="#f06703"
						blockColor="#ffffff"
						:blockSize="20"
						:showValue="false"
					></uv-slider>
				</view>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="saveCommission" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 未中奖返佣比例编辑弹窗 -->
		<uv-popup ref="nowinCommissionPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">修改未中奖返佣比例</view>
			<view style="padding: 20rpx 0;">
					<view style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20rpx;">
						<text style="color: #666; font-size: 28rpx;">未中奖返佣比例</text>
						<text style="color: #f06703; font-size: 32rpx; font-weight: bold;">{{editData.default_nowin_rate}}%</text>
					</view>
					<uv-slider 
						v-model="editData.default_nowin_rate" 
						:min="0"
						:max="userInfo.nowin_rate"
						:step="0.1"
						activeColor="#f06703"
						inactiveColor="#f06703"
						blockColor="#ffffff"
						:blockSize="20"
						:showValue="false"
					></uv-slider>
				</view>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="saveNowinCommission" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 修改密码弹窗 -->
		<uv-popup ref="passwordPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">修改密码</view>
				<uv-input 
					v-model="editData.oldPassword" 
					type="password"
					placeholder="请输入原密码"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
					customStyle="margin-bottom: 20rpx;"
				></uv-input>
				<uv-input 
					v-model="editData.newPassword" 
					type="password"
					placeholder="请输入新密码"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
					customStyle="margin-bottom: 20rpx;"
				></uv-input>
				<uv-input 
					v-model="editData.confirmPassword" 
					type="password"
					placeholder="请确认新密码"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
				></uv-input>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="savePassword" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
		
		<!-- 修改支付密码弹窗 -->
		<uv-popup ref="payPasswordPopup" mode="center" :round="20">
			<view class="popup-content">
				<view class="popup-title">{{userInfo.has_pay_password ? '修改支付密码' : '设置支付密码'}}</view>
				<uv-input 
					v-if="userInfo.has_pay_password"
					v-model="editData.oldPayPassword" 
					type="password"
					placeholder="请输入原支付密码"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
					customStyle="margin-bottom: 20rpx;"
				></uv-input>
				<uv-input 
					v-model="editData.newPayPassword" 
					type="password"
					:placeholder="userInfo.has_pay_password ? '请输入新支付密码' : '请输入支付密码'"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
					customStyle="margin-bottom: 20rpx;"
				></uv-input>
				<uv-input 
					v-model="editData.confirmPayPassword" 
					type="password"
					:placeholder="userInfo.has_pay_password ? '请确认新支付密码' : '请确认支付密码'"
					color="#e1e1e1"
					placeholderStyle="color: #757575;"
				></uv-input>
				<view class="popup-buttons">
					<uv-button text="取消" @click="closePopup" class="popup-cancel-btn"></uv-button>
					<uv-button text="确定" @click="savePayPassword" class="popup-confirm-btn"></uv-button>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
import { getUserInfo, updateUserProfile, uploadAvatar } from '@/api/user.js';
export default {
	data() {
			return {
				userInfo: {
			username: '',
			avatar: '/static/images/avatar.png',
			nickname: '',
			certified: false,
			is_verified: 0, // 0=未实名, 1=已认证, 2=审核中
			mobile: '',
			default_rebate_rate: 0,
			default_nowin_rate: 0,
			rebate_rate: 0,
			nowin_rate: 0,
			has_pay_password: false
		},
				editData: {
					nickname: '',
					mobile: '',
					default_rebate_rate: '',
					default_nowin_rate: '',
					realName: '',
					idCard: '',
					oldPassword: '',
					newPassword: '',
					confirmPassword: '',
					oldPayPassword: '',
					newPayPassword: '',
					confirmPayPassword: ''
				},
				loading: false
			}
		},
	mounted() {
		this.loadUserInfo();
	},
	methods: {
		// 加载用户信息
		async loadUserInfo() {
			try {
				this.loading = true;
				const res = await getUserInfo();
				if (res.code === 1) {
					this.userInfo = {
				username: res.data.username || '',
				avatar: res.data.avatar || '/static/images/avatar.png',
				nickname: res.data.nickname || '',
				certified: res.data.certified || false,
				is_verified: res.data.is_verified || 0, // 0=未实名, 1=已认证, 2=审核中
				mobile: res.data.mobile || '',
				default_rebate_rate: res.data.default_rebate_rate || 0,
				default_nowin_rate: res.data.default_nowin_rate || 0,
				rebate_rate: res.data.rebate_rate || 0,
				nowin_rate: res.data.nowin_rate || 0,
				has_pay_password: res.data.has_pay_password || false
			};
				} else {
					uni.showToast({
						title: res.msg || '获取用户信息失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('获取用户信息失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				this.loading = false;
			}
		},
		

		// 选择头像
		async selectAvatar() {
			uni.chooseImage({
				count: 1,
				sizeType: ['compressed'],
				sourceType: ['album', 'camera'],
				success: async (res) => {
					try {
						uni.showLoading({
							title: '上传中...'
						});
						
						// 上传头像
						const uploadRes = await uploadAvatar(res.tempFilePaths[0]);
						if (uploadRes.code === 1) {
							this.userInfo.avatar = uploadRes.data.avatar;
							uni.showToast({
								title: '头像更新成功',
								icon: 'success'
							});
						} else {
							uni.showToast({
								title: uploadRes.msg || '头像上传失败',
								icon: 'none'
							});
						}
					} catch (error) {
						console.error('头像上传失败:', error);
						uni.showToast({
							title: error.msg || '头像上传失败',
							icon: 'none'
						});
					} finally {
						uni.hideLoading();
					}
				}
			});
		},
		
		// 编辑昵称
		editNickname() {
			this.editData.nickname = this.userInfo.nickname;
			this.$refs.nicknamePopup.open();
		},
		
		// 编辑实名认证
		editRealName() {
			if (this.userInfo.is_verified === 1) {
				uni.showToast({
					title: '您已完成实名认证',
					icon: 'none'
				});
				return;
			}
			if (this.userInfo.is_verified === 2) {
				uni.showToast({
					title: '您的实名认证正在审核中',
					icon: 'none'
				});
				return;
			}
			this.editData.realName = '';
			this.editData.idCard = '';
			this.$refs.realNamePopup.open();
		},
		
		// 编辑手机号
		editPhone() {
			this.editData.mobile = this.userInfo.mobile;
			this.$refs.phonePopup.open();
		},
		

		
		// 编辑返佣比例
		editCommission() {
			this.editData.default_rebate_rate = Number(this.userInfo.default_rebate_rate) || 0;
			this.$refs.commissionPopup.open();
		},
		
		// 编辑未中奖返佣比例
		editNowinCommission() {
			this.editData.default_nowin_rate = Number(this.userInfo.default_nowin_rate) || 0;
			this.$refs.nowinCommissionPopup.open();
		},
		
		// 编辑密码
		editPassword() {
			this.editData.oldPassword = '';
			this.editData.newPassword = '';
			this.editData.confirmPassword = '';
			this.$refs.passwordPopup.open();
		},
		
		// 编辑支付密码
		editPayPassword() {
			this.editData.oldPayPassword = '';
			this.editData.newPayPassword = '';
			this.editData.confirmPayPassword = '';
			this.$refs.payPasswordPopup.open();
		},
		
		// 找回支付密码
		findPayPassword() {
			uni.navigateTo({
				url: '/pages/users/findpaypass'
			});
		},
		
		// 关闭弹窗
		closePopup() {
			this.$refs.nicknamePopup?.close();
			this.$refs.phonePopup?.close();
			this.$refs.realNamePopup?.close();
			this.$refs.commissionPopup?.close();
			this.$refs.nowinCommissionPopup?.close();
			this.$refs.passwordPopup?.close();
			this.$refs.payPasswordPopup?.close();
			// 清空密码输入框
			this.editData.oldPassword = '';
			this.editData.newPassword = '';
			this.editData.confirmPassword = '';
			this.editData.oldPayPassword = '';
			this.editData.newPayPassword = '';
			this.editData.confirmPayPassword = '';
		},
		
		// 保存昵称
		async saveNickname() {
			if (!this.editData.nickname.trim()) {
				uni.showToast({
					title: '请输入昵称',
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '保存中...'
				});
				
				const res = await updateUserProfile({
					nickname: this.editData.nickname
				});
				
				if (res.code === 1) {
					this.userInfo.nickname = this.editData.nickname;
					this.closePopup();
					uni.showToast({
						title: '昵称更新成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: res.msg || '昵称更新失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('昵称更新失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
			}
		},
		
		// 保存实名认证
		async saveRealName() {
			if (!this.editData.realName.trim()) {
				uni.showToast({
					title: '请输入真实姓名',
					icon: 'none'
				});
				return;
			}
			if (!this.editData.idCard.trim()) {
				uni.showToast({
					title: '请输入身份证号码',
					icon: 'none'
				});
				return;
			}
			// 身份证号码格式验证
			const idCardReg = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
			if (!idCardReg.test(this.editData.idCard)) {
				uni.showToast({
					title: '请输入正确的身份证号码',
					icon: 'none'
				});
				return;
			}
			
			// 确认提醒
			uni.showModal({
				title: '确认提交',
				content: '实名认证成功后不可更改，请确认信息无误后提交',
				success: async (res) => {
					if (res.confirm) {
						try {
							uni.showLoading({
								title: '提交中...'
							});
							
							const apiRes = await updateUserProfile({
								realName: this.editData.realName,
								idCard: this.editData.idCard
							});
							
							if (apiRes.code === 1) {
								// 设置为审核中状态
								this.userInfo.is_verified = 2; // 2=审核中
								this.closePopup();
								uni.showToast({
									title: '实名认证提交成功，请等待审核',
									icon: 'success'
								});
							} else {
								uni.showToast({
									title: apiRes.msg || '实名认证提交失败',
									icon: 'none'
								});
							}
						} catch (error) {
							console.error('实名认证提交失败:', error);
							uni.showToast({
								title: error.msg || '网络错误，请重试',
								icon: 'none'
							});
						} finally {
							uni.hideLoading();
						}
					}
				}
			});
		},
		
		// 保存手机号
		async savePhone() {
			if (!this.editData.mobile.trim()) {
				uni.showToast({
					title: '请输入手机号',
					icon: 'none'
				});
				return;
			}
			if (!/^1[3-9]\d{9}$/.test(this.editData.mobile)) {
				uni.showToast({
					title: '请输入正确的手机号',
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '保存中...'
				});
				
				const res = await updateUserProfile({
					mobile: this.editData.mobile
				});
				
				if (res.code === 1) {
					this.userInfo.mobile = this.editData.mobile;
					this.closePopup();
					uni.showToast({
						title: '手机号更新成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: res.msg || '手机号更新失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('手机号更新失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
			}
		},
		

		
		// 保存返佣比例
		async saveCommission() {
			const default_rebate_rate = Number(this.editData.default_rebate_rate);
			
			// 验证范围
			if (default_rebate_rate < 0) {
				uni.showToast({
					title: '返佣比例不能小于0%',
					icon: 'none'
				});
				return;
			}
			
			// 验证不能超过当前用户的返佣比例
			if (default_rebate_rate > Number(this.userInfo.rebate_rate)) {
				uni.showToast({
					title: `默认返佣比例不能超过您的返佣比例${this.userInfo.rebate_rate}%`,
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '保存中...'
				});
				
				const res = await updateUserProfile({
					default_rebate_rate: default_rebate_rate
				});
				
				if (res.code === 1) {
					this.userInfo.default_rebate_rate = default_rebate_rate;
					this.closePopup();
					uni.showToast({
						title: '返佣比例更新成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: res.msg || '返佣比例更新失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('返佣比例更新失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
			}
		},
		
		// 保存未中奖返佣比例
		async saveNowinCommission() {
			const default_nowin_rate = Number(this.editData.default_nowin_rate);
			
			// 验证范围
			if (default_nowin_rate < 0) {
				uni.showToast({
					title: '未中奖返佣比例不能小于0%',
					icon: 'none'
				});
				return;
			}
			
			// 验证不能超过当前用户的未中奖返佣比例
			if (default_nowin_rate > Number(this.userInfo.nowin_rate)) {
				uni.showToast({
					title: `默认未中奖返佣比例不能超过您的未中奖返佣比例${this.userInfo.nowin_rate}%`,
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '保存中...'
				});
				
				const res = await updateUserProfile({
					default_nowin_rate: default_nowin_rate
				});
				
				if (res.code === 1) {
					this.userInfo.default_nowin_rate = default_nowin_rate;
					this.closePopup();
					uni.showToast({
						title: '未中奖返佣比例更新成功',
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: res.msg || '未中奖返佣比例更新失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('未中奖返佣比例更新失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
			}
		},
		
		// 保存密码
		async savePassword() {
			if (!this.editData.oldPassword.trim()) {
				uni.showToast({
					title: '请输入原密码',
					icon: 'none'
				});
				return;
			}
			if (!this.editData.newPassword.trim()) {
				uni.showToast({
					title: '请输入新密码',
					icon: 'none'
				});
				return;
			}
			if (this.editData.newPassword.length < 6) {
				uni.showToast({
					title: '新密码至少6位',
					icon: 'none'
				});
				return;
			}
			if (this.editData.newPassword !== this.editData.confirmPassword) {
				uni.showToast({
					title: '两次输入的密码不一致',
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '修改中...'
				});
				
				const res = await this.$http.post('/api/account/changePassword', {
					oldPassword: this.editData.oldPassword,
					newPassword: this.editData.newPassword
				});
				
				if (res.data.code === 1) {
					this.closePopup();
					uni.showToast({
						title: '密码修改成功，请重新登录',
						icon: 'success'
					});
					
					// 清除本地存储并跳转到登录页
					setTimeout(() => {
						uni.removeStorageSync('ba-user-token');
						uni.removeStorageSync('userInfo');
						uni.reLaunch({
							url: '/pages/users/login'
						});
					}, 1500);
				} else {
					uni.showToast({
						title: res.data.msg || '密码修改失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('密码修改失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
			}
		},
		
		// 保存支付密码
		async savePayPassword() {
			// 如果用户已有支付密码，需要验证原密码
			if (this.userInfo.has_pay_password && !this.editData.oldPayPassword.trim()) {
				uni.showToast({
					title: '请输入原支付密码',
					icon: 'none'
				});
				return;
			}
			if (!this.editData.newPayPassword.trim()) {
				uni.showToast({
					title: this.userInfo.has_pay_password ? '请输入新支付密码' : '请输入支付密码',
					icon: 'none'
				});
				return;
			}
			if (this.editData.newPayPassword.length !== 6 || !/^\d{6}$/.test(this.editData.newPayPassword)) {
				uni.showToast({
					title: '支付密码必须是6位数字',
					icon: 'none'
				});
				return;
			}
			if (this.editData.newPayPassword !== this.editData.confirmPayPassword) {
				uni.showToast({
					title: '两次输入的支付密码不一致',
					icon: 'none'
				});
				return;
			}
			
			try {
				uni.showLoading({
					title: '修改中...'
				});
				
				const data = {
					newPayPassword: this.editData.newPayPassword
				};
				
				// 如果用户已有支付密码，需要传递原密码
				if (this.userInfo.has_pay_password) {
					data.oldPayPassword = this.editData.oldPayPassword;
				}
				
				const res = await updateUserProfile(data);
				
				if (res.code === 1) {
				// 保存操作前的状态用于显示提示
				const wasPasswordSet = this.userInfo.has_pay_password;
				// 更新用户信息，标记已有支付密码
				this.userInfo.has_pay_password = true;
				this.closePopup();
				uni.showToast({
					title: wasPasswordSet ? '支付密码修改成功' : '支付密码设置成功',
					icon: 'success'
				});
			} else {
				uni.showToast({
					title: res.msg || '支付密码操作失败',
					icon: 'none'
				});
			}
			} catch (error) {
				console.error('支付密码操作失败:', error);
				uni.showToast({
					title: error.msg || '网络错误，请重试',
					icon: 'none'
				});
			} finally {
				uni.hideLoading();
			}
		},
		
		// 退出登录
		logout() {
			uni.showModal({
				title: '提示',
				content: '确定要退出登录吗？',
				success: (res) => {
					if (res.confirm) {
						// 清除本地存储的用户信息和token
						uni.removeStorageSync('ba-user-token');
						uni.removeStorageSync('userInfo');
						
						uni.showToast({
							title: '已退出登录',
							icon: 'success'
						});
						
						// 跳转到登录页面
						setTimeout(() => {
							uni.reLaunch({
								url: '/pages/users/login'
							});
						}, 1500);
					}
				}
			});
		}
	}
}
</script>

<style lang="scss">
.container {
}

.section {
	margin: 20rpx;
	background-color: #fff;
	border-radius: 35rpx 35rpx 0 0;
	padding: 30rpx;
	border: 1px solid #e9ecef;
}

.section-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 30rpx;
}

.avatar-section {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 0;
	border-bottom: 1px solid #e9ecef;
	margin-bottom: 20rpx;
}



.setting-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 0;
	border-bottom: 1px solid #e9ecef;
	transition: all 0.3s ease;
}

.setting-item:last-child {
	border-bottom: none;
}

.setting-item:active {
	background-color: #f8f9fa;
	border-radius: 12rpx;
	margin: 0 -20rpx;
	padding: 30rpx 20rpx;
}

.item-label {
	font-size: 28rpx;
	color: #333;
	flex: 1;
}

.item-content {
	display: flex;
	align-items: center;
	gap: 16rpx;
}

.item-value {
	font-size: 26rpx;
	color: #666;
}

.certified {
	color: #52c41a !important;
}

.uncertified {
	color: #ff4d4f !important;
}

.reviewing {
	color: #faad14 !important;
}

.logout-section {
	margin: 40rpx 30rpx;
}

.logout-btn {
	background-color: #ff4444;
	border-radius: 20rpx;
	padding: 20rpx 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
}

.logout-btn:active {
	transform: scale(0.98);
	opacity: 0.8;
}

.logout-text {
	font-size: 30rpx;
	font-weight: 600;
	color: #fff;
}

/* 弹窗样式 */
.popup-content {
	width: 600rpx;
	background-color: #fff;
	border-radius: 20rpx;
	padding: 40rpx;
}

.popup-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	text-align: center;
	margin-bottom: 30rpx;
}

.popup-buttons {
	display: flex;
	justify-content: space-between;
	margin-top: 30rpx;
	gap: 20rpx;
}

/* 弹窗输入框样式 */
.popup-content ::v-deep(.uv-input__content) {
	color: #333 !important;
	background-color: #f8f9fa !important;
	border-color: #e9ecef !important;
}

.popup-content ::v-deep(.uv-input__content input) {
	color: #333 !important;
	background-color: transparent !important;
}

.popup-content ::v-deep(.uv-input__content input::placeholder) {
	color: #999 !important;
}

.popup-content ::v-deep(.uv-textarea__content) {
	color: #333 !important;
	background-color: #f8f9fa !important;
	border-color: #e9ecef !important;
}

.popup-content ::v-deep(.uv-textarea__content textarea) {
	color: #333 !important;
	background-color: transparent !important;
}

.popup-content ::v-deep(.uv-textarea__content textarea::placeholder) {
	color: #999 !important;
}

/* 弹窗按钮样式 */
.popup-cancel-btn {
	flex: 1;
	width: 48% !important;
	background-color: #f8f9fa !important;
	color: #666 !important;
	border: 1px solid #e9ecef !important;
	border-radius: 12rpx;
	height: 80rpx;
	line-height: 80rpx;
	font-size: 28rpx;
	/* #ifdef APP-PLUS */
	max-width: 48%;
	min-width: 48%;
	/* #endif */
}

.popup-confirm-btn {
	flex: 1;
	width: 48% !important;
	background-color: orangered !important;
	color: #fff !important;
	border-radius: 12rpx;
	height: 80rpx;
	line-height: 80rpx;
	font-size: 28rpx;
	/* #ifdef APP-PLUS */
	max-width: 48%;
	min-width: 48%;
	/* #endif */
}

/* 加载状态样式 */
.loading-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	height: 60vh;
	gap: 20rpx;
}

.loading-text {
	font-size: 28rpx;
	color: #999;
}

</style>