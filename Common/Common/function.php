<?php
function showPage($count,$listRows){
	//分页
	$page = new \Think\Page($count=0,$listRows=10);
	$page -> setConfig('prev','上一页');
	$page -> setConfig('next','下一页');
	$page -> setConfig('first','首页');
	$page -> setConfig('last','末页');
	$page -> lastSuffix = false;  // 最后一页是否显示总页数
	return $page -> show();
}

//判断表示否存在
function existsTable($tname=''){
	$sql = 'show tables like %'.$tname.'%';
	$re = M() -> query('SHOW TABLES LIKE "'.C('DB_PREFIX').$tname.'"');
	if($re){
		return true;
	}else{
		return false;
	}
}