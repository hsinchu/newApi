<template>
	<uv-navbar 
		:leftText="showBack ? '返回' : ''" 
		:leftTextStyle="leftTextStyle"
		:fixed="fixed" 
		:title="title" 
		:safeAreaInsetTop="computedSafeAreaInsetTop"
		:bgColor="bgColor"
		:titleStyle="titleStyle"
		:style="navBarStyle"
		@leftClick="handleLeftClick"
	>
		<template v-slot:left>
			<view class="uv-nav-slot">
				<!-- 返回按钮 -->
				<template v-if="showBack">
					<view class="back-button">
						<uv-icon name="arrow-left" :size="iconSize" color="#333333" @click="handleBackClick"></uv-icon>
					</view>
					<uv-line direction="column" :hairline="false" :length="lineLength" margin="0 8px" color="rgba(0,0,0,0.3)"></uv-line>
				</template>
				
				<!-- Logo图片或首页图标 -->
				<template v-if="logoUrl">
					<view class="logo-container">
						<image class="nav-logo" :src="logoUrl" @click="handleLogoClick"></image>
					</view>
				</template>
				<template v-else>
					<view class="home-button">
						<uv-icon name="home" :size="iconSize" color="#333333" @click="handleHomeClick"></uv-icon>
					</view>
				</template>
			</view>
		</template>
		
		<template v-slot:right v-if="rightText">
			<view class="right-text-container">
				<text class="right-text" :style="rightTextStyle">{{rightText}}</text>
			</view>
		</template>
	</uv-navbar>
</template>

<script>
export default {
	name: 'NavBar',
	props: {
		// 标题
		title: {
			type: String,
			default: '个人中心'
		},
		// 是否显示返回按钮
		showBack: {
			type: Boolean,
			default: false
		},
		// Logo图片地址
		logoUrl: {
			type: String,
			default: '/static/images/logo.jpg'
		},
		// 是否固定
		fixed: {
			type: Boolean,
			default: true
		},
		// 是否开启顶部安全区适配（可选，如果不传则自动检测APP环境）
		safeAreaInsetTop: {
			type: Boolean,
			default: null
		},
		// 背景颜色
		bgColor: {
			type: String,
			default: '#ffffff'
		},
		// 标题样式
		titleStyle: {
			type: Object,
			default: () => ({
				color: '#333333'
			})
		},
		// 左侧文字样式
		leftTextStyle: {
			type: Object,
			default: () => ({
				color: '#333333'
			})
		},
		// 右侧文字
		rightText: {
			type: String,
			default: ''
		},
		// 右侧文字样式
		rightTextStyle: {
			type: Object,
			default: () => ({
				fontSize: '28rpx'
			})
		}
	},
	computed: {
		// 自动检测是否需要开启顶部安全区适配
		computedSafeAreaInsetTop() {
			// 如果明确传入了safeAreaInsetTop参数，则使用传入的值
			if (this.safeAreaInsetTop !== null) {
				return this.safeAreaInsetTop;
			}
			// 自动检测：只在APP环境下开启安全区适配
			// #ifdef APP-PLUS
			return true;
			// #endif
			// #ifndef APP-PLUS
			return false;
			// #endif
		},
		
		// 计算导航栏样式
		navBarStyle() {
			let style = {
				backgroundColor: this.bgColor
			};
			
			// #ifdef APP-PLUS
			// 安卓APP特殊优化
			style.borderBottom = '1rpx solid rgba(255, 255, 255, 0.1)';
			style.boxShadow = '0 2rpx 8rpx rgba(0, 0, 0, 0.3)';
			// #ifdef APP-PLUS-NVUE
			style.borderBottom = '2rpx solid rgba(255, 255, 255, 0.15)';
			style.boxShadow = '0 4rpx 12rpx rgba(0, 0, 0, 0.4)';
			// #endif
			// #endif
			
			return style;
		},
		
		// 计算图标大小
		iconSize() {
			// #ifdef APP-PLUS
			// 安卓APP使用更大的图标
			// #ifdef APP-PLUS-NVUE
			return '24';
			// #endif
			// #ifndef APP-PLUS-NVUE
			return '22';
			// #endif
			// #endif
			
			// #ifdef H5
			return '18';
			// #endif
			
			// #ifdef MP
			return '20';
			// #endif
			
			// #ifndef APP-PLUS || H5 || MP
			return '19';
			// #endif
		},
		
		// 计算分割线长度
		lineLength() {
			// #ifdef APP-PLUS
			// #ifdef APP-PLUS-NVUE
			return '20';
			// #endif
			// #ifndef APP-PLUS-NVUE
			return '18';
			// #endif
			// #endif
			
			// #ifdef H5
			return '14';
			// #endif
			
			// #ifdef MP
			return '16';
			// #endif
			
			// #ifndef APP-PLUS || H5 || MP
			return '16';
			// #endif
		}
	},
	methods: {
		// 处理左侧点击事件
		handleLeftClick() {
			this.$emit('leftClick');
		},
		// 处理返回按钮点击
		handleBackClick() {
			this.$emit('backClick');
			// 默认返回上一页
			uni.navigateBack({
				delta: 1
			});
		},
		// 处理Logo点击
		handleLogoClick() {
			this.$emit('logoClick');
		},
		// 处理首页图标点击
		handleHomeClick() {
			this.$emit('homeClick');
			// 默认跳转到首页
			uni.switchTab({
				url: '/pages/order/order',
				fail: () => {
					uni.navigateTo({
						url: '/pages/order/order'
					});
				}
			});
		},
		// 处理右侧文字点击
		handleRightTextClick() {
			this.$emit('rightTextClick');
		}
	}
}
</script>

