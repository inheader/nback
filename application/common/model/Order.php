<?php
namespace app\common\model;
use think\model\concern\SoftDelete;
use think\Db;

/**
 * 订单主表
 * Class Order
 * @package app\common\model
 * @author keinx
 */
class Order extends Common
{
    protected $pk = 'order_id';

    use SoftDelete;
    protected $deleteTime = 'isdel';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    const ORDER_STATUS_NORMAL = 1;          //订单状态正常
    const ORDER_STATUS_COMPLETE = 2;        //订单状态完成
    const ORDER_STATUS_CANCEL = 3;          //订单状态取消

    const PAY_STATUS_NO = 1;                //未付款
    const PAY_STATUS_YES = 2;               //已付款
    const PAY_STATUS_PARTIAL_YES = 3;       //部分付款
    const PAY_STATUS_PARTIAL_NO = 4;        //部分退款
    const PAY_STATUS_REFUNDED = 5;          //已退款

    const SHIP_STATUS_NO = 1;               //未发货
    const SHIP_STATUS_PARTIAL_YES = 2;      //部分发货
    const SHIP_STATUS_YES = 3;              //已发货
    const SHIP_STATUS_PARTIAL_NO = 4;       //部分退货
    const SHIP_STATUS_RETURNED = 5;         //已退货

    const RECEIPT_NOT_CONFIRMED = 1;        //未确认收货
    const CONFIRM_RECEIPT = 2;              //确认收货

    const NO_COMMENT = 1;                   //没有评价
    const ALREADY_COMMENT = 2;              //已经评价

    const ALL_PENDING_PAYMENT = 'pending_payment';  //总订单类型 待付款
    const ALL_PENDING_DELIVERY = 'pending_delivery';        //待发货
    const ALL_PENDING_RECEIPT = 'pending_receipt';  //待收货
    const ALL_PENDING_EVALUATE = 'pending_evaluate';    //待评价
    const ALL_COMPLETED_EVALUATE = 'completed_evaluate'; //已评价
    const ALL_COMPLETED = 'completed';          //已完成
    const ALL_CANCEL = 'cancel';                //已取消

    protected $status_relation = [
        1 => self::ALL_PENDING_PAYMENT,
        2 => self::ALL_PENDING_DELIVERY,
        3 => self::ALL_PENDING_RECEIPT,
        4 => self::ALL_PENDING_EVALUATE,
        5 => self::ALL_COMPLETED_EVALUATE,
        6 => self::ALL_COMPLETED,
        7 => self::ALL_CANCEL
    ];


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
     * 订单明细表关联
     * @return \think\model\relation\HasMany
     */
    public function items()
    {
        return $this->hasMany('OrderItems');
    }

    /**
     * 订单的用户信息关联
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne('User','id','user_id');
    }

    /**
     * 发货信息关联
     * @return \think\model\relation\HasMany
     */
    public function delivery()
    {
        return $this->hasMany('BillDelivery');
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
        if(!empty($input['order_id']))
        {
            $where[] = array('o.order_id', 'LIKE', '%'.$input['order_id'].'%');
        }
        if(!empty($input['username']))
        {
            $where[] = array('u.username|u.mobile|u.nickname', 'eq', $input['username']);
        }
        if(!empty($input['ship_mobile']))
        {
            $where[] = array('o.ship_mobile', 'eq', $input['ship_mobile']);
        }
        if(!empty($input['pay_status']))
        {
            $where[] = array('o.pay_status', 'eq', $input['pay_status']);
        }
        if(!empty($input['ship_status']))
        {
            $where[] = array('o.ship_status', 'eq', $input['ship_status']);
        }

        //用户是店铺权限
        if(isset($input['site_id']) && $input['site_id'] != ""){
            $where[] = ['site_id', 'eq', $input['site_id']];
        }
        //用户是区域权限
        if(isset($input['mall_id']) && $input['mall_id'] != ""){
            $where[] = ['mall_id', 'eq', $input['mall_id']];
        }

        if(!empty($input['date']))
        {
            $date_string = $input['date'];
            $date_array = explode(' 到 ', $date_string);
            $sdate = strtotime($date_array[0].' 00:00:00')*100;
            $edate = strtotime($date_array[1].' 23:59:59')*100;
            $where[] = array('o.ctime', ['>=', $sdate], ['<=', $edate], 'and');
        }
        if(!empty($input['start_date']) || !empty($input['end_date']))
        {
            if(!empty($input['start_date']) && !empty($input['end_date']))
            {
                $sdate = strtotime($input['start_date'].' 00:00:00')*100;
                $edate = strtotime($input['end_date'].' 23:59:59')*100;
                $where[] = array('o.ctime', ['>=', $sdate], ['<=', $edate], 'and');
            }
            elseif(!empty($input['start_date']))
            {
                $sdate = strtotime($input['start_date'].' 00:00:00')*100;
                $where[] = array('o.ctime', '>=', $sdate);
            }
            elseif(!empty($input['end_date']))
            {
                $edate = strtotime($input['end_date'].' 23:59:59')*100;
                $where[] = array('o.ctime', '<=', $edate);
            }
        }
        if(!empty($input['source']))
        {
            $where[] = array('o.source', 'eq', $input['source']);
        }
        if(!empty($input['user_id']))
        {
            $where[] = array('o.user_id', 'eq', $input['user_id']);
        }
        if(!empty($input['order_unified_status']))
        {
            $where = array_merge($where, $this->getReverseStatus($this->status_relation[$input['order_unified_status']], '`o`.'));
        }

        $page = $input['page']?$input['page']:1;
        $limit = $input['limit']?$input['limit']:20;

        $data = $this->alias('o')
            ->field('o.order_id, o.user_id, o.ctime, o.ship_mobile, o.ship_address, o.status, o.pay_status, o.ship_status, o.confirm, o.is_comment, o.order_amount, o.source, o.ship_area_id')
            ->join(config('database.prefix').'user u', 'o.user_id = u.id', 'left')
            ->where($where)
            ->order('ctime desc')
            ->page($page, $limit)
            ->select();

        $count = $this->alias('o')
            ->field('o.order_id, o.user_id, o.ctime, o.ship_mobile, o.ship_address, o.status, o.pay_status, o.ship_status, o.confirm, o.is_comment, o.order_amount, o.source, o.ship_area_id')
            ->join(config('database.prefix').'user u', 'o.user_id = u.id', 'left')
            ->where($where)
            ->count();

        return array('data' => $data, 'count' => $count);
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

        if(count($result['data']) > 0)
        {
            foreach($result['data'] as $k => &$v)
            {
                $v['status_text'] = config('params.order')['status_text'][$this->getStatus($v['status'], $v['pay_status'], $v['ship_status'], $v['confirm'], $v['is_comment'])];
                $v['username'] = get_user_info($v['user_id'], 'nickname');
                $v['operating'] = $this->getOperating($v['order_id'], $v['status'], $v['pay_status'], $v['ship_status']);
                $v['area_name'] = get_area($v['ship_area_id']).'-'.$v['ship_address'];
                $v['pay_status'] = config('params.order')['pay_status'][$v['pay_status']];
                $v['ship_status'] = config('params.order')['ship_status'][$v['ship_status']];
                $v['source'] = config('params.order')['source'][$v['source']];
            }
        }
        return $result;
    }

