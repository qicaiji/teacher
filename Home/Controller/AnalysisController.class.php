<?php
namespace Home\Controller;
//use Think\Controller;
use Common\Common\Controller\AuthController;

//class AnalysisController extends Controller {
class AnalysisController extends AuthController {
	
    public function index(){
		$diaocha = D('diaocha');
		$listRows = 10;
		$map['dstatus'] = array('neq',0);
		
		$dlist = $diaocha->getList($listRows,$map);
		
		$this -> assign('dlist',$dlist);
		//页码链接
		$this -> assign('page',showPage($diaocha->count(),$listRows));
		$this -> display();
	}
	
	//计算问卷得分
	public function compute(){
		$did = intval(I('did'));
		$one = D('diaocha') -> getDiaochaInfo($did);
		if(!$one || $one['dstatus'] == 0) $this -> error('还没发布的调查！');

		$choice_num = count($one['choices']);
		
		//创建个人分析表
		$this -> createPerson($did,$choice_num);
		
		//获取教师信息列表
		$teachers = M(TEACHER.$did) -> select();
		
		//获取学生评价列表
		$students = M(STUDENT.$did) -> where('enabled=1') -> select();
		
		//开始统计
		$result = array();
		foreach($teachers as $key=>$t){
			//把教师的任教班级字符串改为数组
			$t['classes'] = explode(',',$t['classes']);
			
			//查找教师任教学科的索引值 +1
			$index = array_search($t['subject'],$one['subjects']) + 1;
			
			$result[$key]['teacherid'] = $t['tid'];
			//初始化统计数据：全部为0
			$result[$key]['total'] = 0;
			for($i=1;$i<=$choice_num;$i++){
				$result[$key]['choicenum'.$i] = 0;
			}
			
			//统计数量
			foreach($students as $s){
				//对比项目：年级、班级
				if($t['grade']==$s['grade'] && in_array($s['classes'],$t['classes'])){
					if($s['subject'.$index] != -1){
						$result[$key]['total']++;
						$student_choice = $s['subject'.$index];
						$result[$key]['choicenum'.$student_choice]++;
					}
				}
			}
			
			//计算百分比
			for($i=1;$i<=$choice_num;$i++){
				if($result[$key]['total'] == 0){
					$result[$key]['choicepercent'.$i] = 0;
				}else{
					$result[$key]['choicepercent'.$i] = round($result[$key]['choicenum'.$i] * 100 / $result[$key]['total'],2);
				}
			}
		}
		
		//计算失败：没有导入教师
		if(!$result) $this -> error('先把导入了教师信息再说吧！');
		
		$re = M(PERSON.$did) -> addAll($result);
		
		if($re){
			$this -> success('计算完成，快去查看各种分析数据吧！');
		}else{
			$this -> error('计算失败！');
		}
	}
	
