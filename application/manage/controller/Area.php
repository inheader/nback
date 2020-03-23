<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Manage\controller;

use app\common\controller\Manage;
use org\Curl;
use Request;
use app\common\model\Area as AreaModel;
use think\Db;

/**
 * 地区管理
 * Class Area
 * @package app\seller\controller
 */
class Area extends Manage
{
    /**
     * 地区列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        /*$areaList=$areaModel->getTreeArea();
        $this->assign('arealist',json_encode($areaList));*/

        if(Request::isAjax())
        {
            $type = input('type');
            $id = input('id');

            $areaModel = new AreaModel();
            $result = $areaModel->getAreaList($type, $id);
            if($result){
                $return_data = array(
                    'status' => true,
                    'msg' => '获取成功',
                    'data' => $result
                );
            }
            else{
                $return_data = array(
                    'status' => false,
                    'msg' => '获取失败',
                    'data' => $result
                );
            }
            return $return_data;
        }

        return $this->fetch();
    }


    /**
     * 添加地区
     * @return array
     */
    public function add()
    {
        $areaModel         = new AreaModel();
        $data['name']      = input('name');
        $id                = $areaModel->max('id');
        $data['id']        = $id + 1;
        $data['parent_id'] = input('parent_id');
        $data['depth']     = input('depth');
        $data['sort']      = input('sort');
        $result            = $areaModel->add($data);
        if ($result) {
            $return_data = array(
                'status' => true,
                'msg'    => '添加成功',
                'data'   => $result,
            );
        } else {
            $return_data = array(
                'status' => false,
                'msg'    => '添加失败',
                'data'   => $result,
            );
        }
        return $return_data;
    }


    /**
     * 编辑地区
     * @return array
     */
    public function edit()
    {
        if(Request::isPost())
        {
            $id = input('id');
            $data['name'] = input('name');
            $data['sort'] = input('sort');
            $areaModel = new AreaModel();
            $result = $areaModel->edit($id, $data);
            if($result)
            {
                $return_data = array(
                    'status' => true,
                    'msg' => '修改成功',
                    'data' => $result
                );
            }
            else
            {
                $return_data = array(
                    'status' => false,
                    'msg' => '修改失败',
                    'data' => $result
                );
            }
            return $return_data;
        }

        $id = input('id');
        $info = model('common/Area')->getAreaInfo($id);
        if($info)
        {
            $return_data = array(
                'status' => true,
                'msg' => '获取成功',
                'data' => $info
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '获取失败',
                'data' => $info
            );
        }
        return $return_data;
    }


    /**
     * 删除地区
     * @return mixed
     */
    public function del()
    {
        $id = input('id');
        $result = model('common/Area')->del($id);
        return $result;
    }

    public function getlist()
    {
        if (Request::isAjax()) {
            $areaModel       = new AreaModel();
            $filter          = input('request.');
            if (!isset($filter['parent_id']) || !$filter['parent_id']) {
                $filter['parent_id'] = 0;
            }
            return $areaModel->tableData($filter);
        }
        return $this->fetch();
    }

}