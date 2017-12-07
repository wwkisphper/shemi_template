<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Shop extends Controller
{
	//积分商城商品列表
	public function GoodsList(){
		$adminId = input('post.admin_id');
        $keyword = input('post.keyword');
        $orderType = input('post.order');
		//检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //获取商品列表
        $where['status'] = 1;
        $where['admin_id'] = $adminId;
        if($keyword){
            $where['goods_name'] = ['like',"{$keyword}%"];
        }

        $goodsData = db('Shop_goods') 
        -> field('id,goods_name,integral,picture')
        -> where($where)
        -> select();

        foreach ($goodsData as $key => $value) {
            $value['picture'] = explode(',',$value['picture']);
            $goodsData[$key]['picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/goods/'.$value['picture'][0];
        }

        if($orderType == 1){
            //所需积分从低到高
            sortArrByField($goodsData,'integral');
        }elseif($orderType == 2){
            //所需积分从高到低
            sortArrByField($goodsData,'integral',TRUE);
        }else{
            //最新排序
            sortArrByField($goodsData,'id',TRUE);
        }

        //获取轮播图列表
        $slide = db('Slide_shop') -> field('picture') -> where("admin_id = {$adminId}") -> select();
        $slide = ExternalPublicURL($slide,'picture','goods');

        $info['status'] = 1;
        $info['msg'] = '查询成功';
        $info['goods'] = $goodsData;
        $info['slide'] = $slide;

        return json($info);
	}

	//积分商品详情表
	public function GoodsDetail(){
		$id = input('post.id');

		//参数检测
		if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商品id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Shop_goods')
        -> field('id,goods_name,integral,description,picture')
        -> find($id);

        //处理图片
        $data['picture'] = explode(',',$data['picture']);
        foreach ($data['picture'] as $key => $value) {
            $data['picture'][$key] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/goods/'.$value;
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($data);

	}
	
	//提交订单
	public function SubmitOrder(){
		$data['uid'] = input('post.uid');
		$data['name'] = input('post.name');
		$data['phone'] = input('post.phone');
		$data['address'] = input('post.address');
		$data['message'] = input('post.message');
		$goodsId = input('post.goods_id');
		$data['admin_id'] = input('post.admin_id');

		if($data['uid'] == 'undefined' || $data['uid'] =='null' || $data['uid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['name'] == 'undefined' || $data['name'] =='null' || $data['name'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少联系人姓名';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['phone'] == 'undefined' || $data['phone'] =='null' || $data['phone'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少联系人电话';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['address'] == 'undefined' || $data['address'] =='null' || $data['address'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少收货地址';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($goodsId == 'undefined' || $goodsId =='null' || $goodsId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商品id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['admin_id'] == 'undefined' || $data['admin_id'] =='null' || $data['admin_id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //查询商品信息
        $goodsData = db('Shop_goods')
        -> field('goods_name,integral,picture')
        -> find($goodsId);

        $userIntegral = db('Promotion_integral') -> where("belong_uid={$data['uid']}") -> sum('integral');
        if($userIntegral < $goodsData['integral']){
        	$info['status'] = -2;
            $info['msg'] = '积分不足';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $goodsData['picture'] = explode(',',$goodsData['picture']);
        $nowtime = time();

        $data['integral'] = $goodsData['integral'];
        $data['goods_name'] = $goodsData['goods_name'];
        $data['goods_picture'] = $goodsData['picture'][0];
        $data['create_time'] = $nowtime;

        Db::startTrans();
        $success = db('Shop_order') -> insert($data);
        if(!$success){
        	Db::rollback();
        	$info['status'] = -3;
            $info['msg'] = '下单失败';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $record['belong_uid'] = $data['uid'];
        $record['integral'] = -$data['integral'];
        $record['source_uid'] = $data['uid'];

        $success = db('Promotion_integral') -> insert($record);
        if($success){
        	Db::commit();
        	$info['status'] = 1;
            $info['msg'] = '下单成功';
        }else{
        	Db::rollback();
        	$info['status'] = -4;
            $info['msg'] = '积分变动失败';
        }

        header('Content-Type:application/json; charset=utf-8');
       	return json_encode($info);
	}

    //用户积分变动详情
    public function userIntegralDetail(){
        $id = input('post.id');
        if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Promotion_integral')
        -> field('p.integral,u.nickname,u.picture')
        -> alias('p')
        -> join('order_user u',"p.source_uid = u.id AND p.belong_uid = {$id}")
        -> order('p.id DESC')
        -> select();

        $info['status'] = 1;
        $info['msg'] = '查询成功';
        $info['data'] = $data;

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //用户积分订单
    public function UserShopOrder(){
    	$id = input('post.id');
        if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Shop_order')
        -> field('id,integral,name,phone,goods_name,goods_picture,status,create_time')
        -> where("uid = {$id}")
        -> order('id DESC')
        -> select();

        $data = TimeConversions($data,'create_time');	//转换时间
      	$data = ExternalPublicURL($data,'goods_picture','goods');	//添加图片路径

      	$info['status'] = 1;
      	$info['msg'] = '查询成功';
      	$info['data'] = $data;

      	header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //查询某订单详情
    public function ShopOrderDetail(){
    	$id = input('post.id');
        if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少订单id';
            return json_encode($info);
        }

        //查询订单详情
        $data = db('Shop_order')
        -> field('id,integral,name,phone,address,message,express_delivery_name,express_delivery_number,goods_name,goods_picture,status,create_time,admin_id')
        -> find($id);

        $data['create_time'] = date("Y-m-d H:i:s",$data['create_time']);
        $data['goods_picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/goods/'.$data['goods_picture'];

        //查询商铺电话
        $adminData = db('Admin') -> field('store_phone') -> find($data['admin_id']);

        $info['hotline'] = $adminData['store_phone'];
        $info['status'] = 1;
        $info['data'] = $data;

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //确认收货
    public function ConfirmReceipt(){
    	$data['id'] = input('post.id');
        if($data['id'] == 'undefined' || $data['id'] =='null' || $data['id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少订单id';
            return json_encode($info);
        }

        $orderData = db('Shop_order') -> field('status') -> find($data['id']);
        if($orderData['status'] != 1){
        	$info['status'] = -2;
            $info['msg'] = '不在可确认收货状态';
            return json_encode($info);
        }

        $data['status'] = 2;
        $success = db('Shop_order') -> update($data);
        if($success){
        	$info['status'] = 1;
        	$info['msg'] = '确认收货成功';
        }else{
        	$info['status'] = -3;
        	$info['msg'] = '确认收货失败或已为收货状态';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }
}