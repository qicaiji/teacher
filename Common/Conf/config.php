<?php
return array(
	//'配置项'=>'配置值'
	'DB_TYPE' => 'mysql',
	'DB_HOST' => 'localhost',
	'DB_USER' => 'root',
	'DB_PWD' => 'baijdfe110',
	'DB_NAME' => 'teacher',
	'DB_PORT' => '3306',
	'DB_PREFIX' => 'tea_',
	
	//跟踪调试
	//'SHOW_PAGE_TRACE' =>true,

	
	//自定义权限认证表名称
	'AUTH_CONFIG' => array(
        'AUTH_GROUP'        => 'tea_group',        	// 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'tea_middle', 		// 用户-用户组关系表
        'AUTH_RULE'         => 'tea_rule',         	// 权限规则表
        'AUTH_USER'         => 'tea_user'           // 用户信息表
	),
	
	'TMPL_PARSE_STRING' =>array(
		'__PUBLIC__' => __ROOT__.'/Common/Common', // 更改默认的/Public
	),
	
	//seesion前缀
	'SESSION_PREFIX' => 'tea',
	
	//设置默认主题目录
	'DEFAULT_THEME'=>'default',
	
	//回收考试表名称的前缀：ccexam_examID
	//'EXAM' => C('DB_PREFIX').'tea',
	
);