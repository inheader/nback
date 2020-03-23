<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Mall\controller;

use app\common\model\BuyerPhoto;
use Request;
use app\common\controller\Mall;
use app\common\model\ActFullDelivery;
use app\common\model\ActFullDeliveryGoods;
use app\common\model\AreaMallClassCat;
use app\common\model\AreaMallClassCatGoodsMapping;
use app\common\model\AreaMallSite;
use app\common\model\MallRoleRel;
use app\common\model\MallPhoto;
use app\common\model\AreaMall;
use app\common\model\Area;
use app\common\model\Products;
use app\common\model\BuyerAddress;
use app\common\model\OrderBuyerInfo;
use app\common\model\AreaMallCredit;
use app\common\model\CreditLines;
use app\common\model\Buyer;
use app\common\model\Order;
use app\common\model\Images;
use app\common\model\OrderCommon;
use app\common\model\OrderGoods;
use app\common\model\OrderService;
use app\common\model\OrderLog;
use app\common\model\OrderServiceRule;
use app\common\model\AreaMallBuyerCouponList;
use app\common\model\OrderProductEvaluate;
use app\common\model\Goods as goodsModel;
use app\common\model\GoodsImages;
use app\common\model\Ietask;
use app\common\model\GoodsTypeParams;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use org\Curl;
use think\Exception;
use think\exception\PDOException;
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
class Voucher extends Mall
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

    $userWhere = $this->getMyUserWhere();

    $mall_id = $userWhere['mall_id'];

    if($this->request->isAjax())
    {
        $params = $this->request->param();
        if($params)
        {
            $where = [];
            $page = $this->request->get("page", 0);
            $limit = $this->request->get("limit", 0);

            // $where['status'] = 1;

            list($where,$page,$limit) = [$where,$page,$limit];
            $list = (new MallPhoto())
              ->where('mall_id',$mall_id)
              ->where($where)
              ->page($page,$limit)
              ->order([ 'id' => 'desc'])
              ->select();
            $total = (new MallPhoto())
              ->where($where)
              ->count();
              
            foreach ($list as $key => $val) {
                $list[$key]['status_name'] = (new MallPhoto())->customer_state_name[$val['status']];
                $list[$key]['order_state_name'] = (new MallPhoto())->status_name[$val['order_state']];
            }
            
            $result = array("code"=>0,"count" => $total, "data" => $list);
            return json($result);
        }
    }
    return $this->fetch();

  }


    // 新增拍照单
    public function addphoto(){

      return $this->fetch();

    }



    // 编辑商品
    public function edit(){
        $id = $this->request->get("id", 0);

        // 拍照单
        $data = (new MallPhoto())->PhotoOrder($id);

        $this->assign('data', $data);

        return $this->fetch();

    }


    // 保存料单信息
    public function doPhoto(){

      // 查询区域用户
      $userWhere = $this->getMyUserWhere();
      // 区域id
      $mall_id = $userWhere['mall_id'];
      $customer_phone = input('post.customer_phone', '');
      $consignee_name = input('post.consignee_name', '');
      $consignee_phone = input('post.consignee_phone', '');
      $consignee_address = input('post.consignee_address', '');
      $consignee_remark = input('post.consignee_remark', '');
      $provinceId = input('post.province', '');
      $cityId = input('post.city', '');
      $areaId = input('post.area', '');
      $phone_img = input('post.goods.img', '');

      // 判断客服电话
      if(empty($customer_phone)){
        return ['status'=>false , 'msg'=>'客服电话不能为空'];
      }

      // 判断收货人
      if(empty($consignee_name)){
        return ['status'=>false , 'msg'=>'收货人不能为空'];
      }

      // 判断收货电话
      if(empty($consignee_phone)){
        return ['status'=>false , 'msg'=>'收货电话不能为空'];
      }

      // 判断收货地址
      if(empty($consignee_address)){
        return ['status'=>false , 'msg'=>'收货地址不能为空'];
      }
      
      // 判断省市区是否为空
      if(empty($provinceId) || empty($cityId) || empty($cityId)){
        return ['status'=>false , 'msg'=>'省市区不能为空'];
      }

      // 判断料单图片
      if(empty($phone_img)){
        return ['status'=>false , 'msg'=>'料单图片不能为空'];
      }


      $address_data =(new BuyerAddress())->where('mall_id',$mall_id)->find();
      $image_data = (new Images())->InstanceByImageId($phone_img['0']);
      $buyer_data = (new Buyer())->getBuyerInfoForTel($customer_phone);

      if(empty($buyer_data) || empty($buyer_data['buyer_id'])){
        return ['status'=>false , 'msg'=>'未找到该用户'];
      }

      // 判断是否存在区域信息
      if(empty($address_data) || empty($address_data['area_info'])){
        return ['status'=>false , 'msg'=>'区域信息不存在'];
      }

      $buyer_address_data = (new BuyerAddress())->where('mall_id',$mall_id)->where('buyer_id',$buyer_data['buyer_id'])->where('buyer_name',$consignee_name)->where('tel_phone',$consignee_phone)->where([['address', 'like', '%' . $consignee_address . '%']])->find();

      // 判断用户地址库中是否存在该商品
      if(empty($buyer_address_data)){
        $province = (new Area())->getCacheAreaInsForValue($provinceId,'name');
        $city = (new Area())->getCacheAreaInsForValue($cityId,'name');
        $area = (new Area())->getCacheAreaInsForValue($areaId,'name');
        $condition = [];
        $condition['mallId'] = $mall_id;
        $condition['buyerId'] = $buyer_data['buyer_id'];
        $condition['buyerNickName'] = $consignee_name;
        $condition['buyerMobile'] = $consignee_phone;
        $condition['province'] = $province;
        $condition['city'] = $city;
        $condition['area'] = $area;
        $condition['detailAddress'] = $consignee_address;
        $condition['floorNum'] = '';
        $condition['isDefault'] = 0;
        $buyerAddressId = 0;
        $buyer_address_data['buyer_address_id'] = $this->editAddressInfo($buyerAddressId,$condition);
      }

      // 新增拍照单
      $data = [];
      $data['mall_id'] = $mall_id;
      $data['buyer_id'] = $buyer_data['buyer_id'];
      $data['address_id'] =	$buyer_address_data['buyer_address_id'];
      $data['is_express']	=	0;
      $data['coupons_id']	= 0;
      $data['pay_sn']	= '';
      $data['buy_attr']	= 0;
      $data['remark']	= $consignee_remark;
      $data['good_img'] = $image_data['url'];
      $data['order_state'] = 0;
      $data['status'] =	1;
      $data['created_at'] = date("Y-m-d H:i:s",time());
      $res = (new MallPhoto())->insert($data);
      if($res){
        return ['status'=>true , 'msg'=>'新增成功'];
      }
    }



    // 拒绝拍照单状态
    public function invalid(){
        $id = $this->request->get("id",0);
        if($id){
            $data = (new MallPhoto())->PhotoOrder($id);
            if($data){
              return (new MallPhoto())->where('id', $data['id'])->update(['status' => 3]);
            }
        }
        return false;
    }



    // 添加选择商品
    public function doOrder(){
        $data = $this->request->post();
        if($data && $data['id'] && $data['buyer_id'] && $data['mall_id']){
            // 新增购物车前删除已有虚拟购物车
            Db::name('buyer_photo')->where('buyer_id',$data['buyer_id'])->where('photo_id', $data['id'])->delete();
            // 遍历并新增虚拟购物车
            foreach($data['ids'] as $key => $row){
                $pro['id'] = $row;
                $pro['num'] = intval($data['nums'][$key]) <= 0  ? 1 : intval($data['nums'][$key]);
                $products = Db::name('products')->where('goods_id', $pro['id'])->find();
                Db::name('buyer_photo')->insert(['mall_id' => $data['mall_id'], 'site_id' => $products['site_id'], 'buyer_id' => $data['buyer_id'], 'photo_id' => $data['id'], 'goods_id' => $pro['id'], 'products_id' => $products['id'], 'goods_num' => $pro['num'], 'created_at' => date('Y-m-d H:i:s', time()), 'updated_at' => date('Y-m-d H:i:s', time())]);
            }

            // 变更拍照单状态
            Db::name('mall_photo')->where('id', $data['id'])->update(['status' => 2]);
        }
    }


  // 获取拍照单
  public function getOrderPhoto(){

    $photo_id = $this->request->get("photo_id", 0);

    if($photo_id){

        $orderDetail = Db::name('buyer_photo')->where('photo_id',$photo_id)->select();

        $data = [];
        foreach($orderDetail as $key => $row){
            $goods = Db::name('goods')->where('id', $row['goods_id'])->find();
            $row['bn'] = $goods['bn'];
            $row['name'] = $goods['name'];
            $row['price'] = $goods['price'];
            $data[] = $row;  
        }

        return json(['code' => 200, 'data' => $data]);
    }

  }





  // 编辑制单
  public function pictures(){

    $id = $this->request->get("id", 0);

    // 拍照单
    $data = (new MallPhoto())->PhotoOrder($id);

    $this->assign('data', $data);

    return $this->fetch();
  }


  // 制单
  public function goOrder(){
    if($this->request->isPost()){
        $data = $this->request->post();

        $photoInstance = (new MallPhoto())->InstanceMallPhotoId($data['id']);

        // 商品组
        $products = [];
        foreach($data['ids'] as $key => $row){
            $item = (new Products())->InstanceByGoodsId($row);
            $prod = [];
            $pro['spdId'] = $item->id;
            $pro['num'] = $data['nums'][$key];
            $products[] = $pro;
        }

        $handling = '100';
        $isExpress = 1;

        // 确认订单
        $this->confirmOrder($photoInstance->id, $photoInstance->buyer_id, $photoInstance->mall_id, $photoInstance->address_id, $products, $handling, $isExpress);
    }
  }


  // 搜索商品
  public function search(){

    // 这边要给内存，不然会炸
    ini_set('memory_limit', '1024M'); 

    // 初始化
    $this->jieba = new Jieba();
    $this->finalseg = new Finalseg();

    $this->jieba->init();
    $this->finalseg->init();

    $name = input('name');
    $type = input('type');
    $mall_id = input('mall_id');
    // 区域用户
    $mall_id = intval($mall_id);

    $part_list = Jieba::cut($name);
    
    if ($name) {
      $query = DB::name('goods')->where(['mall_id' => $mall_id, 'marketable' => '1']);
      if(!empty($part_list)){
          $query = $query->Where(function($query) use($part_list){
              foreach($part_list as $word){
                  $query->whereOr('name','like','%' . $word . '%');
              }
          });
          //    $query = $query->orWhere('name','like',$keyword);
      }
      $query = $query->field('bn,id,mall_id,site_id,name,price,transport_fee,image_id');
      $result = $query->select();

      foreach($result as $key => &$value){
          $value['images'] = Db::name('images')->where('id',$value['image_id'])->value('url');
      }
      
      return json(['code' => 200, 'data' => $result]);
    }
  }


    // 添加购物车
    public function getGoodsGroup(){

      $data = input('data');
  
      if ($data) {
        // 引用模型
        $goodsModel = new goodsModel();
  
        $val = $data;
  
        // 分割字符串
        $data = explode(",", $data);
        // 数组去重
        $data = array_unique($data);
        $result = $goodsModel->where('id', 'in', $data)->field('id,mall_id,site_id,bn,name,price')->orderRaw('field(id,' . $val . ')')->select()->toArray();
  
        return json(['code' => 200, 'data' => $result]);
      }

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


    /**
     * 添加/编辑地址
     * @param $buyerAddressId
     * @param array $condition
     * @return bool
     * @throws ECException
     */
    public function editAddressInfo($buyerAddressId,$condition)
    {
        $areaIds = (new Area())->transformationArea($condition['province'],$condition['city'],$condition['area']);


        $condition['provinceId'] = $areaIds['provinceId'];
        $condition['cityId'] = $areaIds['cityId'];
        $condition['areaId'] = $areaIds['areaId'];

        //获取省市区地址
        $condition['areaInfo'] = $this->getAreaInfo($condition['provinceId'],$condition['cityId'],$condition['areaId'])['areaInfo'];


        // 详细地址
        $detailed = $condition['province'].$condition['city'].$condition['area'].$condition['detailAddress'];

        // 接口路径
        $url = 'http://api.map.baidu.com/geocoding/v3/?address='.$detailed.'&output=json&ak=18EcLqgffCUED5sFHsGRPaqklFn31jVN';
        $geocode=file_get_contents($url);
        $output = json_decode($geocode,true);

        // 经度、纬度
        $longitude = isset($output['result']['location']['lng']) ? $output['result']['location']['lng'] : '';
        $latitude = isset($output['result']['location']['lat']) ? $output['result']['location']['lat'] : '';

        $coordinates = [
            'longitude'  =>  $longitude,
            'latitude'  =>  $latitude,
        ];

        $data = [];
        $data['mall_id'] = $condition['mallId'];
        $data['buyer_id']	= $condition['buyerId'];
        $data['area_id'] = $condition['areaId'];
        $data['buyer_name']	= $condition['buyerNickName'];
        $data['city_id'] = $condition['cityId'];
        $data['province_id'] = $condition['provinceId'];
        $data['area_info'] = $condition['areaInfo'];
        $data['address'] = $condition['detailAddress'];
        $data['floor_num'] =	'';
        $data['tel_phone'] = $condition['buyerMobile'];
        $data['postal_code'] =	'';
        $data['longitude'] =	$coordinates['longitude'];
        $data['latitude']	=	$coordinates['latitude'];
        $data['is_default']	=	0;
        $data['created_at']	= date("Y-m-d H:i:s");
        return (new BuyerAddress())->insertGetId($data);

    }


      
    /**
     * 获取省市区地址
     * @param $provinceId
     * @param $cityId
     * @param $areaId
     * @return array
     * @throws ECException
     */
    public function getAreaInfo($provinceId,$cityId,$areaId){
        //获取省市区地址
        /** @var Area $provinceIns */
        $provinceIns = (new Area())->getCacheAreaInsForId($provinceId);
        /** @var Area $cityIns */
        $cityIns = (new Area())->getCacheAreaInsForId($cityId);
        /** @var Area $areaIns */
        $areaIns = (new Area())->getCacheAreaInsForId($areaId);
        if(empty($provinceIns) || empty($cityIns) || empty($areaIns)){
            throw new ECException('省市区ID已过期:',GlobalErrCode::ERR_DATA_NOT_FOUND);
        }

        return[
            'provinceName'  =>$provinceIns->name,
            'cityName'      =>$cityIns->name,
            'areaName'      =>$areaIns->name,
            'areaInfo'      =>$provinceIns->name.' '.$cityIns->name.' '.$areaIns->name
        ];
    }


    // 省市区联动
    public function getProvinceList(){

      $provinceList = (new Area())->where(['parent_id'=>0])->where(['depth'=>1])->select();

      return ['status'=>true,'data'=>$provinceList];

    }



    // 省市区联动
    public function getCityList(){

      
      $province = input('province');

      $cityList = (new Area())->where(['parent_id'=>$province])->where(['depth'=>2])->select();

      return ['status'=>true,'data'=>$cityList];

    }


    

    // 省市区联动
    public function getAreaList(){

  
      $city = input('city');

      $areaList = (new Area())->where(['parent_id'=>$city])->where(['depth'=>3])->select();

      return ['status'=>true,'data'=>$areaList];

    }

    public function import_photo()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                $id = isset($params['id']) ? $params['id'] : -1;
                $data = isset($params['data']) ? $params['data'] : [];
                //查询创建任务信息单
                $info = MallPhoto::get($id);
                $result = [];
                //解析提交的数据
                if(!empty($data) && is_array($data))
                {
                    foreach ($data as $key=>$val) {
                        //去除数组下标0的组值
                        unset($data[0]);
                    }
                    foreach ($data as $item) {
                        //根据货号查询商品信息
                        $products = Products::get(['sn'=>$item['sn']]);
                        if(!empty($products))
                        {
                            $result[] = [
                                'mall_id'           => $info['mall_id'],
                                'site_id'           => !empty($products['site_id']) ? $products['site_id'] : 0,
                                'buyer_id'          => $info['buyer_id'],
                                'photo_id'          => $id,
                                'goods_id'          => !empty($products['goods_id']) ? $products['goods_id'] : 0,
                                'products_id'       => !empty($products['id']) ? $products['id'] : 0,
                                'goods_num'         => $item['num'],
                                'created_at'        => date("Y-m-d H:i:s"),
                            ];
                        }
                    }
                    Db::startTrans();
                    try{
                        (new BuyerPhoto())->insertAll($result,true);
                        MallPhoto::update(['status'=>2],['id'=>$id]);
                        Db::commit();
                        return ['status'=>true,'msg'=>'导入成功'];
                    }catch (PDOException $e){
                        Db::rollback();
                        return ['status'=>false,'msg'=>'导入失败'];
                    }
                }
                return ['status'=>false,'msg'=>'数据异常'];
            }
            return ['status'=>false,'msg'=>'参数异常'];
        }
        return ['status'=>false,'msg'=>'请求异常'];
    }

}