	//创建个人分析表
	private function createPerson($did,$choice_num){
		$str = '';
		for($i=1;$i<=$choice_num;$i++){
			$str .= "`choicenum{$i}` int(4) null,";
		}
		for($i=1;$i<=$choice_num;$i++){
			$str .= "`choicepercent{$i}` float(4) null,";
		}
		
		$sql = 'drop table if exists '.C('DB_PREFIX').PERSON.$did.';'.
				'CREATE TABLE IF NOT EXISTS `'.C('DB_PREFIX').PERSON.$did.'` (
				`id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`teacherid` int(6) UNSIGNED NOT NULL ,
				`total` int(5) null,
				'.$str.'
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;';
				
		$model = M();
		$model -> execute($sql);
		$re = $model -> query('SHOW TABLES LIKE "'.C('DB_PREFIX').PERSON.$did.'"');
		if($re){
			return true;
		}else{
			return false;
		}
	}
	
	//查看个人分析数据
	public function showperson(){
		$did = intval(I('did'));
		
		//判断是否已计算
		if(!existsTable(PERSON.$did)) $this -> error('请先计算！');
		
		//调查的选项
		$one = D('diaocha') -> getDiaochaInfo($did);
		
		//join教师基本信息表和个人评价表
		if(intval(I('orderby')) == 0){
			$data = M(TEACHER.$did) -> alias('a') -> 
					join(C('DB_PREFIX').PERSON.$did.' b on a.tid=b.teacherid') -> 
					order('grade,subject') ->
					select();
		}else{
			$data = M(TEACHER.$did) -> alias('a') -> 
					join(C('DB_PREFIX').PERSON.$did.' b on a.tid=b.teacherid') -> 
					select();
		}
		
		$this -> assign('one',$one);
		$this -> assign('list',$data);
		$this -> display();
	}
	
	//教研组分析
	public function showsubjects(){
		$did = intval(I('did'));
		
		//判断是否已计算
		if(!existsTable(PERSON.$did)) $this -> error('请先计算！');
		
		$one = D('diaocha') -> getDiaochaInfo($did);

		$choice_num = count($one['choices']);
		
		//join教师基本信息表和个人评价表
		$data = M(TEACHER.$did) -> alias('a') -> 
				join(C('DB_PREFIX').PERSON.$did.' b on a.tid=b.teacherid') -> 
				select();
		
		//按学科统计，数组结构：array[学科][选项]
		$array = array();
		//先把所有学科的评价选项都填充为0
		foreach($one['subjects'] as $key=>$subject){
			for($i=1;$i<=$choice_num;$i++){
				$array[$key]['choicenum'.$i] = 0;
			}
			$array[$key]['total'] = 0;
		}
		
		//遍历每个教师的数据，累加
		foreach($data as $t){
			//查找教师任教学科的索引值
			$s = array_search($t['subject'],$one['subjects']);
			//如果学科不存在则跳过
			if($s === false) continue;
			for($i=1;$i<=$choice_num;$i++){
				$array[$s]['choicenum'.$i] += $t['choicenum'.$i];
				$array[$s]['total'] += $t['choicenum'.$i];
			}
		}
		
		$this -> assign('one',$one);
		$this -> assign('list',$array);
		$this -> display();
	}
	
	//备课组分析
	public function showbeikezu(){
		$did = intval(I('did'));
		
		//判断是否已计算
		if(!existsTable(PERSON.$did)) $this -> error('请先计算！');
		
		$one = D('diaocha') -> getDiaochaInfo($did);

		$choice_num = count($one['choices']);
		
		//join教师基本信息表和个人评价表
		$data = M(TEACHER.$did) -> alias('a') -> 
				join(C('DB_PREFIX').PERSON.$did.' b on a.tid=b.teacherid') -> 
				select();
		
		//数组结构：array[年级][学科][选项]
		$array = array();
		//先把所有学科的评价选项都填充为0
		foreach($one['grades'] as $g=>$grade){
			foreach($one['subjects'] as $s=>$subject){
				for($i=1;$i<=$choice_num;$i++){
					$array[$g][$s]['choicenum'.$i] = 0;
				}
				$array[$g][$s]['total'] = 0;
			}
		}
		
		//遍历每个教师的数据，累加
		foreach($data as $t){
			//年级索引，教师年级信息是1-6，数据库中是0-5，所以 -1
			$g = $t['grade'] - 1;
			//如果年级不存在，则跳过
			if($g >= count($one['grades'])) continue;
			
			//查找教师任教学科的索引值
			$s = array_search($t['subject'],$one['subjects']);
			//如果学科不存在则跳过
			if($s === false) continue;
			
			for($i=1;$i<=$choice_num;$i++){
				$array[$g][$s]['choicenum'.$i] += $t['choicenum'.$i];
				$array[$g][$s]['total'] += $t['choicenum'.$i];
			}
			
		}
		//var_dump($array);
		
		$this -> assign('one',$one);
		$this -> assign('array',$array);
		$this -> display();
	}

	//年级分析
	public function showgrade(){
		$did = intval(I('did'));
		
		//判断是否已计算
		if(!existsTable(PERSON.$did)) $this -> error('请先计算！');
		
		$one = D('diaocha') -> getDiaochaInfo($did);

		$choice_num = count($one['choices']);
		
		//join教师基本信息表和个人评价表
		$data = M(TEACHER.$did) -> alias('a') -> 
				join(C('DB_PREFIX').PERSON.$did.' b on a.tid=b.teacherid') -> 
				select();
		
		//数组结构：array[年级][选项]
		$array = array();
		//先把所有学科的评价选项都填充为0
		foreach($one['grades'] as $key=>$g){
			for($i=1;$i<=$choice_num;$i++){
				$array[$key]['choicenum'.$i] = 0;
			}
			$array[$key]['total'] = 0;
		}
		
		//遍历每个教师的数据，累加
		foreach($data as $t){
			//年级索引，教师年级信息是1-6，数据库中是0-5，所以 -1
			$g = $t['grade'] - 1;
			//如果年级不存在，则跳过
			if($g >= count($one['grades'])) continue;
			
			for($i=1;$i<=$choice_num;$i++){
				$array[$g]['choicenum'.$i] += $t['choicenum'.$i];
				$array[$g]['total'] += $t['choicenum'.$i];
			}
			
		}
		
		$this -> assign('one',$one);
		$this -> assign('list',$array);
		$this -> display();
	}
	
	//班主任
	public function showbanzhuren(){
		$did = intval(I('did'));
		$one = D('diaocha') -> getDiaochaInfo($did);
		if(!$one || $one['dstatus'] == 0) $this -> error('还没发布的调查！');
		
		//获取学生评价列表
		$students = M(STUDENT.$did) -> order('grade,classes') -> where('enabled=1') -> select();
		
		//开始统计 array[年级_班级][选择]++
		$result = array();
		foreach($students as $s){
			//年级_班级
			$gc = $s['grade'].'_'.$s['classes'];
			$c = $s['banzhuren'];
			isset($result[$gc]['choice'.$c]) ? $result[$gc]['choice'.$c]++ : $result[$gc]['choice'.$c] = 1;
			$result[$gc]['total'] ++;
			
		}
		
		$this -> assign('one',$one);
		$this -> assign('list',$result);
		$this -> display();
	}
	
}