<?php

/**
 * 总后台的控制器基类，用于权限判断和一些统一的后台操作
 *
 * @author sin
 *
 */

namespace app\common\controller;
use app\common\model\AreaMallSite;

use think\Container;
use app\common\model\Operation;
use Request;
use think\facade\Cache;

class Manage extends Base
{
    /**
     * 用户权限
     * @var array
     */
    private $permissionInfo = [];

    private $PermissionUserType = null;

    private $PermissionUserTypeId = 0;

    protected $siteId = 0;

    protected $mallId = 0;

    //1超级管理员 2区域管理员 3店铺管理员
    protected $authType = 0;

    //用户ID
    protected $userId = 0;
    //用户名
    protected $userName = '';

    const PERMISSION_SITE     = 'site_id';//店铺权限
    const PERMISSION_MALL     = 'mall_id';//区域权限
    const PERMISSION_ROOT     = 'root_id';//最高权限

    protected function initialize()
    {
//        new Driver
        parent::initialize();
        //没有登陆，请先登录
        if (!session('?manage')) {
            cookie('redirect_url', Container::get('request')->url(), 3600);
            $this->redirect('manage/common/login');
        }

        $cont_name = request()->controller();
        $act_name = request()->action();

        $operationModel = new Operation();


        $manage = session('manage');
        $belong = 'manage';

        $cacheNavKey = $manage['username'].'-'.$operationModel::MENU_MANAGE.'-'.$cont_name.'-'.$act_name;
        $cacheMenuKey = $manage['username'].'-'.$manage['id'].'-'.$cont_name.'-'. $act_name;

        $this->userId = $manage['id'];
        $this->userName = $manage['username'];

        $navList = Cache::remember(
            $cacheNavKey,
            function () use($belong,$operationModel,$cont_name ,$act_name){
                return $operationModel->nav($belong,$operationModel::MENU_MANAGE,$cont_name ,$act_name);
            },
            3600
        );

        $menuList = Cache::remember(
            $cacheMenuKey,
            function()use($belong,$operationModel,$cont_name ,$act_name,$manage){
                return $operationModel->manageMenu($belong,$manage,$cont_name, $act_name);
            },
            3600
        );

        $this->assign('nav', $navList);

        $this->assign('menu',$menuList);

        //获取用户的权限信息
//        dump(session('manage'));
        $this->siteId = $siteId = $manage['site_id'] ? $manage['site_id'] : 0;
        $this->mallId = $mallId = $manage['mall_id'] ? $manage['mall_id'] : 0;
        $isRoot = $manage['is_root'] ? $manage['is_root'] : 0;

        if(empty($siteId) && empty($mallId) && empty($isRoot)){
            $err = [
                'status' => false,
                'data' => '',
                'msg' => '请检查用户身份'
            ];
            echo json_encode($err);
            die();
        }

        if(!empty($siteId)){
            //店铺管理员
            $permissionInfo = [
                'userType'  => self::PERMISSION_SITE,
                'typeId'    => $siteId,
            ];
            $this->authType = Operation::AUTH_SITE;
        }elseif (!empty($mallId)){
            //区域管理员
            $permissionInfo = [
                'userType'  => self::PERMISSION_MALL,
                'typeId'    => $mallId,
            ];
            $this->authType = Operation::AUTH_MALL;
        }else{
            //最高权限管理员
            $permissionInfo = [
                'userType'  => self::PERMISSION_ROOT,
                'typeId'    => 1,
            ];
            $this->authType = Operation::AUTH_ROOT;
        }

        $this->assign('permissionInfo', $permissionInfo);
        $this->PermissionUserType = $permissionInfo['userType'];
        $this->PermissionUserTypeId = $permissionInfo['typeId'];
        $this->permissionInfo = $permissionInfo;
        $jshopHost = Container::get('request')->domain();
        $this->assign('jshopHost',$jshopHost);
    }

    /**
     * 获取权限类别
     * 1超级管理员 2区域管理员 3店铺管理员
     * @return int
     */
    public function getMyAuthType()
    {
        return $this->authType;
    }

    /**
     * 获取用户的ID
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 获取用户名
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * 获取用户权限
     * 用户权限分为site/mall/root，分别代表店铺，区域，最高管理者账号。对应的typeId是其所对应的店铺，区域，最高管理者的ID
     * @return array
                             [
                                'userType' => 'site',//代表店铺权限
                                'typeId' => 1,       //代表店铺ID
                            ]
     */
    public function getMyPermissionInfo()
    {
        return $this->permissionInfo;
    }

    /**
     * 用户权限类别
     * @return null
     */
    public function getMyPermissionUserType()
    {
        return $this->PermissionUserType;
    }

    /**
     * 用户权限ID
     * @return int
     */
    public function getMyPermissionUserTypeId()
    {
        return $this->PermissionUserTypeId;
    }

    /**
     * 获取权限的筛选条件
     * @return array
     */
    public function getMyUserWhere()
    {
        $userWhere = [];
        //如果是最高权限的管理员，则不做权限控制
        if($this->getMyPermissionUserType()!=self::PERMISSION_ROOT){
            $userWhere[$this->getMyPermissionUserType()] = $this->getMyPermissionUserTypeId();
        }

        return $userWhere;
    }

    /**
     * 获取权限的保存
     * 当权限为店铺的时候，需要将店铺所属区域的权限也保存进去
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyUserAddWhere()
    {
        $userWhere = [];
        //只有店铺的权限才能保存
        if($this->getMyPermissionUserType()!=self::PERMISSION_SITE){
            $err = [
                'status' => false,
                'data' => '',
                'msg' => '只有店铺权限才能保存信息'
            ];
            echo json_encode($err);
            die();
        }
        $userWhere[$this->getMyPermissionUserType()] = $this->getMyPermissionUserTypeId();

        $siteId = $this->getMyPermissionUserTypeId();
        $siteInfo = (new AreaMallSite())->siteInfo($siteId);
        //查看该店铺所属mall_id权限
        $userWhere[self::PERMISSION_MALL] = $siteInfo['mall_id'];
        return $userWhere;
    }

}
