<?php
namespace app\common\model;
use think\model\concern\SoftDelete;
use app\common\model\Buyer;
use app\common\model\OrderReturn;
use think\Db;
/**
 * 订单主表
 * Class OrderCommon
 * @package app\common\model
 * @author keinx
 */
class OrderCommon extends Common
{
    protected $pk = 'order_id';

    protected $autoWriteTimestamp = true;
    // protected $createTime = 'created_at';
    // protected $updateTime = 'updated_at';

    /** ====================== */
    /*
     * 0. 待支付,
     * 10.已取消 (10. 已取消（用户取消）20. 已取消(30分钟系统取消))
     * 30. 待发货
     * 40.  待收货
     * 50. 待评价
     *  60.已完成
    */
    const PENDING_PAYMENT       = 0;    //待付款
    const PENDING_DELIVERY      = 30;   //待发货
    const PENDING_RECEIPT       = 40;    //待收货
    const PENDING_EVALUATE      = 50;   //待评价
    const COMPLETED_EVALUATE    = 60; //已评价
    const COMPLETED_WAIT_RETURN     = 70; //退款完成
    const PENDING_WAIT_RETURN       = 80; //等待退款
    const USER_CANCEL           = 10;  //用户取消
    const SYSTEM_CANCEL         = 20;  //30分钟系统取消
    const ORDER_ALL             = 99;  //全部
    const ORDER_BILL             = 15;  //票据
    /** ====================== */

    /**
     * 订单状态名称
     * @var array
     */
    protected $status_name = [
        self::PENDING_PAYMENT       => '待支付',
        self::PENDING_DELIVERY      => '待发货',
        self::PENDING_RECEIPT       => '待收货',
        self::PENDING_EVALUATE      => '待评价',
        self::COMPLETED_EVALUATE    => '已评价',
        self::COMPLETED_WAIT_RETURN     => '退款完成',
        self::PENDING_WAIT_RETURN       => '等待退款',
        self::USER_CANCEL           => '用户取消',
        self::SYSTEM_CANCEL         => '系统取消'
    ];

    //客服审单
    const CUSTOMER_STATE_NO = 1;//待审单
    const CUSTOMER_STATE_YES = 2;//已审单

    protected $customer_state_name = [
        self::CUSTOMER_STATE_NO     => '待审单',
        self::CUSTOMER_STATE_YES     => '已审单',
    ];

    public function getMyId()      { return isset($this->order_id) ? $this->order_id : 0;}
    public function getMyBuyerId(){return isset($this->buyer_id) ? $this->buyer_id : 0;}
    public function getMyOrderId() { return isset($this->order_id) ? $this->order_id : null; }
    public function getMyOrderSN() { return isset($this->order_sn) ? $this->order_sn : null; }
    public function getMySiteId() { return isset($this->site_id) ? $this->site_id : null; }
    public function getMyMallId() { return isset($this->mall_id) ? $this->mall_id : 0;}
    public function getMyOrderState(){return isset($this->order_state) ? $this->order_state : null;}
    public function getMyOrderPrice(){return isset($this->order_price) ? $this->order_price : 0;}
    public function getMyOrderOriginPrice(){
        return isset($this->order_origin_price) ? $this->order_origin_price : 0;
    }
    public function getMyOrderShippingFee(){
        return isset($this->order_shipping_fee) ? $this->order_shipping_fee : 0;
    }
    public function getMyOrderAreaShippingFee(){
        return isset($this->order_area_shipping_fee) ? $this->order_area_shipping_fee : 0;
    }
    public function getMyOrderCreateTime(){return isset($this->order_add_time) ? $this->order_add_time : 0;}
    public function getMyPayTime(){return isset($this->order_pay_time) ? $this->order_pay_time : 0;}
    public function getMyPaySn(){return isset($this->pay_sn) ? $this->pay_sn : '';}
    public function getMyOrderRemark(){return isset($this->order_remark)? $this->order_remark:'';}
    public function getMyCreateType()  {return isset($this->create_type) ? $this->create_type : null;}
    public function getMyOrderFloorNum(){return isset($this->order_floor_num) ? $this->order_floor_num : 0;}
    public function getMyOrderFloorFee(){return isset($this->order_floor_fee) ? $this->order_floor_fee : 0;}
    public function getMyOrderTransportFee(){return isset($this->order_transport_fee) ? $this->order_transport_fee : 0;}
    public function getMyOrderPointPrice(){return isset($this->order_point_price) ? $this->order_point_price : 0;}
    public function getMyIsCredit(){return isset($this->is_credit) ? $this->is_credit : 0;}
    public function getMyIsSales(){return isset($this->is_sales) ? $this->is_sales : 0;}
    public function getMyIsFee(){return isset($this->is_fee) ? $this->is_fee : 0;}
    public function getMyCreditType(){return isset($this->credit_type) ? $this->credit_type : 0;}
    public function getMyOrderDate(){return isset($this->created_at) ? $this->created_at : 0;}
    public function getMyIsExpress(){return isset($this->is_express) ? $this->is_express : 0;}
    public function getMyIsAdd(){return isset($this->is_add) ? $this->is_add : 0;}
    public function getMyIsPlus(){return isset($this->is_plus) ? $this->is_plus : 0;}
    public function getMyAddress(){return isset($this->address) ? $this->address : 0;}
    public function getMyIsShippingType(){return isset($this->is_shipping_type) ? $this->is_shipping_type : 0;}
    public function getMyIsAllReturn(){return isset($this->is_all_return) ? $this->is_all_return : 0;}
    public function getMyOrderPayCode(){return isset($this->order_payment_code) ? $this->order_payment_code : '';}

