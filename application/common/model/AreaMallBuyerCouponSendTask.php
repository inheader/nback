<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/10/8
 * Time: 13:27
 */

namespace app\common\model;


use think\Exception;
use think\exception\PDOException;

class AreaMallBuyerCouponSendTask extends Common
{


    public function addData(array $data)
    {
        $task = new AreaMallBuyerCouponSendTask();

        $task->title = $data['title'];
        $task->content = $data['content'];
        $task->task_start_time = $data['task_start_time'];
        $task->task_end_time = $data['task_end_time'];
        $task->weight = $data['weight'];
        $task->status = $data['status'];
        $task->created_at = $data['created_at'];

        $task->save();
    }

}