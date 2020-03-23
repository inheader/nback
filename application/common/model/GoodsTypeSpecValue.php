<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;

/**
 * 商品属性值表
 * Class GoodsTypeSpec
 * @package app\common\model
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 20:10
 */
class GoodsTypeSpecValue extends Common
{

    public function addAll($data = [ ])
    {
        return $this->saveAll($data);
    }

}