    /**
     * 获取拍照单信息
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderPhotoDetail($photo_id){
        return $this->where('photo_id',$photo_id)->select();
    }


    /**
     * 添加订单
     * @param $orderInfo
     * @return OROrderCommon
     * @throws ModelException
     */
    function addOrder($orderInfo){

        // try{
        //     $this->arrValidate($orderInfo, [
        //         'mallId'       => 'required',
        //         'siteId'       => 'required',
        //         'buyerId'      => 'required',
        //         'orderType'    => 'required',
        //         'orderSn'      => 'required',
        //         'paySn'        => 'required',
        //         'is_credit'        => 'required',
        //         'is_sales'        => 'required',
        //     ]);
        // }catch(ValidationException $validateException) {
        //     throw new ModelException('添加订单失败,',GlobalErrCode::ERR_PARAM_VALIDATE_ERROR);
        // }

        $orderIns = new OrderCommon();
        $orderIns['mall_id']  = $orderInfo['mallId'];
        $orderIns['site_id']  = $orderInfo['siteId'];
        $orderIns['buyer_id'] = $orderInfo['buyerId'];
        $orderIns['photo_id'] = $orderInfo['photoId'];
        $orderIns['order_type'] = $orderInfo['orderType'];
        $orderIns['order_sn'] = $orderInfo['orderSn'];
        $orderIns['pay_sn']   = $orderInfo['paySn'];
        $orderIns['is_credit']   = $orderInfo['is_credit'];
        $orderIns['is_sales']   = 0;
        $orderIns['credit_type']   = $orderInfo['credit_type'];
        $orderIns['order_add_time']  = time();



        if(isset($orderInfo['orderPrice'])){
            $orderIns['order_price'] = $orderInfo['orderPrice'];
        }

        if(isset($orderInfo['orderPointPrice'])){
            $orderIns['order_point_price'] = $orderInfo['orderPointPrice'];
        }

        if(isset($orderInfo['orderOriginPrice'])){
            $orderIns['order_origin_price'] = $orderInfo['orderOriginPrice'];
        }

        if(isset($orderInfo['orderPlusPrice'])){
            $orderIns['order_plus_price']  = $orderInfo['orderPlusPrice'];
        }

        if(isset($orderInfo['orderShippingFee'])){
            $orderIns['order_shipping_fee']  = $orderInfo['orderShippingFee'];
        }

        if(isset($orderInfo['orderFullActivityAmount'])){
            $orderIns['order_full_activity_amount']  = $orderInfo['orderFullActivityAmount'];
        }

        if(isset($orderInfo['orderActivityAmount'])){
            $orderIns['order_activity_amount']  = $orderInfo['orderActivityAmount'];
        }

        $orderIns['order_state']  = 0;

        if(isset($orderInfo['orderRemark'])){
            $orderIns['order_remark'] = $orderInfo['orderRemark'];
        }

        if(isset($orderInfo['isNeedFloor'])){
            $orderIns['is_need_floor'] = $orderInfo['isNeedFloor'];
        }
        //isShippingType
        if(isset($orderInfo['isShippingType'])){
            $orderIns['is_shipping_type'] = $orderInfo['isShippingType'];
        }

        if(isset($orderInfo['orderFloorNum'])){
            $orderIns['order_floor_num'] = $orderInfo['orderFloorNum'];
        }

        if(isset($orderInfo['orderFloorFee'])){
            $orderIns['order_floor_fee'] = $orderInfo['orderFloorFee'];
        }
        //orderTransportFee
        if(isset($orderInfo['orderTransportFee'])){
            $orderIns['order_transport_fee'] = $orderInfo['orderTransportFee'];
        }

        if(isset($orderInfo['address']))
        {
            $orderIns['address'] = $orderInfo['address'];
        }

        if(isset($orderInfo['isAdd'])){
            $orderIns['is_add'] = $orderInfo['isAdd'];
        }

        if(isset($orderInfo['orderExpressFee'])){
            $orderIns['order_express_fee'] = $orderInfo['orderExpressFee'];
        }

        if(isset($orderInfo['isExpress'])){
            $orderIns['is_express'] = $orderInfo['isExpress'];
        }

        if(isset($orderInfo['isPlus'])){
            $orderIns['is_plus'] = $orderInfo['isPlus'];
        }

        $orderIns['created_at'] = date('Y-m-d H:i:s');
        $orderIns['updated_at'] = date('Y-m-d H:i:s');

        
        $orderIns->save();

        return $orderIns->getMyOrderId();
    }



