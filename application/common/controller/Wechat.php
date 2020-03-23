<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\common\controller;

class Wechat extends Base
{
    public function index()
    {
        header("Content-type: text/html; charset=utf-8");
        echo '欢迎您~';exit();
    }
}