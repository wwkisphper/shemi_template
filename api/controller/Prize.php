<?php
namespace app\api\controller;

use think\Controller;
use think\Db;

class Prize extends Controller
{
	//奖品列表
	public function PrizeList(){
		$adminId = input('post.admin_id');
		//检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //查询及处理奖品数组
        $data = db('Prize') -> field('id,name,picture') -> where("admin_id={$adminId}") -> select();
        for ($i=0; $i < 8; $i++) { 
        	if(empty($data[$i])){
        		$data[$i] = array('id' => 0,'name' => '谢谢参与','picture' => 'thankyou.png');
        	}
        }
        shuffle($data);
        $data = ExternalPublicURL($data,'picture','prize');	//添加图片路径

        //抽奖积分设置
        $adminData = db('Admin') -> field('lottery_integral') -> find($adminId);

        $info['status'] = 1;
        $info['msg'] = '查询成功';
        $info['prize'] = $data;
        $info['lottery_integral'] = $adminData['lottery_integral'];

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
	}

	//抽奖
	public function Lottery(){
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

        //抽奖积分设置
        $adminData = db('Admin') -> field('lottery_integral') -> find($adminId);
        if(!$adminData['lottery_integral']){
        	$info['status'] = -2;
            $info['msg'] = '抽奖未开启';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        Db::startTrans();
        //扣取用户积分
       	$success = model('Prize') -> PrizeDeductionIntegral($uid,$adminData['lottery_integral']);
        if(!$success){
            Db::rollback();
            $info['status'] = -3;
            $info['msg'] = '扣除用户积分失败';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $pumpingId = model('Prize') -> pumping($adminId);   //抽奖

        if($pumpingId){
            $success = db('Prize') -> where("id={$pumpingId}") -> setDec('stock');
            if(!$success){
                Db::rollback();
                $info['status'] = -5;
                $info['msg'] = '扣除奖品数量失败';
                header('Content-Type:application/json; charset=utf-8');
                return json_encode($info);
            }

            $success = model('Prize') -> PumpingRecord($uid,$adminId,$pumpingId);   //记录中奖
            if($success){
                Db::commit();
                $info['status'] = 1;
                $info['msg'] = '中奖了';
                $info['prize_id'] = $pumpingId;
                header('Content-Type:application/json; charset=utf-8');
                return json_encode($info);
            }else{
                Db::rollback();
                $info['status'] = -6;
                $info['msg'] = '记录中奖失败';
                header('Content-Type:application/json; charset=utf-8');
                return json_encode($info);
            }
        }else{
            Db::commit();
            $info['status'] = -4;
            $info['msg'] = '谢谢惠顾';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

	}

	//用户中奖记录
	public function UserPumpingRecord(){
		$uid = input('post.uid');
		if($uid == 'undefined' || $uid =='null' || $uid == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Winning_record') 
        -> field('name,picture,status,create_time') 
        -> where("uid={$uid}")
        -> order('id DESC') 
        -> select();

        $data = ExternalPublicURL($data,'picture','prize');
        $data = TimeConversions($data,'create_time');

        $info['status'] = 1;
       	$info['msg'] = '查询成功';
       	$info['data'] = $data;

       	header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

	}
}