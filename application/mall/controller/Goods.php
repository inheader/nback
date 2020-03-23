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
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\Queue;
use app\common\controller\Mall;
use app\common\model\ActFullDelivery;
use app\common\model\ActFullDeliveryGoods;
use app\common\model\AreaMallClassCat;
use app\common\model\AreaMallClassCatGoodsMapping;
use app\common\model\AreaMallSite;
use app\common\model\MallRoleRel;
use app\common\model\OrderProductEvaluate;
use app\common\model\Goods as goodsModel;
use app\common\model\GoodsType;
use app\common\model\GoodsCat;
use app\common\model\Brand;
use app\common\model\GoodsTypeSpec;
use app\common\model\GoodsTypeSpecRel;
use app\common\model\Products;
use app\common\model\GoodsImages;
use app\common\model\Ietask;
use app\common\model\GoodsTypeParams;
use app\common\validate\Goods as GoodsValidate;
use app\common\validate\Products as ProductsValidate;

/***
 * 商品
 * Class Goods
 * @package app\seller\controller
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-11 17:20
 */
class Goods extends Mall
{

    private $spec = [];//规格数组
    static $sku_item;//规格
    static $deep_key;//规格深度
    static $total_item;//总规格

    /**
     * 商品列表
     * @return mixed
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        //获取权限类别
        $authType = $this->getMyAuthType();
        $this->assign('authType', $authType);



        $goodsModel = new goodsModel();
        $statics    = $goodsModel->staticGoods($userWhere);

        $this->assign('statics', $statics);
        $siteType = 1;
        //获取店铺类型
        if($authType==3){
            $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
            $siteInfo = (new AreaMallSite())->siteInfo($siteId);
            $siteType = isset($siteInfo['site_type']) ? $siteInfo['site_type'] : 0;
        }
        $this->assign('siteType', $siteType);
        if (Request::isAjax()) {
            $filter              = input('request.');

            //列表加上权限的筛选
            $filter = array_merge($filter,$userWhere);

            $list = $goodsModel->tableData($filter,true,$this->getMyAuthType());


            $list['data'] = collect($list['data'])->map(function($info)use($siteType){
                $info['price'] = empty($siteType) ? intval($info['price']) : $info['price'] ;
                return $info;
            });

            return $list;
        }
        return $this->fetch('index');
    }

    public function add()
    {
        $this->_common();
        return $this->fetch('add');
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
        $catList       = $goodsCatModel->getCatByParentId(0,$userWhere);
        $this->assign('catList', $catList);
        //类型
        $goodsTypeModel = new GoodsType();
        $typeList       = $goodsTypeModel->getAllTypes(0,$userWhere);
        $this->assign('typeList', $typeList);

        //品牌
        $brandModel = new Brand();
        $brandList  = $brandModel->getAllBrand($userWhere);
        $this->assign('brandList', $brandList);

        hook('goodscommon', $this);//商品编辑、添加时增加钩子

    }

    /**
     * 获取子分类信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCat()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $id = input('post.cat_id/d');
        if ($id) {

            $goodsCatModel = new GoodsCat();
            $catList       = $goodsCatModel->getCatByParentId($id,$userWhere);

            return [
                'data'   => $catList,
                'msg'    => '获取成功',
                'status' => true,
            ];
        } else {
            return [
                'data'   => '',
                'msg'    => '关键参数丢失',
                'status' => false,
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
        //获取权限的筛选条件
        $userWhere = $this->getMyUserAddWhere();

        $result = [
            'status' => false,
            'msg'    => '',
            'data'   => '',
        ];

        //商品数据组装并校检
        $checkData = $this->checkGoodsInfo();
        if (!$checkData['status']) {
            $result['msg'] = $checkData['msg'];
            return $result;
        }
        $data = $checkData['data'];
        //验证商品数据
        $goodsModel    = new goodsModel();
        $productsModel = new Products();
        $goodsModel->startTrans();
        $goods_id = $goodsModel->doAdd($data['goods']);
        if (!$goods_id) {
            $goodsModel->rollback();
            $result['msg'] = '商品数据保存失败';
            return $result;
        }
        $open_spec = input('post.open_spec', 0);
        if ($open_spec) {
            //多规格
            $product     = input('post.product/a', []);
            //初始化参数
            $total_stock = $price = $price1 = $price2 = $price3 = $price4 = $price5 = $price6 = 0;
            $isExitDefalut = false;
            foreach ($product as $key => $val) {
                $tmp_product['goods']['price']        = isset($val['price']) ? $val['price'] : 0;
                //不同会员等级的价格
                $tmp_product['goods']['price1']        = isset($val['price1']) ? $val['price1'] : 0;
                $tmp_product['goods']['price2']        = isset($val['price2']) ? $val['price2'] : 0;
                $tmp_product['goods']['price3']        = isset($val['price3']) ? $val['price3'] : 0;
                $tmp_product['goods']['price4']        = isset($val['price4']) ? $val['price4'] : 0;
                $tmp_product['goods']['price5']        = isset($val['price5']) ? $val['price5'] : 0;
                $tmp_product['goods']['price6']        = isset($val['price6']) ? $val['price6'] : 0;
                $tmp_product['goods']['marketable']   = isset($val['marketable']) ? $val['marketable'] : $productsModel::MARKETABLE_DOWN;
                $tmp_product['goods']['stock']        = isset($val['stock']) ? $val['stock'] : 0;
                $sn                                   = get_sn(4);
                $tmp_product['goods']['sn']           = isset($val['sn']) ? $val['sn'] : $sn;
                $tmp_product['goods']['product_spes'] = $key;
                $tmp_product['goods']['is_defalut']   = isset($val['is_defalut']) ? $productsModel::DEFALUT_YES : $productsModel::DEFALUT_NO;

                if($tmp_product['goods']['is_defalut'] == $productsModel::DEFALUT_YES ){
                    $isExitDefalut = true;
                }
                $checkData                            = $this->checkProductInfo($tmp_product, $goods_id);
                if (!$checkData['status']) {
                    $result['msg'] = $checkData['msg'];
                    $goodsModel->rollback();
                    return $result;
                }
                $data['product'] = $checkData['data']['product'];


                $upProductData = array_merge($data['product'],$userWhere);
                $product_id      = $productsModel->doAdd($upProductData);
                if (!$product_id) {
                    $goodsModel->rollback();
                    $result['msg'] = '货品数据保存失败';
                    return $result;
                }
                $total_stock = $total_stock + $tmp_product['goods']['stock'];
                if ($tmp_product['goods']['is_defalut'] == $productsModel::DEFALUT_YES) {//todo 取商品默认价格
                    $price     = $tmp_product['goods']['price'];
                    $price1     = $tmp_product['goods']['price1'];
                    $price2     = $tmp_product['goods']['price2'];
                    $price3     = $tmp_product['goods']['price3'];
                    $price4     = $tmp_product['goods']['price4'];
                    $price5     = $tmp_product['goods']['price5'];
                    $price6     = $tmp_product['goods']['price6'];
                }
            }
            if(!$isExitDefalut){
                $result['msg'] = '请选择默认货品';
                $goodsModel->rollback();
                return $result;
            }
            //更新总库存
            $upData['stock']     = $total_stock;
            $upData['price']     = $price;
            $upData['price1']     = $price1;
            $upData['price2']     = $price2;
            $upData['price3']     = $price3;
            $upData['price4']     = $price4;
            $upData['price5']     = $price5;
            $upData['price6']     = $price6;

            $upData = array_merge($upData,$userWhere);
            $goodsModel->updateGoods($goods_id, $upData);
        } else {
            $sn                          = get_sn(4);
            $data['goods']['sn']         = input('post.goods.sn', $sn);//货品编码
            $data['goods']['is_defalut'] = $productsModel::DEFALUT_YES;
            //$data['product'] = $checkData['data']['product'];
            $checkData = $this->checkProductInfo($data, $goods_id);

            if (!$checkData['status']) {
                $result['msg'] = $checkData['msg'];
                $goodsModel->rollback();
                return $result;
            }
            $data       = $checkData['data'];

            $productInfo = $data['product'];
            $productInfo = array_merge($productInfo,$userWhere);

            $product_id = $productsModel->doAdd($productInfo);
            if (!$product_id) {
                $goodsModel->rollback();
                $result['msg'] = '货品数据保存失败';
                return $result;
            }
        }
        //保存图片
        if (isset($data['images']) && count($data['images']) > 1) {
            $imgRelData = [];
            $i          = 0;
            foreach ($data['images'] as $key => $val) {
                if ($key == 0) {
                    continue;
                }
                $imgRelData[$i]['goods_id'] = $goods_id;
                $imgRelData[$i]['image_id'] = $val;
                $i++;
            }
            $goodsImagesModel = new GoodsImages();
            if (!$goodsImagesModel->batchAdd($imgRelData, $goods_id)) {
                $goodsModel->rollback();
                $result['msg'] = '商品图片保存失败';
                return $result;
            }
        }
        $goodsModel->commit();

        array_push($data,['goods_id'=>$goods_id]);
        hook('addgoodsafter', $data);//添加商品后增加钩子

        //已经保存完商品了，需要把商品子规格的mallId和siteId加上

        $result['msg']    = '保存成功';
        $result['status'] = true;
        return $result;
    }

    /**
     * 校检并返回商品信息
     * @param bool $isEdit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function checkGoodsInfo($isEdit = false)
    {
        $result                         = [
            'status' => false,
            'msg'    => '',
            'data'   => '',
        ];
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //如果不是店铺权限，那么将不能编辑商品
        if(!(isset($userWhere[self::PERMISSION_SITE]) && !empty($userWhere[self::PERMISSION_SITE]))){
            $result['msg'] = '此账号不是店铺权限，无法编辑商品';
            return $result;
        }
        $siteId = $userWhere[self::PERMISSION_SITE];
        //获取site下面所属的mallId
        $siteInfo = (new AreaMallSite())->siteInfo($siteId);
        $mallId = isset($siteInfo['mall_id']) ? $siteInfo['mall_id'] : 0;

        $bn                             = get_sn(3);
        $data['goods']['name']          = input('post.goods.name', '');
        $goods_cat_id                   = input('post.goods_cat_id/a');
        $goods_cat_id                   = array_filter($goods_cat_id);
        $data['goods']['site_id']       = $siteId;
        $data['goods']['mall_id']       = $mallId;
        $data['goods']['goods_cat_id']  = $goods_cat_id[count($goods_cat_id)-1];
        $data['goods']['goods_type_id'] = input('post.goods_type_id', 0);
        $data['goods']['brand_id']      = input('post.goods.brand_id', 0);
        $data['goods']['bn']            = input('post.goods.bn', $bn);
        $data['goods']['tiaolou']        = input('post.goods.tiaolou', 0);
        $data['goods']['handing_fee']  = input('post.goods.handing_fee', 0);
        $data['goods']['upstairs_fee']  = input('post.goods.upstairs_fee', 0);
        $data['goods']['warehousing_fee']  = input('post.goods.warehousing_fee', 0);
        $data['goods']['logistics_fee']  = input('post.goods.logistics_fee', 0);
        $data['goods']['transport_fee']  = input('post.goods.transport_fee', 0);
        $data['goods']['brief']         = input('post.goods.brief', '');
        $data['goods']['intro']         = input('post.goods.intro', '');
        $data['goods']['price']         = input('post.goods.price', 0);
        $data['goods']['price1']         = input('post.goods.price1', 0);
        $data['goods']['price2']         = input('post.goods.price2', 0);
        $data['goods']['price3']         = input('post.goods.price3', 0);
        $data['goods']['price4']         = input('post.goods.price4', 0);
        $data['goods']['price5']         = input('post.goods.price5', 0);
        $data['goods']['price6']         = input('post.goods.price6', 0);
        $data['goods']['weight']        = input('post.goods.weight', '');
        $data['goods']['stock']         = input('post.goods.stock', '');
        $data['goods']['unit']          = input('post.goods.unit', '');
        $data['goods']['marketable']    = input('post.goods.marketable', '2');
        $data['goods']['is_recommend']  = input('post.goods.is_recommend', '2');
        $data['goods']['is_hot']        = input('post.goods.is_hot', '2');
        $open_spec                      = input('post.open_spec', 0);
        $specdesc                       = input('post.spec/a', []);

        if ($specdesc && $open_spec) {
            if(count($specdesc) == 1){//优化只一个规格的情况
                $product = input('post.product/a',[]);
                foreach((array)$specdesc as $key=>$val){
                    foreach($val as $k=>$v){
                        $temp_product_key  = $key.':'.$v;
                        if(!isset($product[$temp_product_key])){
                            unset($specdesc[$key][$k]);
                        }
                    }
                }
            }
            $data['goods']['spes_desc'] = serialize($specdesc);
        } else {
            $data['goods']['spes_desc'] = '';
        }

        //商品参数处理
        $params     = [];
        $tempParams = input('post.goods.params/a', []);
        if ($tempParams) {
            foreach ($tempParams as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $vk => $vv) {
                        $params[$key][] = $vk;
                    }
                } elseif($val!=='') {
                    $params[$key] = $val;
                }
            }
            $data['goods']['params'] = serialize($params);
        }else{
            $data['goods']['params'] = '';
        }
        $images = input('post.goods.img/a', []);
        if (count($images) <= 0) {
            $result['msg'] = '请先上传图片';
            return $result;
        }
        $data['goods']['image_id'] = $images[0];
        $data['images']            = $images;
        $goodsModel                = new goodsModel();

        if ($isEdit) {
            $data['goods']['id'] = input('post.goods.id/d', 0);
            $validate            = new GoodsValidate();
            if (!$validate->scene('edit')->check($data['goods'])) {
                $result['msg'] = $validate->getError();
                return $result;
            }
        } else {
            $validate = new GoodsValidate();
            if (!$validate->check($data['goods'])) {
                $result['msg'] = $validate->getError();
                return $result;
            }
        }
        $result['data']   = $data;
        $result['status'] = true;
        return $result;
    }

    /**
     * 检查并组装货品数据
     * @param $data
     * @param int $goods_id
     * @param bool $isEdit
     * @return array
     */
    private function checkProductInfo($data, $goods_id = 0, $isEdit = false)
    {
        $result = [
            'status' => false,
            'msg'    => '',
            'data'   => '',
        ];
        if (!$goods_id) {
            $result['msg'] = '商品ID不能为空';
            return $result;
        }
        $productsModel = new Products();
        //单规格
        $data['product']['goods_id']   = $goods_id;
        $data['product']['sn']         = $data['goods']['sn'];//货品编码
        $data['product']['price']      = $data['goods']['price'];//货品价格
        $data['product']['price1']      = $data['goods']['price1'];//会员等级价格
        $data['product']['price2']      = $data['goods']['price2'];//会员等级价格
        $data['product']['price3']      = $data['goods']['price3'];//会员等级价格
        $data['product']['price4']      = $data['goods']['price4'];//会员等级价格
        $data['product']['price5']      = $data['goods']['price5'];//会员等级价格
        $data['product']['price6']      = $data['goods']['price6'];//会员等级价格
        $data['product']['marketable'] = $data['goods']['marketable'];//是否上架
        $data['product']['stock']      = $data['goods']['stock'];//货品库存
        $data['product']['is_defalut'] = $data['goods']['is_defalut'] ? $data['goods']['is_defalut'] : $productsModel::DEFALUT_YES;//是否默认货品
        $open_spec                     = input('post.open_spec', 0);
        if ($open_spec && $data['goods']['product_spes']) {
            $data['product']['spes_desc'] = $data['goods']['product_spes'];
        }
        if ($isEdit) {
            $validate = new ProductsValidate();
            if (!$validate->scene('edit')->check($data['product'])) {
                $result['msg'] = $validate->getError();
                return $result;
            }
        } else {
            $validate = new ProductsValidate();
            if (!$validate->check($data['product'])) {
                $result['msg'] = $validate->getError();
                return $result;
            }
        }

        $result['data']   = $data;
        $result['status'] = true;
        return $result;
    }

