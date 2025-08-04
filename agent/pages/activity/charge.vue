<template>
	<view class="charge-activity-container">
		<!-- 活动说明 -->
		<view class="activity-header">
			<view class="header-card">
				<text class="activity-desc">请保证自己的余额充足</text>
				<text class="activity-desc">用户充值后会根据匹配的最大充值金额赠送相应的奖励金</text>
				<text class="activity-desc">在余额不足赠送的情况下，会自动关闭所有充值活动</text>
			</view>
		</view>
		
		<!-- 活动配置列表 -->
		<view class="activity-list">
			<view class="list-header">
				<text class="list-title">活动配置</text>
				<view class="add-btn" @click="showAddModal">
					<uv-icon name="plus" size="16" color="#007AFF"></uv-icon>
					<text class="add-text">添加</text>
				</view>
			</view>
			
			<!-- 活动项目列表 -->
			<view class="activity-items">
				<view v-if="activityList.length === 0" class="empty-state">
					<uv-icon name="info-circle" size="48" color="#666"></uv-icon>
					<text class="empty-text">暂无活动配置</text>
					<text class="empty-desc">点击右上角添加按钮创建充值活动</text>
				</view>
				
				<view v-for="(item, index) in activityList" :key="index" class="activity-item">
					<view class="item-content">
						<view class="item-header">
							<text class="charge-amount">充值满 ¥{{ item.chargeAmount }}</text>
							<view class="status-tag" :class="item.status === 1 ? 'active' : 'inactive'">
								<text class="status-text">{{ item.status === 1 ? '启用' : '停用' }}</text>
							</view>
						</view>
						<view class="item-body">
							<text class="bonus-amount">赠送 ¥{{ item.bonusAmount }}</text>
							<text class="bonus-rate">({{ ((item.bonusAmount / item.chargeAmount) * 100).toFixed(1) }}%)</text>
						</view>
						<view class="item-footer">
							<text class="create-time">更新时间: {{ item.updateTime }}</text>
						</view>
					</view>
					<view class="item-actions">
						<view class="action-btn edit-btn" @click="editActivity(item, index)">
							<uv-icon name="edit-pen" size="16" color="#007AFF"></uv-icon>
						</view>
						<view class="action-btn toggle-btn" @click="toggleStatus(index)">
							<uv-icon :name="item.status === 1 ? 'eye-off' : 'eye'" size="16" :color="item.status === 1 ? '#ff6b6b' : '#51cf66'"></uv-icon>
						</view>
						<view class="action-btn delete-btn" @click="deleteActivity(index)">
							<uv-icon name="trash" size="16" color="#ff6b6b"></uv-icon>
						</view>
					</view>
				</view>
			</view>
		</view>
		
		<!-- 添加/编辑活动弹窗 -->
		<uv-popup 
			ref="activityPopup"
			mode="bottom" 
			border-radius="20"
			:custom-style="{ backgroundColor: '#2a2a2a' }"
			@close="closeModal"
		>
			<view class="popup-container">
				<view class="popup-header">
					<text class="popup-title">{{ isEditMode ? '编辑' : '添加' }}充值活动</text>
					<view class="close-btn" @tap="closeModal">
						<uv-icon name="close" size="20" color="#d8d8d8"></uv-icon>
					</view>
				</view>
				
				<view class="form-content">
					<view class="form-item">
						<text class="item-label">充值金额</text>
						<view class="input-wrapper">
							<text class="currency-symbol">¥</text>
							<input 
								v-model="formData.chargeAmount" 
								class="form-input" 
								type="digit"
								placeholder="请输入充值金额"
								placeholder-style="color: #666;"
							/>
						</view>
					</view>
					
					<view class="form-item">
						<text class="item-label">赠送金额</text>
						<view class="input-wrapper">
							<text class="currency-symbol">¥</text>
							<input 
								v-model="formData.bonusAmount" 
								class="form-input" 
								type="digit"
								placeholder="请输入赠送金额"
								placeholder-style="color: #666;"
							/>
						</view>
					</view>
					
					<view class="form-item">
						<text class="item-label">活动状态</text>
						<uv-switch v-model="formData.status" :active-value="1" :inactive-value="0"></uv-switch>
					</view>
					
					<!-- 预览信息 -->
					<view v-if="formData.chargeAmount && formData.bonusAmount" class="preview-section">
						<text class="preview-title">活动预览</text>
						<view class="preview-content">
							<text class="preview-text">充值满 ¥{{ formData.chargeAmount }} 赠送 ¥{{ formData.bonusAmount }}</text>
							<text class="preview-rate">赠送比例: {{ ((formData.bonusAmount / formData.chargeAmount) * 100).toFixed(1) }}%</text>
						</view>
					</view>
				</view>
				
				<!-- 操作按钮 -->
				<view class="popup-actions">
					<uv-button 
						type="info" 
						@click="closeModal"
						class="action-btn cancel-btn"
					>
						取消
					</uv-button>
					<uv-button 
						type="primary" 
						@click="saveActivity"
						class="action-btn save-btn"
						:disabled="!canSave"
					>
						{{ isEditMode ? '更新' : '保存' }}
					</uv-button>
				</view>
			</view>
		</uv-popup>
	</view>
