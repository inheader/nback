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
use app\common\model\AreaMallSite;
use app\common\model\PageSiteClass as labelPageModel;
use app\common\model\PageSiteClass;
use Request;
use think\Db;
use think\exception\PDOException;

/**
 * 店铺现实中需要设置的分类
 * Class LabelPageSiteClass
 * @package app\Mall\controller
 */
class LabelPageSiteClass extends Mall
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
        $labelModel = new labelPageModel();

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
     * 店铺设置标签
     * @return array
     */
    public function setTags()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    $siteIds = implode(',',$params['site_id']);
                    (new AreaMallSite())->whereIn('site_id',$siteIds)->update(['recommend_class'=>$params['tag_id']]);
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }
        }
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

            $labelModel = new labelPageModel();
            return $labelModel->addData($data,$userWhere,$this->getMyAuthType());
        }
    }

    /**
     * 取消店铺组界面
     * @return array
     */
    public function delLabel(){
        $ids = input('ids/a', []);
        $model = input('model', '');//要设置的模型
        $total_item = count($ids);

        $this->assign('total_item', $total_item);
        $this->assign('model', $model);
        //已存在标签
        $labelModel = new labelPageModel();

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

    /**
     * 取消店铺组数据
     * @return array
     */
    public function doDelLabel(){
        if (Request::isPost()) {
            $data = input('param.');
            $data['label'] = input('param.label/a',[]);
            //获取权限的筛选条件
            $userWhere = $this->getMyUserWhere();
            $labelModel = new labelPageModel();
            return $labelModel->delData($data,$userWhere,$this->getMyAuthType());
        }
    }

}