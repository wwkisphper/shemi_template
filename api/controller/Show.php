<?php
namespace app\api\controller;

use think\Controller;

class Show extends Controller
{
	//店铺展示
	public function ShowIndex(){
        $adminId = input('post.admin_id');
        //检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }


        //店铺基本信息
    	$data = db('Admin') -> field('store_address,store_phone,store_coord,store_name') -> find($adminId);
        $info['address'] = $data['store_address'];
        $info['phone'] = $data['store_phone'];
        $info['coord'] = $data['store_coord'];
        $info['store_name'] = $data['store_name'];

        //服务轮播图
        $SlideData = db('Slide') -> field('id,picture,sid') -> where("admin_id={$adminId}") -> select();
        $SlideData = ExternalPublicURL($SlideData,'picture','serve');    //加上外部可调用的链接
        $info['slide'] = $SlideData;

        //店铺展示图
        $showData = db('Show_classify') -> field('c.id,c.classify_name,s.picture') -> alias('c') -> join('order_show s',"c.id=s.sc_id AND c.status=1 AND c.admin_id={$adminId}") -> select();
        $showData = ExternalPublicURL($showData,'picture','show');
        $keyArray = array('picture');	//要归入子内容的键值
		$showData = Tidy($showData,$keyArray);
		$info['show'] = $showData;

		//店铺信息
        $adminData = db('Admin') -> field('pay_status') -> find($adminId);
        $info['payway'] = $adminData['pay_status'];

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
	}
}