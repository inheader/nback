<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/20
 * Time: 14:00
 */

namespace app\common\model;

/**
 * 店铺用户赊账流水表
 * Class CreditFlow
 * @package app\common\model
 */
class CreditFlow extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    /**
     * 关联处理
     */
    public function credit()
    {
        return $this->hasMany('Credit','buyer_id','buyer_id');
    }

    /**
     * @param array $data
     * @return CreditFlow
     */
    public function addData(array $data)
    {
        $balanceLog = new CreditFlow();
        $balanceLog['mall_id']  = $data['mallId'];
        $balanceLog['site_id']  = $data['siteId'];
        $balanceLog['buyer_id'] = $data['buyerId'];
        $balanceLog['order_id'] = $data['orderId'];
        $balanceLog['order_sn'] = $data['orderSn'];
        $balanceLog['type']     = $data['type'];
        $balanceLog['flow_type']     = $data['flowType'];
        $balanceLog['num']           = $data['price'];
        $balanceLog['balance']       = $data['balance'];
        $balanceLog['remarks']       = isset($data['remark']) ? $data['remark'] : '';
        $balanceLog['created_at']    = date("Y-m-d H:i:s");
        $balanceLog->save();

        return $balanceLog;
    }


}