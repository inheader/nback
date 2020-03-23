<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\site\controller;

use app\common\controller\Site as SiteController;
use app\common\model\SiteRole;
use app\common\model\SiteManage;
use app\common\model\SiteRoleRel;
use Request;
use org\Curl;


class Manage extends SiteController
{

    public function index()
    {
        if(Request::isAjax()){
            $manageModel = new SiteManage();
            return $manageModel->tableData([]);
        }else{
            return $this->fetch('index');
        }
    }

    public function add()
    {
        $this->view->engine->layout(false);
        $manageModel = new SiteManage();
        $manageRoleModel = new SiteRole();
        $manageRoleList = $manageRoleModel->select();

        if(Request::isPost()){
            if(!input('?param.username') || input('param.username') == ""){
                return error_code(11008);
            }
            if(!input('?param.mobile') || input('param.mobile') == ""){
                return error_code(11080);
            }
            if(!input('?param.password') || strlen(input('param.password')) < 6 || strlen(input('param.password')) > 12){
                return error_code(11009);
            }
            return $manageModel->toAdd(input('param.'));
        }
        $this->assign('roleList',$manageRoleList);
        return $this->fetch('edit');
    }
    public function edit()
    {
        $this->view->engine->layout(false);

        if(!input('?param.id')){
            return error_code(10000);
        }

        $manageModel = new SiteManage();
        if(input('param.id') == $manageModel::TYPE_SUPER_ID){
            return "超级管理员，就不要编辑了吧？";
        }
        $manageInfo = $manageModel->where(['id'=>input('param.id')])->find();
        if(!$manageInfo){
            return error_code(11004);
        }


        if(Request::isPost()){
            return $manageModel->toAdd(input('param.'));
        }

        $manageRoleModel = new SiteRole();
        $manageRoleList = $manageRoleModel->select();
        $manageRoleRelModel = new SiteRoleRel();
        $smList = $manageRoleRelModel->where(['manage_id'=>input('param.id')])->select();
        foreach($manageRoleList as $k => $v){
            $checked = false;
            foreach($smList as $i => $j){
                if($j['role_id'] == $v['id']){
                    $checked = true;
                    break;
                }
            }
            $manageRoleList[$k]['checked'] = $checked;
        }
        $this->assign('roleList',$manageRoleList);
        $this->assign('manageInfo',$manageInfo);
        return $this->fetch('edit');
    }
    public function del()
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        if(!input('?param.id')){
            return error_code(10000);
        }

        $manageModel = new manageModel();

        if(input('param.id') == $manageModel::TYPE_SUPER_ID){
            $result['msg'] = "超级管理员，就不要删除了把？";
            return $result;
        }

        $where['id'] = input('param.id');
        $re = $manageModel->where($where)->delete();
        if($re){
            $result['status'] = true;
            $result['msg'] = '删除成功';
        }else{
            $result['msg'] = '删除失败，请重试';
        }


        return $result;

    }

    /**
     * 获取用户资料信息
     * @return mixed
     */
    public function information()
    {

        $manageModel = new SiteManage();
        $manageInfo = $manageModel->where(['id'=>session('site.id')])->find();

        $this->assign('manage_info',$manageInfo);
        return $this->fetch();
    }


    /**
     * 用户修改/找回密码
     * @return array
     */
    public function editPwd()
    {
        $result = [
            'status' => false,
            'data' => '',
            'msg' => ''
        ];
        $manageModel = new SiteManage();

        if(!input('?param.newPwd') || !input('?param.password') || !input('?param.rePwd') ){
            $result['msg'] = "密码不能为空";
            return $result;
        }
        if(input('param.newPwd') != input('param.rePwd')){
            $result['msg'] = "两次密码不一致";
            return $result;
        }


        return $manageModel->chengePwd(session('site.id'), input('param.password'), input('param.newPwd'));
    }



}