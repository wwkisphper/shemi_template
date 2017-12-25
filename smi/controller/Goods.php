<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/7 0007
 * Time: 上午 11:52
 */

namespace app\smi\controller;


class Goods extends Base
{
    /* ┏┳┳┳┳┳┳┳┳┳┳商品分类管理┳┳┳┳┳┳┳┳┳┳┓ */


    protected function _initialize()
    {
        parent::_initialize();

    }


    //一级分类
    public function goods_cat(){

//        auth_alert("goods_cat_r");

//        $this->assign('auth',auth_check("goods_cat_w"));
        $admin_id=session('admin_id');

        $map = "pid=0 and admin_id=".$admin_id;

        $count = db('goods_cat')->where($map)->count();

        $Page = new \Think\Page($count,20);

        $show = $Page->show();

        $cat = db('goods_cat')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order("status desc,home_show desc,seat_no desc,create_time desc")->select();

        foreach($cat as $k=>$val){

            $cat[$k]['img'] = __UPLOAD__."/goods_cat/".$val['img'];

        }

        $this->assign('cat',$cat);

        $this->assign('page',$show);

        $this->display();

    }
    //商品二级分类
    public function goods_cat_child($page = 1){

//        auth_alert("goods_cat_r");

//        $this->assign('auth',auth_check("goods_cat_w"));
        $admin_id=session('admin_id');
//        $pid = I('pid');

        $where='pid=1'." and admin_id=".$admin_id;

        $cat = db('goods_cat')->where($where)->order("status desc,home_show desc,seat_no desc,create_time desc")->paginate(15, false, ['page' => $page]);
//        dump($cat);



        return $this -> fetch('goods_cat_child',['cat'=>$cat]);

    }

    public function goods_cat_op(){
//            dump($_SESSION);
//        auth_alert("goods_cat_w");
        $admin_id=session('admin_id');
//        dump($admin_id);
        $op = input('op');
//        dump($op);die;
        $id = input('id')?:0;

        $pid_lists=db('goods_cat')->where('pid=1'." and admin_id=".$admin_id)->field('id,title')->select();
        // var_dump($pid_lists);exit;
        // var_dump($_POST);exit;
        if($op == 'save'){

            $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            $data['home_show'] = input('home_show')&&$data['status']?1:0;

            $data['title'] = input('title');
            $data['title2'] = input('title2');
            $data['pid'] = 1;
            $data['url'] = input('url');
            $data['admin_id'] = $admin_id;
            $data['icon'] = input('icon');
            $data['link'] = input('link');

            if(!$data['title']){$this->error("请填写标题","",2);die;}
//            dump($_FILES['img']['name']);
            if(!empty($_FILES['img']['name'])&&$_FILES['img']['name'][0]!=""){

                $return = upload_goods('img',"goods_cat");

                $data['img'] = $return;


            }
            if(!empty($_FILES['icon']['name'])){

                $return = upload_goods('icon',"goods_cat");

                $data['icon'] = $return;

            }

            if(empty($data['img']) && empty($id)){$this->error('图片保存失败','',2);die;}

            if($id){
//                dump($id);die;
//                dump($data);die;
                if($_FILES['img']['name']){

                    $cat = db('goods_cat')->where("id = {$id}")->find();
                    if(!empty($cat['img'])){
                        unlink("./uploads/".$cat['img']);
                    }
                }

                $data['id'] = $id;

                db('goods_cat')->update($data);

                $url = session(__FUNCTION__.'_jump')?:'';

                session(__FUNCTION__.'_jump',null);

                $this->success('保存成功',$url,2);

                die;

            }else{

                $data['create_time'] = time();
                // dump($data);exit;
                db('goods_cat')->insert( $data);

                $this->success('保存成功','goods_cat_op',1);die;

            }

        }else if($op == 'edit'){

            $cat = db('goods_cat')->where("id = {$id}")->find();

            if(!$cat){$this->error('该分类不存在','',2);die;}

            $this->assign('cat',$cat);

        }else if($op == 'dele'){

            $check = CheckPermissions($admin_id,'goods_cat','admin_id',$id);  //检查商户是否有权限操作该记录
            if(!$check){
                die;
            }
            db('goods_cat')->where("id = {$id}")->delete();

            $this->success('删除成功','goods_cat_child',2);die;

        }
        $this->assign('pid_lists',$pid_lists);
        $this->assign('id',$id);
        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return  $this->fetch('goods_cat_op');

    }

