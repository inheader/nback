<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/9/18
 * Time: 15:33
 */

namespace app\common\model;


class OrderReturnTableGoods extends Common
{


    public function countNum($orderId)
    {
        OrderReturnTableGoods::where('order_id',$orderId)->select();
    }

    /**
     * 数量判断
     * @param $ospId
     * @param $num
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_goods_num($ospId,$num)
    {

        //查询是否有之前退货的商品
        $orderTableGoods = (new OrderReturnTableGoods())->where('order_osp_id',$ospId)->select()->toArray();
        if(!empty($orderTableGoods) && $orderTableGoods != null){
            $ospNum = 0;
            foreach ($orderTableGoods as $k=>$orderTableGood) {
                //计算已经退货的商品数量
                $ospNum += $orderTableGood['goods_return_num'];
            }
            //原有的商品数量
            $order_goods = OrderGoods::get(['osp_id'=>$ospId]);
            //得出现有可退的商品数量
            $returnNum = ($order_goods['goods_send_num']-$ospNum);
            //数量
            if($num > $returnNum )
            {
                return false;
            }
        }else{
            $order_goods = OrderGoods::get(['osp_id'=>$ospId]);
            //数量对比
            if($num > $order_goods->goods_send_num)
            {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $orderId [订单ID]
     * @param $sn [绑定标识]
     * @return []
     */
    public static function sum_goods_price($orderId,$sn)
    {
        $price = 0;
        $priceSum = 0;
        $goods = OrderReturnTableGoods::where(['order_id'=>$orderId,'bind_bn'=>$sn])->select()->toArray();
        foreach ($goods as $k=>$v) {
            $price += ($v['osp_price'] * $v['goods_return_num']);
            $priceSum += ($v['osp_price_sum'] * $v['goods_return_num']);
        }

        return ['price'=>sprintf('%.2f',$price),'priceSum'=>sprintf('%.2f',$priceSum)];
    }

}