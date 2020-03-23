<?php
namespace app\Mall\controller;

use app\common\controller\Mall;

use app\common\model\MallRole;
use app\common\model\MallRoleOperationRel;

use Request;


class Role extends Mall
{
    public function index()
    {
        if(Request::isAjax()){
            $manageRoleModel = new MallRole();
            $data = input('param.');
            return $manageRoleModel->tableData($data);
        }else{
            return $this->fetch('index');
        }
    }

    public function add()
    {
        $this->view->engine->layout(false);
        $manageRoleModel = new MallRole();
        if(Request::isPost()){
            if(!input('?param.name')){
                return error_code(11070);
            }

            $data['name'] = input('param.name');
            $manageRoleModel->save($data);
            return [
                'status' => true,
                'data' => '',
                'msg' => '添加成功'
            ];

        }
        return $this->fetch('edit');
    }
    public function del()
    {
        if(!input('?param.id')){
            return error_code(10000);
        }

        $MallRoleModel = new MallRole();
        return $MallRoleModel->toDel(input('param.id'));

    }
    public function getOperation()
    {
        if(!input('?param.id')){
            return error_code(10000);
        }
        $this->view->engine->layout(false);

        $manageRoleModel = new MallRole();

        $re = $manageRoleModel->getRoleOperation(input('param.id/d'));
        if(!$re['status']){
            return $re;
        }

        $this->assign('data',json_encode($re['data']));
        $re['data'] = $this->fetch('getoperation');
        return $re;

    }
    public function savePerm(){
        $post = input('param.');

        if(!isset($post['id'])){
            return error_code(10000);
        }
        if(!isset($post['data'])){
            return error_code(10000);
        }
        //保存角色信息
        $manageRoleModel = new MallRole();
        $manageRoleInfo = $manageRoleModel->where(['id'=>$post['id']])->find();
        if(!$manageRoleInfo){
            return error_code(11071);
        }
        $mrorModel = new MallRoleOperationRel();

        $mrorModel->savePerm($post['id'],$post['data']);
        return [
            'status' => true,
            'data' => '',
            'msg' => '设置成功'
        ];
    }
}