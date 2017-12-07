<?php
namespace app\smi\model;

use think\Model;

class Shop extends Model
{
	/*
        删除原图片
        @param int $id 服务id
     */
    public function DelGoodsPicture($id){
        $picture = db('Shop_goods') -> where('id',$id)->value('picture');
        $picture = explode(',',$picture);
        foreach ($picture as $key => $value) {
            if($key && $value){
                unlink('./uploads/goods/'.$value);
            }
        }

        return 0;
    }
}