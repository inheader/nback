<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/4/26
 * Time: 14:39
 */

namespace app\common\model;


class HistorySearch extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';
}