    public function goods_cat_add(){
//            dump($_SESSION);
//        auth_alert("goods_cat_w");
        $admin_id=session('admin_id');
//        dump($admin_id);
        $op = input('op');

        $id = input('id')?:0;

        $pid_lists=db('goods_cat')->where('pid=1'." and admin_id=".$admin_id)->field('id,title')->select();
        // var_dump($pid_lists);exit;
        // var_dump($_POST);exit;
        if($op == 'save'){

            $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            $data['home_show'] = input('home_show')&&$data['status']?1:0;

            $data['title'] = input('title');
            $data['title2'] = input('title2');
            $data['pid'] = 1;
            $data['url'] = input('url');
            $data['admin_id'] = $admin_id;
//            $data['icon'] = input('icon');
            $data['link'] = input('link');

            if(!$data['title']){$this->error("请填写标题","",2);die;}
//            dump($_FILES['img']['name']);
            if(!empty($_FILES['img']['name'])){

                $return = upload_goods('img',"goods_cat");
                $data['img'] = "goods_cat/".$return;

            }
            if(!empty($_FILES['icon']['name'])){

                $return = upload_goods('icon',"goods_cat");
                $data['icon'] = $return;

            }
            if(empty($data['img']) && empty($id)){$this->error('图片缺少,保存失败','',2);die;}
            if($id){

                if($_FILES['img']['name']){

                    $cat = db('goods_cat')->where("id = {$id}")->find();

                    unlink("./uploads/".$cat['img']);

                }

                $data['id'] = $id;

                db('goods_cat')->update($data);

                $url = session(__FUNCTION__.'_jump')?:'';

                session(__FUNCTION__.'_jump',null);

                $this->success('保存成功',$url,2);

                die;

            }else{

                $data['create_time'] = time();
                db('goods_cat')->insert($data);

                $this->success('保存成功','goods_cat_child',1);die;

            }

        }
        $this->assign('pid_lists',$pid_lists);
        $this->assign('id',$id);
//        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return  $this->fetch('goods_cat_add');

    }

    public function getAllCat(){

        $list = db('goods_cat')->select();

        $cat = array();

        foreach($list as $val){

            $cat[$val["id"]] = $val["title"];

        }

        return $cat;

    }

    /* ┗┻┻┻┻┻┻┻┻┻┻商品分类管理┻┻┻┻┻┻┻┻┻┻┛ */





    /* ┏┳┳┳┳┳┳┳┳┳┳商品管理┳┳┳┳┳┳┳┳┳┳┓ */

    public function goods($page=1,$title='',$type=''){

        //总商品数量

//        $count=db('goods')->where('status !=5')->count('id');
//         var_dump($count);exit;
//        $this->assign('count',$count);
        $admin_id=session('admin_id');
//        auth_alert('goods_list_r');

//        $this->assign('auth',auth_check("goods_list_w"));

        $op = input('op');

        $map = " status != 5 and admin_id= ".$admin_id;

        $id = input('id')?:0;

        if($id){

            $map = " id = {$id} ";

        }else{

            $type = input('type');

            $title = input('title');

        }

        if($title){

            $map .= " and (title like '%{$title}%' or model like '%{$title}%') ";

        }

        if($type == 1){

            $map .= " and status = 1 and total !=0 ";

        }else if($type == 2){

            $map .= " and status = 0 or total = 0";

        }



        $list = db("goods")->where($map)->order("status desc")->paginate(15, false, ['page' => $page])->each(function($item, $key){
            $cat = $this->getAllCat();

            $item["img"] = 'http://'.$_SERVER['HTTP_HOST']."/reservation/public/uploads/".$item['img'];

            $item["cat_name"] = $cat[$item["cat_id"]];

            $item["status_name"] = $item['status']==1?"上架":"下架";
            return $item;
        });
        $count=count($list);
        // var_dump($list);exit;

        if($op == "export"){

            $data = array(array('商品ID','所属分类','商品名称','型号','金币售价','原价','进货价','销量','库存数量','状态','添加时间'));

            unset($val);

            foreach($list as $val){

                $data[] = array($val["id"],$val["cat_name"],$val["title"],$val["model"],$val["price"],$val["orig_price"],$val["buy_price"],$val["sales"],$val["total"],$val["status_name"],date("Y-m-d H:i",$val["create_time"]));

            }

            create_xls($data,'商品列表_'.date("Y-m-d H:i"));

            die;

        }
//        dump($list);
        $this->assign("list",$list);
        $this->assign("title",$title);
        $this->assign("type",$type);
        $this->assign("count",$count);

        return $this -> fetch('goods');

    }

