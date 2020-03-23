<?php
/**
 * Created by PhpStorm.
 * User: qingger
 * Date: 2019/2/24
 * Time: 20:37
 */

namespace app\common\model;


class OrderReturnGoods extends Common
{
    public function getMyOspId()     { return isset($this->osp_id) ? $this->osp_id : 0;}
    public function getMyReturnNum() { return isset($this->return_num) ? $this->return_num : null;}

    /**
     * @param $returnId
     * @return OrderReturnGoods
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myInstanceByReturnId($returnId){
        return $this->where('return_id', $returnId)->select();
    }
}