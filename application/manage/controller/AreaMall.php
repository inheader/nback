<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/27
 * Time: 22:27
 */

namespace app\Manage\controller;

use app\common\controller\Manage;
use app\common\model\AreaDataMapping;
use app\common\model\AreaMall as AreaMallModel;
use app\common\model\Area as AreaModel;
use app\common\model\Operation as OperationModel;
use app\common\model\Manage as AuthModel;
use app\common\model\MallManage as AuthModels;
use org\Curl;
use think\facade\Request;
use think\Db;
use think\facade\Cache;

/**
 * 区域管理
 * Class AreaMall
 * @package app\seller\controller
 */
class AreaMall extends Manage
{
    /**
     * 区域管理列表
     * @return mixed
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if(!empty($userWhere)){
            return [
                'status' => false,
                'msg' => '只有最高权限管理员有权限打开此界面'
            ];
        }

        if (Request::isAjax())
        {
            $areaMallModel = new AreaMallModel();
            $re = $areaMallModel->tableData(array_merge(input('param.'),$userWhere));

            $data = isset($re['data']) ? $re['data'] : [];

            $re['data'] = collect($data)->map(function($info){
                //前台需要显示状态，这边在后台处理一下
                $ipOpen = isset($info['ip_open']) ? $info['ip_open'] : 0;
                $info['ipOpenName'] = !empty($ipOpen) ? '启用中...' : '停用中...';
                return $info;
            });

            return $re;
        }
        return $this->fetch();
    }

    /**
     * 区域的添加
     * @return AreaMallModel|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        // 实例化区域模型
        $areaMallModel = new AreaMallModel();

        if (Request::isPost()){
            return $areaMallModel->updateAreaMall(0,input('param.'));
        }

        $areaModel = new AreaModel();

        $province = $areaModel->getProvince();
        $this->assign('province',$province);
        $this->assign('provinceText',json_encode($provinceTree));
        
        // $this->assign('areaTree',$areaModel->getTreeArea());
        return $this->fetch('edit');
    }

    /**
     * 区域的编辑
     * @return AreaMallModel|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {

        //获取权限类别
        $authType = $this->getMyAuthType();

        if($authType!=OperationModel::AUTH_ROOT){
            $err = [
                'status' => false,
                'data' => '',
                'msg' => '请检查用户身份'
            ];
            echo json_encode($err);
            die();
        }

        $mallId = input('param.mall_id');
        $areaMallModel = new AreaMallModel();
        $info = $areaMallModel->where('mall_id',$mallId)->find();

        //获取区域映射地区
        $areaDataMappingModel = new AreaDataMapping();
        $areaMapping = $areaDataMappingModel->getMappingForMallId($mallId);

        $areaList = collect($areaMapping)->map(function($areaMappingInfo){
            $areaId = isset($areaMappingInfo['area_id']) ? $areaMappingInfo['area_id'] : 0;
            return[
                'adr_id'    => $areaId,
                'adr_Name'  => (new AreaModel())->getAllName($areaId),
            ];
        })->all();


        $this->assign('areaList',$areaList);

        //用户权限的列表
        $authList = (new AuthModel)->getUserNameForMallId($mallId);
        $this->assign('authList',$authList);

        $this->assign('data',$info);
        return $this->fetch('edit');
    }

    /**
     * 停用区域
     * @return array
     */
    public function stop()
    {
        $mallId = input('param.mall_id');
        $areaMallModel = new AreaMallModel();

        $areaMallModel->updateStatus($mallId,0);
        return [
            'msg'    => '停用成功',
            'status' => true,
        ];
    }

    /**
     * 启用区域
     * @return array
     */
    public function open()
    {
        $mallId = input('param.mall_id');
        $areaMallModel = new AreaMallModel();

        $areaMallModel->updateStatus($mallId,1);
        return [
            'msg'    => '启用成功',
            'status' => true,
        ];
    }

    /**
     * 保存区域的信息
     * @return AreaMallModel|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveAreaMapping()
    {
        $areaDataMappingModel = new AreaDataMapping();
        if (Request::isPost())
        {
            $param = input('param.');

            //获取区域的参数信息
            $mall_id = isset($param['mall_id']) ? $param['mall_id'] : 0;
            $mall_name = isset($param['mall_name']) ? $param['mall_name'] : '';
            $adrIds = isset($param['adr_id']) ? $param['adr_id'] : [];

            $dataIds = [];
            collect($adrIds)->each(function($v)use(&$dataIds){
                $dataIds[] = $v;
            });


            //数组去重
            $dataIds = array_unique($dataIds);

            //查看其它区域是否使用了保存的区域
            $isUse = $areaDataMappingModel->getOtherAreaUse($mall_id,$dataIds);
            if(!empty($isUse)){
                return [
                    'status' => false,
                    'msg' => '地址已经被其他区域使用，无法添加',
                    'data' => ''
                ];
            }

            //更新区域的信息
            $areaMallModel = new AreaMallModel();
            $mallId = $areaMallModel->updateAreaMall($mall_id,[
                'mall_name'=>$mall_name
            ]);

            //删除已经删掉的区域映射信息
            $areaDataMappingModel->deleteAreaDataMappingOut($mallId,$dataIds);

            //更新区域映射信息
            collect($dataIds)->each(function($id)  use($mallId,$areaDataMappingModel){
                $areaDataMappingModel->updateAreaDataMapping($mallId,$id);
            });

            //添加账号的相关信息
            $userNames = isset($param['userName']) ? $param['userName'] : [];
            $userPassWords = isset($param['userPassWord']) ? $param['userPassWord'] : [];
            if(!empty($userNames) && !empty($mallId)){
                $ret =  $this->addUser($userNames,$userPassWords,$mallId);
                if(!empty($ret)){
                    return $ret;
                }
            }

        }
        return [
            'status' => true,
            'msg' => '修改成功',
            'data' => ''
        ];
    }

    //地址选择
    public function addAdr()
    {
        $this->view->engine->layout(false);
        $areaModel = new AreaModel();

    
        // $areaTree = $areaModel->getTreeArea();
        // $areaTree = Cache::remember(
        //     'areaTreeAll',
        //     function()use($areaModel){
        //         return $areaModel->getTreeArea();
        //     },
        //     3600
        // );

        // $this->assign('areaTree',$areaTree);
        // $this->assign('areaTreeText',json_encode($areaTree));

        $province = $areaModel->getProvince();
        $this->assign('province',$province);
        $this->assign('provinceText',json_encode($province));

        return [
            'status' => true,
            'data' => $this->fetch('addadr'),
            'msg' => ''
        ];
    }



    /**
     * 添加区域管理员账号
     * @param $userNames
     * @param $userPassWords
     * @param $mall_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function addUser($userNames,$userPassWords,$mall_id)
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
//        $authModel = (new AuthModel);
        $authModels = (new AuthModels);
        //查看账号是否已经被使用
        $hasAuthName = $authModels->getInsForUserName($userNames);
        if(!empty($hasAuthName)){
            return [
                'status' => false,
                'msg' => $hasAuthName.' 账号已被使用',
            ];
        };

        //添加账号信息
        collect($userNames)->each(function($userName,$k)use($userPassWords,$authModels,$mall_id){
            $authModels->addMallUser($mall_id,$userName,$userPassWords[$k]);
        });

        return [];

    }

}