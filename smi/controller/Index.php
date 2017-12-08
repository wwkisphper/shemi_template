<?php
namespace app\smi\controller;


class Index extends Base
{
    public function index()
    {   
        $begindate = date('Y-m-d',time()-24*3600*30);
        $enddate = date('Y-m-d',time());
        $begintime = strtotime($begindate);
        $endtime = strtotime($enddate) + 24 * 60 * 60;
        $user = db('User');
        $field = 'create_time';
        $where['create_time'] = array('between',array($begintime,$endtime));
        $where['admin_id'] = session('admin_id');
        $data = IntervalStatistics($user,$where,$field,'create_time',$begintime,$endtime);   //区间统计

        $this -> assign('data',$data);
        return $this->fetch('index');
    }

    //配置设置
    public function InfoSet()
    {   
        $adminId = session('admin_id');
        if($this-> request -> isPost()){
            $data['id'] = $adminId;
            $data['appid'] = input('post.appid');
            $data['secret'] = input('post.secret');
            $data['successful_notice'] = input('post.successful_notice');
            $data['failure_notification'] = input('post.failure_notification');
            $data['store_name'] = input('post.store_name');
            $data['store_address'] = input('post.store_address');
            $data['store_phone'] = input('post.store_phone');
            $data['store_url'] = input('post.store_url');
            $data['pay_status'] = input('post.pay_status');
            $data['store_we'] = input('post.store_we');
            $data['store_coord'] = input('post.store_coord');
            $data['mch_secret'] = input('post.mch_secret');
            $data['mch_id'] = input('post.mch_id');
            $data['order_message_placeholder'] = input('post.order_message_placeholder');
            $data['sms_switch'] = input('post.sms_switch');
            $data['sms_phone'] = input('post.sms_phone');
            $data['sms_account_number'] = input('post.sms_account_number');
            $data['sms_password'] = input('post.sms_password');
            $data['sms_signature'] = input('post.sms_signature');
            $data['email_switch'] = input('post.email_switch');
            $data['email_to'] = input('post.email_to');
            $data['share_integral'] = input('post.share_integral');
            $data['sign_in_integral'] = input('post.sign_in_integral');
            $data['lottery_integral'] = input('post.lottery_integral');
            
            if(input('post.userpass')){
                $data['userpass'] = md5(input('post.userpass'));
            }

            if($_FILES['apiclient_cert_p12']['name']){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('apiclient_cert_p12');
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->validate(['size'=>156780,'ext'=>'p12'])->move(ROOT_PATH . "public/uploads/cert/{$adminId}/",'');
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $data['apiclient_cert_p12'] = $info->getFilename();
                }else{
                    // 上传失败获取错误信息
                    echo "<script>alert('".$file->getError()."');location.href=history.back();</script>";die;
                }
            }

            if($_FILES['apiclient_cert_pem']['name']){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('apiclient_cert_pem');
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->validate(['size'=>156780,'ext'=>'pem'])->move(ROOT_PATH . "public/uploads/cert/{$adminId}/",'');
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $data['apiclient_cert_pem'] = $info->getFilename();
                }else{
                    // 上传失败获取错误信息
                    echo "<script>alert('".$file->getError()."');location.href=history.back();</script>";die;
                }
            }

            if($_FILES['apiclient_key_pem']['name']){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('apiclient_key_pem');
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->validate(['size'=>156780,'ext'=>'pem'])->move(ROOT_PATH . "public/uploads/cert/{$adminId}/",'');
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $data['apiclient_key_pem'] = $info->getFilename();
                }else{
                    // 上传失败获取错误信息
                    echo "<script>alert('".$file->getError()."');location.href=history.back();</script>";die;
                }
            }

            if($_FILES['rootca_pem']['name']){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('rootca_pem');
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->validate(['size'=>156780,'ext'=>'pem'])->move(ROOT_PATH . "public/uploads/cert/{$adminId}/",'');
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $data['rootca_pem'] = $info->getFilename();
                }else{
                    // 上传失败获取错误信息
                    echo "<script>alert('".$file->getError()."');location.href=history.back();</script>";die;
                }
            }

            $success = db('Admin') -> update($data);
            if($success){
                $this -> success('修改配置成功','Index/InfoSet');
            }else{
                $this -> error('配置失败或配置无变化');
            }
        }else{
            
            $data = db('Admin') 
            -> field('appid,secret,successful_notice,failure_notification,store_name,store_address,store_phone,store_url,pay_status,store_we,store_coord,mch_id,mch_secret,apiclient_cert_p12,apiclient_cert_pem,apiclient_key_pem,rootca_pem,sms_switch,sms_phone,sms_account_number,sms_password,order_message_placeholder,sms_signature,email_switch,email_to,share_integral,sign_in_integral,lottery_integral') 
            -> find($adminId);

            $this -> assign('data',$data);
            return $this -> fetch('set');
        }

    }
}
