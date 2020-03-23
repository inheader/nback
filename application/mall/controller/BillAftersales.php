<?php
namespace app\Mall\controller;

use app\common\controller\Mall;
use app\common\model\AreaMallCredit;
use app\common\model\AreaMallCreditFlow;
use app\common\model\BillAftersales as BillAfterSalesModel;
use app\common\model\Buyer;
use app\common\model\BuyerBalance;
use app\common\model\BuyerBalanceLog;
use app\common\model\Credit;
use app\common\model\CreditFlow;
use app\common\model\CreditLines;
use app\common\model\OrderCommon;
use app\common\model\OrderGoods;
use app\common\model\OrderPay;
use app\common\model\OrderReturn;
use app\common\model\OrderReturnGoods;
use app\common\model\OrderService;
use app\common\model\OrderServiceRule;
use app\common\model\Payments;
use think\Db;

class BillAftersales extends Mall
{
    /**
     * 获取售后单列表
     * @return mixed
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if($this->request->isAjax()){
            $params = $this->request->param();
			  
            if($params)
            {
				
                $data = array_merge($params,$userWhere);

                $returnOrderModel = new OrderReturn();

                $buyer = new Buyer();

                $returnOrder = $returnOrderModel->tableReturnListData($data);

                $data = [];
                foreach($returnOrder['data'] as $key=>$row){
                    $row['buyer_header'] = $buyer->getBuyerInfoForId($row['buyer_id'])->buyer_header;
                    $row['buyer_name'] = $buyer->getBuyerInfoForId($row['buyer_id'])->buyer_name;
                    $row['buyer_tel'] = $buyer->getBuyerInfoForId($row['buyer_id'])->buyer_tel;

//                    if($row['return_pay_code'] == '微信退款')
//                    {
//                        $row['return_price'] = sprintf('%.2f',$row['refund_price']);
//                    }else{
                        $row['return_price'] = sprintf('%.2f',$row['refund_price'] + $row['return_shipping_fee'] + $row['return_floor_fee'] + $row['return_transport_fee']);
//                    }
                    $data[] = $row;
                }

                $returnOrder['data'] = $data;
                return $returnOrder;
            }


        }
        return $this->fetch('index');
    }

    /**
     * 售后单处理
     * @return array|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function audit()
    {

        $this->view->engine->layout(false);
        if(!input('?param.return_id')){
            return error_code(13215);
        }

        $returnId = input('param.return_id');
        $returnModel = new OrderReturn();

        if($this->request->isPost()){
            if(!input('?param.status')){
                return error_code(10000);
            }

            $status   = input('param.status','');
            $mark     = input('param.mark','');

            //判断退款单审核通过 根据条件更改订单状态
             if($status == 1){
                 $return_info = (new OrderReturn())->myInstanceByReturnId($returnId);
//                 dump($return_info);
                 if($return_info && $return_info['order_id']){
// 					$data = Db::table('')->alias('')->join('','')->field('')->where()->find();
                     Db::table('ev_order_common')->where('order_id',$return_info['order_id'])->update(['order_state'=>80]);
                 }
             }

            $refundPrice = $this->request->param('refund');
            $shippingPrice = $this->request->param('shipping_fee');
            $floorPrice = $this->request->param('floor_fee');
            $transportPrice = $this->request->param('transport_fee');
            $isReturnExpress = $this->request->param('is_return_express');
            $data['status'] = $status;
            $data['reason'] = $mark;
            if(isset($refundPrice))
            {
                $data['refund_price'] = $refundPrice;
            }
            if(isset($shippingPrice))
            {
                $data['refund_shipping_fee'] = $shippingPrice;
            }
            if(isset($floorPrice))
            {
                $data['refund_floor_fee'] = $floorPrice;
            }
            if(isset($transportPrice))
            {
                $data['refund_transport_fee'] = $transportPrice;
            }
            if(isset($isReturnExpress))
            {
                $data['is_return_express'] = $isReturnExpress;
            }
            return $returnModel->audit($returnId,$data);
        }

        $info = (new OrderReturn())->myInstanceByReturnId($returnId);
        if(!$info){
            return error_code(13207);
        }

        $buyerIns = (new Buyer())->getBuyerInfoForId($info->getMyBuyerId());
        $info['status'] = $info->getMyStatusName();
        $info['buyer_name'] = $buyerIns['buyer_name'];
        $info['refund_type'] = $info->getMyReturnTypeName();
        $returnGoods = (new OrderReturnGoods())->myInstanceByReturnId($returnId);

        //查询订单数据
        $orderFind = (new OrderCommon())->where('order_sn',$info['order_sn'])->find();

        $goods = [];
        foreach($returnGoods as $row){
            $ordGoods    = (new OrderGoods())->myInstanceByOspId($row->getMyOspId());
            $goods[] = [
                'goods_id'  => $ordGoods->getMyGoodsId(),
                'goods_name'  => $ordGoods->getMyGoodsName(),
                'goods_num'   => $ordGoods->getMyGoodsNum(),
                'return_num'  => $row->getMyReturnNum(),
            ];
        }

        $info['order_price'] = $orderFind['order_price'];
        $info['floor_fee'] = $orderFind['order_floor_fee'];
        $info['shipping_fee'] = $orderFind['order_shipping_fee'];
        $info['transport_fee'] = $orderFind['order_transport_fee'];
        $info['unloading_fee'] = $orderFind['order_unloading_fee'];
        $info['goods'] = json_encode($goods);

        //是否存在特快
        $service = (new OrderService())->where(['order_id'=>$orderFind['order_id']])->field('status,pay_sn')->find();
        if($service)
        {
            //根据特快信息 获取购买过的订单特快费信息
            $serviceRule = (new OrderServiceRule())->where(['pay_sn'=>$service['pay_sn']])->field('site_rule')->find();
            $siteIds = explode(',',$serviceRule['site_rule']);
            $this->assign('se','true');
        }else{
            $this->assign('se','false');
        }

        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch('audit'),
            'msg' => ''
        ];
    }


    /**
     * 售后单查看
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view()
    {
        $this->view->engine->layout(false);

        if(!input('?param.return_id')){
            return error_code(13217);
        }
        $orderReturnModel = new OrderReturn();

        $returnId = input('param.return_id');
        $info = $orderReturnModel->myInstanceByReturnId($returnId);
        if(!$info){
            return error_code(13218);
        }

        $info['status'] = $info->getMyStatusName();

        $buyerIns = (new Buyer())->getBuyerInfoForId($info->getMyBuyerId());
        $info['buyer_name'] = $buyerIns['buyer_name'];
        $info['refund_type'] = $info->getMyReturnTypeName();
        $returnGoods = (new OrderReturnGoods())->myInstanceByReturnId(input('param.return_id'));
        $goods = [];
        foreach($returnGoods as $row){
            $ordGoods    = (new OrderGoods())->myInstanceByOspId($row->getMyOspId());
            $goods[] = [
                'goods_id'  => $ordGoods->getMyGoodsId(),
                'goods_name'  => $ordGoods->getMyGoodsName(),
                'goods_num'   => $ordGoods->getMyGoodsNum(),
                'return_num'  => $row->getMyReturnNum(),
            ];
        }

        $info['goods'] = json_encode($goods);

        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch('view'),
            'msg' => ''
        ];
    }


    /**
     * 手动退款
     * @param null $returnId
     * @return array
     */
    public function wxReturn($returnId=null)
    {
        $this->view->engine->layout(false);

        $returnId = isset($returnId) ? $returnId : $this->request->get('return_id');

        if(!$returnId)
        {
            return [
                'status' => false,
                'data' => '',
                'msg' => 'ID不存在',
            ];
        }
        $orderReturnModel = new OrderReturn();
        $info = $orderReturnModel::get($returnId);
        $this->assign('info',$info);
        return [
            'status' => true,
            'data' => $this->fetch(),
            'msg' => '',
        ];
    }

