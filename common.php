<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件



/*
    按照区间统计
    @param object $model 实例化模型
    @param array $where 查询条件
    @param string $field 所需要查询的字符串
    @param string $timeKey 根据的时间字段 
    @param int $begintime 开始时间戳
    $param int $endtime 结束时间戳
 */
function IntervalStatistics($model,$where,$field,$timeKey,$begintime,$endtime){
    $result = $model -> field($field) -> where($where) -> select();
    $part = ($endtime - $begintime)/(24*3600);
    for($i = 0; $i < $part;$i++){
        $data['title'][$i] = "'".date("Ymd",$begintime+$i*24*3600)."'";
        $count = 0;
        foreach ($result as $key => $value) {
            if($value[$timeKey] < $begintime+($i+1)*24*3600){
                $count++;
                unset($result[$key]);
            }
        }
        $data['count'][$i] = $count;
    }

    return $data;
}

/************************************************************** 
 * 
 *  将数组转换为JSON字符串（兼容中文） 
 *  @param  array   $array      要转换的数组 
 *  @return string      转换得到的json字符串 
 *  @access public 
 * 
 *************************************************************/
function JSON($array) { 
    arrayRecursive($array, 'urlencode', true); 
    $json = json_encode($array); 
    return urldecode($json); 
} 
/************************************************************** 
 * 
 *  使用特定function对数组中所有元素做处理 
 *  @param  string  &amp;$array     要处理的字符串 
 *  @param  string  $function   要执行的函数 
 *  @return boolean $apply_to_keys_also     是否也应用到key上 
 *  @access public 
 * 
 *************************************************************/
function arrayRecursive(&$array, $function, $apply_to_keys_also = false){ 
    static $recursive_counter = 0; 
    if (++$recursive_counter > 1000) { 
        die('possible deep recursion attack'); 
    } 
    foreach ($array as $key => $value) { 
        if (is_array($value)) { 
            arrayRecursive($array[$key], $function, $apply_to_keys_also); 
        } else { 
            $array[$key] = $function($value); 
        }                                        
        if ($apply_to_keys_also && is_string($key)) { 
            $new_key = $function($key); 
            if ($new_key != $key) { 
                $array[$new_key] = $array[$key]; 
                unset($array[$key]); 
            } 
        } 
    } 
    $recursive_counter--; 
}

    function getJssdk(){
        vendor('Wechat.Jssdk');
        $jssdk = new \Jssdk();
        $sign = $jssdk->getSignPackage();
        return "appId:'{$sign["appid"]}',timestamp:{$sign["timestamp"]},
            nonceStr:'{$sign["nonceStr"]}',signature:'{$sign["signature"]}'";
    }


/*
    信息提示及跳转函数
    @param string $string 提示信息
    @param string $url 跳转地址
 */
function alertMsg($string = "", $url = "")
{
    if (!$url) {
        if ($_SERVER['HTTP_REFERER']) {
            echo "<script>alert('$string');location.href='{$_SERVER['HTTP_REFERER']}';</script>";die;
        } else {
            echo "<script>alert('$string');location.href=history.back();</script>";die;
        }
    } else {
        echo "<script>alert('$string');location.href='$url';</script>";die;
    }
}

/*
    数据状态0，1变换，可用审核，上下架等只有0，1两状态方面
    @param object $model 实例的model对象
    @param int $id 数据ID
    @param char $key 状态字段名

*/
function CheckConversion($model,$id,$key){
    $status = $model -> where("id='{$id}'") -> getField($key);
    if($status){
        $data[$key] = 0;
    }else{
        $data[$key] = 1;
    }
    $success = $model -> where("id='{$id}'") -> save($data);
    if($success){
        return true;
    }else{
        return false;
    }
}
/*
    时间戳批量转换成需要的文本格式
    @param array $data 所传的查询结果二维数组
    @param string $tname 时间值所在的键值
    @param int $status 1精确到秒，2精确到日
*/
function TimeConversions($data,$tname,$status = 1){
    if($status == 1){
        foreach($data as $key => $v){
            $data[$key][$tname] = date("Y-m-d H:i:s",$v[$tname]);
        }
    }elseif($status == 2){
        foreach($data as $key => $v){
            $data[$key][$tname] = date("Y-m-d",$v[$tname]);
        }
    }
    
    return $data;
}

/*
    把二维数组内的某一个字段值的字符串拆分成数组并将该数组赋值回该字段
    @param array $data 所传的查询结果二维数组
    @param string $key 所需处理的键值
    @param string $string 拆分标识字符

 */