    /**
     * 总后台获取数据
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromManage($input)
    {
        $result = $this->getListByWhere($input);

        if(count($result['data']) > 0)
        {
            foreach($result['data'] as $k => &$v)
            {
                $v['status_text'] = config('params.order')['status_text'][$this->getStatus($v['status'], $v['pay_status'], $v['ship_status'], $v['confirm'], $v['is_comment'])];
                $v['username'] = get_user_info($v['user_id'], 'nickname');
                $v['operating'] = $this->getOperating($v['order_id'], $v['status'], $v['pay_status'], $v['ship_status'], 'manage');
                $v['area_name'] = get_area($v['ship_area_id']).'-'.$v['ship_address'];
                $v['pay_status'] = config('params.order')['pay_status'][$v['pay_status']];
                $v['ship_status'] = config('params.order')['ship_status'][$v['ship_status']];
                $v['source'] = config('params.order')['source'][$v['source']];
            }
        }
        return $result;
    }

    /**
     * API获取数据
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromApi($input)
    {
        $return_data = $this->getListByWhere($input);
        return $return_data;
    }

    /**
     * 获取待发货列表
     * @param $input
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function getWaitListFromAdmin($input)
//    {
//        $input['pay_status'] = self::PAY_STATUS_YES;
//        $input['ship_status'] = self::SHIP_STATUS_NO;
//
//        $result = $this->getListByWhere($input);
//
//        if(count($result['data']) > 0)
//        {
//            foreach($result['data'] as $k => &$v)
//            {
//                $v['username'] = get_user_info($v['user_id'], 'nickname');
//                $v['operating'] = $this->getOperating($v['order_id'], $v['status'], $v['pay_status'], $v['ship_status']);
//                $v['area_name'] = get_area($v['ship_area_id']).'-'.$v['ship_address'];
//                $v['pay_status'] = config('params.order')['pay_status'][$v['pay_status']];
//                $v['ship_status'] = config('params.order')['ship_status'][$v['ship_status']];
//                $v['source'] = config('params.order')['source'][$v['source']];
//            }
//        }
//        return $result;
//    } 

    /**
     * 获取订单列表微信小程序
     * @param $input
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListFromWxApi($input)
    {
        $where = [];
        if(!empty($input['status']))
        {
            switch ($input['status']) {
                case 1: //待付款
                    $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                    $where[] = ['pay_status', 'eq', self::PAY_STATUS_NO];
                    break;
                case 2: //待发货
                    $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                    $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                    $where[] = ['ship_status', 'eq', self::SHIP_STATUS_NO];
                    break;
                case 3: //待收货
                    $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                    $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                    $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
                    $where[] = ['confirm', 'eq', self::RECEIPT_NOT_CONFIRMED];
                    break;
                case 4: //待评价
                    $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                    $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                    $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
                    $where[] = ['confirm', 'eq', self::CONFIRM_RECEIPT];
                    $where[] = ['is_comment', 'eq', self::NO_COMMENT];
                    break;
            }
        }
        if(!empty($input['user_id']))
        {
            $where[] = array('user_id', 'eq', $input['user_id']);
        }

        $page = $input['page']?$input['page']:1;
        $limit = $input['limit']?$input['limit']:20;

        $data = $this::with('items')->where($where)
            ->order('ctime desc')
            ->page($page, $limit)
            ->select();

        $count = $this->where($where)
            ->count();
        return array('data' => $data, 'count' => $count);
    }

    /**
     * 获取订单不同状态的数量
     * @param $input
     * @return array
     */
    public function getOrderStatusNum($input)
    {
        $ids = explode(",", $input['ids']);
        if($input['user_id'])
        {
            $user_id = $input['user_id'];
        }
        else
        {
            $user_id = false;
        }

        $data = [];
        foreach ($ids as $k => $v)
        {
            $data[$v] = $this->orderCount($v, $user_id);
        }
        return $data;
    }

