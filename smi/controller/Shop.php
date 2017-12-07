<?php
namespace app\smi\controller;
use think\Db;

class Shop extends Base
{
	//积分商城商品列表
	public function GoodsList(){
		$adminId = session('admin_id');
		$keyword = input('get.keyword');
		if($keyword){
			$data = db('Shop_goods') 
			-> field('id,goods_name,integral,picture,status') 
			-> where("admin_id={$adminId} AND goods_name LIKE '{$keyword}%'")
			-> select();
			$page = '';
		}else{
			$list = db('Shop_goods') 
			-> field('id,goods_name,integral,picture,status') 
			-> where("admin_id={$adminId}")
			-> order("id DESC")
			-> paginate(10);
			$data = $list -> all();
			$page = $list -> render();
		}

		$data = StringToArray($data,'picture',',');	//拆分多图片

		$this -> assign('title','积分商品列表');
		$this -> assign('keyword',$keyword);
		$this -> assign('data',$data);
		$this -> assign('page',$page);

		return $this -> fetch('goods_list');
	}

	//添加积分商品
	public function AddGoods(){
		if($this-> request -> isPost()){
			$data['goods_name'] = input('post.goods_name');
			$data['integral'] = input('post.integral');
			$data['description'] = input('post.description');
			$data['admin_id'] = session('admin_id');
			$data['picture'] = upload('picture','goods');

			$success = db('Shop_goods') -> insert($data);
			if($success){
				$this -> success('添加成功','Shop/GoodsList');
			}else{
				$this -> error('添加失败');
			}
		}else{

			$this -> assign('title','添加积分商品');
			return $this -> fetch('add_goods');
		}
	}

	//积分商品上下架
	public function GoodsStatusAlter(){
		$data['id'] = input('post.id');
        $data['status'] = input('post.status');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Shop_goods','admin_id',$data['id']);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

        $success = db('Shop_goods') -> update($data);
        if($success !== false){
            return 1;
        }else{
            return -1;
        }
	}

	//修改积分商品
	public function AlterGoods(){

		$adminId = session('admin_id');
		$id = input('id');
		$check = CheckPermissions($adminId,'Shop_goods','admin_id',$id);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

		if($this-> request -> isPost()){
			$data['id'] = $id;
			$data['goods_name'] = input('post.goods_name');
			$data['integral'] = input('post.integral');
			$data['description'] = input('post.description');
			$data['status'] = input('post.status');

			if($_FILES['picture']['name'][0]){
                $data['picture'] = upload('picture','goods');
                model('Shop') -> DelGoodsPicture($data['id']);
            }

            $success = db('Shop_goods') -> update($data);
            if($success){
            	$this -> success('修改成功','Shop/GoodsList');
            }else{
            	$this -> error('修改失败');
            }

		}else{
			$data = db('Shop_goods') -> field('id,goods_name,integral,description,picture,status') -> find($id);
			$data['picture'] = explode(',',$data['picture']);

			$this -> assign('title','修改积分商品');
			$this -> assign('data',$data);

			return $this -> fetch('alter_goods');
		}
	}

	//积分订单列表
	public function OrderList(){
		$adminId = session('admin_id');
		$keyword = input('get.keyword');
		$begindate = input('get.begindate');
		$enddate = input('get.enddate');
		$status = input('get.status');

		$where = "admin_id = {$adminId}";
		$paginate['query'] = array();

		if($keyword){
			$where .= " AND (name = '{$keyword}' OR phone = '{$keyword}')";
			$paginate['query']['keyword'] = $keyword;
		}

		if($begindate){
			$begintime = strtotime($begindate);
			$where .= " AND create_time >= {$begintime}";
			$paginate['query']['begindate'] = $begindate;
		}

		if($enddate){
			$endtime = strtotime($enddate) + 24 * 3600 - 1;
			$where .= " AND create_time <= {$endtime}";
			$paginate['query']['enddate'] = $enddate;
		}


		if($status !== '' && $status !== NULL){
			$where .= "AND status = {$status}";
			$paginate['query']['status'] = $status;
		}

		$list = db('Shop_order') 
		-> field('id,integral,name,phone,address,message,goods_name,goods_picture,create_time,status,express_delivery_name,express_delivery_number')
		-> where($where)
		-> order('id DESC')
		-> paginate(50,false,$paginate);

		$data = $list -> all();
		$page = $list -> render();

        $data = TimeConversions($data,'create_time');   //处理时间戳

        $this -> assign('title','积分兑换订单');
        $this -> assign('keyword',$keyword);
        $this -> assign('begindate',$begindate);
        $this -> assign('enddate',$enddate);
        $this -> assign('status',$status);
        $this -> assign('data',$data);
        $this -> assign('page',$page);

        return $this -> fetch('order_list');
        
	}

