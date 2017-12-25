<?php
namespace app\smi\controller;


class Homepage extends Base {

    /* ┏┳┳┳┳┳┳┳┳┳┳轮播管理┳┳┳┳┳┳┳┳┳┳┓ */

    public function swiper(){

        $admin_id=session('admin_id');

        $swiper = db('homepage_swiper')->where("admin_id=".$admin_id)->order("seat_no desc,create_time desc")-> paginate(10);




        $this->assign('swiper',$swiper);


        return $this->fetch('swiper');

    }

    public function swiper_op(){

        $admin_id=session('admin_id');

        $op = input('op');

        $id = input('id')?:0;

        if($id){

            $swiper = db('homepage_swiper')->where("id = $id and admin_id=".$admin_id)->find();

            if(!$swiper){$this->error('该轮播图不存在','',2);die;}

        }

        if($op == 'save'){

            $data['seat_no'] = input('seat_no');

            $data['link'] = input('link');
            $data['admin_id'] = $admin_id;

            if($_FILES['img']['name']){

                $return = upload_goods('img','homepage_swiper');

                $data['img'] = "homepage_swiper/".$return;

            }

            if(!$data['img'] && !$id){$this->error('保存失败','swiper',2);die;}

            if($id){

                if($data['img']){

                    unlink("./uploads/".$swiper['img']);

                }

                $data['id'] = $id;

                db('homepage_swiper')->update($data);

                $this->success('保存成功','',2);die;

            }else{

                $data['create_time'] = time();

                $add_id = db('homepage_swiper')->insert($data);

                $this->success('保存成功','swiper',2);die;

            }

        }

        if($op == 'dele'){
            $check = CheckPermissions($admin_id,'goods','admin_id',$id);  //检查商户是否有权限操作该记录
            if(!$check){
                die;
            }
            unlink("./Uploads/".$swiper['img']);

            db('homepage_swiper')->where("id = $id")->delete();

            $this->success('删除成功','',2);die;

        }

    }

    public function notice_op(){


        $admin_id=session('admin_id');;

        $data['content'] = input('content');
        $data['admin_id'] = $admin_id;

        $res=  db('notice')->where('id=1')->save($data);
//        dump($data);die;
        if($res){
            $this->success('保存成功','',2);die;
        }else{
            $this->error('保存失败','swiper',2);die;
        }





    }
    /* ┗┻┻┻┻┻┻┻┻┻┻轮播管理┻┻┻┻┻┻┻┻┻┻┛ */




}