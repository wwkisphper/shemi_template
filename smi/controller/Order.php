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


    
}