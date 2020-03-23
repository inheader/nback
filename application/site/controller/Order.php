<?php
namespace app\Site\controller;
use app\common\controller\Site;
use app\common\model\AreaMallBuyerCouponList;
use app\common\model\AreaMallBuyerCouponOrderLog;
use app\common\model\BillDelivery;
use app\common\model\DataExpress;
use app\common\model\OrderCommon;
use app\common\model\OrderItems;
use app\common\model\OrderLog;
use app\common\model\Buyer;
use app\common\model\OrderReturn;
use app\common\model\OrderGoods;
use app\common\model\OrderReturnGoods;
use app\common\model\Payments;
use app\common\model\BillAftersales as BillAfterSalesModel;
use app\common\model\Order as orderModel;//这个as主要是防止名称重复
use Request;

/**
 * 订单模块
 * Class Order
 * @package app\seller\controller
 * @author keinx
 */
class Order extends Site
{
    /**
     * 订单列表
     * @return array|mixed
     */
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        if(!Request::isAjax())
        {
            /*
             * 0.  待支付,
             * 10. 已取消 (10. 已取消（用户取消）20. 已取消(30分钟系统取消))
             * 30. 待发货
             * 40. 待收货
             * 50. 待评价
             *  60.已完成
             */
            //数据统计
            $input = [
                'ids' => [
                    OrderCommon::PENDING_PAYMENT,
                    OrderCommon::USER_CANCEL,
                    OrderCommon::PENDING_DELIVERY,
                    OrderCommon::PENDING_RECEIPT,
                    OrderCommon::PENDING_EVALUATE,
                    OrderCommon::COMPLETED_EVALUATE,
                    OrderCommon::ORDER_ALL,
                ]
            ];

            //列表加上权限的筛选
            $input = array_merge($input,$userWhere);

            $orderCommonModel = new OrderCommon();
            $count = $orderCommonModel->getOrderStatusNum($input);
          
            $counts = [
                'all'       => $count[OrderCommon::ORDER_ALL],
                'payment'   => $count[OrderCommon::PENDING_PAYMENT],
                'delivered' => $count[OrderCommon::PENDING_DELIVERY],
                'receive'   => $count[OrderCommon::PENDING_RECEIPT],
                'evaluated' => $count[OrderCommon::PENDING_EVALUATE],
                'cancel'    => $count[OrderCommon::USER_CANCEL],
                'complete'  => $count[OrderCommon::COMPLETED_EVALUATE]
            ];
            $this->assign('count', $counts);
          
            return $this->fetch('index');
        }
        else
        {
            $input = array(
                'order_sn' => input('order_sn'),
                'keyWords' => input('keyWords'),
                'order_unified_status' => input('order_unified_status'),
                'payment_code' => input('payment_code'),
                'date' => input('date'),
                'page' => input('page'),
                'limit' => input('limit')
            );
			
            $this->assign('payment_code', input('payment_code',''));

            //列表加上权限的筛选
            $input = array_merge($input,$userWhere);

            $orderCommonModel = new OrderCommon();
       
            $data = $orderCommonModel->getListFromAdmin($input);
		
            if(count($data['data']) > 0)
            {
				$total=0;
                foreach ($data['data'] as $k =>&$v)
                {
                    $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
					$total = $v['order_price'] + $v['order_shipping_fee'] + $v['order_floor_fee'] + $v['order_transport_fee'];
					$v['totalPrice'] = number_format($total,2);
                }
                $return_data = array(
                    'status' => true,
                    'msg' => '查询成功',
                    'count' => $data['count'],
                    'data' => $data['data']
                );
				
            }
            else
            {
                $return_data = array(
                    'status' => false,
                    'msg' => '没有符合的订单',
                    'count' => $data['count'],
                    'data' => $data['data']
                );
            }

            return $return_data;
        }
    }

    /**
     * 查看订单详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view($id)
    {
        $this->view->engine->layout(false);

        $orderCommonModel = new OrderCommon();
        $order_info = $orderCommonModel->getOrderInfoByOrderID($id);

        $this->assign('order', $order_info);
        return $this->fetch('view');
    }

    /**
     * 优惠劵
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_coupon_info()
    {
        $this->view->engine->layout(false);

        if($this->request->isGet())
        {
            $id = $this->request->param('id');//订单ID
            if($id)
            {
                $list = AreaMallBuyerCouponOrderLog::where('order_id',$id)->select();
                foreach ($list as $key=>$val) {
                    $list[$key]['coupon_name'] = (new AreaMallBuyerCouponList())->where('coupon_id',$val['coupon_id'])->find()['coupon_title'];
                }
                $this->assign('list',$list);
            }
        }

        return $this->fetch();
    }

    //客户审单
    public function customer()
    {
        $orderCommonModel = new OrderCommon();

        $this->view->engine->layout(false);

        if(!Request::isPost())
        {
            //订单发货信息
            $id = input('order_id');

            $order_info = $orderCommonModel->getOrderInfoByOrderID($id);

            $this->assign('order', $order_info);

            return $this->fetch('customer');
        }
        else
        {
            $data = input('param.');
            $orderId = $data['order_id'];

            $orderCommonModel->updateData($orderId, ['order_customer_state' => 2]);

            return ['code'=>0,'msg'=>'审单成功','data'=>''];
        }
    }

    /**
     * 编辑订单
     * @return array|mixed
     */
    public function edit()
    {
        $this->view->engine->layout(false);
        $orderId = isset($id) ? $id : 0;
        $orderCommonModel = new OrderCommon();
        //出发edit 更新订单改价标识
        $orderCommonModel->where('order_id',$orderId)->update(['is_edit'=>1]);
        if(!Request::isPost()){
            //订单信息
            $id = input('id');
            $order_info = $orderCommonModel->getOrderInfoByOrderID($id);

            $this->assign('order', $order_info);

            //当在待支付或者待发货的时候可以修改收货信息
            $canEdit = in_array($order_info['order_state'],
                [OrderCommon::PENDING_PAYMENT,OrderCommon::PENDING_DELIVERY]
            ) ? 1 : 0;
            $this->assign('can_edit', $canEdit);

            return $this->fetch('edit');
        }
        else
        {
            $data = input('param.');
            //修改成功更新修改标识
            $orderCommonModel->where('order_id',$data['order_id'])->update(['is_edit'=>0]);
            $result = $orderCommonModel->edit($data,$this->mallId);
            if($result)
            {
                $return_data = array(
                    'status' => true,
                    'msg' => '编辑成功',
                    'data' => $result
                );
            }
            else
            {
                $return_data = array(
                    'status' => false,
                    'msg' => '编辑失败',
                    'data' => $result
                );
            }
            return $return_data;
        }
    }

    /**
     *
     * @param null $id
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function IsEdit($id=null)
    {
        $orderCommonModel = new OrderCommon();
        $orderId = isset($id) ? $id : 0;
        if($orderId)
            //出发edit 更新订单改价标识
            $orderCommonModel->where('order_id',$orderId)->update(['is_edit'=>0]);

        return ['status'=>true,'msg'=>'成功'];
    }

    /**
     * 订单发货
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function ship()
    {
        $orderCommonModel = new OrderCommon();

        $this->view->engine->layout(false);
        if(!Request::isPost())
        {
            //订单发货信息
            $id = input('order_id');

            $order_info = $orderCommonModel->getOrderInfoByOrderID($id);

            $this->assign('order', $order_info);

            //获取默认快递公司
//            $ship = model('common/ship')->get($order_info['logistics_id']);
//            $this->assign('ship', $ship);
//
//            //获取物流公司
            $dataExpModel = new DataExpress();
            $logi_info = $dataExpModel->getAll();
            $this->assign('logi', $logi_info);

            return $this->fetch('ship');
        }
        else
        {
            $data = input('param.');
            $orderId = $data['order_id'];
            //$deliver_explain = $data['memo']; //发货备忘
            $shippingCode = $data['logi_no']; //物流单号
            $eCode = $data['logi_code']; //物流名称(代号)

            $orderIns = $orderCommonModel->getOrderInfoByOrderID($orderId);
            $expressIns = (new DataExpress())->getMyInstanceByCode($eCode);

            $orderCommonModel->updateData($orderId, ['orderState' => 40]);

            (new OrderLog())->addOrderActLog($orderIns, '商品已经发货', '商家');

            return ['status'=>true,'msg'=>'发货成功'];
        }
    }



    /**
     * 取消订单
     * @return array
     */
    public function cancel()
    {
        $id = input('id');
        $seller_id = $this->sellerId;
        $result = model('common/Order')->cancel($id, $seller_id);
        if($result)
        {
            $return_data = array(
                'status' => true,
                'msg' => '操作成功',
                'data' => $result
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '操作失败',
                'data' => $result
            );
        }
        return $return_data;
    }


    /**
     * 根据条件从数据库查询数据或者api请求获取快递信息
     * User:tianyu
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logistics()
    {
        $this->view->engine->layout(false);
        $billDeliveryModel = new BillDelivery();
        $data = $billDeliveryModel->getLogisticsInformation(input('param.order_id',''));
        return $this->fetch('logistics',[ 'data' => $data ]);
    }


    /**
     * 数据统计
     * @return array
     */
    public function statistics()
    {
        //7天内已支付订单
        $payres = (new OrderCommon())->statistics(OrderCommon::PENDING_DELIVERY);

        //7天内已发货订单
        $deliveryres = (new OrderCommon())->statistics(OrderCommon::PENDING_RECEIPT);

        $data = [
            'legend' => [
                'data' => ['已支付', '已发货']
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $payres['day']
                ]
            ],
            'series' => [
                [
                    'name' => '已支付',
                    'type' => 'line',
                    'data' => $payres['data']
                ],
                [
                    'name' => '已发货',
                    'type' => 'line',
                    'data' => $deliveryres
                ]
            ]
        ];

        return $data;
    }
	
	
	
	
	
	 /**
	 * 售后管理
	 * @return array
	 */
	function after(){
		 //获取权限的筛选条件
		$userWhere = $this->getMyUserWhere();
		
		if(Request::isAjax()){
		    $data = input('param.');
		    $data = array_merge($data,$userWhere);
		
		    $returnOrderModel = new OrderReturn();
		    return $returnOrderModel->tableReturnListData($data);
		
		}
		return $this->fetch('after');
	}
	
	
	
	
	//  /**
	//  * 售后单处理
	//  * @return array|bool|mixed
	//  * @throws \think\db\exception\DataNotFoundException
	//  * @throws \think\db\exception\ModelNotFoundException
	//  * @throws \think\exception\DbException
	//  */
	 public function audit()
	{
	    $this->view->engine->layout(false);
	    if(!input('?param.return_id')){
	        return error_code(13215);
	    }
	
	    $returnId = input('param.return_id');
	    $returnModel = new OrderReturn();
	
	    if(Request::isPOST()){
	        if(!input('?param.status')){
	            return error_code(10000);
	        }
	
	        $status   = input('param.status','');
	        $mark     = input('param.mark','');
	
	        return $returnModel->audit($returnId, input('param.status'), $mark);
	    }
	
	    $info = (new OrderReturn())->myInstanceByReturnId($returnId);
	    if(!$info){
	        return error_code(13207);
	    }
	
	    $buyerIns = (new Buyer())->getBuyerInfoForId($info->getMyBuyerId());
	    $info['status'] = $info->getMyStatusName();
	    $info['buyer_name'] = $buyerIns['buyer_name'];
	    $info['refund_type'] = $info->getMyReturnTypeName();
	    $returnGoods = (new OrderReturnGoods())->where('return_id',$returnId)->select();
		foreach($returnGoods as $return){
			$ordGoods    = (new OrderGoods())->where('osp_id',$return['osp_id'])->find();
			$info['items_json'] = json_encode([ 0 => [
			    'goods_name'  => $ordGoods->getMyGoodsName(),
			    'goods_num'   => $ordGoods->getMyGoodsNum(),
			    'return_num'  => $return['return_num'],
			]]);
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
	public function AfterView()
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
		
		$returnGoods = (new OrderReturnGoods())->where('return_id',$returnId)->select();
		foreach($returnGoods as $return){
			$ordGoods    = (new OrderGoods())->where('osp_id',$return['osp_id'])->find();
			$info['items_json'] = json_encode([ 0 => [
			    'goods_name'  => $ordGoods->getMyGoodsName(),
			    'goods_num'   => $ordGoods->getMyGoodsNum(),
			    'return_num'  => $return['return_num'],
			]]);
		}
	    $this->assign('info',$info);
	    return [
	        'status' => true,
	        'data' => $this->fetch('afterview'),
	        'msg' => ''
	    ];
	}
}