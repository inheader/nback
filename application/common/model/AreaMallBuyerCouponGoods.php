<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/7/17
 * Time: 16:03
 */

namespace app\common\model;


class AreaMallBuyerCouponGoods extends Common
{

    /**
     * @param $cpId
     * @return \Illuminate\Support\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsIdsForQuick($cpId)
    {
        $list = $this->where('cp_id',$cpId)->select();
        return collect($list)->map(function($info){
            return $info['spc_id'];
        });
    }

    public function addQuickGoodsForQuickId($quickId, $goodsId)
    {
        //更新账号信息
        $actGoodsIns = $this->where('cp_id',$quickId)->where('spc_id',$goodsId)->find();
        if(empty($actGoodsIns)){
            $saveInfo['cp_id']    = $quickId;
            $saveInfo['spc_id']     = $goodsId;
            $this->insert($saveInfo);
        }
        return true;
    }



    public function InMyGoodsId($cpId,$spcId)
    {
        return $this->where(['cp_id'=>$cpId,'spc_id'=>$spcId])->find();
    }

}