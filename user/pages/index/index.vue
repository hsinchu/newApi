<template>
	<view class="container">
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
			
			<!-- 快捷入口 -->
			<view class="quick-entry">
				<view class="entry-item" v-for="(entry, index) in quickEntries" :key="index" @click="onEntryClick(entry)">
					<view class="entry-icon">
						<uv-icon :name="entry.icon" size="24" :color="entry.color"></uv-icon>
					</view>
					<text class="entry-text">{{ entry.name }}</text>
				</view>
			</view>
			
			<!-- 快开彩 -->
			<view class="lottery-section" v-if="quickLotteries.length > 0">
				<view class="section-header">
					<view class="header-left">
						<uv-icon name="gift" size="18" color="#9C27B0"></uv-icon>
						<text class="section-title">快开彩</text>
					</view>
					<text class="more-text" @click="viewMore('quick')">更多 ></text>
				</view>
				<view class="lottery-grid">
					<view class="lottery-item" v-for="(lottery, index) in quickLotteries" :key="index" @click="onLotteryClick(lottery)">
						<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
						<text class="lottery-name">{{ lottery.name }}</text>
						<text class="lottery-desc">{{ lottery.desc }}</text>
					</view>
				</view>
			</view>
			
			<!-- 福利彩票 -->
			<view class="lottery-section">
				<view class="section-header">
					<view class="header-left">
						<uv-icon name="gift" size="18" color="#ff6b35"></uv-icon>
						<text class="section-title">福利彩票</text>
					</view>
					<text class="more-text" @click="viewMore('welfare')">更多 ></text>
				</view>
				<view class="lottery-grid">
					<view class="lottery-item" v-for="(lottery, index) in welfareLotteries" :key="index" @click="onLotteryClick(lottery)">
						<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
						<text class="lottery-name">{{ lottery.name }}</text>
						<text class="lottery-desc">{{ lottery.desc }}</text>
					</view>
				</view>
			</view>
			
			<!-- 体育彩票 -->
			<view class="lottery-section" v-if="sportsLotteries.length > 0">
				<view class="section-header">
					<view class="header-left">
						<uv-icon name="gift" size="18" color="#4CAF50"></uv-icon>
						<text class="section-title">体育彩票</text>
					</view>
					<text class="more-text" @click="viewMore('sports')">更多 ></text>
				</view>
				<view class="lottery-grid">
					<view class="lottery-item" v-for="(lottery, index) in sportsLotteries" :key="index" @click="onLotteryClick(lottery)">
						<image class="lottery-icon" :src="lottery.icon" mode="aspectFit"></image>
						<text class="lottery-name">{{ lottery.name }}</text>
						<text class="lottery-desc">{{ lottery.desc }}</text>
					</view>
				</view>
			</view>	
		</view>
	</view>
</template>

