<?php
namespace app\smi\controller;


class Serve extends Base
{
    //分类列表
    public function ClassifyList(){
        $adminId = session('admin_id');
        $data = db('classify') -> field('id,name,logo') -> where("admin_id={$adminId}") -> select();

        $this -> assign('data',$data);

        return $this -> fetch('classify_list');
    }

    //添加分类
    public function AddClassify(){
        
        $data['name'] = input('post.name');
        $data['logo'] = upload('logo','serve');
        $data['admin_id'] = session('admin_id');
        
        $success = db('Classify') -> insert($data);
        if($success){
            $this -> success('添加成功','Serve/ClassifyList');
        }else{
            $this -> error('添加失败');
        }
    }

    //删除分类
    public function DelClassify(){
        
        $id = input('get.id');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Classify','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $PicUrl = db('Classify') -> where('id',$id)->value('logo');
        if(unlink('./uploads/serve/'.$PicUrl)){
            $success = db('Classify') -> delete($id);
            if($success){
                $this -> success('删除成功','Serve/ClassifyList');
            }else{
                $this -> error('删除失败');
            }
        }else{
            $this -> error('删除原图片失败');
        }
    }

    //工作人员列表
    public function WorkerList(){
        $adminId = session('admin_id');
        $data  = db('Worker') -> field('id,name,picture,introduce') -> where("admin_id={$adminId}") -> select();

        foreach ($data as $key => $value) {
            $data[$key]['introduce'] = nl2br($value['introduce']);
        }

        $this -> assign('data',$data);

        return $this -> fetch('worker_list');
    }

    //添加工作人员
    public function AddWorker(){
        $data['name'] = input('post.name');
        $data['introduce'] = input('post.introduce');
        $data['picture'] = upload('picture','serve');
        $data['admin_id'] = session('admin_id');

        $success = db('Worker') -> insert($data);
        if($success){
            $this -> success('添加成功','Serve/WorkerList');
        }else{
            $this -> error('添加失败');
        }
    }

    //删除工作人员
    public function DelWorker(){
        
        $id = input('get.id');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Worker','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $PicUrl = db('Worker') -> where('id',$id) -> value('picture');
        if(unlink('./uploads/serve/'.$PicUrl)){
            $success = db('Worker') -> delete($id);
            if($success){
                $this -> success('删除成功','Serve/WorkerList');
            }else{
                $this -> error('删除失败');
            }
        }else{
            $this -> error('删除原图片失败');
        }
    }

    //服务列表
    public function ServeList(){
        $adminId = session('admin_id');

        $data = db('Serve') -> field('id,serve_name,price,picture,status') -> where("admin_id={$adminId}") -> order('status DESC') -> select();

        //获得第一张图片
        foreach ($data as $key => $value) {
            $value['picture'] = explode(',',$value['picture']);
            $data[$key]['picture'] = $value['picture'][0];
        }

        $this -> assign('data',$data);

        return $this -> fetch('serve_list');
    }

    //添加服务
    public function AddServe(){
        $adminId = session('admin_id');

        if($this-> request -> isPost()){
            //获取服务信息
            $data['serve_name'] = input('post.serve_name');
            $data['price'] = input('post.price');
            $data['admin_id'] = $adminId;
            if(input('post.woker/a')){
                $data['able_worker'] = implode(',',input('post.woker/a'));
            }else{
                $data['able_worker'] = '';
            }
            
            $data['classify'] = input('post.classify');
            $data['picture'] = upload('picture','serve');
            $data['picture_description'] = upload('picture_description','serve');
            $data['description'] = input('post.description');

            //获取预约日期
            $orderTime['Monday'] = input('post.Monday/a')?input('post.Monday/a'):array();
            $orderTime['Tuesday'] = input('post.Tuesday/a')?input('post.Tuesday/a'):array();
            $orderTime['Wednesday'] = input('post.Wednesday/a')?input('post.Wednesday/a'):array();
            $orderTime['Thursday'] = input('post.Thursday/a')?input('post.Thursday/a'):array();
            $orderTime['Friday'] = input('post.Friday/a')?input('post.Friday/a'):array();
            $orderTime['Saturday'] = input('post.Saturday/a')?input('post.Saturday/a'):array();
            $orderTime['Sunday'] = input('post.Sunday/a')?input('post.Sunday/a'):array();
            $data['order_time'] = serialize($orderTime);

            $success = db('Serve') -> insert($data);
            if($success){
                $this -> success('添加成功','Serve/ServeList');
            }else{
                $this -> error('添加失败');
            }


        }else{
            $wokerData = db('Worker') -> field('id,name') -> where("admin_id={$adminId}") -> select();
            $classifyData = db('Classify') -> field('id,name') -> where("admin_id={$adminId}") -> select();

            $this -> assign('woker',$wokerData);
            $this -> assign('classify',$classifyData);

            return $this -> fetch('add_serve');
        }
    }

