<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Mall\controller;

use Request;
use app\common\controller\Mall;
use app\common\model\ActFullDelivery;
use app\common\model\ActFullDeliveryGoods;
use app\common\model\AreaMallClassCat;
use app\common\model\AreaMallClassCatGoodsMapping;
use app\common\model\AreaMallSite;
use app\common\model\MallRoleRel;
use app\common\model\OrderProductEvaluate;
use app\common\model\Goods as goodsModel;
use app\common\model\GoodsImages;
use app\common\model\Ietask;
use app\common\model\GoodsTypeParams;
use think\Exception;
use think\Queue;
use think\Db;

/***
 * 商品
 * Class AddCart
 * @package app\seller\controller
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-11 17:20
 */
class AddCart extends Mall
{

  private $spec = []; //规格数组
  static $sku_item; //规格
  static $deep_key; //规格深度
  static $total_item; //总规格


  /**
   * 商品列表
   * @return mixed
   */
  public function index()
  {
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();

    $goodsModel = new goodsModel();

    //获取权限类别
    $authType = $this->getMyAuthType();
    $this->assign('authType', $authType);
    if (Request::isAjax()) {
      $filter = input('request.');

      //列表加上权限的筛选
      $filter = array_merge($filter, $userWhere);
      $list = $goodsModel->tableDataB($filter, true, $this->getMyAuthType());

      $list['data'] = collect($list['data'])->map(function ($info) use ($siteType) {
        $info['price'] = empty($siteType) ? intval($info['price']) : $info['price'];
        return $info;
      });

      return $list;
    }
    return $this->fetch('index');
  }


  // 添加购物车
  public function add()
  {
    $this->view->engine->layout(false);
    $data = input('data');

    if ($data) {
      // 引用模型
      $goodsModel = new goodsModel();

      $val = $data;

      // 分割字符串
      $data = explode(",", $data);
      // 数组去重
      $data = array_unique($data);
      $goods = $goodsModel->where('id', 'in', $data)->field('id,mall_id,site_id,bn,name,price')->orderRaw('field(id,' . $val . ')')->select()->toArray();

      $goods = json_encode($goods);


      $this->assign('goods', $goods);
      return [
        'status' => true,
        'data' => $this->fetch('add'),
        'msg' => ''
      ];
    } else {
      return [
        'status' => flase,
        'data' => $this->fetch('add'),
        'msg' => '未获取到数据记录'
      ];
    }
  }


  // 添加购物车
  public function readd()
  {
    $data = input('data');

    if ($data) {
      // 引用模型
      $goodsModel = new goodsModel();
      if (Request::isAjax()) {
        // 分割字符串
        $data = explode(",", $data);
        // 数组去重
        $data = array_unique($data);

        $goods = $goodsModel->where('id', 'in', $data)->select();
        return [
          'return' => 0,
          'data' => $goods,
          'msg' => ''
        ];
      }
    } else {
      return [
        'status' => flase,
        'data' => $this->fetch('add'),
        'msg' => '未获取到数据记录'
      ];
    }
  }


