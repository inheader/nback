<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/13
 * Time: 10:13
 */

namespace app\common\model;

use think\Db;
/**
 * 区域赊账账户表模型
 * @package app\common\model
 */
class AreaMallCredit extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    /**
     * 流水表
     * @return \think\model\relation\HasMany
     */
    public function flow()
    {
        return $this->hasMany('AreaCreditFlow','mall_id');
    }



    /**
     * @param $buyerId
     * @param $mallId
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null
     */
    public function InstanceByCreditId($buyerId,$mallId){
        return $this->where('mall_id', $mallId)->where('buyer_id',$buyerId)->find();
    }


    /**
     * @param array $buyerIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerCreditListForBuyerIds($buyerIds = []){
        $insList =  $this->whereIn('buyer_id',$buyerIds)->select();
        return collect($insList)->map(function($ins){
            return[
                'buyerId' =>$ins['buyer_id'],
                'balance' =>$ins['balance'],
            ];
        })->all();
    }



    /**
     * 获取买家赊账余额
     * @param $buyerId
     * @return int
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
     * 保存用户余额
     * @param $areaId
     * @param $buyer_id
     * @param $addBalance
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editBuyerCredit($areaId,$buyer_id,$addBalance,$remark){
        $return = [
            'status' => false,
            'msg' => ''
        ];

        //获取用户现在的余额
        $oldBalance = $this->getBuyerCreditForBuyerId($buyer_id);

        $newBalance = $oldBalance+ $addBalance;
        //余额判断
        if($newBalance < 0)
        {
            $return['msg'] = '余额不足';
            return $return;
        }

        $this->checkAndUpdateBuyerCredit($buyer_id);

        $return = false;
        Db::startTrans();
        try{
            //插入记录
            $data = [
                'mall_id' => $areaId,
                'buyer_id' => $buyer_id,
                'type' => 1,
                'flow_type' => 2,
                'price' => $addBalance,
                'balance' => $newBalance,
                'remark' => $remark,
                'created_at' => date("Y-m-d H:i:s",time()),
                'updated_at' => date("Y-m-d H:i:s",time()),
            ];
            (new AreaMallCreditFlow())->insert($data);

            // 插入主表
            $where[] = ['buyer_id', 'eq', $buyer_id];
            $this->where($where)
                ->update([
                    'balance'=>$newBalance
                ]);

            Db::commit();
            $return['status'] = true;
            $return['msg'] = '赊账额度更改成功';
        }catch(\Exception $e){
            Db::rollback();
            $return['msg'] = '赊账额度更改失败';
        }
        return $return;
    }



    /**
     * @param $buyer_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkAndUpdateBuyerCredit($buyer_id)
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


    /**
     * 平台赊账余额更新
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