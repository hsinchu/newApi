-- 为充值赠送配置表添加生效时间区间字段
ALTER TABLE `fa_recharge_gift` 
ADD COLUMN `start_time` int(11) NOT NULL DEFAULT '0' COMMENT '生效开始时间' AFTER `status`,
ADD COLUMN `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '生效结束时间' AFTER `start_time`,
ADD INDEX `idx_time_range` (`start_time`, `end_time`) USING BTREE;

-- 更新现有数据，设置默认时间范围（当前时间到一年后）
UPDATE `fa_recharge_gift` SET 
`start_time` = UNIX_TIMESTAMP(NOW()),
`end_time` = UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 YEAR))
WHERE `start_time` = 0 AND `end_time` = 0;