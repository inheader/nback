<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: tianyu <tianyu@jihainet.com>
// +----------------------------------------------------------------------

namespace app\Manage\controller;

use app\common\controller\Manage;

use app\common\model\AreaMallClass;
use app\common\model\AreaMallClassCat;
use app\common\model\AreaMallClassCatGoodsMapping;
use app\common\model\AreaMallSite;
use app\common\model\AreaMallSiteAddress;
use app\common\model\Manage as AuthModel;
use app\common\model\PageSiteClass;
use think\Exception;
use think\facade\Request;

class Site extends Manage
{

    /**
     * 管理员店铺列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();


        if (Request::isAjax())
        {

            $siteModel = new AreaMallSite();
            $ret = $siteModel->tableData(array_merge(input('param.'),$userWhere));
            $ret['data'] = collect($ret['data'])->map(function($info){
                $classList = explode(",", $info['recommend_class']);
                $list = (new PageSiteClass())->whereIn('id',$classList)->select();
                $list = collect($list)->map(function ($info){
                    return[
                        'name'  =>$info['name'],
                        'style' =>$info['style'],
                    ];
                })->all();
                $info['recommend_class_ids'] = $list;
                $info['siteType'] = $info['site_type']==1 ? '云商城' : '积分商城';
                return $info;
            })->all();

            return $ret;
        }

        return $this->fetch();
    }

    /**
     * 获取店铺下面的分类别表
     * @return array|mixed
     */
    public function siteClass()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置，这里先注释掉
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }
        if (Request::isAjax())
        {
            $siteClassModel = new AreaMallClass();
            return $siteClassModel->tableData(array_merge(input('param.'),$userWhere));

        }

        return $this->fetch();
    }

    /**
     * 快速选材界面
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function quickClassCat()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }
        $mallClassModel = new AreaMallClass();
        $classList = $mallClassModel->getClassListForMallId($areaId);
        if (Request::isAjax())
        {
            $this->assign('classList', $classList);

            $classId = input('param.classId',0);

            $categories = new AreaMallClassCat();
            $data = $categories->getList($classId);
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

        $this->assign('classList', $classList);

        return $this->fetch();
    }

    /**
     * 快速选材分类添加
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function quickClassAdd()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }
        $classId = input('param.classId',0);
        if(empty($classId)){
            return [
                'status' => false,
                'msg' => '请选择店铺分类'
            ];
        }

        //获取店铺分类下的快速选材子分类
        $quickClassList = (new AreaMallClassCat())->getParentList($classId);

        $mallClassModel = new AreaMallClass();
        $classList = $mallClassModel->getClassListForMallId($areaId);
        $this->assign('classList', $classList);
        $this->assign('parent', $quickClassList);
        $this->view->engine->layout(false);
        return [
                    'status' => true,
                    'data' => $this->fetch(),
                    'msg' => ''
                ];

    }

    /**
     * 快速选材的编辑
     * @return array
     */
    public function quickClassEdit()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }
        $id = input('param.id',0);

        $info = (new AreaMallClassCat())->getInfo($id);

        $this->assign('info', $info);
        $this->view->engine->layout(false);
        return [
            'status' => true,
            'data' => $this->fetch(),
            'msg' => ''
        ];

    }



    /**
     * 添加快速选材
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function quickClassEditAdd()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }
        $classId = input('param.classId',0);
        if(empty($classId)){
            return [
                'status' => false,
                'msg' => '请选择店铺分类'
            ];
        }

        $id = input('param.id',0);
        (new AreaMallClassCat())->updateQuickClassInfo($id,input('param.'));
        return [
            'status' => true,
            'data' => $this->fetch('quickclasscat'),
            'msg' => ''
        ];

    }

    /**
     * @return array
     */
    public function addQuickGoods()
    {
        $goodsId = input('param.id');
        $goodsIds = explode(",",$goodsId);
        $quickId = input('param.quickId');


        //添加某活动的商品
        $actGoodsModel = (new AreaMallClassCatGoodsMapping());
        collect($goodsIds)->each(function($goodsId)use($quickId,$actGoodsModel){
            $actGoodsModel->addQuickGoodsForQuickId($quickId,$goodsId);
        });
        return [
            'status' => true,
            'data' => url('site/quickClassCat?quickId='),
            'msg' => '添加成功'
        ];
    }


    /**
     * 删除快速选材分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function quickClassDel()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限修改'
            ];
        }
        $id = input('param.id',0);

        (new AreaMallClassCat())->deleteClassId($id);
        return [
            'status' => true,
            'data' => $this->fetch('quickclasscat'),
            'msg' => ''
        ];

    }



    /**
     * 添加店铺分类
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addClass()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($mallId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才能添加店铺分类'
            ];
        }
        $userWhere[self::PERMISSION_MALL]=1;
        if ( Request::isAjax() )
        {
            $mallClassModel = new AreaMallClass();
            return $mallClassModel->editData(input('param.'),$mallId);
        }

        return $this->fetch('editclass');
    }

    /**
     * 店铺分类的编辑
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editClass()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里说明是区域权限
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($mallId)){
            return [
                'status' => false,
                'msg' => '没有权限编辑店铺分类'
            ];
        }

        $mallClassModel = new AreaMallClass();

        if( Request::isAjax() )
        {
            //网点的保存
            $result =  $mallClassModel->editData(input('param.'),$mallId);

            return $result;
        }

        //获取店铺分类的信息
        $info = $mallClassModel->where('class_id',input('param.class_id'))->find();

        $this->assign('empty','<div style="text-align:center;color:#999">暂时没有数据</div>');

        return $this->fetch('editclass',[ 'info' => $info ]);
    }


    /**
     * 添加店铺
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($mallId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才能添加店铺'
            ];
        }
        //这里说明能编辑店铺的分类
        $canEditSiteClass = 1;
        $this->assign('canEditSiteClass', $canEditSiteClass);
        $userWhere[self::PERMISSION_MALL]=1;
        if ( Request::isAjax() )
        {
            $storeModel = new AreaMallSite();
            $param = array_merge(input('param.'),$userWhere);
            return $storeModel->addData($param);
        }
        //获取区域下面的所有分类
        $mallClassModel = new AreaMallClass();
        $classList = $mallClassModel->getClassListForMallId($mallId);
        $this->assign('classList', $classList);
        $this->assign('isAdd', 1);
        return $this->fetch('edit');
    }


    /**
     *
     *  门店修改
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $siteModel = new AreaMallSite();
        $addressModel = new AreaMallSiteAddress();
        $mallClassModel = new AreaMallClass();

        //是否能编辑店铺的分类
        $canEditSiteClass = 0;

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        //这里只有区域店铺本身可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(!empty($siteId)){
            //这里说明是店铺权限
            $info = $siteModel->where('site_id',$siteId)->find();
            //这里获取店铺的分类名称
            $classIns = $mallClassModel->where('class_id',$info['class_id'])->find();

            $this->assign('className', $classIns['class_name']);

        }else{
            //这里说明是区域权限
            $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
            $info = $siteModel->where('site_id',input('param.site_id'))->find();
            if(!empty($info) && $info['mall_id']!=$mallId){
                return [
                    'status' => false,
                    'msg' => '没有权限编辑此店铺'
                ];
            }
            //这里说明能编辑店铺的分类
            $canEditSiteClass = 1;
        }


        if( Request::isAjax() )
        {
            $param = input('param.');
            $siteId = $info['site_id'];

            //获取店铺的地址信息
            $amsaIds = isset($param['amsaId']) ? $param['amsaId'] : [];
            $longitude = isset($param['longitude']) ? $param['longitude'] : [];
            $latitude = isset($param['latitude']) ? $param['latitude'] : [];
            $address = isset($param['address']) ? $param['address'] : [];
            //删除已经删掉的店铺地址
            $addressModel->deleteSiteAddressOut($siteId,$amsaIds);

            $addressList = [];
            collect($amsaIds)->each(function($v,$k) use($longitude,$latitude,$address,$siteId,&$addressList){
                $addressList[$k]['amsaId'] = $v;
                $addressList[$k]['site_id'] = $siteId;
                $addressList[$k]['longitude'] = $longitude[$k];
                $addressList[$k]['latitude'] = $latitude[$k];
                $addressList[$k]['address'] = $address[$k];
            });

            //更新店铺地址列表
            collect($addressList)->each(function($info)  use($addressModel){
                $addressModel->updateSiteAddressOut($info['amsaId'],$info);
            });

            $siteInfo = [
                'site_id'               => $param['site_id'],
                'class_id'              => isset($param['class_id']) ? $param['class_id'] : 0,
                'site_name'             => $param['site_name'],
                'site_mobile'           => $param['site_mobile'],
                'site_desc'             => $param['site_desc'],
                'site_logo'             => $param['site_logo'],
                'site_back_image'       => isset($param['site_back_image']) ? $param['site_back_image'] : '',
                'return_shipping_fee'   => $param['return_shipping_fee'],
                'free_shipping_fee'   => $param['free_shipping_fee'],
                'order_shipping_fee'   => $param['order_shipping_fee'],
                'site_type'             => $param['site_type'],
                'goods_stocks_warn'     => $param['goods_stocks_warn'],
                'mall_id'               => !empty($mallId) ? $mallId : 0
            ];

            //网点的保存
            $result =  $siteModel->editData($siteInfo,$canEditSiteClass);

            //添加账号的相关信息
            $userNames = isset($param['userName']) ? $param['userName'] : [];
            $userPassWords = isset($param['userPassWord']) ? $param['userPassWord'] : [];
            if(!empty($userNames) && !empty($siteId)){
                $ret =  $this->addUser($userNames,$userPassWords,$siteId);
                if(!empty($ret)){
                    return $ret;
                }
            }

            return $result;
        }

        //根据店铺ID获取地址列表
        $info['addressList'] = $addressModel->getAddressListForSiteId($info['site_id']);

        //获取区域下面的所有分类
        $classList = $mallClassModel->getClassListForMallId($info['mall_id']);
        $this->assign('classList', $classList);
        $this->assign('canEditSiteClass', $canEditSiteClass);

        //用户权限的列表
        $authList = (new AuthModel)->getUserNameForSiteId($info['site_id']);
        $this->assign('authList',$authList);

        $this->assign('empty','<div style="text-align:center;color:#999">暂时没有数据</div>');

        return $this->fetch('edit',[ 'info' => $info ]);
    }

    /**
     * @param $userNames
     * @param $userPassWords
     * @param $siteId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function addUser($userNames,$userPassWords,$siteId)
    {
        if (
            count($userNames)!=count(array_unique($userNames))
            ||
            count($userNames)!=count($userPassWords)
        ){
            return [
                'status' => false,
                'msg' => '账号名设置有重复，请检查后重新输入',
            ];
        }
        $authModel = (new AuthModel);
        //查看账号是否已经被使用
        $hasAuthName = $authModel->getInsForUserName($userNames);
        if(!empty($hasAuthName)){
            return [
                'status' => false,
                'msg' => $hasAuthName.' 账号已被使用',
            ];
        };

        //添加账号信息
        collect($userNames)->each(function($userName,$k)use($userPassWords,$authModel,$siteId){
            $authModel->addSiteUser($siteId,$userName,$userPassWords[$k]);
        });

        return [];

    }

    /**
     * 设置成功
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editSiteSort()
    {
        $siteId = input('param.site_id');
        $siteSort = input('param.site_sort',0);
        if(!$this->checkMallCanEditSite($siteId)){
            return [
                'status' => false,
                'msg' => '没有权限设置此店铺'
            ];
        }
        $storeModel = new AreaMallSite();
        $storeModel->editSiteSort($siteId,$siteSort);
        return [
            'status' => true,
            'msg' => '设置成功'
        ];
    }

    /**
     * 区域首页推荐
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editPageShow()
    {
        $siteId = input('param.site_id');
        if(!$this->checkMallCanEditSite($siteId)){
            return [
                'status' => false,
                'msg' => '没有权限设置此店铺'
            ];
        }
        $storeModel = new AreaMallSite();

        $field  = input('post.field');
        $value  = input('post.value');

        if ($storeModel->updateIns($siteId, [$field => $value])) {
            $result['msg']    = '更新成功';
            $result['status'] = true;
        } else {
            $result['msg']    = '更新失败';
            $result['status'] = false;
        }

        return $result;



//        $storeModel->updateShowPage($siteId,1);
//        return [
//            'status' => true,
//            'msg' => '首页推荐成功'
//        ];
    }

    /**
     * 店铺停用
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function stop()
    {
        $siteId = input('param.site_id');
        if(!$this->checkMallCanEditSite($siteId)){
            return [
                'status' => false,
                'msg' => '没有权限设置此店铺'
            ];
        }
        $storeModel = new AreaMallSite();
        $storeModel->updateStatus($siteId,0);
        return [
            'status' => true,
            'msg' => '设置成功'
        ];
    }

    /**
     * 店铺启用
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function open()
    {
        $siteId = input('param.site_id');
        if(!$this->checkMallCanEditSite($siteId)){
            return [
                'status' => false,
                'msg' => '没有权限设置此店铺'
            ];
        }
        $storeModel = new AreaMallSite();
        $storeModel->updateStatus($siteId,1);
        return [
            'status' => true,
            'msg' => '设置成功'
        ];
    }

    /**
     * 查看此区域管理员是否有权限编辑此店铺
     * @param $siteId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function checkMallCanEditSite($siteId)
    {
        $siteModel = new AreaMallSite();
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里说明是区域权限
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        $info = $siteModel->where('site_id',$siteId)->find();
        return $info['mall_id']==$mallId;
    }

}