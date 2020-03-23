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
 * 买家积分表
 * Class Buyer
 * @package app\common\model
 */
class BuyerPoints extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    /**
     * 获取买家积分
     * @param $buyerId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerPointsForBuyerId($buyerId)
    {
        $ins =  $this->where([
            'buyer_id' => $buyerId
        ])->find();
        return isset($ins['balance']) ? $ins['balance'] : 0;
    }

    /**
     * @param array $buyerIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerPointsListForBuyerId($buyerIds = [])
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
     * 修改积分余额
     * @param $areaId
     * @param $buyer_id
     * @param $addPoint
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editBuyerPoint($areaId,$buyer_id,$addPoint,$remark){
        $return = [
            'status' => false,
            'msg' => ''
        ];

        //获取用户现在的余额
        $oldPoint = $this->getBuyerPointsForBuyerId($buyer_id);

        $newPoint = $oldPoint+ $addPoint;
        //余额判断
        if($newPoint < 0)
        {
            $return['msg'] = '积分不足';
            return $return;
        }

        $this->checkAndUpdateBuyerBalance($buyer_id);

//        $return = false;
        Db::startTrans();
        try{
            //插入记录
            $data = [
                'mall_id' => $areaId,
                'buyer_id' => $buyer_id,
                'type' => 1,
                'num' => $addPoint,
                'balance' => $newPoint,
                'remarks' => $remark,
                'created_at' => date("Y-m-d H:i:s",time()),
                'updated_at' => date("Y-m-d H:i:s",time()),
            ];
            (new BuyerPointsLog())->insert($data);
            //插入主表
            $this->where('buyer_id',$buyer_id)
                ->update([
                    'balance'=>$newPoint
                ]);
            Db::commit();
            $return['status'] = true;
            $return['msg'] = '积分更改成功';
        }catch(\Exception $e){
            Db::rollback();
            $return['msg'] = '积分更改失败';
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