    public function text(){

        //总商品数量

        $count=db('goods')->where('status !=5')->count('id');
        // var_dump($count);exit;
        $this->assign('count',$count);

        auth_alert('goods_list_r');

        $this->assign('auth',auth_check("goods_list_w"));

        $op = input('op');

        $map = " status != 5 ";

        $id = input('id')?:0;

        if($id){

            $map = " id = {$id} ";

        }else{

            $type = input('type');

            $title = input('title');

        }

        if($title){

            $map .= " and (title like '%{$title}%' or model like '%{$title}%') ";

        }

        if($type == 1){

            $map .= " and status = 1 and total !=0 ";

        }else if($type == 2){

            $map .= " and status = 0 or total = 0";

        }

        if($op != 'export'){

            $count = db("goods")->where($map)->count();

            $Page = new \Think\Page($count,20);

            $this->assign("page",$Page->show());

            $limit = $Page->firstRow.','.$Page->listRows;

        }

        $list = db("goods")->where($map)->order("status desc")->limit($limit)->select();
        // var_dump($list);exit;
        $cat = $this->getAllCat();

        foreach($list as &$val){


            $val["img"] = __UPLOAD__."/".$val['img'];

            $val["cat_name"] = $cat[$val["cat_id"]];

            $val["status_name"] = $val['status']==1?"上架":"下架";

        }

        if($op == "export"){

            $data = array(array('商品ID','所属分类','商品名称','型号','金币售价','原价','进货价','销量','库存数量','状态','添加时间'));

            unset($val);

            foreach($list as $val){

                $data[] = array($val["id"],$val["cat_name"],$val["title"],$val["model"],$val["price"],$val["orig_price"],$val["buy_price"],$val["sales"],$val["total"],$val["status_name"],date("Y-m-d H:i",$val["create_time"]));

            }

            create_xls($data,'商品列表_'.date("Y-m-d H:i"));

            die;

        }

        $this->assign("list",$list);

        $this->display();

    }

