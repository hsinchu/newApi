/**
 * 格式化时间为相对时间
 * @param {string|Date} time 时间
 * @return {string} 格式化后的时间字符串
 */
export const formatTime = (time) => {
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
 * 格式化日期时间
 * @param {string|number} dateTime 时间戳或日期字符串
 * @param {string} format 格式化字符串，默认 'YYYY-MM-DD HH:mm:ss'
 * @return {string} 格式化后的时间字符串
 */
export const formatDateTime = (dateTime, format = 'YYYY-MM-DD HH:mm:ss') => {
	if (!dateTime) return ''
	
	let date
	if (typeof dateTime === 'string' && dateTime.includes('-')) {
		// 处理日期字符串
		date = new Date(dateTime.replace(/-/g, '/'))
	} else if (typeof dateTime === 'number') {
		// 处理时间戳
		if (dateTime.toString().length === 10) {
			date = new Date(dateTime * 1000)
		} else {
			date = new Date(dateTime)
		}
	} else {
		date = new Date(dateTime)
	}
	
	if (isNaN(date.getTime())) {
		return dateTime
	}
	
	const year = date.getFullYear()
	const month = String(date.getMonth() + 1).padStart(2, '0')
	const day = String(date.getDate()).padStart(2, '0')
	const hours = String(date.getHours()).padStart(2, '0')
	const minutes = String(date.getMinutes()).padStart(2, '0')
	const seconds = String(date.getSeconds()).padStart(2, '0')
	
	return format
		.replace('YYYY', year)
		.replace('MM', month)
		.replace('DD', day)
		.replace('HH', hours)
		.replace('mm', minutes)
		.replace('ss', seconds)
}

export default {
	formatTime,
	formatDateTime
}