function StringToArray($data,$key,$string){
    foreach ($data as $k => $v) {
        $data[$k][$key] = explode($string, $v[$key]);
    }
    return $data;
}

/*
    时间筛选
    @param array $data 所传的查询结果二维数组
    @param int $begindate 开始时间
    @param int $enddate 结束时间
    @param char $key 时间字段名
 */
function TimeFiltrate($data,$begindate,$enddate,$key){
    if($begindate || $enddate){     //是否有时间筛选，有则进行筛选，无则直接返回
        foreach ($data as $k => $v) {
            if($begindate == $enddate){
                $tomorrow = $begindate + 24 * 3600;
                if($v[$key] < $begindate || $v[$key] > $tomorrow){
                    unset($data[$k]);
                }
            }elseif($begindate && $enddate){
                if($v[$key] < $begindate || $v[$key] > $enddate){
                    unset($data[$k]);
                }
            }elseif($begindate){
                if($v[$key] < $begindate){
                    unset($data[$k]);
                }
            }else{
                if($v[$key] > $enddate){
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }else{
        return $data;
    }
    
}

/*
    获取文本内的信息并从json格式转换成数组
    @param char $string 文件路径
    @param int $status 1为数据存储格式是json，0为数据存储格式是以','分隔存储的字符串,2为序列化格式
 */

function ReadPointFile($string,$status){
    $file = fopen($string, "r") or die("Unable to open file!");
    $contents = fread($file,filesize($string));
    if($status == 1){
        $data = json_decode($contents,true);
    }elseif($status == 2){
        $data = unserialize($contents);
    }else{
        $data = explode(',',$string); 
    }
    
    fclose($file);
    return $data;

}

/*
    将数组以json格式写入文件
    @param char $string 文件路径
    @param array $data 获得的数据数组
    @param int $status 1为以json数据形式存储，0为以','分隔处理,2为序列化格式
 */
function WritePointFile($string,$data,$status){
    $file = fopen($string, "w") or die("Unable to open file!");
    if($status == 1){
        $data_json = json_encode($data);
        fwrite($file,$data_json);
    }elseif($status == 2){
        $data_serialize = serialize($data);
        fwrite($file,$data_serialize);
    }else{
        $string =implode(',',$data);
        fwrite($file,$string);
    }

    fclose($file);
    

}

/*
    文件上传函数
    @param char $keyname 文件上传的字段名
 */

function upload($keyname,$dirname=''){
    // 获取表单上传文件
    $files = request()->file($keyname);
    foreach($files as $file){
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->validate(['size'=>2145728,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public/uploads/'.$dirname);
        if($info){
            // 成功上传后 获取上传信息           
            $savename[] =  $info->getSaveName();
        }else{
            // 上传失败获取错误信息
             echo "<script>alert('".$file->getError()."');location.href=history.back();</script>";die;
        }    
    }

    $result = implode(',',$savename);
    return $result;

}

/*
    删除原图片
    @param array $data 所查询的图片数组
    @param char $key 所需要删除的图片的数组的键值名
 */

function DelPicture($data,$key,$dir = '.'){
    foreach ($data as $k => $value) {
        foreach ($value[$key] as $v) {
            $reg = unlink("./uploads/{$dir}/{$v}");
        }   
    }
}

/*
    get方式使用Curl
    @param char $url
 */
function MyCurlOfGet($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, true);
    return $data;
}

/*
    post方式使用Curl
    @param char $url 接口地址
    @param array $data post数据
 */

function MyCurlOfPost($url,$data){
     //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    //设置post数据
    $post_data = $data;
    $post_data = json_encode($post_data);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
    $result = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    $result = json_decode($result, true);
    //显示获得的数据
    // file_put_contents("./test.jpg", $result);
    // echo $result;die;
    return $result;
}
/*
    获取用户的真实IP
 */
function getIPaddress(){
    $IPaddress='';
    if(isset($_SERVER)){
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }elseif(isset($_SERVER["HTTP_CLIENT_IP"])){
            $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
        }else{
            $IPaddress = $_SERVER["REMOTE_ADDR"];
        }
    }else{
        if(getenv("HTTP_X_FORWARDED_FOR")){
            $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
        }elseif(getenv("HTTP_CLIENT_IP")) {
            $IPaddress = getenv("HTTP_CLIENT_IP");
        }else{
            $IPaddress = getenv("REMOTE_ADDR");
        }
    }
    return $IPaddress;
}

/*
    增长统计函数（根据时间）
    @param object $model 实例化模型
    @param string $year 年
    @param string $month 月
    @param string $key 时间值所在字段

 */

function GrewStatistics($model,$year,$month,$key){
    if($year && ($month != '0')){   //查某年某月
        //区分月份天数
        $bigmonth = array(1,3,5,7,8,10,12);
        $littlemonth = array(4,6,9,11);
        if(in_array($month,$bigmonth)){
            $i = 31;
        }elseif(in_array($month,$littlemonth)){
            $i = 30;
        }else{
            if(($year%100 == 0) && ($year%400 == 0)){
                $i = 29;
            }elseif($year%4 == 0){
                $i = 29;
            }else{
                $i = 28;
            }
        }
        for($j = 1;$j <= $i; $j++){ //获得日号数组
            $data['title'][$j] = "'{$j}日'";
        }
        //查询符合时间段的数据
        $MonthBegin = "{$year}-{$month}-01 00:00:00";
        $MonthEnd = "{$year}-{$month}-{$i} 23:59:59";
        $BeginTime = strtotime($MonthBegin);
        $EndTime = strtotime($MonthEnd);
        $result = $model -> field($key) -> where("{$key} >= {$BeginTime} AND {$key} <= {$EndTime}") -> select();
        for($j = 1;$j <= $i; $j++){
            $count = 0;
            foreach ($result as $k => $value) {
                $day = date("d",$value[$key]);
                if($day == $j){
                    $count++;
                    unset($result[$k]);
                }
            }
            $data['count'][$j] = $count;
        }
    }elseif($year){     //查某年
        for($i = 1;$i <=12; $i++){  //获得月份数组
            $data['title'][$i] = "'{$i}月'";
        }
        $YearBegin = "{$year}-01-01 00:00:00";
        $YearEnd = "{$year}-12-31 23:59:59";
        $BeginTime = strtotime($YearBegin);
        $EndTime = strtotime($YearEnd);
        $result = $model -> field($key) -> where("{$key} >= {$BeginTime} AND {$key} <= {$EndTime}") -> select();
        for($i = 1;$i <=12; $i++){
            $count = 0;
            foreach ($result as $k => $value) {
                $Month = date("m",$value[$key]);
                if($Month == $i){
                    $count++;
                    unset($result[$k]);
                }
            }
            $data['count'][$i] = $count;
        }
    }else{  //默认今年
        for($i = 1;$i <=12; $i++){  //获得月份数组
            $data['title'][$i] = "'{$i}月'";
        }
        $nowYear = date("Y-01-01 00:00:00",time());
        $nowYearTime = strtotime($nowYear);
        $result = $model -> field($key) -> where("{$key} >= {$nowYearTime}") -> select();
        for($i = 1;$i <=12; $i++){
            $count = 0;
            foreach ($result as $k => $value) {
                $Month = date("m",$value[$key]);
                if($Month == $i){
                    $count++;
                    unset($result[$k]);
                }
            }
            $data['count'][$i] = $count;
        }
    }
    return $data;
}

/*
    统计字段函数（根据时间）
    @param object $model 实例化模型
    @param string $year 年
    @param string $month 月
    @param string $key 时间值所在字段
    @param string $field 需统计字段

 */

function StatisticsMoney($model,$year='',$month='',$key,$field){
     if($year && ($month != '0')){  //查某年某月
        //区分月份天数
        $bigmonth = array(1,3,5,7,8,10,12);
        $littlemonth = array(4,6,9,11);
        if(in_array($month,$bigmonth)){
            $i = 31;
        }elseif(in_array($month,$littlemonth)){
            $i = 30;
        }else{
            if(($year%100 == 0) && ($year%400 == 0)){
                $i = 29;
            }elseif($year%4 == 0){
                $i = 29;
            }else{
                $i = 28;
            }
        }
        for($j = 1;$j <= $i; $j++){ //获得日号数组
            $data['title'][$j] = "'{$j}日'";
        }
        //查询符合时间段的数据
        $MonthBegin = "{$year}-{$month}-01 00:00:00";
        $MonthEnd = "{$year}-{$month}-{$i} 23:59:59";
        $BeginTime = strtotime($MonthBegin);
        $EndTime = strtotime($MonthEnd);
        $result = $model -> field("{$key},{$field}") -> where("{$key} >= {$BeginTime} AND {$key} <= {$EndTime}") -> select();
        for($j = 1;$j <= $i; $j++){
            $sum = 0;
            foreach ($result as $k => $value) {
                $day = date("d",$value[$key]);
                if($day == $j){
                    $sum = $sum + $value[$field];
                    unset($result[$k]);
                }
            }
            $data['sum'][$i] = $sum;
        }
    }elseif($year){     //查某年
        for($i = 1;$i <=12; $i++){  //获得月份数组
            $data['title'][$i] = "'{$i}月'";
        }
        $YearBegin = "{$year}-01-01 00:00:00";
        $YearEnd = "{$year}-12-31 23:59:59";
        $BeginTime = strtotime($YearBegin);
        $EndTime = strtotime($YearEnd);
        $result = $model -> field("{$key},{$field}") -> where("{$key} >= {$BeginTime} AND {$key} <= {$EndTime}") -> select();
        for($i = 1;$i <=12; $i++){
            $sum = 0;
            foreach ($result as $k => $value) {
                $Month = date("m",$value[$key]);
                if($Month == $i){
                    $sum = $sum + $value[$field];
                    unset($result[$k]);
                }
            }
            $data['sum'][$i] = $sum;
        }
    }else{  //默认今年
        for($i = 1;$i <=12; $i++){  //获得月份数组
            $data['title'][$i] = "'{$i}月'";
        }
        $nowYear = date("Y-01-01 00:00:00",time());
        $nowYearTime = strtotime($nowYear);
        $result = $model -> field("{$key},{$field}") -> where("{$key} >= {$nowYearTime}") -> select();
        for($i = 1;$i <=12; $i++){
            $sum = 0;
            foreach ($result as $k => $value) {
                $Month = date("m",$value[$key]);
                if($Month == $i){
                    $sum = $sum + $value[$field];
                    unset($result[$k]);
                }
            }
            $data['sum'][$i] = $sum;
        }
    }
    return $data;
}

/**
 * 导出excel
 * @param   obj $phpexcel phpExcel实例化对象
 * @param  [type] $data     [description]
 * @param  string $filename [description]
 * @return [type]           [description]
 */
function create_xls($phpexcel,$data,$filename='simple.xls')
{
    ini_set('max_execution_time', '0');
    $filename = str_replace('.xls', '', $filename).'.xls';

    $phpexcel->getProperties()
        ->setCreator("Maarten Balliauw")
        ->setLastModifiedBy("Maarten Balliauw")
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");
    $phpexcel->getActiveSheet()->fromArray($data);
    $phpexcel->getActiveSheet()->setTitle('Sheet1');
    $phpexcel->setActiveSheetIndex(0);
    $phpexcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(23);
    

    header('Content-Type: application/vnd.ms-excel');
    header("Content-Type:text/html;CharSet=UTF-8");
    header("Content-Disposition: attachment;filename={$filename}");
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0
    $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
    $objwriter->save('php://output');
    return true;
}

/*
    企业向用户付款
    @param array $data 支付相关信息
    @param int $money 金额
    @param string $openid 用户openid
 */
function CompanyPayToUser($data,$money,$openid,$ordersn){
    Vendor('WxPayPubHelper.WxPayPubHelper');

}

/*
    导入表格并取得函数为数组
    @param string $file Excel表格路径
 */
function ReadXls($file){
    vendor('PHPExcel');
    vendor('PHPExcel.IOFactory');
    vendor('PHPExcel.Reader.Excel2007');
    $objReader = PHPExcel_IOFactory::createReader('Excel5');/*Excel5 for 2003 excel2007 for 2007*/  
    $objPHPExcel = $objReader->load($file); //Excel 路径  
    $sheet = $objPHPExcel->getSheet(0);  
    $highestRow = $sheet->getHighestRow(); // 取得总行数  
    $highestColumn = $sheet->getHighestColumn(); // 取得总列数  
    $objWorksheet = $objPHPExcel->getActiveSheet();          
    $highestRow = $objWorksheet->getHighestRow();   // 取得总行数       
    $highestColumn = $objWorksheet->getHighestColumn();          
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数  
    $data = array();
    for ($row = 1;$row <= $highestRow;$row++)         {  
        //注意highestColumnIndex的列数索引从0开始  
        for ($col = 0;$col < $highestColumnIndex;$col++)            {  
            $data[$row][$col] =$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();  
        }  
         
    }
    return $data;
}

/*
    按照某字段排序
    @param array &$array 引用需排序数组
    @param string $field 所根据的字段
    @param boolean $desc true降序 false升序
 */
function sortArrByField(&$array, $field, $desc = false){
  $fieldArr = array();
  foreach ($array as $k => $v) {
    $fieldArr[$k] = $v[$field];
  }
  $sort = $desc == false ? SORT_ASC : SORT_DESC;
  array_multisort($fieldArr, $sort, $array);
}

/*
    结果集处理外部调用图片路径
    @param array $data 结果集
    @param string $field 处理的字段
 */
function ExternalPublicURL($data,$field,$dir = '.'){
    foreach ($data as $key => $value) {
        $data[$key][$field] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/'.$dir.'/'.$value[$field];
    }

    return $data;
}

//  我的二维码
function erweima($url,$id){
    // $uid = session("userinfo.uid");
    // $wechat = new \Org\Util\WeChat;
    // $scene_id = intval($uid) - 100000+1;
    // $erweima = $wechat->getQRCode($scene_id,$uid);
    // return $erweima;

    vendor("phpqrcode.phpqrcode"); 
    $object = new \QRcode();
    // $url='http://'.$_SERVER["HTTP_HOST"].U("Center/shareres", array("uid" => $uid));//网址或者是文本内容
    $url='http://'.$_SERVER["HTTP_HOST"].'/'.$url.'/shareuid/'.$id.".html";//网址或者是文本内容
    $level='L';
    $size=4;
    $errorCorrectionLevel =intval($level) ;//容错级别
    $matrixPointSize = intval($size);//生成图片大小
    $fileName = './Public/QRCode/'.time().mt_rand(1000,9999).'.png';
    $object->png($url, $fileName, $errorCorrectionLevel, $matrixPointSize);

    return $fileName;
}

/**
 *  @desc 将log存储到文件
 *  @param string $file 文件名
 *  @param mix $data 任意类型的数据
 */
function logFile($file,$data){
    if(!$file) return false;
    $file = "./Log/".$file.".html";
    $text = "<p>".date('Y-m-d H:i:s').":</p>".dump($data,false)."\r\n";
    if(file_get_contents($file) == false){
    
/* 为了输出的代码缩进正常，所以这里不缩进 */
$text = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> <title>Log</title><style type="text/css">body{padding-bottom:100vh;}</style>
<script type="text/javascript">
  window.onload = function(){
    var pp = document.getElementsByTagName("p");
    setTimeout(function(){
      document.getElementsByTagName("body")[0].scrollTop = pp[pp.length-1].offsetTop;
    },10);
  }
</script>'."\r\n".$text;
/* 为了输出的代码缩进正常，所以这里不缩进 */

    }
    file_put_contents($file,$text,FILE_APPEND);
    return true;
}


/*
    获取access_token
    @param array $data 小程序配置参数
 */
function GetAccessToken($data){
    if(cache('access_token_'.session('admin_id')) == false){
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$data['appid'].'&secret='.$data['secret'];
        $data = MyCurlOfGet($url);
        cache('access_token_'.session('admin_id'),$data['access_token'],7000);
    }
    
}

/*
    对查询结果进行整理，使得查询结果的二维数组变成以订单ID为单位的三维数组
    @param array $data 查询结果的二维数组
    @param array $array 详情键值数组
 */
function Tidy($data,$array){
    $Udata = array();
    foreach ($data as $key => $value) {
        foreach ($value as $k => $order) {
            //属于详情中的信息则丢到contents下，不然保持原维度
            if(in_array($k,$array)){
                $Udata[$value['id']]['contents'][$key][$k] = $order; 
            }else{
                $Udata[$value['id']][$k] = $order;
            }   
        }
    }
    return $Udata;
}

/**
 * 系统邮件发送函数
 * @param string $tomail 接收邮件者邮箱
 * @param string $name 接收邮件者名称
 * @param string $subject 邮件主题
 * @param string $body 邮件内容
 * @param string $attachment 附件列表
 * @return boolean
 * @author static7 <static7@qq.com>
 */
function send_mail($tomail, $name, $subject = '', $body = '', $attachment = null) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer();           //实例化PHPMailer对象
    $mail->CharSet = 'UTF-8';           //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();                    // 设定使用SMTP服务
    $mail->SMTPDebug = 0;               // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
    $mail->SMTPAuth = true;             // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';          // 使用安全协议
    $mail->Host = "smtp.qq.com"; // SMTP 服务器
    $mail->Port = 465;                  // SMTP服务器的端口号
    $mail->Username = '32241931@qq.com';    // SMTP服务器用户名
    $mail->Password = 'jgwkqdgokrpycaij';     // SMTP服务器密码
    $mail->SetFrom('32241931@qq.com', '极速上线');
    $replyEmail = '';                   //留空则为发件人EMAIL
    $replyName = '';                    //回复名称（留空则为发件人名称）
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($tomail, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}

/**
 * 检查商户权限
 * @param $value 检验值
 * @param $sheetName 表名
 * @param $fieldName 要检验的字段名（一个）
 * @param $id 要检验的记录id
 */
function CheckPermissions($value,$sheetName,$fieldName,$id){
    $data = db($sheetName) -> field($fieldName) -> find($id);
    if($value == $data[$fieldName]){
        return TRUE;
    }else{
        return FALSE;
    }
}


