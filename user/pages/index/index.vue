<template>
	<view class="container">
		<!-- 顶部下载区域 -->
		<view class="download-banner" v-if="showDownloadBanner">
			<view class="download-content">
				<image src="/static/images/logo.png" class="download-logo" mode="aspectFit"></image>
				<text class="download-text">最顶尖的APP 方寸之间 从容...</text>
				<view class="download-btn" @click="downloadApp">点我下载</view>
			</view>
			<view class="close-btn" @click="closeDownloadBanner">
				<text class="close-icon">×</text>
			</view>
		</view>
		
		<view class="scroll-container">
			
			<!-- 轮播图 -->
			<view class="banner-section">
				<swiper class="banner-swiper" 
					:indicator-dots="true" 
					:autoplay="true" 
					:interval="3000" 
					:duration="500"
					indicator-color="rgba(255,255,255,0.3)"
					indicator-active-color="#ff6b35">
					<swiper-item v-for="(banner, index) in bannerList" :key="index">
						<image class="banner-image" :src="banner.image" mode="aspectFill" @click="onBannerClick(banner)"></image>
					</swiper-item>
				</swiper>
			</view>

			<!-- 登录注册和快捷入口区域 -->
			<view class="entry-container">
				<!-- 登录注册区域 -->
				<view class="auth-section" v-if="!isLoggedIn">
					<view class="auth-buttons">
						<view class="login-btn" @click="goToLogin">登录</view>
						<view class="register-btn" @click="goToRegister">注册</view>
					</view>
					<text class="welcome-text">{{ getGreeting() }}，请先登入或注册</text>
				</view>
				
				<!-- 用户信息区域 -->
				<view class="user-info-section" v-if="isLoggedIn">
					<view class="user-avatar">
						<!-- <image :src="userInfo.avatar || '/static/images/avatar.svg'" mode="aspectFill" class="avatar-image"></image> -->
						<image src="/static/images/avatar.jpg" mode="aspectFill" class="avatar-image"></image>
					</view>
					<view class="user-details">
						<text class="greeting-text">{{ getGreeting() }}</text>
						<text class="nickname-text">{{ userInfo.nickname || userInfo.username || '用户' }}</text>
					</view>
				</view>
				
				<!-- 快捷入口 -->
				<view class="ware-entry">
					<view class="entry-item" v-for="(entry, index) in quickEntries" :key="index" @click="onEntryClick(entry)">
						<view class="entry-icon">
							<image :src="entry.icon" mode="aspectFit" class="entry-icon-image"></image>
						</view>
						<text class="entry-text">{{ entry.name }}</text>
					</view>
				</view>
			</view>
			
			<!-- 主要内容区域 -->
			<view class="main-content">
				<!-- 左侧Tab导航 -->
				<view class="left-tabs">
					<view class="tab-item" 
						:class="{ active: activeTab === 'ware' }" 
						@click="switchTab('ware')">
						<image :src="activeTab === 'ware' ? '/static/icon/index/home_1.png' : '/static/icon/index/home.png'" mode="aspectFit" class="tab-icon"></image>
						<text class="tab-text">BNB<br />彩票</text>
					</view>
					<view class="tab-item" 
						:class="{ active: activeTab === 'live' }" 
						@click="switchTab('live')">
						<image :src="activeTab === 'live' ? '/static/icon/index/zhibo1.png' : '/static/icon/index/zhibo.png'" mode="aspectFit" class="tab-icon"></image>
						<text class="tab-text">BNB<br />直播</text>
					</view>
					<view class="tab-item" 
						:class="{ active: activeTab === 'chess' }" 
						@click="switchTab('chess')">
						<image :src="activeTab === 'chess' ? '/static/icon/index/qipai1.png' : '/static/icon/index/qipai.png'" mode="aspectFit" class="tab-icon"></image>
						<text class="tab-text">BNB<br />棋牌</text>
					</view>
					<view class="tab-item" 
						:class="{ active: activeTab === 'person' }" 
						@click="switchTab('person')">
						<image :src="activeTab === 'person' ? '/static/icon/index/dianzi1.png' : '/static/icon/index/dianzi.png'" mode="aspectFit" class="tab-icon"></image>
						<text class="tab-text">BNB<br />电子</text>
					</view>
				</view>
				
				<!-- 右侧内容区域 -->
				<view class="right-content">
					
					<!-- 彩票彩种 -->
					<view class="lottery-section" v-if="activeTab === 'ware'">
						<!-- <view class="section-header">
							<view class="header-left">
								<image src="/static/icon/index/home_1.png" mode="aspectFit" class="tab-icon"></image>
								<text class="section-title">BNB彩票</text>
							</view>
						</view> -->
						<view v-if="wareLotteries.length > 0" class="lottery-grid">
							<view class="lottery-item" v-for="(lottery, index) in wareLotteries" :key="index" @click="onLotteryClick(lottery)">
								<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
								<view class="lottery-info">
									<text class="lottery-name">{{ lottery.name }}</text>
									<text class="lottery-type_desc" v-if="lottery.type_desc">{{ lottery.type_desc }}</text>
								</view>
							</view>
						</view>
						<view v-else class="empty-state">
							<uv-icon name="empty-data" size="48" color="#ccc"></uv-icon>
							<text class="empty-text" v-if="isLoggedIn">暂未分配游戏，敬请期待！</text>
							<text class="empty-text" v-else>请先登录后查看</text>
						</view>
					</view>
					
					<!-- 直播彩种 -->
					<view class="lottery-section" v-if="activeTab === 'live'">
						<!-- <view class="section-header">
							<view class="header-left">
								<image src="/static/icon/index/zhibo1.png" mode="aspectFit" class="tab-icon"></image>
								<text class="section-title">BNB直播</text>
							</view>
						</view> -->
						<view v-if="liveLotteries.length > 0" class="lottery-grid">
							<view class="lottery-item" v-for="(lottery, index) in liveLotteries" :key="index" @click="onLotteryClick(lottery)">
								<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
								<view class="lottery-info">
									<text class="lottery-name">{{ lottery.name }}</text>
									<text class="lottery-type_desc" v-if="lottery.type_desc">{{ lottery.type_desc }}</text>
								</view>
							</view>
						</view>
						<view v-else class="empty-state">
							<uv-icon name="empty-data" size="48" color="#ccc"></uv-icon>
							<text class="empty-text">暂未分配游戏，敬请期待！</text>
						</view>
					</view>
					
					<!-- 棋牌彩种 -->
					<view class="lottery-section" v-if="activeTab === 'chess'">
						<!-- <view class="section-header">
							<view class="header-left">
								<image src="/static/icon/index/qipai1.png" mode="aspectFit" class="tab-icon"></image>
								<text class="section-title">BNB棋牌</text>
							</view>
						</view> -->
						<view v-if="chessLotteries.length > 0" class="lottery-grid">
							<view class="lottery-item" v-for="(lottery, index) in chessLotteries" :key="index" @click="onLotteryClick(lottery)">
								<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
								<view class="lottery-info">
									<text class="lottery-name">{{ lottery.name }}</text>
									<text class="lottery-type_desc" v-if="lottery.type_desc">{{ lottery.type_desc }}</text>
								</view>
							</view>
						</view>
						<view v-else class="empty-state">
							<uv-icon name="empty-data" size="48" color="#ccc"></uv-icon>
							<text class="empty-text">暂未分配游戏，敬请期待！</text>
						</view>
					</view>
					
					<!-- 电子彩种 -->
					<view class="lottery-section" v-if="activeTab === 'person'">
						<!-- <view class="section-header">
							<view class="header-left">
								<image src="/static/icon/index/dianzi1.png" mode="aspectFit" class="tab-icon"></image>
								<text class="section-title">BNB电子</text>
							</view>
						</view> -->
						<view v-if="personLotteries.length > 0" class="lottery-grid">
							<view class="lottery-item" v-for="(lottery, index) in personLotteries" :key="index" @click="onPersonLotteryClick(lottery)">
								<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
								<view class="lottery-info">
									<text class="lottery-name">{{ lottery.name }}</text>
									<text class="lottery-type_desc" v-if="lottery.type_desc">{{ lottery.type_desc }}</text>
								</view>
							</view>
						</view>
						<view v-else class="empty-state">
							<uv-icon name="empty-data" size="48" color="#ccc"></uv-icon>
							<text class="empty-text">暂未分配游戏，敬请期待！</text>
						</view>
					</view>
				</view>
			</view>	
		</view>
		
		<!-- 公告弹窗 -->
		<uv-popup ref="noticePopup" round="20" @change="onNoticePopupChange">
			<view class="notice-popup">
				<view class="notice-header">
					<text class="notice-title">平台公告</text>
				</view>
				<view class="notice-list">
					<view class="notice-item" v-for="(notice, index) in noticeList" :key="index">
						<view class="notice-icon">❤️</view>
						<text class="notice-text">{{ notice.title || notice.content }}</text>
						<view class="notice-btn" @click="viewNotice(notice)">查看</view>
					</view>
				</view>
				<view class="notice-footer">
					<uv-button type="warning" @click="closeNoticePopup" customStyle="width: 100%;">关闭</uv-button>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
	import authMixin from '@/mixins/auth.js';
	import { getLotteryTypes } from '@/api/lottery/lottery.js';
	import { getBannerList } from '@/api/banner/banner.js';
	import { getDanoList } from '@/api/other.js';
	
	export default {
		mixins: [authMixin],
		data() {
			return {
				refreshing: false, // 刷新状态
				activeTab: 'ware', // 当前激活的tab
				
				// 登录状态
				isLoggedIn: false,
				userInfo: {}, // 用户信息
				
				// 下载横幅
				showDownloadBanner: true,
				
				// 公告弹窗
				noticeList: [],
				
				// 轮播图数据
				bannerList: [],
				
				// 快捷入口
				quickEntries: [{
					id: 1,
					name: '充值',
					icon: '/static/icon/index/1.png',
					color: '#fff',
					url: '/pages/users/charge'
				}, {
					id: 2,
					name: '提现',
					icon: '/static/icon/index/2.png',		
					color: '#fff',
					url: '/pages/users/withdraw'
				}, 
				{
					id: 3,
					name: '客服',
					icon: '/static/icon/index/3.png',
					color: '#fff',
					url: '/pages/service/service'
				},
				/*{
					id: 4,
					name: '公告',
					icon: '/static/icon/index/6.png',
					color: '#fff',
					url: '/pages/other/dano'
				}, */
				 {
					id: 5,
					name: '账变',
					icon: '/static/icon/index/5.png',	
					color: '#fff',
					url: '/pages/users/moneylog'
				}],
				
				// 彩票彩种 (type_group: ware)
				wareLotteries: [],
				
				// 电子彩种 (type_group: person)
				personLotteries: [],
				
				// 直播彩种 (type_group: live)
				liveLotteries: [],
				
				// 棋牌彩种 (type_group: chess)
				chessLotteries: []
				

			}
		},
		
		onLoad() {
			// 页面加载时获取数据
			this.loadData();
			// 检查登录状态
			this.checkLoginStatus();
			// 加载公告数据并显示弹窗
			this.loadNoticeData();
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 重新加载数据
			this.loadData();
			// 延迟停止下拉刷新动画
			setTimeout(() => {
				uni.stopPullDownRefresh();
			}, 1000);
		},
		
		methods: {
				// 检查登录状态
				checkLoginStatus() {
					// 使用正确的token键名'ba-user-token'
					const token = uni.getStorageSync('ba-user-token');
					const userInfo = uni.getStorageSync('userInfo');
					// 检查token是否存在且不为空，同时检查用户信息
					this.isLoggedIn = !!(token && token.trim() !== '' && token !== 'null' && token !== 'undefined');
					// 如果已登录，获取用户信息
					if (this.isLoggedIn && userInfo) {
						this.userInfo = userInfo;
					}
					console.log('登录状态检查:', {
						token: token,
						userInfo: userInfo,
						isLoggedIn: this.isLoggedIn
					});
				},
				
				// 根据时间获取问候语
				getGreeting() {
					const hour = new Date().getHours();
					if (hour >= 6 && hour < 12) {
						return '早上好';
					} else if (hour >= 12 && hour < 18) {
						return '下午好';
					} else {
						return '晚上好';
					}
				},
				
				// 加载公告数据
				async loadNoticeData() {
					try {
						const response = await getDanoList();
						
						if (response.code === 1 && response.data) {
							this.noticeList = response.data.list || response.data || [];
							
							// 每次页面加载都显示公告弹窗
							if (this.noticeList.length > 0) {
								setTimeout(() => {
									this.openNoticePopup();
								}, 1500); // 延迟1.5秒显示
							}
						} else {
							// 为了测试，添加一些模拟数据
							this.noticeList = [];
							setTimeout(() => {
								this.openNoticePopup();
							}, 1500);
						}
					} catch (error) {
						// 出错时也显示测试数据
						this.noticeList = [
						];
						setTimeout(() => {
							this.openNoticePopup();
						}, 1500);
					}
				},
				
				// 登录
				goToLogin() {
					uni.navigateTo({
						url: '/pages/users/login'
					});
				},
				
				// 注册
				goToRegister() {
					uni.navigateTo({
						url: '/pages/users/register'
					});
				},
				
				// 关闭下载横幅
				closeDownloadBanner() {
					this.showDownloadBanner = false;
				},
				
				// 下载APP
				downloadApp() {
					uni.showToast({
						title: '正在跳转下载页面',
						icon: 'none'
					});
				},
				
				// 打开公告弹窗
				openNoticePopup() {
					this.$refs.noticePopup.open('center');
				},
				
				// 关闭公告弹窗
				closeNoticePopup() {
					this.$refs.noticePopup.close();
				},
				
				// 弹窗状态改变事件
				onNoticePopupChange(e) {
					console.log('公告弹窗状态改变：', e);
				},
				
				// 查看公告详情
				viewNotice(notice) {
					uni.navigateTo({
						url: '/pages/other/dano'
					});
				},
				
				// 加载数据
				async loadData() {
				try {
					// 获取轮播图数据
					await this.loadBannerData();
					
					// 获取彩种数据
					const response = await getLotteryTypes();
					if (response.code === 1 && response.data) {
						// 根据type_group分类彩种数据
						const wareLotteries = [];
						const personLotteries = [];
						const liveLotteries = [];
						const chessLotteries = [];
						
						response.data.forEach((lottery, index) => {
							const lotteryItem = {
								id: index + 1,
								name: lottery.type_name,
								desc: this.getLotteryDesc(lottery.type_code),
								category: lottery.category,
								icon: lottery.type_icon,
								type_code: lottery.type_code,
								type_group: lottery.type_group,
								type_desc: lottery.type_desc || ''
							};
							
							// 根据type_group分类到对应的数组
							switch (lottery.type_group) {
								case 'ware':
									wareLotteries.push(lotteryItem);
									break;
								case 'person':
									personLotteries.push(lotteryItem);
									break;
								case 'live':
									liveLotteries.push(lotteryItem);
									break;
								case 'chess':
									chessLotteries.push(lotteryItem);
									break;
								default:
									// 默认归类到彩票
									wareLotteries.push(lotteryItem);
									break;
							}
						});
						
						// 更新数据
						this.wareLotteries = wareLotteries;
						this.personLotteries = personLotteries;
						this.liveLotteries = liveLotteries;
						this.chessLotteries = chessLotteries;
					}
				} catch (error) {
					console.error('获取彩种数据失败:', error);
					uni.showToast({
						title: '获取彩种数据失败',
						icon: 'none'
					});
				}
			},
			
			// 获取轮播图数据
			async loadBannerData() {
				try {
					const response = await getBannerList({ limit: 5 });
					if (response.code === 1 && response.data && response.data.list) {
						this.bannerList = response.data.list.map(banner => ({
							id: banner.id,
							image: banner.image,
							title: banner.title,
							link_type: banner.link_type,
							link_url: banner.link_url
						}));
					}
				} catch (error) {
					console.error('获取轮播图数据失败:', error);
				}
			},
			
			// 轮播图点击
			onBannerClick(banner) {
				if (banner.link_type === 0) {
					// 无链接，不做任何操作
					return;
				} else if (banner.link_type === 1 && banner.link_url) {
					// 内部链接
					uni.navigateTo({
						url: banner.link_url
					});
				} else if (banner.link_type === 2 && banner.link_url) {
					// 外部链接
					// #ifdef H5
					window.open(banner.link_url, '_blank');
					// #endif
					// #ifndef H5
					uni.showModal({
						title: '提示',
						content: '即将跳转到外部链接：' + banner.link_url,
						confirmText: '确定',
						cancelText: '取消',
						success: (res) => {
							if (res.confirm) {
								plus.runtime.openURL(banner.link_url);
							}
						}
					});
					// #endif
				}
			},
			
			// 快捷入口点击
			onEntryClick(entry) {
				if (entry.url) {
					uni.navigateTo({
						url: entry.url
					});
				}
			},
			
			// 彩票点击
			onLotteryClick(lottery) {
				switch (lottery.category) {
					case 'QUICK':
						uni.navigateTo({
							url: `/pages/lottery/quickGame?type=${lottery.type_code}`
						});
						break;
					case 'WELFARE':
						uni.navigateTo({
							url: `/pages/lottery/welfare?type=${lottery.type_code}`
						});
						break;
					case 'live':
						uni.navigateTo({
							url: `/pages/lottery/live?type=${lottery.type_code}`
						});
						break;
					case 'chess':
						uni.navigateTo({
							url: `/pages/lottery/chess?type=${lottery.type_code}`
						});
						break;
					default:
						uni.navigateTo({
							url: `/pages/lottery/${lottery.type}`
						});
						break;
				}
			},
			
			// 获取彩种描述
			getLotteryDesc(typeCode) {
				const descMap = {
					'ff3d': '一分钟一期',
					'5f3d': '五分钟一期',
					'fc3d': '每日开奖',
					'3d': '每日开奖',
					'35': '每日开奖'
				};
				return descMap[typeCode] || '精彩刺激';
			},
			
			// 切换Tab
			switchTab(tab) {
				this.activeTab = tab;
				// 可以根据不同的tab执行不同的逻辑
				switch(tab) {
					case 'ware':
						// 彩票tab逻辑
						console.log('切换到彩票tab');
						break;
					case 'live':
						// 直播tab逻辑
						console.log('切换到直播tab');
						break;
					case 'chess':
						// 棋牌tab逻辑
						console.log('切换到棋牌tab');
						break;
					case 'person':
						// 电子tab逻辑
						console.log('切换到电子tab');
						break;
					default:
						console.log('未知tab类型:', tab);
						break;
				}
			},
			
			// 电子彩种点击事件
			onPersonLotteryClick(lottery) {
				// 电子彩种点击时显示敬请期待提示
				uni.showToast({
					title: '敬请期待',
					icon: 'none',
					duration: 2000
				});
			},
			
			// 获取当前激活tab对应的彩种数据
			getCurrentLotteries() {
				switch(this.activeTab) {
					case 'ware':
						return this.wareLotteries;
					case 'live':
						return this.liveLotteries;
					case 'chess':
						return this.chessLotteries;
					case 'person':
						return this.personLotteries;
					default:
						return [];
				}
			},
			
			// 获取tab配置信息
			getTabConfig() {
				return {
					ware: {
						name: 'BNB彩票',
						icon: 'integral',
						color: '#ff6b35',
						data: this.wareLotteries
					},
					live: {
						name: 'BNB直播',
						icon: 'play-circle',
						color: '#E91E63',
						data: this.liveLotteries
					},
					chess: {
						name: 'BNB棋牌',
						icon: 'coupon',
						color: '#2196F3',
						data: this.chessLotteries
					},
					person: {
						name: 'BNB电子',
						icon: 'play-circle',
						color: '#4CAF50',
						data: this.personLotteries
					}
				};
			}
		}
	}
