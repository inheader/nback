<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/9/21
 * Time: 8:55
 */

namespace app\common\model;


class OrderReturnTable extends Common
{

    /**
     * @param null $code
     * @return null|string
     */
    public static function pay_code_type($code=null)
    {
        switch ($code)
        {
            case 'WEIXIN_PAY':
                $code = '微信';
            break;
            case 'POINT_PAY':
                $code = '积分';
                break;
            case 'VCOUNT_PAY':
                $code = '余额';
                break;
            case 'CREDIT_PAY':
                $code = '店铺赊账';
                break;
            case 'AREA_CREDIT_PAY':
                $code = '平台赊账';
            break;
        default:
            $code = '';
        }
        return $code;
    }

    /**
     * 检测退款订单是否存在
     * @param $orderId
     * @return bool
     */
    public function checkOrder($orderId)
    {
        $res = OrderReturnTable::get(['order_id'=>$orderId]);
        if($res)
            return true;

        return false;
    }

    /**
     * 获取用户
     * @param $buyerId [用户ID]
     * @return mixed
     */
    public static function get_buyer_name($buyerId)
    {
        return Buyer::get(['buyer_id'=>$buyerId]);
    }

    /**
     * 获取店铺
     * @param $siteId [店铺ID]
     * @return mixed
     */
    public static function get_site_name($siteId)
    {
        return AreaMallSite::get(['site_id'=>$siteId]);
    }

    /**
     * @param $orderId
     * @param $sn
     * @param int $price
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateRefundPrice($orderId,$sn,$price=0)
    {
        return OrderReturnTable::where(['order_id'=>$orderId,'bind_sn'=>$sn])->update(['refund_price'=>$price]);
    }

}