<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/27
 * Time: 22:42
 */

namespace app\common\model;

use think\Validate;

/**
 * 限时折扣MODEL，暂不需要
 * Class ActDiscount
 * @package app\common\model
 */
class ActDiscount extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


//    /**
//     * @param $userWhere
//     * @param int $beginTime
//     * @param int $endTime
//     * @param int $status
//     * @param $keywords
//     * @param int $page
//     * @param int $pageNum
//     * @return array
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function getActListForPar($userWhere,$beginTime = 0,$endTime = 0,$status = 0,$keywords,$page=1,$pageNum=10)
//    {
//        $listQuery = $this->where($userWhere);
//
//        if(!empty($beginTime)){
//            $listQuery = $listQuery->where('start_time','<=',$beginTime);
//        }
//
//        if(!empty($endTime)){
//            $listQuery = $listQuery->where('end_time','>',$endTime);
//        }
//
//        if(!empty($status)){
//            $listQuery = $listQuery->where('is_enable',$status);
//        }
//
//        if(!empty($keywords)){
//            $listQuery = $listQuery->where('discount_name',$keywords);
//        }
//
//        $list = $listQuery->page($page, $pageNum)->select();
//
//        return collect($list)->map(function($info){
//            return[
//                'actId'                 => $info['discount_id'],
//                'actType'               => 1,//折扣ID
//                'actTypeName'           => '折扣活动',//折扣ID
//                'actName'               => $info['discount_name'],
//                'discountDescription'   => $info['discount_description'],
//                'startTime'             => date("Y-m-d H:i:s",$info['start_time']),
//                'endTime'               => date("Y-m-d H:i:s",$info['end_time']),
//                'isEnable'              => $info['is_enable'],
//            ];
//        })->all();
//    }
//
//    /**
//     * @param $userWhere
//     * @param int $beginTime
//     * @param int $endTime
//     * @param int $status
//     * @param $keywords
//     * @return array|\PDOStatement|string|\think\Collection
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function getActCountForPar($userWhere,$beginTime = 0,$endTime = 0,$status = 0,$keywords)
//    {
//        $listQuery = $this->where($userWhere);
//
//        if(!empty($beginTime)){
//            $listQuery = $listQuery->where('start_time','<=',$beginTime);
//        }
//
//        if(!empty($endTime)){
//            $listQuery = $listQuery->where('end_time','>',$endTime);
//        }
//
//        if(!empty($status)){
//            $listQuery = $listQuery->where('is_enable',$status);
//        }
//
//        if(!empty($keywords)){
//            $listQuery = $listQuery->where('discount_name',$keywords);
//        }
//
//        return $listQuery->count();
//
//    }

}