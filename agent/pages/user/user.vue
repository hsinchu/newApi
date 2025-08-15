<template>
	<view class="container">
		<!-- 固定搜索框 -->
		<view class="search-section">
			<uv-search 
				v-model="searchKeyword" 
				placeholder="搜索会员ID/用户名/姓名/昵称" 
				:showAction="false"
				bgColor="#f7f7f7"
				color="#333"
				placeholderColor="#999"
				@search="handleSearch"
				@input="handleInput"
			></uv-search>
		</view>
		
		<!-- 内容区域 -->
		<view class="content-wrapper">
		
		<!-- 收藏会员列表 -->
		<view class="favorite-section" v-if="favoriteMembers.length > 0">
			<view class="section-header">
				<uv-icon name="star-fill" color="#ff934a" size="20"></uv-icon>
				<text class="section-title">收藏会员</text>
				<text class="member-count">({{favoriteMembers.length}})</text>
			</view>
			<scroll-view class="favorite-scroll" scroll-x="true" show-scrollbar="false">
				<view class="favorite-list">
					<view class="favorite-item" v-for="(member, index) in favoriteMembers" :key="member.id" @tap="viewMemberDetail(member)">
				<uv-avatar src="/static/images/avatar.jpg" size="45" shape="circle"></uv-avatar>
				<text class="member-name">{{member.nickname || member.username}}</text>
				<!-- <view class="member-status" :class="member.last_bet_time > (Date.now()/1000 - 86400) ? 'online' : 'offline'">
					{{member.last_bet_time > (Date.now()/1000 - 86400) ? '活跃' : ''}}
				</view> -->
			</view>
				</view>
			</scroll-view>
		</view>
		
			<!-- 会员索引列表 -->
			<view class="member-list-section" v-if="!loading || memberList.length > 0">
				<uv-index-list :index-list="indexList" :customNavHeight="customNavHeight" sticky="true" v-if="indexList.length > 0">
					<template v-for="(item, index) in itemArr">
						<!-- #ifdef APP-NVUE -->
						<uv-index-anchor :text="indexList[index]"></uv-index-anchor>
						<!-- #endif -->
						<uv-index-item>
							<!-- #ifndef APP-NVUE -->
							<uv-index-anchor :text="indexList[index]" bgColor="#f7f7f7"></uv-index-anchor>
							<!-- #endif -->
							<view class="list-cell" v-for="(member, cellIndex) in item" :key="member.id" @tap="selectMember(member)">
								<view class="member-info">
									<uv-avatar src="/static/images/avatar.jpg" size="45" shape="circle"></uv-avatar>
									<view class="member-details">
										<text class="member-name-text">{{member.nickname || member.username}}</text>
										<text class="member-info-text">余额: ¥{{member.money}} | 本月投注: ¥{{(member.month_bet_amount / 100).toFixed(2)}}</text>
										<text class="member-time-text" v-if="member.last_bet_time">最近投注: {{formatTime(member.last_bet_time)}}</text>
									</view>
								</view>
								<view class="member-actions">
									<uv-icon :name="member.agent_favorite === 1 ? 'star-fill' : 'star'" :color="member.agent_favorite === 1 ? '#ff934a' : '#ccc'" size="18" @tap.stop="toggleFavorite(member)"></uv-icon>
								</view>
							</view>
						</uv-index-item>
				</template>
			</uv-index-list>
				
				<!-- 空状态 -->
				<view class="empty-state" v-if="!loading && memberList.length === 0">
					<uv-icon name="user" color="#666" size="60"></uv-icon>
					<text class="empty-text">{{searchKeyword ? '未找到匹配的会员' : '暂无会员数据'}}</text>
					<text class="empty-tip" v-if="!searchKeyword">邀请会员注册后将在此显示</text>
				</view>
			</view>
			
			<!-- 加载状态 -->
			<view class="loading-state" v-if="loading && memberList.length === 0">
				<uv-loading-icon mode="circle" color="#ff934a"></uv-loading-icon>
				<text class="loading-text">加载中...</text>
			</view>		
		</view>
	</view>
