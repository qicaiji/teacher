<?php
namespace Home\Controller;
//use Think\Controller;
use Common\Common\Controller\AuthController;

//class CommentController extends Controller {
class CommentController extends AuthController {
	
    public function index(){
        $diaocha = D('diaocha');
		$listRows = 10;
		$map['dstatus'] = array('neq',0);
		$dlist = $diaocha->getList($listRows,$map);
		
		//统计信息
		foreach($dlist as $key=>$one){
			$dlist[$key]['count'] = M(STUDENT.$one['did']) -> where('enabled=1') -> count();
		}
		
		$this -> assign('dlist',$dlist);
		
		//页码链接
		$this -> assign('page',showPage($diaocha->count(),$listRows));
		$this -> display();
    }
	
	//自动判断是否无效评价
	public function autoenabled(){
		$did = intval(I('did'));
		$one = D('diaocha') -> getDiaochaInfo($did);
		$students = M(STUDENT.$did) -> where('enabled=1') -> select();
		
		$sids = array();		//记录全部差评的ID
		$subjects_num = count($one['subjects']);		//调查的学科总数
		$bad_choice = count($one['choices']);		//差评
		foreach($students as $s){
			$num = 0;
			foreach($one['subjects'] as $key=>$subject){
				if($s['subject'.($key+1)] == $bad_choice){
					$num++;
				}
			}
			if($num == $subjects_num){
				$sids[] = $s['sid'];
			}
		}
		
		if(empty($sids)) $this -> error('恭喜，没有无效的评价！');
		
		//设置为无效
		$map['sid'] = array('in',$sids);
		$re = M(STUDENT.$did) -> where($map) -> setField('enabled',0);
		if($re){
			$this -> success('设置成功');
		}else{
			$this -> error('设置失败');
		}
	}
	
	//添加问卷记录
	public function add(){
		//var_dump(I('param.'));exit;
		
		if(cookie('teacher_done') == '1') $this -> error('系统还在准备中，请稍后！');
		
		$did = intval(I('did'));
		$one = D('diaocha') -> find($did);
		if(!$one || $one['dstatus'] == 0){
			$this -> error('还没发布的调查！');
		}
		
		$student = M(STUDENT.$did);
		$data = I('param.');
		
		if(APP_DEBUG){
			$temp = intval(rand(1,10));
			for($i=0;$i<$temp;$i++){
				if($student -> create($data)){
					$student -> ip = get_client_ip();
					$student -> createtime = time();
					$student -> enabled = 1;
					$sid = $student -> add();
					//添加评论
					$this -> addComment($data, $sid);
					echo $i.'-'.time().'<br />';
				}
			}
			echo '<a href="'.U('diaocha/endlook?did='.I('did')).'">返回</a><br />';
		}else{
			if($student -> create($data)){
				$student -> ip = get_client_ip();
				$student -> createtime = time();
				$student -> enabled = 1;
				$sid = $student -> add();
				//添加评论
				$this -> addComment($data, $sid);
				//设置间隔时间的cookie
				$this -> makeCookie($one['retime']);
				
				$this -> success('提交成功，感谢您的参与！',U('index/endlook?did='.I('did')));
			}else{
				$this -> error('提交失败，请重新提交！');
			}
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
		
		if(trim(implode('',$comment)) == ''){
			//echo '评论全为空，无需添加';
			return true;
		}
		
		$array['studentid'] = $sid;
		$array['comment'] = json_encode($comment);
		M(ADDCOMMENT.intval($data['did'])) -> add($array);
		return true;
	}

	//详情
	public function everyone(){
		$did = intval(I('did'));
		$map = $this -> makeMap();
		
		$diaocha = D('diaocha');
		$one = $diaocha -> getDiaochaInfo($did);
		
		$data= M(STUDENT.$did) -> where($map) -> select();
		
		$this -> assign('one',$one);
		$this -> assign('data',$data);
		
		$this -> display();
	}
	
	//生成查询条件
	private function makeMap(){
		$where = I('get.');
		foreach($where as $k => $v){
			if($k == 'classes'){
				$where[$k] = array('in',$v);
			}else{
				$where[$k] = intval($v);
			}
		}
		return $where;
	}

	public function edit(){
		$did = intval(I('did'));
		$sid = intval(I('sid'));
		$student = D(STUDENT.$did);
		$one = $student -> find($sid);
		if(!$one) $this -> error('非法参数');
		
		if(!IS_POST){
			$this -> assign('did',$did);
			$this -> assign('one',$one);
			$this -> display();
		}else{
			if($student -> create(I('param.'))){
				$student -> save();
				$this -> success('修改成功！',U('everyone?did='.$did));
			}else{
				$this -> error('修改失败！');
			}
		}
	}
	
	//设置cookies，防止恶意刷票
	private function makeCookie($s){
		cookie('done', '1', array('expire'=>$s,'prefix'=>'teacher_'));
	}
	
	//生效 失效
	public function setenabled(){
		$did = intval(I('did'));
		$sid = intval(I('sid'));
		$student = D(STUDENT.$did);
		$one = $student -> find($sid);
		if(!$one) $this -> error('非法参数');
		
		if($one['enabled']){
			$re = $student -> where('sid='.$sid) -> setField('enabled',0);
		}else{
			$re = $student -> where('sid='.$sid) -> setField('enabled',1);
		}
		if($re){
			$this -> success('设置成功！');
		}else{
			$this -> error('设置失败！');
		}
	}
	
	//全部生效 或 失效
	public function setall(){
		$did = intval(I('did'));
		$student = D(STUDENT.$did);
		$status = intval(I('status'));
		if($status == 1){
			$re = $student -> where(1) -> setField('enabled',1);
		}else{
			$re = $student -> where(1) -> setField('enabled',0);
		}
		if($re){
			$this -> success('设置成功！');
		}else{
			$this -> error('设置失败！');
		}
	}
	
	public function delall(){
		$did = intval(I('did'));
		$student = D(STUDENT.$did);
		$re = $student -> where(1) -> delete();
		if($re){
			$this -> success('清理完成！');
		}else{
			$this -> error('清理失败！');
		}
	}
}

