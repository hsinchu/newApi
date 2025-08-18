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

 Date: 18/08/2025 14:19:59
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for fa_red_packet
-- ----------------------------
DROP TABLE IF EXISTS `fa_red_packet`;
CREATE TABLE `fa_red_packet`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `target_type` smallint(6) NOT NULL DEFAULT 0 COMMENT '发送对象:0=全部,1=代理商,2=用户民',
  `agent_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发放代理商ID',
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '红包标题',
  `blessing` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '祝福语',
  `type` enum('RANDOM','FIXED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RANDOM' COMMENT '红包类型:RANDOM=随机红包,FIXED=固定红包',
  `total_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '红包总金额(元)',
  `total_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包总个数',
  `received_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '已领取个数',
  `remaining_count` int(11) NOT NULL COMMENT '剩余个数',
  `received_amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '已领取金额(元)',
  `amount_list` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '红包金额分配(JSON数组)',
  `condition_type` enum('NONE','MIN_BET','USER_LEVEL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE' COMMENT '领取条件类型',
  `condition_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '领取条件值',
  `status` enum('ACTIVE','FINISHED','CANCELLED','EXPIRED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE' COMMENT '红包状态',
  `expire_time` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '过期时间',
  `create_time` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `agent_id`(`agent_id` ASC) USING BTREE,
  INDEX `status`(`status` ASC) USING BTREE,
  INDEX `expire_time`(`expire_time` ASC) USING BTREE,
  INDEX `agent_status_time`(`agent_id` ASC, `status` ASC, `create_time` ASC) USING BTREE,
  INDEX `idx_active_packets`(`status` ASC, `expire_time` ASC, `target_type` ASC) USING BTREE,
  INDEX `idx_condition_type`(`condition_type` ASC, `status` ASC) USING BTREE,
  INDEX `idx_agent_status_expire`(`agent_id` ASC, `status` ASC, `expire_time` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 42 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '红包活动表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fa_red_packet
-- ----------------------------

-- ----------------------------
-- Table structure for fa_red_packet_record
-- ----------------------------
DROP TABLE IF EXISTS `fa_red_packet_record`;
CREATE TABLE `fa_red_packet_record`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `red_packet_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '红包ID',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '领取用户ID',
  `amount` decimal(10, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '领取金额(元)',
  `ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '领取IP',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '用户代理',
  `create_time` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT '领取时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `red_packet_user`(`red_packet_id` ASC, `user_id` ASC) USING BTREE,
  INDEX `user_id`(`user_id` ASC) USING BTREE,
  INDEX `create_time`(`create_time` ASC) USING BTREE,
  INDEX `idx_user_packet_cover`(`user_id` ASC, `red_packet_id` ASC, `create_time` ASC) USING BTREE,
  INDEX `idx_packet_time`(`red_packet_id` ASC, `create_time` ASC) USING BTREE,
  CONSTRAINT `fk_red_packet_record_packet` FOREIGN KEY (`red_packet_id`) REFERENCES `fa_red_packet` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_red_packet_record_user` FOREIGN KEY (`user_id`) REFERENCES `fa_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 39 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '红包领取记录表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of fa_red_packet_record
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
