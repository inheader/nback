<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\model\GoodsTypeSpecValue;

/**
 * 商品属性
 * Class GoodsTypeSpec
 * @package app\common\model
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 20:10
 */
class GoodsTypeSpec extends Common
{

    public function getSpecValue()
    {
        return $this->hasMany('GoodsTypeSpecValue','spec_id','id');
    }

    /**
     * 默认排序
     * @param $post
     * @return mixed
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-11 16:32
     */
    protected function tableWhere($post)
    {
        $where = [];
        if(isset($post['seller_id'])&&$post['seller_id']!=='')
        {
            $where[] = ['seller_id','eq',$post['seller_id']];
        }
        //用户是店铺权限
        if(isset($post['site_id']) && $post['site_id'] != ""){
            $where[] = ['site_id', 'eq', $post['site_id']];
        }
        //用户是区域权限
        if(isset($post['mall_id']) && $post['mall_id'] != ""){
            $where[] = ['mall_id', 'eq', $post['mall_id']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = ['id'=>'desc'];
        return $result;
    }

    /***
     * 查询所有属性
     * @param array $where
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllSpec($where=[]){
        return $this->where($where)->select();

    }

    /**
     * 获取属性值
     * @param $list
     * @return mixed
     * @throws \think\exception\DbException
     */
    protected function tableFormat($list)
    {
        $goodsTypeSpecValue = new GoodsTypeSpecValue();
        if($list)
        {
            foreach((array)$list->toArray() as $key=>$val)
            {
                $spec_value = $goodsTypeSpecValue::all([
                    'spec_id'=>$val['id']
                ]);
                $list[$key]['spec_value'] = $spec_value;

            }
        }
        return $list;
    }

    /**
     * 添加商品属性
     * @param $data
     * @return int|string
     */
    public function add($data)
    {
        return $this->insert($data);
    }

    /**
     * 获取参数信息
     * @param int $spec_id
     * @return array|bool
     */
    public function getSpecInfo($spec_id=0)
    {
        if(!$spec_id){
            return false;
        }
        $filter = [];
        if($spec_id){
            $filter['id'] = $spec_id;
        }

        $info = $this->where($filter)->field('id,name')->find();
        if($info){
            return $info->toArray();
        }else{
            return false;
        }
    }

}