    /**
     * @return array
     */
    public function getSpec()
    {
        $result = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $this->view->engine->layout(false);
        $type_id = input('post.type_id');
        if (!$type_id) {
            return $result;
        }
        $goodsTypeModel = new GoodsType();
        $res            = $goodsTypeModel->getTypeValue($type_id);

        $html = '';

        if ($res['status'] == true) {

            $this->assign('typeInfo', $res['data']);
            if (!$res['data']['spec']->isEmpty()) {
                $spec = [];
                foreach ($res['data']['spec']->toArray() as $key => $val) {
                    $spec[$key]['name']      = $val['spec']['name'];
                    $spec[$key]['specValue'] = $val['spec']['getSpecValue'];
                }
                $this->assign('spec', $spec);
            }
            if ($res['data']['spec']->isEmpty()) {
                $this->assign('canOpenSpec', 'false');
            } else {
                $this->assign('canOpenSpec', 'true');
            }
            //获取参数信息
            $goodsTypeParamsModel = new GoodsTypeParams();
            $typeParams           = $goodsTypeParamsModel->getRelParams($type_id);
            $this->assign('typeParams', $typeParams);
            $html             = $this->fetch('getspec');
            $result['status'] = true;
            $result['msg']    = '获取成功';
            $result['data']   = $html;
        }
        return $result;
    }

