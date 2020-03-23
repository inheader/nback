<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/20
 * Time: 22:57
 */
namespace app\common\model;

use think\Db;

/**
 * 买家余额表
 * Class Buyer
 * @package app\common\model
 */
class AreaMallBuyerCouponList extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    const COUPON_CAN_YES = 1;  //可用
    const COUPON_CAN_NO = 0;  //停用

    const COUPON_ACT_TYPE = 1;//满减/立减
    const COUPON_DIS_TYPE = 2;//折扣
    const COUPON_MONEY_TYPE = 3;//现金
    const COUPON_ALONE_TYPE = 4;//单品
    const COUPON_EXPRESS_TYPE = 5;//特快
    const COUPON_SHIPPING_TYPE = 6;//物流

    const COUPON_CLASS_TYPE_MALL = 1;//平台
    const COUPON_CLASS_TYPE_SITE = 2;//店铺

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';

    const COUPON_STATE_USED             = 3; //已使用
    const COUPON_STATE_UNUSED           = 1;//未使用
    const COUPON_STATE_DEACTIVATED      = 0;//已停用
    /**
     * @param $mallId
     * @param $status
     * @return array
     */
    public static function statistics($mallId,$status)
    {
        $num = 7;
        $day = date('Y-m-d', strtotime('-'.$num.' day'));
        $sql = 'SELECT DATE_FORMAT(created_at,"%Y-%m-%d") as day, count(*) as nums FROM '.
            'ev_area_mall_buyer_coupon_list WHERE created_at >= "' . $day . '" AND coupon_state=' . $status . ' AND mall_id='.$mallId.
            ' GROUP BY DATE_FORMAT(created_at,"%Y-%m-%d")';
        $res = Db::query($sql);
        $data = get_lately_days($num, $res);
//        if($status == AreaMallBuyerCouponList::COUPON_STATE_USED){
            return ['day' => $data['day'], 'data' => !empty($data['data']) ? $data['data'] : []];
//        }else{
//            return !empty($data['data']) ? $data['data'] : [];
//        }
    }

    public function getMyMallId() { return isset($this->mall_id) ? $this->mall_id : 0; }
    public function getMySiteId()     { return isset($this->site_id) ? $this->site_id : 0; }
    public function getMyCpId()     { return isset($this->cp_id) ? $this->cp_id : 0; }
    public function getMyBuyerId()     { return isset($this->buyer_id) ? $this->buyer_id : 0; }
    public function getMyCouponState() { return isset($this->coupon_state) ? $this->coupon_state : 0; }
    public function getMyEndTime() { return isset($this->end_time) ? $this->end_time : 0; }
    public function getMyCouponType() { return isset($this->coupon_type) ? $this->coupon_type : 0; }
    public function getMyCouponPriceDiscount() { return isset($this->coupon_price_discount) ? $this->coupon_price_discount : 0; }
    public function getMyCouponMoneyDiscount() { return isset($this->coupon_money_discount) ? $this->coupon_money_discount : 0; }
    public function getMyCouponMoneyLimit() { return isset($this->coupon_money_limit) ? $this->coupon_money_limit : 0; }
    public function getMyCouponSn() { return isset($this->coupon_sn) ? $this->coupon_sn : ''; }
    public function getMyCouponTitle() { return isset($this->coupon_title) ? $this->coupon_title : ''; }
    public function getMyCouponClassType() { return isset($this->coupon_class_type) ? $this->coupon_class_type : 0; }



    public function InsCouponFirst($couponId)
    {
        return $this->where('coupon_id',$couponId)->where('coupon_state','1')->find();
    }

    /**
     * 获取买家余额
     * @param $buyerId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMallBuyerCouponForId($id)
    {
        return $this->where('coupon_id',$id)->find();
    }



    //查看优惠劵
    public function getCouponList(array $data){
        $query = $this;

        if(isset($data['mallId'])){
            $query = $query->where('mall_id', $data['mallId']);
        }
        if(isset($data['siteId'])){
            $query = $query->where('site_id', $data['siteId']);
        }
        if(isset($data['buyerId'])){
            $query = $query->where('buyer_id', $data['buyerId']);
        }

        if(isset($data['state'])){
            if($data['state'] == 1){ //可用
                $query = $query->where('coupon_state', 1);
                $query = $query->where('end_time', '>', time());
            }elseif($data['state'] == 2){ //已使用
                $query = $query->whereIn('coupon_state', [2, 3]);
            }elseif($data['state'] == 3){  //已过期
                $query = $query->where('end_time', '<', time());
            }
        }

        $couponList  = $query->select();

        return $couponList;
    }


}