<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/27
 * Time: 22:42
 */

namespace app\common\model;

use think\Validate;

/**
 * 区域MODEL
 * Class AreaMall
 * @package app\common\model
 */
class AreaMall extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    const OPEN = 1; //开启
    const CLOSE = 0; //关闭


    public function getAreaShippingFee() { return isset($this->shipping_fee) ? $this->shipping_fee : 0;}//运费
    public function getAreaFreeShippingFee() { return isset($this->free_shipping_fee) ? $this->free_shipping_fee : 0;}//减邮价
    public function getAreaReturnShippingFee() { return isset($this->return_shipping_fee) ? $this->return_shipping_fee : 0;}//退货费
    public function getAreaExpressFee() { return isset($this->express_fee) ? $this->express_fee : 0;}//特快服务费
    public function getAreaExpressDiscount() { return isset($this->express_discount) ? $this->express_discount : 0;}//特快服务折扣

    public function getMyMallNoun() { return isset($this->mall_noun) ? $this->mall_noun : 0;}
    public function getMyMallCreditNoun() { return isset($this->mall_credit_noun) ? $this->mall_credit_noun : 0;}


    /**
     * @param $mallId
     * @return AreaMall
     */
    public function InstanceByMallId($mallId){
        return $this->where('mall_id', $mallId)->find();
    }

    /**
     * @param $mallId
     * @return AreaMall
     */
    public function areaMallList(){
        return $this->where('ip_open', 1)->select();
    }

    /**
     * 保存区域信息
     * @param $mallId
     * @param $info
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateAreaMall($mallId,$info)
    {
        $areaMallIns = $this->where('mall_id',$mallId)->find();
        $update = [
            'mall_name' => $info['mall_name'],
        ];

        if(!empty($areaMallIns)){
            $this->where('mall_id',$mallId)->update($update);
            return $mallId;
        }
        $this->insert($update);
        $mallId = $this->getLastInsID();
        return $mallId;
    }

    /**
     * 修改区域状态
     * @param $mallId
     * @param $isOpen
     * @return AreaMall
     */
    public function updateStatus($mallId,$isOpen)
    {
        return $this->where('mall_id',$mallId)
            ->update([
                'ip_open'=>$isOpen
            ]);
    }

    public function flow()
    {
        return $this->hasOne('AreaCreditFlow','mall_id','mall_id')->setEagerlyType(0);
    }
    
}