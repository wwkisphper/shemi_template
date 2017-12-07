<?php
namespace app\smi\controller;


class User extends Base
{   
    
    public function UserList()
    {    
        $keyword = input('get.keyword');
        $user = model('User');
        $adminId = session('admin_id');
        $data = $user -> GetUserList($adminId,$keyword); //获取用户表

        $userCount = db('user') -> where("admin_id={$adminId}") ->count();

        $this -> assign('title','用户列表');
        $this -> assign('data',$data);
        $this -> assign('userCount',$userCount);
        $this -> assign('keyword',$keyword);

        return $this->fetch('user_list');
    }

    //用户签到记录
    public function SignInList(){
        $adminId = session('admin_id');
        $list = db('Sign') 
        -> field('s.create_time,u.nickname,u.picture')
        -> alias('s')
        -> join('order_user u',"s.uid=u.id AND s.admin_id={$adminId}")
        -> order('s.id')
        -> paginate(10);

        $data = $list -> all();
        $page = $list -> render();

        $data = TimeConversions($data,'create_time');

        $this -> assign('data',$data);
        $this -> assign('page',$page);

        return $this -> fetch('sign_in_list');

    }
}
