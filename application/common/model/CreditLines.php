<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/20
 * Time: 14:03
 */

namespace app\common\model;

/**
 * 店铺用户赊账余额表
 * Class CreditLines
 * @package app\common\model
 */
class CreditLines extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


        /**
     * @param $buyerId
     * @param $siteId
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null
     */
    public function InstanceByCreditId($buyerId,$siteId=''){
        $siteId = isset($siteId) ? $siteId : 0;
        $where = [];
        if($siteId)
        {
            $where['site_id'] = $siteId;
        }
        return $this->where($where)->where('buyer_id',$buyerId)->find();
    }

    /**
     * @param $buyerId
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerCreditForBuyerId($buyerId)
    {
        $ins =  $this->where([
            'buyer_id' => $buyerId
        ])->find();
        return isset($ins['balance']) ? $ins['balance'] : 0;
    }


    /**
     * @param $buyerId
     * @param $changeAmount
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeMyBalance($buyerId,$changeAmount){

        $buyerBalanceIns = $this->where('buyer_id',$buyerId)->find();
        $buyerBalanceIns->balance += $changeAmount;
        $buyerBalanceIns->save();

        return $buyerBalanceIns;
    }

}