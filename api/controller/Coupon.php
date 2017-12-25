<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/12 0012
 * Time: 下午 3:23
 */

namespace app\api\controller;


use think\Controller;

class Coupon extends Controller
{
    //活动列表
    public function coupon_activi_list($admin_id){

        $nowtime=time();
        $where['start_time']=array('ELT',$nowtime);
        $where['status']=1;
        $where['admin_id']=$admin_id;
        $list=db('coupon_activi')->where($where)->order('seat_no desc,id desc')->select();
        // echo M()->getlastsql();exit;
        // dump($list);exit;
        foreach($list as $i=>&$val){
            $val['img']="coupon_activi/".$val['img'];
            $coupon_id=explode(",", $val['coupon_id']);
            $coupon_text="";
            foreach($coupon_id as $v=>$vol){
                $coupon_title=db('coupon')->where('id='.$vol)->value('title');
                if($v==0){
                    $coupon_text=$coupon_title."×1";
                }else{
                    $coupon_text.=" + ".$coupon_title."×1";
                }
            }
            $val['coupon_text']=$coupon_text;
        }

        return json($list);

    }

    //领取优惠券
    public function get_coupon($user_id,$activi_id,$admin_id){

        $vers=db('user_coupon')->where('user_id='.$user_id.' and pid='.$activi_id." and admin_id=".$admin_id)->find();
        // dump($vers);exit;
        if(empty($vers)){
            $coupon=db('coupon_activi')->where('id='.$activi_id)->select();
//            dump($coupon);
//            $coupon=explode(",", $coupon);
            foreach($coupon as $i=>$val){
                $detail=db('coupon')->where('id='.$val['coupon_id'])->find();
                // dump($detail);exit;
                // echo $detail['term'];exit;
                if(!empty($detail)){
                    $add['user_id']=$user_id;
                    $add['pid']=$activi_id;
                    $add['coupon_id']=$val['coupon_id'];
                    $add['cate_id_b']=$detail['cate_id'];
                    $add['cate_id_s']=$detail['cate_id2'];
                    $add['addtime']=time();
                    $add['admin_id']=$admin_id;
                    $add['endtime']=(time()+($detail['term']*86400));
                    $add['status']=0;
                    // dump($add);exit;
                    db('user_coupon')->insert($add);
                }

            }
            echo 10000;//领取成功
        }else{
            echo 20000;//已参与此活动
        }
    }

    //优惠券列表和优惠  1满减 2折扣
    public function coupon_list($user_id,$admin_id){

        // $where['user_id']=$user_id;
        $where='user_id='.$user_id." and uo.admin_id=".$admin_id;
        $nowtime=time();
        if(!empty($_REQUEST['price'])){ //下单时
            $price=$_REQUEST['price'];
            $cate_info=$_REQUEST['cate_info'];
            // $where['sale']=array('EGT',$price);
            $where.=' and order_coupon.sale <='.$price;

            // $where['endtime']=array('GT',$nowtime);
            $where.=' and uo.endtime >'.$nowtime;
            // $where['status']=0;
            $where.=' and uo.status=0';
            $wh = $where;
            $where = '';
            $cate_info=json_decode($cate_info,true);
            // dump($cate_info);exit;
            foreach($cate_info as $i=>$val){
                // if($i==0){
                // 	$where.=' and (tbl_user_coupon.cate_id_b='.$val['cate_id1'].' and tbl_user_coupon.cate_id_s=0) or ( tbl_user_coupon.cate_id_b='.$val['cate_id1'].' and tbl_user_coupon.cate_id_s='.$val['cate_id2'].')';
                // }else{
                // 	$where.=' or (tbl_user_coupon.cate_id_b='.$val['cate_id1'].' and tbl_user_coupon.cate_id_s=0) or ( tbl_user_coupon.cate_id_b='.$val['cate_id1'].' and tbl_user_coupon.cate_id_s='.$val['cate_id2'].')';
                // }
                $where.= ' or ('.$wh.' and uo.cate_id_b='.$val['cate_id1'].' and uo.cate_id_s=0)  or ('.$wh.' and  uo.cate_id_b='.$val['cate_id1'].' and uo.cate_id_s='.$val['cate_id2'].') ';

            }
        }
        $where = ltrim($where, ' or ');
        // dump($where);exit;

        $list=db('user_coupon as uo')->join('order_coupon ',' uo.coupon_id = order_coupon.id')->where($where)->order('uo.id desc,uo.status asc')->field('uo.id,order_coupon.title,uo.status,order_coupon.sale,order_coupon.minus,order_coupon.cate_id,order_coupon.cate_id2,uo.endtime,uo.addtime,order_coupon.type')->select();
//         echo db()->getlastsql();exit;
        // foreach($list as $i => &$val){
        // 	$val['detail']=M('coupon')->where('id='.$val['coupon_id'])->find();
        // }
        return json_encode($list);
    }

    //使用优惠券
    public function use_coupon($coupon_id,$user_id,$admin_id){

        $coupon_id=array_filter(explode(",", $coupon_id));
        foreach($coupon_id as $i=>$val){
            $data[$i]=db('user_coupon')->where('id='.$val.' and user_id='.$user_id." and admin_id=".$admin_id)->setField('status',1);
        }
        $data=implode(",",$data);//数组变字符串
        return json($data);

    }


}