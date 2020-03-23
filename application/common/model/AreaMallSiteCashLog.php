<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/21
 * Time: 9:22
 */

namespace app\common\model;

/**
 * 店铺现金记录表
 * Class AreaMallSiteCashLog
 * @package app\common\model
 */
class AreaMallSiteCashLog extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}