    /**
     * 订单数量统计
     * @param $id
     * @param bool $user_id
     * @return int|string
     */
    protected function orderCount($id = 'all', $user_id = false)
    {
        $where = [];
        //都需要验证的
        if($user_id)
        {
            $where[] = ['user_id', 'eq', $user_id];
        }

        switch($id)
        {
            case 0: //待付款
                $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                $where[] = ['pay_status', 'eq', self::PAY_STATUS_NO];
                break;
            case 30: //待发货
                $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                $where[] = ['ship_status', 'eq', self::SHIP_STATUS_NO];
                break;
            case 40: //待收货
                $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
                $where[] = ['confirm', 'eq', self::RECEIPT_NOT_CONFIRMED];
                break;
            case 50: //待评价
                $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
                $where[] = ['confirm', 'eq', self::CONFIRM_RECEIPT];
                $where[] = ['is_comment', 'eq', self::NO_COMMENT];
                break;
            case 60: //已评价
                $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
                $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
                $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
                $where[] = ['confirm', 'eq', self::CONFIRM_RECEIPT];
                $where[] = ['is_comment', 'eq', self::ALREADY_COMMENT];
                break;
            case 10: //已取消订单
                $where[] = ['status', 'eq', self::ORDER_STATUS_CANCEL];
                break;
            case 7: //已完成订单
                $where[] = ['status', 'eq', self::ORDER_STATUS_COMPLETE];
                break;
            default: //全部
                break;
        }

        return $this->where($where)
            ->count();
    }

    /**
     * 根据订单状态生成不同的操作按钮
     * @param $id
     * @param $order_status
     * @param $pay_status
     * @param $ship_status
     * @param string $from
     * @return string
     */
    protected function getOperating($id, $order_status, $pay_status, $ship_status, $from = 'seller')
    {
        $html = '<a class="layui-btn layui-btn-primary layui-btn-xs view-order" data-id="'.$id.'">查看</a>';

        if($order_status == self::ORDER_STATUS_NORMAL)
        {
            //正常
            if($pay_status == self::PAY_STATUS_NO && $from == 'seller')
            {
                $html .= '<a class="layui-btn layui-btn-xs pay-order" data-id="'.$id.'">支付</a>';
            }
            if($pay_status != self::PAY_STATUS_NO)
            {
                if(($ship_status == self::SHIP_STATUS_NO || $ship_status == self::SHIP_STATUS_PARTIAL_YES) && $from == 'seller')
                {
                    $html .= '<a class="layui-btn layui-btn-xs edit-order" data-id="'.$id.'">编辑</a>';
                    $html .= '<a class="layui-btn layui-btn-xs ship-order" data-id="'.$id.'">发货</a>';
                }
                $html .= '<a class="layui-btn layui-btn-xs complete-order" data-id="'.$id.'">完成</a>';

            }
            if($pay_status == self::PAY_STATUS_NO)
            {
                if($from == 'seller')
                {
                    $html .= '<a class="layui-btn layui-btn-xs edit-order" data-id="'.$id.'" data-type="1">编辑</a>';
                }
                $html .= '<a class="layui-btn layui-btn-xs cancel-order" data-id="'.$id.'">取消</a>';
            }
        }
        if ($order_status == self::ORDER_STATUS_CANCEL)
        {
            $html .= '<a class="layui-btn layui-btn-danger layui-btn-xs del-order" data-id="'.$id.'">删除</a>';
        }

        return $html;
    }

    /**
     * 获取订单信息
     * @param $id
     * @param $user_id
     * @return bool|null|static
     * @throws \think\exception\DbException
     */
    public function getOrderInfoByOrderID($id, $user_id = false)
    {
        $order_info = $this->get($id); //订单信息

        if(!$order_info){
            return false;
        }

        if($user_id)
        {
            if($user_id != $order_info['user_id'])
            {
                return false;
            }
        }

        $order_info->items; //订单详情
        $order_info->user; //用户信息
        $order_info->delivery; //发货信息
        foreach($order_info['delivery'] as &$v)
        {
            $v['logi_name'] = get_logi_info($v['logi_code'], 'logi_name');
        }

        $order_info->hidden(['user'=>['isdel', 'password']]);

        if($order_info['logistics_id'])
        {
            $w[] = ['id', 'eq', $order_info['logistics_id']];
            $order_info['logistics'] = model('common/Ship')->where($w)->find();
        }
        else
        {
            $order_info['logistics'] = null;
        }
        $order_info['text_status'] = $this->getStatus($order_info['status'], $order_info['pay_status'], $order_info['ship_status'], $order_info['confirm'], $order_info['is_comment']);
        $order_info['ship_area_name'] = get_area($order_info['ship_area_id']);

        //获取该状态截止时间
        switch($order_info['text_status'])
        {
            case self::ALL_PENDING_PAYMENT: //待付款
                $cancel = 7*86400;
                $ctime = $order_info['ctime'];
                $remaining = $ctime + $cancel - time();
                $order_info['remaining'] = $this->dateTimeTransformation($remaining);
                break;
            case self::ALL_PENDING_RECEIPT: //待收货
                $sign = 15*86400;
                $utime = $order_info['utime'];
                $remaining = $utime + $sign - time();
                $order_info['remaining'] = $this->dateTimeTransformation($remaining);
                break;
            case self::ALL_PENDING_EVALUATE: //待评价
                $eval = 15*86400;
                $confirm = $order_info['confirm_time'];
                $remaining = $confirm + $eval - time();
                $order_info['remaining'] = $this->dateTimeTransformation($remaining);
                break;
            default:
                $order_info['remaining'] = false;
                break;
        }

        return $order_info;
    }

