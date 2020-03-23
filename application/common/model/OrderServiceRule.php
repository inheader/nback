<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/7/22
 * Time: 9:58
 */

namespace app\common\model;


class OrderServiceRule extends Common
{


    /**
     * 录入服务规则
     * @param array $data
     * @return OROrderServiceRule
     * @throws ModelException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addData(array $data)
    {
        // try{
        //     $this->arrValidate($data, [
        //         'paySn'               => 'required',
        //     ]);
        // }catch(ValidationException $validateException){
        //     throw new ModelException('订单服务规则添加失败', GlobalErrCode::ERR_PARAM_VALIDATE_ERROR);
        // }

        $serviceRule = new OrderServiceRule();
        $serviceRule['pay_sn'] = $data['paySn'];
        $serviceRule['site_rule'] = isset($data['siteRule']) ? $data['siteRule'] : '';
        $serviceRule['price'] = isset($data['price']) ? $data['price'] : 0;
        $serviceRule->save();

        return $serviceRule;
    }

}