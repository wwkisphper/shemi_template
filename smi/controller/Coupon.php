<?php
namespace app\smi\controller;


class Coupon extends Base {


    public function coupon_activi_list(){

        //总活动数量
        $admin_id= session('admin_id');
        // $count=db('coupon_activi')->where('status !=5')->count('id');
        // var_dump($count);exit;
        // $this->assign('count',$count);

//    //auth_alert('coupon_activi_list');

//    $this->assign('auth',auth_check("coupon_activi_list"));

        $op = input('op');

        $map = " admin_id=".$admin_id;

        $id = input('id')?:0;

        $nowtime=time();

        if($id){

            $map = " id = {$id} ";

        }else{

            $type = input('type');

            $title = input('title');

        }

        if($title){

            $map .= " and (title like '%{$title}%' ) ";

        }

        if($type == 1){

            $map .= " and status = 1 and end_time < ".$nowtime;

        }else if($type == 2){

            $map .= " and status = 0 or end_time >".$nowtime;

        }



        $list = db("coupon_activi")->where($map)->order("status desc")-> paginate(10);
        // var_dump($list);exit;
        $cat = $this->getAllCat();

//    foreach($list as &$val){
//
//
//      $val["img"] = __ROOT__."/Uploads/coupon_activi/".$val['img'];
//
//      $val["cat_name"] = $cat[$val["cate_id"]];
//
//      $val["status_name"] = $val['status']==1?"上架":"下架";
//
//    }

        if($op == "export"){

            $data = array(array('活动ID','所属分类','活动标题','活动时间','库存数量','状态','添加时间'));

            unset($val);

            foreach($list as $val){

                $data[] = array($val["id"],$val["cat_name"],$val["title"],date('Y-m-d H:i',$val["start_time"])."-".date('Y-m-d H:i',$val["end_time"]),$val["total"],$val["status_name"],date("Y-m-d H:i",$val["addtime"]));

            }

            create_xls($data,'活动列表_'.date("Y-m-d H:i"));

            die;

        }

        $this->assign("list",$list);
        $this->assign("title",$title);
        $this->assign("type",$type);

        return $this->fetch();

    }

    public function coupon_activi_op(){

        //auth_alert("coupon_activi_list");

        $op = input('op');

        $id = $_REQUEST['id']?:0;

        $admin_id= session('admin_id');

        $pid_lists=db('goods_cat')->where('pid=1 and admin_id='.$admin_id)->field('id,title')->select();
        // var_dump($pid_lists);exit;
        // var_dump($_POST);exit;
        if($op == 'save'){
            // dump($_REQUEST);exit;
            $data['cate_id'] = 1;
            $data['cate_id2'] = (int)input('cate_id');
            $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            // $data['home_show'] = input('home_show')&&$data['status']?1:0;
            if(empty($_REQUEST['coupon'])){$this->error("请选择活动优惠券","",2);die;}
            $data['coupon_id']=implode(",",$_REQUEST['coupon']);
            $data['title'] = input('title');
            $data['content'] = input('content');
            if($_REQUEST['start_time']){
                $data['start_time'] = strtotime($_REQUEST['start_time']);
            }
            if($_REQUEST['end_time']){
                $data['end_time'] =strtotime($_REQUEST['end_time']);
            }
            $data['admin_id'] = session('admin_id');
            if(!$data['title']){$this->error("保存失败","",2);die;}

            if($_FILES['img']['name']){

                $return = upload_goods('img',"coupon_activi");

                $data['img'] = "coupon_activi/".$return;


            }

            if(empty($data['img']) && empty($id)){$this->error('保存失败','',2);die;}

            if($id){

                if(!empty($data['img'])){

                    $cat = db('coupon_activi')->where("id = {$id}")->find();

                    unlink($cat['img']);

                }

                $data['id'] = $id;
                // dump($data);exit;
                db('coupon_activi')->update($data);

                $url = session(__FUNCTION__.'_jump')?:'';

                session(__FUNCTION__.'_jump',null);

                $this->success('保存成功',$url,2);

                die;

            }else{

                $data['addtime'] = time();
                // dump($data);exit;
                db('coupon_activi')->insert($data);

                $this->success('保存成功','coupon_activi_list',1);die;

            }

        }else if($op == 'edit'){

            $data = db('coupon_activi')->where("id = {$id}")->find();

            $activi_coupon=explode(",", $data['coupon_id']);
            // dump($activi_coupon);exit;
            if($data['cate_id']==0&&$data['cate_id2']==0){
                $coupon_lists=db('coupon')->where('cate_id=0 and cate_id2=0 and status=1')->select();
            }
            if($data['cate_id']!=0&&$data['cate_id2']==0){
                $coupon_lists=db('coupon')->where('cate_id='.$data['cate_id'].' and status=1')->select();
            }
            if($data['cate_id']!=0&&$data['cate_id2']!=0){
                $coupon_lists=db('coupon')->where('cate_id='.$data['cate_id'].' and status=1 and cate_id2='.$data['cate_id2'])->select();
            }
//      dump($coupon_lists);
            $two_lists=db('goods_cat')->where('pid='.$data['cate_id'])->field('id,title')->select();
            $this->assign("two_lists",$two_lists);


            // dump($coupon_lists);exit;
            if(!$data){$this->error('该活动不存在','',2);die;}

            $this->assign('activi_coupon',$activi_coupon);
            $this->assign('coupon_lists',$coupon_lists);
            $this->assign('data',$data);
            $this->assign('id',$id);
//       dump($data);
        }else if($op == 'dele'){

            db('coupon_activi')->where("id = {$id}")->delete();

            $this->success('删除成功','coupon_activi_list',2);die;

        }
        $this->assign('pid_lists',$pid_lists);
        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return $this->fetch('coupon_activi_op');

    }

