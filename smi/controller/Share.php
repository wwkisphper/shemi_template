<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/15 0015
 * Time: 上午 10:40
 */

namespace app\smi\controller;


class Share extends Base {




    public function get_coupon(){
        // echo $_POST['id'];exit;
        if(isset($_POST['id'])){
            $pid=$_POST['id'];
            $data=db('coupon')->where('cate_id='.$pid.' and status =1')->order('addtime desc')->field('id,title,sale,minus,term,total')->select();
            // var_dump($data);exit;
        }
        // $this->ajaxReturn($data);
        return json_encode($data);
    }


    public function share_apply(){

//        $this->assign('auth',auth_check("share_apply"));

        $op = input('op');

        $admin_id = session('admin_id');

        $map = "admin_id= ".$admin_id;

        $id = input('id')?:0;

        $nowtime=time();


        if($id){

            $map = " id = {$id} ";

        }else{

            $type = input('type');

            $title = input('title');

        }

        if(!empty($title)){

            $map .= " and (name like '%{$title}%' ) ";
            $map .= " or (phone like '%{$title}%' ) ";

        }

        if($type == 1){

            $map .= " and status = 1  ";

        }else if($type == 2){

            $map .= " and status = 0 ";

        }
        $list = db('share_apply')->where($map)->order("status desc")->paginate(10);
//        dump($list);
//       dump($list) ;
        $this->assign("list",$list);
        $this->assign("title",$title);
        $this->assign("type",$type);

       return  $this->fetch('share_apply');

    }


    public function cash_apply(){

        $op = input('op');

        $admin_id = session('admin_id');

        $map = "admin_id= ".$admin_id;

        $id = input('id')?:0;

        $nowtime=time();

        if($id){

            $map = " id = {$id} ";

        }else{

            $type = input('type');

            $title = input('title');

        }

        if($title){

            $map .= " and (name like '%{$title}%' ) ";
            $map .= " or (card_number like '%{$title}%' ) ";

        }

        if($type == 1){

            $map .= " and status = 1  ";

        }else if($type == 2){

            $map .= " and status = 0 ";

        }


        $list = db("cash_apply")->where($map)->order("status desc")->paginate(10);


        $this->assign("list",$list);
        $this->assign("title",$title);
        $this->assign("list",$list);

        return $this->fetch('cash_apply');

    }

    public function share_config(){

        $admin_id = session('admin_id');

        $op = input('op');

        $id = 1;

        $data = db('share_config')->where("id = {$id} and admin_id=".$admin_id)->find();

        $this->assign('data',$data);
        if($op == 'save'){
            // dump($_REQUEST);exit;
            $data['amount'] = input('amount');
            // $data['seat_no'] = (int)input('seat_no');

            $data['one_share'] = input('one_share');

            // $data['home_show'] = input('home_show')&&$data['status']?1:0;

            $data['two_share'] = input('two_share');
            $data['switch'] = input('switch');

            // $data['start_time'] = strtotime($_REQUEST['start_time']);
            // $data['end_time'] =strtotime($_REQUEST['end_time']);

            if(!$data['amount']){$this->error("保存失败","",2);die;}



            if($id){


                $data['id'] = $id;
                // dump($data);exit;
                db('share_config')->update($data);


                $this->success('保存成功','share_config',2);

                die;

            }else{

                $data['addtime'] = time();
                $data['admin_id'] = $admin_id;
                // dump($data);exit;
                db('share_config')->insert($data);

                $this->success('保存成功','share_config',1);die;

            }

        }

        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

       return $this->fetch('share_config');

    }

    public function getAllCat(){

        $list = db('goods_cat')->select();

        $cat = array();

        foreach($list as $val){

            $cat[$val["id"]] = $val["title"];

        }

        return $cat;

    }

    public function upload($name,$folder='',$datefolder=false){

        $upload = new \Think\Upload();

        $upload->maxSize  = 52428800 ;

        $upload->exts     = array('jpg', 'gif', 'png', 'jpeg');

        if($datefolder && $folder){

            $upload->rootPath = "./Uploads/";

            $upload->savePath = "{$folder}/";

        }else{

            $upload->rootPath = './Uploads/';

            //当subName未定义而autoSub为true时，自动以日期创建目录。

            $upload->subName  = $folder?$folder:$name;

        }

        $upload->autoSub  = true;

        $info = $upload->uploadOne($_FILES[$name]);

        if(!$info) $info = $upload->getError();

        return $info;

    }

    public function pass_apply(){
        $id=$_REQUEST['id'];
        $status=$_REQUEST['status'];
        $user_id=$_REQUEST['userid'];
        $admin_id=session('admin_id');

        $save['status']=$status;
        $save['endtime']=time();
        $res=db('share_apply')->where('id='.$id.'and admin_id='.$admin_id)->update($save);

        if($status==1){
            $share['share_status']=1;
            db('user')->where('id='.$user_id)->update($share);
        }



        if($res){
            return 10000;exit;
        }else{
            return 20000;exit;
        }
    }

    public function pass_cash(){
        $id=$_REQUEST['id'];
        $status=$_REQUEST['status'];
        $user_id=$_REQUEST['userid'];
        $money=$_REQUEST['money'];

        $save['status']=$status;
        // $save['endtime']=time();
        $res=db('cash_apply')->where('id='.$id)->update($save);

        if($status==1){

            db('user')->where('id='.$user_id)->setDec('share_money',$money);
        }



        if($res){
            return 10000;exit;
        }else{
            return 20000;exit;
        }
    }

    public function share_apply_del(){
        $id=$_REQUEST['id'];

        $res=db('share_apply')->where('id='.$id)->delete();
        if($res){
            return 10000;exit;
        }else{
            return 20000;exit;
        }
    }
}