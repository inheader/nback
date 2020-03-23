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
 * 区域地理位置映射表
 * Class AreaDataMapping
 * @package app\common\model
 */
class AreaDataMapping extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    /**
     * 保存区域信息
     * @param $mallId
     * @param $areaId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateAreaDataMapping($mallId,$areaId)
    {
        $areaDataMappingIns = $this
            ->where('mall_id',$mallId)
            ->where('area_id',$areaId)
            ->find();

        if(empty($areaDataMappingIns)){
            $this->insert([
                'mall_id' => $mallId,
                'area_id' => $areaId,
            ]);
        }

        return true;
    }

    /**
     * 删除不包含此区域映射信息
     * @param $mallId
     * @param array $areaIds
     * @return int
     */
    public function deleteAreaDataMappingOut($mallId,$areaIds=[])
    {
        return $this->where([
            'mall_id' => $mallId
        ])->whereNotIn('area_id',$areaIds)->delete();
    }

    /**
     * @param $mallId
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMappingForMallId($mallId)
    {
        return $this->where([
            'mall_id' => $mallId
        ])->select();
    }

    /**
     * 查看区域内的其他区域是否能使用
     * @param $mallId
     * @param $areaList
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOtherAreaUse($mallId,$areaList)
    {
        $areaDataMappingIns = $this
            ->where('mall_id','<>',$mallId)
            ->whereIn('area_id',$areaList)
            ->find();

        return $areaDataMappingIns;
    }

}