    /**
     * 时间转换
     * @param $time
     * @return false|string
     */
    protected function dateTimeTransformation($time)
    {
        if($time/86400 >=2)
        {
            return floor($time/86400).'天';
        }
        elseif($time/86400 < 2 && $time/86400 >= 1)
        {
            return '1天'.date('G小时i分钟', $time-86400);
        }
        else
        {
            return date('G小时i分钟', $time);
        }
    }

    /**
     * 获取状态
     * @param $status
     * @param $pay_status
     * @param $ship_status
     * @param $confirm
     * @param $is_comment
     * @return string
     */
    protected function getStatus($status, $pay_status, $ship_status, $confirm, $is_comment)
    {
        if ($status == self::ORDER_STATUS_NORMAL && $pay_status == self::PAY_STATUS_NO)
        {
            //待付款
            return self::ALL_PENDING_PAYMENT;
        }
        elseif ($status == self::ORDER_STATUS_NORMAL && $pay_status == self::PAY_STATUS_YES && $ship_status == self::SHIP_STATUS_NO)
        {
            //待发货
            return self::ALL_PENDING_DELIVERY;
        }
        elseif ($status == self::ORDER_STATUS_NORMAL && $ship_status == self::SHIP_STATUS_YES && $confirm == self::RECEIPT_NOT_CONFIRMED)
        {
            //待收货
            return self::ALL_PENDING_RECEIPT;
        }
        elseif ($status == self::ORDER_STATUS_NORMAL && $pay_status > 1 && $ship_status == self::SHIP_STATUS_YES && $confirm == self::CONFIRM_RECEIPT && $is_comment == self::NO_COMMENT)
        {
            //待评价
            return self::ALL_PENDING_EVALUATE;
        }
        elseif ($status == self::ORDER_STATUS_COMPLETE)
        {
            //已完成
            return self::ALL_COMPLETED;
        }
        elseif ($status == self::ORDER_STATUS_CANCEL)
        {
            //已取消
            return self::ALL_CANCEL;
        }
    }

