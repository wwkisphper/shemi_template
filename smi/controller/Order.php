<?php
namespace app\smi\controller;
use think\Db;
use think\Loader;

class Order extends Base
{    
    //订单列表
    public function OrderList(){
        $adminId = session('admin_id');
        $keyword = input('get.keyword');
        $begindate = input('get.begindate');
        $enddate = input('get.enddate');
        session('order_keyword',$keyword);
        session('order_begindate',$begindate);
        session('order_enddate',$enddate);
        $begintime = strtotime($begindate);
        $endtime = strtotime($enddate) + 24 * 60 * 60;
        if($keyword || ($begindate && $enddate)){
            if($begindate && $enddate){
                $list = db('Order') 
                -> field('id,number,money,name,phone,message,status,opayway,create_time,worker_name,serve_name,order_time,refuse_reason')
                -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND (number LIKE '{$keyword}%' OR name LIKE '{$keyword}%' OR phone LIKE '{$keyword}%') AND order_time BETWEEN {$begintime} AND {$endtime} AND admin_id={$adminId}") 
                -> order('order_time DESC') 
                -> paginate(50,false,['query' => array('keyword' => $keyword, 'begindate' => $begindate, 'enddate' => $enddate)]);
            }else{
                $list = db('Order') 
                -> field('id,number,money,name,phone,message,status,opayway,create_time,worker_name,serve_name,order_time,refuse_reason') 
                -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND (number LIKE '{$keyword}%' OR name LIKE '{$keyword}%' OR phone LIKE '{$keyword}%') AND admin_id={$adminId}") 
                -> order('order_time DESC') 
                -> paginate(50,false,['query' => array('keyword' => $keyword)]);
            }
        }else{
            $list = db('Order')
                -> field('id,number,money,name,phone,message,status,opayway,create_time,worker_name,serve_name,order_time,refuse_reason') 
                -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND admin_id={$adminId}") 
                -> order('order_time DESC') 
                -> paginate(50);
        }
        
        $data = $list -> all();

        $data = TimeConversions($data,'create_time');   //处理时间戳
        $data = TimeConversions($data,'order_time');    
        $page = $list -> render();
        //订单总数
        $orderCount = db('Order') -> field('id') -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND admin_id={$adminId}") -> count();

        $this -> assign('keyword',$keyword);
        $this -> assign('begindate',$begindate);
        $this -> assign('enddate',$enddate);
        $this -> assign('title','订单列表');
        $this -> assign('data',$data);
        $this -> assign('page',$page);
        $this -> assign('orderCount',$orderCount);

        return $this -> fetch('order_list');
    }

