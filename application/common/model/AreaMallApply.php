<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/13
 * Time: 10:15
 */

namespace app\common\model;

use think\Db;

/**
 * 区域赊账流水表模型
 * Class AreaCreditFlow
 * @package app\common\model
 */
class AreaMallApply extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


}