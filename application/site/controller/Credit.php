<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序商城8888888 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Site\controller;

use Request;
use think\Exception;
use think\exception\PDOException;
use think\Queue;
use think\Db;
use app\common\controller\Site;
use app\common\model\ActFullDelivery;
use app\common\model\ActFullDeliveryGoods;
use app\common\model\AreaMallClassCat;
use app\common\model\AreaMallClassCatGoodsMapping;
use app\common\model\AreaMallSite;
use app\common\model\ManageRoleRel;
use app\common\model\OrderProductEvaluate;
use app\common\model\Credit as creditModel;
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
 * 赊账
 * Class Credit
 * @package app\seller\controller
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-11 17:20
 */
class Credit extends Site
{

    private $spec = [];//规格数组
    static $sku_item;//规格
    static $deep_key;//规格深度
    static $total_item;//总规格

    protected $CreditModel = null;

    protected $creditList = null;

    protected $creditLines = null;

    protected $creditFlow = null;

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $this->CreditModel = new creditModel();

        $this->creditList = model('Credit');

        $this->creditLines = model('CreditLines');

        $this->creditFlow = model('CreditFlow');

        $status = $this->CreditModel->statusCredit($userWhere);

        $this->assign('status', $status);
    }

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

        $siteType = 1;

        // 获取店铺类型
        if($authType==3){
            $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
            $siteInfo = (new AreaMallSite())->siteInfo($siteId);
            $siteType = isset($siteInfo['site_type']) ? $siteInfo['site_type'] : 0;
        }

        $this->assign('siteType', $siteType);

        if (Request::isAjax()) {

            $filter = input('request.');
            //列表加上权限的筛选
            $filter = array_merge($filter,$userWhere);
            $list = $this->CreditModel->tableData($filter,true,$this->getMyAuthType());
       
            $list['data'] = collect($list['data'])->map(function($info)use($siteType){
                $info['price'] = empty($siteType) ? intval($info['price']) : $info['price'] ;
                return $info;
            });

            return $list;
        }
        return $this->fetch('index');
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
     * 校检并返回商品信息
     * @param bool $isEdit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function checkCreditInfo($isEdit = false)
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
        $data['goods']['name']          = input('post.goods.name', '');
        $goods_cat_id                   = input('post.goods_cat_id/a');
        $goods_cat_id                   = array_filter($goods_cat_id);
        $data['goods']['site_id']       = $siteId;
        $data['goods']['mall_id']       = $mallId;
        $data['goods']['goods_cat_id']  = $goods_cat_id[count($goods_cat_id)-1];
        $data['goods']['brand_id']      = input('post.goods.brand_id', 0);
        $data['goods']['bn']            = input('post.goods.bn', $bn);
        $data['goods']['tiaolou']        = input('post.goods.tiaolou', 0);
        $data['goods']['handing_fee']  = input('post.goods.handing_fee', 0);
        $data['goods']['brief']         = input('post.goods.brief', '');
        $data['goods']['is_recommend']  = input('post.goods.is_recommend', '2');
        $data['goods']['is_hot']        = input('post.goods.is_hot', '2');


        //商品参数处理
        $params     = [];
        $tempParams = input('post.goods.params/a', []);
        $creditModel = new creditModel();
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

	
	
	
	/*
	 * 获取商品sku
	 * param array $data
	 * param int $index
	 * param int $sku_item
	 * return array
	 */
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
     * 查看信息
     * @return mixed
     */
    public function look(){

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $id = input("id");
        $creditModel = new creditModel();
        // 查询数据
        $data = $creditModel->getOne($id, '*');
        if (!$data['status']) {
            $this->error("未找到数据");
        }
 
        // 数据赋值到模板
        $this->assign('data', $data['data']);
        $this->_common();
        return $this->fetch('look');
    }

    /***
     * 审核信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $id = input("id");
        $creditModel = new creditModel();
        // 查询数据
        $data = $creditModel->getOne($id, '*');
        if (!$data['status']) {
            $this->error("未找到数据");
        }
        // var_dump(config('params.credit.status'));
        // 数据赋值到模板
        $this->assign('data', $data['data']);
        $this->_common();
        return $this->fetch('edit');
    }

    /**
     * 审批赊账
     */
    public function doCredit(){
        // 判断提交
        if(Request::isAjax()){

            $data = \think\facade\Request::param('');

            $id = $data['data']['id'];
            $buyer_id = $data['data']['buyer_id'];
            $status = $data['data']['schedule'];
            $remark = $data['data']['remarks'];
//            $actual_money = isset($data['data']['actual_money']) ? $data['data']['actual_money'] : 0;
            $mall_id = $data['data']['mall_id'];
            $site_id = $data['data']['site_id'];

            if(!empty($data['data']) && !empty($id) && !empty($buyer_id)){
                    // 启动事务
                    Db::startTrans();
                    try{

                        if(!empty($buyer_id)){

                            $arr = [
                                'schedule' => $status,
                                'remarks' => $remark,
                                'updated_at' => date("Y-m-d H:i:s"),
                            ];

                            $result = Db::name('credit_list')->where('id', $id)->update($arr);

//                            // 操作用户额度表
//                            // $lines = Db::name('credit_lines')->insert(['mall_id' => $auths['mall_id'], 'site_id' => $auths['site_id'], 'buyer_id' => $buyer_id,'balance' => $actual_money, 'remark' => '申请审批赊账']);
//                            // 操作用户额度表
//                            $dataLines = [
//                                'mall_id' => $mall_id,
//                                'site_id' => $site_id,
//                                'buyer_id' => $buyer_id,
//                                'balance'   => $actual_money,
//                                'remark'    => '申请审批赊账',
//                                'created_at'    => date("Y-m-d H:i:s"),
//                                'updated_at'    => date("Y-m-d H:i:s"),
//                            ];
//
//                            $lines = Db::name('credit_lines')->insert($dataLines);
//
//                            // 操作积分流水
//                            $flow = [
//                                'mall_id' => $mall_id,
//                                'site_id' => $site_id,
//                                'buyer_id' => $buyer_id,
//                                'type' => 1,
//                                'num' => $actual_money,
//                                'balance' => $actual_money,
//                                'created_at'    => date("Y-m-d H:i:s"),
//                                'updated_at'    => date("Y-m-d H:i:s"),
//                            ];
//
//
//                            $credit = Db::name('credit_flow')->insert($flow);

                            // 提交事务
                            Db::commit();

                            if($result){
                                return json([
                                    'status' => true,
                                    'msg' => '操作成功',
                                    'data' => $result
                                ]);
                            }

                        }

                    }catch ( Exception $e){
                        // 回滚事务
                        Db::rollback();
                        return ['status'=>false,'msg'=>'操作失败'];
                    }

            }
        }
    }


    /**
     * 二审审核
     * @param null $id
     * @return array|mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function step2($id=null)
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $id = isset($id) ? $this->request->get('id') : 0;
        $find = Db::name('credit_list')->where('id',$id)->find();

        if($this->request->isAjax())
        {
            $data = $this->request->param();
            $id = $data['data']['id'];
            $buyer_id = $data['data']['buyer_id'];
            $status = $data['data']['schedule'];
            $remark = $data['data']['remarks'];
            $mall_id = $data['data']['mall_id'];
            $site_id = $data['data']['site_id'];
            if(!empty($data['data']) && !empty($id) && !empty($buyer_id))
            {
                Db::startTrans();
                try{
                    $arr = [
                        'schedule' => $status,
                        'remarks' => $remark,
                        'updated_at' => date("Y-m-d H:i:s"),
                    ];

                    if($status == 6)
                    {
                        $arr['status'] = 2;
                    }

                    //更新审核
                    Db::name('credit_list')->where('id',$id)->update($arr);

                    //生成PDF
                    $findInfo = Db::name('credit_list')->where(['site_id'=>$site_id,'buyer_id'=>$buyer_id])->find();
                    $this->createPdf($findInfo);
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作成功'];
                }
            }
            return [];

        }

        $this->assign('credit',$find);

        return $this->fetch();
    }

    /**
     * @param null $id
     * @return array|mixed
     */
    public function step3($id=null)
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $id = isset($id) ? $id : $this->request->get('id');
        $find = Db::name('credit_list')->where('id',$id)->find();

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {

                $id = $params['data']['id'];
                $buyer_id = $params['data']['buyer_id'];
                $status = $params['data']['schedule'];
                $remark = $params['data']['remarks'];
                $mall_id = $params['data']['mall_id'];
                $site_id = $params['data']['site_id'];

                if($params['data']['actual_money'] <= 0) return ['status'=>false,'msg'=>'金额不能小于或者等于0'];

                if(!empty($buyer_id) && !empty($site_id)) {

                    Db::startTrans();
                    try {

                        $arr = [
                            'schedule' => $status,
                            'actual_money' => $params['data']['actual_money'],
                            'remarks' => $remark,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s"),
                        ];

                        if($status == 9)
                        {
                            $arr['status'] = 2;
                        }else{
                            $arr['status'] = 3;
                        }
                        //更新审核
                        $this->creditList->allowField(true)->isUpdate(true)->save($arr,['site_id'=>$site_id,'buyer_id'=>$buyer_id]);
                        //写入用户赊账余额
                        $credit = [
                            'mall_id' => $mall_id,
                            'site_id' => $site_id,
                            'buyer_id' => $buyer_id,
                            'balance' => $params['data']['actual_money'],
                            'remark' => '审批赊账申请',
                            'updated_at' => date("Y-m-d H:i:s"),

                        ];
                        $this->creditLines->insert($credit);

                        //写入流水
                        $flow = [
                            'mall_id' => $mall_id,
                            'site_id' => $site_id,
                            'buyer_id' => $buyer_id,
                            'type' => 1,
                            'price' => isset($params['data']['actual_money']) ? $params['data']['actual_money'] : 0.00,
                            'balance' => isset($params['data']['actual_money']) ? $params['data']['actual_money'] : 0.00,
                            'remarks' => '审批赊账',
                            'created_at' => date("Y-m-d H:i:s"),
                        ];
                        $this->creditFlow->insert($flow,true);

                        Db::commit();
                        return ['status' => true, 'msg' => '操作成功'];
                    } catch (PDOException $e) {
                        Db::rollback();
                        return ['status' => false, 'msg' => '操作失败'];
                    }
                }
            }
        }

        $this->assign('credit',$find);

        return $this->fetch();
    }


    /**
     * 创建PDF
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function createPdf($data)
    {
        //搭建生成模型
        $html =<<<EOD
<style>
    .a{
        text-align: center;
    }
</style>
<div>
<h1 class="a">店铺信用赊购合同</h1>
<p>赊购单位：<u>　    {$data['company']}　  </u>(以下简称甲方)</p>
<p>法定代表人：<u>　    {$data['username']}　  </u></p>
<p>通讯地址：<u>　    {$data['address']}　  </u></p>
<p>供货单位：<u> 常州佩祥电子商务有限公司 </u>(以下简称乙方)</p>
<p>代理人：______________
    <p>　　鉴于乙方是一家注册在常州钟楼区专注于家装材料的电商平台企业，其平台 入口为：微信小程序：佩祥；甲方是装修行业的从业企业，为了建立甲乙双方长 期合作关系和明确责任，确保双方实现各自的经济目的，经甲乙双方充分协商一 致，特订立本合同，以便双方共同遵守。</p>
</p>
<p><b>第 1 条：下单平台</b></p>
<p>1.1：下单平台是指乙方提供的“佩祥”微信小程序，订单结算平台。 </p>
<p>1.2：甲方在下单平台上指定以下账号（微信号 <u>　    {$data['wx_code']}　  </u> 关联手机号 <u>　    {$data['phone']}　  </u> ）为唯一的下单、结算和其他操作账号。 </p>
<p>1.3：甲方在下单平台上向乙方采购商品，甲方对下单平台的各项规定、注意事 项和提示已经完全了解并同意，对指定账号的各项下单、结算、对账、接受电子 文书和其他操作均予以认可，指定账号有变化的，甲方应立即书面通知乙方，因 通知不及时造成的后果由甲方承担。</p>
<p><b>第 2 条：商品的名称、品牌、规格、数量、单价</b></p>
<p>2.1：按照下单平台的购物须知，甲方采购商品、服务的数量和报价以下单平台 上实时订单为准，含商品、服务详细信息和可能产生的其他费用等。 </p>
<p>2.2：甲方在下单平台上下单的订单号为该笔交易的唯一编号。</p>
<p><b>第 3 条：商品的质量标准 </b></p>
<p>3.1：按下列（1、2）执行： 
<p>1) 按国家、行业有关标准执行； </p>
<p>2) 乙方保证经营商品的质量，并提供质量检测相关资料。 </p>
</p>
<p>3.2：甲方在放置、使用商品的过程中造成损坏，责任自负，乙方不承担责任。 </p>
<p><b>第 4 条：商品的送货和验收 </b></p>
<p>4.1：根据甲方订单确认的送货地点和要求，乙方按时送达商品并现场拍照留存， 更新“商品已送达” 信息到甲方订单中。</p>
<p>4.2：甲方应当在 “商品已送达”信息更新后的 7 个自然日内，在下单平台的订 单中确认商品已收到。 </p>
<p>4.3：如甲方对收到的商品有异议，应当在 4.2 规定的 7 个自然日内向乙方书面 提出异议，乙方提供现场照片供甲方核对。</p>
<p>4.4：如甲方在 4.2 规定的 7 个自然日内未确认收货，又未向乙方书面提出异议， 则视为甲方已经收到全部商品，并且对质量没有任何异议，下单平台上自动更新 “商品已签收”信息到甲方订单中。 </p>
<p><b>第 5 条：商品的货款结算 </b></p>
<p>5.1：商誉赊账：甲方选择商誉赊账，乙方通过下单平台后台为甲方指定账号预 充值初始赊购额度 <u>2</u> 万元，大写 <u>贰萬元整</u> ，该额度仅用 于赊购下单平台货品，甲方在下单平台上确认后，赊购额度进入甲方指定账号。 </p>
<p>5.2：：乙方每月将电子对账单发送到甲方指定账号的微信号供甲方核对，甲方有 异议的 5 个自然日内在微信号中提出，双方校对，逾期视为没有异议；甲方没有
常州佩祥电子商务有限公司 2
异议的签署对账确认回执，应于当月 20 日前将上月赊购金额汇入乙方指定银行 账号。乙方收到甲方还款后，根据还款金额恢复相应的赊购额度。如甲方未能按 期足额还款，乙方有权停止甲方下单并停止送货，未结款部分甲方自愿按日利率 5‰（千分之五）计算逾期利息。逾期还款超过 30 日的，乙方有权自行解除本合 同并取消所有返利，解除合同的通知到达甲方即生效。 </p>
<p>5.3：如终止合作，甲方只需要在微信号中向乙方提出解除合同，乙方同意后即 终止合同，甲方应当于终止合同后 5 个自然日内支付赊购商品的货款，预充值初 始赊购额度未使用金额由乙方清零。逾期还款的，甲方自愿按日利率 5‰（千分 之五）计算逾期利息。 </p>
<p><b>第 6 条：商品的货款结算 </b></p>
<p>6.1：担保人： <u>　  {$data['surety_name']}    </u> 身份证号： <u>　  {$data['surety_card']}    </u> 手机号： <u>　  {$data['surety_phone']}    </u> </p>
<p>6.2：担保人个人自愿承担本合同项下甲方欠款及利息、违约金、损害赔偿金和 实现债权费用的连带担保责任，担保期限为主债务履行期届满之日起三年。</p>
<p><b>第 7 条：商品的货款结算 </b></p>
<p>7.1：双方应保护对方的商业秘密（含本合同和附件、技术和经营信息等），未经 对方书面同意，不得向外泄露因本合同签订和履行中所获悉的对方的商业秘密。 </p>
<p>7.2：违反保密责任的，违约方应当向对方支付违约金伍万元，并承担由此造成 的一切损失和实现债权的费用。 </p>
<p><b>第 8 条：商品的货款结算 </b></p>
<p>8.1：本合同经双方代理人签字并盖章以及担保人签字按印后生效。</p>
<p>8.2：附件 1《返利说明》与本合同具备同等法律效力。 </p>
<p>8.3：本合同一式三份，甲乙双方各执一份，担保人一份。甲方应提供公司营业 执照复印件盖章，代理人、 法定代表人、担保人身份证复印件。</p>
<p>8.4：乙方认为本合同和附件需修订时，有权要求甲方协商签订新合同，如甲方 违反，乙方有权解除本合同并取消所有返利，解除合同的通知到达甲方即生效。</p>
<p>8.5：本合同和附件条款是双方逐条讨论后商定，均深刻了解其内容和含义。</p>
<p>8.6：如因履行本合同发生纠纷，双方协商不成的，任何一方均可直接向常州市 钟楼区人民法院提起诉讼。诉讼费、律师费等实现债权的费用均由违约方承担。 </p>
<p>8.7：本合同终止日期为 2019 年 12 月 31 日。如到期后需要续订合同，双方应协 商后重新订立。如到期后未续订合同，甲方应当于终止合同后 5 个自然日内支付 赊购商品的货款，预充值初始赊购额度未使用金额由乙方清零。逾期还款的，甲 方自愿按日利率 5‰（千分之五）计算逾期利息。 </p>
<p>8.8：乙方账户：常州佩祥电子商务有限公司 <br>　　开 户 行：江苏江南农村商业银行股份有限公司常州市新闸支行 <br>　　账号：8980 1133 0120 1000 0002 882
</p>
<div style="height:15px"></div>
<p><b>其他事宜</b></p>
<p>
1.合同解除后甲方结清乙方赊账，不影响甲方作为普通客户在平台的正常消费。 
</p>
<p>
2.相关产品品牌、质量、规格、价格、送货方式、服务政策等以平台内相关展示说明为准。 
</p>
<p>3.本返利说明的有效期与双方签订的《供货合同》相同，是《供货合同》的附件。</p>
<p> 4.其他未尽事宜：___________________________________________________________________________________________________</p>
<div style="height:15px"></div>
<div>
<p>甲方（公章）：</p>
<p>法人（代理人）：</p>
<p>联系方式：</p>
</div>

<div>
<p>乙方（公章）：</p>
<p>法人（代理人）：</p>
<p>联系方式：</p>
</div>

<div>
<p>担保人：</p>
<p>右手拇指：</p>
<p>联系方式：</p>
</div>
<p>日期：&nbsp;&nbsp;&nbsp;&nbsp;年&nbsp;&nbsp;&nbsp;&nbsp;月&nbsp;&nbsp;&nbsp;&nbsp;日</p>
</div>
</div>
EOD;

        $name = '店铺赊账签押合同_'.date("Ymds").'_'.$data['username'].'.pdf';
        $filename  = ROOT_PATH . 'public'.DS.$name;
//        dump($name);
        $pdf = pdf($html, $filename, 'https://images.pxjiancai.com', '常州佩祥电子商务有限公司', 'F');

        $_FILES['pdf']['id'] = fileowner($pdf);
        $_FILES['pdf']['name'] = $pdf;
        $_FILES['pdf']['error'] = 0;
        $_FILES['pdf']['tmp_name'] = 'C:\Users\Lyunp\AppData\Local\Temp\phpEF7D.tmp';
        $_FILES['pdf']['type'] = 'image/png';
        $_FILES['pdf']['lastModifiedDate'] = date('r', filemtime($pdf));
        $_FILES['pdf']['size'] = filesize($pdf);

        $filePath = $name;
        $info = uploadPdf($name,$filePath);
        if($info)
        {
            //删除之前创建好的pdf文件，已不用
            clearn_file(ROOT_PATH . 'public'.DS,'pdf');
            if($info['data'] != '')
            {
                //将返回的数据更新到数据库
                $this->creditList->where(['site_id'=>$data['site_id'],'buyer_id'=>$data['buyer_id']])->update(['buyer_pdf_url'=>$info['data']]);

            }

            return $info;
        }

        //删除之前创建好的pdf文件，已不用
        clearn_file(ROOT_PATH . 'public'.DS,'pdf');

        return [];
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
        $this->assign('quickId', $quickId);
        $this->view->engine->layout(false);
        return $this->fetch('addgoodslistforquick');
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
        $goodsModel = new goodsModel();
        $filter              = input('request.');
        $quickId = isset($filter['quickId']) ? $filter['quickId'] : 0;

        //查看这个快速选材所能选择的店铺
        $siteIdList = (new AreaMallClassCat())->getThisSites($quickId);
        $filter['inSite']  = $siteIdList;

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
