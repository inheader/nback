<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/18
 * Time: 23:13
 */

namespace app\common\model;

use think\Validate;

class ActFullDeliveryRule extends Common
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
    public function getActRuleListForActId($actId)
    {
        $list = $this->where([
            'fda_id' => $actId
        ])->select();

        return collect($list)->map(function($info){
            return[
                'fdarFullType'          => $info['fdar_full_type'],
                'fullMoney'             => intval($info['fdar_full_num']),
                'actType'               => $info['fdar_discount_type'],
                'reduceMoney'           => $info['fdar_discount_type'] == 1 ? intval($info['fdar_discount_num']) : 0,
                'reduceDiscount'        => $info['fdar_discount_type'] == 2 ? intval($info['fdar_discount_num']) : 0,
                'isFreeShipping'        => $info['is_shipping_free'],
            ];
        })->all();
    }

    /**
     * 删除活动规则
     * @param $actId
     * @return int
     */
    public function deleteActRuleListForActId($actId)
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