</template>
<script>
import { getAgentRecharge, saveAgentRecharge, deleteAgentRecharge, toggleAgentRechargeStatus } from '@/api/agent.js';
export default {
	data() {
		return {
			// 活动列表
			activityList: [],
			
			// 弹窗相关
			isEditMode: false,
			editIndex: -1,
			formData: {
				id: '',
				chargeAmount: '',
				bonusAmount: '',
				status: 1
			},
			
			// 加载状态
			loading: false
		}
	},
	
	computed: {
		// 是否可以保存
		canSave() {
			return this.formData.chargeAmount && this.formData.bonusAmount && 
				   parseFloat(this.formData.chargeAmount) > 0 && 
				   parseFloat(this.formData.bonusAmount) > 0;
		}
	},
	
	onLoad() {
		this.loadActivityList();
	},
	
	methods: {
		// 加载活动列表
		async loadActivityList() {
			try {
				this.loading = true;
				const res = await getAgentRecharge();
				if (res.code === 1) {
					this.activityList = res.data.list || [];
				} else {
					uni.showToast({
						title: res.msg || '获取数据失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('加载活动列表失败:', error);
				uni.showToast({
					title: '网络错误',
					icon: 'none'
				});
			} finally {
				this.loading = false;
			}
		},
		// 显示添加弹窗
		showAddModal() {
			this.isEditMode = false;
			this.editIndex = -1;
			this.resetForm();
			this.$refs.activityPopup.open();
		},
		
		// 编辑活动
		editActivity(item, index) {
			this.isEditMode = true;
			this.editIndex = index;
			this.formData = {
				id: item.id,
				chargeAmount: item.chargeAmount.toString(),
				bonusAmount: item.bonusAmount.toString(),
				status: item.status
			};
			this.$refs.activityPopup.open();
		},
		
		// 切换状态
		async toggleStatus(index) {
			try {
				const item = this.activityList[index];
				const res = await toggleAgentRechargeStatus(item.id);
				if (res.code === 1) {
					this.activityList[index].status = res.data.status;
					uni.showToast({
						title: res.msg,
						icon: 'success'
					});
				} else {
					uni.showToast({
						title: res.msg || '操作失败',
						icon: 'none'
					});
				}
			} catch (error) {
				console.error('切换状态失败:', error);
				uni.showToast({
					title: '网络错误',
					icon: 'none'
				});
			}
		},
		
		// 删除活动
		deleteActivity(index) {
			uni.showModal({
				title: '确认删除',
				content: '确定要删除这个充值活动吗？',
				success: async (res) => {
					if (res.confirm) {
						try {
							const item = this.activityList[index];
							const deleteRes = await deleteAgentRecharge(item.id);
							if (deleteRes.code === 1) {
								this.activityList.splice(index, 1);
								uni.showToast({
									title: '删除成功',
									icon: 'success'
								});
							} else {
								uni.showToast({
									title: deleteRes.msg || '删除失败',
									icon: 'none'
								});
							}
						} catch (error) {
							console.error('删除活动失败:', error);
							uni.showToast({
								title: '网络错误',
								icon: 'none'
							});
						}
					}
				}
			});
		},
		
		// 保存活动
		async saveActivity() {
			if (!this.canSave) {
				uni.showToast({
					title: '请填写完整信息',
					icon: 'none'
				});
				return;
			}

			const saveData = {
				chargeAmount: parseFloat(this.formData.chargeAmount),
				bonusAmount: parseFloat(this.formData.bonusAmount),
				status: this.formData.status
			};

			if (saveData.chargeAmount < saveData.bonusAmount) {
				uni.showToast({
					title: '充值金额不能小于赠送金额',
					icon: 'none'
				});
				return;
			}
			
			// 如果是编辑模式，添加ID
			if (this.isEditMode && this.formData.id) {
				saveData.id = this.formData.id;
			}
			
			const res = await saveAgentRecharge(saveData);
			if (res.code == 1) {
				uni.showToast({
					title: res.msg || (this.isEditMode ? '更新成功' : '添加成功'),
					icon: 'success'
				});
				
				// 重新加载列表
				await this.loadActivityList();
				this.closeModal();
			} else {
				uni.showToast({
					title: res.msg || '保存失败',
					icon: 'none'
				});
			}
		},
		
		// 关闭弹窗
		closeModal() {
			this.$refs.activityPopup.close();
			this.resetForm();
		},
		
		// 重置表单
		resetForm() {
			this.formData = {
				id: '',
				chargeAmount: '',
				bonusAmount: '',
				status: 1
			};
			this.isEditMode = false;
			this.editIndex = -1;
		}
	}
}
</script>

<style scoped lang="scss">

.activity-header {
	
	.header-card {
		padding: 30rpx 40rpx;
		
		.activity-title {
			color: #e1e1e1;
			font-size: 36rpx;
			font-weight: bold;
			display: block;
		}
		
		.activity-desc {
			color: #999;
			font-size: 25rpx;
			line-height:40rpx;
			display: block;
		}
	}
}

.activity-list {
	padding: 0 20rpx;
	
	.list-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 30rpx 0 20rpx 0;
		
		.list-title {
			color: #e1e1e1;
			font-size: 30rpx;
			border-left:5rpx solid orangered;
			text-indent:25rpx;
			font-weight: bold;
		}
		
		.add-btn {
			display: flex;
			align-items: center;
			gap: 8rpx;
			padding: 12rpx 20rpx;
			background-color: rgba(0, 122, 255, 0.1);
			border-radius: 20rpx;
			border: 1px solid #007AFF;
			
			.add-text {
				color: #007AFF;
				font-size: 24rpx;
			}
		}
	}
}

.activity-items {
	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 100rpx 0;
		
		.empty-text {
			color: #666;
			font-size: 32rpx;
			margin: 20rpx 0 10rpx 0;
		}
		
		.empty-desc {
			color: #999;
			font-size: 24rpx;
		}
	}
	
	.activity-item {
		background-color: #1b1b1b;
		border-radius: 55rpx 55rpx 0 0;
		margin-bottom: 20rpx;
		display: flex;
		align-items: center;
		padding: 30rpx;
		
		.item-content {
			flex: 1;
			
			.item-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 15rpx;
				
				.charge-amount {
					color: #e1e1e1;
					font-size: 28rpx;
					font-weight: bold;
				}
				
				.status-tag {
					padding: 8rpx 16rpx;
					border-radius: 12rpx;
					
					&.active {
						background-color: rgba(81, 207, 102, 0.2);
						
						.status-text {
							color: #51cf66;
						}
					}
					
					&.inactive {
						background-color: rgba(255, 107, 107, 0.2);
						
						.status-text {
							color: #ff6b6b;
						}
					}
					
					.status-text {
						font-size: 22rpx;
					}
				}
			}
			
			.item-body {
				display: flex;
				align-items: center;
				gap: 10rpx;
				margin-bottom: 15rpx;
				
				.bonus-amount {
					color: #ff7c4d;
					font-size: 28rpx;
					font-weight: bold;
				}
				
				.bonus-rate {
					color: #999;
					font-size: 24rpx;
				}
			}
			
			.item-footer {
				.create-time {
					color: #666;
					font-size: 22rpx;
				}
			}
		}
		
		.item-actions {
			display: flex;
			flex-direction: column;
			gap: 15rpx;
			margin-left: 20rpx;
			
			.action-btn {
				width: 50rpx;
				height: 50rpx;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				background-color: #252525;
				transition: all 0.2s ease;
				
				&:active {
					transform: scale(0.9);
				}
			}
		}
	}
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
			color: #e1e1e1;
			font-size: 36rpx;
			font-weight: bold;
		}
		
		.close-btn {
			width: 60rpx;
			height: 60rpx;
			border-radius: 50%;
			background-color: #333;
			display: flex;
			align-items: center;
			justify-content: center;
		}
	}
	
	.form-content {
		.form-item {
			margin-bottom: 40rpx;
			
			.item-label {
				color: #e1e1e1;
				font-size: 28rpx;
				display: block;
				margin-bottom: 20rpx;
			}
			
			.input-wrapper {
				display: flex;
				align-items: center;
				background-color: #333;
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
					color: #e1e1e1;
					font-size: 28rpx;
					height: 100%;
					border: none;
					background: transparent;
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
					color: #e1e1e1;
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
</style>