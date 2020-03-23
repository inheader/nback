<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/8/13
 * Time: 16:27
 */

namespace app\common\model;


use think\Model;

class AreaMallSalesFlow extends Model
{

    /**
     * 新增流水
     * @param array $data
     */
    public function addData(array $data)
    {
        AreaMallSalesFlow::insert($data);
    }

}