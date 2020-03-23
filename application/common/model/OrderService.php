<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;
use think\Validate;
use think\model\concern\SoftDelete;

class OrderService extends Common
{


    /**
     * 新增服务关联
     * @param array $data
     * @return OROrderService
     * @throws ModelException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addData(array $data)
    {
        // try{
        //     $this->arrValidate($data, [
        //         'mallId'              => 'required',
        //         'siteId'              => 'required',
        //         'orderId'             => 'required',
        //         'paySn'               => 'required',
        //     ]);
        // }catch(ValidationException $validateException){
        //     throw new ModelException('订单服务单添加失败', GlobalErrCode::ERR_PARAM_VALIDATE_ERROR);
        // }

        $orderService = new OrderService();
        $orderService['mall_id']    = $data['mallId'];
        $orderService['site_id']    = $data['siteId'];
        $orderService['order_id']   = $data['orderId'];
        $orderService['pay_sn']     = $data['paySn'];
        $orderService['desc']       = isset($data['remark']) ? $data['remark'] : '';
        $orderService['created_at']     = date("Y-m-d H:i:s");
        $orderService->save();

        return $orderService;

    }

}