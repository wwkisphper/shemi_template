<?php
namespace app\smi\controller;

use think\Controller;

header("Content-Type:text/html;CharSet=utf-8");
class Base extends Controller
{
    public function __construct(){
        parent::__construct();
        if (!session('?admin')) {
            $last_viset = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            cookie('last_viset', $last_viset, 7200);
            $this -> redirect('Login/login');die;
        }
        $this -> assign("admin", session("admin"));
    }

    public  $auth;
    protected function _initialize()
    {
        parent::_initialize();


    }
}