/**
 * 应用配置文件
 * 统一管理不同环境的配置信息
 */

// 环境配置
const ENV_CONFIG = {
	// 开发环境
	development: {
		// API基础地址
		baseURL: 'http://localhost:9328/api',
		// 是否开启调试模式
		debug: true,
		// 请求超时时间
		timeout: 10000,
		// 其他开发环境配置
		logLevel: 'debug'
	},
	// 生产环境
	production: {
		// API基础地址（请根据实际生产环境地址修改）
		baseURL: 'https://test.chuanshiyinyuan.cn/api',
		// 关闭调试模式
		debug: false,
		// 请求超时时间
		timeout: 15000,
		// 其他生产环境配置
		logLevel: 'error'
	},
	// 测试环境
	test: {
		// API基础地址
		baseURL: 'https://test.chuanshiyinyuan.cn/api',
		// 开启调试模式
		debug: true,
		// 请求超时时间
		timeout: 12000,
		// 其他测试环境配置
		logLevel: 'info'
	}
};

/**
 * 获取当前环境
 * 支持多种环境判断方式
 */
function getCurrentEnv() {
	// 方法1: 手动设置环境（推荐用于开发阶段）
	// 开发时设置为 'development'
	// 测试时设置为 'test'
	// 打包发布时改为 'production'
	// 设置为 null 则自动判断环境
	const manualEnv = null;
	
	// 如果手动设置了环境，直接返回
	if (manualEnv && ENV_CONFIG[manualEnv]) {
		return manualEnv;
	}
	
	// 方法2: 根据编译条件判断
	// #ifdef MP-WEIXIN
	// 微信小程序环境判断
	const accountInfo = wx.getAccountInfoSync();
	if (accountInfo.miniProgram.envVersion === 'develop') {
		return 'development';
	} else if (accountInfo.miniProgram.envVersion === 'trial') {
		return 'test';
	} else {
		return 'production';
	}
	// #endif
	
	// 方法3: H5环境根据域名判断
	// #ifdef H5
	try {
		const hostname = window.location.hostname;
		if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('192.168')) {
			return 'development';
		} else if (hostname.includes('test') || hostname.includes('staging')) {
			return 'test';
		} else {
			return 'production';
		}
	} catch (e) {
		console.warn('获取域名失败，使用默认环境');
	}
	// #endif
	
	// 方法4: APP环境判断
	// #ifdef APP-PLUS
	// 可以根据打包配置或其他方式判断
	// 这里默认返回生产环境
	return 'production';
	// #endif
	
	// 默认返回开发环境
	return 'development';
}

/**
 * 获取当前环境配置
 */
function getConfig() {
	const currentEnv = getCurrentEnv();
	const config = ENV_CONFIG[currentEnv] || ENV_CONFIG.development;
	
	// 在开发环境下输出配置信息
	if (config.debug) {
		console.log('=== 应用配置信息 ===');
		console.log('当前环境:', currentEnv);
		console.log('API地址:', config.baseURL);
		console.log('调试模式:', config.debug);
		console.log('请求超时:', config.timeout + 'ms');
		console.log('==================');
	}
	
	return {
		...config,
		currentEnv
	};
}

/**
 * 导出配置
 */
export default {
	getConfig,
	getCurrentEnv,
	ENV_CONFIG
};

/**
 * 使用说明：
 * 
 * 1. 开发阶段：
 *    - 将 manualEnv 设置为 'development'
 *    - 使用本地API地址进行开发
 * 
 * 2. 测试阶段：
 *    - 将 manualEnv 设置为 'test'
 *    - 使用测试环境API地址
 * 
 * 3. 生产发布：
 *    - 将 manualEnv 设置为 'production'
 *    - 或者设置为 null 让系统自动判断
 *    - 确保生产环境API地址正确
 * 
 * 4. 自动判断：
 *    - H5: 根据域名自动判断环境
 *    - 小程序: 根据版本类型自动判断
 *    - APP: 可根据需要自定义判断逻辑
 */