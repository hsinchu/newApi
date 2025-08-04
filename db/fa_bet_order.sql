/*
 Navicat Premium Dump SQL

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80012 (8.0.12)
 Source Host           : localhost:3306
 Source Schema         : api

 Target Server Type    : MySQL
 Target Server Version : 80012 (8.0.12)
 File Encoding         : 65001

 Date: 05/08/2025 01:04:52
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for fa_bet_order
-- ----------------------------
DROP TABLE IF EXISTS `fa_bet_order`;
CREATE TABLE `fa_bet_order`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_no` varchar(24) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号(24位)',
  `user_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
  `lottery_type_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '彩种ID',
  `lottery_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '彩种代码',
  `period_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '期号',
  `gift_money` bigint(20) NOT NULL COMMENT '赠送金额（分）',
  `gift_money_ratio` decimal(5, 2) NOT NULL DEFAULT 0.00 COMMENT '赠送金额比例(0-1之间)',
  `draw_result` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '开奖结果',
  `bet_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '投注内容(JSON格式)',
  `bet_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '单注金额(分)',
  `multiple` int(11) UNSIGNED NOT NULL DEFAULT 1 COMMENT '投注倍数',
  `note` int(11) NOT NULL DEFAULT 1 COMMENT '投注注数',
  `total_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '总金额(分)',
  `win_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '中奖金额(分)',
  `commission_amount` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '佣金金额(分)',
  `agent_id` int(11) UNSIGNED NULL DEFAULT NULL COMMENT '代理商ID',
  `odds` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '单注奖金（为0时要查询bonus表计算）',
  `bet_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '投注类型',
  `bet_type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '投注类型中文名称',
  `status` enum('PENDING','CONFIRMED','WINNING','PAID','LOSING','CANCELLED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT '订单状态',
  `draw_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '开奖时间',
  `settle_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '结算时间',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '投注IP',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint(16) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `lottery_type_id`(`lottery_type_id` ASC) USING BTREE,
  INDEX `period_no`(`period_no` ASC) USING BTREE,
  INDEX `status`(`status` ASC) USING BTREE,
  INDEX `create_time`(`create_time` ASC) USING BTREE,
  INDEX `lottery_code`(`lottery_code` ASC) USING BTREE,
  INDEX `user_status_time`(`user_id` ASC, `status` ASC, `create_time` ASC) USING BTREE,
  INDEX `lottery_period_status`(`lottery_type_id` ASC, `period_no` ASC, `status` ASC) USING BTREE,
  INDEX `idx_agent_id`(`agent_id` ASC) USING BTREE,
  INDEX `idx_draw_time`(`draw_time` ASC) USING BTREE,
  INDEX `idx_settle_time`(`settle_time` ASC) USING BTREE,
  INDEX `idx_lottery_code_period`(`lottery_code` ASC, `period_no` ASC) USING BTREE,
  INDEX `idx_user_lottery_time`(`user_id` ASC, `lottery_type_id` ASC, `create_time` ASC) USING BTREE,
  INDEX `idx_status_settle_time`(`status` ASC, `settle_time` ASC) USING BTREE,
  INDEX `idx_bet_type`(`bet_type` ASC) USING BTREE,
  INDEX `gift_money_ratio`(`gift_money_ratio` ASC) USING BTREE,
  INDEX `idx_gift_money`(`gift_money` ASC) USING BTREE,
  INDEX `idx_user_time_status`(`user_id` ASC, `create_time` ASC, `status` ASC) USING BTREE,
  INDEX `idx_time_status_amount`(`create_time` ASC, `status` ASC, `total_amount` ASC) USING BTREE,
  CONSTRAINT `fk_bet_order_lottery` FOREIGN KEY (`lottery_type_id`) REFERENCES `fa_lottery_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bet_order_user` FOREIGN KEY (`user_id`) REFERENCES `fa_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 68045 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '投注订单表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of fa_bet_order
-- ----------------------------
INSERT INTO `fa_bet_order` VALUES (68042, '2025080501033464106191', 2, 19, '5f3d', '250805013', 0, 0.00, '', 'da', 5000, 1, 1, 5000, 0, 0, 1, 8.00, 'da', '大', 'CONFIRMED', 0, 0, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '', 1754327014, 1754327014);
INSERT INTO `fa_bet_order` VALUES (68043, '2025080501034087627385', 2, 19, '5f3d', '250805013', 0, 0.00, '', 'xiao', 5000, 1, 1, 5000, 0, 0, 1, 6.00, 'xiao', '小', 'CONFIRMED', 0, 0, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '', 1754327020, 1754327020);
INSERT INTO `fa_bet_order` VALUES (68044, '2025080501034756172516', 2, 19, '5f3d', '250805013', 0, 0.00, '', 'he', 5000, 1, 1, 5000, 0, 0, 1, 7.00, 'he', '和', 'CONFIRMED', 0, 0, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36', '', 1754327027, 1754327027);

SET FOREIGN_KEY_CHECKS = 1;
