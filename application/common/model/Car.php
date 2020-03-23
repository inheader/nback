<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/10/10
 * Time: 17:02
 */

namespace app\common\model;


class Car extends Common
{
    /**
     * @param array $data
     */
    public function addData(array $data)
    {
        $car           = new Car;

        $car['site_id']         = $data['site_id'];
        $car['car_number']      = $data['car_number'];
        $car['car_type']        = $data['car_type'];
        $car['car_level']       = $data['car_level'];
        $car['car_box_length']  = $data['car_box_length'];
        $car['car_box_width']   = $data['car_box_width'];
        $car['car_box_height']  = $data['car_box_height'];
        $car['car_box_weight']  = $data['car_box_weight'];
        $car['car_tons']        = $data['car_tons'];
        $car['car_start_km']    = $data['car_start_km'];
        $car['car_start_price'] = $data['car_start_price'];
        $car['car_km_price']    = $data['car_km_price'];
        $car['car_km_end']      = $data['car_km_end'];
        $car['car_express_price']           = $data['car_express_price'];
        $car['car_express_km_price']        = $data['car_express_km_price'];
        $car['is_urgent']       = $data['is_urgent'];
        $car['status']          = $data['status'];
        $car['car_start_km_urgent']             = isset($data['car_start_km_urgent']) ? $data['car_start_km_urgent'] : 1;
        $car['car_start_price_urgent']          = isset($data['car_start_price_urgent']) ? $data['car_start_price_urgent'] : 0.00;
        $car['car_km_price_urgent']             = isset($data['car_km_price_urgent']) ? $data['car_km_price_urgent'] : 0.00;
        $car['car_km_end_urgent']               = isset($data['car_km_end_urgent']) ? $data['car_km_end_urgent'] : 1;
        $car['created_at']                      = $data['created_at'];
        $car->save();
    }

    /**
     * @param $id
     * @param array $data
     */
    public function editData($id,array $data)
    {
        $car           = Car::get($id);
        $car['car_number']      = $data['car_number'];
        $car['car_type']        = $data['car_type'];
        $car['car_level']       = $data['car_level'];
        $car['car_box_length']  = $data['car_box_length'];
        $car['car_box_width']   = $data['car_box_width'];
        $car['car_box_height']  = $data['car_box_height'];
        $car['car_box_weight']  = $data['car_box_weight'];
        $car['car_tons']        = $data['car_tons'];
        $car['car_start_km']    = $data['car_start_km'];
        $car['car_start_price'] = $data['car_start_price'];
        $car['car_km_price']    = $data['car_km_price'];
        $car['car_km_end']      = $data['car_km_end'];
        $car['car_express_price']           = $data['car_express_price'];
        $car['car_express_km_price']        = $data['car_express_km_price'];
        $car['is_urgent']       = $data['is_urgent'];
        $car['status']          = $data['status'];
        $car['car_start_km_urgent']             = isset($data['car_start_km_urgent']) ? $data['car_start_km_urgent'] : $car['car_start_km_urgent'];
        $car['car_start_price_urgent']          = isset($data['car_start_price_urgent']) ? $data['car_start_price_urgent'] : $car['car_start_price_urgent'];
        $car['car_km_price_urgent']             = isset($data['car_km_price_urgent']) ? $data['car_km_price_urgent'] : $car['car_km_price_urgent'];
        $car['car_km_end_urgent']               = isset($data['car_km_end_urgent']) ? $data['car_km_end_urgent'] : $car['car_km_end_urgent'];

        $car->save();
    }

    /**
     * @param $ids
     */
    public function delData($ids)
    {
        $car = Car::get($ids);
        $car->delete();
    }

}