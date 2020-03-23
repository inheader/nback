<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/9/11
 * Time: 14:58
 */

namespace app\common\model;


class OrderReturnApply extends Common
{

    public function getInfo($id)
    {
        return OrderReturnApply::get($id);
    }


    public function checkBuyerReturnTable($aid)
    {
        return OrderReturnTable::where('aid',$aid)->count();
    }

}