    /***
     * 生成多规格html
     * @return array
     */
    public function getSpecHtml()
    {
        $result = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $this->view->engine->layout(false);
        $spec         = input('post.spec/a');
        $goods_id     = input('post.goods_id/d', 0);
        $goodsDefault = input('post.goods/a', []);
        $products     = [];
        if ($goods_id) {
            $goodsModel = new goodsModel();
            $goods      = $goodsModel->getOne($goods_id, 'id,image_id');
            if (!$goods['status']) {
                return '商品不存在';
            }
            $products = $goods['data']->products;
        }
        if ($spec) {
            $specValue = [];
            $total     = count($spec);
            foreach ($spec as $key => $val) {
                $this->spec[] = $key;
            }
            $items = $this->getSkuItem($spec, -1);
            foreach ((array)$items as $key => $val) {
                $items[$key]['price']     = $goodsDefault['price'];
                $items[$key]['price1']     = $goodsDefault['price1'];
                $items[$key]['price2']     = $goodsDefault['price2'];
                $items[$key]['price3']     = $goodsDefault['price3'];
                $items[$key]['price4']     = $goodsDefault['price4'];
                $items[$key]['price5']     = $goodsDefault['price5'];
                $items[$key]['price6']     = $goodsDefault['price6'];
                if (isset($goodsDefault['sn']) && $goodsDefault['sn']) {
                    $items[$key]['sn'] = $goodsDefault['sn'] . '-' . ($key + 1);
                }
                $items[$key]['stock'] = $goodsDefault['stock'];
            }

            if ($products) {
                foreach ($items as $key => $val) {
                    foreach ($products as $product) {
                        if ($val['spec_name'] == $product['spes_desc']) {
                            $items[$key]               = array_merge((array)$val, (array)$product);
                            $items[$key]['product_id'] = $product['id'];
                        }
                    }
                }
            }
            $this->assign('items', $items);
        }
        $html             = $this->fetch('getspechtml');
        $result['data']   = $html;
        $result['status'] = true;
        return $result;

    }


