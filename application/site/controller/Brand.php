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
use app\common\model\Brand as BrandsModel;
use think\facade\Request;

class Brand extends Site
{

    /**
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if(Request::isAjax())
        {
            $brandModel = new BrandsModel();
            $param = input('param.');
            if(!empty($userWhere)){
                $param = array_merge($param,$userWhere);
            }
            return $brandModel->tableData($param);
        }
        return $this->fetch();
    }


    /**
     *  品牌添加
     * @return array|mixed
     */
    public function add()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserAddWhere();

        $this->view->engine->layout(false);
        if(Request::isPost())
        {
            $brandModel = new BrandsModel();
            $param = array_merge(input('param.'),$userWhere);
            return $brandModel->addData($param);
        }
        return $this->fetch();
    }


    /**
     *  品牌修改
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $this->view->engine->layout(false);
        $brandModel = new BrandsModel();
        if(Request::isPost())
        {
            return $brandModel->saveData(input('param.'));
        }
        $data = $brandModel->where('id',input('param.id/d'))->find();
        if (!$data) {
            return error_code(10002);
        }
        return $this->fetch('edit',['data' => $data]);
    }


    /**
     *  总后台品牌 软删除
     * User:tianyu
     * @return array
     */
    public function del()
    {
        $result = [
            'status' => false,
            'msg' => '删除失败',
            'data' => []
        ];
        $brandModel = new BrandsModel();
        if ($brandModel::destroy(input('param.id/d'))) {
            $result['status'] = true;
            $result['msg'] = '删除成功';
        }
        return $result;
    }

    /**
     * 获取所有品牌
     */
    public function getAll()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $result     = [
            'status' => false,
            'msg'    => '获取失败',
            'data'   => [],
        ];
        $brandModel = new BrandsModel();
        $brandList  = $brandModel->field('id,name,sort')->where($userWhere)->order('sort asc')->select();
        if (!$brandList->isEmpty()) {
            $result['data']   = $brandList->toArray();
            $result['status'] = true;
            $result['msg']    = '获取成功';
        }
        return $result;
    }

}