    //确认接单
    public function OrderAffirm(){
        $adminId = session('admin_id');
        $id = input('get.id');

        $check = CheckPermissions($adminId,'Order','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $success = db('Order') -> where('id',$id) -> setField('status',2);
        if($success){
        	$orderData = db('Order') -> field('o.name,o.phone,o.order_time,o.worker_name,o.serve_name,u.openid,o.form_id') -> alias('o') ->join('order_user u',"o.uid=u.id AND o.id={$id}") -> find();
        	$orderData['order_time'] = date("Y-m-d H:i",$orderData['order_time']);
        	if(!$orderData['worker_name']){
        		$orderData['worker_name'] = '未选择';
        	}
            $adminData = db('Admin') -> field('appid,secret,successful_notice') -> where('id',$adminId) -> find();
        	GetAccessToken($adminData);    //获取access_token,并传入相关小程序参数
    		$url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.cache('access_token_'.session('admin_id'));
    		$sendData['touser'] = $orderData['openid'];
            
            
			$sendData['template_id'] = $adminData['successful_notice'];
			$sendData['form_id'] = $orderData['form_id'];
			$sendData['data'] = array(
				'keyword1' => array('value' => $orderData['name'],'color' => '#353535'),
				'keyword2' => array('value' => $orderData['phone'],'color' => '#353535'),
				'keyword3' => array('value' => $orderData['order_time'],'color' => '#353535'),
				'keyword4' => array('value' => $orderData['worker_name'],'color' => '#353535'),
				'keyword5' => array('value' => $orderData['serve_name'],'color' => '#353535'),
			);
			$result = MyCurlOfPost($url,$sendData);
            $this -> success('确认成功','Order/OrderList');
        }else{
            $this -> error('确认失败或已确认过');
        }
    }

    //客户已到店消费，完成预约订单
    public function OrderComplete(){
        $id = input('get.id');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Order','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $success = db('Order') -> where('id',$id) -> setField('status',3);
        if($success){
            $this -> success('完成订单成功','Order/OrderList');
        }else{
            $this -> error('完成订单失败或已完成过');
        }
    }

    //拒绝接单
    public function OrderRefuse(){
        $adminId = session('admin_id');
        $id = input('post.refuseId');

        $check = CheckPermissions($adminId,'Order','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $adminData = db('Admin') -> field('appid,secret,failure_notification,store_phone,mch_id,mch_secret,apiclient_cert_pem,apiclient_key_pem,rootca_pem') -> where('id',$adminId) -> find();
    	
    	$data['refuse_reason'] = input('post.refuse_reason');
    	$data['status'] = 4;
    	$orderData = db('Order') -> field('o.name,o.order_time,o.serve_name,o.number,o.money,o.opayway,o.form_id,o.refuse_reason,u.openid') -> alias('o') -> join("order_user u","o.uid=u.id AND o.id={$id}") -> find();
    	if($orderData['opayway'] == 1){
    		//到店支付单
    		$success = db('Order') -> where("id",$id) -> update($data);
    		if($success){
    			$orderData['order_time'] = date("Y-m-d H:i",$orderData['order_time']);
    			GetAccessToken($adminData);
    			$url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.cache('access_token_'.session('admin_id'));
    			$sendData['touser'] = $orderData['openid'];
            	
				$sendData['template_id'] = $adminData['failure_notification'];
				$sendData['form_id'] = $orderData['form_id'];
				$sendData['data'] = array(
					'keyword1' => array('value' => $orderData['name'],'color' => '#353535'),
					'keyword2' => array('value' => $orderData['order_time'],'color' => '#353535'),
					'keyword3' => array('value' => $orderData['serve_name'],'color' => '#353535'),
					'keyword4' => array('value' => $data['refuse_reason'],'color' => '#353535'),
					'keyword5' => array('value' => $adminData['store_phone'],'color' => '#353535'),

				);
				$result = MyCurlOfPost($url,$sendData);
    			$this -> success('拒绝成功','Order/OrderList');
    		}else{
    			$this -> error('拒绝订单失败');
    		}

    	}else{
    		//在线支付单
    		Db::startTrans();
    		$success = db('Order') -> where("id",$id) -> update($data);
    		if(!$success){
    			Db::rollback();
    			$this -> error('更新订单状态失败');
    			return false;
    		}

    		Loader::import('WxPayPubHelper.WxPayPubHelper');
    		$refund = new \Refund_pub();
		    $refund->setParameter("out_trade_no",$orderData['number']);
		    $refund->setParameter("out_refund_no",$orderData['number']);
		    $refund->setParameter("total_fee",$orderData['money']*100);   //总额
		    $refund->setParameter("refund_fee",$orderData['money']*100);    //
		    $refund->setParameter("op_user_id",'1228771602');
            $refund->setParameter("appid",$adminData['appid']);
            $refund->setParameter("mch_id",$adminData['mch_id']);
            $refund->setParameter("key",$adminData['mch_secret']);
            $refund-> cert['apiclient_cert_pem'] = "./uploads/cert/{$adminId}/".$adminData['apiclient_cert_pem'];
            $refund-> cert['apiclient_key_pem'] = "./uploads/cert/{$adminId}/".$adminData['apiclient_key_pem'];
            $refund->cert['rootca_pem'] = "./uploads/cert/{$adminId}/".$adminData['rootca_pem'];
		    $refundResult = $refund->getResult();
		    if($refundResult['result_code'] == 'SUCCESS'){
		    	Db::commit();
		    	$orderData['order_time'] = date("Y-m-d H:i",$orderData['order_time']);
    			GetAccessToken($adminData);
                $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.cache('access_token_'.session('admin_id'));
                $sendData['touser'] = $orderData['openid'];
                
                $sendData['template_id'] = $adminData['failure_notification'];
                $sendData['form_id'] = $orderData['form_id'];
                $sendData['data'] = array(
                    'keyword1' => array('value' => $orderData['name'],'color' => '#353535'),
                    'keyword2' => array('value' => $orderData['order_time'],'color' => '#353535'),
                    'keyword3' => array('value' => $orderData['serve_name'],'color' => '#353535'),
                    'keyword4' => array('value' => $data['refuse_reason'],'color' => '#353535'),
                    'keyword5' => array('value' => $adminData['store_phone'],'color' => '#353535'),

                );
				$result = MyCurlOfPost($url,$sendData);
		    	$this -> success('退款成功','Order/OrderList');
		    }else{
				Db::rollback();
    			$this -> error('退款失败');
		    }

    	}
    }

    //查询是否有新订单
    public function CheckNewOrder(){
        $adminId = session('admin_id');
        $data = db('Order') -> field('id,number,money,name,phone,message,opayway,worker_name,serve_name,order_time,status') -> where("((status = 0 AND opayway = 1) OR (status = 1 AND opayway = 2) OR (status = 3 AND opayway = 3 AND status_pop = 0)) AND admin_id={$adminId}") -> order("id DESC") -> find();
        
        if($data['status'] == 3){
            db('Order') -> where('id',$data['id']) -> update(array('status_pop' => 1)); //更新自定义订单弹出状态
        }

        if(!$data){
        	return false;
        }
        $data['order_time'] = date('Y-m-d H:i:s',$data['order_time']);
        
        if(!$data['worker_name']){
        	$data['worker_name'] = '未选择';
        }
        switch ($data['opayway']) {
        	case '1':
        		$data['opayway'] = '到店支付';
        		break;
        	case '2':
        		$data['opayway'] = '微信已支付';
        		break;
        	case '3':
        		$data['opayway'] = '客户自定义支付';
        		break;
        	default:
        		$data['opayway'] = '错误';
        		break;
        }
        if(session('newOrderId')){
        	if(session('newOrderId') != $data['id']){
        		$content = '<div class="all_div"><div class="first_di">订单号</div><div class="second_di"> '.$data['number'].'</div></div><div class="all_div"><div class="first_di">服务名称</div> <div class="second_di">'.$data['serve_name'].'</div></div><div class="all_div"><div class="first_di">付款总额</div> <div class="second_di">￥'.$data['money'].' '.$data['opayway'].'</div></div><div class="all_div"><div class="first_di">预约时间</div><div class="second_di">'.$data['order_time'].'</div></div><div class="all_div"><div class="first_di">联系人姓名</div> <div class="second_di">'.$data['name'].'</div></div><div class="all_div"><div class="first_di">联系电话</div> <div class="second_di">'.$data['phone'].'</div></div><div class="all_div"><div class="first_di">支付方式</div><div class="second_di">'.$data['opayway'].'</div></div><div class="all_div"><div class="first_di">预约服务人员 </div><div class="second_di">'.$data['worker_name'].'</div></div><div class="all_div"><div class="first_di">留言</div><div class="second_di">'.$data['message'].'</div></div>';
	            $foot = '<form class="am-form" action="'.url("Order/OrderAffirm").'" method="get"><input type="hidden" name="id" value="'.$data['id'].'"/><button class="am-btn" style="margin-top:3px;background:#F37B1D;margin-top: 20px;width: 60%;margin-left: 20%;color: #fff;margin-top: 20px;">接受预约</button><a href="javascript:void(0);"  onclick="InquiresRefuse('.$data['id'].')" class="am-btn" style="margin-top:3px;background:#0e90d2;margin-top: 20px;width: 60%;margin-left: 20%;color: #fff;margin-top: 20px;">拒绝预约</a></form>';

	            if($data['opayway'] == '客户自定义支付'){
	            	$info['content'] = $content;
	            }else{
	            	$info['content'] = $content.$foot;
	            }
	            
	            $info['status'] = 1;
	            session('newOrderId',$data['id']);
	            
	            return $info;
        	}else{
        		$info['status'] = -1;
        		return $info;
        	}
        }else{
            $content = '<div class="all_div"><div class="first_di">订单号</div><div class="second_di"> '.$data['number'].'</div></div><div class="all_div"><div class="first_di">服务名称</div> <div class="second_di">'.$data['serve_name'].'</div></div><div class="all_div"><div class="first_di">付款总额</div> <div class="second_di">￥'.$data['money'].' '.$data['opayway'].'</div></div><div class="all_div"><div class="first_di">预约时间</div><div class="second_di">'.$data['order_time'].'</div></div><div class="all_div"><div class="first_di">联系人姓名</div> <div class="second_di">'.$data['name'].'</div></div><div class="all_div"><div class="first_di">联系电话</div> <div class="second_di">'.$data['phone'].'</div></div><div class="all_div"><div class="first_di">支付方式</div><div class="second_di">'.$data['opayway'].'</div></div><div class="all_div"><div class="first_di">预约服务人员 </div><div class="second_di">'.$data['worker_name'].'</div></div><div class="all_div"><div class="first_di">留言</div><div class="second_di">'.$data['message'].'</div></div>';
            $foot = '<form class="am-form" action="'.url("Order/OrderAffirm").'" method="get"><input type="hidden" name="id" value="'.$data['id'].'"/><button class="am-btn" style="margin-top:3px;background:#F37B1D;margin-top: 20px;width: 60%;margin-left: 20%;color: #fff;margin-top: 20px;">接受预约</button><a href="javascript:void(0);"  onclick="InquiresRefuse('.$data['id'].')" class="am-btn" style="margin-top:3px;background:#0e90d2;margin-top: 20px;width: 60%;margin-left: 20%;color: #fff;margin-top: 20px;">拒绝预约</a></form>';

            if($data['opayway'] == '客户自定义支付'){
            	$info['content'] = $content;
            }else{
            	$info['content'] = $content.$foot;
            }
            
            $info['status'] = 1;
            session('newOrderId',$data['id']);

            return $info;
        }
    }

    //订单退款列表
    public function RefundOrderList(){
        $adminId = session('admin_id');
    	$list = db('Order') 
                -> field('id,number,money,name,phone,message,status,opayway,create_time,worker_name,serve_name,order_time,refund_status') 
                -> where("refund_status >= 1 AND admin_id={$adminId}") 
                -> order('refund_status') 
                -> paginate(50);
        $data = $list -> all();
        $data = TimeConversions($data,'create_time');   //处理时间戳
        $data = TimeConversions($data,'order_time');    
        $page = $list -> render();

        $this -> assign('title','退款申请订单列表');
        $this -> assign('data',$data);
        $this -> assign('page',$page);

        return $this -> fetch('refund_order_list');

    }

    //同意订单退约
    public function RefundOrderAgree(){
        $adminId = session('admin_id');
    	$id = input('get.id');

        $check = CheckPermissions($adminId,'Order','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

    	$data['refuse_reason'] = '客户申请退约';
    	$data['status'] = 4;
    	$data['refund_status'] = 2;
    	$orderData = db('Order') -> field('o.name,o.order_time,o.serve_name,o.number,o.money,o.opayway,o.form_id,o.refuse_reason,u.openid') -> alias('o') -> join("order_user u","o.uid=u.id AND o.id={$id}") -> find();
    	if($orderData['opayway'] == 1){
    		//到店支付单
    		$success = db('Order') -> where("id",$id) -> update($data);
    		if($success){
    // 			$orderData['order_time'] = date("Y-m-d H:i",$orderData['order_time']);
    // 			$text = file_get_contents('./static/set.txt');
    //     		$set = unserialize($text);
    // 			GetAccessToken();
    // 			$url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.cache('access_token_'.session('admin_id'));
    // 			$sendData['touser'] = $orderData['openid'];
    //         	//$sendData['page'] = 'http://'.$_SERVER['HTTP_HOST'].'/hotel/index.php/Web/Reservation/OrderDetail?id='.$id;
				// $sendData['template_id'] = $templateId ;
				// $sendData['form_id'] = $orderData['form_id'];
				// $sendData['data'] = array(
				// 	'keyword1' => array('value' => $orderData['name'],'color' => '#353535'),
				// 	'keyword2' => array('value' => $orderData['order_time'],'color' => '#353535'),
				// 	'keyword3' => array('value' => $orderData['serve_name'],'color' => '#353535'),
				// 	'keyword4' => array('value' => $orderData['refuse_reason'],'color' => '#353535'),
				// 	'keyword5' => array('value' => $set['phone'],'color' => '#353535'),

				// );
				// $result = MyCurlOfPost($url,$sendData);
    			$this -> success('同意退约成功','Order/RefundOrderList');
    		}else{
    			$this -> error('同意退约失败');
    		}

    	}else{
            $adminData = db('Admin') -> field('appid,mch_id,mch_secret,apiclient_cert_pem,apiclient_key_pem,rootca_pem') -> find($adminId);
    		//在线支付单
    		Db::startTrans();
    		$success = db('Order') -> where("id",$id) -> update($data);
    		if(!$success){
    			Db::rollback();
    			$this -> error('更新订单状态失败');
    			return false;
    		}
    		Loader::import('WxPayPubHelper.WxPayPubHelper');
    		$refund = new \Refund_pub();
		    $refund->setParameter("out_trade_no",$orderData['number']);
		    $refund->setParameter("out_refund_no",$orderData['number']);
		    $refund->setParameter("total_fee",$orderData['money']*100);   //总额
		    $refund->setParameter("refund_fee",$orderData['money']*100);    //
		    $refund->setParameter("op_user_id",'1228771602');
            $refund->setParameter("appid",$adminData['appid']);
            $refund->setParameter("mch_id",$adminData['mch_id']);
            $refund->setParameter("key",$adminData['mch_secret']);
            $refund->setParameter("apiclient_cert_pem","./uploads/cert/{$adminId}/".$adminData['apiclient_cert_pem']);
            $refund->setParameter("apiclient_key_pem","./uploads/cert/{$adminId}/".$adminData['apiclient_key_pem']);
            $refund->setParameter("rootca_pem","./uploads/cert/{$adminId}/".$adminData['rootca_pem']);
		    $refundResult = $refund->getResult();
		    if($refundResult['result_code'] == 'SUCCESS'){
		    	Db::commit();
		  //   	$orderData['order_time'] = date("Y-m-d H:i",$orderData['order_time']);
    // 			$text = file_get_contents('./static/set.txt');
    //     		$set = unserialize($text);
    // 			GetAccessToken();
    // 			$url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.cache('access_token_'.session('admin_id'));
    // 			$sendData['touser'] = $orderData['openid'];
    //         	//$sendData['page'] = 'http://'.$_SERVER['HTTP_HOST'].'/hotel/index.php/Web/Reservation/OrderDetail?id='.$id;
				// $sendData['template_id'] = $templateId ;
				// $sendData['form_id'] = $orderData['form_id'];
				// $sendData['data'] = array(
				// 	'keyword1' => array('value' => $orderData['name'],'color' => '#353535'),
				// 	'keyword2' => array('value' => $orderData['order_time'],'color' => '#353535'),
				// 	'keyword3' => array('value' => $orderData['serve_name'],'color' => '#353535'),
				// 	'keyword4' => array('value' => $orderData['refuse_reason'],'color' => '#353535'),
				// 	'keyword5' => array('value' => $set['phone'],'color' => '#353535'),

				// );
				// $result = MyCurlOfPost($url,$sendData);
		    	$this -> success('同意退约成功','Order/RefundOrderList');
		    }else{
				Db::rollback();
    			$this -> error('同意退约失败');
		    }

    	}
    }

    //拒绝退约申请
    public function RefundOrderRefuse()
    {
		$id = input('get.id');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Order','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

    	$data['refund_status'] = 3;
    	$success = db('Order') -> where("id",$id) -> update($data);
    	if($success){
    		$this -> success('拒绝申请成功','Order/RefundOrderList');
    	}else{
    		$this -> error('拒绝申请失败或已拒绝过');
    	}
    }

    //导出订单excel表单
    public function OrderExcel(){
    	$adminId = session('admin_id');
        $keyword = session('order_keyword');
        $begindate = session('order_begindate');
        $enddate = session('order_enddate');
        $begintime = strtotime($begindate);
        $endtime = strtotime($enddate) + 24 * 60 * 60;
        if($keyword || ($begindate && $enddate)){
            if($begindate && $enddate){
                $data = db('Order') 
                -> field('number,money,name,phone,status,opayway,create_time,worker_name,serve_name,order_time,refuse_reason')
                -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND (number LIKE '{$keyword}%' OR name LIKE '{$keyword}%' OR phone LIKE '{$keyword}%') AND order_time BETWEEN {$begintime} AND {$endtime} AND admin_id={$adminId}") 
                -> order('order_time DESC') 
                -> select();
            }else{
                $data = db('Order') 
                -> field('number,money,name,phone,status,opayway,create_time,worker_name,serve_name,order_time,refuse_reason') 
                -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND (number LIKE '{$keyword}%' OR name LIKE '{$keyword}%' OR phone LIKE '{$keyword}%') AND admin_id={$adminId}") 
                -> order('order_time DESC') 
                -> select();
            }
        }else{
            $data = db('Order')
                -> field('number,money,name,phone,status,opayway,create_time,worker_name,serve_name,order_time,refuse_reason') 
                -> where("((status > 0 AND opayway = 2) OR opayway = 1 OR (status = 3 AND opayway = 3)) AND admin_id={$adminId}") 
                -> order('order_time DESC') 
                -> select();
        }

        if(!$data){
        	echo '无订单数据';
        	die;
        }

        $data = TimeConversions($data,'create_time');   //处理时间戳
        $data = TimeConversions($data,'order_time');
        foreach ($data as $key => $value) {
        	switch ($value['status']) {
        		case '0':
        			$data[$key]['status'] = '未支付';
        			break;
        		case '1':
        			$data[$key]['status'] = '已付款';
        			break;
        		case '2':
        			$data[$key]['status'] = '已确认';
        			break;
        		case '3':
        			$data[$key]['status'] = '已完成';
        			break;
        		case '4':
        			$data[$key]['status'] = '已取消';
        			break;
        		default:
        			$data[$key]['status'] = '';
        			break;
        	}

        	switch ($value['opayway']) {
        		case '1':
        			$data[$key]['opayway'] = '到店支付';
        			break;
        		case '2':
        			$data[$key]['opayway'] = '线上支付';
        			break;
        		case '3':
        			$data[$key]['opayway'] = '自定义金额支付';
        			break;
        		default:
        			$data[$key]['opayway'] = '';
        			break;
        	}
        }

        $th = array('订单号','订单金额','联系人姓名','联系人电话','状态','支付方式','订单创建时间','工作人员名称','服务名称','预约时间','拒绝理由');
        array_unshift($data,$th);
        $time = date('YmdHis',time());

        Loader::import('PHPExcel',EXTEND_PATH);
        $phpexcel = new \PHPExcel();
        create_xls($phpexcel,$data,session('admin').'Order'.$time.'.xls');


    }

    //现金商品订单
    public function order_op($sts = ""){

//        if(!auth_check("order_list_w"))die;
        $admin_id = session('admin_id');



        $op = input('op');

        $now = time();

        $order_sn = input('order_sn')?:0;

        $map = "order_sn = '{$order_sn}' and admin_id=".$admin_id;

        $order = db('goods_order')->where($map)->find();

        $order['address'] = unserialize($order['address']);

        if($op == 'fachu'){ /* 发出 */

            if($order['status'] != 2 ){return json([0]);}


            $fachu['kuaidi_name'] = input('kuaidi_name');

            $fachu['kuaidi_sn'] = input('kuaidi_sn');

            // var_dump($fachu);exit;
            foreach($fachu as $val){if(!$val){return json([0]);}}


            $data['fachu_time'] = $now;
            $data['fachu_name'] = $fachu['kuaidi_name'];
            $data['fachu_sn'] = $fachu['kuaidi_sn'];

            $data['status'] = 3;

            db('goods_order')->where($map)->update($data);

            return json([1]);

        }

        if($op == 'refund'){ /* 受理申请 */
            // echo $order['status'];exit;


            if($order['status'] != 6 ){return json([0]);}

            $status = $_REQUEST['status'];
            $refund['status'] = $status;


            foreach($refund as $val){if(!$val){return json([0]);}}



//            $data['refunded_time'] = $now;

            $data['status'] = $status;

            db('goods_order')->where($map)->update($data);
            // echo Db::getlastsql();exit;


            // // 发送模板消息

            // $datat['openid'] = getUserInfo('openid','',$order['uid']);

            // $datat['order_sn'] = $order_sn;

            // $datat['create_time'] = cdate(0,$order['create_time']);

            // sendTempMsg('fachu',$datat);



            return json([1]);

        }

        if($op == 'express'){ /* 退货退款 */
            $admin_id=session('admin_id');
            $rows=$this->out_cash($order_sn,$admin_id);


            return $rows;exit;


        }

        if($op == 'recovery'){ /* 恢复已关闭的订单 */

            if($order['status'] != 5 ){return json([0]);}

            $data['status'] = 1;

            db('goods_order')->where($map)->update($data);



            // // 发送模板消息

            // $datat['openid'] = getUserInfo('openid','',$order['uid']);

            // $datat['order_sn'] = $order_sn;

            // $datat['create_time'] = cdate(0,$order['create_time']);

            // sendTempMsg('fachu',$datat);



            return json([1]);

        }

    }

    public function goods_order_list(){
        // var_dump($_SESSION);exit;

//        auth_alert("order_list_r");
        $admin_id = $_SESSION['reservation']['admin_id'];
//        $this->assign('auth',auth_check("order_list_w"));

        $op = input('op');

        $map = " 1 ";

        // $group_id=db('users')->where('username="'.$_SESSION['toy_admin_username'].'"')->value('group_id');
        // echo $group_id;exit;
        // if($group_id){
        //    $map .= " and group_id=".$group_id;
        // }

        $map .= " and o.uid = u.id ";
        $map=$map."and o.admin_id=".$admin_id;
        if($sts = input('sts')) $map .= " and o.status = {$sts} ";

        else $map .= " and o.status != 0 ";

        if($ctstar = input('ctstar')) $map .= " and o.create_time > ".strtotime($ctstar);

        if($ctend = input('ctend')) $map .= " and o.create_time < ".strtotime($ctend);

        if($order_sn = input('order_sn')) $map .= " and o.order_sn like '%{$order_sn}%' ";

        if($nickname = input('nickname')) $map .= " and u.nickname like '%{$nickname}%' ";

        if($address = input('address')) $map .= " and o.address like '%{$address}%' ";

        if($uid = input('uid')) $map .= " and o.uid = {$uid} ";




//         var_dump($map);exit;
        $list = db("goods_order o,order_user u")->field('o.*,u.nickname')->where($map)->order('o.create_time desc')->select();

//        dump(!empty($list));
        if(!empty($list)&&$list!=""){
            foreach($list as $k=>$val){
//            var_dump($val);exit;
                $map = "order_sn = '{$val['order_sn']}'";

                $list[$k]['goods'] = db('order_shop_goods')->where($map)->select();

                $sum_gold="";
                foreach($list[$k]['goods'] as $i=>$vol){
                    $sum_gold=$sum_gold+$vol['gold'];
                }
                $list[$k]['sum_gold']=$sum_gold;

                $list[$k]['address'] = unserialize($val['address']);

            }
        }


        if($op == 'export'){

            $list = $this->address_info($list,true);

            $data = array(array(

                '订单号',

                'UID',

                '微信昵称',

                '收货姓名',

                '联系电话',

                '订单状态',

                '金币数',

                '下单时间',

                '支付时间',

                '发货时间',

                '完成时间',

                '商品名称',

                '商品ID',

            ));

            unset($val);

            foreach($list as $val){

                foreach($val['goods'] as $k=>$vag){

                    if($k == 0){

                        switch ($val['status']) {



                            case 1: $val['status_name'] = '待付款'; break;

                            case 2: $val['status_name'] = '待发货'; break;

                            case 3: $val['status_name'] = '待收货'; break;

                            case 4: $val['status_name'] = '已完成'; break;

                            case 5: $val['status_name'] = '已关闭'; break;

                            case 6: $val['status_name'] = '申请退货/款'; break;

                            case 7: $val['status_name'] = '申请不通过'; break;

                            case 8: $val['status_name'] = '申请受理中'; break;

                            case 9: $val['status_name'] = '已退退货/款'; break;

                            default: break;

                        }

                        if($val['fachu']){

                            $val['fachu'] = unserialize($val['fachu']);

                            $val['fachu_info'] = "快递公司：{$val['fachu']['kuaidi_name']}；快递单号：{$val['fachu']['kuaidi_sn']}";

                        }



                        $data[] = array(

                            $val['order_sn'],

                            $val['uid'],

                            $val['nickname'],


                            $val['address']['name'],

                            $val['address']['phone'],

                            $val['status_name'],

                            $val['gold'],

                            cdate(0,$val['create_time']),

                            cdate(0,$val['pay_time']),

                            cdate(0,$val['fachu_time']),


                            cdate(0,$val['end_time']),

                            $vag['title'],

                            $vag['goods_id'],

                        );

                    }else{

                        $data[] = array('','','','','','','','','','','','','','','','','',$vag['title'],$vag['goods_id'],'￥'.$vag['gold']);

                    }

                }

            }

            create_xls($data,'订单列表_'.date("Y-m-d H:i"));



        }
        // var_dump($list);exit;

        $this->assign('list',$list);


        $count2 = db('order')->where("status = 2 and admin_id=".$admin_id)->count();
        $count=count($list);
        $this->assign("count",$count);
        $this->assign("count2",$count2);
        $this->assign("sts",$sts);
        $this->assign("ctend",$ctend);
        $this->assign("ctstar",$ctstar);
        $this->assign("address",$address);
        $this->assign("nickname",$nickname);
        $this->assign("order_sn",$order_sn);

        return  $this->fetch("goods_order_list");


    }


    public function detail(){

//        auth_alert("order_list_r");

//        $this->assign('auth',auth_check("order_list_w"));

        $order_sn = input('order_sn');
//        dump($order_sn);return;
        $order = $this->getOrder($order_sn);

        if(!$order){$this->error('该订单不存在','',2);die;}

        // $order['address'] = db('wx_users_address')->where('id='.$order['address'])->find();


        $order['user'] =$order['uid'];

        // $order['fachu'] = $order['fachu']?unserialize($order['fachu']):'';

        $map = "order_sn = '{$order_sn}'";
        $goods_info = db('goods_order')->where($map)->select();

        foreach($order['goods'] as $i=>&$val){
            $attr=array_filter(explode(",", $val['attr']));
            $text="";
            $price="";
            foreach($attr as $v=>$vol){
                $title=db('goods_attr')->where('goods_id='.$val['goods_id'].' and id='.$vol)->value('attr_title');
                $money=db('goods_attr')->where('goods_id='.$val['goods_id'].' and id='.$vol)->value('attr_price');
                $text=$text."  ".$title;
                $price=$price+$money;
            }
            $val['attrtext']=$text;
            $val['sum_gold']=$val['gold']+$price;
            // echo $val['attrtext'];exit;
        }
        // var_dump($order);exit;

        $order['apply_reson']=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->value('apply_content');
        $order['apply_time']=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->value('addtime');
        $order['apply_gold']=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->value('apply_gold');
        $apply_imgs=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->value('apply_imgs');
        $order['apply_imgs']=explode(",", $apply_imgs);

        $order['apply_type']=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->value('apply_type');

        if($order['apply_type']==2){
            $order['apply']=db('order_shop_apply')->where('order_sn="'.$order['order_sn'].'"')->field('express_name,express_sn,phone')->find();
        }
        // dump($order);exit;
        $this->assign('order',$order);

        return $this->fetch('detail');

    }

    public function address_info($address,$list = false){

        if(!$address){

            return "";

        }else{

            $city_data = db('city')->select();

            foreach($city_data as $val){

                $city[$val['id']] = $val['name'];

            }

            $pickup_data = db('pickup_station')->select();

            foreach($pickup_data as $val){

                $pickup[$val['id']] = $val;

            }

            if($list){

                foreach($address as $k=>$val){

                    $address[$k]['address']['province'] = $city[$val['address']['province']];

                    $address[$k]['address']['city'] = $city[$val['address']['city']];

                    $address[$k]['address']['county'] = $city[$val['address']['county']];

                    if($val['address']['pickup'] > 0){

                        $address[$k]['address']['pickup'] = $pickup[$val['address']['pickup']];

                    }

                }

            }else{

                $address = unserialize($address);

                $address['province'] = $city[$address['province']];

                $address['city'] = $city[$address['city']];

                $address['county'] = $city[$address['county']];

                if($address['pickup'] > 0){

                    $address['pickup'] = $pickup[$address['pickup']];

                }

            }

            return $address;

        }

    }

    /* ┏┳┳┳┳┳┳┳┳┳┳针对这个项目的方法┳┳┳┳┳┳┳┳┳┳┓ */
    function getOrder($sn = 0,$uid = 0){
        if(in_array($sn,array('all','now',-1,1,2,3,4,5,6,7,8))){
            if($sn == 'all'){
                $map = " status != 0 ";
            }else if($sn == 'now'){
                $map = " status in (1,2,3,4,5,6) ";
            }else{
                $map = " status = {$sn} ";
            }
            if($uid){
                $map .= " and uid = {$uid} ";
            }
            $order = db('goods_order')->where($map)->order('create_time desc')->select();
            foreach($order as $k=>$val){
                $map = "order_sn = '{$val['order_sn']}'";
                $order[$k]['goods'] = db('order_shop_goods')->where($map)->select();
                // $order[$k]['users'] = getUserInfo('','',$val['uid']);
                // $order[$k]['address'] = unserialize($val['address']);
                // foreach($order[$k]['goods'] as $m=>$vam){
                //     $goods = db('goods')->where("id = {$vam['goods_id']}")->find();
                //     $order[$k]['goods'][$m]['title'] = $goods['title'];
                //     $order[$k]['goods'][$m]['total'] = $goods['total'];
                //     $order[$k]['goods'][$m]['status'] = $goods['status'];
                // }
            }
            return $order;
        }else{
            $map = " order_sn = '{$sn}' ";
            if($uid){
                $map .= " and uid = {$uid} ";
            }
            $order = db('goods_order')->where($map)->find();
            $order['goods'] = db('order_shop_goods')->where($map)->select();
            foreach($order['goods'] as $k=>$val){
                $goods = db('goods')->where("id = {$val['goods_id']}")->find();
                $order['goods'][$k]['total'] = $goods['total'];
                $order['goods'][$k]['status'] = $goods['status'];
            }
            return $order['goods']?$order:false;
        }
    }
    /* ┗┻┻┻┻┻┻┻┻┻┻针对这个项目的方法┻┻┻┻┻┻┻┻┻┻┛ */

    //退款
    public function out_cash($order_sn,$admin_id){

        $Order=db('goods_order');
        $adminData=db('admin')->find($admin_id);
        $orderData=$Order->where("order_sn=".$order_sn)->find();

        $Apply=db('order_shop_apply');
        $apply=$Apply->where('order_sn='.$order_sn)->find();
        $out_refund_no=date('YmdHis') . str_pad(mt_rand(1, 99999), 4, '2', STR_PAD_LEFT).str_pad(mt_rand(1, 99999), 3, '4', STR_PAD_LEFT);
        $total_fee=$orderData['gold'];
        $refund_fee=$apply['apply_gold'];
        $transaction_id=$orderData['transaction_id'];
        $openid=db('user')->where('id='.$orderData['uid'])->value('openid');
        $orderData['openid']=$openid;
        //在线支付单
        $adminId=$admin_id;
        Db::startTrans();
        $dat['status']=9;
        $success=$Order->where("order_sn=".$order_sn)->update($dat);
        if(!$success){
            Db::rollback();
            $this -> error('更新订单状态失败');
            return false;
        }
//            if(!$success){
//                Db::rollback();
//                $this -> error('更新订单状态失败');
//                return false;
//            }
        Loader::import('WxPayPubHelper.WxPayPubHelper');
        $refund = new \Refund_pub();
        $refund->setParameter("out_trade_no",$order_sn);
        $refund->setParameter("out_refund_no",$out_refund_no);
        $refund->setParameter("total_fee",$total_fee*100);   //总额
        $refund->setParameter("refund_fee",$refund_fee*100);    //
        $refund->setParameter("op_user_id",'1228771602');
        $refund->setParameter("appid",$adminData['appid']);
        $refund->setParameter("mch_id",$adminData['mch_id']);
        $refund->setParameter("key",$adminData['mch_secret']);
        $refund-> cert['apiclient_cert_pem'] = "./uploads/cert/{$adminId}/".$adminData['apiclient_cert_pem'];
        $refund-> cert['apiclient_key_pem'] = "./uploads/cert/{$adminId}/".$adminData['apiclient_key_pem'];
        $refund->cert['rootca_pem'] = "./uploads/cert/{$adminId}/".$adminData['rootca_pem'];
        $refundResult = $refund->getResult();
        if($refundResult['result_code'] == 'SUCCESS'){
            Db::commit();
            $this -> success('退款成功','Order/OrderList');
        }else{
            Db::rollback();
            $this -> error('退款失败');
        }

    }
    
}