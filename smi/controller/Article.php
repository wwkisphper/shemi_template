<?php
namespace app\smi\controller;
use think\Db;

class Article extends Base
{
	//文章列表
	public function ArticleList()
	{	
		$keyword = input('get.keyword');
		$adminId = session('admin_id');
		if($keyword){
			$data = db('Article') -> field('id,title,create_time,picture,reading_amount,number_praise') -> where("title LIKE '{$keyword}%' AND admin_id={$adminId}") ->order("id DESC") -> select();
		}else{
			$data = db('Article') -> field('id,title,create_time,picture,reading_amount,number_praise') -> where("admin_id={$adminId}") -> order("id DESC") -> select();
		}
		

		$data = TimeConversions($data,'create_time');   //处理时间戳

		$this -> assign('keyword',$keyword);
        $this -> assign('data',$data);

        return $this -> fetch('article_list');
	}

	//添加文章
	public function AddArticle(){
		if($this-> request -> isPost()){
			$data['title'] = input('post.title');
			$data['create_time'] = time();
			$data['content'] = input('post.content');
			$data['picture'] = upload('picture','article');
			$data['admin_id'] = session('admin_id');
			$success = db('Article') -> insert($data);
			if($success){
				$this -> success('添加成功','Article/ArticleList');
			}else{
				$this -> error('添加失败');
			}
		}else{
			$this -> assign('title','添加文章');
			return $this -> fetch('add_article');
		}
	}

	//修改文章
	public function AlterArticle(){

		$id = input('id');
		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Article','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		if($this-> request -> isPost()){
			$data['id'] = $id;
			$data['title'] = input('post.title');
			$data['content'] = input('post.content');
			$data['reading_amount'] = input('post.reading_amount');
			$data['number_praise'] = input('post.number_praise');
			if($_FILES['picture']['name'][0]){
				$picture = db('Article') -> where("id",$data['id']) -> value('picture');
				$data['picture'] = upload('picture','article');
			}

			$success = db('Article') -> update($data);
			if($success){
				unlink('./uploads/article/'.$picture);
				$this -> success('修改成功','Article/ArticleList');
			}else{
				$this -> error('修改失败');
			}
			
		}else{
			$data = db('Article') -> field('id,title,reading_amount,number_praise,content,picture') -> find($id);

			$this -> assign('title','修改文章');
			$this -> assign('data',$data);
			return $this -> fetch('alter_article');
		}
	}

	//删除文章
	public function DelArticle(){
		$id = input('get.id');

		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Article','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		$picture = db('Article') -> where("id",$id) -> value('picture');
		Db::startTrans();
		$success = db('Article') -> delete($id);
		if(!$success){
			Db::rollback();
    		$this -> error('删除文章失败');
		}

		$success = db('Article_collection') -> where("aid",$id) -> delete();
		if($success === false){
			Db::rollback();
    		$this -> error('删除该文章用户收藏失败');
		}

		$success = db('Article_comment') -> where("aid",$id) -> delete();
		if($success === false){
			Db::rollback();
    		$this -> error('删除该文章评论失败');
		}

		unlink('./uploads/article/'.$picture);
		Db::commit();
		$this -> success('删除成功','Article/ArticleList');
	}

	//评论列表
	public function CommentList(){
		$adminId = session('admin_id');
		$keyword = input('get.keyword');
		if($keyword){
			$list = db('Article_comment') 
			-> field('c.id,c.content,c.create_time,c.reply_content,c.is_reply,a.title') 
			-> alias('c') 
			-> join('order_article a',"c.aid=a.id AND a.title LIKE '{$keyword}%' AND c.admin_id={$adminId}") 
			-> order('c.id DESC') 
			-> paginate(50,false,['query' => array('keyword' => $keyword)]);
		}else{
			$list = db('Article_comment') 
			-> field('c.id,c.content,c.create_time,c.reply_content,c.is_reply,a.title') 
			-> alias('c') 
			-> join('order_article a',"c.aid=a.id AND c.admin_id={$adminId}") 
			-> order('c.id DESC') 
			-> paginate(50);
		}
		

		$data = $list -> all();
		$page = $list -> render();

		$data = TimeConversions($data,'create_time');   //处理时间戳

		$this -> assign('keyword',$keyword);
		$this -> assign('title','评论列表');
		$this -> assign('data',$data);
		$this -> assign('page',$page);

		return $this -> fetch('article_comment_list');
	}

	//回复评论
	public function ReplyComment(){

		$id = input('id');
		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Article_comment','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		if($this-> request -> isPost()){
			$data['id'] = $id;
			$data['reply_content'] = input('post.reply_content');
			$data['is_reply'] = input('post.is_reply');

			$success = db('Article_comment') -> update($data);
			if($success){
				$this -> success('回复成功','Article/CommentList');
			}else{
				$this -> error('回复失败或内容无修改');
			}
		}else{

			$data = db('Article_comment') -> field('id,reply_content,is_reply') -> find($id);

			$this -> assign('title','回复评论');
			$this -> assign('data',$data);

			return $this -> fetch('reply_comment');
		}
	}

	//删除评论
	public function CommentDel(){
		$id = input('get.id');
		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Article_comment','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }
		$success = db('Article_comment') -> delete($id);
		if($success){
			$this -> success('删除成功','Article/CommentList');
		}else{
			$this -> error('删除失败');
		}
	}

}