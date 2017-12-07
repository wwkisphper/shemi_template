<?php
namespace app\smi\controller;

class Show extends Base
{	
	//展示列表
	public function ShowList(){
		$adminId = session('admin_id');
		$classifyData = db('Show_classify') -> field('id,classify_name,status') -> where("admin_id={$adminId}") -> select();
		if($classifyData){
			$classifyIdString = model('ShowClassify') -> getAdminClassifyId($classifyData);	//获取展示分类id
			$showData = db('Show') -> field('id AS sid,sc_id,picture') -> where("sc_id IN ({$classifyIdString})") -> select();
			$data = model('ShowClassify') -> TidyShowData($classifyData,$showData);	//获得处理好的展示列表
		}else{
			$data = array();
		}

		$this -> assign('data',$data);
		return $this -> fetch('show_list');
	}

	//添加分类
	public function AddClassify(){
		$data['admin_id'] = session('admin_id');
		$data['classify_name'] = input('post.classify_name');

		$success = db('Show_classify') -> insert($data);

		if($success){
            $this -> success('添加成功','Show/ShowList');
        }else{
            $this -> error('添加失败');
        }
	}

	//修改分类名称
	public function AlterClassify(){

		$id = input('id');
		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Show_classify','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		if($this-> request -> isPost()){
			$data['id'] = $id;
			$data['classify_name'] = input('post.classify_name');

			$success = db('Show_classify') -> update($data);

			if($success){
				$this -> success('修改成功','Show/ShowList');
			}else{
				$this -> error('修改失败');
			}
		}else{

			$data = db('Show_classify') -> field('classify_name') -> where('id',$id)->find();

			$this -> assign('title','修改分类名称');
			$this -> assign('data',$data);
			$this -> assign('id',$id);

			return $this -> fetch('show_classify_alter');
		}
	}

	//修改分类状态
	public function AlterClassifyStatus(){
		$data['id'] = input('get.id');

		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Show_classify','admin_id',$data['id']);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		$data['status'] = input('get.status');

		$success = db('Show_classify') -> update($data);
		if($success){
			$this -> success('修改成功','Show/ShowList');
		}else{
			$this -> error('修改失败');
		}
	}

	//删除分类
	public function DelClassify(){
		$id = input('get.id');

		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Show_classify','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		$picData = db('Show') -> where("sc_id",$id) -> column('picture');
		if($picData){
			$success = db('Show') -> where("sc_id",$id) -> delete();
			if($success){
				$success = db('Show_classify') -> delete($id);
				if($success){
					foreach ($picData as $value) {
						unlink('./uploads/show/'.$value);
					}
					$this -> success('删除成功','Show/ShowList');
				}else{
					$this -> error('删除分类失败');
				}
			}else{
				$this -> error('删除图片失败');
			}
		}else{
			$success = db('Show_classify') -> delete($id);
			if($success){
				$this -> success('删除成功','Show/ShowList');
			}else{
				$this -> error('删除失败');
			}
		}
	}

	//添加图片
	public function AddPicture(){
		$classifyId = input('post.sc_id');
		$picture = upload('picture','show');
		$pictureArray = explode(',',$picture);

		$data = array();
		foreach ($pictureArray as $key => $value) {
			$data[$key]['sc_id'] = $classifyId;
			$data[$key]['picture'] = $value;
		}

		$success = db('Show') -> insertAll($data);

		if($success){
			$this -> success('添加成功','Show/ShowList');
		}else{
			$this -> error('添加失败');
		}
	}

	//删除图片
	public function DelClassifyPicture(){
		$id = input('get.id');
		$picData = db('Show') -> field('picture,sc_id') -> find($id);

		$adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Show_classify','admin_id',$picData['sc_id']);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

		if(unlink('./uploads/show/'.$picData['picture'])){
			$success = db('Show') -> delete($id);
			if($success){
				$this -> success('删除成功','Show/ShowList');
			}else{
				$this -> error('删除失败');
			}
		}else{
			 $this -> error('删除原图片失败');
		}
		
	}
}