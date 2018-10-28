<?php
namespace Home\Controller;
//use Think\Controller;
use Common\Common\Controller\AuthController;

//class DiaochaController extends Controller {
class DiaochaController extends AuthController {
	
	public function index(){
		$diaocha = D(CONTROLLER_NAME);
		$listRows = 10;
		$dlist = $diaocha->getList($listRows);
		
		//统计已导入的教师信息数
		foreach($dlist as $key=>$one){
			if($one['dstatus'] != 0){
				$dlist[$key]['teachers'] = M(TEACHER.$one['did']) -> count();
			}
		}
		
		$this -> assign('dlist',$dlist);
		
		//页码链接
		$this -> assign('page',showPage($diaocha->count(),$listRows));
		$this -> display();
	}
	
	//查看已导入的教师
	public function showteachers(){
		$did = intval(I('did'));
		$teacher = M(TEACHER.$did) -> select();
		$this -> assign('tlist',$teacher);
		$this -> display();
	}
	public function editteacher(){
		$did = intval(I('did'));
		$tid = intval(I('tid'));
		$teacher = D(TEACHER.$did);
		$one = $teacher -> find($tid);
		if(!$one) $this -> error('非法参数');
		
		if(!IS_POST){
			$this -> assign('did',$did);
			$this -> assign('one',$one);
			$this -> display();
		}else{
			if($teacher -> create(I('param.'))){
				$teacher -> fullname = I('grade').I('subject').I('name');
				$teacher -> save();
				$this -> success('修改成功！',U('showteachers?did='.$did));
			}else{
				$this -> error($teacher -> getError());
			}
		}
	}
	public function delteacher(){
		$did = intval(I('did'));
		$tid = intval(I('tid'));
		$re = M(TEACHER.$did) -> delete($tid);
		if($re){
			$this -> success('删除成功！');
		}else{
			$this -> error('删除失败！');
		}
	}
	public function delallteacher(){
		$did = intval(I('did'));
		$re = M(TEACHER.$did) -> where(1) -> delete();
		if($re){
			$this -> success('清理完成！');
		}else{
			$this -> error('清理失败！');
		}
	}
	
	//导入教师 Excel
	public function importteacher(){
		$did = intval(I('did'));
		$teacher = M(TEACHER.$did);
		$teachers = $teacher -> field('tid,grade,subject,name,classes') -> select();
		
		if(!IS_POST){
			$this -> assign('did',$did);
			$this -> assign('tlist',$teachers);
			$this -> display();
		}else{
			$data = D(CONTROLLER_NAME) -> getExcelData($did);
			if($data == -1){
				$this -> error('不支持的文件格式！');
			}
			if($data == -2){
				$this -> error('表格列数不够！必须包含4列：年级，学科，姓名，班级','',10);
			}
			if($data == -3){
				$this -> error('表格存在重复的教师信息，请检查！','',10);
			}
			if($data < -3){
				$this -> error('第'.abs($data+10).'个教师信息已导入过，请删除后再导入！','',10);
			}
			
			if($teacher ->addAll($data)){
				$this -> success('导入成功！');
			}else{
				$this -> error('导入失败！');
			}
		}
	}
	
	public function beginlook(){
		$did = intval(I('did'));
		$one = D(CONTROLLER_NAME) -> field('did,dname,begininfo') -> find($did);
		if(!$one) $this -> error('非法参数！');
		//$one['begininfo'] = base64_decode($one['begininfo']);
		$this -> assign('one',$one);
		$this -> display();
	}
	
	public function look(){
		if(IS_POST){
			var_dump(I('param.'));
			exit;
		}
		
		$did = intval(I('did'));
		$one = D(CONTROLLER_NAME) -> getDiaochaInfo($did);
		if(!$one) $this -> error('非法参数！');
		//$one['begininfo'] = base64_decode($one['begininfo']);
		//$one['endinfo'] = base64_decode($one['endinfo']);
		
		$this -> assign('one',$one);
		$this -> display();
	}
	
	public function endlook(){
		$did = intval(I('did'));
		$one = D(CONTROLLER_NAME) -> field('did,dname,endinfo') -> find($did);
		if(!$one) $this -> error('非法参数！');
		//$one['endinfo'] = base64_decode($one['endinfo']);
		$this -> assign('one',$one);
		$this -> display();
	}
	
	public function setstatus(){
		//调查状态：0 未发布，1 使用中，2 已停止
		$s = intval(I('s'));	
		$did = intval(I('did'));
		$diaocha = D(CONTROLLER_NAME);
		$one = $diaocha -> getDiaochaInfo($did);
		
		switch($s){
			case 0:
				$diaocha -> startTrans();	//开启事务
				$re1 = $diaocha -> where(array('did'=>$did)) -> setField('dstatus',0);		//清空
				//删除教师基本信息表、学生评价表补充意见表
				$re2 = $this-> dropTable($did);
				
				if($re1 && $re2){
					$diaocha -> commit();	//真正提交数据库
					$this -> success('还原成功！',U('index'));
				}else{
					$diaocha -> rollback();	//事务回滚
					$this -> error('还原失败！');
				}
				break;
			case 1:
				$diaocha -> startTrans();	//开启事务
				//发布
				$re1 = $diaocha -> where(array('did'=>$did)) -> setField('dstatus',1);
				
				//创建教师基本信息表
				$re2 = $this->createTeacherBaseinfo($did);
				
				//创建学生评价表
				$re3 = $this -> createStudentOrder($one);
				
				//创建补充意见表
				if($one['addcomment'] == 1) $re4 = $this -> createAddcomment($did);
				isset($re4) ? $re4 : $re4 = true;
				
				if($re1 && $re2 && $re3 && $re4){
					$diaocha -> commit();	//正式提交
					$this -> success('发布成功！',U('index'));
				}else{
					$diaocha -> rollback();	//事务回滚
					$this -> error('发布失败！');
				}
				break;
			
			case 2:
				$re1 = $diaocha -> where(array('did'=>$did)) -> setField('dstatus',2);		//停止调查
				if($re1){
					$this -> success('停止成功！',U('index'));
				}else{
					$this -> error('停止失败！');
				}
				break;
			default:
				$this -> error('非法参数！');
		}
	}
	
