<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\Site\controller;

use app\common\controller\Site;
use app\common\model\Logistics as LogisticsModel;
use Request;

class Logistics extends Site
{


    public function index()
    {
        if(Request::isAjax()) {
            $logModel = new LogisticsModel();
            return $logModel->tableData(input('request.'));
        }
        return $this->fetch();

    }



    public function add()
    {
        $this->view->engine->layout(false);
        if(Request::isPost())
        {
            $return_data = [
                'status' => false,
                'msg'    => '添加失败',
                'data'   => ''
            ];
            //存储添加内容
            $data = [
                'logi_name' => input('post.logi_name'),
                'logi_code' => input('post.logi_code'),
                'sort' => input('post.sort'),
            ];
            $logModel = new LogisticsModel();
            $result = $logModel->add($data);
            if($result !== false)
            {
                $return_data = [
                    'status' => true,
                    'msg'    => '添加成功',
                    'data'   => $result
                ];
            }
            return $return_data;
        }
        return $this->fetch('add');

    }



    public function edit()
    {
        $this->view->engine->layout(false);
        $logModel = new LogisticsModel();
        if(Request::isPost())
        {
            return $logModel->saveData(input('param.'));

        }
        $data = $logModel->getInfo(input('param.id/d'));
        $this->assign('data',$data);
        return $this->fetch('edit');
    }


    public function del()
    {
        $logModel = new LogisticsModel();
        return $logModel->del(input('post.id/d'));
    }


}