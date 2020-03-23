<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/20
 * Time: 22:57
 */
namespace app\common\model;

use think\Db;

/**
 * 买家余额表
 * Class Buyer
 * @package app\common\model
 */
class BuyerAddress extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    public function getMyMallId()          { return isset($this->mall_id) ? $this->mall_id : 0; }
    public function getMyBuyerId()          { return isset($this->buyer_id) ? $this->buyer_id : 0; }
    public function getMyAreaId()           { return isset($this->area_id) ? $this->area_id : 0; }
    public function getMyBuyerName()        { return isset($this->buyer_name) ? $this->buyer_name : ''; }
    public function getMyCityId()           { return isset($this->city_id) ? $this->city_id : 0; }
    public function getMyProvinceId()       { return isset($this->province_id) ? $this->province_id : 0; }
    public function getMyAreaInfo()         { return isset($this->area_info) ? $this->area_info : ''; }
    public function getMyAddress()          { return isset($this->address) ? $this->address : ''; }
    public function getMyTelPhone()         { return isset($this->tel_phone) ? $this->tel_phone : ''; }
    public function getMyIsDefault()        { return isset($this->is_default) ? $this->is_default : 1; }
    public function getMyFloorNum()         { return isset($this->floor_num) ? $this->floor_num : 0; }


    /**
     * 获取买家余额
     * @param $buyerId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBuyerAddressForId($id)
    {
        return $this->where('buyer_address_id',$id)->find();
    }




}