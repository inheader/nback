<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/6/23
 * Time: 17:27
 */

namespace app\common\model;


class CreditPostpone extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
}