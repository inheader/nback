<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/7/9
 * Time: 15:54
 */

namespace app\common\model;


class AreaMallClassCatBrand extends Common
{

    public function getInfo($id)
    {
        return $this->where('id',$id)->find();
    }


    public function getListInfo($classId)
    {
        return $this->where('amcc_id',$classId)->select();
    }

    public function deleteTagsIdsForClassId($deleteIds)
    {
        $this->whereIn('amcc_id',$deleteIds)->delete();
        return true;
    }

}