<script>
	export default {
		onLaunch: function() {
			console.log('App Launch')
			// 设置dark主题
			uni.setTabBarStyle({
				color: '#999999',
				selectedColor: '#007AFF',
				backgroundColor: '#1C1C1E',
				borderStyle: 'black'
			})
		},
		onShow: function() {
			console.log('App Show')
		},
		onHide: function() {
			console.log('App Hide')
		}
	}
</script>

<style>
	/*每个页面公共css */
	page {
		background-color: #252525;
		color: #FFFFFF;
	}
	
	/* 全局dark主题样式 */
	.uni-page-body {
		background-color: #1a1a1a;
		color: #FFFFFF;
	}
	
	/* 导航栏样式 */
	.uni-navbar {
		background-color: #1C1C1E !important;
		color: #FFFFFF !important;
	}
	
	/* 状态栏样式 */
	.uni-status-bar {
		background-color: #000000 !important;
	}
</style>
