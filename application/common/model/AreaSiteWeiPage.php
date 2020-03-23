<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;
use think\facade\Cache;

/**
 * 店铺小程序首页
 * Class AreaSiteWeiPage
 * @package app\common\model
 * @author keinx
 */
class AreaSiteWeiPage extends Common
{
    /**
     * 获取店铺首页
     * @param $siteId
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSiteWeiPage($conditions= [])
    {
        if(isset($conditions['site_id'])){
            $where[] = ['site_id', 'eq', $conditions['site_id']];
        }else{
            $where[] = ['site_id', 'eq', 0];
        }

        if(isset($conditions['mall_id'])){
            $where[] = ['mall_id', 'eq', $conditions['mall_id']];
        }

        return $this->where($where)->order('sort','desc')->select();
    }

    /**
     * 更新记录
     * @param $id
     * @param array $data
     * @return array
     */
    public function updateData($id, $data=[]){
        $result = [
            'status' => true,
            'msg' => '保存成功',
            'data' => []
        ];

        if (!$this->where('aswp_id',$id)->update($data))
        {
            $result['status'] = false;
            $result['msg'] = '保存失败';
        }

        return $result;
    }

    /**
     * 添加记录
     * @param $data
     * @return array
     */
    public function addData($data)
    {
        $result = [
            'status' => true,
            'msg' => '保存成功',
            'data'=> []
        ];

        if (!$this->save($data))
        {
            $result['status'] = false;
            $result['msg'] = '保存失败';
        }
        return $result;
    }

    /**
     * 删除记录
     * @param $id
     * @return int
     */
    public function delData($id){
        $where[] = array('aswp_id', 'eq', $id);

        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * @param $aswpId
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fieldInfo($aswpId)
    {
        $where[] = ['aswp_id', 'eq', $aswpId];
        return $this->where($where)->find();
    }
}