<script>
	import authMixin from '@/mixins/auth.js';
	import { getLotteryTypes } from '@/api/lottery/lottery.js';
	
	export default {
		mixins: [authMixin],
		data() {
			return {
				refreshing: false, // 刷新状态
				
				// 轮播图数据
				bannerList: [{
					id: 1,
					image: '/static/banner/1.jpeg',
					title: '新用户注册送彩金',
					url: '/pages/activity/register'
				}, {
					id: 2,
					image: '/static/banner/1.jpeg',
					title: '充值送好礼',
					url: '/pages/activity/recharge'
				}, {
					id: 3,
					image: '/static/banner/1.jpeg',
					title: '每日签到领奖励',
					url: '/pages/activity/signin'
				}],
				
				// 快捷入口
				quickEntries: [{
					id: 1,
					name: '账变',
					icon: 'list',
					color: '#ff6b35',
					url: '/pages/users/moneylog'
				}, {
					id: 2,
					name: '充值',
					icon: 'woman',
					color: '#4CAF50',
					url: '/pages/users/charge'
				}, {
					id: 3,
					name: '提现',
					icon: 'man',
					color: '#2196F3',
					url: '/pages/users/withdraw'
				}, {
					id: 4,
					name: '客服',
					icon: 'kefu-ermai',
					color: '#9C27B0',
					url: '/pages/service/service'
				}],
				
				// 福利彩票
				welfareLotteries: [],
				
				// 体育彩票
				sportsLotteries: [],
				
				// 快开彩
				quickLotteries: []
				

			}
		},
		

		
		onLoad() {
			// 页面加载时获取数据
			this.loadData();
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
			// 加载数据
			async loadData() {
				try {
					// 获取彩种数据
					const response = await getLotteryTypes();
					if (response.code === 1 && response.data) {
						// 根据category分类彩种数据
						const welfareLotteries = [];
						const sportsLotteries = [];
						const quickLotteries = [];
						
						response.data.forEach((lottery, index) => {
							const lotteryItem = {
								id: index + 1,
								name: lottery.type_name,
								desc: this.getLotteryDesc(lottery.type_code),
								icon: lottery.type_icon,
								type: this.getLotteryType(lottery.category, lottery.type_code)
							};
							
							if (lottery.category === 'WELFARE') {
								welfareLotteries.push(lotteryItem);
							} else if (lottery.category === 'QUICK') {
								// QUICK类型分类到快开彩
								quickLotteries.push(lotteryItem);
							} else if (lottery.category === 'SPORTS') {
								// SPORTS类型分类到体育彩票
								sportsLotteries.push(lotteryItem);
							}
						});
						
						this.welfareLotteries = welfareLotteries;
						this.sportsLotteries = sportsLotteries;
						this.quickLotteries = quickLotteries;
					}
				} catch (error) {
					console.error('获取彩种数据失败:', error);
					uni.showToast({
						title: '获取彩种数据失败',
						icon: 'none'
					});
				}
			},
			
			// 轮播图点击
			onBannerClick(banner) {
				if (banner.url) {
					uni.navigateTo({
						url: banner.url
					});
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
				uni.navigateTo({
					url: `/pages/lottery/${lottery.type}`
				});
			},
			
			// 查看更多
			viewMore(category) {
				// 根据分类跳转到对应页面
				if (category === 'welfare') {
					uni.navigateTo({
						url: '/pages/lottery/welfare'
					});
				} else if (category === 'sports') {
					uni.navigateTo({
						url: '/pages/lottery/welfare'
					});
				} else if (category === 'quick') {
					uni.navigateTo({
						url: '/pages/lottery/quickGame'
					});
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
			
			// 获取彩种跳转类型
			getLotteryType(category, typeCode) {
				// 特定彩种跳转规则
				if (typeCode === '3d' || typeCode === '35') {
					return `welfare?type=${typeCode}`;
				}
				// 快开彩跳转到quickGame
				if (category === 'QUICK') {
					return `quickGame?type=${typeCode}`;
				}
				// 其他福利彩票和体育彩票跳转到welfare
				if (category === 'WELFARE' || category === 'SPORTS') {
					return `welfare?type=${typeCode}`;
				}
				// 默认跳转
				return `lottery?type=${typeCode}`;
			},
		}
	}
</script>

<style scoped lang="scss">
	.container {
		background: linear-gradient(180deg, #0f1419 0%, #1a1f2e 50%, #0f1419 100%);
		color: #e1e1e1;
		position: relative;
	}
	
	.container::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 400rpx;
		background: radial-gradient(ellipse at center top, rgba(255, 107, 53, 0.15) 0%, transparent 70%);
		pointer-events: none;
		z-index: 0;
	}
	
	.scroll-container {
		
		padding: 20rpx;
		padding-bottom: 40rpx;
		box-sizing: border-box;
		background: transparent;
	}
	
	// 轮播图区域
	.banner-section {
		margin-bottom: 30rpx;
	}
	
	.banner-swiper {
		height: 300rpx;
		border-radius: 16rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.2);
	}
	
	.banner-swiper::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.1) 0%, 
			transparent 30%, 
			transparent 70%, 
			rgba(255, 107, 53, 0.05) 100%);
		pointer-events: none;
		z-index: 1;
	}
	
	.banner-swiper:hover {
		transform: translateY(-4rpx);
		box-shadow: 
			0 16rpx 48rpx rgba(0, 0, 0, 0.5),
			0 0 0 3rpx rgba(255, 107, 53, 0.4),
			inset 0 0 0 2rpx rgba(255, 255, 255, 0.2);
	}
	
	.banner-image {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: all 0.5s ease;
		position: relative;
		z-index: 2;
		filter: brightness(1.1) contrast(1.05) saturate(1.1);
	}
	
	.banner-image:hover {
		transform: scale(1.05);
		filter: brightness(1.2) contrast(1.1) saturate(1.2);
	}
	
	// 快捷入口
	.quick-entry {
		display: flex;
		justify-content: space-around;
		padding: 32rpx 24rpx;
		margin: 0 20rpx 20rpx;
		background: linear-gradient(135deg, 
			#1a1f2e 0%, 
			#252b3a 25%, 
			#1e2332 50%, 
			#252b3a 75%, 
			#1a1f2e 100%);
		border-radius: 24rpx;
		border: 2rpx solid transparent;
		background-clip: padding-box;
		box-shadow: 
			0 8rpx 32rpx rgba(0, 0, 0, 0.4),
			0 0 0 1rpx rgba(255, 107, 53, 0.1),
			inset 0 1rpx 0 rgba(255, 255, 255, 0.1),
			inset 0 -1rpx 0 rgba(0, 0, 0, 0.2);
		position: relative;
		overflow: hidden;
		transition: all 0.3s ease;
		
		/* #ifdef APP-PLUS */
		padding: 40rpx 28rpx;
		border-radius: 28rpx;
		margin: 0 24rpx 24rpx;
		box-shadow: 
			0 12rpx 48rpx rgba(0, 0, 0, 0.5),
			0 0 0 2rpx rgba(255, 107, 53, 0.15),
			inset 0 2rpx 0 rgba(255, 255, 255, 0.15);
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		padding: 44rpx 32rpx;
		border-radius: 32rpx;
		margin: 0 28rpx 28rpx;
		box-shadow: 
			0 16rpx 64rpx rgba(0, 0, 0, 0.6),
			0 0 0 3rpx rgba(255, 107, 53, 0.2),
			inset 0 3rpx 0 rgba(255, 255, 255, 0.2);
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		padding: 28rpx 20rpx;
		border-radius: 20rpx;
		margin: 0 16rpx 16rpx;
		box-shadow: 
			0 6rpx 24rpx rgba(0, 0, 0, 0.3),
			0 0 0 1rpx rgba(255, 107, 53, 0.08),
			inset 0 1rpx 0 rgba(255, 255, 255, 0.08);
		/* #endif */
		
		/* #ifdef MP */
		padding: 32rpx 20rpx;
		border-radius: 20rpx;
		/* #endif */
	}
	
	.quick-entry::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.05) 0%, 
			transparent 30%, 
			transparent 70%, 
			rgba(255, 193, 7, 0.03) 100%);
		pointer-events: none;
		z-index: 1;
		opacity: 0;
		transition: opacity 0.3s ease;
	}
	
	.quick-entry:hover::before {
		opacity: 1;
	}
	
	.quick-entry:hover {
		transform: translateY(-4rpx);
		box-shadow: 
			0 16rpx 48rpx rgba(0, 0, 0, 0.6),
			0 0 0 2rpx rgba(255, 107, 53, 0.2),
			inset 0 2rpx 0 rgba(255, 255, 255, 0.15),
			0 0 20rpx rgba(255, 107, 53, 0.1);
	}
	
	.entry-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 16rpx;
		flex: 1;
		padding: 20rpx;
		border-radius: 16rpx;
		transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
		cursor: pointer;
		position: relative;
		z-index: 2;
		background: rgba(255, 255, 255, 0.02);
		border: 1rpx solid rgba(255, 107, 53, 0.1);
		
		/* #ifdef APP-PLUS */
		gap: 20rpx;
		padding: 24rpx;
		border-radius: 20rpx;
		border: 2rpx solid rgba(255, 107, 53, 0.15);
		/* #endif */
		
		/* #ifdef H5 */
		gap: 14rpx;
		padding: 16rpx;
		border-radius: 14rpx;
		/* #endif */
	}
	
	.entry-item::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.1) 0%, 
			rgba(255, 193, 7, 0.05) 100%);
		border-radius: inherit;
		opacity: 0;
		transition: opacity 0.3s ease;
		pointer-events: none;
		z-index: -1;
	}
	
	.entry-item:hover::before {
		opacity: 1;
	}
	
	.entry-item:hover {
		transform: translateY(-6rpx) scale(1.05);
		box-shadow: 
			0 12rpx 32rpx rgba(0, 0, 0, 0.4),
			0 0 0 2rpx rgba(255, 107, 53, 0.3),
			0 0 20rpx rgba(255, 107, 53, 0.2);
		border-color: rgba(255, 107, 53, 0.4);
	}
	
	.entry-item:hover .entry-icon {
		transform: scale(1.1) rotate(5deg);
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.2) 0%, 
			rgba(255, 193, 7, 0.15) 100%);
		border-color: rgba(255, 107, 53, 0.4);
		box-shadow: 
			0 8rpx 24rpx rgba(255, 107, 53, 0.3),
			0 0 0 2rpx rgba(255, 107, 53, 0.2),
			inset 0 2rpx 4rpx rgba(255, 255, 255, 0.1);
	}
	
	.entry-item:hover .entry-text {
		color: #ff6b35;
		text-shadow: 0 0 8rpx rgba(255, 107, 53, 0.5);
		font-weight: 600;
	}
	
	.entry-item:active {
		transform: translateY(-2rpx) scale(0.98);
		transition: all 0.1s ease;
		opacity: 0.9;
	}
	
	.entry-icon {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.15) 0%, 
			rgba(255, 193, 7, 0.1) 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
		border: 2rpx solid rgba(255, 107, 53, 0.2);
		box-shadow: 
			0 4rpx 16rpx rgba(0, 0, 0, 0.2),
			0 0 0 1rpx rgba(255, 107, 53, 0.1),
			inset 0 1rpx 2rpx rgba(255, 255, 255, 0.1);
		position: relative;
		overflow: hidden;
		
		/* #ifdef APP-PLUS */
		width: 96rpx;
		height: 96rpx;
		border: 3rpx solid rgba(255, 107, 53, 0.3);
		box-shadow: 
			0 6rpx 20rpx rgba(0, 0, 0, 0.3),
			0 0 0 2rpx rgba(255, 107, 53, 0.15),
			inset 0 2rpx 4rpx rgba(255, 255, 255, 0.15);
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		width: 104rpx;
		height: 104rpx;
		border: 4rpx solid rgba(255, 107, 53, 0.4);
		box-shadow: 
			0 8rpx 24rpx rgba(0, 0, 0, 0.4),
			0 0 0 3rpx rgba(255, 107, 53, 0.2),
			inset 0 3rpx 6rpx rgba(255, 255, 255, 0.2);
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		width: 72rpx;
		height: 72rpx;
		box-shadow: 
			0 3rpx 12rpx rgba(0, 0, 0, 0.15),
			0 0 0 1rpx rgba(255, 107, 53, 0.08),
			inset 0 1rpx 2rpx rgba(255, 255, 255, 0.08);
		/* #endif */
		
		/* #ifdef MP */
		width: 84rpx;
		height: 84rpx;
		box-shadow: 
			0 4rpx 16rpx rgba(0, 0, 0, 0.2),
			0 0 0 1rpx rgba(255, 107, 53, 0.1),
			inset 0 1rpx 2rpx rgba(255, 255, 255, 0.1);
		/* #endif */
	}
	
	.entry-icon::before {
		content: '';
		position: absolute;
		top: -2rpx;
		left: -2rpx;
		right: -2rpx;
		bottom: -2rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.2) 0%, 
			rgba(255, 193, 7, 0.1) 50%, 
			rgba(255, 107, 53, 0.2) 100%);
		z-index: -1;
		opacity: 0;
		transition: opacity 0.3s ease;
		animation: iconPulse 3s ease-in-out infinite;
	}
	
	@keyframes iconPulse {
		0%, 100% {
			opacity: 0.3;
			transform: scale(0.95);
		}
		50% {
			opacity: 0.6;
			transform: scale(1.05);
		}
	}
	
	.entry-text {
		font-size: 24rpx;
		color: #e1e1e1;
		text-align: center;
		font-weight: 500;
		line-height: 1.3;
		letter-spacing: 0.5rpx;
		transition: all 0.3s ease;
		position: relative;
		z-index: 2;
		text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
		
		/* #ifdef APP-PLUS */
		font-size: 28rpx;
		font-weight: 600;
		letter-spacing: 0.8rpx;
		text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.4);
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		font-size: 30rpx;
		letter-spacing: 1rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		font-size: 22rpx;
		letter-spacing: 0.3rpx;
		text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.2);
		/* #endif */
		
		/* #ifdef MP */
		font-size: 26rpx;
		letter-spacing: 0.5rpx;
		text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.3);
		/* #endif */
	}
	
	.entry-text::after {
		content: '';
		position: absolute;
		bottom: -4rpx;
		left: 50%;
		transform: translateX(-50%);
		width: 0;
		height: 2rpx;
		background: linear-gradient(90deg, #ff6b35 0%, #ffc107 100%);
		border-radius: 1rpx;
		transition: width 0.3s ease;
		opacity: 0;
	}
	
	.entry-item:hover .entry-text::after {
		width: 80%;
		opacity: 1;
	}
	
	/* 全局动画效果 */
	@keyframes fadeInUp {
		from {
			opacity: 0;
			transform: translateY(30rpx);
		}
		to {
			opacity: 1;
			transform: translateY(0);
		}
	}
	
	@keyframes shimmer {
		0% {
			background-position: -200% 0;
		}
		100% {
			background-position: 200% 0;
		}
	}
	
	.lottery-item {
		animation: fadeInUp 0.6s ease-out;
	}
	
	.lottery-item:nth-child(1) { animation-delay: 0.1s; }
	.lottery-item:nth-child(2) { animation-delay: 0.2s; }
	.lottery-item:nth-child(3) { animation-delay: 0.3s; }
	.lottery-item:nth-child(4) { animation-delay: 0.4s; }
	.lottery-item:nth-child(5) { animation-delay: 0.5s; }
	.lottery-item:nth-child(6) { animation-delay: 0.6s; }
	
	/* 添加微妙的闪烁效果 */
	.section-title {
		background-size: 200% 100%;
		animation: shimmer 3s ease-in-out infinite;
	}
	
	/* 响应式优化 */
	@media (max-width: 750rpx) {
		.lottery-grid {
			grid-template-columns: repeat(2, 1fr);
			gap: 20rpx;
		}
	}
	
	@media (max-width: 500rpx) {
		.lottery-grid {
			grid-template-columns: 1fr;
			gap: 16rpx;
		}
		
		.lottery-item {
			padding: 24rpx 16rpx;
		}
	}
	
	// 彩票区域
	.lottery-section {
		margin-bottom: 30rpx;
		padding: 0 20rpx;
		position: relative;
		z-index: 3;
		
		/* #ifdef APP-PLUS */
		margin-bottom: 36rpx;
		padding: 0 24rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		margin-bottom: 40rpx;
		padding: 0 28rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		margin-bottom: 24rpx;
		padding: 0 16rpx;
		/* #endif */
		
		/* #ifdef MP */
		margin-bottom: 32rpx;
		padding: 0 20rpx;
		/* #endif */
	}
	
	.lottery-section::before {
		content: '';
		position: absolute;
		top: -20rpx;
		left: 10rpx;
		right: 10rpx;
		bottom: -20rpx;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.05) 0%, 
			rgba(255, 193, 7, 0.03) 50%, 
			rgba(255, 107, 53, 0.05) 100%);
		border-radius: 32rpx;
		pointer-events: none;
		z-index: -1;
		filter: blur(1rpx);
	}
	
	.section-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
		padding: 0 10rpx;
		min-height: 60rpx;
		
		/* #ifdef APP-PLUS */
		margin-bottom: 24rpx;
		padding: 0 12rpx;
		min-height: 68rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		margin-bottom: 28rpx;
		padding: 0 16rpx;
		min-height: 72rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		margin-bottom: 16rpx;
		padding: 0 8rpx;
		min-height: 56rpx;
		/* #endif */
		
		/* #ifdef MP */
		margin-bottom: 20rpx;
		padding: 0 10rpx;
		min-height: 64rpx;
		/* #endif */
	}
	
	.header-left {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}
	
	.section-title {
		font-size: 32rpx;
		font-weight: 700;
		background: linear-gradient(135deg, #ff6b35 0%, #ffc107 50%, #ff6b35 100%);
		-webkit-background-clip: text;
		-webkit-text-fill-color: transparent;
		background-clip: text;
		line-height: 1.2;
		letter-spacing: 0.5rpx;
		position: relative;
		text-shadow: 0 2rpx 8rpx rgba(255, 107, 53, 0.3);
		
		/* #ifdef APP-PLUS */
		font-size: 36rpx;
		font-weight: 700;
		letter-spacing: 1rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		font-size: 38rpx;
		letter-spacing: 1.5rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		font-size: 30rpx;
		/* #endif */
		
		/* #ifdef MP */
		font-size: 34rpx;
		/* #endif */
	}
	
	.section-title::after {
		content: '';
		position: absolute;
		bottom: -8rpx;
		left: 0;
		width: 60rpx;
		height: 4rpx;
		background: linear-gradient(90deg, #ff6b35 0%, #ffc107 100%);
		border-radius: 2rpx;
		box-shadow: 0 2rpx 8rpx rgba(255, 107, 53, 0.4);
	}
	
	.more-text {
		font-size: 24rpx;
		color: #999;
		transition: color 0.3s ease;
		padding: 8rpx 12rpx;
		border-radius: 12rpx;
		background-color: rgba(255, 255, 255, 0.05);
		
		/* #ifdef APP-PLUS */
		font-size: 26rpx;
		padding: 10rpx 16rpx;
		border-radius: 16rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		font-size: 28rpx;
		padding: 12rpx 18rpx;
		border-radius: 18rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		font-size: 22rpx;
		padding: 6rpx 10rpx;
		border-radius: 10rpx;
		/* #endif */
		
		/* #ifdef MP */
		font-size: 24rpx;
		padding: 8rpx 14rpx;
		border-radius: 14rpx;
		/* #endif */
	}
	
	.more-text:active {
		color: #ff6b35;
	}
	
	// 彩票网格
	.lottery-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 16rpx;
		
		/* #ifdef APP-PLUS */
		gap: 20rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		gap: 24rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		gap: 12rpx;
		/* #endif */
		
		/* #ifdef MP */
		gap: 16rpx;
		/* #endif */
	}
	
	.lottery-item {
		background: linear-gradient(135deg, 
			#1a1f2e 0%, 
			#252b3a 25%, 
			#1e2332 50%, 
			#252b3a 75%, 
			#1a1f2e 100%);
		border-radius: 16rpx;
		padding: 20rpx 12rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 12rpx;
		border: 2rpx solid transparent;
		background-clip: padding-box;
		box-shadow: 
			0 8rpx 32rpx rgba(0, 0, 0, 0.4),
			0 0 0 1rpx rgba(255, 107, 53, 0.1),
			inset 0 1rpx 0 rgba(255, 255, 255, 0.1),
			inset 0 -1rpx 0 rgba(0, 0, 0, 0.2);
		transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
		position: relative;
		overflow: hidden;
		
		/* #ifdef APP-PLUS */
		padding: 40rpx 28rpx;
		border-radius: 28rpx;
		box-shadow: 
			0 12rpx 48rpx rgba(0, 0, 0, 0.5),
			0 0 0 2rpx rgba(255, 107, 53, 0.15),
			inset 0 2rpx 0 rgba(255, 255, 255, 0.15);
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		padding: 44rpx 32rpx;
		border-radius: 32rpx;
		box-shadow: 
			0 16rpx 64rpx rgba(0, 0, 0, 0.6),
			0 0 0 3rpx rgba(255, 107, 53, 0.2),
			inset 0 3rpx 0 rgba(255, 255, 255, 0.2);
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		padding: 24rpx 16rpx;
		border-radius: 16rpx;
		box-shadow: 
			0 6rpx 24rpx rgba(0, 0, 0, 0.3),
			0 0 0 1rpx rgba(255, 107, 53, 0.08),
			inset 0 1rpx 0 rgba(255, 255, 255, 0.08);
		/* #endif */
		
		/* #ifdef MP */
		padding: 32rpx 20rpx;
		border-radius: 20rpx;
		/* #endif */
	}
	
	.lottery-item::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.08) 0%, 
			transparent 30%, 
			transparent 70%, 
			rgba(255, 193, 7, 0.05) 100%);
		pointer-events: none;
		z-index: 1;
		opacity: 0;
		transition: opacity 0.3s ease;
	}
	
	.lottery-item:hover::before {
		opacity: 1;
	}
	
	.lottery-item:hover {
		transform: translateY(-8rpx) scale(1.02);
		box-shadow: 
			0 20rpx 60rpx rgba(0, 0, 0, 0.6),
			0 0 0 3rpx rgba(255, 107, 53, 0.3),
			inset 0 2rpx 0 rgba(255, 255, 255, 0.2),
			0 0 40rpx rgba(255, 107, 53, 0.2);
		border-color: rgba(255, 107, 53, 0.3);
	}
	
	.lottery-item:hover .lottery-icon {
		transform: scale(1.1) rotate(5deg);
		box-shadow: 
			0 12rpx 32rpx rgba(255, 107, 53, 0.6),
			0 0 0 4rpx rgba(255, 255, 255, 0.2),
			inset 0 3rpx 6rpx rgba(255, 255, 255, 0.4);
	}
	
	.lottery-item:hover .lottery-name {
		color: #ff6b35;
		text-shadow: 0 0 8rpx rgba(255, 107, 53, 0.5);
	}
	
	.lottery-item:hover .lottery-desc {
		color: #ffc107;
	}
	
	.lottery-item:active {
		transform: scale(0.95) translateY(-4rpx);
		box-shadow: 
			0 4rpx 16rpx rgba(0, 0, 0, 0.4),
			0 0 0 2rpx rgba(255, 107, 53, 0.4),
			inset 0 1rpx 0 rgba(255, 255, 255, 0.15);
	}
	
	.lottery-icon {
		width: 64rpx;
		height: 64rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, #ff6b35 0%, #ffc107 50%, #ff6b35 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		font-size: 36rpx;
		font-weight: 700;
		box-shadow: 
			0 8rpx 24rpx rgba(255, 107, 53, 0.4),
			0 0 0 3rpx rgba(255, 255, 255, 0.1),
			inset 0 2rpx 4rpx rgba(255, 255, 255, 0.3),
			inset 0 -2rpx 4rpx rgba(0, 0, 0, 0.2);
		position: relative;
		z-index: 2;
		transition: all 0.3s ease;
		
		/* #ifdef APP-PLUS */
		width: 104rpx;
		height: 104rpx;
		font-size: 44rpx;
		box-shadow: 
			0 12rpx 32rpx rgba(255, 107, 53, 0.5),
			0 0 0 4rpx rgba(255, 255, 255, 0.15),
			inset 0 3rpx 6rpx rgba(255, 255, 255, 0.4);
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		width: 112rpx;
		height: 112rpx;
		font-size: 48rpx;
		box-shadow: 
			0 16rpx 40rpx rgba(255, 107, 53, 0.6),
			0 0 0 5rpx rgba(255, 255, 255, 0.2),
			inset 0 4rpx 8rpx rgba(255, 255, 255, 0.5);
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		width: 80rpx;
		height: 80rpx;
		font-size: 32rpx;
		box-shadow: 
			0 6rpx 18rpx rgba(255, 107, 53, 0.3),
			0 0 0 2rpx rgba(255, 255, 255, 0.08),
			inset 0 1rpx 2rpx rgba(255, 255, 255, 0.2);
		/* #endif */
		
		/* #ifdef MP */
		width: 92rpx;
		height: 92rpx;
		font-size: 38rpx;
		box-shadow: 
			0 8rpx 24rpx rgba(255, 107, 53, 0.4),
			0 0 0 3rpx rgba(255, 255, 255, 0.1),
			inset 0 2rpx 4rpx rgba(255, 255, 255, 0.3);
		/* #endif */
	}
	
	.lottery-icon::before {
		content: '';
		position: absolute;
		top: -4rpx;
		left: -4rpx;
		right: -4rpx;
		bottom: -4rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, 
			rgba(255, 107, 53, 0.3) 0%, 
			rgba(255, 193, 7, 0.2) 50%, 
			rgba(255, 107, 53, 0.3) 100%);
		z-index: -1;
		animation: iconGlow 2s ease-in-out infinite;
	}
	
	@keyframes iconGlow {
		0%, 100% {
			opacity: 0.5;
			transform: scale(1);
		}
		50% {
			opacity: 0.8;
			transform: scale(1.1);
		}
	}
	
	.lottery-name {
		font-size: 30rpx;
		font-weight: 700;
		color: #fff;
		text-align: center;
		line-height: 1.3;
		letter-spacing: 0.5rpx;
		position: relative;
		z-index: 2;
		text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.5);
		margin-bottom: 8rpx;
		
		/* #ifdef APP-PLUS */
		font-size: 34rpx;
		font-weight: 700;
		letter-spacing: 1rpx;
		margin-bottom: 10rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		font-size: 36rpx;
		font-weight: 700;
		letter-spacing: 1.5rpx;
		margin-bottom: 12rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		font-size: 28rpx;
		margin-bottom: 6rpx;
		/* #endif */
		
		/* #ifdef MP */
		font-size: 32rpx;
		margin-bottom: 8rpx;
		/* #endif */
	}
	
	.lottery-desc {
		font-size: 24rpx;
		color: #bbb;
		text-align: center;
		line-height: 1.4;
		letter-spacing: 0.3rpx;
		position: relative;
		z-index: 2;
		opacity: 0.9;
		transition: color 0.3s ease;
		
		/* #ifdef APP-PLUS */
		font-size: 28rpx;
		color: #ccc;
		letter-spacing: 0.6rpx;
		/* 安卓APP特殊优化 */
		/* #ifdef APP-PLUS-NVUE */
		font-size: 30rpx;
		color: #ddd;
		letter-spacing: 0.9rpx;
		/* #endif */
		/* #endif */
		
		/* #ifdef H5 */
		font-size: 22rpx;
		/* #endif */
		
		/* #ifdef MP */
		font-size: 26rpx;
		/* #endif */
	}
	

	


</style>
