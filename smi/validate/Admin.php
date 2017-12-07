<?php

namespace app\Smi\validate;

use think\Validate;

class Admin extends Validate
{
    protected $rule = [
        'username'  =>  'unique:admin',
        
    ];

    protected $message = [
        'username.unique' => '帐号名已存在',
    ];

    protected $scene = [
        'admin' => ['username'],
    ];
}