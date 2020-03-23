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
 * 买家积分记录表
 * Class Buyer
 * @package app\common\model
 */
class BuyerPointsLog extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    /**
     * @param $isCount
     * @param $buyerId
     * @param int $page
     * @param int $limit
     * @return array|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerPointsLog($isCount,$buyerId,$page=1,$limit=10)
    {
        $list = $this->where('buyer_id',$buyerId);
        if(empty($isCount)){
            $list = $list->order('updated_at','desc')->page($page,$limit)->select();
            return collect($list)->map(function($info){
                return[
                    'type'      => $info['type']==1 ? '获得' :'消费',
                    'num'       => $info['num'],
                    'balance'   => $info['balance'],
                    'remarks'   => $info['remarks'],
                    'created_at'=> $info['created_at'],
                ];
            })->all();
        }
        return $list->count();

    }


}