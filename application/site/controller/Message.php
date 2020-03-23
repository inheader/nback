<?php

/**
 * 平台消息管理
 */

namespace app\site\controller;

use app\common\controller\Site as SiteController;
use app\common\model\SiteRole;
use app\common\model\MessageCenter;
use app\common\model\Operation;
use app\common\model\User;
use app\common\model\Message as MessageModel;
use app\common\model\SiteRoleRel;
use Request;


class Message extends SiteController
{
    public function index()
    {
        if(Request::isAjax()){
            $messageModel = new MessageModel();
            return $messageModel->tableData(input('param.'));
        }else{
            $messageCenterModel = new MessageCenter();
            $this->assign('tpl',$messageCenterModel->tpl);
            return $this->fetch('index');
        }
    }
    public function del()
    {
        if(!input('?param.id')){
            return error_code(10003);
        }
        $id = input('param.id');
        $messageModel = new MessageModel();
        if($messageModel->where(['id'=>$id])->delete()){
            return [
                'status' => true,
                'data' => '',
                'msg' => '删除成功'
            ];
        }else{
            return [
                'status' => false,
                'data' => '',
                'msg' => '删除失败'
            ];
        }
    }

}