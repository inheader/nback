<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/13
 * Time: 10:15
 */

namespace app\common\model;

use think\Db;

/**
 * 区域赊账流水表模型
 * Class AreaCreditFlow
 * @package app\common\model
 */
class AreaMallCreditFlow extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    /**
     * 赊账用户表
     * @return \think\model\relation\HasMany
     */
    public function credit()
    {
        return $this->hasMany('AreaMallCreditList','buyer_id','buyer_id');
    }


    /**
     * @param array $data
     * @return AreaMallCreditFlow
     */
    public function addData(array $data)
    {
        $balanceLog = new AreaMallCreditFlow();
        $balanceLog['mall_id']  = $data['mallId'];
        $balanceLog['site_id']  = $data['siteId'];
        $balanceLog['buyer_id'] = $data['buyerId'];
        $balanceLog['order_id'] = isset($data['orderId']) ? $data['orderId'] : 0;
        $balanceLog['order_sn'] = $data['orderSn'];
        $balanceLog['order_pay_sn'] = isset($data['orderPaySn']) ? $data['orderPaySn'] : '';
        $balanceLog['type']     = $data['type'];
        $balanceLog['flow_type']     = $data['flowType'];
        $balanceLog['price']         = $data['price'];
        $balanceLog['balance']  = $data['balance'];
        $balanceLog['remark']   = isset($data['remark']) ? $data['remark'] : '';
        $balanceLog['created_at']   = date("Y-m-d H:i:s");
        $balanceLog->save();

        return $balanceLog;
    }


    /**
     * 余额记录
     * @param $isCount
     * @param $buyerId
     * @param int $page
     * @param int $limit
     * @return array|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerCreditLog($isCount,$buyerId,$page=1,$limit=10)
    {
        $list = $this->where('buyer_id',$buyerId);
        if(empty($isCount)){
            $list = $list->order('updated_at','desc')->page($page,$limit)->select();
            return collect($list)->map(function($info){
                return[
                    'type'      => $info['flow_type'] == 1 ? '使用' :'增加',
                    'price'     => $info['price'],
                    'balance'   => $info['balance'],
                    'remarks'   => $info['remark'],
                    'created_at'=> $info['created_at'],
                ];
            })->all();
        }
        return $list->count();

    }
}