<?php
namespace app\api\controller;
use app\common\controller\Api;
use app\common\model\BillAftersales;
use app\common\model\BillPayments;
use app\common\model\BillDelivery;
use app\common\model\BillReship;
use app\common\model\Order as orderModel;
use Request;

/**
 * 订单模块
 * Class Order
 * @package app\api\controller
 * @author keinx
 */
class Order extends Api
{
    /**
     * 取消订单接口
     * @param array order_ids
     * @return array
     */
    public function cancel()
    {
        $order_ids = input('order_ids');
        $user_id = $this->userId;
        $result = model('common/Order')->cancel($order_ids, $user_id);
        if($result !== false)
        {
            $return_data = array(
                'status' => true,
                'msg' => '取消订单成功',
                'data' => $order_ids
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '取消订单失败',
                'data' => $order_ids
            );
        }
        return $return_data;
    }

    /**
     * 删除订单接口
     * @param array order_ids
     * @return array
     */
    public function del()
    {
        $order_ids = input('order_ids');
        $user_id = $this->userId;
        $result = model('common/Order')->del($order_ids, $user_id);
        if($result)
        {
            $return_data = array(
                'status' => true,
                'msg' => '删除成功',
                'data' => $order_ids
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '删除失败',
                'data' => $order_ids
            );
        }
        return $return_data;
    }

    /**
     * 获取订单详情
     * @return array
     */
    public function details()
    {
        $order_id = input('order_id');
        $user_id = $this->userId;
        $result = model('common/Order')->getOrderInfoByOrderID($order_id, $user_id);
        if($result)
        {
            $return_data = array(
                'status' => true,
                'msg' => '获取成功',
                'data' => $result
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '获取失败',
                'data' => $result
            );
        }
        return $return_data;
    }

    /**
     * 确认收货
     * @param int order_id
     * @return array
     */
    public function confirm()
    {
        $order_id = input('order_id');
        $user_id = $this->userId;
        $result = model('common/Order')->confirm($order_id, $user_id);
        if($result)
        {
            $return_data = array(
                'status' => true,
                'msg' => '确认收货成功',
                'data' => $order_id
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '确认收货失败',
                'data' => $order_id
            );
        }
        return $return_data;
    }

    /**
     * 获取订单列表
     * @return array
     */
    public function getList()
    {
        $input = array(
            'order_id' => input('order_id'),
            'pay_status' => input('pay_status'),
            'ship_status' => input('ship_status'),
            'start_date' => input('start_date'),
            'end_date' => input('end_date'),
            'source' => input('source'),
            'page' => input('page'),
            'limit' => input('limit'),
            'user_id' => $this->userId
        );
        $data = model('common/Order')->getListFromApi($input);

        if(count($data['data']) > 0)
        {
            $return_data = array(
                'status' => true,
                'msg' => '获取成功',
                'data' => array(
                    'list' => $data['data'],
                    'count' => $data['count'],
                )
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '没有符合的订单',
                'data' => array(
                    'list' => $data['data'],
                    'count' => $data['count'],
                )
            );
        }
        return $return_data;
    }

    /**
     * 创建订单
     * @return array|mixed
     */
    public function create()
    {
        if(!input("?param.uship_id")){
            return error_code(13001);
        }else{
            $uship_id = input('param.uship_id');
        }
        $memo = input('param.memo',"");
        $cart_ids = input('param.cart_ids',"");
        $area_id = input('param.area_id', false);
        $point = input('point', 0);
        $coupon_code = input('coupon_code', '');
        $formId = input('formId', false);
        return model('common/Order')->toAdd($this->userId, $cart_ids, $uship_id, $memo, $area_id, $point, $coupon_code, $formId);
    }

    /**
     * 获取配送方式
     * @return array
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-01 15:43
     */
    public function getShip()
    {
        $area_id     = input('area_id',0);
        $return_data = [
            'status' => false,
            'data'   => '',
            'msg'    => '暂未设置配送方式',
        ];
        $ship        = model('common/Ship')->getShip($area_id);
        if($ship) {
            $return_data['status'] = true;
            $return_data['data']   = $ship;
        }
        return $return_data;
    }

    /**
     * 获取订单列表微信小程序
     * @return array
     */
    public function getOrderList()
    {
        $input = array(
            'status' => input('status'),
            'page' => input('page'),
            'limit' => input('limit'),
            'user_id' => $this->userId
        );
        $data = model('common/Order')->getListFromWxApi($input);

        if(count($data['data']) > 0)
        {
            $return_data = array(
                'status' => true,
                'msg' => '获取成功',
                'data' => array(
                    'list' => $data['data'],
                    'count' => $data['count'],
                    'page' => $input['page'],
                    'limit' => $input['limit'],
                    'status' => $input['status']
                )
            );
        }
        else
        {
            $return_data = array(
                'status' => false,
                'msg' => '没有符合的订单',
                'data' => array(
                    'list' => $data['data'],
                    'count' => $data['count'],
                    'page' => $input['page'],
                    'limit' => $input['limit'],
                    'status' => $input['status']
                )
            );
        }
        return $return_data;
    }

