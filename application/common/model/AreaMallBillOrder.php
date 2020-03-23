<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/12/17
 * Time: 13:42
 */

namespace app\common\model;

use think\Db;

/**
 * 开票订单模型
 * Class AreaMallBillOrder
 * @package app\common\model
 */
class AreaMallBillOrder extends Common
{

    const BILL_ORDER_STATUS_OK = 1;
    const BILL_ORDER_STATUS_NO = 0;

    /**
     * 近7天完成OR未完成开票订单
     * @param $status
     * @param $mallId
     * @return array
     */
    public static function mall_statistics($mallId,$status)
    {
        $num = 7;
        $day = date('Y-m-d', strtotime('-'.$num.' day'));
        $sql = 'SELECT DATE_FORMAT(created_at,"%Y-%m-%d") as day, count(*) as nums FROM '.
            'ev_area_mall_bill_order WHERE created_at >= "' . $day . '" AND status=' . $status . ' AND mall_id='.$mallId.
            ' GROUP BY DATE_FORMAT(created_at,"%Y-%m-%d")';
        $res = Db::query($sql);
        $data = get_lately_days($num, $res);
        if($status == AreaMallBillOrder::BILL_ORDER_STATUS_OK){
            return ['day' => $data['day'], 'data' => !empty($data['data']) ? $data['data'] : []];
        }else{
            return !empty($data['data']) ? $data['data'] : [];
        }
    }

    /**
     * 近7天完成OR未完成开票订单
     * @param $status
     * @param $siteId
     * @return array
     */
    public static function site_statistics($siteId,$status)
    {
        $num = 7;
        $day = date('Y-m-d', strtotime('-'.$num.' day'));
        $sql = 'SELECT DATE_FORMAT(created_at,"%Y-%m-%d") as day, count(*) as nums FROM '.
            'ev_area_mall_bill_order WHERE created_at >= "' . $day . '" AND status=' . $status . ' AND site_id='.$siteId.
            ' GROUP BY DATE_FORMAT(created_at,"%Y-%m-%d")';
        $res = Db::query($sql);
        $data = get_lately_days($num, $res);
        if($status == AreaMallBillOrder::BILL_ORDER_STATUS_OK){
            return ['day' => $data['day'], 'data' => !empty($data['data']) ? $data['data'] : []];
        }else{
            return !empty($data['data']) ? $data['data'] : [];
        }
    }



}