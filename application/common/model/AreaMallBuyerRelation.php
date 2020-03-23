<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/7/23
 * Time: 15:32
 */

namespace app\common\model;


class AreaMallBuyerRelation extends Common
{

    

    /**
     * @param $mallId
     * @return AreaMall
     */
    public function MallBuyerRelationList($buyer_id){
        return $this->where('buyer_id', $buyer_id)->select();
    }



            /**
     * @param $mallId
     * @return AreaMall
     */
    public function MallBuyerRelation($buyer_id,$mall_id){
        return $this->where('buyer_id', $buyer_id)->where('mall_id', $mall_id)->find();
    }

        /**
     * @param $mallId
     * @return AreaMall
     */
    public function MallBuyerRelationInset($buyer_id,$mall_id){
        $data = ['buyer_id' => $buyer_id, 'mall_id' => $mall_id, 'created_at' => date('Y-m-d H:i:s',time())];
        return $this->insert($data);
    }
    
    
    
    /**
     * @param $mallId
     * @return AreaMall
     */
    public function MallBuyerRelationDelete($buyer_id,$mall_id){
        return $this->where('buyer_id', $buyer_id)->where('mall_id', $mall_id)->delete();
    }





}