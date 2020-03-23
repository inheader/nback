<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/18
 * Time: 23:13
 */

namespace app\common\model;

use think\Validate;

class ActFullDeliveryGoods extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    /**
     * @param $actId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsListForAct($actId)
    {
        $list = $this->where([
            'fda_id' => $actId
        ])->select();

        return collect($list)->map(function($info){
            return $info['goods_id'];
        })->all();
    }

    /**
     * @param $userWhere
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsListForSiteId($userWhere)
    {
        $actIds = (new ActFullDelivery())->getActIdListForAuth($userWhere);

        $list = $this->whereIn('fda_id',$actIds)->select();

        return collect($list)->map(function($info){
            return $info['goods_id'];
        })->all();
    }


    /**
     * 删除活动商品
     * @param $actId
     * @param $goods
     * @return int
     */
    public function deleteActGoodsForActId($actId,$goods )
    {
        if(!is_array($goods)){
            $goods = [$goods];
        }
        return $this->where([
            'fda_id' => $actId
        ])->whereIn('goods_id',$goods)->delete();
    }

    /**
     * 添加活动商品
     * @param $actId
     * @param $goodsId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addActGoodsForActId($actId,$goodsId )
    {
        //更新账号信息
        $actGoodsIns = $this->where('fda_id',$actId)->where('goods_id',$goodsId)->find();
        if(empty($actGoodsIns)){
            $saveInfo['fda_id'] = $actId;
            $saveInfo['goods_id'] = $goodsId;
            $this->insert($saveInfo);
        }
        return true;
    }

    /**
     * 取消全部商品
     * @param $actId
     * @return int
     */
    public function deleteActAllGoodsForActId($actId)
    {
        return $this->where([
            'fda_id' => $actId
        ])->delete();
    }


    /**
     * @param $actId
     * @param $info
     * @return bool
     */
    public function addActRuleInfo($actId,$info)
    {
        $update = [
            'fda_id'    => $actId,
            'fdar_full_type'   => 1,//目前都是满减
            'fdar_full_num'   => $info['fullMoney'],
            'fdar_discount_type' => $info['actType'],
            'fdar_discount_num'  => $info['actType']==1 ? $info['reduceMoney'] :$info['reduceDiscount'],
            'is_shipping_free'  => isset($info['isFreeShipping']) ? $info['isFreeShipping'] : 0,
        ];

        $this->insert($update);
        return true;
    }


}