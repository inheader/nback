<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/18
 * Time: 23:13
 */

namespace app\common\model;

use think\Validate;

class AreaMallSiteAddress extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


    /**
     * 根据店铺ID获取店铺的地址信息
     * @param $siteId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAddressListForSiteId($siteId)
    {
        $list = $this->where([
            'site_id' => $siteId
        ])->select();

        return collect($list)->map(function($info){
            return[
                'amsaId'        => $info['amsa_id'],
                'address'       => $info['address'],
                'phone'         => $info['phone'],
                'latitude'      => $info['latitude'],   //纬度
                'longitude'     => $info['longitude'],  //经度
            ];
        })->all();
    }

    /**
     * 删除前台已经删掉的店铺地址
     * @param $siteId
     * @param array $amsaIds
     * @return int
     */
    public function deleteSiteAddressOut($siteId,$amsaIds=[])
    {
        return $this->where([
            'site_id' => $siteId
        ])->whereNotIn('amsa_id',$amsaIds)->delete();
    }

    /**
     * 店铺地址的新增/编辑
     * @param $amsaId
     * @param $info
     * @return AreaMallSiteAddress|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateSiteAddressOut($amsaId,$info)
    {
        $addressIns = $this->where('amsa_id',$amsaId)->find();
        $update = [
            'site_id' => $info['site_id'],
            'address' => $info['address'],
            'longitude' => $info['longitude'],
            'latitude' => $info['latitude'],
        ];

        if(!empty($addressIns)){
            return $this->where('amsa_id',$amsaId)->update($update);
        }
        $this->insert($update);
        return true;
    }


}