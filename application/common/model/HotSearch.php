<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/4/26
 * Time: 13:29
 */

namespace app\common\model;

/**
 * 热门词模型
 * @package app\common\model
 */
class HotSearch extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';


}