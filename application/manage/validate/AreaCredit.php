<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/15
 * Time: 9:19
 */

namespace app\manage\validate;


use think\Validate;

/**
 * 赊账用户验证器
 * Class AreaCredit
 * @package app\manage\validate
 */
class AreaCredit extends Validate
{
    protected $rule = [
        'name'  =>  'require|max:25',
        'email' =>  'email',
    ];
}