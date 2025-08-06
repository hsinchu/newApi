import itertools

# 生成所有可能的三个数字组合
combinations = list(itertools.product(range(10), repeat=3))

# 计算每个组合的和值
sum_ranges = {
    'small': range(0, 9),  # 和值小于9
    'medium': range(9, 19), # 和值在9到18之间
    'large': range(19, 29)  # 和值在19到28之间
}

# 统计不同和值范围的组合数
range_counts = {'small': 0, 'medium': 0, 'large': 0}

# 遍历所有组合，计算和值并归类到对应的范围
for combo in combinations:
    sum_value = sum(combo)
    if sum_value in sum_ranges['small']:
        range_counts['small'] += 1
    elif sum_value in sum_ranges['medium']:
        range_counts['medium'] += 1
    elif sum_value in sum_ranges['large']:
        range_counts['large'] += 1

# 总的组合数是1000
total_combinations = 1000

# 计算每个范围的概率
probabilities = {key: value / total_combinations for key, value in range_counts.items()}

print(probabilities)