  /**
   * 保存商品
   * @return array
   * @throws \think\exception\PDOException
   */
  public function doAdd()
  {
    if (Request::isPost()) {
      $ids = input('ids');
      $data = input('data');

      $valdata = json_decode($data, true);
      if (!empty($valdata) && isset($valdata['phone'])) {
        if (!checkPhoneNumberValidate($valdata['phone'])) {
          return [
            'status' => flase,
            'data' => '',
            'msg' => '您输入的手机号码格式错误'
          ];
        }
        // 查询用户信息
        $buyer = Db::name('buyer')->where('buyer_tel', $valdata['phone'])->find();
        // 判断是否存在改用户
        if (!$buyer) {
          return [
            'status' => flase,
            'data' => '',
            'msg' => '未找到用户信息'
          ];
        }

        $val = $ids;
        // 分割字符串
        $ids = explode(",", $ids);
        // 数组去重
        $ids = array_unique($ids);
        $goodsModel = new goodsModel();
        $goods = $goodsModel->where('id', 'in', $ids)->field('id,mall_id,site_id,bn,name,price')->orderRaw('field(id,' . $val . ')')->select()->toArray();


        if ($goods) {
          foreach ($goods as $key => $val) {
            // 判断该商品是否在购物车中存在
            // $isgoods = Db::name('buyer_cart')->where(['buyer_id'=>$buyer['buyer_id'],'goods_id'=>$val['id']])->find();
            // if($isgoods && $isgoods['bc_id']){
            //     Db::name('buyer_cart')->where('bc_id',$isgoods['bc_id'])->delete();
            // }
            $goods_num = $valdata['goods_sum' . '[' . $key . ']'] ? $valdata['goods_sum' . '[' . $key . ']'] : 1;
            // 查询货品表
            $products = Db::name('products')->where('goods_id', $val['id'])->find();
            Db::name('buyer_cart')->insert(['mall_id' => $val['mall_id'], 'site_id' => $val['site_id'], 'buyer_id' => $buyer['buyer_id'], 'goods_id' => $val['id'], 'products_id' => $products['id'], 'goods_num' => $goods_num, 'created_at' => date('Y-m-d H:i:s', time()), 'updated_at' => date('Y-m-d H:i:s', time())]);
          }
        }

        return [
          'status' => true,
          'data' => '',
          'msg' => '执行成功'
        ];
      }
    }
  }


  /**
   * 编辑商品公共数据
   * User: wjima
   * Email:1457529125@qq.com
   * Date: 2018-01-12 17:34
   */
  private function _common()
  {
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();

    //分类
    $goodsCatModel = new GoodsCat();
    $catList       = $goodsCatModel->getCatByParentId(0, $userWhere);
    $this->assign('catList', $catList);
    //类型
    $goodsTypeModel = new GoodsType();
    $typeList       = $goodsTypeModel->getAllTypes(0, $userWhere);
    $this->assign('typeList', $typeList);

    //品牌
    $brandModel = new Brand();
    $brandList  = $brandModel->getAllBrand($userWhere);
    $this->assign('brandList', $brandList);

    hook('goodscommon', $this); //商品编辑、添加时增加钩子

  }


  /**
   * 拍照制单
   * @return mixed
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  public function order(){

    return $this->fetch('order');
  }





  /**
   * erp制单
   * @return mixed
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  public function index_erp(){
    if (request()->isGet()) {
      return $this->fetch('index_erp');
    }
  }


  /**
   * 商品搜索
   * @return mixed
   * @throws \think\db\exception\DataNotFoundException
   * @throws \think\db\exception\ModelNotFoundException
   * @throws \think\exception\DbException
   */
  public function goodsSearch()
  {
    $this->_common();

    //能否选择店铺的信息
    $canChooseSite = 0;
    //获取权限的筛选条件
    $userWhere = $this->getMyUserWhere();
    //这里只有区域管理员可以设置，这里先注释掉
    $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
    if (!empty($areaId)) {
      //这里说明是区域权限，需要将店铺列表的展示信息显示出来
      $canChooseSite = 1;
      //获取区域下面的店铺列表
      $siteList = (new AreaMallSite())->getMallSiteList($areaId);
      $this->assign('siteList', $siteList);
    }

    $this->assign('canChooseSite', $canChooseSite);

    $this->view->engine->layout(false);
    return $this->fetch('goodssearch');
  }

  public function search()
  {
    $search = input('search');
    $type = input('type');
    if ($search) {
      $result = DB::name('goods')->field('bn,id,name')->where('bn', 'like', '%' . $search . '%')->whereOr('name', 'like', '%' . $search . '%')->select();
      $result_data = [];
      foreach ($result as $item) {
        $data['id'] = $item['id'];
        if ($type == 'bn') {
          $data['text'] = $item['bn'];
        }
        if ($type == 'name') {
          $data['text'] = $item['name'];
        }

        array_push($result_data, $data);
      }
      return json(['code' => 200, 'data' => $result_data]);
    }
  }
}
