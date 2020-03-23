<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/7/23
 * Time: 15:32
 */

namespace app\common\model;


class AreaMallBuyerCouponSite extends Common
{


    public function getSelect($cpId)
    {
        return $this->where('cp_id',$cpId)->select();
    }


    public function addData(array $data)
    {
        
    }

}