/**
 * 格式化时间为相对时间
 * @param {string|Date} time 时间
 * @return {string} 格式化后的时间字符串
 */
const formatTime = (time) => {
	// 如果时间戳是秒级（10位数字），转换为毫秒级
	let timestamp = time
	if (typeof time === 'number' && time.toString().length === 10) {
		timestamp = time * 1000
	} else if (typeof time === 'string' && time.length === 10 && !isNaN(time)) {
		timestamp = parseInt(time) * 1000
	}
	
	const date = new Date(timestamp)
	const now = new Date()
	const diff = now.getTime() - date.getTime()
	
	if (diff < 60000) {
		return '刚刚'
	} else if (diff < 3600000) {
		return Math.floor(diff / 60000) + '分钟前'
	} else if (diff < 86400000) {
		return Math.floor(diff / 3600000) + '小时前'
	} else {
		return date.toLocaleDateString() + ' ' + date.toLocaleTimeString().slice(0, 5)
	}
}

/**
 * 计算vtabs组件的高度
 * @param {Object} options 配置选项
 * @param {number} options.headerHeight 头部内容高度(px)，如果不传则会尝试动态获取
 * @param {string} options.headerSelector 头部内容选择器，用于动态获取高度
 * @param {number} options.containerMargin 容器边距(px)，默认10
 * @param {boolean} options.includeTabbar 是否包含tabbar高度，默认false(App环境下不需要)
 * @param {Function} options.callback 计算完成后的回调函数
 * @param {Object} options.context Vue组件实例，用于SelectorQuery
 * @return {Promise<Object>} 返回计算结果 { vtabsHeight, hdHeight }
 */
const calculateVtabsHeight = (options = {}) => {
	return new Promise((resolve, reject) => {
		const {
			headerHeight,
			headerSelector = '.balance-section',
			containerMargin = 10,
			includeTabbar = false,
			callback,
			context
		} = options;
		
		// 获取系统信息
		uni.getSystemInfo({
			success: (systemInfo) => {
				// 如果提供了头部高度，直接计算
				if (headerHeight && typeof headerHeight === 'number') {
					const result = calculateWithHeight(systemInfo, headerHeight, containerMargin, includeTabbar);
					if (callback) callback(result);
					resolve(result);
					return;
				}
				
				// 如果提供了context和选择器，动态获取高度
				if (context && headerSelector) {
					const query = uni.createSelectorQuery().in(context);
					query.select(headerSelector).boundingClientRect();
					query.exec((result) => {
						let actualHeight = 160; // 默认高度
						if (result && result[0] && result[0].height > 0) {
							actualHeight = Math.ceil(result[0].height);
							console.log('使用实际测量的头部高度:', actualHeight);
						} else {
							console.log('使用默认头部高度:', actualHeight);
						}
						
						const calcResult = calculateWithHeight(systemInfo, actualHeight, containerMargin, includeTabbar);
						if (callback) callback(calcResult);
						resolve(calcResult);
					});
				} else {
					// 使用默认高度计算
					const result = calculateWithHeight(systemInfo, 160, containerMargin, includeTabbar);
					if (callback) callback(result);
					resolve(result);
				}
			},
			fail: (error) => {
				console.error('获取系统信息失败:', error);
				reject(error);
			}
		});
	});
};

/**
 * 使用指定高度进行vtabs高度计算
 * @param {Object} systemInfo 系统信息
 * @param {number} headerHeightPx 头部高度(px)
 * @param {number} containerMargin 容器边距(px)
 * @param {boolean} includeTabbar 是否包含tabbar高度
 * @return {Object} 计算结果 { vtabsHeight, hdHeight }
 */
const calculateWithHeight = (systemInfo, headerHeightPx, containerMargin = 10, includeTabbar = false) => {
	const windowHeight = systemInfo.windowHeight;
	const statusBarHeight = systemInfo.statusBarHeight || 0;
	const platform = systemInfo.platform;
	const pixelRatio = systemInfo.pixelRatio || 2;
	
	// 计算导航栏高度
	let navBarHeight = 44;
	// #ifdef APP-PLUS
	if (platform === 'ios') {
		navBarHeight = 44;
	} else if (platform === 'android') {
		// 安卓需要更多空间，包括导航栏和可能的虚拟按键
		navBarHeight = 56;
	}
	// #endif
	// #ifdef H5
	navBarHeight = 44;
	// #endif
	
	// 设置头部内容高度（转换为rpx）
	const hdHeightRpx = Math.ceil(headerHeightPx * 2);
	const hdHeight = `${hdHeightRpx}rpx`;
	
	// 底部安全区域
	let safeAreaBottom = 0;
	if (systemInfo.safeAreaInsets && systemInfo.safeAreaInsets.bottom) {
		safeAreaBottom = systemInfo.safeAreaInsets.bottom;
	} else if (systemInfo.safeArea && systemInfo.screenHeight) {
		safeAreaBottom = systemInfo.screenHeight - systemInfo.safeArea.bottom;
	}
	
	// tabbar高度（App环境下通常不需要）
	let tabbarHeight = 0;
	if (includeTabbar) {
		// #ifdef H5
		tabbarHeight = 50; // H5环境下可能需要考虑tabbar
		// #endif
	}
	
	// 安卓平台额外边距（考虑虚拟导航栏等）
	let extraMargin = 0;
	// #ifdef APP-PLUS
	if (platform === 'android') {
		extraMargin = 20; // 安卓额外预留20px空间
	}
	// #endif
	
	// 计算vtabs可用高度
	const usedHeight = statusBarHeight + navBarHeight + headerHeightPx + containerMargin + tabbarHeight + safeAreaBottom + extraMargin;
	let availableHeight = windowHeight - usedHeight;
	
	// 确保最小高度和最大高度
	const minHeight = 300;
	const maxHeight = windowHeight * 0.7;
	
	if (availableHeight < minHeight) {
		availableHeight = minHeight;
		console.warn('vtabs高度过小，使用最小高度:', minHeight);
	} else if (availableHeight > maxHeight) {
		availableHeight = maxHeight;
		console.warn('vtabs高度过大，使用最大高度:', maxHeight);
	}
	
	// 设置vtabs高度（转换为rpx）
	const vtabsHeight = `${Math.ceil(availableHeight * 2)}rpx`;
	
	// 调试信息
	console.log('vtabs高度计算结果:', {
		windowHeight,
		statusBarHeight,
		navBarHeight,
		headerHeightPx,
		containerMargin,
		tabbarHeight,
		safeAreaBottom,
		extraMargin,
		usedHeight,
		availableHeight,
		vtabsHeight,
		hdHeight,
		platform,
		pixelRatio,
		includeTabbar
	});
	
	return {
		vtabsHeight,
		hdHeight,
		availableHeight,
		headerHeightPx
	};
};

export default {
	formatTime,
	calculateVtabsHeight
}
