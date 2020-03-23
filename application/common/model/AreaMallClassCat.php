<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/19
 * Time: 23:27
 */


namespace app\common\model;

use think\Validate;

/**
 * 快速选材
 * Class AreaMallClassCat
 * @package app\common\model
 */
class AreaMallClassCat extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    const OPEN = 1; //开启
    const CLOSE = 0; //关闭
    const PLATFORM_ID = 0;                  //平台ID
    const TOP_CLASS_PARENT_ID = 0;          //顶级分类父类ID
    const TOP_CLASS = 1;                    //顶级分类
    const SUB_CLASS = 2;                    //子分类
    const SUB_CLASS_1 = 3;                    //子分类

    public function getInfo($id)
    {
        return $this->where('id',$id)->find();
    }

    public function getThisSites($id)
    {
        $info = $this->where('id',$id)->find();
        $classId = isset($info['class_id']) ? $info['class_id']: 0;
        //查看店铺分类下面的店铺
        $siteList = (new AreaMallSite())->where('class_id',$classId)->select();

        return collect($siteList)->map(function($info){
            return $info['site_id'];
        })->all();
    }


    public function getThisSiteList($id)
    {
        $info = $this->where('id',$id)->find();
        $classId = isset($info['class_id']) ? $info['class_id']: 0;
        //查看店铺分类下面的店铺
        $siteList = (new AreaMallSite())->where('class_id',$classId)->select();

        return collect($siteList)->map(function($info){
            return [
                'site_id'=>$info['site_id'],
                'site_name'=>$info['site_name'],
            ];
        })->all();
    }

    /**
     * 更新快速选材数据
     * @param int $id
     * @param $info
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateQuickClassInfo($id=0,$info)
    {
        $ins = $this->where('id',$id)->find();
        $update = [];
        if(isset($info['classId'])){
            $update['class_id'] = $info['classId'];
        }
        if(isset($info['parentId'])){
            $update['parent_id'] = $info['parentId'];
        }
        if(isset($info['name'])){
            $update['name'] = $info['name'];
        }
        if(isset($info['sort'])){
            $update['sort'] = $info['sort'];
        }

        $update['is_open'] = 1;
        if(!empty($ins)){
            $this->where('id',$id)->update($update);
            return $id;
        }
        $this->insert($update);
        $id = $this->getLastInsID();
        return $id;
    }

    /**
     * 删除快速选材分类
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deleteClassId($id)
    {
        //获取这个分类下面的子分类
        $childList = $this->where('parent_id',$id)->select();
        $childIds = collect($childList)->pluck('id')->all();

        $deleteIds = array_merge($childIds,[$id]);

        //删除设置的商品
        (new AreaMallClassCatGoodsMapping())->deleteGoodsIdsForClassId($deleteIds);
        //删除设置的标签规格
        (new AreaMallClassCatBrand())->deleteTagsIdsForClassId($deleteIds);
        //删除分类
        $this->whereIn('id',$deleteIds)->delete();

        return true;
    }


    /**
     * @param $classId
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getParentList($classId)
    {
        $data = $this->field('id, class_id, parent_id, name, sort')
            ->where('class_id',$classId)
            ->where('parent_id',0)
            ->order([ 'sort' => 'asc'])
            ->select();

//        $temp = $this->tree($data);

//        dump($temp);
        return $data;
    }


    function tree($arr,$pid='0') {
        $tree = array();
        foreach($arr as $row){
            if($row['parent_id']==$pid){
                $tmp = self::tree($arr,$row['id']);
                if($tmp){
                    $row['children']=$tmp;
                }
                $tree[]=$row;
            }
        }
        return $tree;
    }

    /**
     * @param $classId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($classId)
    {
        $data = $this->field('id, class_id, parent_id, name, sort')
            ->where('class_id',$classId)
            ->order([ 'sort' => 'asc'])
            ->select();

//        $return_data = $this->getTree($data);
        $dat = $this->unlimitedForLevel ($data, $html = '--', $pid = 0, $level = 0);
//        dump($dat);
        return $dat;
    }


    Public function unlimitedForLevel ($cate, $html = '--', $pid = 0, $level = 0) {
        $arr = array();
        foreach ($cate as $k => $v) {
            if ($v['parent_id'] == $pid) {
                $v['level'] = $level + 1;
                $v['html']  = str_repeat($html, $level);
                $arr[] = $v;
                $arr = array_merge($arr, self::unlimitedForLevel($cate, $html, $v['id'], $level + 1));
            }
        }
        return $arr;
    }

    /**
     * 转换成树状
     * @param $data
     * @return array
     */
    protected function getTree($data)
    {
        $new_data = array();
        foreach($data as $k=>$v)
        {
//            amcc_id, class_id, parent_id, name, sort
            if($v['parent_id'] == self::TOP_CLASS_PARENT_ID)
            {
                $new_data[$v['id']]['id'] = $v['id'];
                $new_data[$v['id']]['name_1'] = $v['name'];
                $new_data[$v['id']]['name_2'] = '';
                $new_data[$v['id']]['name_3'] = '';
                $new_data[$v['id']]['name_id_2'] = '';
                $new_data[$v['id']]['name_id_3'] = '';
                $new_data[$v['id']]['sort'] = $v['sort'];
                $new_data[$v['id']]['operating'] = $this->getOperating($v['id'], self::TOP_CLASS);
            }
            else
            {

                $new_data[$v['parent_id']]['subclass'][] = array(
                    'id' => $v['id'],
                    'name_1' => '',
                    'name_2' => $v['name'],
                    'name_id_2' => $v['id'],
                    'sort' => $v['sort'],
                    'operating' => $this->getOperating($v['id'], self::SUB_CLASS),
                );
            }
        }

        $return_data = array();
        foreach($new_data as $v)
        {
            $return_data[] = array(
                'id' => $v['id'],
                'name_1' => $v['name_1'],
                'name_2' => $v['name_2'],
                'name_id_2' => $v['name_id_2'],
                'sort' => $v['sort'],
                'operating' => $v['operating']
            );
            if(isset($v['subclass']) && count($v['subclass']) > 0)
            {
                foreach($v['subclass'] as $vv)
                {
                    $return_data[] = array(
                        'id' => $vv['id'],
                        'name_1' => $vv['name_1'],
                        'name_2' => $vv['name_2'],
                        'name_id_2' => $vv['name_id_2'],
                        'sort' => $vv['sort'],
                        'operating' => $vv['operating'],
                    );
                }
            }
        }

        return $return_data;
    }

    /**
     * 生成操作按钮
     * @param $id
     * @param int $type
     * @return string
     */
    protected function getOperating($id, $type = self::TOP_CLASS)
    {
        $html = '';
        if($type == self::TOP_CLASS)
        {
            $html .= '<a class="layui-btn layui-btn-xs edit-class" data-id="'.$id.'">编辑</a>';
            $html .= '<a class="layui-btn layui-btn-danger layui-btn-xs del-class" data-id="'.$id.'">删除</a>';
        }
        elseif($type == self::SUB_CLASS)
        {
            $html .= '<a class="layui-btn layui-btn-xs add-class-cat" data-id="'.$id.'">添加子分类</a>';
            $html .= '<a class="layui-btn layui-btn-xs add-goods" data-id="'.$id.'">添加商品</a>';
            $html .= '<a class="layui-btn layui-btn-xs edit-class" data-id="'.$id.'">编辑</a>';
            $html .= '<a class="layui-btn layui-btn-danger layui-btn-xs del-class" data-id="'.$id.'">删除</a>';
        }
        return $html;
    }

}