    private function getSkuItem($data, $index = -1, $sku_item = [])
    {
        self::$total_item = array();
        if ($index < 0) {
            self::$deep_key = count($data) - 1;
            $this->getSkuItem($data, 0, $sku_item);
        } else {
            if ($index == 0) {
                $first = $data[$this->spec[$index]];

                foreach ($first as $key => $value) {
                    self::$total_item[$key] = array(
                        'spec_name' => $this->spec[$index] . ':' . $value,
                        'spec_key'  => $this->spec[$index],
                    );
                }
            } else {
                $first = $data[$this->spec[$index]];

                if (count($sku_item) >= count($first)) {
                    foreach ($first as $key => $value) {
                        foreach ($sku_item as $s => $v) {

                            self::$total_item[] = array(
                                'spec_name' => $v['spec_name'] . ',' . $this->spec[$index] . ':' . $value,
                                'spec_key'  => $v['spec_key'] . '_' . $this->spec[$index],
                            );
                        }
                    }
                } else {
                    if ($sku_item) {
                        foreach ($sku_item as $key => $value) {
                            foreach ($first as $fkey => $fvalue) {
                                self::$total_item[] = array(
                                    'spec_name' => $value['spec_name'] . ',' . $this->spec[$index] . ':' . $fvalue,
                                    'spec_key'  => $value['spec_key'] . '_' . $this->spec[$index],
                                );
                            }
                        }
                    }
                }
            }
            if ($index < self::$deep_key) {
                $this->getSkuItem($data, $index + 1, self::$total_item);
            }
        }
        return self::$total_item;

    }

