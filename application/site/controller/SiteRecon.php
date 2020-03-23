<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/20
 * Time: 11:35
 */

namespace app\site\controller;

use app\common\controller\Site;

/**
 * 店铺对账
 * Class SiteRecon
 * @package app\site\controller
 */
class SiteRecon extends Site
{
    protected $creditModel = null;
    protected $creditFlowModel = null;
    protected $creditLinesModel = null;

    protected $orderCommonModel = null;
    protected $orderGoodsModel = null;
    protected $returnOrderModel = null;//退款订单
    protected $orderReturnGoods = null;//退款商品

    protected $mallModel = null;
    protected $siteModel = null;
    protected $buyerModer = null;

    protected $siteCashModel = null;
    protected $siteCashLogModel = null;
    protected $buyerBalanceModel = null;
    protected $buyerBalanceLogModel = null;

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->creditModel = model('Credit');
        $this->creditFlowModel = model('CreditFlow');
        $this->creditLinesModel = model('CreditLines');

        $this->orderCommonModel = model('OrderCommon');
        $this->returnOrderModel = model('OrderReturn');
        $this->orderGoodsModel = model('OrderGoods');
        $this->orderReturnGoods = model('OrderReturnGoods');

        $this->mallModel = model('AreaMall');
        $this->siteModel = model('AreaMallSite');
        $this->buyerModer = model('Buyer');

        $this->siteCashModel = model('AreaMallSiteCash');
        $this->siteCashLogModel = model('AreaMallSiteCashLog');
        $this->buyerBalanceModel = model('BuyerBalance');
        $this->buyerBalanceLogModel = model('BuyerBalanceLog');
    }


    /**
     * 赊账
     * @return mixed
     */
    public function CreditIndex()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();

            $where = [];
            $wheres = [];
            $where['type'] = 2;
            $where['site_id'] = $siteId;

            $page = isset($params['page']) ? $params['page'] : $this->request->get("page", 0);
            $limit = isset($params['limit"']) ? $params['limit'] : $this->request->get("limit", 0);

            if(isset($params['name']) && !empty($params['name']))
            {
                //查询列表姓名
                $buyer = $this->creditModel->where('username','like','%'.$params['name'].'%')->field('buyer_id')->find();
                if($buyer)
                {
                    $where['buyer_id'] = $buyer['buyer_id'];
                }else{
                    $where['buyer_id'] = 0;
                }
//                    $where_a[] = ['credit.username','LICK','%'.$params['name'].'%'];
            }

            if(isset($params['staDate']) && !empty($params['staDate']))
            {
                //分割字符串
                $date = explode(' - ',$params['staDate']);

                $staDate = $date[0].' 00:00:00';
                $endDate = $date[1].' 23:59:59';
                $wheres[] = array('updated_at', ['>=', $staDate], ['<=', $endDate, 'between time']);
            }

            if(isset($params['flow_type']))
            {
                $where['flow_type'] = $params['flow_type'] ? $params['flow_type'] : $this->request->get("flow_type", 0);
            }

            $list = $this->creditFlowModel
                ->with('credit')
                ->where($where)
                ->where($wheres)
                ->page($page,$limit)
                ->select();

            $total = $this->creditFlowModel
                ->with('credit')
                ->where($where)
                ->where($wheres)
                ->count();

            $flowType = ['1'=>'收入','2'=>'支出'];

            foreach ($list as $key=>$value) {
                $list[$key]['flow_type'] = $flowType[$value['flow_type']];
                $list[$key]['username'] = $value['credit']['0']['username'].'('.$value['buyer_id'].')';
            }

            $result = array("code"=>0,"count" => $total, "data" => $list);

            return json($result);

        }

        $sum = $this->getSiteCreditSumPrice($siteId);
        $this->assign('sum',$sum);

        $flowSum = $this->getFlowSum($siteId);
        $this->assign('flowSum',$flowSum);
        return $this->fetch();
    }

    /**
     * 赊账用户详细
     * @return mixed
     */
    public function creditDetail($buyerId='',$orderSn='')
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺管理员才有权限打开此界面'
            ];
        }

        $buyerId = $buyerId ? $buyerId : $this->request->get('bid');//用户ID
        $orderSn = $orderSn ? $orderSn : $this->request->get('order_sn');//订单编号

        $orderList = $this->orderCommonModel
            ->where(['buyer_id'=>$buyerId,'pay_sn'=>$orderSn])
            ->select();