    public function goods_op(){

//        auth_alert('goods_list_w');

        $admin_id=session('admin_id');
//        dump($admin_id);
        $op = input('op');

        $id = input('id')?:0;
        // 商品一级分类
        $one=db('goods_cat')->where('pid=1 and admin_id='.$admin_id)->field('id,title')->select();
        $this->assign("one",$one);

        $cate2_lists=db('goods_cat')->where('pid=1 and admin_id='.$admin_id)->field('id,title')->select();
        $this->assign("cate2_lists",$cate2_lists);
        //当前管理员商家id
        // $group_id=db('users')->where('id='.$_SESSION['toy_admin_id'])->getField('group_id');
//      dump($cate2_lists);die;
        if($id){

            $goods = db('goods')->where("id = {$id}")->find();

            if(!$goods){$this->error('该商品不存在','',2);die;}
            // var_dump($goods);exit;
            if($goods['cat_id1']){
                $cate1_name=db('goods_cat')->where('id='.$goods['cat_id1'])->find();
                $goods['cate1_name']=$cate1_name['title'];
//        $cate2_lists=db('goods_cat')->where('pid=1')->field('id,title')->select();
//        $this->assign("cate2_lists",$cate2_lists);
            }

            if($goods['cat_id']){
                $cate2_name=db('goods_cat')->where('id='.$goods['cat_id'])->find();
                $goods['cate2_name']=$cate2_name['title'];
            }
            // print_r($goods['attr_lists']);exit;

        }


        if($op == 'save'){

            $data['title'] = input('title');
            $data['price'] = (float)input('price');
            // $data['deposit'] = (float)input('deposit');
            $data['cat_id'] = (int)input('cat_id');
            $data['cat_id1'] = 1;//(int)input('cat_id1');
            foreach($data as $val){if(empty($val)){ dump($val);die;
                $this->error("请填写商品关键参数!","",2);die;}}
            $data['seat_no'] = (int)input('seat_no');
            $data['status'] = input('status')?1:0;
            // $data['allow_comment'] = I('allow_comment')?1:0;
            $data['home_show'] = input('home_show');
            // $data['brand'] = I('brand');
            // $data['buy_price'] = (float)I('buy_price');
            $data['total'] = (int)input('total');
            $data['model'] = input('model');

            $data['admin_id'] = $admin_id;
            $data['content'] = input('content','htmlspecialchars_decode');
            //迭代代码
            $data['home_show'] = input('home_show')?:0; //商品单位名称
            $data['is_hot'] = input('is_hot'); //商品属性
            $data['is_like'] = input('is_like'); //商品属性
            $data['false_sell'] = input('false_sell'); //虚拟销量
            $data['parameter']=input('parameter',"htmlspecialchars_decode"); //产品参数
            // $data['cat_id1'] = input('cat_id1'); //一级分类id
            // $data['orig_price'] = (float)I('orig_price'); //进货价
            $data['img'] = input('img');
//       var_dump($data);exit;
//       dump($_FILES);exit;
            if($_FILES['img']['name']){
//          echo 111;exit;
                $return = upload_goods('img',"goods");
                $data['img'] ="goods/".$return;
            }
            if($_FILES['home_img']['name']){
                $return = upload_goods('home_img',"goods");
                $data['home_img'] = "goods/".$return;
            }
//            dump($data['img'] );die;
            if(empty($data['img'])){$this->error('图片保存失败','',2);die;}


            if(!empty($id)){

                $imgs_old = unserialize($goods["imgs"]);
//                dump($imgs_old);
                $imgs_dele = explode(",",input('imgs_dele'));

                foreach($imgs_dele as $val){

                    if(!empty($val) && !empty($imgs_old[$val])){

                        unlink("./uploads/".$imgs_old[$val]);

                        $imgs_old[$val] = "";

                    }

                }

            }
            $imgs = array();

            for($i=0;$i<9;$i++){

                if($_FILES["imgs_{$i}"]["name"]){

                    $return = upload_goods("imgs_{$i}","goods_swiper");
                    $imgs[$i] = "goods_swiper/".$return;

                }
//                dump($imgs[$i]);return;
                if(empty($imgs[$i])){
                    if(empty($imgs_old[$i])){
                        $imgs[$i] = "";
                    }else{
                        $imgs[$i] = $imgs_old[$i];
                    }

                }

            }
            $data['imgs'] = serialize($imgs);




            if($id){

                if($_FILES['img']['name']){

                    unlink("./uploads/".$goods['img']);

                }

                $data['id'] = $id;
                // var_dump($data);exit;
                $news_cat_id=input('cat_id');
                $goods_cateid=db('goods')->where('id='.$id)->field('cat_id');
                // echo $goods_cateid;
                // echo $news_cat_id;exit;
//                if($goods_cateid!=$news_cat_id){
//                    db('goods_attr')->where('goods_id='.$id)->delete();
//                    db('goods_stock')->where('goods_id='.$id)->delete();
//                }



                db('goods')->where('id='.$id)->update($data);

                $attr_= input();
                $attr=$attr_['attr'];
//                dump($attr);
                if(empty($attr)){
                    $this->error('商品库存生成失败,请添加分类属性','',2);die;
                }
                foreach($attr as $i=>$val){
                    $cateattr_id=$val['cateattr_id'];

                    foreach($val['data'] as $k=>$vel){
                        $add['goods_id']=$id;
                        $add['cateattr_id']=$cateattr_id;
                        $add['attr_title']=$vel['title'];
                        $add['attr_price']=$vel['price'];
                        $add['admin_id']=$admin_id;
                        if(!empty($vel['id'])){
                            if(!empty($vel['title'])&&!empty($vel['price'])){
                                $save['attr_title']=$vel['title'];
                                $save['attr_price']=$vel['price'];
//                                dump('sdsd');
//                                dump($save);die;
                                $vers=db('goods_attr')->where('id='.$vel['id'])->update($save);
                            }else{
//                                dump('idid');
//                                dump($vel['id']);die;
                                db('goods_attr')->where('id='.$vel['id'])->delete();
                            }
                        }else{

                            $vers=db('goods_attr')->insert($add);
                        }


                    }
                }

                // $url = session(__FUNCTION__.'_jump')?:'';

                // session(__FUNCTION__.'_jump',null);
                $save= input('save');
                if($save=='111'){
                    $this->success('保存成功',url('goods'),2);
                }
                if($save=='222'){
                    $this->success('保存成功,正在生成组合库存','goods_stock'.'?id='.$id,2);
                }



            }else{

                $data['create_time'] = time();

                $goods_id = db('goods')->insert($data);

                if($goods_id){
                    $attr_= input();
                    $attr= $attr_['attr'];
                    foreach($attr as $i=>$val){
                        $cateattr_id=$val['cateattr_id'];

                        foreach($val['data'] as $k=>$vel){
                            $add['goods_id']=$goods_id;
                            $add['cateattr_id']=$cateattr_id;
                            $add['attr_title']=$vel['title'];
                            $add['attr_price']=$vel['price'];
//                            dump($admin_id);die;
                            $add['admin_id']=$admin_id;
                            $vers=db('goods_attr')->insert($add);
                            // echo db()->getlastsql()."<br>";
                        }
                    }
                }

                $this->success('保存成功,正在生成组合库存','goods_stock'.'?id='.$goods_id,2);

            }



            die;




        }else if($op == 'edit'){
            // var_dump($goods);exit;
//            $goods["img"] ="/uploads/".$goods['img'];

            $goods["imgs"] = unserialize($goods["imgs"]);

            foreach($goods["imgs"] as $k=>$val){

                if(!empty($val)){

                    $goods["imgs_{$k}"] = $val;

                }else{

                    $goods["imgs_{$k}"] = "";

                }

            }
            // var_dump($goods);exit;
            if($id){
                //商品属性分类
                $goods['attr_lists']=db('goods_cateattr')->where('cate_id='.$goods['cat_id']." and admin_id=".$admin_id)->field('id,title')->order('sort desc')->select();
                // print_r($goods['attr_lists']);exit;
                $iii=[];
                if($goods['attr_lists']){
                    foreach($goods['attr_lists'] as $i=>&$val){
                        $data=db('goods_attr')->where('goods_id='.$goods['id'].' and cateattr_id='.$val['id'])->order('id asc')->field('id,attr_title,attr_price')->select();
                        if(!empty($data)){
                            $val['data']=$data;
                        }else{
                            $val['data']="";
                        }
                        $iii[$i]['id']=$val['id'];
                        $iii[$i]['num']=count($val['data'])-1;
                        if($iii[$i]['num']<0){
                            $iii[$i]['num']=0;
                        }
                    }

                }
            }
            // print_r($goods['attr_lists']);exit;
            // var_dump($iii);exit;
//            dump($goods['imgs']);die;
            $this->assign("iii",$iii);
            $this->assign("goods",$goods);
//            dump($goods);die;
        }else if($op == 'dele'){
            $check = CheckPermissions($admin_id,'goods','admin_id',$id);  //检查商户是否有权限操作该记录
            if(!$check){
                die;
            }
            db('goods')->where("id = {$id}")->setField("status",5);
            $this->success('删除成功','goods',2);die;

        }


        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return $this->fetch('goods_op');

    }