    public function coupon_activi_add(){

        //auth_alert("coupon_activi_list");

        $op = input('op');

        $admin_id=session('admin_id');


        $pid_lists=db('goods_cat')->where('pid=1 and admin_id='.$admin_id)->field('id,title')->select();
        // var_dump($pid_lists);exit;
        // var_dump($_POST);exit;
        if($op == 'save'){
            // dump($_REQUEST);exit;
            $data['cate_id'] = 1;
            $data['cate_id2'] = (int)input('cate_id');
            $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            // $data['home_show'] = input('home_show')&&$data['status']?1:0;
            $data['coupon_id']=implode(",",$_REQUEST['coupon']);
            $data['title'] = input('title');
            $data['content'] = input('content');
            $data['start_time'] = strtotime($_REQUEST['start_time']);
            $data['end_time'] =strtotime($_REQUEST['end_time']);
            $data['admin_id'] = $admin_id;
            if(!$data['title']){$this->error("保存失败","",2);die;}

            if($_FILES['img']['name']){

                $return = upload_goods('img',"coupon_activi");

                $data['img'] = "coupon_activi/".$return;

            }

            if(!$data['img']){$this->error('保存失败','',2);die;}

            $data['addtime'] = time();
            // dump($data);exit;
            db('coupon_activi')->insert($data);

            $this->success('保存成功','coupon_activi_list',1);die;


        }
        $this->assign('pid_lists',$pid_lists);
        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return $this->fetch('coupon_activi_add');

    }

    public function get_coupon(){
        // echo $_POST['id'];exit;
        // dump($_REQUEST);
        $admin_id= session('admin_id');
        $where['status']=1;
        $where['admin_id']=$admin_id;
        $pid=$_POST['id'];
//    $pid2=$_POST['id2'];

        if($pid!=NULL){
            // echo 111;
            $where['cate_id']=1;
            $where['cate_id2']=$pid;
        }

        // dump($where);
        $data=db('coupon')->where($where)->order('addtime desc')->select();
        // echo db()->getlastsql();exit;
        // $this->ajaxReturn($data);
        echo json($data);
    }


    public function coupon_list(){

        //总优惠券数量
        $admin_id= session('admin_id');
        // $count=db('coupon_activi')->where('status !=5')->count('id');
        // var_dump($count);exit;
        // $this->assign('count',$count);

        //auth_alert('coupon_list');

//    $this->assign('auth',auth_check("coupon_list"));

        $op = input('op');

        $map = " admin_id= ".$admin_id;

        $id = input('id')?:0;

        $nowtime=time();

        if($id){

            $map = " id = {$id} ";

        }else{

            $type = input('type');

            $title = input('title');

        }

        if($title){

            $map .= " and (title like '%{$title}%' ) ";

        }

        if($type == 1){

            $map .= " and status = 1 and end_time < ".$nowtime;

        }else if($type == 2){

            $map .= " and status = 0 or end_time >".$nowtime;

        }

        $list = db("coupon")->where($map)->order("status desc")->paginate(10)->each(function($item, $key){

            $cat = $this->getAllCat();
            if($item["cate_id2"]==0) {
                $item["cat_name"] = '无限制类型';
            }else{
                $item["cat_name"] = $cat[$item["cate_id2"]];
            }
            $item["status_name"] = $item['status']==1?"上架":"下架";
            return $item;
        });


        $this->assign("list",$list);
        $this->assign("title",$title);
        $this->assign("type",$type);

        return $this->fetch();

    }

