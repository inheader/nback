<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/15
 * Time: 13:46
 */

namespace app\mall\controller;

use app\common\controller\Mall;
use think\Db;

/**
 * 区域对账显示
 * Class AreaRecon
 * @package app\manage\controller
 */
class AreaRecon extends Mall
{

    //------公共-------
    protected $buyerModel = null;//用户
    protected $orderCommonModel = null;//购买订单
    protected $returnOrderModel = null;//退款订单
    protected $orderGoodsModel = null;
    protected $areaMallModel = null;//区域
    protected $siteModel = null;
    //------区域-------
    protected $creditModel = null;//区域赊账
    protected $creditFlowModel = null;//区域赊账流水
    protected $creditListModel = null;//区域赊账申请列
    //------余额-------
    protected $balanceModel = null;//余额
    protected $balanceLogModel = null;//余额流水

    protected $areaMallCashModel = null;//现金流水

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->buyerModel = model('Buyer');
        $this->orderCommonModel = model('OrderCommon');
        $this->returnOrderModel = model('OrderReturn');
        $this->orderGoodsModel = model('OrderGoods');
        $this->areaMallModel = model('AreaMall');
        $this->siteModel = model('AreaMallSite');

        //-----区域-----
        $this->creditModel = model('AreaMallCredit');
        $this->creditListModel = model('AreaMallCreditList');
        $this->creditFlowModel = model('AreaMallCreditFlow');
        //-----余额-----
        $this->balanceModel = model('BuyerBalance');
        $this->balanceLogModel = model('BuyerBalanceLog');

