<?php
namespace Home\Controller;
use Think\Controller;
use Think\Verify;

class LoginController extends Controller {
    public function index(){
        if(!IS_POST){
			//错误等待
			if(cookie('wrongtimes')){
				$this -> error('已连续错了5次，请5分钟后再试！','',10);
			}
			
			//cookies直接登陆
			if(cookie('diaocha_save_login')){
				$save_login = cookie('diaocha_save_login');
				$map['username'] = $save_login[0];
				$map['password'] = $save_login[1];
				$data = M('user') -> field('password',true) -> where($map) -> find();
				$this -> doLogin($data);
			}
			
			$this -> display();
		}else{
			$verify = new Verify();
			if(!$verify->check(I('verifyImg'))){
				$this -> error('验证码错误！',U(ACTION_NAME));
			}
			
			//验证登陆信息
			$user = M('user');
			$map['username'] = I('username');
			$map['password'] = md5(I('password'));
			$data = $user -> field('password',true) -> where($map) -> find();
			
			$this -> doLogin($data);
			
		}
    }
	
	private function doLogin($data=array()){
		if(empty($data)){
			//自动登陆密码已修改，删除自动登陆的cookies
			cookie('diaocha_save_login',null);
			//累计错误次数
			$this -> wrongTimes();
			
			$this -> error('账号或密码不对！');
		}else{
			
			//登陆成功，创建session
			if($data['status']==0){
				$this -> error('对不起，此账号已被禁用，请联系管理员！');
			}
			foreach($data as $key=>$val){
				session($key,$val);
			}
			
			//保存登陆cookies
			$this -> saveCookies(365);	//一年内自动登录
			
			$this -> success('欢迎回来！'.session('username'),U('diaocha/index'),1);
			exit;
		}
	}
	
	private function wrongTimes(){
		//累计错误次数
		if(session('?wrongtimes')){
			session('wrongtimes',session('wrongtimes')+1);
		}else{
			session('wrongtimes',1);
		}
		
		//判断是否超过错误次数限制
		if(session('wrongtimes') >= 5){
			cookie('wrongtimes',5,300);
			session('wrongtimes',null);
		}
	}
	
	private function saveCookies($days = 365){
		//保存登陆信息到cookies，下次自动登陆
		if(I('save_login') == 'yes'){
			$save_login = array();
			$save_login[] = session('username');
			$save_login[] = md5(I('password'));
			cookie('save_login',$save_login,
				array('expire'=>3600 * 24 * $days, 'prefix'=>'diaocha_')
			);
		}
	}
	
	public function verifyImg(){
		//设置验证码参数
		$config = array(
			'fontSize' => 15,	//验证码字体大小
			'length' => 4,	//验证码位数
			'useNoise' => false,	//关闭验证码杂点
			//'useCurve' => false,	//取消曲线混淆
			'codeSet' => '0123456789',
			'fontttf' => '2.ttf',
			'imageH' => 30,
			'imageW' => 120,
		);
		//实例化验证码类
		$verify = new Verify($config);
		//生成一个验证码图形
		$verify->entry();
	}
	
	public function logout(){
		session('[destroy]');
		cookie('diaocha_save_login',null);
		$this->success('退出成功！',U('index/index'),1);
	}
	
	public function r_manage(){
		return array(
			
		);
	}
}