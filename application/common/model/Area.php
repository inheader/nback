<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;

use think\facade\Cache;

/**
 * 地区模型
 * Class Area
 * @package app\common\model
 * @author keinx
 */
class Area extends Common
{

    const PROVINCE_DEPTH = 1;
    const CITY_DEPTH = 2;
    const COUNTY_DEPTH = 3;
    const FOUR_DEPTH = 4;
    const PROVINCE_PARENT_ID = 0;           //根节点

    public function getMyAreaName() { return isset($this->name) ? $this->name :'';}

    /**
     * 根据省市区名称获取省市区ID
     * @param $provinceName
     * @param $cityName
     * @param $areaName
     * @return array
     * @throws \App\Exceptions\ECException
     */
    public function transformationArea($provinceName,$cityName,$areaName)
    {
        //获取省份ID
        $provinceIns = $this->getCheckAreaIns($provinceName,1,0);
        //获取城市ID
        $cityIns = $this->getCheckAreaIns($cityName,2,$provinceIns->id);
        //获取区县ID
        $areaIns = $this->getCheckAreaIns($areaName,3,$cityIns->id);
        return[
            'provinceId'    => $provinceIns->id,
            'cityId'        => $cityIns->id,
            'areaId'        => $areaIns->id,
        ];
    }

        /**
     * 根据地址名称返回地址ID，并更新地址名称
     * @param $areaNewName
     * @param $areaDeep
     * @param $parentId
     * @return Area
     */
    private function getCheckAreaIns($areaNewName, $areaDeep, $parentId)
    {
        $areaName = $this->getSelectName($areaNewName);
        /** @var Area $areaIns */
        $areaIns = $this->where([
            'parent_id' => $parentId,
            'depth'     => $areaDeep,
        ])->where('name','like',$areaName.'%')
            ->find();
        if(!empty($areaIns)){
            if($areaIns->getMyAreaName()!=$areaNewName){
                $areaIns->updateMyAreaName($areaNewName);
            }
        }else{
            $areaIns = $this->addDateArea($areaNewName,$areaDeep,$parentId);
        }
        return $areaIns;
    }


    /**
     * 返回需要搜索的地址字符串
     * @param $name
     * @return string
     */
    private function getSelectName($name)
    {
        $length = mb_strlen($name,"utf-8");
        if($length>2){
            return mb_substr($name,0,2);
        }
        return $name;
    }


    /**
     * 更新地址名称
     * @param $areaName
     * @return $this
     */
    public function updateMyAreaName($areaName){
        $this['name'] = $areaName;
        $this->save();
        return $this;
    }


    /**
     * 添加地址表
     * @param $areaName
     * @param $areaDeep
     * @param $parentId
     * @return Area
     */
    public function addDateArea($areaName,$areaDeep,$parentId){
        $areaIns = new Area();
        $areaIns['name'] = $areaName;
        $areaIns['parent_id'] = $parentId;
        $areaIns['depth'] = $areaDeep;
        $areaIns->save();
        return $areaIns;
    }


    /**
     * 根据地址ID，获取地址实例
     * @param $areaId
     * @return Area
     */
    public function getCacheAreaInsForId($areaId){
        return $this->where('id',$areaId)->find();
    }

    /**
     * 根据字段查找对应值
     * @param $areaId
     * @return Area
     */
    public function getCacheAreaInsForValue($areaId,$value){
        return $this->where('id',$areaId)->value($value);
    }

    


