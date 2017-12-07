<?php
namespace app\API\model;

use think\Model;

class Serve extends Model
{
    /*
        给预约时间表添加一些信息，如几月几日等等
        @param array $data 可预约时间结果集
     */
    public function AddSomeOrderTimeInfo($data){
        $i = 0;
        foreach ($data as $key => $value) {
            $data[$key]['date'] = date('m月d日',strtotime($value['key']));
            if($i == 0){
                $data[$key]['daymsg'] = '今天';
                $i++;
            }elseif($i == 1){
                $data[$key]['daymsg'] = '明天';
                $i++;
            }elseif($i == 2){
                $data[$key]['daymsg'] = '后天';
                $i++;
            }else{
                switch ($value['key']) {
                    case 'Monday':
                        $data[$key]['daymsg'] = '周一';
                        break;
                    case 'Tuesday':
                        $data[$key]['daymsg'] = '周二';
                        break;
                    case 'Wednesday':
                        $data[$key]['daymsg'] = '周三';
                        break;
                    case 'Thursday':
                        $data[$key]['daymsg'] = '周四';
                        break;
                    case 'Friday':
                        $data[$key]['daymsg'] = '周五';
                        break;
                    case 'Saturday':
                        $data[$key]['daymsg'] = '周六';
                        break;
                    case 'Sunday':
                        $data[$key]['daymsg'] = '周日';
                        break;
                    
                    default:
                        $data[$key]['daymsg'] = '加载出错';
                        break;
                }
            }
        }
        return $data;
    }
}