    //更新服务上下架状态
    public function SetServeStatus(){

        $data['id'] = input('post.id');
        $data['status'] = input('post.status');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Serve','admin_id',$data['id']);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $success = db('Serve') -> update($data);
        if($success !== false){
            return 1;
        }else{
            return -1;
        }
    }

    //修改服务
    public function AlterServe(){

        $id = input('id');
        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Serve','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }
        
        if($this-> request -> isPost()){
            $data['id'] = $id;
            $data['serve_name'] = input('post.serve_name');
            $data['price'] = input('post.price');
            if(input('post.woker/a')){
                $data['able_worker'] = implode(',',input('post.woker/a'));
            }else{
                $data['able_worker'] = '';
            }
            
            $data['description'] = input('post.description');
            $data['classify'] = input('post.classify');

            //获取预约日期
            $orderTime['Monday'] = input('post.Monday/a')?input('post.Monday/a'):array();
            $orderTime['Tuesday'] = input('post.Tuesday/a')?input('post.Tuesday/a'):array();
            $orderTime['Wednesday'] = input('post.Wednesday/a')?input('post.Wednesday/a'):array();
            $orderTime['Thursday'] = input('post.Thursday/a')?input('post.Thursday/a'):array();
            $orderTime['Friday'] = input('post.Friday/a')?input('post.Friday/a'):array();
            $orderTime['Saturday'] = input('post.Saturday/a')?input('post.Saturday/a'):array();
            $orderTime['Sunday'] = input('post.Sunday/a')?input('post.Sunday/a'):array();
            $data['order_time'] = serialize($orderTime);

            if($_FILES['picture']['name'][0]){
                $data['picture'] = upload('picture','serve');
                //删除原图片
                 model('Serve') -> DelServeSlidePicture($data['id']);
            }

            if($_FILES['picture_description']['name'][0]){
            	$data['picture_description'] = upload('picture_description','serve');
            	//删除原图片
                 model('Serve') -> DelServeOldPicture($data['id']);
            }

            $success = db('Serve') -> update($data);
            if($success){
                $this -> success('修改成功','Serve/ServeList');
            }else{
                $this -> error('修改失败或信息无变化');
            }
            

        }else{
            //获得服务数据
            $data = db('Serve') -> field('serve_name,picture,order_time,price,able_worker,classify,description') -> where('id',$id) -> find();
            $data['picture'] = explode(',',$data['picture']);
            $data['order_time'] = unserialize($data['order_time']);
            $data['able_worker'] = explode(',',$data['able_worker']);

            //获得分类数据
             $classifyData = db('Classify') -> field('id,name') -> where("admin_id={$adminId}") -> select();

             //获得工作人员数据
             $wokerData = db('Worker') -> field('id,name') -> where("admin_id={$adminId}") -> select();

             //渲染数据
             $this -> assign('woker',$wokerData);
             $this -> assign('classify',$classifyData);
             $this -> assign('data',$data);
             $this -> assign('id',$id);

             return $this -> fetch('alter_serve');
        }
    }

    //首页轮播图列表
    public function SlideList(){
        $adminId = session('admin_id');
        $data = db('Slide') -> field('s.id,s.picture,serve.serve_name') -> alias('s') -> join('order_serve serve',"s.sid=serve.id AND s.admin_id={$adminId}") ->select();

        $serveData = db('Serve') -> field('id,serve_name') -> where("status = 1 AND admin_id={$adminId}") -> select();

        $this -> assign('data',$data);
        $this -> assign('serve',$serveData);

        return $this -> fetch('slide_list');
    }

    //添加轮播图
    public function AddSlide(){
        
        $data['sid'] = input('post.sid');
        $data['picture'] = upload('picture','serve');
        $data['admin_id'] = session('admin_id');

        $success = db('Slide') -> insert($data);
        if($success){
            $this -> success('添加成功','Serve/SlideList');
        }else{
            $this -> error('添加失败');
        }
    }

    //删除轮播图
    public function DelSlide(){
        
        $id = input('get.id');

        $adminId = session('admin_id');
        $check = CheckPermissions($adminId,'Slide','admin_id',$id);  //检查商户是否有权限操作该记录
        if(!$check){
            die;
        }

        $PicUrl = db('Slide') -> where('id',$id)->value('picture');
        if(unlink('./uploads/serve/'.$PicUrl)){
            $success = db('Slide') -> delete($id);
            if($success){
                $this -> success('删除成功','Serve/SlideList');
            }else{
                $this -> error('删除失败');
            }
        }else{
            $this -> error('删除原图片失败');
        }
    }
}
