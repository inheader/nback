<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\common\model;
class PageSiteClass extends Common
{
    /**
     * 保存label数据
     * @param $data ['ids'=>'模型主键id数组','label'=>'标签数组','model'=>'打标签模型']
     * @param $userWhere //用户权限
     * @param $authTape
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addData($data,$userWhere,$authTape)
    {
        $result = [
            'status' => false,
            'data'   => [],
            'msg'    => '参数丢失',
        ];
        if (!isset($data['ids'])) {
            return $result;
        }
        if (!isset($data['label'])) {
            $result['msg'] = '请先选择店铺组';
            return $result;
        }
        $labels = $ids = [];
        foreach ($data['label'] as $key => $val) {
            $labels[$key]['name']  = $val['text'];

            //区域管理员
            if($authTape==2){
                $labels[$key]['mall_id'] = $userWhere['mall_id'];
            }

            $label = self::where($labels[$key])->find();
            $labels[$key]['style'] = $val['style'];
            if ($label) {
                //如果此标签已经有了，则更新标签的颜色就可以了
                self::where('id', 'eq', $label['id'])->update($labels[$key]);

                $labels[$key]['id'] = $label['id'];
            } else {
                //插入
                $labels[$key]['id'] = $this->insert($labels[$key], false, true);
            }
            $ids[] = $labels[$key]['id'];
        }

        //将标签打在商品上
        $model = model($data['model']);

        $dataLabels = $model::where('site_id', 'in', $data['ids'])
            ->field('site_id,recommend_class')->select();


        if (!$dataLabels->isEmpty()) {
            foreach ($dataLabels as $key => $val) {
                $label_ids          = [];
                $label_ids          = explode(',', $val['recommend_class']);
                $label_ids          = array_filter($label_ids);
                $label_ids          = array_unique(array_merge((array)$ids, (array)$label_ids));
                $udata['recommend_class'] = implode(',', $label_ids);
                $res                = $model::where('site_id', 'eq', $val['site_id'])->update($udata);
            }
        }
        if ($res !== false) {
            $result = [
                'status' => true,
                'data'   => [],
                'msg'    => '操作成功',
            ];
            return $result;
        }
        return $result;
    }

    /**
     * 获取所有标签
     * @param $userWhere
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllLabel($userWhere)
    {
        $list = $this->where($userWhere)->select();
        if(!empty($list)){
            return $list->toArray();
        }

//        if (!$this->select()->isEmpty()) {
//            return $this->select()->toArray();
//        }
        return [];
    }

    /**
     * 根据id获取名称
     * @param string $names 关联商品组名称
     * @return array
     */
    public function getIdsByName($names = '', $isForce = false)
    {
        $label      = [];
        $labelIds   = '';
        $labelArray = explode(',', $names);
        if ($labelArray) {
            foreach ($labelArray as $key => $val) {
                if ($val) {
                    $labelSql = $this->field('id')->where('name', 'like', '%' . $val . '%');

                    $id = $labelSql->find();
                    if (!$id && $isForce) {
                        $iData['name']      = $val;
                        $this->save($iData);
                        $label_id = $this->getLastInsID();
                        $id['id'] = $label_id;
                    }
                    $label[] = $id;
                }
            }
        }
        if ($label) {
            $ids      = array_column($label, 'id');
            $labelIds = implode(',', $ids);
        }
        return $labelIds;
    }

    /**
     * 获取所有选中数据的标签
     * @param $ids
     * @param $model_name
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllSelectLabel($ids, $model_name)
    {
        $model      = model($model_name);
        $filter[]   = ['site_id', 'in', $ids];

        $dataLabels = $model::where($filter)->field('site_id,recommend_class')->select();
        $labels     = [];
        if (!$dataLabels->isEmpty()) {
            foreach ($dataLabels->toArray() as $key => $val) {
                $label_ids = explode(',', $val['recommend_class']);
                $labels    = array_merge((array)$label_ids, (array)$labels);
            }
            $label_id = array_unique(array_filter($labels));
            if (!$label_id) {
                return [];
            }
            $labels = self::where('id', 'in', implode(',', $label_id))->select();
            return $labels->toArray();
        } else {
            return [];
        }
    }


    public function delData($data,$userWhere)
    {
        $result = [
            'status' => false,
            'data'   => [],
            'msg'    => '参数丢失',
        ];
        if (!isset($data['ids'])) {
            return $result;
        }
        $labels = $ids = [];
        foreach ((array)$data['label'] as $key => $val) {

            //区域管理员
            $labels[$key]['mall_id'] = $userWhere['mall_id'];

            $labels[$key]['name']  = $val['text'];
            $labels[$key]['style'] = $val['style'];
            $label = self::where($labels[$key])->find();
            if ($label) {
                $labels[$key]['id'] = $label['id'];
                $ids[]              = $labels[$key]['id'];
            }
        }

        $model = model($data['model']);
        $dataLabels = $model::where('site_id', 'in', $data['ids'])
            ->field('site_id,recommend_class')->select();

        if (!$dataLabels->isEmpty()) {
            foreach ($dataLabels as $key => $val) {
                $label_ids = explode(',', $val['recommend_class']);
                if ($label_ids) {
                    $label_ids = array_filter($label_ids);
                }
                $label_ids          = array_unique(array_intersect($ids, $label_ids));
                $udata['recommend_class'] = implode(',', $label_ids);
                $res                = $model::where('site_id', 'eq', $val['site_id'])->update($udata);
            }
        }
        if ($res !== false) {
            $result = [
                'status' => true,
                'data'   => [],
                'msg'    => '操作成功',
            ];
            return $result;
        }
        return $result;
    }
}
