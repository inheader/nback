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
 * 买家余额表
 * Class Buyer
 * @package app\common\model
 */
class BuyerBalance extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    /**
     * 获取买家余额
     * @param $buyerId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerBalanceForBuyerId($buyerId)
    {
        $ins =  $this->where([
            'buyer_id' => $buyerId
        ])->find();
        return isset($ins['balance']) ? $ins['balance'] : 0;
    }


    /**
     * 保存用户余额
     * @param $areaId
     * @param $buyer_id
     * @param $addBalance
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editBuyerBalance($areaId,$buyer_id,$addBalance,$remark){
        $return = [
            'status' => false,
            'msg' => ''
        ];

        //获取用户现在的余额
        $oldBalance = $this->getBuyerBalanceForBuyerId($buyer_id);

        $newBalance = $oldBalance+ $addBalance;
        //余额判断
        if($newBalance < 0)
        {
            $return['msg'] = '余额不足';
            return $return;
        }

        $this->checkAndUpdateBuyerBalance($buyer_id);

        $return = false;
        Db::startTrans();
        try{
            //插入记录
            $data = [
                'mall_id' => $areaId,
                'buyer_id' => $buyer_id,
                'type' => 1,
                'num' => $addBalance,
                'balance' => $newBalance,
                'remark' => $remark,
                'created_at' => date("Y-m-d H:i:s",time()),
                'updated_at' => date("Y-m-d H:i:s",time()),
            ];
            (new BuyerBalanceLog())->insert($data);

            //插入主表
            $where[] = ['buyer_id', 'eq', $buyer_id];
            $this->where($where)
                ->update([
                    'balance'=>$newBalance
                ]);

            Db::commit();
            $return['status'] = true;
            $return['msg'] = '余额更改成功';
        }catch(\Exception $e){
            Db::rollback();
            $return['msg'] = '余额更改失败';
        }
        return $return;
    }

    /**
     * @param array $buyerIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerBalanceListForBuyerIds($buyerIds = [])
    {
        $insList =  $this->whereIn('buyer_id',$buyerIds)->select();
        return collect($insList)->map(function($ins){
            return[
                'buyerId' =>$ins['buyer_id'],
                'balance' =>$ins['balance'],
            ];
        })->all();
    }


    /**
     * 更新余额
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

    /**
     * @param $buyer_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkAndUpdateBuyerBalance($buyer_id)
    {
        //查看用户是否有余额字段，没有的话需要新增
        $buyerBalanceIns = $this->where('buyer_id',$buyer_id)->find();
        if(empty($buyerBalanceIns)){
            //没有需要新增这个用户的字段
            $saveInfo['buyer_id'] = $buyer_id;
            $this->insert($saveInfo);
        }

       return true;
    }


}