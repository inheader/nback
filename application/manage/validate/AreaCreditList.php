<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/15
 * Time: 9:20
 */

namespace app\manage\validate;


use think\Validate;

/**
 * 申请赊账验证器
 * Class AreaCreditList
 * @package app\manage\validate
 */
class AreaCreditList extends Validate
{
    protected $rule = [
        'username'  =>  'require',
        'card_id' =>  'require',
        'actual_money' => 'require'
    ];

    protected $message  =   [
        'username.require' => '真实名称必填',
        'card_id.require'     => '身份证号码必填',
        'actual_money.require'     => '实际金额必填',
    ];

    // 自定义验证规则
    protected function checkName($value,$rule,$data=[])
    {
        return $rule == $value ? true : '名称错误';
    }

}