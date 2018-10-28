<?php

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',true);

// 定义应用目录
define('APP_PATH','../teacher/');
define('BIND_MODULE','Home');

//自动创建表的名称模版
define('TEACHER','teacherbaseinfo');	//教师信息
define('STUDENT','studentorder');		//学生问卷
define('ADDCOMMENT','addcomment');		//学生补充意见
define('PERSON','person');				//个人分析表


header('Content-type:text/html;charset=UTF8');
// 引入ThinkPHP入口文件
require '../ThinkPHP/ThinkPHP.php';
