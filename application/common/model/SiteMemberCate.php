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
class SiteMemberCate extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $updateTime = 'updated_at';


    /**
     * 根据店铺ID获取店铺配置的会员等级的折扣
     * @param $siteId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMemberCateForSiteId($siteId)
    {
        $list = $this->where([
            'site_id' => $siteId
        ])->order('name')->select();

        return collect($list)->map(function($info){
            return[
                'site_id'           => $info['site_id'],
                'id'           => $info['id'],
                'name'             => $info['name'],
            ];
        })->all();
    }




    /**
     * 会员等级配置折扣保存
     * @param $name
     * @param $site_id
     * @param $info
     * @return SiteMemberLevelConfig|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateMemberCate($name,$site_id,$info)
    {
        $configIns = $this->where('name',$name)
            ->where('site_id',$site_id)->find();
        $update = [
            'site_id'           => $site_id,
            'name'             => $name,
        ];

        if(!empty($configIns)){
            return $this->where('id',$configIns['id'])->update($update);
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
    public function deleteWithOutSmlcIds($siteId,$name=[])
    {
        return $this->where([
            'site_id' => $siteId
        ])->whereNotIn('name',$name)->delete();
    }


}