<?php
namespace app\smi\controller;


class Admin extends Base
{      

	public function _initialize()
    {	
        if(session('admin') != 'admin'){
        	die;
        }
    }

    //管理员列表
    public function AdminList()
    {
        $keyword = input('get.keyword');
        $data = model('Admin') -> GetAdminList($keyword); //获取管理员表

        $this -> assign('data',$data);

        $this -> assign('keyword',$keyword);
        return $this->fetch('admin_list');
    }

    //添加管理员
    public function AddAdmin()
    {
        $data['username'] = input('post.username');
        $data['userpass'] = md5(input('post.userpass'));
        $data['value_added'] = implode(',', input('post.value_added/a'));

        $check = db('Admin') -> field('id') -> where("username='{$data['username']}'") -> find();
        if($check){
            $this -> error('帐号已存在');
        }else{
            $success = db('Admin') -> insert($data);
            if($success){
                $this -> success('添加成功','Admin/AdminList');
            }else{
                $this -> error('添加失败');
            }
        }

    }

    //删除管理员
    public function AdminDel()
    {
        $id = input('get.id');
        $success = db('Admin') -> delete($id);
        if($success){
            $this -> success('删除成功','Admin/AdminList');
        }else{
            $this -> error('删除失败');
        }
    }

    //修改管理员
    public function AlterAdmin()
    {
        if($this-> request -> isPost()){
            $data['id'] = input('post.id');
            $data['username'] = input('post.username');
            $data['value_added'] = implode(',', input('post.value_added/a'));
            
            if(input('post.userpass')){
                $data['userpass'] = md5(input('post.userpass'));
            }

            $validate = validate('Admin');
            if(!$validate->check($data)){
                $this -> error($validate->getError());
                return false;
            }

            $success = db('Admin') -> update($data);
            if($success){
                $this -> success('修改成功','Admin/AdminList');
            }else{
                $this -> error('修改失败或信息未有变化');
            }

        }else{
            $id = input('get.id');
            $data = db('Admin') -> field('username,value_added') -> where('id',$id) -> find();
            $data['value_added'] = explode(',',$data['value_added']);

            $this -> assign('data',$data);
            $this -> assign('id',$id);

            return $this -> fetch('admin_alter');

        }
    }
}
