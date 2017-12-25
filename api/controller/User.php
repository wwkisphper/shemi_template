<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class User extends Controller
{
    /**
     * 获取 OpenID
     */
    public function getOpenID()
    {
    	$adminId = input('get.admin_id');
    	//检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Admin') -> field('appid,secret') -> find($adminId);
        
        if(isset($_GET['code'])){
            $code = $_GET['code'];
            $url='https://api.weixin.qq.com/sns/jscode2session?appid='.$data['appid'].'&secret='.$data['secret'].'&js_code='.$code.'&grant_type=authorization_code';
            $result = MyCurlOfGet($url);
            $data = array(
                "status" => 1,
                "openid" => $result['openid']
            );
        } else {
            $data = array(
                "status" => -1,
                "msg" => "code参数获取失败"
            );
        }
        

        return json($data);
    }

    //登录
    public function Login()
    {
        $data['openid'] = input('post.openid');
        $data['nickname'] = input('post.nickname');
        $data['picture'] = input('post.picture');
        $data['admin_id'] = input('post.admin_id');
        $shareUid = input('post.share_uid');

        //检测传参
        if($data['openid'] == 'undefined' || $data['openid'] =='null' || $data['openid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户openid';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['nickname'] == 'undefined' || $data['nickname'] =='null' || $data['nickname'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户微信昵称';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['picture'] == 'undefined' || $data['picture'] =='null' || $data['picture'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户头像信息';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['admin_id'] == 'undefined' || $data['admin_id'] =='null' || $data['admin_id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }
        $id = db('User') -> where("openid = '{$data['openid']}' AND admin_id={$data['admin_id']}") -> value('id');

        if($id){
            db('User') -> where("id",$id) -> update($data);
            $info['status'] = 1;
            $info['id'] = $id;
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }else{
            $shareConfig= db('share_config')->where('admin_id='.$data['admin_id'])-> field('switch') -> find();
            if($shareUid != 'undefined' && $shareUid != 'null' && $shareUid !== '' && $shareConfig['switch']==1){
                $share_user= db('user')->where('id='.$shareUid)->value('share_status');
                if($share_user==1){
                    $data['share_uid']=$shareUid;

                }else{
                    $data['share_uid']="";

                }
            }
            $data['create_time'] = time();
            $id = db('User') -> insertGetId($data);
            if($id){
                $adminData = db('Admin') -> field('share_integral') -> find($data['admin_id']);
                if($shareUid != 'undefined' && $shareUid != 'null' && $shareUid !== '' && $adminData['share_integral']){
                    $share['belong_uid'] = $shareUid;
                    $share['source_uid'] = $id;
                    $share['integral'] = $adminData['share_integral'];
                    db('Promotion_integral') -> insert($share);

                }
                $info['status'] = 1;
                $info['id'] = $id;
                header('Content-Type:application/json; charset=utf-8');
                return json_encode($info);
            }else{
                $info['status'] = -2;
                header('Content-Type:application/json; charset=utf-8');
                return json_encode($info);
            }
        }
    }

    //我的信息界面信息
    public function MyInfo()
    {   
        $adminId = input('post.admin_id');
       	$uid = input('post.uid');
        //检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }
        
        //获取商户设置
        $data = db('Admin') -> field('store_address,store_phone,store_url,store_coord,sign_in_integral') -> find($adminId);
        $info['address'] = $data['store_address'];
        $info['phone'] = $data['store_phone'];
        $info['url'] = $data['store_url'];
        $info['coord'] = $data['store_coord'];

        if($uid != 'undefined' && $uid != 'null' && $uid != ''){
        	$info['user_integral'] = db('Promotion_integral') -> where("belong_uid={$uid}") -> sum('integral');
        	$nowtime = time();
        	$todayBegin = strtotime(date('Y-m-d',$nowtime));
        	$todayEnd = $todayBegin + 24 * 3600 - 1;
        	$info['today_sign_in'] = db('Sign') -> where("uid = {$uid} AND admin_id={$adminId} AND create_time >= {$todayBegin} AND create_time <= {$todayEnd}") -> count();
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //关于我们内容
    public function AboutUs()
    {   
        $adminId = input('post.admin_id');
        //检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Admin') -> field('store_we') -> find($adminId);
        $info['content'] = str_replace('/reservation','https://'.$_SERVER['HTTP_HOST'].'/reservation',$data['store_we']);
        

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

    }

    //获取商户id
    public function MerchantsId(){
        $appid = input('post.appid');
        //检测传参
        if($appid == 'undefined' || $appid =='null' || $appid == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户appid';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $merchantsId = db('Admin') -> where('appid',$appid) -> value('id');
        if($merchantsId){
            $info['status'] = 1;
            $info['admin_id'] = $merchantsId; 
        }else{
            $info['status'] = -2;
            $info['msg'] = '没有该商户';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //签到获取积分
    public function SignIn(){
    	$adminId = input('post.admin_id');
    	$uid = input('post.uid');
        //检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($uid == 'undefined' || $uid =='null' || $uid == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $adminData = db('Admin') -> field('sign_in_integral') -> find($adminId);

        $nowtime = time();
        $todayBegin = strtotime(date('Y-m-d',$nowtime));
        $todayEnd = $todayBegin + 24 * 3600 - 1;
        Db::startTrans();
        $check = db('Sign') -> where("uid = {$uid} AND admin_id={$adminId} AND create_time >= {$todayBegin} AND create_time <= {$todayEnd}") -> count();

        if(!$check){
        	if(!$adminData['sign_in_integral']){
        		Db::rollback();
        		$info['status'] = -3;
        		$info['msg'] = '未开启签到';

        		header('Content-Type:application/json; charset=utf-8');
        		return json_encode($info);
        	}

        	//签到写入
        	$data['admin_id'] = $adminId;
        	$data['uid'] = $uid;
        	$data['create_time'] = $nowtime;

        	$success = db('Sign') -> insert($data);
        	if(!$success){
        		Db::rollback();
        		$info['status'] = -2;
        		$info['msg'] = '签到失败';

        		header('Content-Type:application/json; charset=utf-8');
        		return json_encode($info);
        	}

        	//签到积分
        	$integral['belong_uid'] = $uid;
        	$integral['integral'] = $adminData['sign_in_integral'];
        	$integral['source_uid'] = $uid;

        	$success = db('Promotion_integral') -> insert($integral);
        	if($success){
        		Db::commit();
        		$info['status'] = 1;
        		$info['msg'] = '签到成功';
        	}else{
        		Db::rollback();
        		$info['status'] = -4;
        		$info['msg'] = '积分记录失败';
        	}

        }else{
        	Db::rollback();
        	$info['status'] = 1;
        	$info['msg'] = '已签到';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
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
}