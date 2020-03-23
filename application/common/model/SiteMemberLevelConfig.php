<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/20
 * Time: 22:57
 */

namespace app\common\model;

use think\Db;
use think\Exception;

/**
 * 店铺会员等级价格配置
 * Class SiteMemberLevelConfig
 * @package app\common\model
 */
class SiteMemberLevelConfig extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';

    public function getMySiteId()           { return isset($this->site_id) ? $this->site_id : 0; }
    public function getMyLevel()            { return isset($this->level) ? $this->level : 0; }
    public function getMyLevelName()        { return isset($this->level_name) ? $this->level_name : ''; }
    public function getMyLevelDiscount()    { return isset($this->level_discount) ? $this->level_discount : 1; }

    /**
     * @param $siteId
     * @param $level
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null|SiteMemberLevelConfig
     */
    public function getInstanceBySiteAndLevel($siteId,$level){
        return $this->where([
            'site_id'   =>$siteId,
            'level'     =>$level,
        ])->find();
    }

    /**
     * 根据店铺ID获取店铺配置的会员等级的折扣
     * @param $siteId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMemberConfigForSiteId($siteId)
    {
        $list = $this->where([
            'site_id' => $siteId
        ])->order('level')->select();

        return collect($list)->map(function($info){
            return[
                'smlc_id'           => $info['smlc_id'],
                'site_id'           => $info['site_id'],
                'level'             => $info['level'],
                'level_name'        => $info['level_name'],
                'level_discount'    => $info['level_discount'],
            ];
        })->all();
    }

    /**
     * 会员等级配置折扣保存
     * @param $level
     * @param $site_id
     * @param $info
     * @return SiteMemberLevelConfig|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateMemberConfig($level,$site_id,$info)
    {
        $configIns = $this->where('level',$level)
            ->where('site_id',$site_id)->find();
        $update = [
            'site_id'           => $site_id,
            'level'             => $level,
            'level_name'        => $info['level_name'],
            'level_discount'    => $info['level_discount'],
        ];

        if(!empty($configIns)){
            return $this->where('smlc_id',$configIns['smlc_id'])->update($update);
        }
        $this->insert($update);
        return true;
    }



//    /**
//     * 已废弃
//     *会员等级配置折扣保存
//     * @param $siteId
//     * @param $level1
//     * @param $level2
//     * @param $level3
//     * @param $level4
//     * @param $level5
//     * @param $level6
//     * @return SiteMemberLevelConfig|bool
//     * @throws Exception
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function updateMemberConfigOld($siteId,$level1,$level2,$level3,$level4,$level5,$level6)
//    {
//        if(empty($level1) && empty($level2) && empty($level3) && empty($level4) && empty($level5) && empty($level6)){
//            throw new Exception('设置的折扣不能为空');
//        }
//
//        $info = $this->where([
//            'site_id' => $siteId
//        ])->find();
//
//        $update = [
//            'site_id' => $siteId,
//            'level1' => $level1,
//            'level2' => $level2,
//            'level3' => $level3,
//            'level4' => $level4,
//            'level5' => $level5,
//            'level6' => $level6,
//        ];
//
//        if(!empty($info)){
//            return $this->where('smlc_id',$info['smlc_id'])->update($update);
//        }
//        $this->insert($update);
//        return true;
//    }

    /**
     * 删除前台已经删掉的会员等级
     * @param $siteId
     * @param array $level
     * @return int
     */
    public function deleteWithOutSmlcIds($siteId,$level=[])
    {
        return $this->where([
            'site_id' => $siteId
        ])->whereNotIn('level',$level)->delete();
    }


}