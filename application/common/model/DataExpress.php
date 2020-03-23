<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\common\model;
use think\Validate;
use think\Db;

/**
 * 物流公司表
 * Class DataExpress
 * @package app\common\model
 * @author keinx
 */
class DataExpress extends Common
{
    public function getMyId()               { return isset($this->express_id) ? $this->express_id : 0;}
    public function getMyExpressName()      { return isset($this->e_name) ? $this->e_name : '';}
    public function getMyECode()            { return isset($this->e_code) ? $this->e_code : '';}
    public function getMyELetter()          { return isset($this->e_letter) ? $this->e_letter : '';}
    public function getMyEUrl()             { return isset($this->e_url) ? $this->e_url : '';}
    public function getMyEState()           { return isset($this->e_state) ? $this->e_state : '';}
    public function getMyCode()             { return isset($this->code) ? $this->code : '';}

    public function add($data)
    {
        return $this->insert($data);
    }

    protected function tableWhere($post)
    {
        $result['where'] = [];
        $result['field'] = "*";
        $result['order'] = ['sort'=>'asc'];
        return $result;
    }

    /**
     * 获取全部物流公司
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAll()
    {
        return $this->where([])
            ->select();
    }


    /**
     * @param $code
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyInstanceByCode($eCode){
        return $this->where(['e_code' => $eCode])->find();
    }
}