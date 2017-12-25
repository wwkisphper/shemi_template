<?php
namespace app\api\controller;

use ErrorException;
use think\Controller;
use think\Db;
use think\Loader;
use Think\Model;
header('Content-Type:application/json; charset=utf-8');
class Shopapi extends Controller {


    public function __construct(){
        parent::__construct();
    }



    function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);

        // return
        // return '/zhongxin/Uploads/';
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').'/reservation/public/uploads/';

    }

    public function userinfo_($user_id){
        $data=array();
        $info=db('user')->where('id='.$user_id)->find();
        // dump($info);exit;
        $data['share_money']=$info['share_money'];
        return $data;
    }


    public function userinfo($user_id){

        $data=$this->userinfo_($user_id);

        if($data){
            // var_dump($swiper);exit;
            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $data));
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }
    }

    public function index_goods(){
        $admin_id=$_REQUEST['admin_id'];
        //显示排序最高的前四个分类
        $goods_list=db('goods_cat')->where('status=1 and home_show=1 and admin_id='.$admin_id)->order("seat_no desc,create_time desc")->field('id,title,title2,img')->select();

        foreach($goods_list as $i=>&$val){
            //排序 销量 创建时间 前5位商品
            $val['goods'] = db('goods')->where('cat_id='.$val['id'].' and status=1 and admin_id='.$admin_id)->field('id,title,price,img')->order("seat_no desc,sales desc,create_time desc")->select();

        }
//    foreach($goods_list as $i=>&$val){
//       $val['sell_count']=$val['false_sell']+$val['sales'];
//           $val['img']='/case/Uploads/'.$val['img'];
//     }
        // var_dump($goods_list);exit;
        if($goods_list){
            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $goods_list));
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }

    }

    //基础信息 //官方网站 关于我们 地图经纬度
    public function index_info(){
        $admin_id=$_REQUEST['admin_id'];
        $data=db('info')->where('admin_id='.$admin_id)->find();

        if($data){
            // var_dump($swiper);exit;
            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $data));
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }

    }
    //首页轮播图
    public function index_ad($admin_id){

        $swiper = db('homepage_swiper')->where('admin_id='.$admin_id)->order("seat_no desc,create_time desc")->limit(0,7)->select();
        // var_dump($swiper);exit;
        if(count($swiper)>0){
            return json_encode($swiper);
        }

    }


    //特价列表
    public function index_sale($page=1,$size=20,$type=1,$admin_id){

        // return $type;exit;
        $page  = $page-1;

        $limit = ($page*$size).','.$size;
        // return $limit;exit;
        if($type==1){ //全部
            $goods_list = db("goods")->where('is_hot=1 and admin_id='.$admin_id)->order("seat_no desc,create_time desc")->limit($limit)->select();
        }
        if($type==2){ //首页专区 4个
            $goods_list = db('goods')->where('is_hot=1 admin_id='.$admin_id)->order("seat_no desc,create_time desc")->limit(0,4)->select();
        }


        foreach($goods_list as $i=>&$val){
            $val['sell_count']=$val['false_sell']+$val['sales'];
        }
        if($type==1){
            $sell_count=array();
            foreach($goods_list as $i=>&$val){
                $sell_count[$i]=$val['false_sell']+$val['sales'];
            }
        }
        // var_dump($goods_list);exit;
        if($goods_list){
            return json($goods_list);
        }

    }

    //新品列表
    public function index_new($page=1,$size=20,$type=1,$admin_id){

        $page  = $page-1;

        $limit = ($page*$size).','.$size;

        if($type==1){ //全部
            $goods_list = db("goods")->where('is_hot=2 and admin_id='.$admin_id)->order("seat_no desc,create_time desc")->limit($limit)->select();
        }
        if($type==2){ //首页专区 4个
            $goods_list = db('goods')->where('is_hot=2 and admin_id='.$admin_id)->order("seat_no desc,create_time desc")->limit(0,4)->select();
        }
        foreach($goods_list as $i=>&$val){
            $val['sell_count']=$val['false_sell']+$val['sales'];
        }
        if($type==1){
            $sell_count=array();
            foreach($goods_list as $i=>&$val){
                $sell_count[$i]=$val['false_sell']+$val['sales'];
            }
        }
        // var_dump($goods_list);exit;
        if($goods_list){
            return json($goods_list);
        }

    }


    //商品列表
    public function goods_list($page=1,$size=20,$admin_id){
        $where =" 1 ";


        $type=$_REQUEST['order']; //1销量排序 2金币正序排序 3金币降序排序 4综合
        $order="seat_no desc,create_time desc";
        if($type==1){ //1销量排序
            $order="seat_no desc,create_time desc";
        }
        if($type==2){ //金币价格正序排序
            $order="price asc,create_time desc";
        }
        if($type==3){ //金币价格降序排序
            $order="price desc,create_time desc";
        }
        if($type==4){ //综合排序
            $order="seat_no desc,create_time desc";
        }
        if(!empty($_REQUEST['keyword'])){
            $keyword=$_REQUEST['keyword'];
            $where .= " and title like '%{$keyword}%' ";
        }
        if(!empty($_REQUEST['cate_id'])){
            $cate_id=$_REQUEST['cate_id'];
            $where .= " and cat_id =".$cate_id;
        }
        $where .=" and status =1 and total >0 and admin_id= ".$admin_id ;

        $page  = $page-1;

        $limit = ($page*$size).','.$size;

        $goods_list = db("goods")->where($where)->order($order)->limit($limit)->select();
        // return db()->getlastsql();exit;
        foreach($goods_list as $i=>&$val){
            $val['sell_count']=$val['false_sell']+$val['sales'];
            $val['imgs'] = unserialize($val["imgs"]);
        }
        if($type==1){
            $sell_count=array();
            foreach($goods_list as $i=>&$val){
                $sell_count[$i]=$val['false_sell']+$val['sales'];
            }
            $sort = $desc == false ?  SORT_DESC: SORT_ASC;
            array_multisort($sell_count, $sort, $goods_list);
        }
        // var_dump($goods_list);exit;
        if($goods_list){
            return json_encode( $goods_list);
        }

    }

    //一级分类
    public function goods_cat($page=1,$size=20,$admin_id){



        $where = "pid=1 and admin_id=".$admin_id;

        $page  = $page-1;

        $limit = ($page*$size).','.$size;


        $cat_list = db('goods_cat')->where($where)->limit($limit)->order("seat_no desc,create_time desc")->select();
        foreach($cat_list as $k=>&$val){

            $val['img'] =  'http://'.$_SERVER['HTTP_HOST']."/Uploads/goods_cat/".$val['img'];

        }
        // var_dump($cat_list);exit;
        if($cat_list){
            return json($cat_list);
        }
    }

    //商品二级分类
    public function goods_cat_child($page=1,$size=20,$pid,$type,$admin_id){

        // $pid = $_REQUEST['pid'];
        $where=' 1 ';
        $where.=' and pid='.$pid." and  admin_id=".$admin_id;
        if($type==2){ // 热门
            $where.=' and home_show= 1 ';
        }

        $page  = $page-1;

        $limit = ($page*$size).','.$size;
        // return $limit;exit;
        $cat_list = db('goods_cat')->where($where)->limit($limit)->order("seat_no desc,create_time desc")->select();
        // return db()->getlastsql();exit;

        foreach($cat_list as $k=>&$val){

            $val['img'] = "http://".$_SERVER("HTTP_HOST")."/Uploads/goods_cat/".$val['img'];

        }
        // var_dump($cat_list);exit;
        if($cat_list){
            return json($cat_list);
        }

    }

    public function click_goods(){
        $goods_id=$_REQUEST['goods_id'];

        if($goods_id){
            $rows=db('goods')->where('id='.$goods_id)->setInc('click',1);
        }
        if($rows){
            return json_encode(array('code' => '200', 'info' => '点击量增加成功'));
        }
    }
    //商品详情
    public function goods_detail(){
        $id=$_REQUEST['id'];

        $user_id=$_REQUEST['user_id'];
        $admin_id=$_REQUEST['admin_id'];
        // $user_id=cookie('user_id');

        $data = db('goods')->where('id='.$id." and admin_id =".$admin_id)->find();

        $data['sell_count']=$data['sales']+$data['false_sell'];//销量计算

        $collect=db('collect')->where('user_id='.$user_id.' and goods_id='.$id." and admin_id =".$admin_id)->value('id');
        if(!empty($collect)){
            $data['is_collect']=1;//已收藏
        }else{
            $data['is_collect']=0;//未收藏
        }
        $data['imgs']=unserialize($data['imgs']);
        $data['imgs']=array_filter($data['imgs']);
        $data['img']='https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/'.$data['img'];
        $data['content'] = str_replace('/reservation','https://'.$_SERVER['HTTP_HOST'].'/reservation',$data['content']);
        $data['parameter'] = str_replace('/reservation','https://'.$_SERVER['HTTP_HOST'].'/reservation',$data['parameter']);
        $data['cate_attr']=db('goods_cateattr')->where('cate_id='.$data['cat_id'])->field('id,title')->select();
        $key="";
        foreach ($data['imgs'] as $k => $value) {
            $data['imgs'][$k] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/'.$value;
        }
        foreach($data['cate_attr'] as $i=>&$val){
            $val['attr']=db('goods_attr')->where('cateattr_id='.$val['id'].' and goods_id ='.$data['id'])->field('id,attr_title')->select();
            if($i==0){
                $key=$i+1;
            }else{
                $key+=$i;
            }
        }
        $data['attr_key']=$key;
        $data['car_count']=db('shopcar')->where('user_id='.$_REQUEST['user_id']." and admin_id =".$admin_id)->count('id'); //购物车数量
        if($data){
            return json_encode($data);
        }
    }

    //加入购物车
    public function add_goodscar(){
        $goods_id=$_REQUEST['goods_id'];
        $user_id=$_REQUEST['user_id'];
        $admin_id=$_REQUEST['admin_id'];
        // $uid=cookie('user_id');
        $num=$_REQUEST['num'];
        $attr=$_REQUEST['attr'];

        if($goods_id==null or $user_id==NULL or $num==NULL){
            return json(array('code' => '404', 'info' => '缺少参数'));
        }
        $goods=db('goods')->where('id='.$goods_id." and admin_id=".$admin_id)->find();
        if(count($goods)>0){
//            dump($user_id);
//            dump($goods_id);
//            dump($attr);

            $verid=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and attr="'.$attr.'"'." and admin_id=".$admin_id)->find();
            // return  db()->getlastsql();exit;
            if(count($verid)>0){
                $vers=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$verid['id']." and admin_id=".$admin_id)->setInc('num',$num);
                if(!empty($vers)){
                    // return json(array('code' => '200', 'info' => '添加成功'));
                    return 10000;
                }
            }else{
                $add['user_id']=$user_id;
                $add['goods_id']=$goods_id;
                $add['admin_id']=$admin_id;
                $add['num']=$num;
                $add['attr']=$attr;
                $add['addtime']=time();

                $rows=db('shopcar')->insert($add);
            }

            if(!empty($rows)){
                // return json(array('code' => '200', 'info' => '添加成功'));
                return 10000;
            }
        }else{
            // return json(array('code' => '404', 'info' => '没有此商品'));
            return 20000;
        }

    }
    //删除购物车 //支持多个
    public function delete_car(){
        $car_id=$_REQUEST['car_id'];
        $car_id=array_filter(explode(",",$car_id));
        // var_dump($car_id);exit;
        foreach($car_id as $i =>$val){
            $rows=db('shopcar')->where('id='.$val)->delete();
        }

        return json_encode(array('code' => '200', 'info' => '删除成功'));

    }
    //购物车列表
    public function shopcar_list($page=1,$size=20,$admin_id){

        $user_id=$_REQUEST['user_id'];
        // $uid=cookie('user_id');
        $where = "user_id=".$user_id." and admin_id=".$admin_id;

        $page  = $page-1;

        $limit = ($page*$size).','.$size;

        $data = db('shopcar')->where($where)->group('attr,goods_id')->limit($limit)->order("addtime desc")->select();
        // var_dump($data);exit;
        foreach($data as $k=>&$val){

            $val['goods_detail']=db('goods')->where('id='.$val['goods_id'])->field('title,img,price')->find(); //商品详情
            $val['goods_detail']['img']= "http://".$_SERVER["HTTP_HOST"]."/public/uploads/".$val['goods_detail']['img']; //商品图片前缀


            $attr=array_filter(explode(",", $val['attr'])); //","重组属性
            $text="";
            $price="";
            // var_dump($attr);exit;
            foreach($attr as $l=>$vel){
                $title=db('goods_attr')->where('goods_id='.$val['goods_id'].' and id='.$vel)->value('attr_title'); //获取属性标题
                $money=db('goods_attr')->where('goods_id='.$val['goods_id'].' and id='.$vel)->value('attr_price');
//         return db()->getlastsql();
//                 return dump($title);exit;
//          dump($vel);
                if($l==0){
                    $text=$title;
                }else{
                    $text.="+".$title;
                }
                $price=$price+$money;
            }
//            dump($val);
            $val['goods_detail']['attr_text']=$text; //属性标题
            $val['goods_detail']['attr_price']=$price;//属性价格
            $val['goods_detail']['sum_gold']=($val['goods_detail']['price']+$price);//单商品总价格
            $val['count_price']=$val['goods_detail']['sum_gold']*$val['num']; //计算商品价格 单价*数量
        }
//         var_dump($data);exit;
        if($data){
            return json_encode($data);
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }
    }

    public function like_goods(){

        $admin_id=$_REQUEST['admin_id'];

        $goods_list = db('goods')->where('status=1 and is_like=1 and admin_id='.$admin_id)->field('id,title,price,img')->order("seat_no desc,sales desc,create_time desc")->limit(0,4)->select();

        // foreach($goods_list as $i=>&$val){
        //   $val['sell_count']=$val['false_sell']+$val['sales'];
        //       $val['img']='/case/Uploads/'.$val['img'];
        // }
        // return db()->getlastsql();exit;
        // var_dump($goods_list);exit;
        if($goods_list){
            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $goods_list));
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }

    }
    //增减或删除 购物车商品
    public function edit_goodscar(){
        $car_id=$_REQUEST['car_id'];
        $goods_id=$_REQUEST['goods_id'];
        $user_id=$_REQUEST['user_id'];
        // $oper=$_REQUEST['oper']; // 1 修改数量 2 删除
        if($goods_id==null or $user_id==NULL){
            return json_encode(array('code' => '404', 'info' => '缺少参数'));exit;
        }
        $type=$_REQUEST['type'];
        if($type=="add"){// 增
            $vers=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->setInc('num',1);
            if($vers){
                $num=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->value('num');
                return json_encode(array('code' => '200', 'info' => '数量增加成功','num'=>$num));exit;
            }else{
                $num=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->value('num');
                return json_encode(array('code' => '200', 'info' => '数量增加失败','num'=>$num));exit;
            }
        }
        if($type=="sub"){ // 减
            // var_dump($_REQUEST);exit;
            $vers=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->setDec('num',1);

            if($vers){
                $num=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->value('num');
                return json_encode(array('code' => '200', 'info' => '数量减少成功','num'=>$num));exit;
            }else{
                $num=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->value('num');
                return json_encode(array('code' => '200', 'info' => '数量减少失败','num'=>$num));exit;
            }
        }
        if($type=="edit"){
            $number=$_REQUEST['num'];
            $save['num']=$number;
            // dump($_REQUEST);exit;
            $vers=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->update($save);
            // return db()->getlastsql();exit;
            if($vers){
                $num=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->value('num');
                return json_encode(array('code' => '200', 'info' => '数量修改成功','num'=>$num));exit;
            }else{
                $num=db('shopcar')->where('user_id='.$user_id.' and goods_id='.$goods_id.' and id='.$car_id)->value('num');
                return json_encode(array('code' => '404', 'info' => '数量修改失败','num'=>$num));exit;
            }
        }

    }


    //优惠券列表
    public function coupon_list(){
        $user_id=$_REQUEST['user_id'];
        $price=$_REQUEST['price'];
        $cate_info=$_REQUEST['cate_info'];
        $admin_id=$_REQUEST['admin_id'];

        $data=$this->coupon_list_($user_id,$price,$cate_info,$admin_id);

        if($data){
            return json_encode(array('code' => '200', 'info' => '获取成功','data'=>$data));
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }
    }


    //收藏操作
    public function collect(){
        $goods_id=$_REQUEST['goods_id'];
        $user_id=$_REQUEST['user_id'];
        // $uid=cookie('user_id');
        $type=$_REQUEST['type']; // 1加入 2取消
        $admin_id=$_REQUEST['admin_id'];
        // if($goods_id==null or $user_id==NULL or $type==NULL){
        //   return json(array('code' => '404', 'info' => '缺少参数'));exit;
        // }
        if($type==1){ //加入收藏
            $goods=db('goods')->where('id='.$goods_id." and admin_id=".$admin_id)->value('id');
            if($goods){
                $vers=db('collect')->where('user_id='.$user_id.' and goods_id='.$goods_id." and admin_id=".$admin_id)->value('id');
                if($vers){
                    return json(array('code' => '404', 'info' => '已收藏'));exit;
                }else{
                    $add['user_id']=$user_id;
                    $add['goods_id']=$goods_id;
                    $add['addtime']=time();
                    $add['admin_id']=$admin_id;

                    $rows=db('collect')->insert($add);
                    if($rows){
                        return json(array('code' => '200', 'info' => '收藏成功'));
                    }else{
                        return json(array('code' => '404', 'info' => '收藏出错'));
                    }
                }

            }else{
                return json(array('code' => '404', 'info' => '没有此商品'));exit;
            }
        }
        if($type==2){ //取消收藏
            $rows=db('collect')->where('user_id='.$user_id.' and goods_id='.$goods_id." and admin_id=".$admin_id)->delete();
            if($rows){
                return json(array('code' => '200', 'info' => '取消收藏成功'));
            }else{
                return json(array('code' => '404', 'info' => '取消收藏失败'));
            }
        }

    }

    //我的收藏
    public function mine_collect($page=1,$size=10){

        $user_id=$_REQUEST['user_id'];
        $admin_id=$_REQUEST['admin_id'];

        $data['user_id']=$user_id;
        $data['order_collect.admin_id']=$admin_id;
        $collect= db('collect')->join('order_goods ',' order_collect.goods_id = order_goods.id')->where($data)->field('img,goods_id,false_sell,sales,title,price')->page($page,$size)->select();
        // var_dump($collect);exit;
        return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $collect)) ;
    }

    //订单列表
    public function order_list($page=1,$size=20){

        $user_id=$_REQUEST['user_id'];

        $page=$_REQUEST['page'];

        $size=$_REQUEST['size'];

        $type=$_REQUEST['type']; // 0全部 1待付款 2待发货 3待收货

        if($user_id==NULL or $type==NULL){
            return json_encode(array('code' => '404', 'info' => '缺少参数'));exit;
        }

        $where = "uid=".$user_id;

        if($type==1){ //待付款
            $where .= " and status= 1 ";
        }
        if($type==2){ //待发货
            $where .= " and status= 2 ";
        }
        if($type==3){ //待收货
            $where .= " and status= 3 ";
        }
        if($type==8){ //售后单
            $where .= " and status= 6 or status=7 or status=8 or status=9";
        }

        $page  = $page-1;

        $limit = ($page*$size).','.$size;

        $goods_list = db('goods_order')->where($where)->limit($limit)->field('id,uid,order_sn,goods_id,gold,address,status,create_time')->order("create_time desc,id desc")->select();
        // var_dump($goods_list);exit;

        if($goods_list){

            foreach($goods_list as $i=>&$val){
                $goods_idlist=array_filter(explode(",", $val['goods_id']));
                // var_dump($goods_idlist);exit;
                $count_num=0;
                foreach($goods_idlist as $k=>&$vol){
                    $val['goods_detail'][$k-1]=db('order_shop_goods')->where('goods_id='.$vol.' and order_sn="'.$val['order_sn'].'"')->field('id,title,img,gold,order_sn,attr,buy_num')->find(); //商品详情
                    $count_num+=$val['goods_detail'][$k-1]['buy_num'];
                    // return $count_num;exit;
                    // var_dump($val['goods_detail'][$k-1]);exit;
                    // $val['goods_detail'][$k-1]['img']= __ROOT__."/Uploads/".$val['goods_detail'][$k-1]['img']; //商品图片前缀
                    $attr=array_filter(explode(",", $val['goods_detail'][$k-1]['attr'])); //","重组属性
                    // var_dump($attr);exit;
                    $text="";
                    foreach($attr as $l=>$vel){

                        $title=db('goods_attr')->where('goods_id='.$vol.' and id='.$vel)->value('attr_title'); //获取属性标题
                        // return db()->getlastsql();exit;
                        // return $title;exit;
                        $text=$text." ".$title;
                    }
                    // return $text;exit;
                    $val['goods_detail'][$k-1]['attr_text']=$text;
                    // return $val['goods_detail'][$k-1]['attr_text'];exit;
                }
                // return $count_num;exit;
                $val['count_num']=$count_num;

                if($val['status']==8){
                    $val['apply_type']=db('order_shop_apply')->where('order_sn="'.$val['order_sn'].'"')->value('apply_type');

                }
            }

        }
        // exit;
        // var_dump($goods_list);exit;
        // print_r($goods_list);exit;
        if($goods_list){
            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $goods_list)) ;
        }else{
            return json_encode(array('code' => '404', 'info' => '没有数据'));
        }
    }

    //取消订单
    public function cancel_order(){
        // $user_id=$_REQUEST['user_id'];
        // $uid=cookie('user_id');
        $order_sn=$_REQUEST['order_sn'];
        $admin_id=$_REQUEST['admin_id'];

        $where = 'order_sn="'.$order_sn.'"'." and admin_id=".$admin_id;

        $status=db('goods_order')->where($where)->value('status');
        if($status==1){
            $save['status']=5;
            $rows=db('goods_order')->where($where)->update($save);
            // return json(array('code' => '200', 'info' => '取消成功'));
            return 10000;
        }else{
            // return json(array('code' => '404', 'info' => '订单状态不符合取消条件'));
            return 20000;
        }
    }

    //申请退货退款 //POST
    public function order_apply(){
        // var_dump($_REQUEST);exit;
        $order_sn=$_REQUEST['order_sn'];
        $type=$_REQUEST['type']; // 1退款 2退货
        $apply_content=$_REQUEST['apply_content'];
        $apply_gold=$_REQUEST['apply_gold'];
        $admin_id=$_REQUEST['admin_id'];
        if(!empty($_REQUEST['apply_imgs'])){
            $apply_imgs=$_REQUEST['apply_imgs'];
        }else{
            $apply_imgs='';
        }
        $where = 'order_sn="'.$order_sn.'"';
        $status=db('goods_order')->where($where."and admin_id=".$admin_id)->value('status');
//         return $status;exit;
        if($type==1){
            if($status==2){ //已付款待发货状态下
                $add['order_sn']=$order_sn;
                $add['apply_type']=1;
                $add['addtime']=time();
                $add['apply_content']=$apply_content;
                $add['apply_gold']=$apply_gold;
                $rows=db('order_shop_apply')->insert($add);
                // return json(array('code' => '200', 'info' => '申请成功'));
                $save['status']=6;
                $ver = db('goods_order')->where('order_sn="'.$order_sn.'"'."and admin_id=".$admin_id)->update($save);

                return json_encode( $order_sn);
            }else{
                return json_encode(array('code' => '404', 'info' => '订单状态不符合申请条件'));
                // return 20000;
            }
        }
        if($type==2){
            if($status==3){ //已发货待收货状态下
                $add['order_sn']=$order_sn;
                $add['apply_type']=2;
                $add['addtime']=time();
                $add['apply_content']=$apply_content;
                $add['apply_gold']=$apply_gold;

                $add['apply_imgs']=$apply_imgs;


                $rows=db('order_shop_apply')->insert($add);
                // return json(array('code' => '200', 'info' => '申请成功'));
                $save['status']=6;
                $ver = db('goods_order')->where('order_sn="'.$order_sn.'"')->update($save);
                // return $order_sn;exit;
                return json_encode( $order_sn);
            }else{
                return json_encode(array('code' => '404', 'info' => '订单状态不符合申请条件'));
                // return 20000;
            }
        }

    }


    //提交退货信息
    public function submit_retinfo(){
        $order_sn=$_REQUEST['order_sn'];
        $express_name=$_REQUEST['express_name'];
        $express_sn=$_REQUEST['express_sn'];
        $phone=$_REQUEST['phone'];
        $admin_id=$_REQUEST['admin_id'];


        $where = 'order_sn="'.$order_sn.'"'."and admin_id=".$admin_id;
        $status=db('order')->where($where)->value('status');
        if($status==8){ //申请受理中状态

            $save['express_name']=$express_name;
            $save['express_sn']=$express_sn;
            $save['phone']=$phone;
            $rows=db('order_shop_apply')->where('order_sn="'.$order_sn.'"')->update($save);
            // return json(array('code' => '200', 'info' => '提交信息成功'));
            return json( $order_sn);
        }else{
            // return json(array('code' => '404', 'info' => '订单状态不符合提交条件'));
            exit;
        }
    }

    //订单详情
    public function order_detail(){
        $order_sn=$_REQUEST['order_sn'];
        $admin_id=$_REQUEST['admin_id'];
        $where = "order_sn='".$order_sn."'"." and admin_id=".$admin_id;

        $order = db('goods_order')->where($where)->field('id,uid,order_sn,goods_id,gold,address,status,create_time,pay_time,fachu_time,fachu_name,fachu_sn,address_name,address_phone,address_area,address_info,address_code,message,address_email')->find();
//         var_dump($order);exit;
        if($order){
            // $order['address']=db('wx_users_address')->where('id='.$order['address'])->find();//地址
            $order['goods']=array_filter(explode(",", $order['goods_id']));//定义商品id数组
            $buys_num="";//定义商品总数名称
            // var_dump($order['goods']);exit;
            foreach($order['goods'] as $i=>&$val){
                // return $val;
                $val=db('order_shop_goods')->where('goods_id='.$val.' and order_sn="'.$order['order_sn'].'"')->Field('id,goods_id,title,img,attr,buy_num,gold')->find(); //从订单商品表获取商品信息
//                dump($val);die;
                // $val['img']= __ROOT__."/Uploads/".$val['img']; //商品图片前缀
                $buys_num=$buys_num+$val['buy_num'];
                $attr=array_filter(explode(",", $val['attr']));//定义属性id数组
                $attr_text="";//定义商品属性标题名称
                foreach($attr as $m=>$vol){
                    $title=db('goods_attr')->where('id='.$vol)->value('attr_title'); //从商品属性表里获取属性标题
                    if($m==1){
                        $attr_text=$title;
                    }
                    if($m>1){
                        $attr_text.="+".$title;
                    }

                }
                $val['attr_text']=$attr_text;

            }
            // exit;
            $order['buys_num']=$buys_num; //商品总数

            if($order['status']==8){
                $order['apply_type']=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->value('apply_type');

            }

            // var_dump($order);exit;

            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $order)) ;
        }else{
            return json_encode(array('code' => '404', 'info' => '没有此订单'));
        }

    }

    //删除订单
    public function delete_order(){
        $user_id=$_REQUEST['user_id'];
        // $uid=cookie('user_id');
        $order_sn=$_REQUEST['order_sn'];
        $admin_id=$_REQUEST['admin_id'];

        $where = "uid=".$user_id.' and order_sn="'.$order_sn.'"'."and admin_id=".$admin_id;

        $status=db('goods_order')->where($where)->value('status');
        if($status==4 || $status==5){ //已完成 已关闭 状态
            $rows=db('goods_order')->where($where)->delete();
            return json(array('code' => '200', 'info' => '删除成功'));
        }else{
            return json(array('code' => '404', 'info' => '订单状态不符合删除条件'));
        }
    }

