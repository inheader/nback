<?php

/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/22
 * Time: 14:40
 */

namespace app\mall\controller;


use app\common\controller\Mall;
use app\common\model\AreaMallSite;
use app\common\model\MallLoad;
use think\Db;
use think\exception\PDOException;

class AreaInfo extends Mall
{

  protected $areaMallModel = null;
  protected $mallLoad = null;

  protected function initialize()
  {
    parent::initialize();
    $this->areaMallModel = model('AreaMall');
    $this->mallLoad = model('MallLoad');
  }


  public function index()
  {
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();
    $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
    if (empty($areaId)) {
      return [
        'status' => false,
        'msg' => '只有区域管理员才有权限打开此界面'
      ];
    }

    $info = $this->areaMallModel
      ->where('mall_id', $areaId)
      ->find();

    $this->assign('mall', $info);

    return $this->fetch();
  }


  public function add()
  {
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();
    $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;

    if ($this->request->isPost()) {
      $params = $this->request->param();
      if ($params) {
          //查询旗下的官方派送店铺
          $sid = [];
          $siteIns = (new AreaMallSite())->where(['mall_id'=>$areaId,'site_shipping_fee_type'=>'mall'])->field('site_id')->select();
          if(!empty($siteIns)){
              foreach ($siteIns as $siteId=>$site) {
                  $sid[] = $site['site_id'];
              }
          }
        Db::startTrans();
        try {
          $this->areaMallModel->where('mall_id', $areaId)->update($params);
          //并且更新旗下店铺的装配方式
          (new AreaMallSite())->whereIn('site_id',$sid)->update(['site_shipping_loading_type'=>$params['shipping_loading_type']]);
          Db::commit();
          return ['status' => true, 'msg' => '修改成功'];
        } catch (PDOException $e) {
          Db::rollback();
          return ['status' => false, 'msg' => '修改失败'];
        }
      }
    }
  }

  public function load()
  {
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();
    $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
    if (empty($areaId)) {
      return [
        'status' => false,
        'msg' => '只有区域管理员才有权限打开此界面'
      ];
    }

    $result_data = $this->mallLoad->where('mall_id', $areaId)->find();

    $this->assign('info', $result_data);

    return $this->fetch();
  }

  public function addLoad()
  {
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();
    $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;

    if ($this->request->isPost()) {
      $params = $this->request->param();

      if (empty($params['load_img'])) {
        return ['status' => false, 'msg' => '请上传加载页面图片'];
      }
      $load = $this->mallLoad->where('mall_id', $areaId)->find();
      if (isset($load->id)) {
        $load_info = $load;
      } else {
        $load_info = new MallLoad();
        $load_info->create_time = date("Y-m-d H:i:s");
      }
      $load_info->mall_id = $areaId;
      $load_info->load_img = $params['load_img'];
      $load_info->back_color = $params['back_color'];
      $load_info->update_time = date('Y-m-d H:i:s');
      $load_info->save();
      if ($load_info->id) {
        return ['status' => true, 'msg' => '设置成功'];
      } else {
        return ['status' => false, 'msg' => '设置失败'];
      }
    }
  }
}
