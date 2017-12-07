<?php
namespace app\smi\controller;
use think\Controller;

class Login extends Controller
{
    public function login()
    {   
        if($this-> request -> isPost()){
            $username = input('post.username');
            $password = input('post.password');
            
            session('remeber',input('post.remeber'));
            $status = model('Admin') -> checkAdmin($username,$password);
            if($status){
                session('remeber_name',$username);
                session('remeber_pass',$password);
                $this -> success('登录成功','Index/index');
            }else{
                $this -> error('帐号不存在或密码错误');
                
            }
        }else{
            if(session('remeber') == 1){
                $this -> assign('username',session('remeber_name'));
                $this -> assign('password',session('remeber_pass'));
                $this -> assign('remeber',session('remeber'));
            }else{
                $this -> assign('username','');
                $this -> assign('password','');
                $this -> assign('remeber',session('remeber'));
            }
            $this -> assign('title','预约模板');
            return $this -> fetch('login');
        }
    }

     /**
     * 登出
     */
    public function logout()
    {
        session('admin',null);
        session('jurisdiction',null);
        $this->redirect('Login/login');die;
    }


}