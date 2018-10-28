<?php
namespace Home\Model;
//use Think\Model;
use Think\Model\RelationModel;

class UserModel extends RelationModel{
	protected $_validate = array(
		array('username', '', '用户名已存在！',2,'unique',self::MODEL_BOTH),

	);
	
	public function userList($listRows){
		$ulist = $this -> page(I('p'),$listRows) -> where($map) -> select(); 
		return $ulist;
	}
	
	public function addData(){
		if(I('password')!==I('password2')){
			return false;
		}
		$data['username'] = I('username');
		$data['group'] = intval(I('group'));
		$data['password'] = md5(I('password'));
		$data['status'] = intval(I('status'));
		return $data;
	}
	
	public function editData(){
		if(I('password')!==I('password2')){
			return false;
		}
		$data['uid'] = I('uid');
		$data['username'] = I('username');
		$data['group'] = intval(I('group'));
		$data['status'] = intval(I('status'));
		if(strlen(I('password'))>0){
			$data['password'] = md5(I('password'));
		}
		return $data;
	}
}