    /**
     * 获取订单不同状态的数量
     * @param $input
     * @return array
     */
    public function getOrderStatusNum($input)
    {
        $where = [];
        //用户是店铺权限
        if(isset($input['site_id']) && $input['site_id'] != ""){
            $where[] = ['site_id', 'eq', $input['site_id']];
        }
        //用户是区域权限
        if(isset($input['mall_id']) && $input['mall_id'] != ""){
            $where[] = ['mall_id', 'eq', $input['mall_id']];
        }

        $ids = $input['ids'];

        $data = [];
        foreach ($ids as $k => $v)
        {
            $data[$v] = $this->orderCount($v, $where);
        }

        return $data;
    }

    /**
     * 客服审单
     * @param $input
     * @return array
     */
    public function getOrderCustomerStatusNum($inputs)
    {
        $where = [];
        //用户是店铺权限
        if(isset($inputs['site_id']) && $inputs['site_id'] != ""){
            $where[] = ['site_id', 'eq', $inputs['site_id']];
        }
        //用户是区域权限
        if(isset($inputs['mall_id']) && $inputs['mall_id'] != ""){
            $where[] = ['mall_id', 'eq', $inputs['mall_id']];
        }

        $ids = $inputs['ids1'];

        $data = [];
        foreach ($ids as $k => $v)
        {
            $data[$v] = $this->orderCount($v, $where);
        }

        return $data;
    }

    /**
     * 订单数量统计
     * @param string $id
     * @param array $where
     * @return int|string
     */
    protected function orderCount($id = 'all', $where = [])
    {
        $sqlWhere = $this->orderStateSql($id);
       
        if(!empty($sqlWhere)){
            $where[] = $sqlWhere;
        }

        return $this->where($where)
            ->count();
    }

    /**
     * 获取状态的SQL条件
     * @param string $state
     * @return array
     */
    protected function orderStateSql($state = 'all')
    {

        $where = [];
        switch($state)
        {
            case self::PENDING_PAYMENT: //待付款
                $where = ['order_state', 'eq', self::PENDING_PAYMENT];
                break;
            case self::PENDING_DELIVERY: //待发货
                $where = [['order_state', 'eq', self::PENDING_DELIVERY],['order_customer_state','eq',self::CUSTOMER_STATE_YES]];
                break;
            case self::PENDING_RECEIPT: //待收货
                $where = ['order_state', 'eq', self::PENDING_RECEIPT];
                break;
            case self::PENDING_EVALUATE: //待评价
                $where = ['order_state', 'eq', self::PENDING_EVALUATE];
                break;
            case self::COMPLETED_EVALUATE: //已评价
                $where = ['order_state', 'eq', self::COMPLETED_EVALUATE];
                break;
            case self::USER_CANCEL: //已取消订单
                $where = ['order_state', 'in', [self::USER_CANCEL,self::SYSTEM_CANCEL]];
                break;
            case (string)self::CUSTOMER_STATE_NO: //待审单
                $where = [['order_customer_state', 'eq', self::CUSTOMER_STATE_NO],['order_state','eq',self::PENDING_DELIVERY]];
                break;
            default: //全部
                break;
        }

        return $where;
    }

