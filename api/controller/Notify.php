<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Loader;

class Notify extends Controller
{

  public function _initialize()
    {
       Loader::import('WxPayPubHelper.WxPayPubHelper');
    }
  /**
     * 结算
     */
    public function notify()
    {
        logFile(__FUNCTION__,'已被调用');

        //使用通用通知接口
        $notify = new \Notify_pub();
        $common = new \Common_util_pub();
        //存储微信的回调
        if(!empty($GLOBALS['HTTP_RAW_POST_DATA'])){
          $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
          $record = $common -> xmlToArray($xml);
          $notify -> saveData($xml);
          $msg = array();
          $msg = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        $orderData = db('Order') -> field('status,opayway,admin_id,serve_name,name,phone,message,order_time') -> where("number",$notify->data["out_trade_no"]) -> find();
        $adminData = db('Admin') -> field('mch_secret,sms_switch,sms_phone,sms_account_number,sms_password,sms_signature,email_switch,email_to') -> find($orderData['admin_id']);

        $notify -> setParameter("key",$adminData['mch_secret']);
        $checkSign = $notify->checkSign();

        if($checkSign == FALSE){
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
            $record['mymsg'] = "code or msg fail";
            logFile(__FUNCTION__,$notify->data["out_trade_no"].' '.$record['mymsg']);
        }else{
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        }

        $returnXml = $notify->returnXml();
        echo $returnXml;
        $notify -> setParameter("key",$adminData['mch_secret']);
        if($checkSign == TRUE){
            if ($notify->data["return_code"] == "FAIL") {
                //通信出错，此处应该更新一下定单状态，商户自行增删操作
                $record['mymsg'] = "communication fail";
                logFile(__FUNCTION__,$notify->data["out_trade_no"].' '.$record['mymsg']);
            }elseif($notify->data["result_code"] == "FAIL"){
                //业务出错，此处应该更新一下定单状态，商户自行增删操作
                $record['mymsg'] = "operation fail";
                logFile(__FUNCTION__,$notify->data["out_trade_no"].' '.$record['mymsg']);
            }else{
                //支付成功，此处应该更新一下定单状态，商户自行增删操作
                if($orderData['status'] == 1){
                  return true;
                }

                if($orderData['opayway'] == 3){
                  $data['status'] = 3;
                }else{
                  $data['status'] = 1;
                }
                
                $success = db('Order') -> where("number",$notify->data["out_trade_no"]) -> update($data);
                if($success){
                  //短信通知
                  if($adminData['sms_switch'] && $adminData['sms_phone'] && $adminData['sms_account_number'] && $adminData['sms_password'] && $adminData['sms_signature']){
                    Loader::import('ChuanglanSmsHelper.ChuanglanSmsApi');
                    $clapi  = new \ChuanglanSmsApi();
                    $clapi -> API_ACCOUNT = $adminData['sms_account_number'];
                    $clapi -> API_PASSWORD = $adminData['sms_password'];
                    $clapi -> sendSMS($adminData['sms_phone'],"【{$adminData['sms_signature']}】您有一个新的预约订单，请及时登录后台处理。预约服务：{$orderData['serve_name']}，姓名：{$orderData['name']}，联系电话：{$orderData['phone']}，留言：{$orderData['message']}，预约时间：".date('Y-m-d H:i:s',$orderData['order_time']));
                  }

                  //邮箱通知
                  //发送邮箱
                  if($adminData['email_switch'] && $adminData['email_to']){
                    $toemail = $adminData['email_to'];
                    $name = '尊敬的客户';
                    $subject = '预约订单通知';
                    $content= "您有一个新的预约订单，请及时登录后台处理。预约服务：{$orderData['serve_name']}，姓名：{$orderData['name']}，联系电话：{$orderData['phone']}，留言：{$orderData['message']}，预约时间：".date('Y-m-d H:i:s',$orderData['order_time']);
                    send_mail($toemail,$name,$subject,$content);
                  }
                  logFile(__FUNCTION__,"{$notify->data["out_trade_no"]} 更新订单状态成功");
                }else{
                  logFile(__FUNCTION__,"{$notify->data["out_trade_no"]} 更新订单状态失败");
                }
            }
        } else {
            logFile(__FUNCTION__,"checkSign未通过");
        }
    }
}