    /**
     * 指定region id的下级信息
     * @param $areaId
     * @return array|\PDOStatement|string|\think\Collection 所有地区数据数组
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllChild($areaId)
    {
        return $this->where([
            'parent_id' => intval($areaId),
        ])->select();
    }


    /**
     * 获取地区最后一级ID
     * todo:给微信使用
     * @param $countyName
     * @param $cityName
     * @param $provinceName
     * @param $postalCode
     * @return int|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getThreeAreaId($countyName, $cityName, $provinceName, $postalCode)
    {
        $county = $this->where('name', 'eq', $countyName)
            ->find();
        if ($county) {
            $id = $county['id'];
        } else {
            $city = $this->where('name', 'eq', $cityName)
                ->find();
            if ($city) {
                //创建区域
                $county_data['parent_id']   = $city['id'];
                $county_data['depth']       = self::COUNTY_DEPTH;
                $county_data['name']        = $countyName;
                $county_data['postal_code'] = $postalCode;
                $id                         = $this->insertGetId($county_data);
            } else {
                $province = $this->where('name', 'eq', $provinceName)
                    ->find();
                if ($province) {
                    //创建城市
                    $city_data['parent_id'] = $province['id'];
                    $city_data['depth']     = self::CITY_DEPTH;
                    $city_data['name']      = $cityName;
                    $city_id                = $this->insertGetId($city_data);

                    //创建区域
                    $county_data['parent_id']   = $city_id;
                    $county_data['depth']       = self::COUNTY_DEPTH;
                    $county_data['name']        = $countyName;
                    $county_data['postal_code'] = $postalCode;
                    $id                         = $this->insertGetId($county_data);
                } else {
                    //创建省份
                    $province_data['parent_id'] = self::PROVINCE_PARENT_ID;
                    $province_data['depth']     = self::PROVINCE_DEPTH;
                    $province_data['name']      = $provinceName;
                    $province_id                = $this->insertGetId($province_data);

                    //创建城市
                    $city_data['parent_id'] = $province_id;
                    $city_data['depth']     = self::CITY_DEPTH;
                    $city_data['name']      = $cityName;
                    $city_id                = $this->insertGetId($city_data);

                    //创建区域
                    $county_data['parent_id']   = $city_id;
                    $county_data['depth']       = self::COUNTY_DEPTH;
                    $county_data['name']        = $countyName;
                    $county_data['postal_code'] = $postalCode;
                    $id                         = $this->insertGetId($county_data);
                }
            }
        }
        return $id;
    }


    /**
     * 获取地区全部名称
     * @param $area_id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllName($area_id)
    {
        if ($area_id) {
            $data = array();
            $this->recursive($area_id, $data);
            $result = $this->structuralTransformation($data);
            $name   = '';
            for ($i = 1; $i <= count($result); $i++) {
                $name .= $result[$i] . '';
            }
            $name = rtrim($name, "");
        } else {
            $name = '';
        }
        return $name;
    }


    /**
     * 区域递归查询
     * @param $area_id
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recursive($area_id, &$data = array())
    {
        $info   = $this->where('id', 'eq', $area_id)
            ->find();
        $data[] = array('depth' => $info['depth'], 'name' => $info['name']);
        if ($info['depth'] != self::PROVINCE_DEPTH) {
            $this->recursive($info['parent_id'], $data);
        }
    }


    /**
     * 地区结果转换
     * @param $data
     * @return array
     */
    public function structuralTransformation($data)
    {
        $new_data = array();
        foreach ($data as $k => $v) {
            $new_data[$v['depth']] = $v['name'];
        }
        return $new_data;
    }


    /**
     * 获取地区列表
     * @param $type
     * @param $id
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAreaList($type, $id)
    {
        switch ($type) {
            case 'province':
                $depth = self::PROVINCE_DEPTH;
                break;
            case 'city':
                $depth = self::CITY_DEPTH;
                break;
            case 'area':
                $depth = self::COUNTY_DEPTH;
                break;
            case 'four':
                $depth = self::FOUR_DEPTH;
                break;
            default:
                $depth = self::PROVINCE_DEPTH;
                break;
        }

        $data = $this->field('name, id')
            ->where('depth', 'eq', $depth)
            ->where('parent_id', 'eq', $id)
            ->select();
        return $data;
    }


    /**
     * 添加地区
     * @param $data
     * @return int|string
     */
    public function add($data)
    {
        return $this->insert($data);
    }


    /**
     * 获取详情
     * @param $id
     * @return null|static
     * @throws \think\exception\DbException
     */
    public function getAreaInfo($id)
    {
        return $this->get($id);
    }


    /**
     * 编辑存储
     * @param $id
     * @param $data
     * @return static
     */
    public function edit($id, $data)
    {
        return $this->where('id', 'eq', $id)
            ->update($data);
    }


    /**
     * 删除地区
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del($id)
    {
        $is_parent = $this->where('parent_id', 'eq', $id)->find();
        if ($is_parent) {
            $result = array(
                'status' => false,
                'msg'    => '该地区下存在关联地区，无法删除',
                'data'   => array(),
            );
        } else {
            $res = $this->destroy($id);
            if ($res) {
                $result = array(
                    'status' => true,
                    'msg'    => '删除成功',
                    'data'   => array(),
                );
            } else {
                $result = array(
                    'status' => false,
                    'msg'    => '删除失败',
                    'data'   => array(),
                );
            }
        }
        return $result;
    }

    /**
     * 根据id来返回省市区信息，如果没有查到，就返回省的列表
     * @param int $id 省市区id，不传为直接取省的信息
     * @return array
     */
    public function getArea($id = 0)
    {
        $data = $this->getParentArea($id);
        //如果没有找到地区，那么就返回一级的省列表
        if (!$data) {
            $data[0]['list'] = $this->field('id,name,parent_id')->where(array('parent_id' => self::PROVINCE_PARENT_ID))->select();
        }
        return $data;
    }

