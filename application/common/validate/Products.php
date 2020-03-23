<?php
/**
 * 货品验证规则
 * Created by PhpStorm.
 * User: mark
 * Date: 2018/3/17
 * Time: 下午12:36
 */
namespace app\common\validate;

use think\Validate;

class Products extends Validate
{


    public $rule = [
        'goods_id'   => 'require|number',
        'price'      => 'float',
        'price1'      => 'float',
        'price2'      => 'float',
        'price3'      => 'float',
        'price4'      => 'float',
        'price5'      => 'float',
        'price6'      => 'float',
        'is_defalut' => 'in:1,2',
        'marketable' => 'in:1,2',
        'stock'      => 'number|max:8',
        'sn'         => 'unique:products',
    ];

    protected $scene = [
        'edit'  =>  ['goods_id','price','price1','price2','price3','price4','price5','price6','is_defalut','marketable','stock','sn'=>'unique:products,sn^id'],
    ];

    public $message = [
        'goods_id.require' => '商品ID不能为空',
        'goods_id.number'  => '商品ID非法',
        'price'            => '请输入正确的销售价',
        'is_defalut.in'    => '是否默认商品超出范围',
        'marketable.in'    => '上下架状态超出范围',
        'stock.number'     => '库存非法',
        'stock.max'        => '库存最多只能输入8位数字',
        'sn.unique'        => '货品编号不能重复',
    ];

}