/**
 * 格式化投注内容显示
 * @param {string|object|array} betContent - 投注内容
 * @returns {string} 格式化后的投注内容
 */
export function formatOrderBetContent(betContent) {
	if (!betContent) return '';
	
	// 如果是字符串，尝试解析为JSON
	if (typeof betContent === 'string') {
		try {
			betContent = JSON.parse(betContent);
		} catch (e) {
			// 如果解析失败，直接返回字符串
			return betContent;
		}
	}
	
	// 如果是数组，处理每个投注项
	if (Array.isArray(betContent)) {
		return betContent.map(item => formatSingleBetContent(item)).join('; ');
	}
	
	// 单个投注项
	return formatSingleBetContent(betContent);
}

/**
 * 格式化单个投注内容
 * @param {object} content - 单个投注内容对象
 * @returns {string} 格式化后的投注内容
 */
export function formatSingleBetContent(content) {
	if (!content) return '';
	
	let result = '';
	
	// 显示玩法名称
	if (content.type_name) {
		result = content.type_name;
	} else if (content.type_key) {
		// 如果没有type_name，根据type_key获取显示名称
		const typeMap = {
			'zhixuan_fushi': '直选复式',
			'zhixuan_danshi': '直选单式',
			'zhixuan_hezhi': '直选和值',
			'zhixuan_kuadu': '直选跨度',
			'zusan_danshi': '组三单式',
			'zusan_fushi': '组三复式',
			'zuliu_danshi': '组六单式',
			'zuliu_fushi': '组六复式',
			'zuxuan_yima_dingwei': '一码定位',
			'zuxuan_liangma_dingwei': '两码定位',
			'zuxuan_yima_budingwei': '一码不定位',
			'hezhi_daxiao': '和值大小',
			'hezhi_danshuang': '和值单双'
		};
		result = typeMap[content.type_key] || content.type_key;
	}
	
	// 添加选号信息
	if (content.numbers) {
		const numbers = content.numbers;
		
		// 直选类型（百十个位）
		if (numbers.bai && numbers.shi && numbers.ge && 
			Array.isArray(numbers.bai) && Array.isArray(numbers.shi) && Array.isArray(numbers.ge)) {
			result += ` [${numbers.bai.join(',')} | ${numbers.shi.join(',')} | ${numbers.ge.join(',')}]`;
		}
		// 和值、跨度等单选类型
		else if (numbers.selected && Array.isArray(numbers.selected)) {
			result += ` [${numbers.selected.join(',')}]`;
		}
		// 定位类型
		else if (numbers.positions) {
			const positionNames = { bai: '百', shi: '十', ge: '个' };
			const positionTexts = [];
			for (const [pos, nums] of Object.entries(numbers.positions)) {
				if (nums && nums.length > 0) {
					positionTexts.push(`${positionNames[pos]}位:${nums.join(',')}`);
				}
			}
			if (positionTexts.length > 0) {
				result += ` [${positionTexts.join(' ')}]`;
			}
		}
		// 数组格式的numbers（如和值大小、单双等）
		else if (Array.isArray(numbers)) {
			result += ` [${numbers.join(',')}]`;
		}
		// 字符串格式
		else if (typeof numbers === 'string') {
			result += ` [${numbers}]`;
		}
		// 其他对象格式，尽量避免显示原始JSON
		else if (typeof numbers === 'object') {
			// 尝试提取有用信息
			const keys = Object.keys(numbers);
			if (keys.length > 0) {
				const values = keys.map(key => {
					const value = numbers[key];
					if (Array.isArray(value)) {
						return value.join(',');
					}
					return value;
				}).filter(v => v !== null && v !== undefined && v !== '');
				if (values.length > 0) {
					result += ` [${values.join(',')}]`;
				}
			}
		}
	}
	
	// 添加注数信息
	if (content.note) {
		result += ` ${content.note}注`;
	}
	
	// 添加倍数信息（如果倍数大于1）
	if (content.multiplier && content.multiplier > 1) {
		result += ` ${content.multiplier}倍`;
	}
	
	return result || '未知投注';
}