//        dump($orderList);
        if(count($orderList) <= 0)
        {
            $this->error('该用户订单下，信息异常');
        }
        $data = [];
        $shipping_fee = 0;
        foreach ($orderList as $index => $item) {
            $goods = $this->orderGoodsModel
                ->where('order_id',$item['order_id'])
                ->find();
            $data[] = [
                'order_sn' => $item['order_sn'],
                'order_price' => $item['order_origin_price'],
                'order_goods_image' => $goods['goods_image'],
                'order_goods_name' => $goods['goods_name'],
                'order_goods_num' => $goods['goods_num'],
                'order_fee' => $item['order_shipping_fee'],
                'order_floor_fee' => $item['order_floor_fee'],
            ];
        }

        //计算满额包邮
        $goods_price = $this->orderCommonModel
            ->where(['buyer_id'=>$buyerId,'pay_sn'=>$orderSn])
            ->sum('order_origin_price');

        $siteInfo = $this->getSiteInfo($siteId);

        //是否选择区域官方物流
        if($siteInfo['site_shipping_type'] === 'mall')
        {
            //获取区域店铺信息
            $mallInfo = $this->getAreaMall($siteInfo['mall_id']);

            if($goods_price >= $mallInfo['free_shipping_fee'])
            {
                $shipping_fee = 0;
            }else{

                $shipping_fee = $mallInfo['shipping_fee'] / count($orderList);
            }

            foreach ($data as $key=>$d) {
                $data[$key]['shipping_fee'] = sprintf('%.2f',$shipping_fee);
            }

        }else{

            if($goods_price >= $siteInfo['free_shipping_fee'])
            {
                $shipping_fee = 0;
            }else{
                $shipping_fee = $siteInfo['shipping_fee'] / count($orderList);
            }

            foreach ($data as $key=>$d) {
                $data[$key]['shipping_fee'] = sprintf('%.2f',$shipping_fee);
            }
        }

        $this->assign('list',$data);

        //头部订单显示
        $order = $this->orderCommonModel->where(['pay_sn'=>$orderSn])->find();
        $this->assign('order',$order);
