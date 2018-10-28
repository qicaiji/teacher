<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller {
	
	public function index(){
		$diaocha = D('diaocha');
		$listRows = 10;
		$map['dstatus'] = 1;
		$dlist = $diaocha->getList($listRows,$map);
		
		$this -> assign('dlist',$dlist);
		$this -> display();
	}
	
	public function beginlook(){
		$did = intval(I('did'));
		$one = D('diaocha') -> cache('did'.$did) -> find($did);
		if(!$one) $this -> error('非法参数！');
	//	$one['begininfo'] = base64_decode($one['begininfo']);
		$this -> assign('one',$one);
		$this -> display();
	}
	
	public function look(){
		if(IS_POST){
			var_dump(I('param.'));
			exit;
		}
		
		if(cookie('teacher_done') == '1') $this -> error('系统还在准备中，请稍后！');
		
		$did = intval(I('did'));
		$one = D('diaocha') -> cache('did'.$did) -> find($did);
		$one = $this -> makeInfo($one);
		if(!$one) $this -> error('非法参数！');
		
		$this -> assign('one',$one);
		$this -> display();
	}
	
	private function makeInfo($one){
		if(!$one) return array();
		//年级数组
		$one['grades'] = explode('-',$one['grades']);
		//班级数组
		$one['classes'] = explode('-',$one['classes']);
		
		//班主任
		$one['banzhuren'] = explode('-',$one['banzhuren']);
		
		//调查的学科（教研组）
		$one['subjects'] = explode('-',$one['subjects']);
		
		//调查的选项
		$one['choices'] = explode('-',$one['choices']);
		
		return $one;
	}
	
	public function endlook(){
		$did = intval(I('did'));
		$one = D('diaocha') -> cache('did'.$did) -> find($did);
		if(!$one) $this -> error('非法参数！');
	//	$one['endinfo'] = base64_decode($one['endinfo']);
		$this -> assign('one',$one);
		$this -> display();
	}
	
	//添加问卷记录
	public function add(){
		
		if(cookie('teacher_done') == '1') $this -> error('系统还在准备中，请稍后！');
		
		$did = intval(I('did'));
		$one = D('diaocha') -> find($did);
		if(!$one || $one['dstatus'] == 0){
			$this -> error('还没发布的调查！');
		}
		
		$student = M(STUDENT.$did);
		$data = I('param.');
		
		if($student -> create($data)){
			$student -> ip = get_client_ip();
			$student -> createtime = time();
			$student -> enabled = 1;
			$sid = $student -> add();
			//添加评论
			$this -> addComment($data, $sid);
			//设置间隔时间的cookie
			$this -> makeCookie($one['retime']);
			
			$this -> success('提交成功，感谢您的参与！',U('endlook?did='.I('did')));
		}else{
			$this -> error('提交失败，请重新提交！');
		}
	}
	
	//补充意见
	private function addComment($data,$sid){
		$addcomment = M('diaocha') -> where('did='.intval($data['did'])) -> getField('addcomment');
		if(!$addcomment) return true;
		$comment = array();
		foreach($data as $key=>$val){
			if(substr($key,-3) == 'add'){
				$comment[$key] = $val;
			}
		}
		$array['studentid'] = $sid;
		$array['comment'] = json_encode($comment);
		M(ADDCOMMENT.intval($data['did'])) -> add($array);
		return true;
	}
	
	//设置cookies，防止恶意刷票
	private function makeCookie($s){
		cookie('done', '1', array('expire'=>$s,'prefix'=>'teacher_'));
	}
	
	

}