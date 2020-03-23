<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/10/10
 * Time: 16:27
 */

namespace app\site\controller;

use app\common\model\AreaMallSite;
use app\common\model\Buyer;
use think\Db;
use think\exception\PDOException;
use think\Validate;

/**
 * 车辆管理控制
 * Class Car
 * @package app\site\controller
 */
class Car extends \app\common\controller\Site
{

    //初始化2
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    //查看
    public function index()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if (empty($siteId)) {
            return [
                'status' => false,
                'msg' => '只有店铺管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];

                $where['site_id'] = $siteId;
                list($where,$page,$limit) = [$where,$params['page'],$params['limit']];

                $list = (new \app\common\model\Car())
                    ->where($where)
                    ->page($page,$limit)
                    ->order('created_at desc')
                    ->select();

                $total = (new \app\common\model\Car())
                    ->where($where)
                    ->count();

                $result = array("code"=>0,"count" => $total, "data" => $list);
                return json($result);
            }

        }

        return $this->fetch();
    }

    /**
     * @return array|mixed
     */
    public function add()
    {
        $this->view->engine->layout(false);
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if (empty($siteId)) {
            return [
                'status' => false,
                'msg' => '只有店铺管理员才有权限打开此界面'
            ];
        }

        //数据添加处理
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
//                $license = $this->isCarLicense($params['car_number']);
//                if(!$license)
//                {
//                    return ['status'=>false,'msg'=>'车牌号码格式错误'];
//                }
                $repeat = $this->isCarRepeat($params['car_number']);
                if(!$repeat)
                {
                    return ['status'=>false,'msg'=>'该车牌号码已存在'];
                }
                Db::startTrans();
                try{

                    $datas = [];
                    if($params['is_urgent'] == 1)
                    {
                        $datas = [
                            'car_start_km_urgent' => $params['car_start_km_urgent'],
                            'car_start_price_urgent' => $params['car_start_price_urgent'],
                            'car_km_price_urgent' => $params['car_km_price_urgent'],
                            'car_km_end_urgent' => $params['car_km_end_urgent'],
                        ];
                    }

                    $data = [
                        'site_id'           => $siteId,
                        'car_number'        => $params['car_number'],
                        'car_type'          => $params['car_type'],
                        'car_level'         => $params['car_level'],
                        'car_box_length'    => $params['car_box_length'],
                        'car_box_width'     => $params['car_box_width'],
                        'car_box_height'    => $params['car_box_height'],
                        'car_box_weight'    => $params['car_box_weight'],
                        'car_tons'          => $params['car_tons'],
                        'car_start_km'      => $params['car_start_km'],
                        'car_start_price'   => $params['car_start_price'],
                        'car_km_price'      => $params['car_km_price'],
                        'car_km_end'        => $params['car_km_end'],
                        'car_express_price'     => $params['car_express_price'],
                        'car_express_km_price'  => $params['car_express_km_price'],
                        'is_urgent'         => $params['is_urgent'],
                        'status'            => $params['status'],
                        'created_at'        => date("Y-m-d H:i:s"),
                    ];

                    $arr = array_merge($datas,$data);

                    (new \app\common\model\Car())->addData($arr);
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败','data'=>$e->getMessage()];
                }
            }
        }

        return $this->fetch();
    }


    public function edit($id=null)
    {
        $this->view->engine->layout(false);
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if (empty($siteId)) {
            return [
                'status' => false,
                'msg' => '只有店铺管理员才有权限打开此界面'
            ];
        }
        $id = isset($id) ? $id : $this->request->param('id');
        if($id)
        {
            //获取信息
            $get = \app\common\model\Car::get($id);
            if(!$get)
                return ['status'=>false,'msg'=>'数据不存在'];
            if($this->request->isPost())
            {
                $params = $this->request->param();
                if($params)
                {
                    Db::startTrans();
                    try{

                        $datas = [];
                        if($params['is_urgent'] == 1)
                        {
                            $datas = [
                                'car_start_km_urgent' => $params['car_start_km_urgent'],
                                'car_start_price_urgent' => $params['car_start_price_urgent'],
                                'car_km_price_urgent' => $params['car_km_price_urgent'],
                                'car_km_end_urgent' => $params['car_km_end_urgent'],
                            ];
                        }

                        $data = [
                            'car_number'        => $params['car_number'],
                            'car_type'          => $params['car_type'],
                            'car_level'         => $params['car_level'],
                            'car_box_length'    => $params['car_box_length'],
                            'car_box_width'     => $params['car_box_width'],
                            'car_box_height'    => $params['car_box_height'],
                            'car_box_weight'    => $params['car_box_weight'],
                            'car_tons'          => $params['car_tons'],
                            'car_start_km'      => $params['car_start_km'],
                            'car_start_price'   => $params['car_start_price'],
                            'car_km_price'      => $params['car_km_price'],
                            'car_km_end'        => $params['car_km_end'],
                            'car_express_price'     => $params['car_express_price'],
                            'car_express_km_price'  => $params['car_express_km_price'],
                            'is_urgent' => $params['is_urgent'],
                            'status'    => $params['status'],
                        ];
                        $arr = array_merge($datas,$data);
                        (new \app\common\model\Car())->editData($get['id'],$arr);
                        Db::commit();
                        return ['status'=>true,'msg'=>'操作成功'];
                    }catch (PDOException $e){
                        Db::rollback();
                        return ['status'=>false,'msg'=>'操作失败'];
                    }
                }
            }

            $this->assign('get',$get);
        }
        return $this->fetch();
    }

    /**
     * 删除
     * @param null $ids
     * @return array
     */
    public function del($ids=null)
    {
        if($ids)
        {
            $get = \app\common\model\Car::get($ids);
            if(!$get)
                return ['status'=>false,'msg'=>'没有数据'];
            Db::startTrans();
            try{
                (new \app\common\model\Car())->delData($get['id']);
                Db::commit();
                return ['status'=>true,'msg'=>'删除成功'];
            }catch (PDOException $e)
            {
                Db::rollback();
                return ['status'=>false,'msg'=>'删除失败'];
            }
        }
        return [];
    }

    //检测车牌是否重复
    public function isCarRepeat($license)
    {
        if (empty($license)) {
            return false;
        }

        $validate = Validate::make([
            'car_number'  => 'unique:car',
        ]);

        $data = [
            'car_number'  => $license,
        ];

        if (!$validate->check($data)) {
            return false;
        }

        return true;
    }

    //检测车牌
    public function isCarLicense($license)
    {
        if (empty($license)) {
           return false;
        }

        #匹配民用车牌和使馆车牌
        # 判断标准
        # 1，第一位为汉字省份缩写
        # 2，第二位为大写字母城市编码
        # 3，后面是5位仅含字母和数字的组合
        $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5}$/u";
        preg_match($regular, $license, $match);
        if (isset($match[0])) {
            return true;
        }

        return false;
    }

}