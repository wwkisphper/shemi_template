<?php
namespace app\smi\model;

use think\Model;

class Admin extends Model
{   
    /*
        @param char $username 登录帐号名
        @param char $password 登录密码
     */
    public function checkAdmin($username,$password){
        $password = md5($password);

        $data = $this -> field('id,value_added') -> where("username='{$username}' AND userpass='{$password}'")-> find();
        if($data){
            session('admin_id',$data['id']);
            session('admin',$username);
            session('value_added',$data['value_added']);
            return true;
        }else{
            return false;
        }
        
    }

    //查看管理员列表
    public function GetAdminList($keyword = ''){
        if($keyword){
            $data = db('Admin') -> field('id,username,appid,secret,mch_id,mch_secret,value_added') -> where("username LIKE '{$keyword}%'") -> select();
        }else{
            $data = db('Admin') -> field('id,username,appid,secret,mch_id,mch_secret,value_added') -> select(); 
        }      

        return $data;
    }
}