    public function goods_op_add(){

//        auth_alert('goods_list_w');

        $admin_id=session('admin_id');
//        dump($admin_id);die;
        $op = input('op');

        $id = input('id')?:0;
        // 商品一级分类
        $one=db('goods_cat')->where('pid=1 and admin_id='.$admin_id)->field('id,title')->select();
        $this->assign("one",$one);

        $cate2_lists=db('goods_cat')->where('pid=1 and admin_id='.$admin_id)->field('id,title')->select();
        $this->assign("cate2_lists",$cate2_lists);
        //当前管理员商家id
        // $group_id=db('users')->where('id='.$_SESSION['toy_admin_id'])->getField('group_id');
//      dump($cate2_lists);die;
        if(!empty($id)){

            $goods = db('goods')->where("id = {$id}")->find();

            if(!$goods){$this->error('该商品不存在','',2);die;}
            // var_dump($goods);exit;
            if($goods['cat_id1']){
                $goods['cate1_name']=db('goods_cat')->where('id='.$goods['cat_id1'])->field('title');
//        $cate2_lists=db('goods_cat')->where('pid=1')->field('id,title')->select();
//        $this->assign("cate2_lists",$cate2_lists);
            }

            if($goods['cat_id']){
                $goods['cate2_name']=db('goods_cat')->where('id='.$goods['cat_id'])->field('title');
            }
            // print_r($goods['attr_lists']);exit;

        }


        if($op == 'save'){

            // var_dump($_REQUEST);exit;

            $data['title'] = input('title');

            $data['price'] = (float)input('price');

            // $data['deposit'] = (float)input('deposit');

            $data['cat_id'] = (int)input('cat_id');

            $data['cat_id1'] = 1;//(int)input('cat_id1');


            foreach($data as $val){if(!$val){$this->error("请填写商品关键参数!","",2);die;}}

            $data['seat_no'] = (int)input('seat_no');

            $data['status'] = input('status')?1:0;

            // $data['allow_comment'] = I('allow_comment')?1:0;


            $data['home_show'] = input('home_show');

            // $data['brand'] = I('brand');

            // $data['buy_price'] = (float)I('buy_price');

            $data['total'] = (int)input('total');

            $data['model'] = input('model');


            $data['admin_id'] = $admin_id;



            $data['content'] = input('content','htmlspecialchars_decode');

            //迭代代码
            $data['home_show'] = input('home_show')?:0; //商品单位名称
            $data['is_hot'] = input('is_hot'); //商品属性
            $data['is_like'] = input('is_like'); //商品属性

            $data['false_sell'] = input('false_sell'); //虚拟销量
            $data['parameter']=input('parameter',"htmlspecialchars_decode"); //产品参数
            // $data['cat_id1'] = input('cat_id1'); //一级分类id
            // $data['orig_price'] = (float)I('orig_price'); //进货价



//       var_dump($data);exit;
//       dump($_FILES);exit;
            if($_FILES['img']['name']){
//          echo 111;exit;
                $return = upload_goods('img',"goods");
                $data['img'] ="goods/".$return;
            }

            if($_FILES['home_img']['name']){

                $return = upload_goods('home_img',"goods");

                $data['home_img'] ="goods/". $return;


            }

            if(!empty($data['img']) && !empty($id)){$this->error('图片保存失败','',2);die;}


            if(!empty($id)){

                $imgs_old = unserialize($goods["imgs"]);

                $imgs_dele = explode(",",input('imgs_dele'));

                foreach($imgs_dele as $val){

                    if(!empty($val) && !empty($imgs_old[$val])){

                        unlink("./uploads/".$imgs_old[$val]);

                        $imgs_old[$val] = "";

                    }

                }

            }

            $imgs = array();

            for($i=0;$i<9;$i++){

                if($_FILES["imgs_{$i}"]["name"]){

                    $return = upload_goods("imgs_{$i}","goods_swiper");
                    $imgs[$i] = "goods_swiper/".$return;

                }
                if(empty($imgs[$i])){

                    if(empty($imgs_old[$i])){
                        $imgs[$i] = "";
                    }else{
                        $imgs[$i] = $imgs_old[$i];
                    }

                }

            }
            $data['imgs'] = serialize($imgs);




            if($id){

                if($_FILES['img']['name']){

                    unlink("./uploads/".$goods['img']);

                }

                $data['id'] = $id;
                // var_dump($data);exit;
                $news_cat_id=input('cat_id');
                $goods_cateid=db('goods')->where('id='.$id)->field('cat_id');
                // echo $goods_cateid;
                // echo $news_cat_id;exit;
                if($goods_cateid!=$news_cat_id){
                    db('goods_attr')->where('goods_id='.$id)->delete();
                    db('goods_stock')->where('goods_id='.$id)->delete();
                }



                db('goods')->where('id='.$id)->update($data);

                $attr_= input();
                $attr=$attr_['attr'];



                foreach($attr as $i=>$val){
                    $cateattr_id=$val['cateattr_id'];

                    foreach($val['data'] as $k=>$vel){
                        $add['goods_id']=$id;
                        $add['cateattr_id']=$cateattr_id;
                        $add['attr_title']=$vel['title'];
                        $add['attr_price']=$vel['price'];
                        if(!empty($vel['id'])&&$vel['id']!=""){
                            if($vel['title']!=NULL&&$vel['price']!=NULL){
                                $save['attr_title']=$vel['title'];
                                $save['attr_price']=$vel['price'];
                                $vers=db('goods_attr')->where('id='.$vel['id'])->update($save);
                            }else{
                                db('goods_attr')->where('id='.$vel['id'])->delete();
                            }
                        }else{

                            $vers=db('goods_attr')->insert($add);
                        }


                    }
                }

                // $url = session(__FUNCTION__.'_jump')?:'';

                // session(__FUNCTION__.'_jump',null);
                $save= input('save');
                if($save=='111'){
                    $this->success('保存成功',url('goods'),2);
                }
                if($save=='222'){
                    $this->success('保存成功,正在生成组合库存','goods_stock?id='.$id,2);
                }
//                dump( $data['content']);exit;


            }else{

                $data['create_time'] = time();

                db('goods')->insert($data);
                $goods_id = db('goods')->getLastInsID();

                if(!empty($goods_id)){
                    $attr_= input();
                    if(empty($attr_['attr'])){
                        $this->error('商品库存生成失败,请添加分类属性','',2);die;
                    }
                    $attr=$attr_['attr'];
                    foreach($attr as $i=>$val){
                        $cateattr_id=$val['cateattr_id'];
                        foreach($val['data'] as $k=>$vel){
                            $add['goods_id']=$goods_id;
                            $add['cateattr_id']=$cateattr_id;
                            $add['admin_id'] = $admin_id;
                            $add['attr_title']=$vel['title'];
                            $add['attr_price']=$vel['price'];
                            $vers=db('goods_attr')->insert($add);
                            // echo db()->getlastsql()."<br>";
                        }
                    }
                }

                $this->success('保存成功,正在生成组合库存','goods_stock?id='.$goods_id,2);

            }



            die;



        }



        /* 个人供方、商家供方列表 */

        // $personal = db("supply_personal")->order("create_time desc")->select();

        // $business = db("supply_business")->order("create_time desc")->select();

        // $this->assign('personal',$personal);

        // $this->assign('business',$business);


        session(__FUNCTION__.'_jump',$_SERVER['HTTP_REFERER']);

        return $this->fetch('goods_op_add');

    }

