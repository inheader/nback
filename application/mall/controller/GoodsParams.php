<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Mall\controller;

use app\common\controller\Mall;
use Request;
use app\common\model\GoodsParams as GPmodel;

/**
 * 商品参数
 * Class GoodsTypeSpec
 * @package app\Mall\controller
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 20:07
 */
class GoodsParams extends Mall
{
    /**
     * 商品类型列表
     * @return mixed
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if (Request::isAjax()) {
            $goodsParamsModel       = new GPmodel();
            $filter              = array_merge(input('request.'),$userWhere);
            return $goodsParamsModel->tableData($filter);
        }
        return $this->fetch('index');
    }

    /**
     * 添加参数
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-09 20:47
     */
    public function add()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserAddWhere();

        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $return_data = [
                'status' => false,
                'msg'    => '添加失败',
                'data'   => '',
            ];
            //存储添加内容
            $data             = [
                'name'      => input('post.name'),
                'type'      => input('post.type'),
                'value'     => input('post.value'),
            ];
            $data = array_merge($data,$userWhere);
            $goodsParamsModel = new GPmodel();
            $result           = $goodsParamsModel->doAdd($data);
            if ($result !== false) {
                $return_data = [
                    'status' => true,
                    'msg'    => '保存成功',
                    'data'   => $result,
                ];
            }
            return $return_data;
        }
        //获取添加页面
        return $this->fetch('add');
    }

    public function edit()
    {
        $goodsParamsModel = new GPmodel();
        $id = input('id/d','0');
        if(!$id){
            return '参数错误';
        }
        $this->view->engine->layout(false);
        if (Request::isPost()) {
            $return_data = [
                'status' => false,
                'msg'    => '添加失败',
                'data'   => '',
            ];
            //存储添加内容
            $data             = [
                'name'      => input('post.name'),
                'type'      => input('post.type'),
                'value'     => input('post.value'),
            ];
            $result           = $goodsParamsModel->doAdd($data,$id);
            if ($result !== false) {
                $return_data = [
                    'status' => true,
                    'msg'    => '保存成功',
                    'data'   => $result,
                ];
            }
            return $return_data;
        }

        $data = $goodsParamsModel->where(['id'=>$id])->find();
        if(!$data)
        {
            return '无数据';
        }
        $this->assign($data->toArray());
        //获取添加页面
        return $this->fetch('edit');
    }

    /**
     * 删除参数
     * @return array
     */
    public function del()
    {
        $result = [
            'status' => false,
            'msg'    => '删除失败',
            'data'   => '',
        ];
        $id     = input('post.id', 0);
        if ($id) {
            $goodsParamsModel    = new GPmodel();
            $filter['id']        = $id;
            $res                 = $goodsParamsModel->doDel($filter);
            if ($res) {
                $result['msg']    = '删除成功';
                $result['status'] = true;
            }
        }
        return $result;
    }

    /**
     * 弹窗参数列表
     */
    public function getlist(){
        $this->view->engine->layout(false);
        return $this->fetch('getlist');
    }

}