    /**
     * 获取订单状态反查
     * @param $status
     * @param string $table_name
     * @return array
     */
    protected function getReverseStatus($status, $table_name = '')
    {
        $where = [];
        switch($status)
        {
            case self::ALL_PENDING_PAYMENT: //待付款
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name.'pay_status', 'eq', self::PAY_STATUS_NO]
                ];
                break;
            case self::ALL_PENDING_DELIVERY: //待发货
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name.'pay_status', 'eq', self::PAY_STATUS_YES],
                    [$table_name.'ship_status', 'eq', self::SHIP_STATUS_NO]
                ];
                break;
            case self::ALL_PENDING_RECEIPT: //待收货
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name.'ship_status', 'eq', self::SHIP_STATUS_YES],
                    [$table_name.'confirm', 'eq', self::RECEIPT_NOT_CONFIRMED]
                ];
                break;
            case self::ALL_PENDING_EVALUATE: //待评价
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name.'pay_status', '>', self::PAY_STATUS_NO],
                    [$table_name.'ship_status', 'eq', self::SHIP_STATUS_YES],
                    [$table_name.'confirm', 'eq', self::CONFIRM_RECEIPT],
                    [$table_name.'is_comment', 'eq', self::NO_COMMENT]
                ];
                break;
            case self::ALL_COMPLETED_EVALUATE: //已评价
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_NORMAL],
                    [$table_name.'pay_status', '>', self::PAY_STATUS_NO],
                    [$table_name.'ship_status', 'eq', self::SHIP_STATUS_YES],
                    [$table_name.'confirm', 'eq', self::CONFIRM_RECEIPT],
                    [$table_name.'is_comment', 'eq', self::ALREADY_COMMENT]
                ];
                break;
            case self::ALL_CANCEL: //已取消
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_CANCEL]
                ];
                break;
            case self::ALL_COMPLETED: //已完成
                $where = [
                    [$table_name.'status', 'eq', self::ORDER_STATUS_COMPLETE]
                ];
                break;
            default:
                break;
        }

        return $where;
    }

    /**
     * 完成订单操作
     * @param $id
     * @return static
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function complete($id)
    {
        $where[] = ['order_id', 'eq', $id];
        $where[] = ['pay_status', 'neq', self::PAY_STATUS_NO];

        $data['status'] = self::ORDER_STATUS_COMPLETE;
        $data['utime'] = time();

        $info = $this->where($where)
            ->find();
        if($info)
        {
            $result = $this->where($where)
                ->update($data);
            //奖励积分
            $money = $info['order_amount']-$info['cost_freight'];
            $userPointLog = new UserPointLog();
            $userPointLog->orderComplete($info['user_id'], $info['seller_id'], $money, $info['order_id']);
            //订单记录
            $orderLog = new OrderLog();
            $orderLog->addLog($info['order_id'], $info['user_id'], $info['seller_id'], $orderLog::LOG_TYPE_COMPLETE, '后台订单完成操作', $where);
        }
        else
        {
            $result = false;
        }

        return $result;
    }

    /**
     * 取消订单操作
     * @param $id
     * @param bool $user_id
     * @return bool|static
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel($id,$user_id = false)
    {
        $where[] = array('order_id', 'in', $id);
        $where[] = array('pay_status', 'eq', self::PAY_STATUS_NO);
        $where[] = array('status', 'eq', self::ORDER_STATUS_NORMAL);
        $where[] = array('ship_status', 'eq', self::SHIP_STATUS_NO);

        if($user_id)
        {
            $where[] = array('user_id', 'eq', $user_id);
        }

        $order_info = $this->where($where)
            ->select();

        if($order_info)
        {
            Db::startTrans();
            try{
                //更改状态和库存
                $order_ids = [];
                $orderLog = new OrderLog();
                foreach ($order_info as $k => $v)
                {
                    $order_ids[] = $v['order_id'];
                    //订单记录
                    $orderLog->addLog($v['order_id'], $v['user_id'], $orderLog::LOG_TYPE_CANCEL, '订单取消操作', $where);
                }
                //状态修改
                $w[] = ['order_id', 'in', $order_ids];
                $d['status'] = self::ORDER_STATUS_CANCEL;
                $d['utime'] = time();
                $this->where($w)
                    ->update($d);
                $itemModel = new OrderItems();
                $goods = $itemModel->field('product_id, nums')->where($w)->select();
                $goodsModel = new Goods();
                foreach ($goods as $v)
                {
                    $goodsModel->changeStock($v['product_id'], 'cancel', $v['nums']);
                }
                $result = true;
                Db::commit();
            }catch(\Exception $e){
                $result = false;
                Db::rollback();
            }
        }
        else
        {
            $result = false;
        }

        return $result;
    }

    /**
     * 删除订单
     * @param $order_ids
     * @param $user_id
     * @param $seller_id
     * @return int
     */
    public function del($order_ids, $user_id)
    {
        $where[] = array('order_id', 'in', $order_ids);
        $where[] = array('user_id', 'eq', $user_id);

        $result = $this->where($where)
            ->delete();
        return $result;
    }

    /**
     * 获取支付订单信息
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayInfo($id)
    {
        return $this->where('order_id', 'eq', $id)->find();
    }

    /**
     * 编辑保存订单
     * @param $data
     * @return static
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($data)
    {
        $update = [
            'ship_area_id' => $data['ship_area_id'],
            'ship_address' => $data['ship_address'],
            'ship_name' => $data['ship_name'],
            'ship_mobile' => $data['ship_mobile']
        ];
        if($data['order_amount'])
        {
            $update['order_amount'] = $data['order_amount'];
        }
        $res = $this->where('order_id', 'eq', $data['order_id'])
            ->update($update);

        //订单记录
        $orderLog = new OrderLog();
        $w[] = ['order_id', 'eq', $data['order_id']];
        $info = $this->where($w)
            ->find();
        $orderLog->addLog($info['order_id'], $info['user_id'], $info['seller_id'], $orderLog::LOG_TYPE_EDIT, '后台订单编辑修改', $update);

        return $res;
    }

    /**
     * 获取需要发货的信息
     * @param $id
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderShipInfo($id)
    {
        $where[] = array('pay_status', 'neq', self::PAY_STATUS_NO);
        $where[] = array('order_id', 'eq', $id);
        $where[] = array('ship_status', 'in', self::SHIP_STATUS_NO.','.self::SHIP_STATUS_PARTIAL_YES);
        $where[] = array('status', 'eq', 1);

        $order = $this->field('order_id, logistics_id, logistics_name, cost_freight, ship_area_id, ship_address, ship_name, ship_mobile, weight, memo')
            ->where($where)
            ->find();

        $order['ship_area_id'] = get_area($order['ship_area_id']);

        return $order;
    }

    /**
     * 发货改状态
     * @param $order_id
     * @return false|int
     */
    public function ship($order_id)
    {
        $ship_status = model('common/OrderItems')->isAllShip($order_id);

        if($ship_status == 'all')
        {
            $order_data['ship_status'] = self::SHIP_STATUS_YES;
        }
        else
        {
            $order_data['ship_status'] = self::SHIP_STATUS_PARTIAL_YES;
        }
        $where[] = ['order_id', 'eq', $order_id];
        return $this->save($order_data, $where);
    }

    /**
     * 支付
     * @param $order_id
     * @param $payment_code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pay($order_id, $payment_code)
    {
        $return_data = array(
            'status' => false,
            'msg' => '订单支付失败',
            'data' => array()
        );

        $w[] = ['order_id', 'eq', $order_id];
        $w[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $order = $this->where($w)
            ->find();

        if(!$order)
        {
            return $return_data;
        }

        if($order['pay_status'] == self::PAY_STATUS_YES || $order['pay_status'] == self::PAY_STATUS_PARTIAL_NO || $order['pay_status'] == self::PAY_STATUS_REFUNDED)
        {
            $return_data['msg'] = '订单支付失败，该订单已支付';
            $return_data['data'] = $order;
        }
        else
        {
            $data['payment_code'] = $payment_code;
            $data['payed'] = $order['order_amount'];
            $data['pay_status'] = self::PAY_STATUS_YES;
            $data['payment_time'] = time();
            $result = $this->where('order_id', 'eq', $order_id)
                ->update($data);

            $return_data['data'] = $result;
            if($result !== false)
            {
                $return_data['status'] = true;
                $return_data['msg'] = '订单支付成功';

                //发送支付成功信息,增加发送内容
                $order['pay_time']  = date('Y-m-d H:i:s', $data['payment_time']);
                $order['money']     = $order['order_amount'];
                $order['user_name'] = get_user_info($order['user_id']);
                sendMessage($order['user_id'], 'order_payed', $order);
            }
        }

        //订单记录
        $orderLog = new OrderLog();
        $orderLog->addLog($order_id, $order['user_id'], $orderLog::LOG_TYPE_PAY, $return_data['msg'], [$return_data, $data]);

        return $return_data;
    }

    /**
     * 确认签收
     * @param $order_id
     * @param $user_id
     * @param $seller_id
     * @return false|int
     */
    public function confirm($order_id, $user_id)
    {
        $where[] = array('order_id', 'eq', $order_id);
        $where[] = array('pay_status', 'neq', self::PAY_STATUS_NO);
        $where[] = array('ship_status', 'neq', self::SHIP_STATUS_NO);
        $where[] = array('status', 'eq', self::ORDER_STATUS_NORMAL);
        $where[] = array('confirm', 'neq', self::CONFIRM_RECEIPT);
        $where[] = array('user_id', 'eq', $user_id);

        $data['confirm'] = self::CONFIRM_RECEIPT;
        $data['confirm_time'] = time();

        Db::startTrans();
        try{
            //修改订单
            $this->save($data, $where);

            //修改发货单
            model('common/BillDelivery')->confirm($order_id);

            //订单记录
            $orderLog = new OrderLog();
            $w[] = ['order_id', 'eq', $order_id];
            $info = $this->where($w)
                ->find();
            $orderLog->addLog($order_id, $info['user_id'], $orderLog::LOG_TYPE_SIGN, '确认签收操作', $where);

            $return = true;
            Db::commit();
        }catch(\Exception $e){
            Db::rollback();
            $return = false;
        }
        return $return;
    }

    /**
     * 生成订单方法
     * @param $user_id
     * @param $cart_ids
     * @param $uship_id
     * @param $memo
     * @param $area_id
     * @param int $point
     * @param bool $coupon_code
     * @param bool $formId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function toAdd($user_id,$cart_ids,$uship_id,$memo,$area_id,$point=0,$coupon_code=false, $formId = false)
    {
        $result = [
            'status' => false,
            'data' => array(),
            'msg' => ''
        ];
        $ushopModel = new UserShip();
        $ushopInfo = $ushopModel->getShipById($uship_id,$user_id);
        if(!$ushopInfo){
            return error_code(11050);
        }
        $orderInfo = $this->formatOrderItems($user_id,$cart_ids,$area_id,$point,$coupon_code);

        if(!$orderInfo['status']){
            return $orderInfo;
        }
        if(!isset($orderInfo['data']['items']) || count($orderInfo['data']['items']) <=0){
            return error_code(11100);
        }

        $order['order_id'] = get_sn(1);
        $order['goods_amount'] = $orderInfo['data']['goods_amount'];
        $order['order_amount'] = $orderInfo['data']['amount'];
        if($order['order_amount'] <= 0)
        {
            $order['pay_status'] = 2;
            $order['payment_time'] = time();
        }
        $order['cost_freight'] = $orderInfo['data']['cost_freight'];
        $order['user_id'] = $user_id;

        //收货地址信息
        $order['ship_area_id'] = $ushopInfo['area_id'];
        $order['ship_address'] = $ushopInfo['address'];
        $order['ship_name'] = $ushopInfo['name'];
        $order['ship_mobile'] = $ushopInfo['mobile'];
        $shipInfo = model('common/Ship')->getShip($ushopInfo['area_id']);
        $order['logistics_id'] = $shipInfo['id'];
        $order['cost_freight'] = model('common/Ship')->getShipCost($ushopInfo['area_id'], $orderInfo['data']['weight'],$order['goods_amount']);

        //积分使用情况
        $order['point'] = $orderInfo['data']['point'];
        $order['point_money'] = $orderInfo['data']['point_money'];

        $order['weight'] = $orderInfo['data']['weight'];;
        $order['order_pmt'] = isset($orderInfo['data']['order_pmt'])?$orderInfo['data']['order_pmt']:0;
        $order['goods_pmt'] = isset($orderInfo['data']['goods_pmt'])?$orderInfo['data']['goods_pmt']:0;
        $order['coupon_pmt'] = $orderInfo['data']['coupon_pmt'];
        $order['coupon'] = json_encode($orderInfo['data']['coupon']);
        if(isset($orderInfo['promotion_list'])){
                $promotion_list = [];
                foreach($orderInfo['promotion_list'] as $k => $v){
                    $promotion_list[$k] = $v['name'];
                }
            $item['promotion_list'] = json_encode($promotion_list);
        }
        $order['memo'] = $memo;
        $order['ip'] = get_client_ip();
        Db::startTrans();
        try {
            $this->save($order);
            //上面保存好收款单表，下面保存收款单明细表，更改库存
            $goodsModel = new Goods();
            foreach($orderInfo['data']['items'] as $k => $v){
                $orderInfo['data']['items'][$k]['order_id'] = $order['order_id'];
                //更改库存
                $goodsModel->changeStock($v['product_id'], 'order', $v['nums']);
            }
            $orderItemsModel = new OrderItems();
            $orderItemsModel->saveAll($orderInfo['data']['items']);

            //优惠券核销
            if($coupon_code)
            {
                $coupon = new Coupon();
                $coupon_res = $coupon->usedMultipleCoupon($coupon_code, $user_id);
                if(!$coupon_res['status'])
                {
                    return $coupon_res;
                }
            }

            //积分核销
            if($order['point'] > 0)
            {
                $userPointLog = new UserPointLog();
                $userPointLog->setPoint($user_id, 0-$order['point'], $userPointLog::POINT_TYPE_DISCOUNT, $remarks = '');
            }

            //消息模板存储
            if($formId)
            {
                $templateMessageModel = new TemplateMessage();
                $message = [
                    'type' => $templateMessageModel::TYPE_ORDER,
                    'code' => $order['order_id'],
                    'form_id' => $formId,
                    'status' => $templateMessageModel::SEND_STATUS_NO
                ];
                $templateMessageModel->addSend($message);
            }

            //清除购物车信息
            $cartModel = new Cart();
            $cartModel->del($user_id,$cart_ids);

            //订单记录
            $orderLog = new OrderLog();
            $orderLog->addLog($order['order_id'], $user_id, $orderLog::LOG_TYPE_CREATE, '订单创建', $order);

            $result['status'] = true;
            $result['data']   = $order;
            //添加订单其它信息，发送短信时使用
            $shipModel          = new Ship();
            $ship               = $shipModel->getInfo(['id' => $order['logistics_id']]);
            $order['ship_id']   = $ship['name'];
            $order['ship_addr'] = get_area($order['ship_area_id']) . $order['ship_address'];
            Db::commit();

            sendMessage($user_id, 'create_order', $order);
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            $result['msg'] = $e->getMessage();
            return $result;
        }
    }

    /**
     * 订单前执行
     * @param $user_id
     * @param $cart_ids
     * @param $area_id
     * @param $point
     * @param $coupon_code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function formatOrderItems($user_id,$cart_ids,$area_id,$point, $coupon_code)
    {
        $cartModel = new Cart();
        $cartList = $cartModel->info($user_id,$cart_ids,'',$area_id, $point, $coupon_code);
        if(!$cartList['status']){
            return $cartList;
        }
        foreach($cartList['data']['list'] as $v){
            $item['goods_id'] = $v['products']['goods_id'];
            $item['product_id'] = $v['products']['id'];
            $item['sn'] = $v['products']['sn'];
            $item['bn'] = $v['products']['bn'];
            $item['name'] = $v['products']['name'];
            $item['price'] = $v['products']['price'];
            $item['image_url'] = get_goods_info($v['products']['goods_id'],'image_id');
            $item['nums'] = $v['nums'];
            $item['amount'] = $v['products']['amount'];
            $item['promotion_amount'] = isset($v['products']['promotion_amount'])?$v['products']['promotion_amount']:0;
            $item['weight'] = $v['weight'];
            $item['sendnums'] = 0;
            $item['addon'] = $v['products']['spes_desc'];
            if(isset($v['products']['promotion_list'])){
                $promotion_list = [];
                foreach($v['products']['promotion_list'] as $k =>$v){
                    $promotion_list[$k] = $v['name'];
                }
                $item['promotion_list'] = json_encode($promotion_list);
            }
            $cartList['data']['items'][] = $item;
        }
        //unset($cartList['data']['list']);
        return $cartList;
    }

    /**
     * 判断订单是否可以评价
     * @param $order_id
     * @param $seller_id
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isOrderComment($order_id, $user_id)
    {
        $where[] = ['order_id', 'eq', $order_id];
        $where[] = ['user_id', 'eq', $user_id];

        $res = $this->where($where)
            ->find();
        if($res)
        {
            if($res['pay_status'] > self::PAY_STATUS_NO && $res['status'] == self::ORDER_STATUS_NORMAL && $res['ship_status'] > self::SHIP_STATUS_NO && $res['is_comment'] == self::NO_COMMENT)
            {
                $data = [
                    'status' => true,
                    'msg' => '可以评价',
                    'data' => $res
                ];
            }
            else
            {
                $data = [
                    'status' => false,
                    'msg' => '订单状态存在问题，不能评价',
                    'data' => $res
                ];
            }
        }
        else
        {
            $data = [
                'status' => false,
                'msg' => '不存在这个订单',
                'data' => $res
            ];
        }
        return $data;
    }

    /**
     * 自动取消订单
     * @param $setting
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function autoCancel($setting)
    {
        $orderLog = new OrderLog();
        unset($where);
        $where[] = ['pay_status', 'eq', self::PAY_STATUS_NO];
        $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[] = ['ctime', '<=', time()-$setting*86400];

        $order_info = $this->where($where)
            ->select();

        if(count($order_info)>0)
        {
            Db::startTrans();
            try{
                //更改状态和库存
                unset($order_ids);
                $order_ids = [];
                foreach ($order_info as $kk => $vv)
                {
                    $order_ids[] = $vv['order_id'];

                    //订单记录
                    $orderLog->addLog($vv['order_id'], $vv['user_id'],$orderLog::LOG_TYPE_AUTO_CANCEL, '订单后台自动取消', $vv);
                }
                //状态修改
                unset($w);
                $w[] = ['order_id', 'in', $order_ids];
                $d['status'] = self::ORDER_STATUS_CANCEL;
                $d['utime'] = time();
                $this->where($w)
                    ->update($d);

                //修改库存
                $itemModel = new OrderItems();
                $goods = $itemModel->field('product_id, nums')->where($w)->select();
                $goodsModel = new Goods();
                foreach ($goods as $vv)
                {
                    $goodsModel->changeStock($vv['product_id'], 'cancel', $vv['nums']);
                }
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
            }
        }

    }

    /**
     * 自动签收订单
     * @param $setting
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function autoSign($setting)
    {
        $orderLog = new OrderLog();

        $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $where[] = ['ship_status', 'eq', self::SHIP_STATUS_YES];
        $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[] = ['utime', '<=', time() - $setting * 86400];

        $order_list = $this->field('order_id, user_id')->where($where)->select();
        if (count($order_list) > 0) {
            unset($order_ids);
            unset($wh);
            $order_ids = [];
            foreach ($order_list as $vv) {
                $order_ids[] = $vv['order_id'];
            }
            $wh[]                 = ['order_id', 'in', $order_ids];
            $data['confirm']      = self::CONFIRM_RECEIPT;
            $data['confirm_time'] = time();
            $data['utime']        = time();
            $this->where($wh)->update($data);

            //订单记录
            $orderLog->addLogs($order_list, $orderLog::LOG_TYPE_AUTO_SIGN, '订单后台自动签收', $where);
        }

    }

    /**
     * 自动评价订单
     * @param $setting
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function autoEvaluate($setting)
    {
        $orderLog = new OrderLog();

        //查询订单
        $where[]    = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $where[]    = ['ship_status', 'eq', self::SHIP_STATUS_YES];
        $where[]    = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[]    = ['confirm', 'eq', self::CONFIRM_RECEIPT];
        $where[]    = ['is_comment', 'eq', self::NO_COMMENT];
        $where[]    = ['confirm_time', '<=', time() - $setting * 86400];
        $order_info = $this::with('items')->field('order_id, user_id')->where($where)
            ->select()->toArray();

        unset($order_ids);
        $order_ids   = [];
        $order_items = [];
        foreach ($order_info as $vo) {
            $order_ids[] = $vo['order_id'];
            if (count($vo['items']) > 0) {
                foreach ($vo['items'] as &$vv) {
                    $vv['user_id'] = $vo['user_id'];
                }
                $order_items = array_merge($order_items, $vo['items']);
            }
            //订单记录
            $orderLog->addLog($vo['order_id'], $vo['user_id'], $orderLog::LOG_TYPE_AUTO_EVALUATION, '订单后台自动评价', $where);
        }

        //更新订单
        unset($wheres);
        $wheres[]           = ['order_id', 'in', $order_ids];
        $data['is_comment'] = self::ALREADY_COMMENT;
        $data['utime']      = time();
        $this->where($wheres)->update($data);

        //查询评价商品
        unset($goods_comment);
        $goods_comment = [];
        foreach ($order_items as $vo) {
            $goods_comment[] = [
                'score'    => 1,
                'user_id'  => $vo['user_id'],
                'goods_id' => $vo['goods_id'],
                'order_id' => $vo['order_id'],
                'addon'    => $vo['addon'],
                'content'  => '用户' . $setting . '天内未对商品做出评价，已由系统自动评价。',
                'ctime'    => time(),
            ];
        }
        model('common/GoodsComment')->insertAll($goods_comment);

    }

    /**
     * 自动完成订单
     * @param $setting
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function autoComplete($setting)
    {
        $orderLog = new OrderLog();
        unset($where);
        $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $where[] = ['status', 'eq', self::ORDER_STATUS_NORMAL];
        $where[] = ['ctime', '<=', time() - $setting * 86400];

        $order_list = $this->field('order_id, user_id')
            ->where($where)
            ->select();

        if (count($order_list) > 0) {
            unset($order_ids);
            unset($wh);
            $order_ids = [];
            foreach ($order_list as $vv) {
                $order_ids[] = $vv['order_id'];
            }
            $wh[]           = ['order_id', 'in', $order_ids];
            $data['status'] = self::ORDER_STATUS_COMPLETE;
            $data['utime']  = time();
            $this->where($wh)
                ->update($data);

            //订单记录
            $orderLog->addLogs($order_list, $orderLog::LOG_TYPE_COMPLETE, '订单后台自动完成', $where);
        }
    }


    /**
     *
     *  获取当月的资金池
     * @return array
     */
    public function cashPooling()
    {
        $monthTimeStamp = $this->specifiedTimeStamp();
        $where[] = ['utime', 'egt', $monthTimeStamp['start_time']];
        $where[] = ['utime', 'elt', $monthTimeStamp['end_time']];
        $where[] = ['pay_status', 'eq', self::PAY_STATUS_YES];
        $order_amount = $this->where($where)->sum('order_amount');
        $result['data'] = $order_amount / 10;
        $result = [
            'status' => true,
            'msg' => '获取成功',
            'data' => $result
        ];
        return $result;
    }


    /**
     * 获取指定年月的第一天开始和最后一天结束的时间戳
     *
     * @param int $y 年份 $m 月份
     * @return array(本月开始时间，本月结束时间)
     */
    public function specifiedTimeStamp($year = "", $month = ""){
        if ($year == "") $year = date("Y");
        if ($month == "") $month = date("m");
        $month = sprintf("%02d", intval($month));
        //填充字符串长度
        $y = str_pad(intval($year), 4, "0", STR_PAD_RIGHT);
        $month > 12 || $month < 1 ? $m = 1 : $m = $month;
        $firstDay = strtotime($y . $m . "01000000");
        $firstDayStr = date("Y-m-01", $firstDay);
        $lastDay = strtotime(date('Y-m-d 23:59:59', strtotime("$firstDayStr +1 month -1 day")));

        return [
            "start_time" => $firstDay,
            "end_time" => $lastDay
        ];
    }
}