    /***
     * 编辑商品
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $goods_id      = input("id");
        $goodsModel    = new goodsModel();
        $productsModel = new Products();
        $goods         = $goodsModel->getOne($goods_id, '*');
        if (!$goods['status']) {
            $this->error("无此商品");
        }
        $this->assign('open_spec', '0');
        $this->assign('data', $goods['data']);
        $this->assign('products', $goods['data']['products']);

        if ($goods['data']['spes_desc'] != '') {
            $this->assign('open_spec', '1');
        } else {
            $this->assign('open_spec', '0');
        }

        //类型
        $goodsTypeModel = new GoodsType();
        $res            = $this->getEditSpec($goods['data']['goods_type_id'], $goods['data']);
        $this->assign('spec_html', $res['data']);

        $goodsCatModel = new GoodsCat();
        $catInfo       = $goodsCatModel->getCatByLastId($goods['data']['goods_cat_id']);
        $catInfo = _krsort($catInfo);
        $this->assign('catInfo',$catInfo);
        $secondCat = $goodsCatModel->getCatByParentId($catInfo[0]['id'],$userWhere);
        $this->assign('secondCat', $secondCat);

        $this->_common();
        return $this->fetch('edit');
    }

    /**
     * 编辑商品
     */
    public function doEdit()
    {
        $result = [
            'status' => false,
            'msg'    => '',
            'data'   => '',
        ];
        //商品数据组装并校检
        $checkData = $this->checkGoodsInfo(true);
        if (!$checkData['status']) {
            $result['msg'] = $checkData['msg'];
            return $result;
        }

        $data = $checkData['data'];
        //验证商品数据
        $goodsModel    = new goodsModel();
        $productsModel = new Products();
        $goodsModel->startTrans();
        $updateRes = $goodsModel->updateGoods($data['goods']['id'], $data['goods']);
        $goods_id  = $data['goods']['id'];
        if ($updateRes === false) {
            $goodsModel->rollback();
            $result['msg'] = '商品数据保存失败';
            return $result;
        }
        $productIds = [];
        $products   = $productsModel->field('id')->where(['goods_id' => $goods_id])->select()->toArray();
        $productIds = array_column($products, 'id');

        $open_spec = input('post.open_spec', 0);

        if ($open_spec) {
            //多规格
            $product     = input('post.product/a', []);
            //初始化参数
            $total_stock = $price = $price1 = $price2 = $price3 = $price4 = $price5 = $price6 = 0;
            $isExitDefalut = false;
            $exit_product = [];
            if(isset($product['id'])&&$product['id']){
                unset($product['id']);
            }

            foreach ($product as $key => $val) {
                $tmp_product['goods']['price']        = !empty($val['price']) ? $val['price'] : 0;
                $tmp_product['goods']['price1']        = !empty($val['price1']) ? $val['price1'] : 0;
                $tmp_product['goods']['price2']        = !empty($val['price2']) ? $val['price2'] : 0;
                $tmp_product['goods']['price3']        = !empty($val['price3']) ? $val['price3'] : 0;
                $tmp_product['goods']['price4']        = !empty($val['price4']) ? $val['price4'] : 0;
                $tmp_product['goods']['price5']        = !empty($val['price5']) ? $val['price5'] : 0;
                $tmp_product['goods']['price6']        = !empty($val['price6']) ? $val['price6'] : 0;
                $tmp_product['goods']['marketable']   = !empty($val['marketable']) ? $val['marketable'] : $productsModel::MARKETABLE_UP;
                $tmp_product['goods']['stock']        = !empty($val['stock']) ? $val['stock'] : 0;
                $sn                                   = get_sn(4);
                $tmp_product['goods']['sn']           = !empty($val['sn']) ? $val['sn'] : $sn;
                $tmp_product['goods']['product_spes'] = $key;
                $tmp_product['goods']['is_defalut']   = !empty($val['is_defalut']) ? $productsModel::DEFALUT_YES : $productsModel::DEFALUT_NO;
                if($tmp_product['goods']['is_defalut'] == $productsModel::DEFALUT_YES ){
                    $isExitDefalut = true;
                }

                if (isset($val['id'])) {
                    $tmp_product['product']['id'] = $val['id'];
                    $checkData                    = $this->checkProductInfo($tmp_product, $goods_id, true);
                } else {
                    unset($tmp_product['product']['id']);
                    $checkData = $this->checkProductInfo($tmp_product, $goods_id);
                }
                if (!$checkData['status']) {
                    $result['msg'] = $checkData['msg'];
                    $goodsModel->rollback();
                    return $result;
                }
                $data['product'] = $checkData['data']['product'];

                if (isset($val['id'])&&$val['id']) {
                    $productRes = $productsModel->updateProduct($val['id'], $data['product']);
                    if (in_array($val['id'], $productIds)) {
                        $productIds = unsetByValue($productIds, $val['id']);
                    }
                    if($val['id']){
                        $exit_product[] = $val['id'];
                    }
                } else {
                    unset($data['product']['id']);
                    $productRes = $productsModel->doAdd($data['product']);
                    if(is_numeric($productRes)){
                        $exit_product[] = $productRes;
                    }
                }
                if ($productRes === false) {
                    $goodsModel->rollback();
                    $result['msg'] = '货品数据保存失败';
                    return $result;
                }

                $total_stock = $total_stock + $tmp_product['goods']['stock'];
                if ($tmp_product['goods']['is_defalut'] == $productsModel::DEFALUT_YES) {//todo 取商品默认价格
                    $price     = $tmp_product['goods']['price'];
                    $price1     = $tmp_product['goods']['price1'];
                    $price2     = $tmp_product['goods']['price2'];
                    $price3     = $tmp_product['goods']['price3'];
                    $price4     = $tmp_product['goods']['price4'];
                    $price5     = $tmp_product['goods']['price5'];
                    $price6     = $tmp_product['goods']['price6'];
                }
            }

            if(!$isExitDefalut){
                $result['msg'] = '请选择默认货品';
                $goodsModel->rollback();
                return $result;
            }
            //更新总库存
            $upData['stock']     = $total_stock;
            $upData['price']     = $price;
            $upData['price1']     = $price1;
            $upData['price2']     = $price2;
            $upData['price3']     = $price3;
            $upData['price4']     = $price4;
            $upData['price5']     = $price5;
            $upData['price6']     = $price6;
            $goodsModel->updateGoods($goods_id, $upData);
            //删除多余规格
            $productsModel->where([['id','not in',$exit_product],['goods_id','=',$goods_id]])->delete();
        } else {
            $sn                          = get_sn(4);
            $data['goods']['sn']         = input('post.goods.sn', $sn);//货品编码
            $data['goods']['is_defalut'] = $productsModel::DEFALUT_YES;
            //$data['product'] = $checkData['data']['product'];
            $data['product']['id'] = input('post.product.id/d', 0);
            if ($data['product']['id']) {
                $checkData = $this->checkProductInfo($data, $goods_id, true);
            } else {
                $checkData = $this->checkProductInfo($data, $goods_id);
            }
            if (!$checkData['status']) {
                $result['msg'] = $checkData['msg'];
                $goodsModel->rollback();
                return $result;
            }
            $data = $checkData['data'];

            if ($data['product']['id']) {
                if (in_array($data['product']['id'], $productIds)) {
                    $productIds = unsetByValue($productIds, $data['product']['id']);
                }
                $updateRes = $productsModel->updateProduct($data['product']['id'], $data['product']);
            } else {
                $updateRes = $productsModel->doAdd($data['product']);
            }

            if ($updateRes === false) {
                $goodsModel->rollback();
                $result['msg'] = '货品数据保存失败';
                return $result;
            }
        }
        //删除多余货品数据
        if ($productIds) {
            $productsModel->deleteProduct($productIds);
        }
        //保存图片
        if (isset($data['images']) && count($data['images']) >= 1) {
            $imgRelData = [];
            $i          = 0;
            foreach ($data['images'] as $key => $val) {
                if ($key == 0) {
                    continue;
                }
                $imgRelData[$i]['goods_id'] = $goods_id;
                $imgRelData[$i]['image_id'] = $val;
                $i++;
            }
            $goodsImagesModel = new GoodsImages();
            if (!$goodsImagesModel->batchAdd($imgRelData, $goods_id)) {
                $goodsModel->rollback();
                $result['msg'] = '商品图片保存失败';
                return $result;
            }
        }
        $goodsModel->commit();
        hook('editgoodsafter', $data);//编辑商品后增加钩子
        $result['msg']    = '保存成功';
        $result['status'] = true;
        return $result;
    }

