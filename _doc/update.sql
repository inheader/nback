-- 2019-03-29 15:34:33  修改了订单支付方式

ALTER TABLE `ev_order_pay`
CHANGE `pay_type_code` `pay_type_code` enum('WEIXIN_PAY','ALI_PAY','POINT_PAY','COUPON_PAY','VCOUNT_PAY','CREDIT_PAY') COLLATE 'utf8_general_ci' NOT NULL DEFAULT 'WEIXIN_PAY' COMMENT '支付类型代码' AFTER `pay_type_id`;


-- 2019-04-01 06:44:51  新增了用户赊账权限字段

ALTER TABLE `ev_buyer`
ADD `is_credit` tinyint(4) unsigned NULL DEFAULT '1' COMMENT '权限 0：未知、1：无、2：有' AFTER `country`;

ALTER TABLE `ev_buyer`
ADD `limits_credit` tinyint(4) unsigned NULL DEFAULT '1' COMMENT '赊账权限 0：未知、1：无、2：有' AFTER `is_credit`;


-- 2019-04-01 06:44:51  新增了赊账表以及赊账流水表

DROP TABLE IF EXISTS `ev_credit_flow`;
CREATE TABLE `ev_credit_flow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mall_id` int(11) unsigned NOT NULL,
  `site_id` int(10) unsigned DEFAULT NULL,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单ID',
  `buyer_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型 1=申请到账 2=使用',
  `num` int(10) NOT NULL DEFAULT '0' COMMENT '赊账数量',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '赊账余额',
  `remarks` varchar(255) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_MALL_BUYER` (`buyer_id`),
  KEY `mall_id` (`mall_id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `ev_credit_flow_ibfk_1` FOREIGN KEY (`mall_id`) REFERENCES `ev_area_mall` (`mall_id`),
  CONSTRAINT `ev_credit_flow_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `ev_area_mall_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户赊账流水表';



INSERT INTO `ev_credit_flow` (`id`, `mall_id`, `site_id`, `order_id`, `buyer_id`, `type`, `num`, `balance`, `remarks`, `created_at`, `updated_at`) VALUES
(27,	2,	18,	0,	1,	1,	6000,	6000.00,	NULL,	NULL,	NULL),
(28,	2,	NULL,	4294967295,	1,	2,	25,	5899.96,	'支付单号：20190329101571021',	'2019-03-29 02:12:48',	'2019-03-29 02:12:48'),
(29,	2,	NULL,	4294967295,	1,	2,	160,	5739.96,	'支付单号：20190329515556571',	'2019-03-29 04:11:06',	'2019-03-29 04:11:06'),
(32,	2,	NULL,	4294967295,	1,	1,	0,	6674.62,	'',	'2019-03-29 09:26:16',	'2019-03-29 09:26:16'),
(33,	2,	NULL,	4294967295,	1,	1,	0,	6674.63,	'',	'2019-03-29 09:26:36',	'2019-03-29 09:26:36'),
(34,	2,	NULL,	4294967295,	1,	1,	160,	6834.63,	'',	'2019-03-29 09:26:36',	'2019-03-29 09:26:36'),
(35,	2,	NULL,	4294967295,	1,	2,	160,	6674.63,	'支付单号：20190329549957531',	'2019-03-29 09:27:54',	'2019-03-29 09:27:54'),
(36,	2,	NULL,	4294967295,	1,	2,	25,	6649.63,	'支付单号：20190330985053571',	'2019-03-30 04:04:32',	'2019-03-30 04:04:32');

DROP TABLE IF EXISTS `ev_credit_lines`;
CREATE TABLE `ev_credit_lines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '余额',
  `remark` varchar(200) NOT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `IX_USER` (`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户赊账余额表';

INSERT INTO `ev_credit_lines` (`id`, `buyer_id`, `balance`, `remark`, `created_at`, `updated_at`) VALUES
(28,	1,	6649.63,	'',	NULL,	'2019-03-30 04:04:32');

DROP TABLE IF EXISTS `ev_credit_list`;
CREATE TABLE `ev_credit_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `buyer_id` int(10) unsigned NOT NULL COMMENT '买家id（对应用户表）',
  `card_id` bigint(20) NOT NULL COMMENT '身份证号码',
  `phone` bigint(20) NOT NULL COMMENT '联系电话',
  `company` varchar(50) NOT NULL COMMENT '公司名称',
  `username` varchar(30) NOT NULL COMMENT '用户名称',
  `apply_money` decimal(10,2) NOT NULL COMMENT '申请金额',
  `actual_money` decimal(10,2) NOT NULL COMMENT '实际金额',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态（0：未申请，1：已申请，2：已拒绝，3：已通过）',
  `schedule` tinyint(4) NOT NULL DEFAULT '0' COMMENT '进度（0：未申请，1：待审核，2：审核未通过，3：审核通过）',
  `mall_id` int(11) unsigned NOT NULL COMMENT '可用分站',
  `site_id` int(10) unsigned NOT NULL COMMENT '可用站点',
  `isdel` bigint(12) unsigned DEFAULT NULL COMMENT '数据库软删除',
  `created_at` timestamp NOT NULL COMMENT '申请时间',
  `updated_at` timestamp NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `mall_id` (`mall_id`),
  KEY `site_id` (`site_id`),
  KEY `buyer_id` (`buyer_id`),
  CONSTRAINT `ev_credit_list_ibfk_1` FOREIGN KEY (`mall_id`) REFERENCES `ev_area_mall` (`mall_id`),
  CONSTRAINT `ev_credit_list_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `ev_area_mall_site` (`site_id`),
  CONSTRAINT `ev_credit_list_ibfk_3` FOREIGN KEY (`buyer_id`) REFERENCES `ev_buyer` (`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='信用额度表';

INSERT INTO `ev_credit_list` (`id`, `buyer_id`, `card_id`, `phone`, `company`, `username`, `apply_money`, `actual_money`, `status`, `schedule`, `mall_id`, `site_id`, `isdel`, `created_at`, `updated_at`) VALUES
(2,	1,	421121199310154839,	18261161533,	'常州佩祥',	'王彪',	50.00,	6000.00,	3,	0,	2,	18,	NULL,	'2019-03-25 03:04:41',	'2019-03-25 03:04:41'),
(3,	1,	421121199310154555,	18261161599,	'常州佩祥',	'王彪',	50.00,	0.00,	1,	0,	2,	18,	NULL,	'2019-03-25 03:05:30',	'2019-03-25 03:05:30'),
(4,	1,	421121199222222,	182611622222,	'常州佩祥1',	'陈方',	50.00,	0.00,	1,	0,	2,	18,	NULL,	'2019-03-25 07:48:41',	'2019-03-25 07:48:41'),
(5,	1,	421121199615142834,	15161165204,	'常州佩祥电子',	'王晓',	10000.00,	0.00,	2,	0,	2,	18,	NULL,	'2019-03-25 07:50:06',	'2019-03-25 07:50:06'),
(6,	1,	42112119922222255,	182611622222,	'常州佩祥1',	'陈一',	50.00,	0.00,	1,	0,	2,	18,	NULL,	'2019-03-27 00:32:55',	'2019-03-27 00:32:55'),
(7,	1,	421121199222225511,	182611622222,	'常州佩祥1',	'陈二',	50.00,	6000.00,	3,	0,	2,	18,	NULL,	'2019-03-27 00:34:00',	'2019-03-27 00:34:00');

DROP TABLE IF EXISTS `ev_credit_perm`;
CREATE TABLE `ev_credit_perm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `mall_id` smallint(4) unsigned NOT NULL DEFAULT '0',
  `site_id` int(10) unsigned NOT NULL DEFAULT '0',
  `buyer_id` int(10) unsigned NOT NULL COMMENT '用户id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_MALL_USER` (`mall_id`,`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='赊账权限表';

INSERT INTO `ev_credit_perm` (`id`, `mall_id`, `site_id`, `buyer_id`, `created_at`, `updated_at`) VALUES
(9,	1,	10,	5,	'2019-01-09 02:20:20',	'2019-01-09 02:20:20'),
(10,	1,	10,	4,	'2019-01-10 11:17:28',	'2019-01-10 11:17:28'),
(11,	1,	5,	4,	'2019-01-17 09:42:17',	'2019-01-17 09:42:17'),
(12,	1,	3,	4,	'2019-01-18 03:11:02',	'2019-01-18 03:11:02'),
(15,	1,	10,	15,	'2019-01-29 03:33:48',	'2019-01-29 03:33:48'),
(16,	1,	10,	16,	'2019-02-25 14:01:24',	'2019-02-25 14:01:24'),
(17,	2,	18,	4,	'2019-03-04 08:28:44',	'2019-03-04 08:28:44'),
(19,	3,	19,	21,	'2019-03-05 06:51:42',	'2019-03-05 06:51:42'),
(22,	2,	18,	7,	'2019-03-05 11:41:59',	'2019-03-05 11:41:59'),
(24,	2,	18,	8,	'2019-03-13 01:15:49',	'2019-03-13 01:15:49'),
(25,	2,	18,	1,	'2019-03-23 04:32:56',	'2019-03-23 04:32:56'),
(26,	2,	0,	1,	NULL,	NULL);


-- 2019-04-02 14:47:44  新增了赊账表以及赊账流水表

ALTER TABLE `ev_credit_lines`
ADD `mall_id` int(11) unsigned NOT NULL COMMENT '区域id' AFTER `id`,
ADD `site_id` int(10) unsigned NOT NULL COMMENT '站点id' AFTER `mall_id`,
ADD FOREIGN KEY (`mall_id`) REFERENCES `ev_area_mall` (`mall_id`),
ADD FOREIGN KEY (`site_id`) REFERENCES `ev_area_mall_site` (`site_id`);


-- 2019-04-11 14:47:44  取消了店铺地址表中的必填手机号

ALTER TABLE `ev_area_mall_site_address`
CHANGE `phone` `phone` varchar(11) COLLATE 'utf8_general_ci' NULL COMMENT '店铺电话' AFTER `address`;


-- 2019-04-11 16:47:44  修改了店铺用户的赊账权限

ALTER TABLE `ev_site_member`
ADD `is_credit` tinyint(4) NULL COMMENT '用户赊账权限' AFTER `buyer_level`;

ALTER TABLE `ev_site_member`
CHANGE `is_credit` `is_credit` tinyint(4) NULL DEFAULT '0' COMMENT '赊账权限 0：未知、1：无、2：有' AFTER `buyer_level`;


ALTER TABLE `ev_buyer`
DROP `is_credit`,
DROP `limits_credit`;

-- 2019-04-12 16:47:44  重新修改了赊账权限表

DROP TABLE IF EXISTS `ev_credit_perm`;
CREATE TABLE `ev_credit_perm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `site_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sm_id` int(10) unsigned NOT NULL COMMENT '店铺会员id',
  `buyer_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_MALL_USER` (`sm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='赊账权限表';

-- 2019-04-13 16:47:44  删除了物流先关字段

ALTER TABLE `ev_order_package`
DROP `shipping_type`,
DROP `shipping_express_id`,
DROP `shipping_express_name`,
DROP `shipping_express_code`,
DROP `shipping_code`;


ALTER TABLE `ev_credit_list`
ADD `type` tinyint(2) NOT NULL COMMENT '申请类型' AFTER `buyer_id`;


ALTER TABLE `ev_credit_list`
CHANGE `type` `type` tinyint(2) NOT NULL COMMENT '申请类型（1：企业，2：个人）' AFTER `buyer_id`,
ADD `card_img` varchar(100) NOT NULL COMMENT '身份证正面' AFTER `card_id`,
ADD `cardup_img` bigint(100) NOT NULL COMMENT '身份证反面' AFTER `card_img`,
ADD `business` bigint(100) NOT NULL COMMENT '营业执照' AFTER `cardup_img`;

-- 2019-04-15 10:11:44  新增了营业执照代码字段

ALTER TABLE `ev_credit_list`
CHANGE `cardup_img` `cardup_img` varchar(100) NOT NULL COMMENT '身份证反面' AFTER `card_img`,
ADD `business_code` varchar(30) NOT NULL COMMENT '营业执照代码' AFTER `business`;


-- 2019-04-15 15:19:44  新增了退款总金额

ALTER TABLE `ev_order_return`
ADD `refund_amount` decimal(16,2) NULL DEFAULT '0.00' COMMENT '退款金额' AFTER `refund_price`;


-- 2019-04-15 15:49:44  新增了店铺用户分类表

DROP TABLE IF EXISTS `ev_site_member_cate`;
CREATE TABLE `ev_site_member_cate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(10) unsigned NOT NULL COMMENT '商品id',
  `name` varchar(30) NOT NULL COMMENT '分类名称',
  `status` int(11) DEFAULT NULL COMMENT '状态',
  `created_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '新增时间',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `ev_site_member_cate_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `ev_area_mall_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2019-04-15 15:49:44  新增了店铺会员表中的赊账权限
ALTER TABLE `ev_site_member`
ADD `site_cat_id` int(10) NOT NULL COMMENT '店铺分类id' AFTER `site_id`,
ADD `credit_perm` tinyint(4) NULL COMMENT '权限 0：未知、1：无、2：有' AFTER `buyer_level`;


-- 2019-04-15 15:49:44  删除了会员主表中的赊账权限字段
ALTER TABLE `ev_buyer`
DROP `is_credit`,
DROP `limits_credit`;


-- 2019-04-15 15:55:44  修改了后台的菜单

INSERT INTO `ev_operation` (`parent_id`, `name`, `code`, `type`, `parent_menu_id`, `perm_type`, `sort`)
SELECT '238', '会员分类', 'cateMember', 3, '238', '1', '98'
FROM `ev_operation`
WHERE `name` = '会员等级配置' AND ((`id` = '488'));


INSERT INTO `ev_manage_see_menu` (`auth_type`, `operation_id`)
SELECT '3', '498'
FROM `ev_manage_see_menu`
WHERE `operation_id` = '488' AND ((`id` = '258'));


-- 2019-04-16 10:34:44  新增了商品主表里的会员组购买权限

ALTER TABLE `ev_goods`
ADD `cate_tag` varchar(50) NULL COMMENT '会员组购买权限' AFTER `downtime`;


-- 2019-04-16 13:18:44  新增了商品主表里的会员组浏览权限

ALTER TABLE `ev_goods`
CHANGE `cate_tag` `cate_tag_buy` varchar(50) COLLATE 'utf8mb4_general_ci' NULL COMMENT '会员组购买权限' AFTER `downtime`,
ADD `cate_tag_see` varchar(50) COLLATE 'utf8mb4_general_ci' NULL COMMENT '会员组浏览权限' AFTER `cate_tag_buy`;


-- 2019-04-16 16:28:44  删除了外键

ALTER TABLE `ev_credit_lines`
DROP FOREIGN KEY `ev_credit_lines_ibfk_1`


ALTER TABLE `ev_credit_lines`
DROP FOREIGN KEY `ev_credit_lines_ibfk_2`


-- 2019-04-17 14:30:44  新增了是否赊账字段

ALTER TABLE `ev_order_common`
ADD `is_credit` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否赊账' AFTER `order_floor_fee`;


-- 2019-04-18 13:34:44  修改了店铺id分组为必填项


ALTER TABLE `ev_site_member`
CHANGE `site_cat_id` `site_cat_id` int(10) NULL COMMENT '店铺分类id' AFTER `site_id`;


-- 2019-04-18 14:49:44  订单商品表中新增商品货号字段

ALTER TABLE `ev_order_goods`
ADD `goods_bn` varchar(30) COLLATE 'utf8_general_ci' NULL COMMENT '商品货号' AFTER `goods_spec_value`;





CREATE TABLE `ims_diff` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `uid` int NOT NULL,
  `openid` varchar(100) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `nickname_wechat` varchar(100) NOT NULL,
  `realname` varchar(100) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `area` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL
) ENGINE='InnoDB' COLLATE 'utf8mb4_general_ci';


DROP TABLE IF EXISTS `ev_hot_search`;
CREATE TABLE `ev_hot_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1.单2.双',
  `keywords` varchar(120) NOT NULL DEFAULT '' COMMENT '关键词',
  `tips` enum('top','hot','old','new') NOT NULL DEFAULT 'hot' COMMENT '提示',
  `click` int(10) NOT NULL DEFAULT '0' COMMENT '点击',
  `search_num` int(10) NOT NULL DEFAULT '0' COMMENT '搜索量',
  `weight` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1.开启2.隐藏',
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='热门搜索词';

DROP TABLE IF EXISTS `ev_history_search`;
CREATE TABLE `ev_history_search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` bigint(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `keywords` varchar(120) NOT NULL DEFAULT '' COMMENT '历史词',
  `exp_time` datetime NOT NULL COMMENT '过期时间',
  `click` int(10) NOT NULL DEFAULT '0' COMMENT '点击',
  `search_num` int(10) NOT NULL DEFAULT '0' COMMENT '搜索次',
  `weight` int(10) NOT NULL DEFAULT '0' COMMENT '权重',
  `status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '1,开启2.隐藏',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '记录时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='历史词搜索';


-- 2019-05-05 09:19:44  新增了店铺id

ALTER TABLE `ev_buyer_points_log`
CHANGE `mall_id` `mall_id` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '区域id' AFTER `id`,
ADD `site_id` bigint unsigned NOT NULL COMMENT '店铺id' AFTER `mall_id`;


--  2019-05-11 15:25:44  新增了后台区域用户表
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
CREATE TABLE `ev_mall` LIKE `ev_manage`;
INSERT INTO `ev_mall` SELECT * FROM `ev_manage`;


--  2019-05-11 15:25:44  新增了后台店铺用户表
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
CREATE TABLE `ev_site` LIKE `ev_manage`;
INSERT INTO `ev_site` SELECT * FROM `ev_manage`;

--  2019-09-07 10:00:00  店铺表中新增店铺海报字段
ALTER TABLE `jshop`.`ev_area_mall_site` ADD COLUMN `site_posters` varchar(2000) COMMENT '店铺海报' AFTER `site_logo`;

--2019-09-07 14:00:00 菜单栏添加加载页面配置
INSERT INTO `ev_operation` (`id`, `belong`, `parent_id`, `name`, `code`, `type`, `parent_menu_id`, `perm_type`, `sort`)
VALUES ('586', 'mall', '573', '加载页面配置', 'load', 3, '573', '1', '100');

--2019-09-07 16:00:00 新增区域加载页图片设置表
CREATE TABLE `ev_mall_load_img` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mall_id` int(11) NOT NULL COMMENT '区域id',
  `load_img` varchar(256) NOT NULL COMMENT '页面加载图片',
  `back_color` varchar(32) DEFAULT NULL COMMENT '图片背景颜色',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4

