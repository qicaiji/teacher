<?php
namespace Home\Controller;
//use Think\Controller;
use Common\Common\Controller\AuthController;

//class UserController extends Controller {
class UserController extends AuthController {

	public function index(){
		$user = D(CONTROLLER_NAME);
		$listRows = 10;
		$this -> assign('ulist',$user->userList($listRows));
		
		//输出页码链接
		$this -> assign('page',showPage($user->count(),$listRows));
		
		$this -> display();
	}
	
	public function add(){
		if(!IS_POST){
			//添加页面
    		$this -> display();
		}else{	
			//执行添加
			$user = D(CONTROLLER_NAME);
			$data = $user->addData();
			if($data){
				if($user -> create($data)){
					$user -> add($data);
					$this -> success('添加成功！',U('index'));
				}else{
					$this -> error($user -> getError());
				}
			}else{
				$this -> error('两次输入的密码不同！');
			}
		}
	}
	
    public function edit(){
    	$user = D(CONTROLLER_NAME);
		$uid = intval(I('uid'));
		
    	if(!IS_POST){
			//用户信息
			$one = $user -> find($uid);
			if(!$one){
				$this -> error('非法参数！');
			}
			$this -> assign('one',$one);
			$this -> display();

		}else{
			$data = $user->editData();
			if($user -> create($data)){
				$user -> save($data);
				$this -> success('修改成功！',U('index'));
			}else{
				$this -> error($user -> getError());
			}
		}
    }
	
	public function del(){
		$uid = intval(I('uid'));
		$user = D(CONTROLLER_NAME);
		if(!empty($uid)){
			$map['uid'] = $uid;
			if($user -> where($map) -> delete()){
				$this -> success('删除成功！',U('index'));
			}else{
				$this -> error('删除失败！');
			}
		}
	}
	
	public function r_manage(){
		return array(
			'index' => '用户管理首页',
			'add' => '添加用户',
			'edit' => '修改用户',
			'del' => '删除用户',
		);
	}
}