</script>

<style scoped lang="scss">
	.container {
		background-color: #f8f9fa;
		color: #333333;
	}
	
	.scroll-container {
		padding: 20rpx;
		padding-top: 45rpx;
		padding-bottom: 40rpx;
		box-sizing: border-box;
	}
	
	// 主要内容区域
	.main-content {
		display: flex;
		gap: 20rpx;
		margin-top: 20rpx;
	}
	
	// 左侧Tab导航
	.left-tabs {
		width: 145rpx;
		flex-shrink: 0;
		background-color: #fff;
		border-radius: 12rpx;
		padding: 20rpx 0;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
		height: fit-content;
	}
	
	.tab-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 24rpx 16rpx;
		margin: 8rpx 16rpx;
		border-radius: 12rpx;
		transition: all 0.3s ease;
		cursor: pointer;
	}
	
	.tab-item.active {
		background-color: #c6e3ff;
		border: 1rpx solid #c6e3ff;
	}
	
	.tab-item:hover {
		background-color: #f8f9fa;
	}
	
	.tab-item.active:hover {
		background-color: #c6e3ff;
	}
	
	.tab-text {
		font-size: 30rpx;
		color: #666;
		margin-top: 8rpx;
	}
	
	.tab-item.active .tab-text {
		color: #ff6b35;
		font-weight: 600;
	}
	
	.tab-icon {
		width: 50rpx;
		height: 50rpx;
	}
	
	// 右侧内容区域
	.right-content {
		flex: 1;
		min-width: 0;
	}
	
	// 轮播图区域
	.banner-section {
		margin-bottom: 30rpx;
	}
	
	.banner-swiper {
		height: 300rpx;
		border-radius: 12rpx;
		overflow: hidden;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
	}
	
	.banner-image {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	
	// 登录注册和快捷入口容器
	.entry-container {
		display: flex;
		// gap: 20rpx;
		margin-bottom: 20rpx;
		align-items: stretch;
	}
	
	// 快捷入口
	.ware-entry {
		display: flex;
		justify-content: space-around;
		padding: 15rpx;
		background-color: #c6e3ff;
		border-radius: 0 45rpx 45rpx 0;
		border: 1rpx solid #e9ecef;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
		flex: 1;
	}
	
	.entry-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 3rpx;
		flex: 1;
		border-radius: 12rpx;
		cursor: pointer;
	}
	

	
	.entry-icon {
		width: 55rpx;
		height: 55rpx;
		border-radius: 50%;
		margin-bottom: 12rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	
	.entry-icon-image {
		width: 55rpx;
		height: 55rpx;
	}
	

	
	.entry-text {
		font-size: 24rpx;
		color: #333333;
		text-align: center;
		font-weight: 500;
		line-height: 1.3;
	}
	
	// 彩票区域
	.lottery-section {
		margin-bottom: 30rpx;
	}	
	
	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
		padding: 0 10rpx;
		min-height: 60rpx;
	}
	
	.header-left {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}
	
	.section-title {
		font-size: 32rpx;
		font-weight: 700;
		color: #ff6b35;
		line-height: 1.2;
	}
	

	
	.more-text {
		font-size: 24rpx;
		color: #333;
		transition: color 0.3s ease;
		padding: 8rpx 12rpx;
		border-radius: 12rpx;
		background-color: rgba(0, 0, 0, 0.05);
	}
	
	.more-text:active {
		color: #ff6b35;
	}
	
	// 彩票网格
	.lottery-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 16rpx;
	}
	
	.lottery-item {
		background-color: #fff2ed;
		border-radius: 35rpx;
		padding: 20rpx 12rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 12rpx;
		border: 1rpx solid #e9ecef;
		box-shadow: 0 12rpx 18rpx rgba(0, 0, 0, 0.1);
	}
	
	.lottery-icon {
		width: 105rpx;
		height: 105rpx;
		border-radius: 50%;
		background-color: #ff6b35;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-size: 36rpx;
		font-weight: 700;
		box-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.1);
	}
	
	.lottery-info {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 4rpx;
		width: 100%;
	}
	
	.lottery-name {
		font-size: 25rpx;
		font-weight: 700;
		color: #ff6b35;
		text-align: center;
		line-height: 1.3;
	}
	
	.lottery-type_desc {
		font-size: 20rpx;
		color: #999;
		text-align: center;
		line-height: 1.2;
	}
	
	.lottery-desc {
		font-size: 24rpx;
		color: #666;
		text-align: center;
		line-height: 1.4;
	}
	
	// 空状态样式
	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 80rpx 40rpx;
		background-color: #fff;
		border-radius: 12rpx;
		margin-top: 20rpx;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
	}
	
	.empty-text {
		font-size: 25rpx;
		color: #999;
		margin-top: 20rpx;
	}
	
	// 顶部下载横幅样式
	.download-banner {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		z-index: 999;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		padding: 20rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		border-radius: 0 0 20rpx 20rpx;
		box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.1);
	}
	
	.download-content {
		display: flex;
		align-items: center;
		flex: 1;
	}
	
	.download-logo {
		width: 60rpx;
		height: 60rpx;
		margin-right: 20rpx;
		border-radius: 8rpx;
	}
	
	.download-text {
		color: #fff;
		font-size: 28rpx;
		flex: 1;
		margin-right: 20rpx;
	}
	
	.download-btn {
		background-color: #ff1493;
		color: #fff;
		padding: 12rpx 24rpx;
		border-radius: 20rpx;
		font-size: 24rpx;
		font-weight: bold;
	}
	
	.close-btn {
		padding: 10rpx;
	}
	
	.close-icon {
		color: #fff;
		font-size: 40rpx;
		font-weight: bold;
	}
	
	// 登录注册区域样式
	.auth-section {
		background: linear-gradient(135deg, #88c5ff 0%, #50a9ff 100%);
		border-radius: 55rpx 0 0 55rpx;
		padding: 30rpx 20rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 20rpx;
		min-width: 200rpx;
		flex-shrink: 0;
	}
	
	// 用户信息区域
	.user-info-section {
		background: linear-gradient(135deg, #88c5ff 0%, #50a9ff 100%);
		border-radius: 45rpx 0 0 45rpx;
		padding: 30rpx 20rpx;
		display: flex;
		align-items: center;
		gap: 20rpx;
		min-width: 200rpx;
		flex-shrink: 0;
	}
	
	.user-avatar {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
		overflow: hidden;
		border: 3rpx solid rgba(255, 255, 255, 0.3);
	}
	
	.avatar-image {
		width: 100%;
		height: 100%;
	}
	
	.user-details {
		display: flex;
		flex-direction: column;
		gap: 8rpx;
	}
	
	.greeting-text {
		color: rgba(255, 255, 255, 0.9);
		font-size: 24rpx;
		font-weight: 400;
	}
	
	.nickname-text {
		color: #ffffff;
		font-size: 25rpx;
		font-weight: 600;
	}
	
	.auth-buttons {
		display: flex;
		gap: 20rpx;
	}
	
	.login-btn {
		background-color: #ffd700;
		color: #000;
		padding: 12rpx 15rpx;
		border-radius: 25rpx;
		font-size: 28rpx;
		font-weight: bold;
		min-width: 80rpx;
		text-align: center;
	}
	
	.register-btn {
		background-color: #ff1493;
		color: #e4e4e4;
		padding: 12rpx 15rpx;
		border: 2rpx solid #ff1493;
		border-radius: 25rpx;
		font-size: 28rpx;
		font-weight: bold;
		min-width: 80rpx;
		text-align: center;
	}
	
	.welcome-text {
		color: #fff;
		font-size: 20rpx;
	}
	
	// 公告弹窗样式
	.notice-popup {
		padding: 40rpx;
		min-width: 600rpx;
		display: flex;
		flex-direction: column;
	}
	
	.notice-header {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-bottom: 30rpx;
		padding:20rpx;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border-radius: 15rpx;
	}
	
	.notice-logo {
		width: 80rpx;
		height: 80rpx;
		margin-bottom: 15rpx;
		border-radius: 10rpx;
	}
	
	.notice-title {
		color: #fff;
		font-size: 32rpx;
		font-weight: bold;
	}
	
	.notice-list {
		// flex: 1;
		overflow-y: auto;
	}
	
	.notice-item {
		display: flex;
		align-items: center;
		padding: 20rpx;
		margin-bottom: 15rpx;
		background-color: #f8f9fa;
		border-radius: 12rpx;
		border-left: 4rpx solid #ff1493;
	}
	
	.notice-icon {
		margin-right: 15rpx;
		font-size: 24rpx;
	}
	
	.notice-text {
		flex: 1;
		font-size: 26rpx;
		color: #333;
		line-height: 1.4;
	}
	
	.notice-btn {
		background-color: #667eea;
		color: #fff;
		padding: 8rpx 20rpx;
		border-radius: 15rpx;
		font-size: 22rpx;
	}
	
	.notice-footer {
		padding: 20rpx 0;
		display: flex;
		justify-content: center;
		border-top: 1rpx solid #f0f0f0;
		margin-top: 20rpx;
		width: 100%;
	}
	
	.notice-footer uv-button {
		width: 100%;
		max-width: 400rpx;
	}
</style>