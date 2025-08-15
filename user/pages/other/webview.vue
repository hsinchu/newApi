<template>
	<view class="webview-container">
		<web-view :src="url" @message="handleMessage" @error="handleError" @load="handleLoad"></web-view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			url: '',
			title: ''
		}
	},
	
	onLoad(options) {
		// 获取传入的URL和标题
		if (options.url) {
			this.url = decodeURIComponent(options.url);
		}
		if (options.title) {
			this.title = decodeURIComponent(options.title);
			// 设置导航栏标题
			uni.setNavigationBarTitle({
				title: this.title
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
</style>