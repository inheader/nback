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
use app\common\model\Label as labelModel;
use Request;


class Label extends Manage
{
    /**
     * 通用设置标签方法
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function setLabel()
    {
        $ids = input('ids/a', []);
        $model = input('model', '');//要设置的模型
        $total_item = count($ids);

        $this->assign('total_item', $total_item);
        $this->assign('model', $model);
        //已存在标签
        $labelModel = new labelModel();

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $labels = $labelModel->getAllLabel($userWhere);
        $this->assign('labels', $labels);

        $this->view->engine->layout(false);
        $content = $this->fetch('setlabel');
        return [
            'status'=>true,
            'data'=>$content,
            'msg'=>'获取成功',
        ];
    }

    /**
     * 新增标签
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function doSetLabel()
    {
        if (Request::isPost()) {
            $data = input('param.');

            //获取权限的筛选条件
            $userWhere = $this->getMyUserWhere();

            $labelModel = new labelModel();
            return $labelModel->addData($data,$userWhere);
        }
    }

    /**
     * 删除标签
     * @return array
     */
    public function delLabel(){
        $ids = input('ids/a', []);
        $model = input('model', '');//要设置的模型
        $total_item = count($ids);

        $this->assign('total_item', $total_item);
        $this->assign('model', $model);
        //已存在标签
        $labelModel = new labelModel();

        $labels = $labelModel->getAllSelectLabel($ids,$model);
        $this->assign('labels', json_encode($labels,320));

        $this->view->engine->layout(false);
        $content = $this->fetch('dellabel');
        return [
            'status'=>true,
            'data'=>$content,
            'msg'=>'获取成功',
        ];
    }

    public function doDelLabel(){
        if (Request::isPost()) {
            $data = input('param.');
            $data['label'] = input('param.label/a',[]);
            $labelModel = new labelModel();
            return $labelModel->delData($data);
        }
    }

}