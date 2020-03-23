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
 * 优惠券领取记录
 * Class BuyerCouponRule
 * @package app\common\model
 */
class BuyerCouponSn extends Common
{

    /**
     * 优惠券领取记录
     * @param $isCount
     * @param $cpId
     * @param int $page
     * @param int $limit
     * @return array|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCouponLog($isCount,$cpId,$page=1,$limit=10)
    {
        $list = $this->where('cp_id',$cpId);
        if(empty($isCount)){
            $list = $list->order('updated_at','desc')->page($page,$limit)->select();
            $buyerIds = collect($list)->pluck('buyer_id')->all();

            $buyerList = (new Buyer())->whereIn('buyer_id',$buyerIds)->select();
            return collect($list)->map(function($info) use($buyerList){
                $buyerIns = collect($buyerList)->where('buyer_id',$info['buyer_id'])->first();
                $buyerName = !empty($buyerIns) ? $buyerIns['buyer_name'] : '游客';
                return[
                    'buyer_id'          => $info['buyer_id'],
                    'buyer_name'        => $buyerName,
                    'coupon_state'      => $info['coupon_state']==2 ? '已使用' : '未使用',
                    'created_at'        => $info['created_at'],
                ];
            })->all();
        }
        return $list->count();

    }


}