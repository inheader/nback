<?php
namespace app\common\model;
use think\model\concern\SoftDelete;
use think\Db;

/**
 * 订单收货人信息
 * Class OrderBuyerInfo
 * @package app\common\model
 * @author keinx
 */
class OrderBuyerInfo extends Common
{
    protected $pk = 'obi_id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    /**
     * 获取收件信息的pay列表
     * @param $keywords
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayIdsForKeywords($keywords)
    {
        $keywords = '%'.$keywords.'%';
        $list = $this
            ->where('receiver_name|receiver_tel','LIKE',$keywords)
            ->select();

        return collect($list)->map(function($info){
            return isset($info['pay_sn']) ? $info['pay_sn'] : '';
        })->all();

    }

    /**
     * 根据支付信息获取收货信息
     * @param $paySn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayBuyerInfo($paySn)
    {
        $ins =  $this->where([
            'pay_sn' => $paySn
        ])->find();
        return [
            'receiver_name'         => isset($ins['receiver_name']) ? $ins['receiver_name'] : '',
            'receiver_address_info' => isset($ins['receiver_address_info']) ? $ins['receiver_address_info'] : '',
            'receiver_tel'          => isset($ins['receiver_tel']) ? $ins['receiver_tel'] : '',
            'receiver_area'         => $ins['receiver_province'].'-'.$ins['receiver_city'].'-'.$ins['receiver_area'],//收货区域
        ];

    }


    /**
     * 根据订单PaySn找到买家地址
     * @param $paySn
     * @return mixed
     */
    public function getOrderAddress($paySn){
        return $this->where('pay_sn', $paySn)->find();
    }


    /**
     * @param array $addressInfo
     * @return OROrderBuyerInfo
     * @throws ModelException
     */
    public function updateAddress(array $addressInfo){
        // try{
        //     $this->arrValidate($addressInfo, [
        //         'mallId'               => 'required',
        //         'siteId'                => 'required',
        //         'paySn'                => 'required',
        //         'buyerId'              => 'required',
        //         'receiverName'         => 'required',
        //         'receiverDaddressId'   => 'required',
        //         'receiverProvince'     => 'required',
        //         'receiverCity'         => 'required',
        //         'receiverArea'         => 'required',
        //         'receiverAddressInfo'  => 'required',
        //         'receiverTel'          => 'required',
        //     ]);
        // }catch(ValidationException $e){
        //     throw new ModelException('添加收货地址失败:'.$this->getValidationExceptionFailedMessage($e), GlobalErrCode::ERR_PARAM_VALIDATE_ERROR);
        // }

        $ordBuyInfoInstance = $this->getOrderAddress($addressInfo['paySn']);
        if(!$ordBuyInfoInstance){
            $ordBuyInfoInstance = new OrderBuyerInfo();
            $ordBuyInfoInstance['mall_id']     = $addressInfo['mallId'];
            $ordBuyInfoInstance['site_id']     = $addressInfo['siteId'];
            $ordBuyInfoInstance['pay_sn']       = $addressInfo['paySn'];
            $ordBuyInfoInstance['buyer_id']     = $addressInfo['buyerId'];
        }

        $ordBuyInfoInstance['receiver_name']         = $addressInfo['receiverName'];
        $ordBuyInfoInstance['receiver_address_id']   = $addressInfo['receiverDaddressId'];
        $ordBuyInfoInstance['receiver_province']     = $addressInfo['receiverProvince'];
        $ordBuyInfoInstance['receiver_city']         = $addressInfo['receiverCity'];
        $ordBuyInfoInstance['receiver_area']         = $addressInfo['receiverArea'];
        $ordBuyInfoInstance['receiver_address_info'] = $addressInfo['receiverAddressInfo'];
        $ordBuyInfoInstance['receiver_tel']          = $addressInfo['receiverTel'];
        $ordBuyInfoInstance['receiver_floor_num']    = $addressInfo['receiverFloorNum'];
        $ordBuyInfoInstance->save();

        return $ordBuyInfoInstance;
    }

    /**
     * 更新收货地址信息
     * @param $paySn
     * @param $data
     * @return bool
     */
    public function updateBuyerInfo($paySn,$data)
    {
        $update = [];
        if(!empty($data['receiver_name'])){
            $update['receiver_name'] = $data['receiver_name'];//姓名
        }
        if(!empty($data['receiver_address_info'])){
            $update['receiver_address_info'] = $data['receiver_address_info'];//收件地址
        }
        if(!empty($data['receiver_tel'])){
            $update['receiver_tel'] = $data['receiver_tel'];//电话
        }
        if(!empty($update)){
            $this->where('pay_sn', 'eq', $paySn)->update($update);
        }
        return true;
    }
}