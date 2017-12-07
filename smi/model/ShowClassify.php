<?php
namespace app\smi\model;

use think\Model;

class ShowClassify extends Model
{
	/*
		获取该商户的分类管理id字符串
		@param array $data 分类数组结果集
	 */
	public function getAdminClassifyId($data){
		$string = '';
		foreach ($data as $key => $value) {
			$string .= "{$value['id']},";
		}

		return rtrim($string,',');
	}

	/*
		整理好可遍历输出的展示数组
		@param array $classifyData 	//分类数组集
		@param array $showData //图片数组集
	 */
	public function TidyShowData($classifyData,$showData){
		
		foreach ($classifyData as $key => $value) {
			$classifyData[$key]['contents'] = array();
			foreach ($showData as $k => $val) {
				if($val['sc_id'] == $value['id']){
					$classifyData[$key]['contents'][] = $val;
					unset($showData[$k]);
				}
			}
		}

		return $classifyData;
	}
}