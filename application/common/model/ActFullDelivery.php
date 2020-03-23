<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/27
 * Time: 22:42
 */

namespace app\common\model;

use think\Validate;
use think\Db;

/**
 * 满减MODEL
 * Class ActFullDelivery
 * @package app\common\model
 */
class ActFullDelivery extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


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
    public function getActListForPar($userWhere,$beginTime = 0,$endTime = 0,$status = 0,$keywords,$page=1,$pageNum=10)
    {
        $listQuery = $this->where($userWhere);

        if(!empty($beginTime)){
            $listQuery = $listQuery->where('start_time','>=',$beginTime);
        }

        if(!empty($endTime)){
            $listQuery = $listQuery->where('end_time','<',$endTime);
        }

        if(!empty($status)){
            $listQuery = $listQuery->where('is_enable',$status);
        }

        if(!empty($keywords)){
            $listQuery = $listQuery->where('fda_name','like','%'.$keywords."%");
        }

        $list = $listQuery->page($page, $pageNum)->select();

        return collect($list)->map(function($info){
            return[
                'actId'                 => $info['fda_id'],
                'actName'               => $info['fda_name'],
                'discountDescription'   => $info['fda_description'],
                'startTime'             => date("Y-m-d H:i:s",$info['start_time']),
                'endTime'               => date("Y-m-d H:i:s",$info['end_time']),
                'isEnable'              => $info['is_enable'],
                'isEnableName'          => $info['is_enable'] == 1 ? '开启' : '关闭',
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
    public function getActCountForPar($userWhere,$beginTime = 0,$endTime = 0,$status = 0,$keywords)
    {
        $listQuery = $this->where($userWhere);

        if(!empty($beginTime)){
            $listQuery = $listQuery->where('start_time','>=',$beginTime);
        }

        if(!empty($endTime)){
            $listQuery = $listQuery->where('end_time','<',$endTime);
        }

        if(!empty($status)){
            $listQuery = $listQuery->where('is_enable',$status);
        }

        if(!empty($keywords)){
            $listQuery = $listQuery->where('fda_name','like','%'.$keywords."%");
        }

        return $listQuery->count();

    }

    /**
     * 根据活动ID获取活动详情
     * @param $userWhere
     * @param $actId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getActInfo($userWhere,$actId)
    {
        $actInfo = $this->where($userWhere)->where('fda_id',$actId)->find();
        if(empty($actInfo)){
            return [];
        }
        $ruleList = (new ActFullDeliveryRule())->getActRuleListForActId($actInfo['fda_id']);

        return[
            'actId'                 => $actInfo['fda_id'],
            'actName'               => $actInfo['fda_name'],
            'discountDescription'   => $actInfo['fda_description'],
            'isAllGoods'            => $actInfo['is_all_goods'],
            'startTime'             => date("Y-m-d H:i:s",$actInfo['start_time']),
            'endTime'               => date("Y-m-d H:i:s",$actInfo['end_time']),
            'isEnable'              => $actInfo['is_enable'],
            'ruleList'              => $ruleList,
        ];
    }

    /**
     * 更新活动数据
     * @param $actId
     * @param $updateActInfo
     * @param array $userAddWhere
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateActInfo($actId,$updateActInfo,$userAddWhere=[])
    {
        $actIns = $this->where('fda_id',$actId)->find();
        $update = [
            'fda_name'      => $updateActInfo['actName'],
            'fda_description' => $updateActInfo['discountDescription'],
            'is_enable' => $updateActInfo['isEnable'],
            'start_time' => $updateActInfo['beginTime'],
            'end_time' => $updateActInfo['endTime'],
        ];

        if(!empty($actIns)){
            $this->where('fda_id',$actId)->update($update);
            return $actId;
        }
        //这里说明是新增了
        $update['site_id']=$userAddWhere['site_id'];
        $update['mall_id']=$userAddWhere['mall_id'];
        $this->insert($update);
        return $this->getLastInsID();
    }

    /**
     * 删除活动规则
     * @param $userWhere
     * @param $actId
     * @return array|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deleteActRule($userWhere,$actId)
    {
        $actInfo = $this->where($userWhere)->where('fda_id',$actId)->find();
        if(empty($actInfo)){
            return [];
        }
        return (new ActFullDeliveryRule())->deleteActRuleListForActId($actInfo['fda_id']);
    }

    /**
     * 获取权限下的ID
     * @param $userWhere
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getActIdListForAuth($userWhere)
    {
        $actInfo = $this->where($userWhere)->select();
        return collect($actInfo)->map(function($info){
            return $info['fda_id'];
        })->all();

    }

    /**
     * 取消全部商品
     * @param $actId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelAllGoods($actId)
    {
        $actIns = $this->where('fda_id',$actId)->find();
        $update = [
            'is_all_goods'      => 0,
        ];

        if(!empty($actIns)){
            $this->where('fda_id',$actId)->update($update);
            return $actId;
        }

    }


    /**
     * 添加全部商品
     * @param $actId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addAllGoods($actId)
    {
        $actIns = $this->where('fda_id',$actId)->find();
        $update = [
            'is_all_goods'      => 1,
        ];

        if(!empty($actIns)){
            $this->where('fda_id',$actId)->update($update);
            $actGoodsModel = (new ActFullDeliveryGoods());
            $actGoodsModel->deleteActAllGoodsForActId($actId);
            return $actId;
        }

    }


    /**
     * 删除活动
     * @param $actId
     * @param $siteId
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deleteActIns($actId,$siteId){
        $actIns = $this->where('fda_id',$actId)->find();
//        dump($actId);
//        dump($siteId);
        if($actIns['site_id']!=$siteId){
            return error_code(10007);
        }

        Db::startTrans();
        try{
            //删除活动规格
            (new ActFullDeliveryRule())->where('fda_id',$actId)->delete();
            //删除活动商品
            (new ActFullDeliveryGoods())->where('fda_id',$actId)->delete();
            //删除活动
            $this->where('fda_id',$actId)->delete();
            Db::commit();
            return [
                'status' => true,
                'data' => '',
                'msg' => ''
            ];
        }catch(\Exception $e){
            Db::rollback();
            return error_code(10007);
        }
    }

}