    /* ┗┻┻┻┻┻┻┻┻┻┻商品管理┻┻┻┻┻┻┻┻┻┻┛ */





    public function upload($name,$folder='',$datefolder=false){

        $upload = new Upload();

        $upload->maxSize  = 52428800 ;

        $upload->exts     = array('jpg', 'gif', 'png', 'jpeg');

        if($datefolder && $folder){

            $upload->rootPath = "/uploads/";

            $upload->savePath = "{$folder}/";

        }else{

            $upload->rootPath = '/uploads/';

            //当subName未定义而autoSub为true时，自动以日期创建目录。

            $upload->subName  = $folder?$folder:$name;

        }

        $upload->autoSub  = true;

        $info = $upload->uploadOne($_FILES[$name]);

        if(!$info) $info = $upload->getError();

        return $info;

    }

    public function get_two(){
        // echo $_POST['id'];exit;
        if(isset($_POST['id'])){
            $pid=$_POST['id'];
            $data=db('goods_cat')->where('pid='.$pid)->field('id,title')->select();
            // var_dump($data);exit;
        }
        // $this->ajaxReturn($data);
        echo json($data);
    }

    public function get_attr(){
        // echo $_POST['id'];exit;
        if(isset($_POST['id'])){
            $pid=$_POST['id'];
            $data=db('goods_cateattr')->where('cate_id='.$pid)->order('sort desc')->field('id,title')->select();
            // var_dump($data);exit;
        }
        // $this->ajaxReturn($data);
        echo json($data);
    }


