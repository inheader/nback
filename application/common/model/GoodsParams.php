<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;
use app\common\model\GoodsTypeParams;
/**
 * 商品参数
 * Class GoodsParams
 * @package app\common\model
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 20:10
 */
class GoodsParams extends Common
{


    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
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
        if(isset($post['seller_id'])&&$post['seller_id']){
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


    /**
     * 数据转换
     * User:wjima
     * Email:1457529125@qq.com
     * @param $list
     * @return array
     */
    protected function tableFormat($list)
    {
        return $list;
    }

    /**
     * 添加商品参数
     * @param $data
     * @return int|string
     */
    public function doAdd($data = [],$id=0)
    {
        if($id>0){
            $result = $this->save($data,['id'=>$id]);
        }else{
            $result = $this->save($data);

        }
        if ($result) {
            return $this->getLastInsID();
        }
        return $result;
    }

    /**
     * 删除商品参数
     * @param array $filter
     * @return bool|int
     */
    public function doDel($filter = [])
    {
        if (!$filter['id']) {
            return false;
        }
        //删除关联表
        $typeParams      = new GoodsTypeParams();
        $typeParamsModel = $typeParams->where(['params_id' => $filter['id']]);
        if ($typeParamsModel->find()) {
            $res = $typeParamsModel->delete();
            if ($res) {
                return $this->where($filter)->delete();
            } else {
                return false;
            }
        } else {
            return $this->where($filter)->delete();
        }
    }

    /**
     * 获取所有参数
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllParams($where=[])
    {
        $list = $this->where($where)->select();
        if(!$list->isEmpty()){
            return $list->toArray();
        }
        return [];
    }

    /**
     * 获取参数信息
     * @param int $params_id
     * @return array|bool
     */
    public function getParamsInfo($params_id=0)
    {
        if(!$params_id){
            return false;
        }
        $filter = [];
        if($params_id){
            $filter['id'] = $params_id;
        }

        $info = $this->where($filter)->field('id,name,value,type')->find();
        if($info){
            if($info['value']){
                $info['value'] = explode(' ',$info['value']);
            }
            return $info->toArray();
        }else{
            return false;
        }
    }
}