    public function coupon_op(){

        //auth_alert("coupon_list");

        $op = input('op');
        $admin_id= session('admin_id');
        $id = input('id')?:0;

        $pid_lists=db('goods_cat')->where('pid=1')->field('id,title')->select();
        // var_dump($pid_lists);exit;
        // var_dump($_POST);exit;
        if($op == 'save'){
            // dump($_REQUEST);exit;
            $data['cate_id'] = 1;//(int)input('cate_id');
            $data['cate_id2'] = (int)input('cate_id');
            // $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            // $data['home_show'] = input('home_show')&&$data['status']?1:0;
            $data['admin_id'] = session('admin_id');
            $data['title'] = input('title');
            $data['sale'] = input('sale');
            $data['minus'] = input('minus');
            $data['total'] = input('total');
            $data['term'] = input('term');
            $data['type'] = input('type');
            // $data['start_time'] = strtotime($_REQUEST['start_time']);
            // $data['end_time'] =strtotime($_REQUEST['end_time']);

            if(!$data['title']){$this->error("保存失败","",2);die;}

            // if($_FILES['img']['name']){

            //   $return = $this->upload('img',"coupon");

            //   if(is_array($return)) $data['img'] = $return['savename'];

            //   else {$this->error($return,'',2);die;}

            // }

            // if(!$data['img'] && !$id){$this->error('保存失败','',2);die;}

            if($id){

                // if($data['img']){

                //   $cat = db('coupon')->where("id = {$id}")->find();

                //   unlink("./Uploads/coupon/".$cat['img']);

                // }

                $data['id'] = $id;
                // dump($data);exit;
                db('coupon')->update($data);

                $url = session(__FUNCTION__.'_jump')?:'';

                session(__FUNCTION__.'_jump',null);

                $this->success('保存成功',$url,2);

                die;

            }else{

                $data['addtime'] = time();
                // dump($data);exit;
                db('coupon')->add($data);

                $this->success('保存成功','coupon_list',1);die;

            }

        }else if($op == 'edit'){

            $data = db('coupon')->where("id = {$id}")->find();

            $two_lists=db('goods_cat')->where('pid='.$data['cate_id'])->field('id,title')->select();
            // dump($two_lists);exit;

            if(!$data){$this->error('该优惠券不存在','',2);die;}


            $this->assign('data',$data);
            $this->assign('two_lists',$two_lists);

        }else if($op == 'dele'){

            db('coupon')->where("id = {$id}")->delete();

            $this->success('删除成功','coupon_list',2);die;

        }
        $this->assign('pid_lists',$pid_lists);
        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return $this->fetch();

    }

    public function coupon_add(){

        //auth_alert("coupon_list");

        $op = input('op');
        $admin_id= session('admin_id');
        $id = input('id')?:0;

        $pid_lists=db('goods_cat')->where('pid=1')->field('id,title')->select();
        // var_dump($pid_lists);exit;
        // var_dump($_POST);exit;
        if($op == 'save'){
            // dump($_REQUEST);exit;
            $data['cate_id'] = 1;//(int)input('cate_id');
            $data['cate_id2'] = (int)input('cate_id');
            // $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            // $data['home_show'] = input('home_show')&&$data['status']?1:0;
            $data['admin_id'] = session('admin_id');
            $data['title'] = input('title');
            $data['sale'] = input('sale');
            $data['minus'] = input('minus');
            $data['total'] = input('total');
            $data['term'] = input('term');
            $data['type'] = input('type');
            // $data['start_time'] = strtotime($_REQUEST['start_time']);
            // $data['end_time'] =strtotime($_REQUEST['end_time']);

            if(!$data['title']){$this->error("保存失败","",2);die;}


            if($id){

                $data['id'] = $id;
                // dump($data);exit;
                db('coupon')->save($data);

                $url = session(__FUNCTION__.'_jump')?:'';

                session(__FUNCTION__.'_jump',null);

                $this->success('保存成功',$url,2);

                die;

            }else{

                $data['addtime'] = time();
                // dump($data);exit;
                db('coupon')->insert($data);

                $this->success('保存成功','coupon_list',1);die;

            }

        }
        $this->assign('pid_lists',$pid_lists);
        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return $this->fetch();

    }

    public function getAllCat(){
        $admin_id= session('admin_id');

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

    public function get_two(){
        $admin_id= session('admin_id');
        // echo $_POST['id'];exit;
        if(isset($_POST['id'])){
            $pid=$_POST['id'];
            $data=db('goods_cat')->where('pid='.$pid)->field('id,title')->select();
            // var_dump($data);exit;
        }
        // $this->ajaxReturn($data);
        echo json($data);
    }

}