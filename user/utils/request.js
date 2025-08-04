import config from './config.js';

/**
 * 基于uni.request的HTTP请求封装类
 * 支持token自动携带、请求拦截、响应拦截等功能
 */
class Request {
	constructor() {
		// 获取当前环境配置
		const appConfig = config.getConfig();
		
		// 基础配置
		this.config = {
			baseURL: appConfig.baseURL,
			timeout: appConfig.timeout, 
			header: {
				'Content-Type': 'application/json',
				'server': 1
			}
		};
		
		// 存储环境信息
		this.env = appConfig.currentEnv;
		this.debug = appConfig.debug;
	}
	
	/**
	 * 统一请求方法
	 */
	request(options = {}) {
		return new Promise((resolve, reject) => {
			// 显示加载提示
			if (options.showLoading !== false) {
				// uni.showLoading({
				// 	title: '加载中...'
				// });
			}
			
			// 构建请求配置
			const config = {
				url: this.config.baseURL + (options.url || ''),
				method: options.method || 'GET',
				data: options.data || {},
				header: {
					...this.config.header,
					...options.header
				},
				timeout: options.timeout || this.config.timeout
			};
			
			// 自动携带token
			const token = this.getToken();
			if (token) {
				config.header['ba-user-token'] = token;
			}
			
			// 发起请求
			uni.request({
				...config,
				success: (response) => {
					// 隐藏加载提示
					uni.hideLoading();
					
					// 处理响应数据
					if (response.data) {
						const { code, msg, data } = response.data;
						
						// 成功响应
						if (code === 1) {
							resolve(response.data);
							return;
						}
						
						// token过期，需要重新登录
						if (code === 409 || code === 303) {
							this.removeToken();
							uni.showToast({
								title: '登录已过期，请重新登录',
								icon: 'none'
							});
							// 跳转到登录页
							setTimeout(() => {
								uni.reLaunch({
									url: '/pages/users/login'
								});
							}, 1500);
							reject(response.data);
							return;
						}
						
						// 其他错误
						if (msg) {
							uni.showToast({
								title: msg,
								icon: 'none'
							});
						}
						reject(response.data);
					} else {
						resolve(response);
					}
				},
				fail: (error) => {
					// 隐藏加载提示
					uni.hideLoading();
					
					// 处理网络错误
					let message = '网络请求失败';
					if (error.statusCode) {
						switch (error.statusCode) {
							case 400:
								message = '请求参数错误';
								break;
							case 401:
								message = '未授权，请重新登录';
								break;
							case 403:
								message = '拒绝访问';
								break;
							case 404:
								message = '请求地址不存在';
								break;
							case 500:
								message = '服务器内部错误';
								break;
							default:
								message = `连接错误${error.statusCode}`;
						}
					}
					
					uni.showToast({
						title: message,
						icon: 'none'
					});
					
					reject(error);
				}
			});
		});
	}
	
	/**
	 * 获取存储的token
	 */
	getToken() {
		return uni.getStorageSync('ba-user-token') || '';
	}
	
	/**
	 * 设置token
	 */
	setToken(token) {
		uni.setStorageSync('ba-user-token', token);
	}
	
	/**
	 * 移除token
	 */
	removeToken() {
		uni.removeStorageSync('ba-user-token');
		uni.removeStorageSync('userInfo');
	}
	
	/**
	 * 获取当前环境信息
	 */
	getEnvInfo() {
		return {
			env: this.env,
			baseURL: this.config.baseURL,
			debug: this.debug,
			timeout: this.config.timeout
		};
	}
	
	/**
	 * 动态切换环境（仅开发模式下可用）
	 */
	switchEnv(envName) {
		if (!this.debug) {
			console.warn('生产环境下不允许切换环境');
			return false;
		}
		
		const envConfig = config.ENV_CONFIG[envName];
		if (!envConfig) {
			console.error('无效的环境名称:', envName);
			return false;
		}
		
		// 更新配置
		this.config.baseURL = envConfig.baseURL;
		this.config.timeout = envConfig.timeout;
		this.env = envName;
		
		console.log('环境已切换到:', envName);
		console.log('新的API地址:', this.config.baseURL);
		
		return true;
	}
	
	/**
	 * GET请求
	 */
	get(url, params = {}, options = {}) {
		return this.request({
			url,
			method: 'GET',
			data: params,
			...options
		});
	}
	
	/**
	 * POST请求
	 */
	post(url, data = {}, options = {}) {
		return this.request({
			url,
			method: 'POST',
			data,
			...options
		});
	}
	
	/**
	 * PUT请求
	 */
	put(url, data = {}, options = {}) {
		return this.request({
			url,
			method: 'PUT',
			data,
			...options
		});
	}
	
	/**
	 * DELETE请求
	 */
	delete(url, data = {}, options = {}) {
		return this.request({
			url,
			method: 'DELETE',
			data,
			...options
		});
	}
	
	/**
	 * 上传文件
	 */
	upload(url, filePath, options = {}) {
		return new Promise((resolve, reject) => {
			const token = this.getToken();
			const header = {
				...this.config.header
			};
			
			if (token) {
				header['ba-user-token'] = token;
			}
			
			uni.uploadFile({
				url: this.config.baseURL + url,
				filePath,
				header,
				...options,
				success: (res) => {
					try {
						const data = JSON.parse(res.data);
						if (data.code === 1) {
							resolve(data);
						} else {
							reject(data);
						}
					} catch (e) {
						reject(res);
					}
				},
				fail: reject
			});
		});
	}
}

// 创建实例
const request = new Request();

// 导出实例
export default request;