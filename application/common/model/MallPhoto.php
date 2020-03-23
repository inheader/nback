<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/8/13
 * Time: 15:47
 */

namespace app\common\model;

use think\Model;

class MallPhoto extends Model
{
    protected $pk = 'id';

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
    /** ====================== */

    /**
     * 订单状态名称
     * @var array
     */
    public $status_name = [
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
    const CUSTOMER_STATE_NO = 1;  //  未制单
    const CUSTOMER_STATE_YES = 2; //  已制单

    public $customer_state_name = [
        self::CUSTOMER_STATE_NO     => '未制单',
        self::CUSTOMER_STATE_YES     => '已制单',
    ];

    /**
     * 查询拍照单id
     * @param $buyerId
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null
     */
    public function InstanceMallPhotoId($id){

        return $this->where('id',$id)->find();
    }


    
    // 查询制单详情
    public function PhotoOrder($id){
 
        $data = $this->where([ 'id' => $id ])->find();

        if($data) {
            $Buyer = new Buyer();
            $BuyerAddress = new BuyerAddress();
            $MallBuyerCouponList = new AreaMallBuyerCouponList();
            $BuyerInfo = $Buyer->getBuyerInfoForId($data->buyer_id);
            $BuyerAddressInfo = $BuyerAddress->getBuyerAddressForId($data->address_id);
            $BuyerCouponInfo = $MallBuyerCouponList->getMallBuyerCouponForId($data->coupons_id);

            $data['buyerInfo'] = $BuyerInfo;
            $data['buyerAddressInfo'] = $BuyerAddressInfo;
            $data['buyerCouponInfo'] = $BuyerCouponInfo;
            
            return $data;
        }else{
            return $data; 
        }
        
    }


    
}