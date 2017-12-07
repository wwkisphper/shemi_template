<?php
namespace app\api\controller;

use think\Controller;

class Article extends Controller
{
	//文章列表
	public function ArticleList(){
        $adminId = input('post.admin_id');
        //检测传参
        if($adminId == 'undefined' || $adminId =='null' || $adminId == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

		$data = db('Article') -> field('id,title,create_time,reading_amount,number_praise,picture') -> where("admin_id={$adminId}") -> order('id DESC') -> select();

		foreach ($data as $key => $value) {
			$data[$key]['picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/article/'.$value['picture'];
		}

		$data = TimeConversions($data,'create_time');	//转换时间

		

		header('Content-Type:application/json; charset=utf-8');
        return json_encode($data);
	}

	//文章详情
	public function ArticleDetail(){
		$id = input('post.id');
		$uid = input('post.uid');
		if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少文章id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($uid == 'undefined' || $uid =='null' || $uid == ''){
        	$info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //文章点击数增加
        $success = db('Article') -> where('id',$id) -> setInc('reading_amount');
        $info['reading_add'] = $success;

        //获取文章信息
        $data = db('Article') -> field('id,title,create_time,reading_amount,number_praise,picture,content') -> find($id);
        $data['content'] = str_replace('/reservation','https://'.$_SERVER['HTTP_HOST'].'/reservation',$data['content']);
        $data['create_time'] = date('Y-m-d H:i:s',$data['create_time']);	//转换时间
      	$info['content'] = $data;

      	//获取文章评论
      	$commentData = db('Article_comment') -> field('c.id,c.content,c.create_time,u.nickname,c.reply_content,c.is_reply') -> alias('c') -> join('order_user u',"c.uid=u.id AND c.aid={$id}") -> order('c.id DESC') -> select();
      	$commentData = TimeConversions($commentData,'create_time');	//转换时间
      	$info['comment'] = $commentData;

      	//获取此文章是否被收藏
      	$check = db('Article_collection') -> where("aid={$data['id']} AND uid={$uid}") -> find();
      	if($check){
      		$info['is_collection'] = 1;
      	}else{
			$info['is_collection'] = 0;
      	}

      	$info['status'] = 1;

      	header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

	}

	//文章点赞数增加
	public function ArticlePraise(){
		$id = input('post.id');
		if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少文章id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        //文章点击数增加
        $success = db('Article') -> where('id',$id) -> setInc('number_praise');
        if($success){
        	$info['status'] = 1;
        	$info['msg'] = '点赞成功';
        }else{
        	$info['status'] = -2;
        	$info['msg'] = '点赞失败';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
	}

	//文章评论
	public function ArticleComment(){
		$data['aid'] = input('post.aid');
		$data['uid'] = input('post.uid');
		$data['content'] = input('post.content');
        $data['admin_id'] = input('post.admin_id');
		if($data['aid'] == 'undefined' || $data['aid'] =='null' || $data['aid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少文章id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['uid'] == 'undefined' || $data['uid'] =='null' || $data['uid'] == ''){
        	$info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['content'] == 'undefined' || $data['content'] =='null' || $data['content'] == ''){
        	$info['status'] = -1;
            $info['msg'] = '缺少评论内容';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['admin_id'] == 'undefined' || $data['admin_id'] =='null' || $data['admin_id'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少商户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data['create_time'] = time();
        $success = db('Article_comment') -> insert($data);
        if($success){
        	$info['status'] = 1;
            $info['msg'] = '评论成功';
        }else{
        	$info['status'] = -2;
            $info['msg'] = '评论失败';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
	}

	//收藏文章
	public function ArticleCollection(){
		$data['aid'] = input('post.aid');
		$data['uid'] = input('post.uid');

		if($data['aid'] == 'undefined' || $data['aid'] =='null' || $data['aid'] == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少文章id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }elseif($data['uid'] == 'undefined' || $data['uid'] =='null' || $data['uid'] == ''){
        	$info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $check = db('Article_collection') -> where("aid={$data['aid']} AND uid={$data['uid']}") -> find();
        if(!$check){
			$success = db('Article_collection') -> insert($data);
			if($success){
				$info['status'] = 1;
            	$info['msg'] = '收藏成功';
			}else{
				$info['status'] = -3;
            	$info['msg'] = '收藏失败';
			}
        }else{
        	$info['status'] = -2;
            $info['msg'] = '用户已收藏此文章';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);
	}

	//用户收藏列表
	public function UserCollection(){
		$id = input('post.id');
		if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少用户id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $data = db('Article_collection') -> field('c.id,a.id AS aid,a.title,a.create_time,a.reading_amount,a.number_praise,a.picture') -> alias('c') -> join('order_article a',"c.aid=a.id AND c.uid={$id}") -> order('c.id DESC') -> select();

        foreach ($data as $key => $value) {
			$data[$key]['picture'] = 'https://'.$_SERVER['HTTP_HOST'].'/reservation/public/uploads/article/'.$value['picture'];
		}

		$data = TimeConversions($data,'create_time');	//转换时间

		header('Content-Type:application/json; charset=utf-8');
		$info['content'] = $data;
		$info['status'] = 1;
        return json_encode($info);
	}

	//取消文章收藏
	public function DelCollection(){
		$id = input('post.id');
		if($id == 'undefined' || $id =='null' || $id == ''){
            $info['status'] = -1;
            $info['msg'] = '缺少收藏id';
            header('Content-Type:application/json; charset=utf-8');
            return json_encode($info);
        }

        $success = db('Article_collection') -> delete($id);
        if($success){
        	$info['status'] = 1;
            $info['msg'] = '删除成功';
        }else{
        	$info['status'] = -2;
            $info['msg'] = '删除失败';
        }

        header('Content-Type:application/json; charset=utf-8');
        return json_encode($info);

	}

}