    /**
     * 后台获取数据
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromAdmin($input)
    {
        $result = $this->getListByWhere($input);
        return $result;
    }

    /**
     * 获取订单原始数据
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getListByWhere($input)
    {
        $where = [];

        //用户是店铺权限
        if (isset($input['site_id']) && $input['site_id'] != "") {
            $where[] = ['site_id', 'eq', $input['site_id']];
        }
        //用户是区域权限
        if (isset($input['mall_id']) && $input['mall_id'] != "") {
            $where[] = ['mall_id', 'eq', $input['mall_id']];
        }
        if (isset($input['payment_code']) && trim($input['payment_code']) != '') {
            $where[] = ['order_payment_code', 'eq', trim($input['payment_code'])];
        }
        if (!empty($input['keyWords'])) {
            $orderBuyerInfo = new OrderBuyerInfo();
            //说明这里需要查询出收件人名称/手机的信息
            $paySnList = $orderBuyerInfo->getPayIdsForKeywords($input['keyWords']);
            $where[] = array('order_sn', 'in', $paySnList);

        }

        if (!empty($input['buyerKey'])) {
            $paySnList = $this->getBuyerKeywords($input['buyerKey']);
            $where[] = array('pay_sn', 'in', $paySnList);
        }

        if(!empty($input['buyer_id']))
        {
            $where[] = array('buyer_id', 'eq', $input['buyer_id']);
        }

        if(!empty($input['order_sn']))
        {
            $where[] = array('order_sn', 'LIKE', '%'.$input['order_sn'].'%');
        }

        if(!empty($input['date']))
        {
            $date_string = $input['date'];
            $date_array = explode(' 到 ', $date_string);
            $sdate = strtotime($date_array[0].' 00:00:00');
            $edate = strtotime($date_array[1].' 23:59:59');
            $where[] = ['order_add_time','BETWEEN TIME',[$sdate,$edate]];
        }
        if(!empty($input['start_date']) || !empty($input['end_date']))
        {
            if(!empty($input['start_date']) && !empty($input['end_date']))
            {
                $sdate = strtotime($input['start_date'].' 00:00:00');
                $edate = strtotime($input['end_date'].' 23:59:59');
                $where[] = ['order_add_time','BETWEEM TIME',[$sdate,$edate]];
            }

            elseif(!empty($input['start_date']))
            {
                $sdate = strtotime($input['start_date'].' 00:00:00');
                $where[] = array('order_add_time', '>=', $sdate);
            }
            elseif(!empty($input['end_date']))
            {
                $edate = strtotime($input['end_date'].' 23:59:59');
                $where[] = array('order_add_time', '<=', $edate);
            }
        }
        if(isset($input['order_unified_status']))
        {
            $sqlWhere = $this->orderStateSql($input['order_unified_status']);
            if(!empty($sqlWhere)){
                $where[] = $sqlWhere;
            }
        }


        $page = $input['page']?$input['page']:1;
        $limit = $input['limit']?$input['limit']:20;

        //根据条件获取订单列表
        $data = $this->getOrderList($where,$page,$limit);

        //根据条件获取订单总数
        $count = $this->where($where)->count();

        return array('data' => $data, 'count' => $count);
    }


    public function getBuyerKeywords($keywords)
    {
        $keywords = '%'.$keywords.'%';
        $list = $this
            ->alias('i')
            ->join('ev_buyer b','i.buyer_id = b.buyer_id')//|i.buyer_id
            ->where('b.buyer_name|b.buyer_nickname|b.buyer_tel','LIKE',$keywords)
            ->select();

        return collect($list)->map(function($info){
            return isset($info['pay_sn']) ? $info['pay_sn'] : '';
        })->all();

    }


    /**
     * 根据条件获取订单列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getOrderList($where,$page,$limit)
    {

        $sql = $this->where($where)->order('order_add_time desc');
        if(!empty($page) && !empty($limit)){
            $sql = $sql->page($page, $limit);
        }
        $list = $sql->select();
        return collect($list)->map(function($info){

            $orderBuyerInfo = new OrderBuyerInfo();
            //获取收件信息
            $buyerInfo = $orderBuyerInfo->getPayBuyerInfo($info['order_sn']);

            $Buyers = new Buyer();
            $UserInfo = $Buyers->getBuyerInfoForId($info['buyer_id']);

            return[
                'order_id'              => $info['order_id'],
                'order_sn'              => $info['order_sn'],
                'pay_sn'                => $info['pay_sn'],
                'order_type'            => $info['order_type'], //订单类型 points_order goods_order
                'ctime'                 => $info['order_add_time'],
                'address'               => $info['address'],
                'ship_mobile'           => $buyerInfo['receiver_tel'],
                'ship_address'          => $buyerInfo['receiver_address_info'],
                'ship_name'             => $buyerInfo['receiver_name'],
                'buyer_id'              => $UserInfo['buyer_id'],
                'buyer_name'            => $UserInfo['buyer_name'],
                'buyer_header'          => $UserInfo['buyer_header'],
                'order_price'           => $info['order_type'] =='goods_order' ? $info['order_price'] : $info['order_point_price'],//订单价格
                'order_point_price'     => $info['order_point_price'],//订单价格
                'order_origin_price'     => $info['order_origin_price'],//订单原价格
                'order_full_activity_amount'    => $info['order_full_activity_amount'],//优惠金额
                'order_shipping_fee'    => $info['order_shipping_fee'],//订单运费  
                'order_transport_fee'   => $info['order_transport_fee'],//运输费
                'order_floor_fee'       => $info['order_floor_fee'],//订单挑楼费
                'order_unloading_fee'   => $info['order_unloading_fee'],//订单挑楼费
                'order_bill_price'      => $info['order_bill_price'],//订单挑楼费
                'is_express'       => $info['is_express'],//特快
                'is_unloading'       => $info['is_unloading'],//仅卸货
                'is_bill'               => $info['is_bill'],//仅卸货
                'status'                => isset($info['order_state']) ? $info['order_state'] : $info['order_customer_state'],
                'status_text'           => $this->status_name[$info['order_state']], //订单状态
                'order_payment_code'           => $info['order_payment_code'],
                //前台操作显示的按钮
                'operating' => $this->getOperating($info['order_id'], $info['order_sn'], $info['order_state'], $info['order_customer_state'])
            ];
        })->all();
    }

    /**
     * 获取前台所需要的操作的按钮
     * @param $orderId
     * @param $orderState
     * @return string
     */
    protected function getOperating($orderId, $orderSn, $orderState,$orderCustomerState)
    {

        $OrderReturn = new OrderReturn();
        $returnInfo = $OrderReturn->myInstanceByReturnInfo($orderId,$orderSn);
        //查询订单是否使用优惠劵
        $coupon = (new AreaMallBuyerCouponOrderLog)->where('order_id',$orderId)->find();

        $html = '';
        $html .= '<a class="layui-btn layui-btn-primary layui-btn-xs view-order" data-id="'.$orderId.'">查看</a>';
        if($coupon){
            $html .= '<a class="layui-btn layui-btn-xs edit-order-coupon" data-id="'.$orderId.'">优惠</a>';
        }

        if($orderCustomerState == 1) //待审单
        {
            if(in_array($orderCustomerState,[1]))
            {
                $html .= '<a class="layui-btn layui-btn-primary layui-btn-xs ship-cu-order" data-id="'.$orderId.'">审单</a>';
            }

        }else{

            switch ($orderState){
                //待付款
                case self::PENDING_PAYMENT:

                    $html .= '<a class="layui-btn layui-btn-xs edit-order" data-id="'.$orderId.'">编辑</a>';
                    $html .= '<a class="layui-btn layui-btn-xs cancel-order" data-id="'.$orderId.'">取消</a>';
                    break;
                //待发货
                case self::PENDING_DELIVERY:

                    $html .= '<a class="layui-btn layui-btn-xs edit-order" data-id="'.$orderId.'">编辑</a>';
                    if($returnInfo){
                        if($returnInfo['status'] == 10 && $returnInfo['status'] == 11){
                            $html .= '<a class="layui-btn layui-btn-xs ship-order" data-id="'.$orderId.'">发货</a>';
                        }else{
                            $html .= '<a class="layui-btn layui-btn-xs return-order" data-id="'.$returnInfo['return_id'].'">退货明细</a>';
                        }
                    }else{
                        $html .= '<a class="layui-btn layui-btn-xs ship-order" data-id="'.$orderId.'">发货</a>';
                    }
                    break;
            }
        }
        return $html;
    }