//        //账单信息
//        $credit = $this->creditFlowModel->where(['site_id'=>$siteId,'order_id'=>$orderSn])->find();

        return $this->fetch();
    }

    /**
     * 现金
     * @return mixed
     */
    public function cashIndex()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺管理员才有权限打开此界面'
            ];
        }

        //头部
        $siteCashSum = $this->siteCashLogModel
            ->where(['site_id'=>$siteId,'type'=>1])
            ->sum('price');//总收入
        $siteCashBlock = $this->siteCashLogModel
            ->where(['site_id'=>$siteId,'ptype'=>1])
            ->sum('price');//冻结资金
        $siteCashHarvest = $this->siteCashLogModel
            ->where(['site_id'=>$siteId,'type'=>2])
            ->sum('price');//支出现金
        $data = [
            'priceSum' => sprintf('%.2f',$siteCashSum-$siteCashHarvest),
            'priceBlock' => sprintf('%.2f',$siteCashBlock),
            'priceHarvest' => sprintf('%.2f',$siteCashHarvest),
            'CashPriceSum' => sprintf('%.2f',$siteCashSum),
        ];
        $this->assign('data',$data);
        return $this->fetch();
    }


    /**
     * 收入详细
     * @param string $buyerId
     * @param string $orderSn
     */
    public function cashDetail($Id='')
    {
        $Id = $Id ? $Id : $this->request->get('id');
        $cashInfo = $this->siteCashLogModel->where(['id'=>$Id])->find();
        if($cashInfo)
        {
            //根据来源类型获取不同订单信息
           if( $cashInfo['source'] == 1 || $cashInfo['source'] == 2 )
           {
               //余额/现金来源,购买成功订单
               //存在订单号
               $order = $this->orderCommonModel
                   ->with('goods')
                   ->where(['order_sn'=>$cashInfo['order_sn']])
                   ->select();
               $temp = [];//商品列
               $shippingFee = 0;//运费
               $floorFee = 0;//挑楼费
               $carryFee = 0;//搬运费
               $nounFee = 0;//手续费
               $goodsPrice = 0;//交易收入
               $sumPrice = 0;//合计收入
               foreach ($order as $index => $item) {
                   $temp[] = [
                           'site_id' => $item['site_id'],
                           'mall_id' => $item['mall_id'],
                           'order_sn'  => $item['order_sn'],
                           'goods_name' => $item['goods']['goods_name'],
                           'goods_image' => $item['goods']['goods_image'],
                           'goods_price' => $item['goods']['osp_price'],
                           'goods_num' => $item['goods']['goods_num'],
                           'osp_price' => $item['goods']['osp_price'],
                           'created_at'  => $item['created_at'],
                   ];

                   if($item['order_origin_price'] >= $this->getAreaMall($item['mall_id'])['free_shipping_fee'])
                   {
                       $shippingFee = 0;
                   }else{
                       $shippingFee = $this->getAreaMall($item['mall_id'])['shipping_fee'];
                   }

                   $floorFee = $item['order_floor_fee'];
                   $carryFee = '0.00';
                   $goodsPrice = $item['order_origin_price'];
               }

               $priceSum = 0;
               //遍历多个商品
               foreach ($temp as $key=>$value) {
                   $nounFee +=  $value['goods_price'] * $this->getAreaMall($value['mall_id'])['mall_noun'];
                   $priceSum += $value['osp_price'] * $value['goods_num'];
               }

//               $refundPrice = ($goodsPrice - $nounFee);//实际收入金额

               $datas = [
                   'profession_type' => $cashInfo['type'] == 1 ? '收入金额' : '支出金额',//业务类型
                   'shippingFee' => $shippingFee,
                   'floorFee'  => $floorFee,
                   'carryFee'  => $carryFee,
                   'nounFee'   => $nounFee,
                   'goodsPrice' => $priceSum,
                   'sumPrice' => sprintf('%.2f',($shippingFee + $floorFee + $carryFee + $goodsPrice)),
                   'order_sn'     => $cashInfo['order_sn'],
                   'created_at'   => $cashInfo['created_at']

               ];
               $this->assign('list',$temp);
               $this->assign('datas',$datas);
           }else if( $cashInfo['source'] == 4 || $cashInfo['source'] == 5)
           {
                $datas = [
                    'profession_type' => $cashInfo['source'] == 4 ? '平台退回' : '平台收取',//业务类型
                    'price_type'    => $cashInfo['remark'],
                    'return_price'  => $cashInfo['price']
                ];
                $this->assign('datas',$datas);
           }

        }

        //获取订单

        //显示数据
        $data = [
            'site_name' => $this->getSiteInfo($cashInfo['site_id'])['site_name'],
            'mall_name' => $this->getAreaMall($cashInfo['mall_id'])['mall_name'],
            'source' => $cashInfo['source']
        ];
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 支出详细
     * @param string $Id
     * @return mixed
     */
    public function harvestDetail($Id='')
    {
        $Id = $Id ? $Id : $this->request->get('id');
        $cashInfo = $this->siteCashLogModel->where(['id'=>$Id])->find();
        if($cashInfo)
        {
            //根据来源获取账单信息
            if($cashInfo['source'] == 1 || $cashInfo['source'] == 2)
            {
                //获取退款订单
                $orderReturn = $this->returnOrderModel
                    ->where(['order_sn'=>$cashInfo['order_sn']])
                    ->find();

                //获取退款商品
                $returnGoods = $this->orderReturnGoods
                    ->where(['return_id'=>$orderReturn['return_id']])
                    ->select();
                //遍历获取商品信息
                $temp = [];
                foreach ($returnGoods as $index => $item) {

                    $goods = $this->orderGoodsModel
                        ->where('osp_id',$item['osp_id'])
                        ->find();
                    $temp[] = [
                        'goods_name' => $goods['goods_name'],
                        'goods_image' => $goods['goods_image'],
                        'goods_price' => $goods['osp_price'],
                    ];
                }

                //遍历商品
//                foreach ($temp as $key=>$value) {
//
//                }
//
//                $data = [
//                    ''
//                ];

                $this->assign('list',$temp);

            }else if($cashInfo['source'] == 4 || $cashInfo['source'] == 5)
            {

            }
        }
        //显示数据
        $data = [
            'site_name' => $this->getSiteInfo($cashInfo['site_id'])['site_name'],
            'mall_name' => $this->getAreaMall($cashInfo['mall_id'])['mall_name'],
            'source' => $cashInfo['source']
        ];
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 获取现金消费记录
     * @param int $flow_type
     */
    public function getSiteCash()
    {
        $userWhere = $this->getMyUserWhere();
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $wheres = [];
                if(isset($params['flow_type']) && !empty($params['flow_type']))
                {
                    $where['type'] = $params['flow_type'] ? $params['flow_type'] : $this->request->get("flow_type", 0);
                }

                if(isset($params['order_sn']) && !empty($params['order_sn']))
                {
                    $where['order_sn'] = $params['order_sn'] ? $params['order_sn'] : $this->request->get("order_sn", 0);
                }

                if(isset($params['staDate']) && !empty($params['staDate']))
                {
                    //分割字符串
                    $date = explode(' - ',$params['staDate']);

                    $staDate = $date[0].' 00:00:00';
                    $endDate = $date[1].' 23:59:59';
                    $wheres[] = array('created_at', ['>=', $staDate], ['<=', $endDate, 'between time']);
                }

                $page = isset($params['page']) ? $params['page'] : $this->request->get("page", 0);
                $limit = isset($params['limit"']) ? $params['limit'] : $this->request->get("limit", 0);
                //现金
                $list = $this->siteCashLogModel
                    ->where(['site_id'=>$siteId])
                    ->where($where)
                    ->where($wheres)
                    ->page($page,$limit)
                    ->select();
                $total = $this->siteCashLogModel
                    ->where(['site_id'=>$siteId])
                    ->where($where)
                    ->where($wheres)
                    ->count();

                $source = ['1'=>'余额','2'=>'现金','3'=>'赊账','4'=>'平台退回','5'=>'平台收取','6'=>'用户充值','7'=>'用户退款'];

                $type = '';
                foreach ($list as $index => $item) {
                    $type = $item['type'] == 1 ? '+ ' : '- ';
                    $list[$index]['price'] = $type.$item['price'];
                    $list[$index]['username'] = $this->getBuyerInfo($item['buyer_id'])['buyer_name'];
                    $list[$index]['source'] = rtrim($source[$item['source']].'-'.$item['order_sn'],'-');
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);

                return json($result);
            }
        }
        return [];
    }

    /**
     * 店铺赊账出入账
     * @return array
     */
    public function getFlowSum($siteId=0)
    {
        $come = $this->creditFlowModel->where(['site_id'=>$siteId,'flow_type'=>1])->sum('num');
        $harvest = $this->creditFlowModel->where(['site_id'=>$siteId,'flow_type'=>2])->sum('num');
        return [
            'come' => $come,
            'harvest' => $harvest
        ];
    }

    /**
     * 获取店铺信息
     * @param int $siteId
     * @return mixed
     */
    public function getSiteInfo($siteId=0)
    {
        $info = $this->siteModel
            ->where('site_id',$siteId)
            ->find();
        return $info;
    }

    /**
     * 获取区域分站信息
     * @param int $mallId
     * @return mixed
     */
    public function getAreaMall($mallId=0)
    {
        $info = $this->mallModel
            ->where('mall_id',$mallId)
            ->find();

        return $info;
    }

    /**
     * 获取用户信息
     * @param $buyerId
     * @return mixed
     */
    public function getBuyerInfo($buyerId)
    {
        return $this->buyerModer->where('buyer_id',$buyerId)->find();
    }

    /**
     * 赊账总额度与欠款总额
     * @param $siteId
     * @return array
     */
    public function getSiteCreditSumPrice($siteId)
    {
        if(!empty($siteId))
        {
            //总赊账额度，申请成功的人
            $creditSum = $this->creditFlowModel->where(['site_id'=>$siteId,'type'=>1])->sum('balance');
            $creditSums = $creditSum ? $creditSum : 0;
            //总欠款金额
            $creditPrice = $this->creditLinesModel->where(['site_id'=>$siteId])->sum('balance');
            $creditPrices = $creditPrice ? $creditPrice : 0;

            $sumPrice = $creditSums - $creditPrices;

            return [
                'sum' => sprintf('%.2f',$creditSum),
                'price' => sprintf('%.2f',$sumPrice),
            ];
        }

        return [
            'sum' => 0,
            'price' => 0,
        ];
    }


}