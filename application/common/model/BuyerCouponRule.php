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
 * 优惠券表
 * Class BuyerCouponRule
 * @package app\common\model
 */
class BuyerCouponRule extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    /**
     * @param $userWhere
     * @param int $beginTime
     * @param int $endTime
     * @param int $status
     * @param $keywords
     * @param int $page
     * @param int $pageNum
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCouponListForPar($userWhere,$beginTime = 0,$endTime = 0,$status = 0,$keywords,$page=1,$pageNum=10)
    {
        $listQuery = $this->where($userWhere);

        if(!empty($beginTime)){
            $listQuery = $listQuery->where('start_time','>=',$beginTime);
        }

        if(!empty($endTime)){
            $listQuery = $listQuery->where('end_time','<',$endTime);
        }

        if(!empty($status)){
            //当参数=2的时候表示关闭
            $st = $status==2? 0 :$status;
            $listQuery = $listQuery->where('cp_is_show',$st);
        }

        if(!empty($keywords)){
            $listQuery = $listQuery->where('cp_title','like','%'.$keywords."%");
        }

        $list = $listQuery->page($page, $pageNum)->select();

        return collect($list)->map(function($info){
            return[
                'id'                    => $info['cp_id'],
                'name'                  => $info['cp_title'],
                'cp_is_show'            => $info['cp_is_show'],
                'startTime'             => date("Y-m-d H:i:s",$info['start_time']),
                'endTime'               => date("Y-m-d H:i:s",$info['end_time']),
                'cpIsShowName'          => $info['cp_is_show'] == 1 ? '开启' : '关闭',
            ];
        })->all();
    }

    /**
     * @param $userWhere
     * @param int $beginTime
     * @param int $endTime
     * @param int $status
     * @param $keywords
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCouponCountForPar($userWhere,$beginTime = 0,$endTime = 0,$status = 0,$keywords)
    {
        $listQuery = $this->where($userWhere);

        if(!empty($beginTime)){
            $listQuery = $listQuery->where('start_time','>=',$beginTime);
        }

        if(!empty($endTime)){
            $listQuery = $listQuery->where('end_time','<',$endTime);
        }

        if(!empty($status)){
            //当参数=2的时候表示关闭
            $st = $status==2? 0 :$status;
            $listQuery = $listQuery->where('cp_is_show',$st);
        }

        if(!empty($keywords)){
            $listQuery = $listQuery->where('cp_title','like','%'.$keywords."%");
        }

        return $listQuery->count();

    }

}