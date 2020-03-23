<?php
/**
 * 商品验证规则
 * Created by PhpStorm.
 * User: mark
 * Date: 2018/3/17
 * Time: 下午12:36
 */
namespace app\common\validate;

use think\Validate;

class Goods extends Validate
{

    protected $rule = [
        'name' => 'require|max:100',
        'brief' => 'max:255',
        'price' => 'float',
        'price1' => 'float',
        'price2' => 'float',
        'price3' => 'float',
        'price4' => 'float',
        'price5' => 'float',
        'price6' => 'float',
        'image_id' => 'require',
        'goods_cat_id' => 'require|number',
        'goods_type_id' => 'require|number',
        'brand_id' => 'number',
        'is_nomal_virtual' => 'in:1,2',
        'marketable' => 'in:1,2',
        'stock' => 'number|max:8',
        'weight' => 'float|max:10',
        'sort' => 'number|max:5',
        'bn'=>'unique:goods',
    ];

    protected $scene = [
        'edit'  =>  ['name','brief','price','price1','price2','price3','price4','price5','price6','image_id','goods_cat_id','goods_type_id','brand_id','is_nomal_virtual','marketable','stock','weight','sort','bn'=>'unique:goods,bn^id'],
        'import'  =>  ['name','brief','price','price1','price2','price3','price4','price5','price6','goods_cat_id','goods_type_id','brand_id','is_nomal_virtual','marketable','stock','weight','sort'],
    ];

    protected $message = [
        'name.require' => '商品名称必填',
        'name.max' => '商品名称最长100个字符',
        'brief.max' => '商品简介最多255个字符',
        'price' => '请输入正确的销售价',
        'image_id.require' => '图片不能为空',
        'goods_cat_id.require' => '商品分类不能为空',
        'goods_type_id.require' => '商品类型不能为空',
        'goods_cat_id.number' => '商品分类非法',
        'brand_id.number' => '商品品牌非法',
        'is_nomal_virtual.in' => '是否虚拟商品超出范围',
        'marketable.in' => '上下架状态超出范围',
        'stock.number' => '库存非法',
        'stock.max' => '库存最多只能输入8位数字',
        'weight.number' => '商品重量非法',
        'weight.max' => '商品重量最多只能输入10位数字',
        'sort.number' => '商品排序非法',
        'sort.max' => '商品排序最多只能输入5位数字',
        'bn.unique' => '商品编号不能重复',
    ];

}