    public function attr_op(){

        $op = input('op');

        if($op == 'save'){

            $data['title'] = input('title');

            $data['sort'] = input('sort');

            $data['status'] = input('status');


            foreach($data as $val){ if(!$val){ $this->error('保存失败,参数缺少请填写完整','',1);die; } }
            $data['addtime']=time();

            db('goods_attr')->add($data);

            $this->success('保存成功','',1);die;

        }else if($op == 'dele'){

            $id = input('id');

            db('goods_attr')->where("id = {$id}")->delete('status',0);

            $this->success('删除成功','',1);die;

        }else if($op == 'dowm'){

            $id = input('id');

            db('goods_attr')->where("id = {$id}")->setField('status',0);

            $this->success('隐藏成功','',1);die;

        }else if($op == 'up'){

            $id = input('id');

            db('goods_attr')->where("id = {$id}")->setField('status',1);

            $this->success('显示成功','',1);die;

        }

    }


    //分类属性类型列表
    public function cateattr_lists($pid,$page=1){

//        auth_alert("goods_cat_r");

//        $this->assign('auth',auth_check("goods_cat_w"));

        $admin_id = session('admin_id');

        $where='cate_id='.$pid." and admin_id=".$admin_id;

        $attr = db('goods_cateattr')->where($where)->order("sort desc")->paginate(15, false, ['page' => $page]);

        $cate_one_id=db('goods_cat')->where('id='.$_GET['pid']." and admin_id=".$admin_id)->field('pid')->find() ;//获取一级分类id

        $cate_one_list=db('goods_cat')->where('pid=1')->field('id,title')->select();//获取一级分类列表
//        dump($attr);die;
        $cate_two_list=db('goods_cat')->where('pid=1'." and admin_id=".$admin_id)->field('id,title')->select();//获取二级分类列表

        $this -> assign('one',$cate_one_list);
        $this -> assign('two',$cate_two_list);
        $this -> assign('cate_one_id',$cate_one_id);
        $this -> assign('attr',$attr);
//        $this->display();

        return $this -> fetch('cateattr_lists');


    }


    public function cateattr_delete($id){
        auth_alert("goods_cat_r");

        $this->assign('auth',auth_check("goods_cat_w"));

        $id = input('id');

        db('goods_cateattr')->where("id = {$id}")->delete();

        $this->success('删除成功','',2);die;

    }

    public function cateattr_add(){
//        auth_alert("goods_cat_r");

//        $this->assign('auth',auth_check("goods_cat_w"));
        $admin_id = session('admin_id');

        $cate_id=input('cate_id');
        $add['cate_id'] = $cate_id;
        $add['admin_id'] = $admin_id;

        $add['title'] = input('title');

        $add['sort'] = input('sort');
        $ver=db('goods_cateattr')->where('cate_id='.$cate_id." and admin_id=".$admin_id)->count('id');
        if($ver>=3){
            $this->error('新增失败!分类属性上限为3个!','',2);die;
        }else{
            db('goods_cateattr')->insert($add);

            $this->success('新增成功','goods_cat_child',2);die;
        }


    }

    public function cateattr_edit(){
        auth_alert("goods_cat_r");

        $this->assign('auth',auth_check("goods_cat_w"));

        // var_dump($_REQUEST);exit;
        $id = input('id');

        $save['cate_id'] = input('cate_id');

        $save['title'] = input('title');

        $save['sort'] = input('sort');

        db('goods_cateattr')->where('id='.$id)->save($save);

        $this->success('修改成功','',2);die;

    }