    /**
     * 商品删除
     * @return array
     */
    public function del()
    {
        $result     = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        $goods_id   = input("id");
        $goodsModel = new goodsModel();
        if (!$goods_id) {
            return $result;
        }
        $delRes = $goodsModel->delGoods($goods_id);
        if (!$delRes['status']) {
            $result['msg'] = $delRes['msg'];
            return $result;
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
    }

    private function getEditSpec($type_id, $goods)
    {

        $result = [
            'status' => false,
            'msg'    => '关键参数丢失',
            'data'   => '',
        ];
        if (!$type_id) {
            return $result;
        }
        $spes_desc = unserialize($goods['spes_desc']);
        $this->assign('goods', $goods);
        $goodsTypeModel = new GoodsType();
        $res            = $goodsTypeModel->getTypeValue($type_id);

        $html = '';

        if ($res['status'] == true) {

            $this->assign('typeInfo', $res['data']);
            if (!$res['data']['spec']->isEmpty()) {
                $spec = [];
                foreach ($res['data']['spec']->toArray() as $key => $val) {
                    $spec[$key]['name']      = $val['spec']['name'];
                    $spec[$key]['specValue'] = $val['spec']['getSpecValue'];
                    if ($spes_desc) {
                        foreach ((array)$spec[$key]['specValue'] as $vkey => $vval) {
                            $spec[$key]['specValue'][$vkey]['isSelected'] = 'false';
                            foreach ($spes_desc as $gk => $gv) {
                                foreach ($gv as $v) {
                                    if ($v == $vval['value']) {
                                        $spec[$key]['specValue'][$vkey]['isSelected'] = 'true';
                                    }
                                }

                            }

                        }
                    }

                }
                $this->assign('spec', $spec);
            }

            if ($res['data']['spec']->isEmpty()) {
                $this->assign('canOpenSpec', 'false');
            } else {
                $this->assign('canOpenSpec', 'true');
            }
            //获取参数信息
            $goodsTypeParamsModel = new GoodsTypeParams();
            $typeParams           = $goodsTypeParamsModel->getRelParams($type_id);
            $this->assign('typeParams', $typeParams);
            //解析参数信息
            $params = [];
            if ($goods['params']) {
                $params = unserialize($goods['params']);
            }

            $this->assign('goodsParams', $params);
            $items = [];
            if ($spes_desc) {
                $specValue = [];
                $total     = count($spes_desc);
                foreach ($spes_desc as $key => $val) {
                    $this->spec[] = $key;
                }
                $items = $this->getSkuItem($spes_desc, -1);
                //循环货品
                foreach ($goods['products'] as $product) {
                    foreach ($items as $key => $ispec) {
                        if ($ispec['spec_name'] == $product['spes_desc']) {
                            $items[$key]               = array_merge((array)$ispec, (array)$product);
                            $items[$key]['product_id'] = $product['id'];
                        }
                    }
                }
            } else {
                $this->assign('product', $goods['products'][0]);
            }
            $this->assign('items', $items);
//            dump($items);
            $this->view->engine->layout(false);
            $html = $this->fetch('editgetspechtml');
            $this->view->engine->layout(true);
            $result['status'] = true;
            $result['data']   = $html;
        }
        return $result;
    }


    /**
     * 后台评价列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function commentList()
    {
        if (!Request::isAjax()) {
            $goods_id = input('goods_id');
            $this->assign('goods_id', $goods_id);
            return $this->fetch('commentlist');
        } else {
            $goods_id = input('goods_id');
            $page     = input('page', 1);
            $limit    = input('limit', 10);
            $res      = (new OrderProductEvaluate())->getList($goods_id, $page, $limit);

            $return_data = [
                'status' => true,
                'msg'    => '获取评价成功',
                'count'  => $res['count'],
                'data'   => $res['list'],
            ];
            return $return_data;
        }
    }


    /**
     * 获取单条评价
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentInfo()
    {
        $id  = input('id');
        $res = (new OrderProductEvaluate())->getCommentInfo($id);
        return $res;
    }


    /**
     * 商家回复
     * @return mixed
     */
    public function sellerContent()
    {
        $id             = input('id');
        $evaluateRemark = input('evaluateRemark');
        $res            = (new OrderProductEvaluate())->sellerComment($id, $evaluateRemark);
        return $res;
    }


    /**
     * 显示不显示
     * @return mixed
     */
    public function setDisplay()
    {
        $id  = input('id');
        $res = model('common/GoodsComment')->setDisplay($id);
        return $res;
    }

    /**
     * 批量上下架
     * @return array
     */
    public function batchMarketable()
    {
        $result = [
            'status' => false,
            'data'   => [],
            'msg'    => '参数丢失',
        ];
        $ids    = input('ids/a', []);
        $type   = input('type/s', 'up');
        if (count($ids) <= 0) {
            return $result;
        }
        $goodsModel = new goodsModel();
        $res        = $goodsModel->batchMarketable($ids, $type);
        if ($res !== false) {
            $result['status'] = true;
            $result['msg']    = '操作成功';
        } else {
            $result['msg'] = '操作失败';
        }
        return $result;
    }

    /**
     * 批量删除商品
     * TODO:删除商品暂时没有(hebo)
     * @return array
     */
    public function batchDel()
    {
        $result = [
            'status' => false,
            'data'   => [],
            'msg'    => '参数丢失',
        ];
        $ids    = input('ids/a', []);
        if (count($ids) <= 0) {
            return $result;
        }
        $goodsModel = new goodsModel();
        foreach ($ids as $goods_id) {
            $delRes = $goodsModel->delGoods($goods_id);
            if (!$delRes['status']) {
                $result['msg'] = $delRes['msg'];
                return $result;
            }
        }
        $result['status'] = true;
        $result['msg']    = '删除成功';
        return $result;
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
        if(!empty($areaId)){
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
     * 更改状态
     * @return array
     */
    public function changeState()
    {
        $result = [
            'status' => false,
            'data'   => [],
            'msg'    => '参数丢失',
        ];
        $id     = input('post.id/d', 0);
        $state  = input('post.state/s', 'true');
        $type   = input('post.type/s', 'hot');

        if (!$id) {
            return $result;
        }
        $iData = [];
        if ($state == 'true') {
            $state = '1';
        } else {
            $state = '2';
        }
        if ($type == 'hot') {
            $iData['is_hot'] = $state;
        } elseif ($type == 'rec') {
            $iData['is_recommend'] = $state;
        }
        if (!$iData) {
            return $result;
        }
        $goodsModel = new goodsModel();
        if ($goodsModel->save($iData, ['id' => $id])) {
            $result['msg']    = '设置成功';
            $result['status'] = true;
        } else {
            $result['msg']    = '设置失败';
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * 更新排序
     * @return array
     */
    public function updateSort()
    {
        $result = [
            'status' => false,
            'data'   => [],
            'msg'    => '参数丢失',
        ];
        $field  = input('post.field/s');
        $value  = input('post.value/d');
        $id     = input('post.id/d', '0');
        if (!$field || !$value || !$id) {
            $result['msg']    = '参数丢失';
            $result['status'] = false;
        }
        $goodsModel = new goodsModel();


        if($field=='page_recommend_num'){
            //获取权限的筛选条件
            $userWhere = $this->getMyUserWhere();
            //这里只有区域管理员可以设置
            $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
            if(empty($areaId)){
                return [
                    'status' => false,
                    'msg' => '只有区域管理员更新首页推荐参数'
                ];
            }
            $info = $goodsModel->where('id',$id)->find();
            if($info['mall_id']!=$areaId){
                return [
                    'status' => false,
                    'msg' => '没有权限编辑此店铺的商品'
                ];
            }

        }

        if ($goodsModel->updateGoods($id, [$field => $value])) {
            $result['msg']    = '更新成功';
            $result['status'] = true;
        } else {
            $result['msg']    = '更新失败';
            $result['status'] = false;
        }
        return $result;
    }

    private function checkMallCanEdit()
    {
        $siteModel = new AreaMallSite();
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里说明是区域权限
        $mallId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        $info = $siteModel->where('site_id',$siteId)->find();
        return $info['mall_id']==$mallId;
    }

    /**
     * 商品列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function GoodsListForPage()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        //这里只有区域管理员可以设置，这里先注释掉
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            $this->error('只有店铺管理员才有权限打开此界面');
        }


        $goodsModel = new goodsModel();
        $filter              = input('request.');

        $actId = isset($filter['actId']) ? $filter['actId'] : 0;


        if (Request::isAjax()) {
            $filter              = input('request.');

            $actId = isset($filter['actId']) ? $filter['actId'] : 0;

            //查看活动的商品列表
            $actModel = (new ActFullDelivery());
            $actInfo = $actModel->getActInfo($userWhere,$actId);

            if(isset($actInfo['isAllGoods']) && $actInfo['isAllGoods']==1){
                //这里说明是全部的商品，不需要筛选商品
                $this->assign('isAllGoods', 1);//这里前台需要做处理
            }else{
                //这里是部分商品，需要筛选
                $filter['id'] = (new ActFullDeliveryGoods())->getGoodsListForAct($actId);
            }

            //列表加上权限的筛选
            $filter = array_merge($filter,$userWhere);

            $this->assign('actId', $actId);

            $list = $goodsModel->tableData($filter,true,$this->getMyAuthType());

            $list['isAllGoods']=1;
            return $list;
        }


            $this->assign('actId', $actId);

//        $this->assign('goodsList', $list);

        return $this->fetch('goodslistforpage');
    }

    public function AddGoodsListForPage()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $goodsModel = new goodsModel();
        $filter              = input('request.');

        $actId = isset($filter['actId']) ? $filter['actId'] : 0;

        $this->assign('actId', $actId);
        $this->view->engine->layout(false);
        return $this->fetch('addgoodslistforpage');
    }


    public function GoodsListForQuickChange()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $goodsModel = new goodsModel();
        $filter              = input('request.');

        $quickId = isset($filter['quickId']) ? $filter['quickId'] : 0;

        //查看这个快速选材所能选择的店铺
        $siteList = (new AreaMallClassCat())->getThisSiteList($quickId);
        $this->assign('siteList', $siteList);
        //查看这个快速选材所能选择的品牌
        $brandList = (new Brand())->getAreaSiteBrand(['mall_id'=>$userWhere['mall_id']]);
        $this->assign('brandList', $brandList);

        $this->assign('quickId', $quickId);
        $this->view->engine->layout(false);
        return $this->fetch('addgoodslistforquick');
    }


    //读取商品
    public function getCatGoodsList()
    {
        $this->view->engine->layout(false);
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $filter              = input('request.');
        $quickId = isset($filter['quickId']) ? $filter['quickId'] : 0;
        $this->assign('quickId', $quickId);

        return $this->fetch('getcatgoodslist');

    }


    /**
     * @throws Exception\DbException
     */
    public function getQuickGoodsList()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if($this->request->isAjax())
        {
            $id = $this->request->get('quickId');
            $page  = $this->request->get('page');
            $limit = $this->request->get('limit');
            $name  = $this->request->get('name');

            $goodsModel = new goodsModel();

            $filter  = (new AreaMallClassCatGoodsMapping())
                ->where('amcc_id',$id)
                ->field('spc_id,tags')
                ->select();
            $ids = '';
            foreach ($filter as $key=>$val) {
                $ids .= $val['spc_id'].',';
            }
            $ids = rtrim($ids,',');
            $where = [];
            if (isset($userWhere['mall_id']) && $userWhere['mall_id'] != "") {
                $where[] = ['mall_id', 'eq', $userWhere['mall_id']];
            }

            if(isset($name) && !empty($name))
            {
                $where['name'] = ['name','LIKE','%'.$name.'%'];
            }

            if (isset($filter) && $filter != "") {
                $where[] = ['id', 'in', $ids];
            }

            $list = $goodsModel->where($where)->page($page,$limit)->field('id,image_id,name,brand_id,price,stock')->select();

            foreach ($list as $key=>$val) {
                $list[$key]['amcc_id'] = $id;
                $list[$key]['image'] = _sImage($val['image_id']);
                $list[$key]['brand_name'] = Db::name('brand')->where('id',$val['brand_id'])->find()['name'];
                $list[$key]['tag'] = Db::name('area_mall_class_cat_goods_mapping')->where(['amcc_id'=>$id,'spc_id'=>$val['id']])->find()['tags'];
                $list[$key]['sort'] = Db::name('area_mall_class_cat_goods_mapping')->where(['amcc_id'=>$id,'spc_id'=>$val['id']])->find()['sort'];
            }

            $total = $goodsModel->where($where)->count();

            return ['code'=>0,'count'=>$total,'data'=>$list];

        }
        return [];
    }

    /**
     * 移除分类下的商品
     * @return array
     * @throws Exception
     */
    public function removeQuickGoods()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if($this->request->isAjax())
        {
            $id = $this->request->param('id');

            try{
                $goodsMapping = new AreaMallClassCatGoodsMapping();
                $goodsMapping->where('spc_id',$id)->delete();
                return ['status'=>true,'msg'=>'移除成功'];
            }catch (PDOException $e){
                return ['status'=>false,'msg'=>'移除失败'];
            }

        }
        return ['status'=>false,'msg'=>'请求失败'];
    }

    /**
     * 批量移除商品
     * @return array
     * @throws Exception
     */
    public function removeQuickGoodsMulti($ids=null)
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        if($this->request->isAjax())
        {
            $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');

            $ids = rtrim($ids,',');

            try{
                $goodsMapping = new AreaMallClassCatGoodsMapping();
                $goodsMapping->whereIn('spc_id',$ids)->delete();
                return ['status'=>true,'msg'=>'移除成功'];
            }catch (PDOException $e){
                return ['status'=>false,'msg'=>'移除失败'];
            }

        }
//        return ['status'=>false,'msg'=>'请求失败'];
    }

    /**
     * 添加商品数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function AddQuickGoodsList()
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
        $goodsModel = new goodsModel();
        $filter              = input('request.');
        $quickId = isset($filter['quickId']) ? $filter['quickId'] : 0;

        //查看这个快速选材所能选择的店铺
        $siteIdList = (new AreaMallClassCat())->getThisSites($quickId);
        $filter['inSite']  = $siteIdList;
        $filter['marketable']  = 1;

        //将店铺的其他活动添加的商品也需要隐藏
        $filter['notId']  = (new AreaMallClassCatGoodsMapping())->getGoodsIdsForQuick($quickId);

        //列表加上权限的筛选
        $filter = array_merge($filter,$userWhere);

        $this->assign('quickId', $quickId);

        $list = $goodsModel->tableData($filter,true,$this->getMyAuthType());
        return $list;
    }

    /**
     * 添加商品数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function AddActGoodsList()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $goodsModel = new goodsModel();
        $filter              = input('request.');
        $actId = isset($filter['actId']) ? $filter['actId'] : 0;

        //将店铺的其他活动添加的商品也需要隐藏
        $filter['notId']  = (new ActFullDeliveryGoods())->getGoodsListForSiteId($userWhere);

        //列表加上权限的筛选
        $filter = array_merge($filter,$userWhere);

        $this->assign('actId', $actId);

        $list = $goodsModel->tableData($filter,true,$this->getMyAuthType());
        return $list;
    }


}