	public function add(){
		if(!IS_POST){
    		$this -> display();
		}else{	
			//执行添加
			$diaocha = D(CONTROLLER_NAME);
			if($diaocha -> create(I('param.'))){
				$diaocha -> begininfo = $_POST['begininfo'];
				$diaocha -> endinfo = $_POST['endinfo'];
				$diaocha -> dstatus = 0;
				$diaocha -> createtime = time();
				$diaocha -> add();
				$this -> success('添加成功！',U('index'));
			}else{
				$this -> error('添加失败！');
			}
		}
	}
	
    public function edit(){
    	$diaocha = D(CONTROLLER_NAME);
		$did = intval(I('did'));
		
    	if(!IS_POST){
			$one = $diaocha -> find($did);
			if(!$one){
				$this -> error('非法参数！');
			}
		//	$one['begininfo'] = base64_decode($one['begininfo']);
		//	$one['endinfo'] = base64_decode($one['endinfo']);
			$this -> assign('one',$one);
			//var_dump($one);
			$this -> display();
		}else{
			if($diaocha -> create(I('param.'))){
				$diaocha -> begininfo = $_POST['begininfo'];
				$diaocha -> endinfo = $_POST['endinfo'];
				//var_dump($diaocha);exit;
				
				$diaocha -> save();
				
				//清理缓存
				S('did'.$did,null);
				
				$this -> success('修改成功！',U('index'));
			}else{
				$this -> error($diaocha -> getError());
			}
		}
    }
	
	public function del(){
		$did = intval(I('did'));
		$diaocha = D(CONTROLLER_NAME);
		$one = $diaocha -> find($did);
		if($one['dstatus'] != 0) $this -> error('已发布的调查不能删除，请先清空！');
		
		$map['did'] = $did;
		if($diaocha -> where($map) -> delete()){
			$this -> success('删除成功！',U('index'));
		}else{
			$this -> error('删除失败！');
		}
	}
	
	//创建教师基本信息表
	private function createTeacherBaseinfo($did){
		$sql = 'CREATE TABLE IF NOT EXISTS `'.C('DB_PREFIX').TEACHER.$did.'` (
				`tid` int(6) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`grade` char(50) not null,
				`subject` char(20) not null,
				`name` char(10) not null,
				`fullname` char(30) not null,
				`classes` char(100) not null,
				PRIMARY KEY (`tid`),
				UNIQUE INDEX `fullname` (`fullname`) 
				) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;';
				
		$model = M();
		$model -> execute($sql);
		$re = $model -> query('SHOW TABLES LIKE "'.C('DB_PREFIX').TEACHER.$did.'"');
		if($re){
			return true;
		}else{
			return false;
		}
	}
	
	//创建学生评价表
	private function createStudentOrder($one){
		$str = '';
		for($i=1;$i<=count($one['subjects']);$i++){
			$str .= "`subject{$i}` int(1) null,";
		}
		
		$sql = 'CREATE TABLE IF NOT EXISTS `'.C('DB_PREFIX').STUDENT.$one['did'].'` (
				`sid` int(6) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`grade` int(2) not null,
				`classes` int(2) not null,
				`banzhuren` int(2) null,
				'.$str.'
				`ip` char(20) not null,
				`createtime` char(20) not null,
				`enabled` int(1) null default 1,
				
				PRIMARY KEY (`sid`)
				) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;';
				
		$model = M();
		$model -> execute($sql);
		$re = $model -> query('SHOW TABLES LIKE "'.C('DB_PREFIX').STUDENT.$one['did'].'"');
		if($re){
			return true;
		}else{
			return false;
		}
	}
	
	//创建补充意见表
	private function createAddcomment($did){
		$sql = 'CREATE TABLE IF NOT EXISTS `'.C('DB_PREFIX').ADDCOMMENT.$did.'` (
				`id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`studentid` int(6) UNSIGNED NOT NULL ,
				`comment` text(10000) null,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;';
				
		$model = M();
		$model -> execute($sql);
		$re = $model -> query('SHOW TABLES LIKE "'.C('DB_PREFIX').ADDCOMMENT.$did.'"');
		if($re){
			return true;
		}else{
			return false;
		}
	}
	
	//删除表
	private function dropTable($did){
		$sql = "drop table if exists `".C('DB_PREFIX').TEACHER.$did.'`;';
		$sql .= "drop table if exists `".C('DB_PREFIX').STUDENT.$did.'`;';
		$sql .= "drop table if exists `".C('DB_PREFIX').ADDCOMMENT.$did.'`;';
		
		$model = M();
		$model -> execute($sql);
		$re = $model -> query('SHOW TABLES LIKE "'.C('DB_PREFIX').STUDENT.$did.'"');
		if(!$re){
			return true;
		}else{
			return false;
		}
	}
	
	public function r_manage(){
		return array(
			'index' => '调查管理首页',
			'add' => '添加调查',
			'edit' => '修改调查',
			'del' => '删除调查',
		);
	}
}