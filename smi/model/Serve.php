<?php
namespace app\smi\model;

use think\Model;

class Serve extends Model
{
    /*
        删除原图片
        @param int $id 服务id
     */
    public function DelServeOldPicture($id){
        $picture = db('Serve') -> where('id',$id)->value('picture_description');
        if($picture){
        	unlink('./uploads/serve/'.$picture);
        }

        return 0;
    }

    /*
        删除原图片
        @param int $id 服务id
     */
    public function DelServeSlidePicture($id){
        $picture = db('Serve') -> where('id',$id)->value('picture');
        $picture = explode(',',$picture);
        foreach ($picture as $key => $value) {
            if($key && $value){
                unlink('./uploads/serve/'.$value);
            }
        }

        return 0;
    }
}