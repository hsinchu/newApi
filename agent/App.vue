<script>
	export default {
		onLaunch: function() {
			console.log('App Launch')
			// 设置dark主题
			uni.setTabBarStyle({
				color: '#808080',
				selectedColor: '#ff6b35 ',
				backgroundColor: '#f7f7f7',
				borderStyle: '#fff'
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