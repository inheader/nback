<?php
namespace app\common\model;

use think\Db;
/**
 * 订单子表
 * Class OrderGoods
 * @package app\common\model
 * @author keinx
 */
class OrderGoods extends Common
{

    public function getMyGoodsId() { return isset($this->goods_id) ? $this->goods_id : null;}
    public function getMyGoodsName() { return isset($this->goods_name) ? $this->goods_name : null;}
    public function getMyGoodsNum()  { return isset($this->goods_num) ? $this->goods_num : null;}



    /**
     * @param array $info
     * @return int
     * @throws ModelException
     */
    public function addData(array $info){
        // try{
        //     $this->arrValidate($info, [
        //         'mallId'        => 'required',
        //         'siteId'        => 'required',
        //         'orderId'       => 'required',
        //         'spdDetailId'   => 'required',
        //         'spdDetailName' => 'required',
        //         'spdBn' => 'required',
        //         'spcCommonId'   => 'required',
        //         'ospGoodNum'    => 'required',
        //         'ospPrice'      => 'required',
        //         'ospPointPrice' => 'required',
        //         'agentDiscount' => 'required',
        //         'agentPrice' => 'required',
        //         'commissionDiscount' => 'required',
        //         'commissionPrice' => 'required',
        //     ]);
        // }catch(ValidationException $validateException){
        //     throw new ModelException('包裹添加失败111', GlobalErrCode::ERR_PARAM_VALIDATE_ERROR);
        // }


        $data = array();
        $goodsIns = new OrderGoods();
        $goodsIns['mall_id']           = $info['mallId'];
        $goodsIns['site_id']           = $info['siteId'];
        $goodsIns['order_id']          = $info['orderId'];
        $goodsIns['products_id']       = $info['spdDetailId'];
        $goodsIns['goods_name']        = $info['spdDetailName'];
        $goodsIns['goods_id']          = $info['spcCommonId'];
        $goodsIns['goods_spec_value']  = isset($info['spdSpecName']) ? $info['spdSpecName'] : '';
        $goodsIns['goods_bn']          = isset($info['spdBn']) ? $info['spdBn'] : '';
        $goodsIns['goods_num']         = $info['ospGoodNum'];
        $goodsIns['osp_price']         = $info['ospPrice'];//购买价
        $goodsIns['osp_price_sum']     = $info['ospPriceSum'];//ospPriceSum//购买物流价
        $goodsIns['osp_origin_price']  = isset($info['ospDiscountPrice']) ? $info['ospDiscountPrice'] : $info['ospPrice'];//实际价
        $goodsIns['osp_full_price']    = isset($info['ospFullPrice']) ? $info['ospFullPrice'] : $info['ospPrice'];
        $goodsIns['osp_point_price']   = isset($info['ospPointPrice']) ? $info['ospPointPrice'] : 0;
        $goodsIns['agent_discount']   = isset($info['agentDiscount']) ? $info['agentDiscount'] : 0;
        $goodsIns['agent_price']   = isset($info['agentPrice']) ? $info['agentPrice'] : 0;
        $goodsIns['commission_discount']   = isset($info['commissionDiscount']) ? $info['commissionDiscount'] : 0;
        $goodsIns['commission_price']   = isset($info['commissionPrice']) ? $info['commissionPrice'] : 0;
        $goodsIns['erp_code'] = 0;

        $goodsIns['osp_floor_price']   = isset($info['ospFloorPrice']) ? $info['ospFloorPrice'] : 0;
        $goodsIns['osp_transport_price']   = isset($info['ospTransportPrice']) ? $info['ospTransportPrice'] : 0;

        if(isset($info['discountId'])){
            $goodsIns['discount_id']   = $info['discountId'];
        }

        if(isset($info['fullDeliveryId'])){
            $goodsIns['full_delivery_id']   = $info['fullDeliveryId'];
        }

        //$data['osp_point_price']   = $info['ospPointPrice'];
        $goodsIns['goods_image']       = isset($info['spcImageUrl'])?$info['spcImageUrl']:'';
        $goodsIns['created_at'] = date('Y-m-d H:i:s');
        $goodsIns['updated_at'] = date('Y-m-d H:i:s');
        //$data['osp_shape']         = $info['ospShape'];
        // $data['created_at']        = Carbon::now();
        // $data['updated_at']        = Carbon::now();


        return $goodsIns->save();

        // return $goodsIns->getMyOrderId();
    }

    /**
     * 获取订单中的商品列表
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsListForOrderId($order_id)
    {
        //查询使用优惠卷类型
        $orderCouponModel = new AreaMallBuyerCouponOrderLog();
        $couponId = $orderCouponModel::get(['order_id'=>$order_id])->coupon_id;
        $couponListModel = new AreaMallBuyerCouponList();
        $coupon = $couponListModel::get(['coupon_id'=>$couponId]);
        //订单商品列表
        $goodsInsList = $this->where('order_id',$order_id)->select();
        return collect($goodsInsList)->map(function($goodsIns,$coupon){
            $productIns = (new Products())->where('id',$goodsIns['products_id'])->find();
            $productCommon =Db::table('ev_goods')->where('id',$goodsIns['goods_id'])->find();
            $goodsIns['Logistic']  =  $productCommon['transport_fee'];
            $goodsIns['all_point_price'] = $goodsIns['goods_num']*$goodsIns['osp_point_price'];
            $goodsIns['all_full_price'] = $goodsIns['goods_num']*$goodsIns['osp_price'];
//            if($coupon == 1) //满减卷
//            {
            $goodsIns['osp_full_price'] = sprintf('%.2f',$goodsIns['osp_full_price']);
//            }
            if($goodsIns->osp_unloading_price){
                $goodsIns['all_full_plus_price'] = ($productIns['mall_member_price']+$productCommon['transport_fee']-$productCommon['upstairs_fee'])*$goodsIns['goods_num'];
                $goodsIns['all_full_Logistic'] = ($goodsIns['osp_price']+$productCommon['transport_fee']-$productCommon['upstairs_fee'])*$goodsIns['goods_num'];
            }else{
                $goodsIns['all_full_plus_price'] = ($productIns['mall_member_price']+$productCommon['transport_fee'])*$goodsIns['goods_num'];
                $goodsIns['all_full_Logistic'] = ($goodsIns['osp_price']+$productCommon['transport_fee'])*$goodsIns['goods_num'];
            }
            $goodsIns['osp_plus_price'] = $productIns['mall_member_price'];
            $goodsIns['all_point_Logistic'] =($goodsIns['osp_point_price']+$productCommon['transport_fee'])*$goodsIns['goods_num'];

            $goodsIns['sku'] = !empty($productIns) ? $productIns['sn'] : $goodsIns['products_id'];
            return $goodsIns;
        })->all();
    }

    /**
     * @param $ospId
     * @return OrderGoods
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myInstanceByOspId($ospId){
        return $this->where('osp_id', $ospId)->find();
    }

    /**
     * 更改订单商品已发货数量
     * @param $ospId
     * @param $sendNum
     * @return static
     */
    public function changeSendNumByOspId($ospId, $sendNum){
        return $this->update([
            'goods_send_num' => $sendNum,
            'updated_at'     => date('Y-m-d H:i:s')
        ], ['osp_id' => $ospId]);
    }
}