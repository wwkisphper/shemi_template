<?php
namespace app\smi\model;

use think\Model;

class User extends Model
{
    public function GetUserList($adminId,$keyword){
        $user = new User();
        if($keyword){
        	$data = $user -> field('id,nickname,picture,create_time') -> where("admin_id={$adminId} AND nickname LIKE '{$keyword}%'") -> order('id DESC') -> paginate(50,false,['query' => array('keyword' => $keyword)]);
        }else{
        	$data = $user -> field('id,nickname,picture,create_time') -> where("admin_id={$adminId}") -> order('id DESC') -> paginate(50);
        }
        

        return $data;
    }
}