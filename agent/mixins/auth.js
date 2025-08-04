/**
 * 登录验证混入
 * 用于验证用户是否已登录，未登录则跳转到登录页面
 */
export default {
	data() {
		return {
			// 不需要登录验证的页面路径
			excludePages: [
				'/pages/users/login',
				'/pages/users/register'
			]
		}
	},
	methods: {
		/**
		 * 检查用户登录状态
		 */
		checkLoginStatus() {
			// 获取当前页面路径
			const pages = getCurrentPages();
			const currentPage = pages[pages.length - 1];
			const currentRoute = '/' + currentPage.route;
			
			// 如果是不需要验证的页面，直接返回
			if (this.excludePages.includes(currentRoute)) {
				return true;
			}
			
			// 检查token是否存在
			const token = uni.getStorageSync('ba-user-token');
			const userInfo = uni.getStorageSync('userInfo');
			
			if (!token || !userInfo) {
				// 未登录，跳转到登录页面
				uni.showToast({
					title: '请先登录',
					icon: 'none'
				});
				
				setTimeout(() => {
					uni.reLaunch({
						url: '/pages/users/login'
					});
				}, 1500);
				
				return false;
			}
			
			// 验证是否为代理商
			if (userInfo.is_agent !== 1) {
				// 清除登录信息
				uni.removeStorageSync('ba-user-token');
				uni.removeStorageSync('userInfo');
				
				uni.showToast({
					title: '您不是代理商，无法访问',
					icon: 'none'
				});
				
				setTimeout(() => {
					uni.reLaunch({
						url: '/pages/users/login'
					});
				}, 1500);
				
				return false;
			}
			
			return true;
		}
	},
	onShow() {
		// 页面显示时检查登录状态
		this.checkLoginStatus();
	}
};