    /**
     * 获取订单不同状态的数量
     */
    public function getOrderStatusNum()
    {
        $input = array(
            'user_id' => $this->userId,
            'ids' => input('ids', '1,2,3,4')
        );
        $data = model('common/Order')->getOrderStatusNum($input);

        if($data)
        {
            $return_data = [
                'status' => true,
                'msg' => '获取成功',
                'data' => $data
            ];
        }
        else
        {
            $return_data = [
                'status' => false,
                'msg' => '没有符合的数据',
                'data' => $data
            ];
        }
        return $return_data;
    }

    /**
     * 售后单列表
     */
    public function aftersalesList()
    {
        $data = [
            'page' => input('page/d',1),
            'limit' => input('limit/d',config('jshop.page_limit')),
            'user_id' => $this->userId,
        ];
        $asModel = new BillAftersales();
        return $asModel->getListApi($data);

    }

    /**
     * 售后单详情
     */
    public function aftersalesInfo()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];

        if(!input("?param.aftersales_id")){
            return error_code(13222);
        }
        $asModel = new BillAftersales();
        $info = $asModel->getInfo(input('param.aftersales_id'),$this->userId);
        if(!$info['status']){
            return $info;
        }

//        $reship = [
//            'reship_name' => getShopSetting($this->sellerId,'reship_name'),
//            'reship_mobile' => getShopSetting($this->sellerId,'reship_mobile'),
//            'reship_area' => get_area(getShopSetting($this->sellerId,'reship_area_id')),
//            'reship_address' => getShopSetting($this->sellerId,'reship_address'),
//        ];
        $result['data']['info'] = $info['data'];
//        $result['data']['reship'] = $reship;
        $result['status'] = true;
        return $result;



    }

    /**
     * 查看订单售后状态
     */
    public function aftersalesStatus()
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => ''
        ];

        if(!input("?param.order_id")){
            return error_code(13100);
        }
        $asModel = new BillAftersales();
        $orderInfo = $asModel->orderAftersalesSatatus(input('param.order_id'),$this->userId);
        if($orderInfo){
            $result['status'] = true;
            $result['data'] = $orderInfo;
        }
        return $result;
    }

    /**
     * 添加售后单
     * @return array|\think\Config
     */
    public function addAftersales()
    {

        if(!input("?param.order_id")){
            return error_code(13100);
        }
        if(!input("?param.type")){
            return error_code(10051);
        }

//        $items = [
//            4=>1,
//            3=>3
//        ];
        $items = [];
        $post = input('param.');
        if(isset($post['items'])){
            foreach($post['items'] as $v){
                $items[$v['id']] = $v['nums'];
            }
        }
        //图片
        $images = [];
        if(isset($post['images'])){
            $images = $post['images'];
        }


        $refund = input('param.refund/f',0);        //退款金额，如果type是退款，这个值无所谓，

        //formId
        $formId = \think\facade\Request::param('formId');

        $billAftersalesModel = new BillAftersales();
        return  $billAftersalesModel->toAdd($this->userId,input('param.order_id'),input('param.type'),$items,$images,input('param.reason',''),$refund, $formId);
    }

    /**
     * 退货单，用户发送退货包裹
     * @return bool
     */
    public function sendReship()
    {
        if(!input("?param.reship_id")){
            return error_code(13212);
        }

        if(!input("?param.logi_code")){
            return error_code(13213);
        }

        if(!input("?param.logi_no")){
            return error_code(13214);
        }

        $billReshipModel = new BillReship();
        return  $billReshipModel->sendReship($this->sellerId,$this->userId,input('param.reship_id'),input('param.logi_code'),input('param.logi_no'));
    }

    /**
     * 是否可以评价
     * @return mixed
     */
    public function isComment()
    {
        $order_id = input('order_id');
        $user_id = $this->userId;
        $seller_id = $this->sellerId;
        $res = model('common/Order')->isOrderComment($order_id, $seller_id, $user_id);
        return $res;
    }

    /**
     * 获取订单的物流信息
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logistics()
    {
        if(!input("?param.order_id")){
            return error_code(13100);
        }
        $billDeliveryModel = new BillDelivery();
        return $billDeliveryModel->getLogisticsInformation(input('param.order_id'));
    }


    public function getCashPooling()
    {
        $orderModel = new orderModel();
        return $orderModel->cashPooling();
    }
}