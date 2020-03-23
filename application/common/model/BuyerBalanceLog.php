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
 * 买家余额记录表
 * Class Buyer
 * @package app\common\model
 */
class BuyerBalanceLog extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


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
    public function getBuyerBalanceLog($isCount,$buyerId,$page=1,$limit=10)
    {
        $list = $this->where('buyer_id',$buyerId);
        if(empty($isCount)){
            $list = $list->order('updated_at','desc')->page($page,$limit)->select();
            return collect($list)->map(function($info){
                return[
                    'type'      => $info['type']==1 ? '获得' :'消费',
                    'num'       => $info['num'],
                    'give_num'  => $info['give_num'],//充值赠送金额
                    'balance'   => $info['balance'],
                    'remarks'   => $info['remark'],
                    'created_at'=> $info['created_at'],
                ];
            })->all();
        }
        return $list->count();

    }

    /**
     * @param array $data
     * @return BuyerBalanceLog
     */
    public function addData(array $data)
    {

        $balanceLog = new BuyerBalanceLog();
        $balanceLog['mall_id']  = $data['mallId'];
        $balanceLog['buyer_id'] = $data['buyerId'];
        $balanceLog['order_id'] = $data['orderId'];
        $balanceLog['order_sn'] = $data['orderSn'];
        $balanceLog['type']     = $data['type'];
        $balanceLog['num']      = $data['num'];
        $balanceLog['balance']  = $data['balance'];
        $balanceLog['remark']   = isset($data['remark']) ? $data['remark'] : '';
        $balanceLog['created_at']   = date("Y-m-d H:i:s");
        $balanceLog->save();

        return $balanceLog;
    }


}