<style scoped>
.uv-nav-slot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 100%;
}

.back-button {
	display: flex;
	align-items: center;
	justify-content: center;
	/* #ifdef APP-PLUS */
	padding: 8rpx;
	border-radius: 50%;
	transition: background-color 0.3s ease;
	/* #ifdef APP-PLUS-NVUE */
	padding: 12rpx;
	border-radius: 8rpx;
	/* #endif */
	/* #endif */
	
	/* #ifdef H5 */
	padding: 4rpx;
	border-radius: 50%;
	/* #endif */
	
	/* #ifdef MP */
	padding: 6rpx;
	border-radius: 50%;
	/* #endif */
}

.back-button:active {
	/* #ifdef APP-PLUS */
	background-color: rgba(0, 0, 0, 0.1);
	/* #endif */
	
	/* #ifdef H5 */
	background-color: rgba(0, 0, 0, 0.08);
	/* #endif */
}

.home-button {
	display: flex;
	align-items: center;
	justify-content: center;
	/* #ifdef APP-PLUS */
	padding: 8rpx;
	border-radius: 50%;
	transition: background-color 0.3s ease;
	/* #ifdef APP-PLUS-NVUE */
	padding: 12rpx;
	border-radius: 8rpx;
	/* #endif */
	/* #endif */
	
	/* #ifdef H5 */
	padding: 4rpx;
	border-radius: 50%;
	/* #endif */
	
	/* #ifdef MP */
	padding: 6rpx;
	border-radius: 50%;
	/* #endif */
}

.home-button:active {
	/* #ifdef APP-PLUS */
	background-color: rgba(0, 0, 0, 0.1);
	/* #endif */
	
	/* #ifdef H5 */
	background-color: rgba(0, 0, 0, 0.08);
	/* #endif */
}

.logo-container {
	display: flex;
	align-items: center;
	justify-content: center;
	/* #ifdef APP-PLUS */
	padding: 4rpx;
	border-radius: 50%;
	overflow: hidden;
	/* #ifdef APP-PLUS-NVUE */
	padding: 6rpx;
	border-radius: 8rpx;
	/* #endif */
	/* #endif */
	
	/* #ifdef H5 */
	padding: 2rpx;
	border-radius: 50%;
	overflow: hidden;
	/* #endif */
	
	/* #ifdef MP */
	padding: 3rpx;
	border-radius: 50%;
	overflow: hidden;
	/* #endif */
}

.nav-logo {
	/* #ifdef APP-PLUS */
	width: 60rpx;
	height: 60rpx;
	border-radius: 50%;
	border: 2rpx solid rgba(0, 0, 0, 0.2);
	/* #ifdef APP-PLUS-NVUE */
	width: 68rpx;
	height: 68rpx;
	border-radius: 8rpx;
	border: 3rpx solid rgba(0, 0, 0, 0.25);
	/* #endif */
	/* #endif */
	
	/* #ifdef H5 */
	width: 56rpx;
	height: 56rpx;
	border-radius: 50%;
	border: 1rpx solid rgba(0, 0, 0, 0.15);
	/* #endif */
	
	/* #ifdef MP */
	width: 58rpx;
	height: 58rpx;
	border-radius: 50%;
	border: 1rpx solid rgba(0, 0, 0, 0.18);
	/* #endif */
	
	/* #ifndef APP-PLUS || H5 || MP */
	width: 30px;
	height: 30px;
	border-radius: 50%;
	/* #endif */
}

.right-text-container {
	display: flex;
	align-items: center;
	justify-content: center;
	padding-right: 25rpx;
}

.right-text {
	color: #ffffff;
	background:#4f46e5;
	padding:0 8rpx 5rpx 8rpx;
	border-radius:12rpx;
	font-size: 18rpx;
}
</style>