        $this->areaMallCashModel = model('AreaMallCashLog');

    }

    /**
     * 余额界面显示
     * @return mixed
     */
    public function balanceIndex()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $wheres = [];
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);

                if(isset($params['flow_type']) && !empty($params['flow_type']))
                {
                    $where['type'] = $params['flow_type'] ? $params['flow_type'] : $this->request->get("flow_type", 0);
                }

                if(isset($params['staDate']) && !empty($params['staDate']))
                {
                    //分割字符串
                    $date = explode(' - ',$params['staDate']);

                    $staDate = $date[0].' 00:00:00';
                    $endDate = $date[1].' 23:59:59';
                    $wheres[] = array('created_at', ['>=', $staDate], ['<=', $endDate, 'between time']);
                }
                $where['mall_id'] = $areaId;
                $list = $this->areaMallCashModel
                    ->where($where)
                    ->where($wheres)
                    ->page($page,$limit)
                    ->select();

                $total = $this->areaMallCashModel
                    ->where($where)
                    ->where($wheres)
                    ->count();

                $type = '';
                foreach ($list as $index => $item) {
                    $type = $item['type'] == 1 ? '+ ' : '- ';
                    $list[$index]['price'] = $type.$item['price'];
                    $list[$index]['site_name'] = $this->getSiteInfo($item['site_id'])['site_name'];
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);

                return json($result);
            }
        }

        $result = $this->getAreaBalanceSumAndPrice($areaId);
        $this->assign('balance',$result);

        return $this->fetch();
    }

    public function balanceDetail($id)
    {
        $id = $id ? $id : $this->request->get('id');

        return $this->fetch();
    }


    /**
     * 赊账界面
     */
    public function creditIndex()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                $wheres =[];
                $where['type'] = 2;
                $where['mall_id'] = $areaId;

                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);

                if(isset($params['name']) && !empty($params['name']))
                {
                    //查询列表姓名
                    $buyer = $this->creditListModel->where('username','like','%'.$params['name'].'%')->field('buyer_id')->find();
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
                    $wheres[] = array('created_at', ['>=', $staDate], ['<=', $endDate, 'between time']);
                }

                if(isset($params['flow_type']))
                {
                    $where['flow_type'] = $params['flow_type'] ? $params['flow_type'] : $this->request->get("flow_type", 0);
                }

                //区域信息
                $areaInfo = $this->areaMallModel
                    ->where('mall_id',$areaId)
                    ->find();

                $where['mall_id'] = $areaId;
                //查询区域赊账流水
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

                $type = '';
                foreach ($list as $key=>$value) {
                    $type = $value['flow_type'] == 1 ? '+ ' : '- ';
                    $list[$key]['price'] = $type.$value['price'];
                    $list[$key]['flow_type'] = $flowType[$value['flow_type']];
                    $list[$key]['username'] = $value['credit']['0']['username'];
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);

                return json($result);

            }
        }

        $sum = $this->getCreditSumAndPrice($areaId);
        $this->assign('sumPrice',$sum);
        //总的欠款金额

        return $this->fetch();
    }

    /**
     * 根据订单号查询订单信息
     * @param string $orderId
     * @return mixed
     */
    public function getOrderCommonList($orderId='')
    {
        $orderId = $orderId ? $orderId : 0;
        return $this->orderCommonModel->where('pay_sn',$orderId)->select();
    }

    /**
     * 用户具体详情操作
     * @param $buyerId
     * @return mixed
     */
    public function creditDetail($buyerId='',$orderSn='')
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        $buyerId = $buyerId ? $buyerId : $this->request->get('bid');//用户ID
        $orderSn = $orderSn ? $orderSn : $this->request->get('order_sn');//订单编号

        $orderInfo = $this->orderCommonModel
            ->where(['buyer_id'=>$buyerId,'order_sn'=>$orderSn])
            ->find();

        $data = [];
        $shipping_fee = 0;
        $orderSum = 0;
            $goods = $this->orderGoodsModel
                ->where('order_id',$orderInfo['order_id'])
                ->select();
            foreach ($goods as $k=>$val) {
                $data[] = [
                    'order_sn' => $orderInfo['order_sn'],
                    'goods_price' => $val['osp_price'],
                    'order_goods_image' => $val['goods_image'],
                    'order_goods_name' => $val['goods_name'],
                    'goods_num' => $val['goods_num'],
                    'order_fee' => $orderInfo['order_shipping_fee'],
                    'order_floor_fee' => $orderInfo['order_floor_fee'],
                ];
                $orderSum += $val['osp_price'] * $val['goods_num'];
//                $orderProSum += $val['goods_num'];
            }

        foreach ($data as $key=>$d) {
            $data[$key]['shipping_fee'] = sprintf('%.2f',$shipping_fee);
        }

        $orderInfo['orderPrice'] = sprintf('%.2f',$orderSum);
        $this->assign('list',$data);
        $this->assign('order',$orderInfo);
        //获取总额度，使用总额度

        return $this->fetch();
    }

    /**
     * 根据店铺ID店铺信息
     * @param string $siteId
     * @return mixed
     */
    public function getSiteInfo($siteId='')
    {
        return $this->siteModel->where('site_id',$siteId)->find();
    }

    /**
     * 获取区域站点信息
     * @param string $mallId
     */
    public function getAreaMallInfo($mallId='')
    {
        $info = $this->areaMallModel->where('mall_id',$mallId)->find();

        return $info;
    }

    /**
     * 获取用户信息
     * @param $buyerID
     * @return mixed
     */
    public function getBuyerInfo($buyerId)
    {
        $info = $this->buyerModel
            ->alias('b')
            ->where('b.buyer_id',$buyerId)
            ->join(['ev_area_mall_credit_list'=>'l'],'b.buyer_id=l.buyer_id')
            ->field('username,company,phone,card_id')
            ->find();
        return $info;
    }

    /**
     * 根据区域ID获取现金订单完成总额退款总额待付总额包括运费
     * @param string $mallId
     * @return array
     */
    public function getAreaBalanceSumAndPrice($mallId='')
    {
        $mallPriceSum = $this->areaMallCashModel->where(['mall_id'=>$mallId,'type'=>1])->sum('price');//现金总收入
        $mallPriceOutSum = $this->areaMallCashModel->where(['mall_id'=>$mallId,'type'=>2])->sum('price');//现金总支出
        $mallPrice = $mallPriceSum - $mallPriceOutSum;//现金余额

        return [
            'mallPriceSum' => $mallPriceSum,
            'mallPriceOutSum' => $mallPriceOutSum,
            'mallPrice' => $mallPrice
        ];

    }


    /**
     * 获取赊账总额度和欠款总额
     */
    public function getCreditSumAndPrice($mallId)
    {
        if(!empty($mallId))
        {
            //总赊账额度，申请成功的人
            $creditSum = $this->creditFlowModel->where(['mall_id'=>$mallId,'type'=>1])->sum('balance');
            $creditSums = $creditSum ? $creditSum : 0;
            //总欠款金额
            $creditPrice = $this->creditModel->where(['mall_id'=>$mallId])->sum('balance');
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