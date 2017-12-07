<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Loader;

class Order extends Controller
{
    //提交订单
    public function SubmitOrder(){
        $data['uid'] = input('post.uid');
        $data['money'] = input('post.money');
        $data['name'] = input('post.name');
        $data['phone'] = input('post.phone');
        $data['sid'] = input('post.sid');
        $data['opayway'] = input('post.opayway');
        $dayChoose = input('post.dayChoose');
        $hourChoose = input('post.hourChoose');
        $data['form_id'] = input('post.form_id');
        $data['admin_id'] = input('post.admin_id');

        //检测传参
        if($data['uid'] == 'undefined' || $data['uid'] =='null' || $data['uid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['money'] == 'undefined' || $data['money'] =='null' || $data['money'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少金额';
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
        }elseif($data['sid'] == 'undefined' || $data['sid'] =='null' || $data['sid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少服务id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['opayway'] == 'undefined' || $data['opayway'] =='null' || $data['opayway'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少支付方式参数';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($dayChoose == 'undefined' || $dayChoose =='null' || $dayChoose == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少选择日期';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($hourChoose == 'undefined' || $hourChoose =='null' || $hourChoose == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少选择的预约时间';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['form_id'] == 'undefined' || $data['form_id'] =='null' || $data['form_id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少submit的form_id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['admin_id'] == 'undefined' || $data['admin_id'] =='null' || $data['admin_id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //非必填或非提交参数
        $data['message'] = input('post.message');
        $data['worker_name'] = input('post.worker_name');
        $data['create_time'] = time();
        $data['order_time'] = strtotime($dayChoose) + $hourChoose * 60 * 60;
        $data['status'] = 0;

        $orderNumber = db('Order') -> where("admin_id={$data['admin_id']}") ->column('number');
        do{
            $data['number'] = date('YmdHis').mt_rand(10000,99999);
        }while(in_array($data['number'], $orderNumber));
        
        //获得服务的信息
        $ServeData = db('Serve') -> field('serve_name,picture') -> where('id',$data['sid']) -> find();
        $ServeData['picture'] = explode(',',$ServeData['picture']);
        $data['serve_picture'] = $ServeData['picture'][0];
        $data['serve_name'] = $ServeData['serve_name'];

        Db::startTrans();
        $success = db('Order') -> insert($data);
        if($success){
            $info['status'] = 1;
        }else{
            $info['status'] = -2;
        }

        if($data['opayway'] == 1){
        	Db::commit();
        	$adminData = db('Admin') -> field('sms_switch,sms_phone,sms_account_number,sms_password,sms_signature,email_switch,email_to') -> find($data['admin_id']);

        	//发送短信
        	if($adminData['sms_switch'] && $adminData['sms_phone'] && $adminData['sms_account_number'] && $adminData['sms_password'] && $adminData['sms_signature']){
        		Loader::import('ChuanglanSmsHelper.ChuanglanSmsApi');
        		$clapi  = new \ChuanglanSmsApi();
        		$clapi -> API_ACCOUNT = $adminData['sms_account_number'];
        		$clapi -> API_PASSWORD = $adminData['sms_password'];
        		$clapi -> sendSMS($adminData['sms_phone'],"【{$adminData['sms_signature']}】您有一个新的预约订单，请及时登录后台处理。预约服务：{$data['serve_name']}，姓名：{$data['name']}，联系电话：{$data['phone']}，留言：{$data['message']}，预约时间：".date('Y-m-d H:i:s',$data['order_time']));
        	}

        	//发送邮箱
        	if($adminData['email_switch'] && $adminData['email_to']){
        		$toemail = $adminData['email_to'];
		        $name = '尊敬的客户';
		        $subject = '预约订单通知';
		        $content= "您有一个新的预约订单，请及时登录后台处理。预约服务：{$data['serve_name']}，姓名：{$data['name']}，联系电话：{$data['phone']}，留言：{$data['message']}，预约时间：".date('Y-m-d H:i:s',$data['order_time']);
		        send_mail($toemail,$name,$subject,$content);
        	}
			header('Content-Type:application/json; charset=utf-8');
        	return json_encode($info);
        }else{
        	$openid = db('User') -> where("id",$data['uid']) -> value('openid');
            $adminData = db('Admin') -> field('appid,mch_id,mch_secret,secret,apiclient_cert_pem,apiclient_key_pem,rootca_pem') -> find($data['admin_id']);
            $url = 'https://jisu.shenmikj.com/reservation/public/api/Notify/notify';
            Loader::import('WxPayPubHelper.WxPayPubHelper');
        	$unifiedOrder = new \UnifiedOrder_pub();
	      	$unifiedOrder->setParameter("out_trade_no",$data['number']);
	      	$unifiedOrder->setParameter("body",$data['serve_name']);
	      	$unifiedOrder->setParameter("total_fee",$data['money']*100);
	      	$unifiedOrder->setParameter("trade_type","JSAPI");
	      	$unifiedOrder->setParameter("openid",$openid);
	      	$unifiedOrder->setParameter("notify_url",$url);
            $unifiedOrder->setParameter("appid",$adminData['appid']);
            $unifiedOrder->setParameter("mch_id",$adminData['mch_id']);
            $unifiedOrder->setParameter("key",$adminData['mch_secret']);
            $unifiedOrder-> cert['apiclient_cert_pem'] = "./uploads/cert/{$data['admin_id']}/".$adminData['apiclient_cert_pem'];
            $unifiedOrder-> cert['apiclient_key_pem'] = "./uploads/cert/{$data['admin_id']}/".$adminData['apiclient_key_pem'];
            $unifiedOrder-> cert['rootca_pem'] = "./uploads/cert/{$data['admin_id']}/".$adminData['rootca_pem'];
	      	$prepay_id = $unifiedOrder->getPrepayId();
	      	if(!$prepay_id){
	      		Db::rollback();
	      		$info['status'] = -3;
				$info['msg'] = '调微信支付失败';
				header('Content-Type:application/json; charset=utf-8');
        		return json_encode($info);
	      	}

	      	Db::commit();

	      	/* 获得jsApi参数 */
			$jsApi = new \JsApi_pub();
            $jsApi -> appid = $adminData['appid'];
            $jsApi -> secret = $adminData['secret'];
            $jsApi->setParameter("key",$adminData['mch_secret']);
			$jsApi->setPrepayId($prepay_id);
			$param = $jsApi->getParameters();

			$param = json_decode($param);
			$info['status'] = 1;
			$info['msg'] = '支付配置成功';
			$info['param'] = $param;
			header('Content-Type:application/json; charset=utf-8');
        	return json_encode($info);
        }
        


    }

    //查看订单列表
    public function UserOrderList()
    {
    	$id = input('post.id');

    	if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Order') -> field('id,number,money,status,worker_name,serve_name,serve_picture,order_time,refuse_reason,refund_status') -> where("uid={$id} AND (opayway = 1 OR (opayway = 2 AND (status BETWEEN 1 AND 4)) OR (opayway = 3 AND status = 3))") -> order('id DESC') -> select();
        foreach ($data as $key => $value) {
            $data[$key]['serve_picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/serve/'.$data[$key]['serve_picture'];
        	$data[$key]['order_time'] = date('Y年m月d日 H:i',$data[$key]['order_time']);
        }

        $info['status'] = 1;
        $info['data'] = $data;

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
    }

    //订单详情
    public function OrderDetial()
    {
        $id = input('post.id');
        if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少订单id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Order') -> field('id,number,money,worker_name,serve_name,serve_picture,order_time,create_time,name,phone,refuse_reason,message,admin_id') -> where("id",$id) -> find();
        $data['serve_picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/serve/'.$data['serve_picture'];
        $data['order_time'] = date("Y-m-d H:i:s",$data['order_time']);
		$data['create_time'] = date("Y-m-d H:i:s",$data['create_time']);
		
        $adminData = db('Admin') -> field('store_phone') -> find($data['admin_id']);
        $info['hotline'] = $adminData['store_phone'];

        $info['status'] = 1;
        $info['data'] = $data;

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

    }

    //退约接口
    public function ApplyCancelOrder(){
    	$id = input('post.id');

    	if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少订单id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data['refund_status'] = 1;
        $success = db('Order') -> where("id",$id) -> update($data);

        if($success){
        	$info['status'] = 1;
        }else{
        	$info['status'] = -2;
        	$info['msg'] = '更新订单失败';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

    }

     //提交客户自定义金额在线支付
    public function CustomizeOrderQuantity(){
        $data['uid'] = input('post.uid');
        $data['money'] = input('post.money');
        $data['name'] = '----';
        $data['phone'] = '----';
        $data['admin_id'] = input('post.admin_id');

        //检测传参
        if($data['uid'] == 'undefined' || $data['uid'] =='null' || $data['uid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['money'] == 'undefined' || $data['money'] =='null' || $data['money'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少金额';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['admin_id'] == 'undefined' || $data['admin_id'] =='null' || $data['admin_id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data['sid'] = 0;
        $data['opayway'] = 3;

        //非必填或非提交参数
        $nowtime = time();
        $data['message'] = '';
        $data['worker_name'] = '';
        $data['status'] = 0;
        $data['create_time'] = $nowtime;
        $data['order_time'] = $nowtime;
        $data['serve_picture'] = '';
        $data['serve_name'] = '店内在线支付';
        
        $orderNumber = db('Order') -> column('number');
        do{
            $data['number'] = date('YmdHis').mt_rand(10000,99999);
        }while(in_array($data['number'], $orderNumber));
        

        Db::startTrans();
        $success = db('Order') -> insert($data);
        if($success){
            $info['status'] = 1;
        }else{
            $info['status'] = -2;
        }

        if($data['opayway'] == 1){
        	Db::commit();
			header('Content-Type:application/json; charset=utf-8');
        	return json_encode($info);
        }else{
        	$openid = db('User') -> where("id",$data['uid']) -> value('openid');
            $adminData = db('Admin') -> field('appid,mch_id,mch_secret,secret,apiclient_cert_pem,apiclient_key_pem,rootca_pem') -> find($data['admin_id']);
            $url = 'https://jisu.shenmikj.com/reservation/public/api/Notify/notify';
            Loader::import('WxPayPubHelper.WxPayPubHelper');
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("out_trade_no",$data['number']);
            $unifiedOrder->setParameter("body",$data['serve_name']);
            $unifiedOrder->setParameter("total_fee",$data['money']*100);
            $unifiedOrder->setParameter("trade_type","JSAPI");
            $unifiedOrder->setParameter("openid",$openid);
            $unifiedOrder->setParameter("notify_url",$url);
            $unifiedOrder->setParameter("appid",$adminData['appid']);
            $unifiedOrder->setParameter("mch_id",$adminData['mch_id']);
            $unifiedOrder->setParameter("key",$adminData['mch_secret']);
            $unifiedOrder-> cert['apiclient_cert_pem'] = "./uploads/cert/{$data['admin_id']}/".$adminData['apiclient_cert_pem'];
            $unifiedOrder-> cert['apiclient_key_pem'] = "./uploads/cert/{$data['admin_id']}/".$adminData['apiclient_key_pem'];
            $unifiedOrder-> cert['rootca_pem'] = "./uploads/cert/{$data['admin_id']}/".$adminData['rootca_pem'];
            $prepay_id = $unifiedOrder->getPrepayId();
            if(!$prepay_id){
                Db::rollback();
                $info['status'] = -3;
                $info['msg'] = '调微信支付失败';
                header('Content-Type:application/json; charset=utf-8');
                return json_encode($info);
            }

            Db::commit();

            /* 获得jsApi参数 */
            $jsApi = new \JsApi_pub();
            $jsApi -> appid = $adminData['appid'];
            $jsApi -> secret = $adminData['secret'];
            $jsApi->setPrepayId($prepay_id);
            $jsApi->setParameter("key",$adminData['mch_secret']);
            $param = $jsApi->getParameters();
			$param = json_decode($param);
			$info['status'] = 1;
			$info['msg'] = '支付配置成功';
			$info['param'] = $param;
			header('Content-Type:application/json; charset=utf-8');
        	return json_encode($info);
        }
        


    }


}