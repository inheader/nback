<?php
// +----------------------------------------------------------------------
// | JSHOP [ 小程序 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\crontab;
use taskphp\Utils;

/**
 * 订单相关定时任务
 */
class Order
{
    /**
     * 取消订单
     */
    public static function cancle()
    {
        Utils::log('自动取消订单任务开始时间：' . date('Y-m-d H:i:s') . PHP_EOL);
        $setting = getSetting('order_cancel_time');
        $order   = model('common/Order');
        $order->autoCancel($setting);
        Utils::log('自动取消订单任务结束时间：' . date('Y-m-d H:i:s') . PHP_EOL);
    }

    /**
     * 订单自动完成
     */
    public static function complete()
    {
        Utils::log('自动完成订单任务开始时间：'.date('Y-m-d H:i:s').PHP_EOL);
        $setting = getSetting('order_complete_time');
        model('common/Order')->autoComplete($setting);
        Utils::log('自动完成订单任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }

    /**
     * 自动评价订单
     */
    public static function evaluate()
    {
        Utils::log('自动评价订单任务开始时间：'.date('Y-m-d H:i:s').PHP_EOL);

        $setting = getSetting('order_autoEval_time');
        model('common/Order')->autoEvaluate($setting);
        Utils::log('自动评价订单任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }

    /**
     * 订单自动签收
     */
    public static function sign()
    {
        Utils::log('自动签收订单任务开始时间：'.date('Y-m-d H:i:s').PHP_EOL);
        $setting = getSetting('order_autoSign_time');
        model('common/Order')->autoSign($setting);

        Utils::log('自动签收订单任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }
}