    /**
     * 退款操作
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxReturnPost()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                if(!isset($params['refund_price']))
                {
                    return ['status'=>false,'msg'=>'金额异常'];
                }

                $orderReturnModel = new OrderReturn();
                $info = $orderReturnModel->where('return_id',$params['return_id'])->find();
                if($info)
                {
                    $orderCommonModel = new OrderCommon();
                    $orderInfo = $orderCommonModel->where('order_id',$info['order_id'])->select();
                    $total=0;//订单支付总金额
                    foreach ($orderInfo as $ord=>$order) {
                        $total += ($order['order_price'] + $order['order_shipping_fee'] + $order['order_floor_fee'] + $order['order_transport_fee']);
                    }
                    //退款操作
                    $return = $this->returnOrderPay($params['refund_price'],$total,$info['reason'],$info,$info['return_pay_code']);
                    if($return)
                    {
                        return ['status'=>true,'msg'=>'退款成功'];
                    }else{
                        return ['status'=>false,'msg'=>'退款失败'];
                    }
                }
            }
            return ['status'=>false,'msg'=>'数据异常'];
        }

        return [];
    }


    public function returnOrderPay($amount,$total,$reason,$info,$returnType='')
    {

        switch ($returnType)
        {
            case 'WEIXIN_PAY': //微信退款

                $url = "https://api.pxjiancai.com/job/wx/return?return_id=".$info['return_id']."&amount=".$amount."&total=".$total."&_ajax=PxData";//https://dev.pxjiancai.com/

//                $params = "return_id=".$info['return_id']."&amount=".$amount."&total=".$total;

//                $params = [
//                    'return_id' => $info['return_id'],
//                    'amount' => $amount,
//                    'total' => $total,
//                ];

                $res = $this->request_by_curl($url, []);
                $data = json_decode($res,true);
                if($data['code'] == 20000)
                {
                    return true;
                }else{
                    return false;
                }
              break;
            case 'POINT_PAY': //积分退款

                break;
            case 'VCOUNT_PAY': //余额退款
                $balanceIns = new BuyerBalance();
                $balanceIns->changeMyBalance($info['buyer_id'],$amount);
                (new BuyerBalanceLog())->addData([
                    'mallId'  => $info['mall_id'],
                    'buyerId' => $info['buyer_id'],
                    'orderId' => $info['order_id'],
                    'orderSn' => $info['order_sn'],
                    'type'    => 1,
                    'num'     => $amount,
                    'balance' => $balanceIns->getBuyerBalanceForBuyerId($info['buyer_id']),
                    'remark'  => '支付单号：'.$info['order_sn'],
                ]);

                //更改退款单状态
                $orderReturnModel = new OrderReturn();
                $orderReturnModel->where(['return_id'=>$info['return_id']])->update(['status'=>8,'reason'=>$reason]);
                return true;
                break;
            case 'CREDIT_PAY': //店铺赊账
                $balanceIns = new CreditLines();
                $balanceIns->changeMyBalance($info['buyer_id'],$amount);
                (new CreditFlow())->addData([
                    'mallId'  => $info['mall_id'],
                    'siteId'  => $info['site_id'],
                    'buyerId' => $info['buyer_id'],
                    'orderId' => $info['order_id'],
                    'orderSn' => $info['order_sn'],
                    'type'    => 2,
                    'flowType'    => 2,
                    'price'     => $amount,
                    'balance' => $balanceIns->getBuyerCreditForBuyerId($info['buyer_id']),
                    'remark'  => '支付单号：'.$info['order_sn'],
                ]);
                //更改退款单状态
                $orderReturnModel = new OrderReturn();
                $orderReturnModel->where(['return_id'=>$info['return_id']])->update(['status'=>8,'reason'=>$reason]);
                return true;
                break;
            case 'AREA_CREDIT_PAY': // 平台赊账
                $balanceIns = new AreaMallCredit();
                $balanceIns->changeMyBalance($info['buyer_id'],$amount);
                (new AreaMallCreditFlow())->addData([
                    'mallId'  => $info['mall_id'],
                    'siteId'  => $info['site_id'],
                    'buyerId' => $info['buyer_id'],
                    'orderId' => $info['order_id'],
                    'orderSn' => $info['order_sn'],
                    'type'    => 2,
                    'flowType'    => 2,
                    'price'     => $amount,
                    'balance' => $balanceIns->getBuyerCreditForBuyerId($info['buyer_id']),
                    'remark'  => '支付单号：'.$info['order_sn'],
                ]);
                //更改退款单状态
                $orderReturnModel = new OrderReturn();
                $orderReturnModel->where(['return_id'=>$info['return_id']])->update(['status'=>8,'reason'=>$reason]);
                return true;
                break;
            default:
          
        }
    }

    /**
     * @param $remote_server
     * @param $post_string
     * @return mixed
     */
    public function request_by_curl($remote_server, $post_string) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

}