//确认收货
    public function shouhuo(){
        // $uid=cookie('user_id');
        $order_sn=$_REQUEST['order_sn'];
        $uid=$_REQUEST['user_id'];
        $admin_id=$_REQUEST['admin_id'];

        $where = 'order_sn="'.$order_sn.'" and admin_id='.$admin_id.' and uid='.$uid;
        $status=db('goods_order')->where($where)->value('status');
        if($status==3){ //已完成 已关闭 状态
            $save['status']=4;
            $rows=db('goods_order')->where($where)->update($save);
            $goods=db('goods_order')->where('order_sn='.$order_sn)->value('goods_id');
            $goods_list=array_filter(explode(",",$goods));
            foreach($goods_list as $i=>$val){
                db('goods')->where('id='.$val)->setInc('sales',1);//销量增加
            }
            if($rows){
                $rec=db('share_rec')->where('order_sn='.$order_sn)->select();
                foreach($rec as $i => $val){
                    db('share_rec')->where('order_sn='.$order_sn)->setField('status',1);//修改佣金状态
                    db('user')->where('uid='.$val['user_id'])->setInc('count_sale',$val['money']);//累计可用提现佣金
                }

            }
            return json_encode(array('code' => '200', 'info' => '收货成功'));
        }else{
            return json_encode(array('code' => '404', 'info' => '订单状态不符合收货条件'));
        }
        // }

    }


    //优惠券列表和优惠  1满减 2折扣
    public function  coupon_list_($user_id,$price,$cate_info,$admin_id){

        // $where['user_id']=$user_id;
        $where='user_id='.$user_id.' and uc.admin_id='.$admin_id;
        $nowtime=time();

        if($price){ //下单时
            // $where['sale']=array('EGT',$price);
            $where.=' and order_coupon.sale <='.$price;

            // $where['endtime']=array('GT',$nowtime);
            $where.=' and uc.endtime >'.$nowtime;
            // $where['status']=0;
            $where.=' and uc.status=0';
            $wh = $where;
            $where = '';
            $cate_info=json_decode($cate_info,true);
            // dump($cate_info);exit;
            foreach($cate_info as $i=>$val){
                // if($i==0){
                // 	$where.=' and (uc.cate_id_b='.$val['cate_id1'].' and uc.cate_id_s=0) or ( uc.cate_id_b='.$val['cate_id1'].' and uc.cate_id_s='.$val['cate_id2'].')';
                // }else{
                // 	$where.=' or (uc.cate_id_b='.$val['cate_id1'].' and uc.cate_id_s=0) or ( uc.cate_id_b='.$val['cate_id1'].' and uc.cate_id_s='.$val['cate_id2'].')';
                // }
                $where.= ' or ('.$wh.' and uc.cate_id_b='.$val['cate_id1'].' and uc.cate_id_s=0)  or ('.$wh.' and  uc.cate_id_b='.$val['cate_id1'].' and uc.cate_id_s='.$val['cate_id2'].') ';

            }
        }
        $where = ltrim($where, ' or ');
//         var_dump($where);exit;

        $list=db('user_coupon as uc')->join('order_coupon ',' uc.coupon_id = order_coupon.id')->where($where)->order('uc.id desc,uc.status asc')->field('uc.id,order_coupon.title,uc.status,order_coupon.sale,order_coupon.minus,order_coupon.cate_id,order_coupon.cate_id2,uc.endtime,uc.addtime,order_coupon.type')->select();
//         return db()->getlastsql();exit;
        // foreach($list as $i => &$val){
        // 	$val['detail']=db('coupon')->where('id='.$val['coupon_id'])->find();
        // }
        return $list;
    }

    //我的下线
    public function share_friend($user_id,$admin_id){
//        dump($user_id);die;
        $one=db('user')->where('share_uid='.$user_id." and admin_id=".$admin_id)->select();

        $two=array();
        foreach($one as $i=>$val){
            $fri=db('user')->where('share_uid='.$val['id']." and admin_id=".$admin_id)->select();
            array_push($two,$fri);
        }
        $data['one']=$one;
        $data['two']=$two;
        // dump($data);exit;
        return json_encode($data);
    }


    public function cash_apply(){


        $data=$this->cash_apply_();
        if($data==10000){
            return json_encode(array('code' => '200', 'info' => '提交申请成功','data'=>$data));exit;
        }else{
            return json(array('code' => '404', 'info' => '提交失败'));exit;
        }
    }

    public function cash_apply_(){
        $add['user_id']=$_REQUEST['user_id'];
        $add['admin_id']=$_REQUEST['admin_id'];
        $add['money']=$_REQUEST['money'];
        $add['name']=$_REQUEST['name'];
        $add['card_number']=$_REQUEST['card_number'];
        $add['bank']=$_REQUEST['bank'];
        $add['addtime']=time();
        $add['status']=0;

        $rows=db('cash_apply')->insert($add);
        if($rows){
            return 10000;exit;
        }else{
            return  20000;exit;
        }
    }

    //获取组合库存
    public function get_stock(){
        $goods_id=$_REQUEST['goods_id'];
        $attr_id=$_REQUEST['attr_id']; //34,35,37
        $admin_id=$_REQUEST['admin_id']; //34,35,37

        $num=db('goods_stock')->where('goods_id='.$goods_id.' and attr_id="'.$attr_id.'"'." and admin_id=".$admin_id)->find();
//         dump( $num);exit;
        // return db()->getlastsql();exit;['stock']
        if(!empty($num)){
            return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $num)) ;
        }else{
            return json_encode(array('code' => '200', 'info' => '没有库存','data' => 0)) ;
        }

    }

    //获取等级和折扣
    public function get_grade(){
        $user_id=$_REQUEST['user_id'];
        $admin_id=$_REQUEST['admin_id'];

        $user_sale =db('user')->where('id='.$user_id)->find();
        $user_sale=$user_sale['count_sale'];
//             dump($user_sale);
        $grade=db('user_grade')->where('sale_money <='.$user_sale.' and status=1 and admin_id='.$admin_id)->order('sale_money desc')->limit(0,1)->select();
//             dump($grade);exit;
        if(!empty($grade)){
            return json_encode(array('code' => '200', 'info' => '获取成功','data'=>$grade));
        }else{
            return json_encode(array('code' => '404', 'info' => '获取失败'));
        }
    }


    //累计消费 获得分销身份

    public function up_level($order_sn){

        $order_pay=db('order')->where('order_sn='.$order_sn.' and status=2')->find(); //获取订单消费金额
        $user_id=db('order')->where('order_sn='.$order_sn.' and status=2')->find(); //获取用户id
        $user_id=$user_id['uid'];
        $share_status=db('wx_users')->where('uid='.$user_id)->find();//获取用户分销身份

        $sum_pay=db('wx_users')->where('uid='.$user_id)->setInc('count_sale',$order_pay['gold']);//累计用户消费金额后 返回金额
        $share_amount=db('share_config')->where('id=1')->find(); //获取配置的分销身份消费门槛


        if($share_status['share_status']==0){
            if($sum_pay>=$share_amount['amount']){
                db('wx_users')->where('uid='.$user_id)->setField('share_status',1); //给予用户分销身份
            }
        }



    }


    /**
     * 结算
     */
    public function sett_order(){
        $type=$_REQUEST['type'];
        // $user_id=cookie('user_id');
        $user_id=$_REQUEST['user_id'];
        // var_dump($user_id);exit;

        // $address_id=db('wx_users_address')->where('uid='.$user_id.' and isdefault=1')->limit(0,1)->value('id');
        // if($_REQUEST['address_id']){
        //   $address_id=$_REQUEST['address_id'];
        // }
        // if($address_id){
        //   $address=db('wx_users_address')->where('id='.$address_id)->find();
        // }

        if($type==1){
            $goods_id=$_REQUEST['goods_id'];
            $user_id=$_REQUEST['user_id'];
            $num=$_REQUEST['num'];
            $attr=$_REQUEST['attr'];
            // return $attr;exit;
            $data=db('goods')->where('id='.$goods_id)->field('id,title,img,price')->find();
            // $data['img']='/case/Uploads/'.$data['img'];
            // var_dump($data);exit;

            $attr=array_filter(explode(",", $attr));
            // $attr=array_filter(explode(",", $val['attr']));

            $text="";
            $price="";
            foreach($attr as $v=>$vol){
                $title=db('goods_attr')->where('goods_id='.$goods_id.' and id='.$vol)->value('attr_title');
                $money=db('goods_attr')->where('goods_id='.$goods_id.' and id='.$vol)->value('attr_price');
                if($v==0){
                    $text=$title;
                }else{
                    $text.="+".$title;
                }
                $price=$price+$money;
            }
            $data['num']=$num;
            $data['attr_text']=$text;//属性标题拼接
            $data['attr_price']=$price;//属性价格
            $data['sum_gold']=($data['price']+$price);//单商品总价格
            $data['sum_golds']=($data['price']+$price)*$num;//所有总价格
        }

        if($type==2){
            $car_id=$_REQUEST['car_id'];
            $car_id=array_filter(explode(",", $car_id));
            // var_dump($car_id);exit;
            $user_id=$_REQUEST['user_id'];
            $gold="";
            $nember="";


            foreach($car_id as $i=>$val){

                $goods_info[$i-1]=db('shopcar')->where('id='.$val)->field('id,goods_id,user_id,attr,num')->find();

                $data['goods_lists'][$i-1]=db('goods')->where('id='.$goods_info[$i-1]['goods_id'])->field('id,title,img,price,cat_id1,cat_id')->find();

                $num=$goods_info[$i-1]['num'];
                // $data['goods_lists'][$i-1]['img']='/case/Uploads/'.$data['goods_lists'][$i-1]['img'];
                // var_dump($num);
                $attr=explode(",", $goods_info[$i-1]['attr']);

                // var_dump($attr);exit;

                $text="";
                $price="";

                foreach($attr as $v=>$vol){

                    $title=db('goods_attr')->where('goods_id='.$goods_info[$i-1]['goods_id'].' and id='.$vol)->value('attr_title');
                    $money=db('goods_attr')->where('goods_id='.$goods_info[$i-1]['goods_id'].' and id='.$vol)->value('attr_price');
                    if($v==0){
                        $text=$title;
                    }else{
                        $text.="+".$title;
                    }
                    $price=$price+$money;
                }
                // exit;
                $data['goods_lists'][$i-1]['num']=$num;//商品数量
                $data['goods_lists'][$i-1]['attr_text']=$text;//属性标题拼接
                $data['goods_lists'][$i-1]['attr_price']=$price;//属性价格
                $data['goods_lists'][$i-1]['sum_gold']=($data['goods_lists'][$i-1]['price']+$price);//单商品总价格
                $data['goods_lists'][$i-1]['sum_golds']=($data['goods_lists'][$i-1]['price']+$price)*$num;//多个商品总价格

                $nember+=$num;
                $gold+=$data['goods_lists'][$i-1]['sum_golds'];

            }

            $data['count_gold']=$gold;//订单总价格
            $data['count_num']=$nember;//订单总数量

        }
        // var_dump($address);exit;
        // var_dump($data);exit;
        // $this->assign('type',$type);
        return json_encode(array('code' => '200', 'info' => '获取成功', 'data' => $data));
    }

    //单商品下单 type==1
    public function orderGenerate(){
        $POST=$_GET;
        // dump($_REQUEST);exit;
        try{
            // $dealer_id=$_REQUEST['dealer_id'];
            $goods_id=$_REQUEST['goods_id']; //商品
            $attr_id=$_REQUEST['attr_id']; //属性标签 ,13,14,15,
            $goods_num=$_REQUEST['goods_num']; //商品数量
            $uid =$_REQUEST['uid']; //用户id
            $order_price =$_REQUEST['price']; //总价格
            $admin_id =$_REQUEST['admin_id']; //总价格

            // $coupon_dis=$_REQUEST['coupon_dis'];//优惠
            $grade_dis=$_REQUEST['grade_dis'];//优惠
            $old_gold=$_REQUEST['old_price'];//优惠前的金额


            $address_name=$_REQUEST['address_name']; //收货人姓名
            $address_phone=$_REQUEST['address_phone']; //收货人手机
            $address_email=$_REQUEST['address_email']; //收货人邮箱
            $address_area=$_REQUEST['address_area']; //收货人区域
            $address_info=$_REQUEST['address_info']; //收货人详细地址
            $address_code=$_REQUEST['address_code']; //收货人编码


        }catch (ErrorException $e){
            return json_encode(array('code' => '404', 'info' => '参数有误'));exit;
        }

        $attr_stock=array_filter(explode(",",$attr_id));
        $attr_stock=implode(",",$attr_stock);
        // var_dump($attr_stock);exit;
        $stock=db('goods_stock')->where('goods_id='.$goods_id.' and attr_id="'.$attr_stock.'"')->value('stock');

        if($stock<=0){
            return json_encode(array('code' => '404', 'info' => '此商品无库存'));exit;
        }

        $Good=db('goods');

        $good=$Good->where('id='.$goods_id)->find();
        // $total=$good['total'];

        // if($total<=0){}return '404';

//        dump($good);return;
        // $data['group_id']=$dealer_id;
        $data['goods_id']=",".$goods_id.",";
        $data['gold']=$order_price;
        $data['admin_id']=$admin_id;



        if(!empty($_REQUEST['coupon_id'])){
            $coupon_id=$_REQUEST['coupon_id'];//使用的用户优惠券
            $data['coupon_id']=$this->use_coupon($coupon_id,$uid,$admin_id); //使用用户的优惠券 返回优惠券id
        }

        $data['old_gold']=$old_gold;
        $data['grade_dis']=$grade_dis;

        $data['address_name']=$address_name;
        $data['address_phone']=$address_phone;
        $data['address_email']=$address_email;
        $data['address_area']=$address_area;
        $data['address_info']=$address_info;
        $data['address_code']=$address_code;

        $message=$_REQUEST['message']; //留言

        if($message){
            $data['message']=$message;
        }


        $data['status']=1;
        $order_sn= date('YmdHis') . str_pad(mt_rand(10, 999999), 8, '2', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 3, '0', STR_PAD_LEFT);
        $data['order_sn']=$order_sn;
        $data['uid']=$uid;
        $data['create_time']=time();

        //******-_-_-_-_大佬专属特效提醒-_-_-_-_******(订单名称)
        $data['order_name']=db('admin')->where('id='.$admin_id)->value('store_name')."商品";
        //******-_-_-_-_大佬专属特效提醒-_-_-_-_******(订单名称)

        // dump($data);exit;
        $Order=db('goods_order');
        $order=$Order->insert($data);
        // return db()->getlastsql();
        $Order_Goods=db('order_shop_goods');
        $good_['uid']=$uid;
        $good_['order_sn']=$order_sn;
        $good_['goods_id']=$goods_id;
        // return $attr_id;exit;
        $good_['attr']=$attr_id;
        $good_['buy_num']=$goods_num;
        $good_['gold']=$old_gold;
        $good_['title']=$good['title'];
        $good_['title2']=$good['title2'];
        $good_['admin_id']=$admin_id;
        $good_['create_time']=time();
        $good_['img']=$good['img'];
        // var_dump($good_);exit;
        $Order_Goods->insert($good_);
        // return db()->getlastsql();exit;
//        return $this->payMoney($trade_no);
        if($order){

            return json_encode(array('code' => '200', 'info' => '订单生成成功','order_sn'=>$order_sn));exit;
            // return $order_sn;
        }else{
            return json_encode(array('code' => '404', 'info' => '订单生成失败'));exit;

            // return 20000;
        }



    }
    //多商品下单 type==2
    public function orderGenerateAll(){
        $POST=$_GET;
        // dump($_REQUEST);exit;
        try{
            // $dealer_id=$_REQUEST['dealer_id'];
            $shopcar_id=$_REQUEST['car_id']; //购物车id
            $uid =$_REQUEST['uid']; //用户id
            $order_price=$_REQUEST['price'];
            $admin_id=$_REQUEST['admin_id'];

            // $coupon_dis=$_REQUEST['coupon_dis'];//优惠
            $grade_dis=$_REQUEST['grade_dis'];//优惠
            $old_gold=$_REQUEST['old_price'];//优惠前的金额
            if(!empty($_REQUEST['coupon_id'])){
                $coupon_id=$_REQUEST['coupon_id'];//使用的用户优惠券
            }


            $address_name=$_REQUEST['address_name']; //收货人姓名
            $address_phone=$_REQUEST['address_phone']; //收货人手机
            $address_email=$_REQUEST['address_email']; //收货人邮箱
            $address_area=$_REQUEST['address_area']; //收货人区域
            $address_info=$_REQUEST['address_info']; //收货人详细地址
            $address_code=$_REQUEST['address_code']; //收货人编码
            $message=$_REQUEST['message']; //留言

        }catch (ErrorException $e){
            return (json(array('code' => '404','info'=>'参数有误')));exit;
        }

        $Good_Attr=db('goods_attr');
        $Good=db('goods');
        $Shopcar=db('shopcar');
        $Order_Goods=db('order_shop_goods');
        $order_sn= date('YmdHis') . str_pad(mt_rand(10, 999999), 8, '2', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 3, '0', STR_PAD_LEFT);
        $shop=array_filter(explode(',',$shopcar_id));
        //dump($shop);return;
        $price_sum=0;
        $good_gold=0;
        $attr=[];
        $goods_id=[];

        $error=array();
        $error_ver=0;
        // var_dump($shop);exit;
        foreach ($shop as $i=>$val){ //走组合库存
            $e_goods_id =$Shopcar->where('id='.$val)->value('goods_id');
            // return db()->getlastsql();exit;
            // return $e_goods_id;exit;
            $e_attr_id =$Shopcar->where('id='.$val)->value('attr');
            $e_goods_title =db('goods')->where('id='.$e_goods_id)->value('title');
            $stock=db('goods_stock')->where('goods_id='.$e_goods_id.' and attr_id="'.$e_attr_id.'"')->value('stock');
            // return $stock;exit;
            if($stock<=0){
                $error[$i-1]['title']=$e_goods_title;
                $error[$i-1]['stock']=$stock;
                $error_ver+=1;
            }

        }
        if($error_ver>0){
            return json_encode(array('code' => '404', 'info' => '订单生成失败!有商品没有库存','error'=>$error));exit;
        }


        // foreach ($shop as $i=>$s){ //走单库存
        //     $shopcar=$Shopcar->where('id='.$s)->select();
        //     $good=$Good->where('id='.$shopcar[0]['goods_id'])->find();
        //     $total=$good['total'];
        //     if($total<=0) return '404';
        // }

        foreach ($shop as $i=>$s){
            $price=0;
            $shopcar=$Shopcar->where('id='.$s)->select();
            $goods_num=$shopcar[0]['num'];
            $attr=explode(',',$shopcar[0]['attr']);
            $good=$Good->where('id='.$shopcar[0]['goods_id'])->find();

            $attr_price=0;
            // $Shopcar->where('id='.$s)->delete();
            foreach($attr as $x=>$attr_){
                $good_attr=$Good_Attr->where('id='.$attr_)->find();
                $attr_price=$attr_price+$good_attr['attr_price']*1;

            }
            $price=($attr_price+$good['price']*1)*$goods_num;
            $good_['uid']=$uid;
            $good_['order_sn']=$order_sn;

            $good_['goods_id']=$shopcar[0]['goods_id'];

            $good_['buy_num']=$goods_num;
            $good_['gold']=$price;
            $good_['title']=$good['title'];
            $good_['title2']=$good['title2'];
            $good_['admin_id']=$admin_id;
            $good_['create_time']=time();
            $good_['img']=$good['img'];
            $good_['attr']=",".implode(',',$attr).",";
            // return $good_['attr'];exit;
            // $good_['goods_id']=implode(',',$goods_id);
            $goods_id[]=$shopcar[0]['goods_id'];

            $Order_Goods->insert($good_);

        }



        // $data['group_id']=$dealer_id;
        $data['goods_id']=",".implode(",",$goods_id).",";
        $data['gold']=$order_price;

        $data['grade_dis']=$grade_dis;
        // $data['coupon_dis']=$coupon_dis;
        $data['old_gold']=$old_gold;

        if(!empty($_REQUEST['coupon_id'])){
            $coupon_id=$_REQUEST['coupon_id'];
            $data['coupon_id']=$this->use_coupon($coupon_id,$uid,$admin_id); //使用用户的优惠券 返回优惠券id
        }

        $data['address_name']=$address_name;
        $data['address_phone']=$address_phone;
        $data['address_email']=$address_email;
        $data['address_area']=$address_area;
        $data['address_info']=$address_info;
        $data['address_code']=$address_code;
        $data['admin_id']=$admin_id;
        $data['message']=$message;
        //******-_-_-_-_大佬专属特效提醒-_-_-_-_******(订单名称)
        $data['order_name']=db('admin')->where('id='.$admin_id)->value('store_name')."商品";
        //******-_-_-_-_大佬专属特效提醒-_-_-_-_******(订单名称)
        $data['status']=1;
        $data['order_sn']=$order_sn;
        $data['uid']=$uid;
        $data['create_time']=time();
        $Order=db('goods_order');
//        dump('asdasd');
        // dump($data);exit;
        $order=$Order->insert($data);
//        dump('asdasd');
//        dump($price);return;
        if($order){
            // return 10000;
            // return $order_sn;

            return json_encode(array('code' => '200', 'info' => '订单生成成功','order_sn'=>$order_sn));exit;

        }else{
            return json_encode(array('code' => '404', 'info' => '订单生成失败'));exit;
            // return 20000;
        }


    }
    //使用优惠券
    public function use_coupon($coupon_id,$user_id,$admin_id){

        $coupon_id=array_filter(explode(",", $coupon_id));
        foreach($coupon_id as $i=>$val){
            $data[$i]=db('user_coupon')->where('id='.$val.' and user_id='.$user_id." and admin_id=".$admin_id)->setField('status',1);
        }
        $data=implode(",",$data);//数组变字符串
        return $data;
    }

    //分销身份申请
    public function share_apply(){
        $user_id=$_REQUEST['user_id'];
        $add['status']=0;
        $add['addtime']=time();
        $add['name']=$_REQUEST['name'];
        $add['phone']=$_REQUEST['phone'];

        $vers=db('share_apply')->where('user_id='.$user_id.' and status=0')->find();
        if($vers){
            echo json_encode(array('code' => '201', 'info' => '已提交申请!等待审核'));exit;
        }
        $add['user_id']=$user_id;
        db('share_apply')->insert($add);
        return json_encode(array('code' => '200', 'info' => '提交成功!等待审核'));exit;
    }

    /**
     * 图片上传
     * @param $img
     * @return string
     */
    public function upload_img(){
//        $name=$_REQUEST['name'];
        $return = upload_goods('file',"applay_img");

        if(!empty($return)){
            $return='applay_img/'.$return;
            return json_encode(array('code' => '200', 'data' => $return));
        }else{
            return json_encode(array('code' => '404', 'info' => '提交成功!等待审核'));
        }



    }

}