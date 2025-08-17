<template>
	<view class="webview-container">
		<!-- #ifdef H5 -->
		<view class="h5-iframe-container">
			<iframe :src="webviewUrl" frameborder="0" class="h5-iframe" @load="handleLoad" @error="handleError" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation"></iframe>
		</view>
		<!-- #endif -->
		<!-- #ifndef H5 -->
		<web-view :src="webviewUrl" @message="handleMessage" @error="handleError" @load="handleLoad"></web-view>
		<!-- #endif -->
	</view>
</template>

<script>
export default {
	data() {
		return {
			webviewUrl: '',
			pageTitle: ''
		}
	},
	
	onLoad(options) {
		// 获取传入的URL和标题
		if (options.url) {
			this.webviewUrl = decodeURIComponent(options.url);
		}
		if (options.title) {
			this.pageTitle = decodeURIComponent(options.title);
			// 设置导航栏标题
			uni.setNavigationBarTitle({
				title: this.pageTitle
			});
		}
	},
	
	methods: {
		// 处理webview消息
		handleMessage(event) {
			console.log('WebView消息:', event.detail.data);
		},
		
		// 处理加载错误
		handleError(event) {
			console.error('WebView加载错误:', event);
			uni.showToast({
				title: '页面加载失败',
				icon: 'none'
			});
		},
		
		// 处理加载完成
		handleLoad(event) {
			console.log('WebView加载完成:', event);
		}
	}
}
</script>

<style lang="scss">
.webview-container {
	width: 100%;
	height: 100vh;
	background-color: #fff;
}

/* #ifdef H5 */
.h5-iframe-container {
	width: 100%;
	height: 100%;
	position: relative;
}

.h5-iframe {
	width: 100%;
	height: 100%;
	border: none;
	outline: none;
}
/* #endif */
</style>