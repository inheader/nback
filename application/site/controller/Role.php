<?php
namespace app\Site\controller;

use app\common\controller\Site;

use app\common\model\SiteRole;
use app\common\model\SiteRoleOperationRel;

use Request;


class Role extends Site
{
    public function index()
    {
        if(Request::isAjax()){
            $manageRoleModel = new SiteRole();
            $data = input('param.');
            return $manageRoleModel->tableData($data);
        }else{
            return $this->fetch('index');
        }
    }

    public function add()
    {
        $this->view->engine->layout(false);
        $manageRoleModel = new SiteRole();
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

        $SiteRoleModel = new SiteRole();
        return $SiteRoleModel->toDel(input('param.id'));

    }
    public function getOperation()
    {
        if(!input('?param.id')){
            return error_code(10000);
        }
        $this->view->engine->layout(false);

        $manageRoleModel = new SiteRole();

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
        $manageRoleModel = new SiteRole();
        $manageRoleInfo = $manageRoleModel->where(['id'=>$post['id']])->find();
        if(!$manageRoleInfo){
            return error_code(11071);
        }
        $mrorModel = new SiteRoleOperationRel();

        $mrorModel->savePerm($post['id'],$post['data']);
        return [
            'status' => true,
            'data' => '',
            'msg' => '设置成功'
        ];
    }
}