	//确认发货
	public function OrderShip(){
		$data['id'] = input('post.id');
		$data['status'] = 1;
		$data['express_delivery_name'] = input('post.express_delivery_name');
		$data['express_delivery_number'] = input('post.express_delivery_number');

		$adminId = session('admin_id');
		$check = CheckPermissions($adminId,'Shop_order','admin_id',$data['id']);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

		$success = db('Shop_order') -> update($data);
		if($success){
			$this -> success('修改成功','Shop/OrderList');
		}else{
			$this -> error('修改失败或信息无变化');
		}
	}

	//确认订单完成
	public function OrderComplete(){
		$data['id'] = input('get.id');
		$data['status'] = 3;

		$adminId = session('admin_id');
		$check = CheckPermissions($adminId,'Shop_order','admin_id',$data['id']);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

		$success = db('Shop_order') -> update($data);
		if($success){
			$this -> success('修改成功','Shop/OrderList');
		}else{
			$this -> error('修改失败');
		}
	}

	//取消订单
	public function OrderCancel(){
		$data['id'] = input('get.id');
		$data['status'] = 4;

		$adminId = session('admin_id');
		$check = CheckPermissions($adminId,'Shop_order','admin_id',$data['id']);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

		Db::startTrans();
		//返还积分
		$orderData = db('Shop_order') -> field('integral,uid') -> find($data['id']);

		$record['belong_uid'] = $orderData['uid'];
		$record['integral'] = $orderData['integral'];
		$record['source_uid'] = $orderData['uid'];

		$success = db('Promotion_integral') -> insert($record);
		if(!$success){
			Db::rollback();
			$this -> error('返还积分失败');
		}

		//修改订单
		$success = db('Shop_order') -> update($data);
		if($success){
			Db::commit();
			$this -> success('修改成功','Shop/OrderList');
		}else{
			Db::rollback();
			$this -> error('修改失败');
		}
	}

	//查询用户详细积分变动记录
	public function UserIntegralDetail(){
		$id = input('get.id');

		$adminId = session('admin_id');
		$check = CheckPermissions($adminId,'User','admin_id',$id);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

		$list = db('Promotion_integral')
        -> field('p.integral,u.nickname,u.picture')
        -> alias('p')
        -> join('order_user u',"p.source_uid = u.id AND p.belong_uid = {$id}")
        -> order('p.id DESC')
        -> paginate(50);

        $data = $list -> all();
		$page = $list -> render();

		//获取总积分
		$total = db('Promotion_integral') -> where("belong_uid={$id}") -> sum('integral');

        $this -> assign('title','用户详细积分变动');
        $this -> assign('data',$data);
        $this -> assign('page',$page);
        $this -> assign('total',$total);

        return $this -> fetch('user_intergral_detail');
	}

	//首页轮播图列表
    public function SlideList(){
        $adminId = session('admin_id');
        $data = db('Slide_shop') -> field('id,picture') -> where("admin_id = {$adminId}") ->select();

        $this -> assign('data',$data);

        return $this -> fetch('slide_list');
    }

    //添加轮播图
    public function AddSlide(){
        
        $data['picture'] = upload('picture','goods');
        $data['admin_id'] = session('admin_id');

        $success = db('Slide_shop') -> insert($data);
        if($success){
            $this -> success('添加成功','Shop/SlideList');
        }else{
            $this -> error('添加失败');
        }
    }

    //删除轮播图
    public function DelSlide(){
        
        $id = input('get.id');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Slide_shop','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $PicUrl = db('Slide_shop') -> where('id',$id)->value('picture');
        if(unlink('./uploads/goods/'.$PicUrl)){
            $success = db('Slide_shop') -> delete($id);
            if($success){
                $this -> success('删除成功','Shop/SlideList');
            }else{
                $this -> error('删除失败');
            }
        }else{
            $this -> error('删除原图片失败');
        }
    }


}