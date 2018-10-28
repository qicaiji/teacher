<?php
namespace Home\Model;
use Think\Model;

class DiaochaModel extends Model{

	public function getList($listRows=10,$map=array()){
		$list = $this -> page(I('p'),$listRows) -> where($map) -> select(); 
		return $list;
	}
	
	public function getDiaochaInfo($did){
		$one = $this -> find($did);
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
	
	//导入考试名单信息
	public function getExcelData($did){
		//判断excel后缀
		switch(strtolower(substr($_FILES['excel']['name'],-4))){
			case '.xls':
				import('Excel5','./phpexcel/PHPExcel/Reader','.php');
				$PHPReader = new \PHPExcel_Reader_Excel5();
				break;
			case 'xlsx':
				import('Excel2007','./phpexcel/PHPExcel/Reader','.php');
				$PHPReader = new \PHPExcel_Reader_Excel2007();
				break;
			default:
				return -1;
				break;
		}
		
		//读取excel内容到数组
		$filename = $_FILES['excel']['tmp_name'];
		$PHPExcel = $PHPReader -> load($filename);
		
		$dataArray = $PHPExcel -> getActiveSheet()->toArray(); 
		
		//检查表格列数是否足够
		if(count($dataArray[0]) < 4){
			return -2;
		}
		
		//创建数据模型
		$data = array();
		for($row=1;$row<count($dataArray);$row++){	//从第二行开始读取
			$data[$row-1]['grade'] = intval($dataArray[$row][0]);		//年级
			$data[$row-1]['subject'] = $dataArray[$row][1];				//学科
			$data[$row-1]['name'] = $dataArray[$row][2];				//姓名
			$data[$row-1]['fullname'] = $data[$row-1]['grade'].$data[$row-1]['subject'].$data[$row-1]['name'];
			$data[$row-1]['classes'] = $dataArray[$row][3];				//任教班级
			
			//检查导入的数据中有无重复
			for($i=0;$i<(count($data)-1);$i++){
				if($data[$row-1]['fullname'] == $data[$i]['fullname']){
					return -3;
				}
			}
		}
		//检查导入的数据是否与数据库重复
		$error_num = $this -> checkRename($data,$did);
		if($error_num){
			return (0-10-$error_num);
		}
		
		return $data;
	}
	
	private function checkRename(&$data,$did){
		$fullnames = M(TEACHER.$did) -> getField('fullname',true);
		foreach($data as $key=>$row){
			if(in_array($row['fullname'],$fullnames)){
				return ($key+1);
			}
		}
		return false;
	}


}