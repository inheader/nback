<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/19
 * Time: 23:27
 */


namespace app\common\model;

use think\Validate;

/**
 * 快速选材商品
 * Class AreaMallClassCatGoodsMapping
 * @package app\common\model
 */
class AreaMallClassCatGoodsMapping extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    /**
     * @param $deleteIds
     * @return bool
     */
    public function deleteGoodsIdsForClassId($deleteIds)
    {
        $this->whereIn('amcc_id',$deleteIds)->delete();
        return true;
    }

    /**
     * @param $id
     * @param $goodsId
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfo($id,$goodsId)
    {
        return $this->where(['amcc_id'=>$id,'spc_id'=>$goodsId])->find();
    }

    /**
     * @param mixed $where
     * @param array $data
     * @return bool|int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateData($where,$data=[])
    {
        return $this->where($where)->update($data);
    }


    /**
     * @param $quickId
     * @param $goodsId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addQuickGoodsForQuickId($quickId, $goodsId,$brand_id=0)
    {
        //更新账号信息
        $actGoodsIns = $this->where('amcc_id',$quickId)->where('spc_id',$goodsId)->where('brand_id',$brand_id)->find();
        if(empty($actGoodsIns)){
            $saveInfo['amcc_id']    = $quickId;
            $saveInfo['brand_id']   = $brand_id;
            $saveInfo['spc_id']     = $goodsId;
            $this->insert($saveInfo);
        }
        return true;
    }


    /**
     * @param $amccId
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsIdsForQuick($amccId)
    {
        $list = $this->where('amcc_id',$amccId)->select();
        return collect($list)->map(function($info){
            return $info['spc_id'];
        });
    }

}