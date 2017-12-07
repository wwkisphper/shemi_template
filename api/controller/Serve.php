<?php
namespace app\api\controller;

use think\Controller;

class Serve extends Controller
{
    //首页信息
    public function ServeIndex()
    {
        $adminId = input('post.admin_id');
        //检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //获得产品信息
        $ServeData = db('Serve') -> field('id,serve_name,picture,price') -> where("status = 1 AND admin_id={$adminId}") -> select();

        foreach ($ServeData as $key => $value) {
            $value['picture'] = explode(',',$value['picture']);
            $ServeData[$key]['picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/serve/'.$value['picture'][0];
        }
        
        

        //获得分类信息
        $ClassifyData = db('Classify') -> field('id,name,logo') -> where("admin_id={$adminId}") -> select();

        $ClassifyData = ExternalPublicURL($ClassifyData,'logo','serve');    //加上外部可调用的链接
        
        //获得轮播图信息
        $SlideData = db('Slide') -> field('id,picture,sid') -> where("admin_id={$adminId}") -> select();
        $SlideData = ExternalPublicURL($SlideData,'picture','serve');    //加上外部可调用的链接
        
        $result['serve'] = $ServeData;
        $result['classify'] = $ClassifyData;
        $result['slide'] = $SlideData;
        
        header('Content-Type:application/json; charset=utf-8');
        return json_encode($result);
    }

    //根据分类id查商品
    public function SearchServe()
    {
        $id = input('post.id');
        if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少分类id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Serve') -> field('id,serve_name,picture,price') -> where("status = 1 AND classify={$id}") -> select();
        foreach ($data as $key => $value) {
            $value['picture'] = explode(',',$value['picture']);
            $data[$key]['picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/serve/'.$value['picture'][0];
        }

        $info['status'] = 1;
        $info['content'] = $data;

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //服务详情
    public function ServeDetail()
    {
        $id = input('post.id');
        $adminId = input('post.admin_id');
        if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少服务id';
            return json_encode($info);
        }elseif($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //某服务的详细信息
        $data = db('Serve') -> field('id,serve_name,picture,order_time,price,able_worker,description,picture_description') -> where("id",$id) -> find();

        //处理预约时间
        $data['order_time'] = unserialize($data['order_time']);
        
        $today = date('D');
        $array1 = array();
        $array2 = array();
        foreach ($data['order_time'] as $key => $value) {
            if(strpos($key,$today) !== false){
                $array['content'] = array_shift($data['order_time']);
                $array['key'] = $key;
                $array1[] = $array;
                $array = array();
                foreach ($data['order_time'] as $k => $v) {
                    $array['content'] = array_shift($data['order_time']);
                    $array['key'] = $k;                 
                    $array1[] = $array;
                    $array = array();
                }
                break;
            }else{
                $array['content'] = array_shift($data['order_time']);
                $array['key'] = $key;
                $array2[] = $array;
                $array = array();
            }
        }
        $data['order_time'] = array_merge($array1,$array2);



        $data['order_time'] = model('Serve') -> AddSomeOrderTimeInfo($data['order_time']);  //添加一些如几月几日等信息

        //处理图片
        $data['picture'] = explode(',',$data['picture']);
        foreach ($data['picture'] as $key => $value) {
            $data['picture'][$key] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/serve/'.$value;
        }
        $data['picture_description'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/serve/'.$data['picture_description'];

        //可选的服务人员信息
        if($data['able_worker']){
        	$workerData = db('Worker') -> field('id,name,picture,introduce') -> where("id IN ({$data['able_worker']})") -> select();
	        $workerData = ExternalPublicURL($workerData,'picture','serve');
        }else{
        	$workerData = array();
        }
        

        //店铺信息
        $adminData = db('Admin') -> field('store_address,store_phone,pay_status,order_message_placeholder') -> find($adminId);

        $info['status'] = 1;
        $info['phone'] = $adminData['store_phone'];
        $info['address'] = $adminData['store_address'];
        $info['payway'] = $adminData['pay_status'];
        $info['data'] = $data;
        $info['worker'] = $workerData;
        $info['placeholder'] = $adminData['order_message_placeholder'];

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

    }




}