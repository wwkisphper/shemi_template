<?php
namespace app\smi\controller;


class Statistics extends Base
{
    //注册量统计
    public function RegisterStatistics()
    {
        $begindate = input('get.begindate');
        $enddate = input('get.enddate');
        $begintime = strtotime($begindate);
        $endtime = strtotime($enddate) + 24 * 60 * 60;

        if(!($begindate && $enddate)){
            $begindate = date('Y-m-d',time()-24*3600*30);
            $enddate = date('Y-m-d',time());
            $begintime = strtotime($begindate);
            $endtime = strtotime($enddate) + 24 * 60 * 60;
        }

        $user = db('User');
        $field = 'create_time';
        $where['create_time'] = array('between',array($begintime,$endtime));
        $where['admin_id'] = session('admin_id');
        $data = IntervalStatistics($user,$where,$field,'create_time',$begintime,$endtime);   //区间统计

        $this -> assign('title','注册量统计');
        $this -> assign('begindate',$begindate);
        $this -> assign('enddate',$enddate);
        $this -> assign('data',$data);
        
        return $this -> fetch('RegisterStatistics');
    }

    //预约服务统计表
    public function ServeStatistics(){
        $adminId = session('admin_id');
        $begindate = input('get.begindate');
        $enddate = input('get.enddate');    
        if($begindate && $enddate){
            $begintime = strtotime($begindate);
            $endtime = strtotime($enddate) + 24 * 60 * 60 - 1;  
        }else{
            $begindate = date('Y-m-d',time()-24*3600*30);
            $enddate = date('Y-m-d',time());
            $begintime = strtotime($begindate);
            $endtime = strtotime($enddate) + 24 * 60 * 60 - 1;
        }

        $data = db('Order') 
        -> field("s.serve_name,o.sid,COUNT(case when ((o.status >= 0 AND o.opayway = 1) OR (o.status >= 1 AND o.opayway = 2)) AND o.admin_id={$adminId} then 1 else null end) AS ordering_num,COUNT(case when o.status = 3 AND o.admin_id={$adminId} then 1 else null end) AS complete_num,SUM(case when o.status = 3 AND o.admin_id={$adminId} then o.money else 0 end) AS total_money") 
        -> alias('o') 
        -> join('order_serve s','s.id = o.sid') 
        -> where("o.order_time >= {$begintime} AND o.order_time <= {$endtime} AND o.admin_id={$adminId}") 
        -> group('o.sid')
        -> order('total_money DESC')
        -> select();

        $this -> assign('title','服务订单统计');
        $this -> assign('begindate',$begindate);
        $this -> assign('enddate',$enddate);
        $this -> assign('data',$data);
        return $this -> fetch('ServeStatistics');
    }

    //积分商品兑换总统计
    public function GoodsOrderStatistics(){
    	$adminId = session('admin_id');
    	$begindate = input('get.begindate');
        $enddate = input('get.enddate');    
        if($begindate && $enddate){
            $begintime = strtotime($begindate);
            $endtime = strtotime($enddate) + 24 * 60 * 60 - 1;  
        }else{
            $begindate = date('Y-m-d',time()-24*3600*30);
            $enddate = date('Y-m-d',time());
            $begintime = strtotime($begindate);
            $endtime = strtotime($enddate) + 24 * 60 * 60 - 1;
        }

        $data = db('Shop_order')
        -> field("goods_name,COUNT(id) AS total_order,COUNT(case when status = 0 then 1 else null end) AS stauts0,COUNT(case when status >= 1 AND status <= 3 then 1 else null end) AS effective,COUNT(case when status = 4 then 1 else null end) AS cancel")
        -> where("create_time >= {$begintime} AND create_time <= {$endtime} AND admin_id={$adminId}")
        -> group('goods_name')
        -> order('total_order')
        -> select();

        $this -> assign('title','积分商品兑换统计');
        $this -> assign('begindate',$begindate);
        $this -> assign('enddate',$enddate);
        $this -> assign('data',$data);
        return $this -> fetch('goods_order_statistics');



    }
}