    public function goods_stock($id){
        $goods_id=$id;
        $admin_id = session('admin_id');
        $data=db('goods')->where('id='.$goods_id." and admin_id=".$admin_id)->field('id,title,img,total,model')->find();
        // $data['img']='/case/uploads/'.$data['img'];
        // var_dump($data);exit;

        // var_dump($old_stock);exit;
        // echo $old_stock[7]['attr_name'][2];exit;
        $cate_id=db('goods')->where('id='.$goods_id." and admin_id=".$admin_id)->find();
//        dump($cate_id);
        $cateattr=db('goods_cateattr')->where('cate_id='.$cate_id['cat_id']." and admin_id=".$admin_id)->select();
//         var_dump($cateattr);exit;
        $lay=array();

        $cateattr_i=db('goods_attr')->where('goods_id='.$goods_id." and admin_id=".$admin_id)->group('cateattr_id')->select();
//         var_dump($admin_id);exit;

        foreach($cateattr_i as $i=>$val){

            $lay[$i]['cateattr_id']=$val['cateattr_id'];
            $lay[$i]['data']=db('goods_attr')->where('goods_id='.$goods_id.' and cateattr_id='.$val['cateattr_id'])->select();

        }

        $toy=[];
        foreach($lay as $i =>$val){
            foreach($val['data'] as $o=>$vol){
                $toy[$i][]=$vol['id'];
            }
        }

        $table=[];
//         foreach($toy[0] as $a=>$val){
//           foreach($toy[1] as $b=>$vbl){
//             foreach($toy[2] as $c=>$vcl){
//               $table[]=$val.",".$vbl.",".$vcl;
//             }
//           }
//         }
//        dump($toy);exit;
        foreach($toy[0] as $a=>$val){

            if(!empty($toy[1])){
                foreach($toy[1] as $b=>$vbl){

                    if(!empty($toy[2])){
                        foreach($toy[2] as $c=>$vcl){
                            $table[]=$val.",".$vbl.",".$vcl;
                        }
                    }else{
                        $table[]=$val.",".$vbl;
                    }
                }
            }else{
                $table[]=$val;
            }

        }
        // var_dump($table);exit;
        $arr = array(); //内容
        $arr2 = array(); //分类名
        foreach($table as $i =>&$val){
            $arr[$i]['attr_id']=$val;

            $catearry=array_filter(explode(",", $val));
            // var_dump($catearry);exit;
            foreach($catearry as $k=>&$vel){
                $vel=db('goods_attr')->where('id='.$vel)->find();
//                 var_dump($vel);exit;
            }
            // var_dump($catearry);exit;
            $arr2=$catearry;

        }
//        dump($arr2);

        $old_stock=db('goods_stock')->where('goods_id='.$goods_id." and admin_id=".$admin_id)->select(); //历史库存
        // var_dump($old_stock);exit;
        foreach($old_stock as $i=>$val){
            $attr=explode(",", $val['attr_id']);
            foreach($attr as $o=>&$vol){
                $vol=db('goods_attr')->where('id='.$vol)->find();
            }
            $old_stock[$i]['attr_name']=$attr;
        }
        foreach($arr as $i =>&$val){
            $val['attr_name']=explode(",",$val['attr_id']);
            foreach($val['attr_name'] as $m=>&$vml){
                $vml=db('goods_attr')->where('id='.$vml)->select();
            }
        }
//        dump($arr2);
        foreach($arr2 as $i=>&$val){
            $val=db('goods_cateattr')->where('id='.$val['cateattr_id'])->find();
        }
//         dump($arr);
//         var_dump($old_stock);exit;
        $this->assign('arr',$arr);
        $this->assign('arr2',$arr2);
        $this->assign('id',$id);
        $this->assign('cateattr',$cateattr);
        $this->assign('old_stock',$old_stock);
        $this->assign('data',$data);
        return $this->fetch('goods_stock');
    }


    public function post_stock(){
        // var_dump($_REQUEST);exit;
        $goods_id=$_REQUEST['goods_id'];
        $data=$_REQUEST['data'];
        $sum_stock=$_REQUEST['sum_stock'];
        $admin_id = session('admin_id');

        $stock=0;
        foreach($data as $i=>$val){
            $stock+=$val['stock'];
        }
        // var_dump($sum_stock);exit;
        // var_dump($stock);exit;
        if($stock>$sum_stock){
            $this->error('组合库存总和不得大于总库存!',"",2);
        }else{
            db('goods_stock')->where('goods_id='.$goods_id)->delete();
            foreach($data as $i=>$val){
                $add['goods_id']=$goods_id;
                $add['attr_id']=$val['attr'];
                $add['admin_id']=$admin_id;
                $add['stock']=$val['stock'];
                $stock+=$val['stock'];
                db('goods_stock')->insert($add);
            }
        }



        $this->success('保存库存成功!','goods',2);

    }
}