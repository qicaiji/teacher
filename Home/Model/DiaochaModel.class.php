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
		//�꼶����
		$one['grades'] = explode('-',$one['grades']);
		//�༶����
		$one['classes'] = explode('-',$one['classes']);
		
		//������
		$one['banzhuren'] = explode('-',$one['banzhuren']);
		
		//�����ѧ�ƣ������飩
		$one['subjects'] = explode('-',$one['subjects']);
		
		//�����ѡ��
		$one['choices'] = explode('-',$one['choices']);
		
		return $one;
	}
	
	//���뿼��������Ϣ
	public function getExcelData($did){
		//�ж�excel��׺
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
		
		//��ȡexcel���ݵ�����
		$filename = $_FILES['excel']['tmp_name'];
		$PHPExcel = $PHPReader -> load($filename);
		
		$dataArray = $PHPExcel -> getActiveSheet()->toArray(); 
		
		//����������Ƿ��㹻
		if(count($dataArray[0]) < 4){
			return -2;
		}
		
		//��������ģ��
		$data = array();
		for($row=1;$row<count($dataArray);$row++){	//�ӵڶ��п�ʼ��ȡ
			$data[$row-1]['grade'] = intval($dataArray[$row][0]);		//�꼶
			$data[$row-1]['subject'] = $dataArray[$row][1];				//ѧ��
			$data[$row-1]['name'] = $dataArray[$row][2];				//����
			$data[$row-1]['fullname'] = $data[$row-1]['grade'].$data[$row-1]['subject'].$data[$row-1]['name'];
			$data[$row-1]['classes'] = $dataArray[$row][3];				//�ν̰༶
			
			//��鵼��������������ظ�
			for($i=0;$i<(count($data)-1);$i++){
				if($data[$row-1]['fullname'] == $data[$i]['fullname']){
					return -3;
				}
			}
		}
		//��鵼��������Ƿ������ݿ��ظ�
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