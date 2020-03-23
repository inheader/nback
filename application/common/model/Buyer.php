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
 * 买家表
 * Class Buyer
 * @package app\common\model
 */
class Buyer extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    public function getMyIsPlus() { return isset($this->is_plus) ? $this->is_plus : ''; }

    /**
     * @param $phone
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerInfoForTel($phone)
    {
        return $this->where(['buyer_tel' => $phone])->find();
    }

    /**
     * @param $buyerId
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerInfoForId($buyerId)
    {
        return $this->where([
            'buyer_id' => $buyerId
        ])->find();
    }


    /**
     * 按天统计商户下面的数据
     * @param $day
     * @param $state : 3新增用户，4活跃用户
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statistics($day,$state)
    {
        if($state == 3){
            $field = '\'3\',DATE_FORMAT(created_at,"%Y-%m-%d") as day, count(*) as nums';
            $res = $this->field($field)->where("TIMESTAMPDIFF(DAY,created_at,now()) <7")->group('DATE_FORMAT(created_at,"%Y-%m-%d")')->select();
        }else{
            $field = '\'4\',DATE_FORMAT(updated_at,"%Y-%m-%d") as day, count(*) as nums';
            $res = $this->field($field)->where($where)->where("TIMESTAMPDIFF(DAY,updated_at,now()) <7")->group('DATE_FORMAT(updated_at,"%Y-%m-%d")')->select();
        }

        $data = get_lately_days($day, $res);
        return ['day' => $data['day'], 'data' => $data['data']];


    }

    /**
     * 获取条件行数
     * @param $paramInfo
     * @return mixed
     */
    public function getBuyerCountForParam($paramInfo)
    {
        $sql = $this->getBuyerSql($this,$paramInfo);
        return $sql->count();
    }

    /**
     * 获取条件列表
     * @param $paramInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerListForParam($paramInfo)
    {
        $page = isset($paramInfo['page']) ? $paramInfo['page'] : 1;
        $limit = isset($paramInfo['limit']) ? $paramInfo['limit'] : 10;

        $sql = $this->getBuyerSql($this,$paramInfo)->order('created_at','desc')->page($page,$limit)->select();

        $buyerIdList = collect($sql)->pluck('buyer_id')->all();

        //为了效率，这边先将数据记录下来
        // 用户赊账
        $creditList = (new AreaMallCredit())->getBuyerCreditListForBuyerIds($buyerIdList);
        //用户余额
        $balanceList = (new BuyerBalance())->getBuyerBalanceListForBuyerIds($buyerIdList);
        //用户积分
        $pointsList = (new BuyerPoints())->getBuyerPointsListForBuyerId($buyerIdList);

        return collect($sql)->map(function($info) use($creditList,$balanceList,$pointsList){

            // 全平台赊账
//            $balance = (new AreaMallCredit())->getBuyerCreditListForBuyerIds($info['buyer_id']);
            $creditInfo = collect($creditList)->where('buyerId',$info['buyer_id'])->first();
            //查看用户余额
//            $balance = (new BuyerBalance())->getBuyerBalanceForBuyerId($info['buyer_id']);
            $balanceInfo = collect($balanceList)->where('buyerId',$info['buyer_id'])->first();
            //查看用户积分
//            $points = (new BuyerPoints())->getBuyerPointsForBuyerId($info['buyer_id']);
            $pointsInfo  = collect($pointsList)->where('buyerId',$info['buyer_id'])->first();
            return[
                'buyer_id'      => $info['buyer_id'],
                'buyer_name'    => $info['buyer_name'],
                'buyer_nickname'    => $info['buyer_nickname'],
                'buyer_header'  => !empty($info['buyer_header']) ? $info['buyer_header'] : 'http://',
                'buyer_tel'     => $info['buyer_tel'],
                'sex'           => $info['sex'],
                'is_credit'           => !empty($creditInfo) ? 1 : 0,
                'limits_credit'           => $info['limits_credit'],
                'wx_open_id'    => $info['wx_open_id'],
                'credit'       => isset($creditInfo['balance']) ? $creditInfo['balance'] : 0,
                'balance'       => isset($balanceInfo['balance']) ? $balanceInfo['balance'] : 0,
                'points'        => isset($pointsInfo['balance']) ? $pointsInfo['balance'] : 0,
                'is_plus'       => $info['is_plus'],
                'is_mall'       => $info['is_mall'],
                'created_at'       => $info['created_at'],
            ];
        })->all();
    }

    /**
     * @param $sql
     * @param $paramInfo
     * @return mixed
     */
    private function getBuyerSql($sql,$paramInfo)
    {

        if(isset($paramInfo['credit']) && !empty($paramInfo['credit']))
        {
            if(!empty($paramInfo['mall_id']))
            {
                //查询赊账 取出平台赊账的用户ID
                $credit = (new AreaMallCredit())->where(['mall_id'=>$paramInfo['mall_id']])->select();
                if($credit)
                {
                    $ids = [];
                    foreach ($credit as $key=>$val) {
                        $ids [] = $val['buyer_id'];
                    }
                    $sql = $sql->where('buyer_id','in', $ids);
                }

            }
        }

//        $sql = $sql->where('buyer_header','<>', '');
        if(isset($paramInfo['buyer_tel']) && !empty($paramInfo['buyer_tel'])){
            $sql = $sql->where('buyer_tel','like', '%'.$paramInfo['buyer_tel'].'%');
//            $sql = $sql->where('buyer_tel',$paramInfo['buyer_tel']);
        }

        if(isset($paramInfo['sex']) && $paramInfo['sex']!==''){
            $sql = $sql->where('sex',$paramInfo['sex']);
        }
        if(isset($paramInfo['buyer_name']) && !empty($paramInfo['buyer_name'])){
            $sql = $sql->where('buyer_name','like', '%'.$paramInfo['buyer_name'].'%');
        }
        if(isset($paramInfo['buyer_id']) && !empty($paramInfo['buyer_id'])){
            $sql = $sql->where('buyer_id','eq', $paramInfo['buyer_id']);
        }
        if(isset($paramInfo['buyer_nickname']) && !empty($paramInfo['buyer_nickname'])){
            $sql = $sql->where('buyer_nickname','eq', $paramInfo['buyer_nickname']);
        }
        return $sql;
    }

}