    /**
     * 根据订单ID获取订单详情
     * @param $orderId
     * @return OrderCommon|bool|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfoByOrderID($orderId)
    {
        // 订单信息
        $order_info = $this->get($orderId);

        if(!$order_info){
            return false;
        }

        // 订单状态名称
        $order_info['status_text'] = $this->status_name[$order_info['order_state']];

        //获取订单的商品信息
        $orderGoodsModel = new OrderGoods();
        $orderGoodList = $orderGoodsModel->getGoodsListForOrderId($orderId);
        $order_info['items'] = $orderGoodList;

        //获取订单的收货人信息
        $orderBuyerModel = new OrderBuyerInfo();
        $buyerInfo = $orderBuyerModel->getPayBuyerInfo($order_info['order_sn']);
        $order_info['buyerInfo'] = $buyerInfo;

        //获取订单记录
        $orderLogModel = new OrderLog();
        $order_info['order_log'] = $orderLogModel->getOrderLogListForOrderId($orderId);

        return $order_info;
    }

    /**
     * 订单修改
     * @param $data
     * @param $mallId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($data,$mallId)
    {
        $orderId = $data['order_id'];
        if($data['order_price'] || $data['order_transport_fee'] || $data['order_shipping_fee'] || $data['order_floor_fee'])
        {
            $update['order_price'] = $data['order_price'];//金额
            $update['order_transport_fee'] = $data['order_transport_fee'];//金额
            $update['order_shipping_fee'] = $data['order_shipping_fee'];//金额
            $update['order_floor_fee'] = $data['order_floor_fee'];//金额
            $this->where('order_id', 'eq', $orderId)->update($update);
        }

        $orderIns = $this->where('order_id',$orderId)->find();

        //更新收件人信息
        $orderBuyerModel = new OrderBuyerInfo();
        $orderBuyerModel->updateBuyerInfo($orderIns['pay_sn'],$data);

        //订单记录
        $orderLog = new OrderLog();
        $orderLog->addOrderLog($mallId,
            $orderIns['order_id'],
            $orderIns['order_sn'],
        '管理员修改订单信息',
            $orderIns['order_state']
        );

        return true;
    }

    /**
     * @param array $orderId
     * @param array $dataInfo
     * @return false|int|void
     */
    public function updateData($orderId, $dataInfo=[]){

        $editInfo = [];
        if(isset($dataInfo['orderState'])){
            $editInfo['order_state'] = $dataInfo['orderState'];
        }

        if(isset($dataInfo['order_customer_state'])){
            $editInfo['order_customer_state'] = $dataInfo['order_customer_state'];
        }

        $editInfo['created_at'] = date('Y-m-d H:i:s');
        $editInfo['updated_at'] = date('Y-m-d H:i:s');
        $this->where('order_id', $orderId)->update($editInfo);
    }

    /**
     * 支付单统计
     * @return array
     */
    public function statistics($status){
        $num = 7;
        $day = date('Y-m-d', strtotime('-'.$num.' day'));
        $sql = 'SELECT DATE_FORMAT(from_unixtime(order_add_time),"%Y-%m-%d") as day, count(*) as nums FROM '.
               'ev_order_common WHERE from_unixtime(order_add_time) >= "' . $day . '" AND order_state=' . $status .
               ' GROUP BY DATE_FORMAT(from_unixtime(order_add_time),"%Y-%m-%d")';
        $res = Db::query($sql);
        $data = get_lately_days($num, $res);
        if($status == OrderCommon::PENDING_DELIVERY){
            return ['day' => $data['day'], 'data' => $data['data']];
        }else{
            return $data['data'];
        }
    }

    /**
     * 关联订单商品
     * @return \think\model\relation\BelongsTo
     */
    public function goods()
    {
        return $this->belongsTo('OrderGoods', 'order_id', 'order_id', [], 'LEFT')->setEagerlyType(0);
    }

}