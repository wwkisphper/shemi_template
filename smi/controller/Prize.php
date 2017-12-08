<?php
namespace app\smi\controller;

class Prize extends Base
{
	//奖品列表
	public function PrizeList(){
		$adminId = session('admin_id');
		$data = db('Prize') -> field('id,name,picture,stock,probability') -> where("admin_id={$adminId}") -> select();

		$this -> assign('data',$data);

		return $this -> fetch('prize_list');
	}

	//添加奖品
	public function AddPrize(){

		if($this-> request -> isPost()){
			$adminId = session('admin_id');
			$prizeCount = db('Prize') -> where("admin_id={$adminId}") -> count();
			if($prizeCount >= 8){
				$this -> error('奖品数量不得超过8个');
				die;
			}

			$data['admin_id'] = $adminId;
			$data['name'] = input('post.name');
			$data['picture'] = upload('picture','prize');
			$data['stock'] = input('post.stock');
			$data['probability'] = input('post.probability');

			$success = db('Prize') -> insert($data);
			if($success){
				$this -> success('添加成功','Prize/PrizeList');
			}else{
				$this -> error('添加失败');
			}
		}else{
			return $this -> fetch('add_prize');
		}
	}

	//修改奖品
	public function AlterPrize(){
		$adminId = session('admin_id');
		$id = input('id');
		$check = CheckPermissions($adminId,'Prize','admin_id',$id);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

        if($this-> request -> isPost()){
        	$data['id'] = $id;
        	$data['name'] = input('post.name');
        	$data['stock'] = input('post.stock');
        	$data['probability'] = input('post.probability');
        	if($_FILES['picture']['name'][0]){
                $data['picture'] = upload('picture','prize');
            }

            $success  = db('Prize') -> update($data);
            if($success){
            	$this -> success('修改成功','Prize/PrizeList');
            }else{
            	$this -> error('修改失败或内容无变化');
            }

        }else{
        	$data = db('Prize') -> field('id,name,picture,stock,probability') -> find($id);

        	$this -> assign('data',$data);
        	return $this -> fetch('alter_prize');
        }
	}

	//删除奖品
	public function DelPrize(){
		$adminId = session('admin_id');
		$id = input('get.id');
		$check = CheckPermissions($adminId,'Prize','admin_id',$id);	//检查商户是否有权限操作该记录
        if(!$check){
        	die;
        }

        $success = db('Prize') -> delete($id);
        if($success){
        	$this -> success('删除成功','Prize/PrizeList');
        }else{
        	$this -> error('删除失败');
        }
	}

	//中奖记录
	public function AdminPumpingRecord(){
		$adminId = session('admin_id');
		$keyword = input('get.keyword');

		$list = db('Winning_record')
		-> field('w.name,w.picture,w.status,w.create_time,u.nickname')
		-> alias('w')
		-> join('order_user u',"u.id=w.uid AND w.admin_id={$adminId}")
		-> where('u.nickname','like',"{$keyword}%")
		-> order('id DESC')
		-> paginate(10,false,['query' => array('keyword' => $keyword)]);

		$data = $list -> all();
		$page = $list -> render();

		$this -> assign('data',$data);
		$this -> assign('page',$page);
		$this -> assign('keyword',$keyword);

		return $this -> fetch('pumping_record');
	}
}