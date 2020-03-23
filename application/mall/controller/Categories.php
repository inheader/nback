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
use app\common\model\GoodsCat;
use app\common\model\GoodsType;

/**
 * 商品分类
 * Class Categories
 * @package app\Mall\controller
 * @author keinx
 */
class Categories extends Mall
{
    /**
     * 商品分类列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(!Request::isAjax())
        {
            //打开主页(这边为什么要打开首页)
            return $this->fetch('index');
        }
        else
        {
            $categories = new GoodsCat();
            $data = $categories->getList($userWhere);
            if(count($data) > 0)
            {
                $return_data = array(
                    'status' => 1,
                    'msg' => "数据获取成功",
                    'count' => count($data),
                    'data' => $data
                );
            }
            else
            {
                $return_data = array(
                    'status' => 0,
                    'msg' => "没有分类快去添加一个吧",
                    'count' => count($data),
                    'data' => $data
                );
            }
            return $return_data;
        }
    }


    /**
     * 添加商品分类
     * @param int $parent_id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add($parent_id = GoodsCat::TOP_CLASS_PARENT_ID)
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserAddWhere();
        $this->view->engine->layout(false);
        $goodsCat = (new GoodsCat());
        $goodsType = (new GoodsType());
        if(!Request::isPost())
        {
            //获取添加页面
            $this->assign('parent_id', $parent_id); //父级ID

            $parent = $goodsCat->getAllCat(0,$userWhere);
//            $parent = model('common/GoodsCat')->getAllCat();
            $this->assign('parent', $parent); //顶级分类

            $type = $goodsType->getList($userWhere);
//            $type = model('common/GoodsType')->getList();
            $this->assign('type', $type['data']);
            return $this->fetch('add');
        }
        else
        {
            //存储添加内容
            $data = array(
                'parent_id'     => input('parent_id'),
                'type_id'       => input('type_id'),
                'name'          => input('name'),
                'image_id'      => input('image_id'),
                'sort'          => input('sort')
            );
            $data = array_merge($data,$userWhere);
            $result = $goodsCat->add($data);
//            $result = model('common/GoodsCat')->add($data);
            if($result !== false)
            {
                $return_data = array(
                    'status' => true,
                    'msg' => '添加成功',
                    'data' => $result
                );
            }
            else
            {
                $return_data = array(
                    'status' => false,
                    'msg' => '添加失败',
                    'data' => $result
                );
            }
            return $return_data;
        }
    }


    /**
     * 编辑商品分类
     * @param $id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $this->view->engine->layout(false);
        $goodsCat = (new GoodsCat());
        $goodsType = (new GoodsType());
        if(!Request::isPost())
        {
            //获取编辑页面
            $parent = $goodsCat->getAllCat($id,$userWhere);
            $this->assign('parent', $parent); //父级分类
            $type = $goodsType->getList($userWhere);
            $this->assign('type', $type['data']);
            $data = $goodsCat->getCatInfo($id);
            $this->assign('data', $data); //分类信息
            return $this->fetch('edit');
        }
        else
        {
            $userWhere = $this->getMyUserAddWhere();
            //存储编辑内容
            $data = array(
                'id' => input('id'),
                'parent_id' => input('parent_id'),
                'type_id' => input('type_id'),
                'name' => input('name'),
                'image_id' => input('image_id'),
                'sort' => input('sort')
            );
            $data = array_merge($data,$userWhere);
            $result = $goodsCat->edit($data);
            return $result;
        }
    }


    /**
     * 删除商品分类
     * @param $id
     * @return array
     */
    public function del($id)
    {
        if(!Request::isPost())
        {
            //查询是否可以删除
            $result = model('common/GoodsCat')->getIsDel($id);
            if($result['is'])
            {
                $return_data = array(
                    'status' => true,
                    'msg' => '可以删除',
                    'data' => $result
                );
            }
            else
            {
                $return_data = array(
                    'status' => false,
                    'msg' => '该分类下存在子分类无法删除，请先删除子分类',
                    'data' => $result
                );
            }
            return $return_data;
        }
        else
        {
            //删除
            $result = model('common/GoodsCat')->del($id);
            if($result)
            {
                $return_data = array(
                    'status' => true,
                    'msg' => '删除成功',
                    'data' => $result
                );
            }
            else
            {
                $return_data = array(
                    'status' => false,
                    'msg' => '删除失败',
                    'data' => $result
                );
            }
            return $return_data;
        }
    }

    /**
     * 获取所有一级分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAll()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $result        = [
            'status' => false,
            'msg'    => '获取失败',
            'data'   => [],
        ];
        $goodsCatModel = new GoodsCat();
        $catList     = $goodsCatModel->getFirstCatList($userWhere);
        if (!$catList->isEmpty()) {
            $result['data']   = $catList->toArray();
            $result['status'] = true;
            $result['msg']    = '获取成功';
        }
        return $result;
    }

}