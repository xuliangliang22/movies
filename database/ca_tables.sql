-- 后台分类表,与规则表,一对多hasMany
DROP TABLE IF EXISTS `ca_arctypes`;
CREATE TABLE `ca_arctypes` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `top_id` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '父级id',
  `sort` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `dede_id` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '对应dede表中的栏目id',
  `dede_typename` varchar(255) NOT NULL DEFAULT '' COMMENT '对应dede表中的栏目名称',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ca_arctypes_dede_id_index` (`dede_id`),
  KEY `ca_arctypes_top_id_index` (`top_id`),
  KEY `ca_arctypes_sort_index` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;


-- 后台命令规则表,与分类表,一对多belogsTo
DROP TABLE IF EXISTS `ca_rules`;
CREATE TABLE `ca_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(255) NOT NULL DEFAULT '' COMMENT '规则名称',
  `site_url` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '对应的采集网站链接地址',
  `rule_command` varchar(255) NOT NULL DEFAULT '' COMMENT '对应的php artisan命令',
  `rule_args` varchar(255) NOT NULL DEFAULT '' COMMENT '对应的参数,以逗号分隔,如果包含问号则是可选参数',
  `file_path` varchar(255) NOT NULL DEFAULT '' COMMENT '文件位置',
  `type_id` tinyint(4) NOT NULL DEFAULT 0 COMMENT '对应的分类id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ca_rules_type_id_index` (`type_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;


-- 采集数据保存表
DROP TABLE IF EXISTS `ca_gather`;
CREATE TABLE `ca_gather` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '标题',
  `title_hash` varchar(255) NOT NULL DEFAULT '' COMMENT '标题的hash值',
  `typeid` tinyint(4) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL COMMENT '评分值',
  `litpic` varchar(255) DEFAULT NULL COMMENT '缩略图',
  `con_url` varchar(255) NOT NULL DEFAULT '' COMMENT '内容页地址',
  `body` text DEFAULT NULL COMMENT '描述,如果是文章刚是文章的内容',
  `director` varchar(255) DEFAULT NULL COMMENT '导演,只有一位',
  `actors` varchar(255) DEFAULT NULL COMMENT '主演有多个,以逗号分隔',
  `myear` varchar(20) DEFAULT NULL COMMENT '影片上映年份',
  `lan_guage` varchar(50) DEFAULT NULL COMMENT '影片语言',
  `types` varchar(255) DEFAULT NULL COMMENT '类别信息,以逗号分隔',
  `down_link` longtext DEFAULT NULL COMMENT '下载链接,以逗号分隔,如果是文章,则是描述信息',
  `is_litpic` tinyint(2) NOT NULL DEFAULT -1 COMMENT '是否下载图片',
  `is_con` tinyint(2) NOT NULL DEFAULT -1 COMMENT '是否下载内容页,-1没有,0提交',
  `is_body` tinyint(2) DEFAULT -1 COMMENT '内容页图片是否下载',
  `is_douban` tinyint(4) DEFAULT -1 COMMENT '是否运行过豆瓣程序,默认为1,否则为0',
  `is_post` tinyint(4) NOT NULL DEFAULT -1 COMMENT '是否已经提交到dede后台',
  `episode_nums` smallint(6) NOT NULL DEFAULT 0 COMMENT '电视剧的总集数',
  `m_time` timestamp NULL DEFAULT NULL COMMENT '影片网站上更新的时间,通过这个时间去判断是否需要更新',
  `is_update` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否更新的标志,如果为-1表示数据更新,0表示没有更新',
  PRIMARY KEY (`id`),
  KEY `idx_is_con` (`is_con`),
  KEY `idx_is_litpic` (`is_litpic`) USING BTREE,
  KEY `idx_typeid` (`typeid`),
  KEY `idx_is_body` (`is_body`) USING BTREE,
  KEY `idx_m_time` (`m_time`) USING BTREE COMMENT '根据时间去判断更新',
  KEY `idx_title_hash` (`title_hash`) USING BTREE,
  KEY `idx_is_update` (`is_update`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=19435 DEFAULT CHARSET=utf8;