    /**
     * 递归取得父节点信息
     * @author sin
     * @param $id
     * @return array
     */
    public function getParentArea($id)
    {
        $data['info'] = $this->field('id,name,parent_id')->where(array('id' => $id))->find();
        if ($data['info']) {
            $data['list'] = $this->field('id,name,parent_id')->where(array('parent_id' => $data['info']['parent_id']))->select();
            if ($data['info']['parent_id'] != self::PROVINCE_PARENT_ID) {
                //上面还有节点
                $pdata = $this->getParentArea($data['info']['parent_id']);
                if ($pdata) {
                    $pdata[] = $data;
                }
            } else {
                $pdata[] = $data;
            }
        } else {
            return [];
        }
        return $pdata;
    }

    // 获取省
    public function getProvince(){
        $data = $this->where(['parent_id' => 0,'depth'=>1])->select();
        return $data;
    }



    /**
     * 获取所有省市区信息
     * @return array
     */
    public function getTreeArea($checked = [])
    {
        //$this->delAreaCache();
        $area_tree = Cache::get('area_tree');
        if ($area_tree) {
            $list = json_decode($area_tree, true);
        } else {
            $list = $this->where([])->select()->toArray();
            Cache::set('area_tree', json_encode($list));
        }
        $tree = $this->resolve2($list, 0, $checked);
        return $tree;
    }


    private function resolve2($list, $pid = 0, $checked = [])
    {
        $manages = array();
        $i       = 0;
        foreach ($list as $row) {
            if ($row['parent_id'] == $pid) {
                $row['checkboxValue'] = $row['id'];
                if ($checked && in_array($row['id'], $checked)) {
                    $row['checked'] = true;

                } else {
                    $row['checked'] = false;
                }
                $manages[$i] = $row;
                $children = $this->resolve2($list, $row['id'], $checked);
                $children && $manages[$i]['children'] = $children;
                $i++;
            }
        }
        return $manages;
    }

    /**
     * 删除地区树缓存
     */
    public function delAreaCache()
    {
        Cache::set('area_tree', '');
    }


    /**
     * 根据输入的查询条件，返回所需要的where
     * @author sin
     * @param $post
     * @return mixed
     */
    protected function tableWhere($post)
    {
        $where = [];
        if (isset($post['parent_id']) && $post['parent_id'] !== '') {
            $where[] = ['parent_id', 'eq', $post['parent_id']];
        }
        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = [];
        return $result;
    }

    protected function tableFormat($list)
    {
        foreach ($list as $key => $val) {
            if ($val) {
                $child = $this->where([
                    'parent_id' => intval($val['id']),
                ])->count('id');
                if ($child > 0) {
                    $list[$key]['child'] = 'true';
                } else {
                    $list[$key]['child'] = 'false';
                }
            }
        }
        return $list;
    }

    /**
     * 获取区域的三级分类(meiyong)
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function areaTreeList()
    {
        return $this->where([
            'parent_id'=>0,
            'depth'=>1
        ])
            ->get()->map(function($provinceIns){
                $childs = $this->where([
                    'parent_id'=>$provinceIns['id'],
                    'depth'=>2
                ])->get();
                return[
                    'id'  =>$provinceIns['id'],
                    'name'  =>$provinceIns['name'],
                    'deep'  =>$provinceIns['deep'],
                    'childs'=>$childs->map(function($cityIns){
                        $childs = $this->where([
                            'parent_id'=>$cityIns['id'],
                            'depth'=>3
                        ])->get();
                        return[
                            'id'  =>$cityIns['id'],
                            'name'  =>$cityIns['name'],
                            'deep'  =>$cityIns['deep'],
                            'childs'=>$childs->map(function($areaIns){
                                return[
                                    'id'  =>$areaIns['id'],
                                    'name'  =>$areaIns['name'],
                                    'deep'  =>$areaIns['deep'],
                                ];
                            })->values()->all()
                        ];
                    })->values()->all()
                ];
            })->values()->all();
    }

}