</template>
<script>
	import authMixin from '@/mixins/auth.js';
	import { getMembers, toggleMemberFavorite } from '@/api/agent.js';
	export default {
		mixins: [authMixin],
		data() {
		return {
			searchKeyword: '', // 搜索关键词
			memberList: [], // 会员列表
			favoriteMembers: [], // 收藏会员列表
			allMembers: [], // 所有会员数据
			indexList: [], // 索引列表
			itemArr: [], // 按字母分组的会员数据
			loading: false, // 加载状态
			total: 0, // 总数量

			searchTimer: null // 搜索防抖定时器
			}
		},
		computed: {
			// 计算自定义导航高度，适配不同平台
			customNavHeight() {
				// #ifdef H5
				return '44px'; // H5环境
				// #endif
				// #ifdef APP-PLUS
				return '88rpx'; // APP环境，包含状态栏
				// #endif
				// #ifdef MP
				return '88rpx'; // 小程序环境
				// #endif
				return '88rpx'; // 默认值
			}
		},
		onLoad() {
			this.loadMembers();
		},
		onShow() {
			// 页面显示时刷新数据
			if (this.memberList.length > 0) {
				this.loadMembers(true);
			}
		},
		// 下拉刷新
		onPullDownRefresh() {
			this.refreshing = true;
			this.loadMembers(true).finally(() => {
				uni.stopPullDownRefresh();
				this.refreshing = false;
			});
		},
		methods: {
			// 加载会员数据
			async loadMembers(refresh = false) {
				if (this.loading) return;
				
				this.loading = true;
				
				try {
					const params = {
						keyword: this.searchKeyword
					};
					
					const response = await getMembers(params);
					
					if (response.code === 1) {
						const { data, total } = response.data;
						
						this.memberList = data;
						this.allMembers = data;
						this.total = total;
						
						// 分离收藏会员
						this.favoriteMembers = this.memberList.filter(member => member.agent_favorite === 1);
						
						// 生成索引列表
						this.generateIndexList();
					} else {
						uni.showToast({
							title: response.msg || '加载失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('加载会员数据失败:', error);
					uni.showToast({
						title: '网络错误，请重试',
						icon: 'none'
					});
				} finally {
					this.loading = false;
				}
			},
			// 生成索引列表
			generateIndexList() {
				const indexMap = {};
				const indexList = [];
				
				this.memberList.forEach(member => {
					const firstChar = (member.nickname || member.username || '').charAt(0).toUpperCase();
					const index = /^[A-Z]$/.test(firstChar) ? firstChar : '#';
					
					if (!indexMap[index]) {
						indexMap[index] = [];
						indexList.push(index);
					}
					indexMap[index].push(member);
				});
				
				// 排序索引
				indexList.sort((a, b) => {
					if (a === '#') return 1;
					if (b === '#') return -1;
					return a.localeCompare(b);
				});
				
				this.indexList = indexList;
				this.itemArr = indexList.map(index => indexMap[index]);
			},
			// 查看会员详情
			viewMemberDetail(member) {
				console.log('查看会员详情:', member);
				// 跳转到会员详情页面
				uni.navigateTo({
					url: `/pages/user/detail?id=${member.id}`
				});
			},
			// 添加收藏
			addFavorite() {
				console.log('添加收藏会员');
				uni.showToast({
					title: '添加收藏会员',
					icon: 'none'
				});
			},
			// 选择会员
			selectMember(member) {
				console.log('选择会员:', member);
				// 跳转到会员详情页面
				uni.navigateTo({
					url: `/pages/user/detail?id=${member.id}`
				});
			},
			// 切换收藏状态
			async toggleFavorite(member) {
				try {
					const response = await toggleMemberFavorite({
						member_id: member.id
					});
					
					if (response.code === 1) {
						// 更新本地数据
						const memberIndex = this.memberList.findIndex(m => m.id === member.id);
						if (memberIndex > -1) {
							this.memberList[memberIndex].agent_favorite = response.data.agent_favorite;
						}
						
						// 更新收藏列表
						this.favoriteMembers = this.memberList.filter(m => m.agent_favorite === 1);
						
						// 重新生成索引列表
						this.generateIndexList();
						
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
			// 搜索处理
			handleSearch(value) {
				console.log('搜索:', value);
				this.performSearch(value);
			},
			// 输入处理
			handleInput(value) {
				this.searchKeyword = value;
				// 防抖搜索
				clearTimeout(this.searchTimer);
				this.searchTimer = setTimeout(() => {
					this.performSearch(value);
				}, 500);
			},
			// 执行搜索
			performSearch(keyword) {
				this.searchKeyword = keyword;
				this.loadMembers(true);
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
			}
		}
	}
</script>
<style lang="scss" scoped>
	/* 固定搜索框区域 */
	.search-section {
		position: fixed;
		top: 80rpx; 
		/* #ifdef APP-PLUS */
		top: 0; 
		/* #endif */
		left: 0;
		right: 0;
		padding: 20rpx 30rpx;
		background-color: #fff;
		z-index: 99;
	}	
	
	/* 内容包装器 */
	.content-wrapper {
		margin-top:125rpx;
	}
	
	/* 收藏会员区域 */
	.favorite-section {
		background-color: #fff;
		margin: 12rpx 25rpx;
		border-radius: 55rpx;
		padding: 20rpx 25rpx;
		border: 1px solid #e9ecef;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
	}
	
	.section-header {
		display: flex;
		align-items: center;
		margin-bottom: 24rpx;
	}
	
	.section-title {
		font-size: 25rpx;
		font-weight: 600;
		color: #333;
		margin-left: 12rpx;
	}
	
	.member-count {
		font-size: 24rpx;
		color: #333;
		margin-left: 8rpx;
	}
	
	.favorite-scroll {
		width: 100%;
	}
	
	.favorite-list {
		display: flex;
		flex-direction: row;
		padding-bottom: 8rpx;
	}
	
	.favorite-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		min-width: 120rpx;
		padding: 10rpx 5rpx;
		border-radius: 16rpx;
		transition: all 0.3s ease;
	}
	
	.favorite-item:active {
		background-color: #333;
		transform: scale(0.98);
		border-color: #555;
	}
	
	.member-name {
		font-size: 24rpx;
		color: #333;
		margin-top: 12rpx;
		text-align: center;
		max-width: 100rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	
	.member-status {
		font-size: 20rpx;
		padding: 4rpx 12rpx;
		border-radius: 20rpx;
		margin-top: 8rpx;
	}
	
	.member-status.online {
		background-color: #e8f5e8;
		color: #52c41a;
	}
	
	.member-status.offline {
		background-color: #f8f9fa;
		color: #666;
	}
	
	.add-favorite {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		min-width: 120rpx;
		height: 140rpx;
		border: 2rpx dashed #e9ecef;
		border-radius: 12rpx;
		background-color: #f8f9fa;
		transition: all 0.3s ease;
	}
	
	.add-favorite:active {
		background-color: #e9ecef;
		border-color: #dee2e6;
	}
	
	.add-text {
		font-size: 24rpx;
		color: #666;
		margin-top: 8rpx;
	}
	
	/* 会员列表区域 */
	.member-list-section {
		margin: 20rpx 30rpx;
		border-radius: 40rpx;
		overflow: hidden;
		background-color: #fff;
		border: 1px solid #e9ecef;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.1);
	}
	
	.list-cell {
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-sizing: border-box;
		width: 100%;
		padding: 30rpx;
		overflow: hidden;
		color: #333;
		font-size: 28rpx;
		line-height: 40rpx;
		border-bottom: 1px solid #e9ecef;
		background-color: transparent;
		transition: all 0.3s ease;
		/* 确保在安卓设备上不换行 */
		flex-wrap: nowrap;
	}
	
	.list-cell:active {
		background-color: #f8f9fa;
	}
	
	.list-cell:last-child {
		border-bottom: none;
	}
	
	.member-info {
		display: flex;
		align-items: center;
		flex: 1;
		min-width: 0; /* 防止flex子元素溢出 */
	}
	
	.member-details {
		margin-left: 24rpx;
		flex: 1;
		display: flex;
		flex-direction: column;
		min-width: 0; /* 防止文本溢出导致布局问题 */
		overflow: hidden;
	}
	
	.member-name-text {
		font-size: 30rpx;
		color: #333;
		font-weight: 500;
		margin-bottom: 8rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		max-width: 100%;
	}
	
	.member-info-text {
		font-size: 22rpx;
		color: #666;
		margin-bottom: 4rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		max-width: 100%;
	}
	
	.member-time-text {
		font-size: 20rpx;
		color: #999;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		max-width: 100%;
	}
	
	.member-actions {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		padding: 12rpx;
		margin-left: auto;
		flex-shrink: 0; /* 防止收藏按钮被压缩 */
		min-width: 60rpx; /* 确保按钮有最小宽度 */
	}
	
	/* 空状态 */
	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 100rpx 40rpx;
		text-align: center;
	}
	
	.empty-text {
		font-size: 28rpx;
		color: #666;
		margin-top: 24rpx;
	}
	
	.empty-tip {
		font-size: 24rpx;
		color: #999;
		margin-top: 12rpx;
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
	

</style>