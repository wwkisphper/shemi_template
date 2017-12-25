<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/15 0015
 * Time: 下午 7:39
 */

namespace app\api\controller;


use think\Controller;

class Goodsapi extends Controller
{

    //检索商城分销开关
    public function check_share($admin_id){
        //0关闭 1开始
        $check=db('share_config')->where('id=1 and admin_id='.$admin_id)->value('switch');
        return $check;
    }
    public function payGold($order_sn,$transaction_id){

        $Order=db('goods_order');
        $dat['order_sn']=$order_sn;
        $pay['status']=2;
        $pay['transaction_id']=$transaction_id;
        $pay['pay_time']=time();
        $w=$Order->where($dat)->update($pay);

        $order=db('goods_order')->where($dat)->find();
        $order_goods=db('order_shop_goods')->where($dat)->select();
        $admin_id=$order_goods[0]['admin_id'];

        foreach ($order_goods as $key => $value) {

            db('goods')->where('id='.$value['goods_id'])->setDec('total',$value['buy_num']);
            db('goods')->where('id='.$value['goods_id'])->setInc('sales',$value['buy_num']);

            $attr=$value['attr'];
            $attr=array_filter(explode(",", $attr));
            $attr=implode($attr);

            db('goods_stock')->where('attr_id="'.$attr.'"')->setDec('stock',$value['buy_num']);
        }

       $this->up_level($order_sn); //累计消费 获得分销身份

        $share_check=$this->check_share($admin_id);//检查商城分销开关

        if($share_check==1){
            $share_check=$this->create_share($order_sn,$admin_id); //生成佣金记录


        }

    }
    public function up_level($order_sn){

        $order_pay=db('goods_order')->where('order_sn='.$order_sn.' and status=2')->find(); //获取订单消费金额
        $user_id=db('goods_order')->where('order_sn='.$order_sn.' and status=2')->find(); //获取用户id
        $user_id=$user_id['uid'];
        $share_status=db('user')->where('id='.$user_id)->find();//获取用户分销身份

        $sum_pay=db('user')->where('id='.$user_id)->setInc('count_sale',$order_pay['gold']);//累计用户消费金额后 返回金额
        $share_amount=db('share_config')->where('id=1')->find(); //获取配置的分销身份消费门槛


        if($share_status['share_status']==0){
            if($sum_pay>=$share_amount['amount']){
                db('user')->where('id='.$user_id)->setField('share_status',1); //给予用户分销身份
            }
        }



    }


    //支付后 分成佣金记录

    public function create_share($order_sn,$admin_id){

        $price=db('goods_order')->where('order_sn='.$order_sn.' and status=2')->value('gold'); //获取订单消费金额
        $user_id=db('goods_order')->where('order_sn='.$order_sn.' and status=2')->value('uid'); //获取用户id

        $one=db('share_config')->where('id=1')->value('one_share');
        $two=db('share_config')->where('id=1')->value('two_share');

        $up_id=db('user')->where('id='.$user_id)->value('share_uid');
        if($up_id){
            $add['user_id']=$up_id;
            $add['order_sn']=$order_sn;
            $add['order_money']=$price;
            $add['money']=($price*$one);
            $add['admin_id']=$admin_id;
            $add['status']=0;
            $add['addtime']=time();
            db('share_rec')->insert($add);
            $upup_id=db('user')->where('uid='.$up_id)->value('share_uid');
            if($upup_id){
                $add1['user_id']=$up_id;
                $add1['order_sn']=$order_sn;
                $add1['order_money']=$price;
                $add1['money']=($price*$two);
                $add1['status']=0;
                $add1['addtime']=time();
                db('share_rec')->insert($add1);
            }
        }

    }
}