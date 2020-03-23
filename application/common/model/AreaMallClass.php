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
 * 区域分类MODEL
 * Class AreaMallClass
 * @package app\common\model
 */
class AreaMallClass extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    const OPEN = 1; //开启
    const CLOSE = 0; //关闭


    /**
     * 店铺分类的编辑
     * @param array $classInfo
     * @param $mallId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editData($classInfo,$mallId)
    {

        $result = [ 'status' => true, 'msg' => '保存成功', 'data' => ''];

        $classId = isset($classInfo['class_id']) ? $classInfo['class_id'] : 0;
        $classIns = $this->where('class_id',$classId)->find();

        $saveInfo = [];
        if(!empty($classInfo['class_name'])){
            $saveInfo['class_name'] = $classInfo['class_name'];
        }
        if(!empty($classInfo['class_logo'])){
            $saveInfo['class_logo'] = $classInfo['class_logo'];
        }
        $saveInfo['class_sort'] = !empty($classInfo['class_sort']) ? $classInfo['class_sort'] : 0;

        $saveInfo['class_sort'] = !empty($classInfo['class_sort']) ? $classInfo['class_sort'] : 0;
        $saveInfo['is_open'] = 1;//默认开启状态


        if(!empty($classIns)){
            $this->where('class_id',$classIns['class_id'])->update($saveInfo);
        }else{
            $saveInfo['mall_id'] = $mallId;
            $this->insert($saveInfo);
        }

        return $result;

    }

    /**
     * 获取区域下面的所有分类
     * @param $mallId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getClassListForMallId($mallId)
    {
        $list = $this->where([
            'mall_id'   =>$mallId,
            'is_open'   =>self::OPEN
        ])->select();
        return collect($list)->map(function($info){
            return[
                'class_id'      =>$info['class_id'],
                'class_name'    =>$info['class_name'],
            ];
        })->all();
    }

}