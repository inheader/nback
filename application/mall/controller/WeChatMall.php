<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Mall\controller;
use app\common\controller\Mall;
use app\common\model\AreaMallSite;
use app\common\model\AreaMallWeiPage;
use app\common\model\GoodsCat;
use app\common\model\Goods;
use app\common\model\Images;
use app\common\model\PageGoodsClass;
use app\common\model\PageSiteClass;
use app\common\model\Template;
use app\common\model\Setting;

class WeChatMall extends Mall
{

    public function doEdit()
    {
        $result            = [
            'status' => false,
            'data'   => '',
            'msg'    => '保存失败',
        ];
        $settingModel = new Setting();

        if(Request::isAjax()){
            foreach(input('param.') as $k => $v) {
                $result = $settingModel->setValue($k, $v);
                //如果出错，就返回，如果是没有此参数，就默认跳过
                if (!$result['status'] && $result['data'] != 10008) {
                    return $result;
                }
            }
            $result['status'] = true;
            $result['msg']    = '保存成功';
            return $result;
        }
        return $result;
    }

    /**
     * 获取模板信息
     */
    public function template()
    {
        $templateModel = new AreaMallWeiPage();
//        if(isset($this->site_id) && $this->site_id > 0){
//            $data = $templateModel->getSiteWeiPage(['site_id' => $this->site_id]);
//        }else{
//            $data = $templateModel->getSiteWeiPage(['mall_id' => $this->mall_id]);
//        }
//
//        $this->assign('data', $data);
        return $this->fetch('template');
    }

    public function templatePhone()
    {

        $this->view->engine->layout(false);
        $templateModel = new AreaMallWeiPage();

        $res = [];
        if($this->getMyPermissionUserType()==self::PERMISSION_MALL) {
            $data = $templateModel->getMallWeiPage(['mall_id' => $this->mallId]);

            if(count($data) > 0){
                $list = [];
                foreach($data as $k => $info){
                    $list[$k]['field_id']   = $info['amwp_id'];
                    $list[$k]['field_type'] = $info['field_type'];
                    $list[$k]['field_list'] = unserialize($info['desc']);
                    $productCondition = unserialize($info['desc']);


                    if($info['field_type'] == 'ProductGroup'){

                        $productList = (new Goods())->tableData([
                            'limit'                 => $productCondition['maxProductNum'],
                            'mall_recommend_class'  => $productCondition['class_id']
                        ]);

                        $list[$k]['products_list'] = [];
                        if($productList['count'] > 0){
                            foreach($productList['data'] as $kk => $pInfo){
                                $imageUrl = '';
                                if($pInfo['image_id'] > 0){
                                    $imageIns = (new Images())->imageInfo($pInfo['image_id']);
                                    $imageUrl = $imageIns['url'];
                                }

                                $list[$k]['products_list'][$kk] = [
                                    'goodsId' => $pInfo['id'],
                                    'goodsName' => $pInfo['name'],
                                    'goodsImage' => $imageUrl,
                                    'goodsPrice' => $pInfo['price']
                                ];
                            }
                        }
                    }

                    if ($info['field_type'] == 'ShopGroup') {
                        $siteList = (new AreaMallSite())->tableData([
                            'limit'                 => $productCondition['maxSiteNum'],
                            'recommend_class'       => $productCondition['class_id']
                        ]);
                        $list[$k]['site_list'] = [];
                        if($siteList['count'] > 0){
                            foreach ($siteList['data'] as $kk => $pInfo) {
                                $list[$k]['site_list'][$kk] = [
                                    'siteId'    => $pInfo['site_id'],
                                    'siteName'  => $pInfo['site_name'],
                                    'siteLogo'  => $pInfo['site_logo'],
                                    'siteFans'  => 100
                                ];
                            }
                        }
                    }

                }
            }


            $res['list'] = $list;
        }



        $this->assign('data', $res);
        return [
            'status' => true,
            'data' => $this->fetch('phone-template'),
            'msg' => ''
        ];



    }

    /**
     * 修改&添加模板信息
     * @return array
     */
    public function saveTemplate(){

        $params = $this->request->param();

        if($params)
        {
            $amwp_id = $params['field_id'];
            $sort    = $params['sort'];
            $field_type = $params['field_type'];

            if($field_type == 'Swiper')
            {
                $linkType = isset($params['linkType']) ? $params['linkType'] : [];
                //排序需要序列化的数组
                $data  = [
                    'field_id' => $amwp_id,
                    'field_type' => $field_type,
                    'sort' => $sort,
                    'file' => !empty($params['file']) ? $params['file'] : '',
                    'imageUrl' => array_values($params['imageUrl']),
                    'linkType' => array_values($linkType),
                ];
                $data['linkKey'] = isset($params['linkKey']) ? array_values($params['linkKey']) : [];
                //序列化
                $desc = serialize($data);
            }else{
                $desc = serialize(input('param.'));
            }

            $weiPageModel = new AreaMallWeiPage();
            if($amwp_id > 0){
                //编辑
                $res = $weiPageModel->updateData($amwp_id, [
                    'sort' => $sort,
                    'desc' => $desc
                ]);
            }else{
                //新增
                $res = $weiPageModel->addData([
                    'mall_id'    => $this->mallId,
                    'field_type' => $field_type,
                    'sort' => $sort,
                    'desc' => $desc
                ]);
            }

            if ($res) {
                $return_data = array(
                    'status' => true,
                    'msg'    => '添加成功',
                    'data'   => $res,
                );
            } else {
                $return_data = array(
                    'status' => false,
                    'msg'    => '添加失败',
                    'data'   => $res,
                );
            }
            return $return_data;

        }

//        $desc = serialize(input('param.'));
//
//        $weiPageModel = new AreaMallWeiPage();
//        if($amwp_id > 0){
//            //编辑
//            $res = $weiPageModel->updateData($amwp_id, [
//                'sort' => $sort,
//                'desc' => $desc
//            ]);
//        }else{
//            //新增
//            $res = $weiPageModel->addData([
//                'mall_id'    => $this->mallId,
//                'field_type' => $field_type,
//                'sort' => $sort,
//                'desc' => $desc
//            ]);
//        }
//
//        if ($res) {
//            $return_data = array(
//                'status' => true,
//                'msg'    => '添加成功',
//                'data'   => $res,
//            );
//        } else {
//            $return_data = array(
//                'status' => false,
//                'msg'    => '添加失败',
//                'data'   => $res,
//            );
//        }
//        return $return_data;
    }

    /**
     * 删除记录
     * @return array
     */
    public function delTemplate(){
        $amwp_id = input('field_id');

        $weiPageModel = new AreaMallWeiPage();
        $weiPageModel->delData($amwp_id);

        return [
            'status' => true,
            'msg' => '删除成功',
            'data'=> []
        ];
    }

    /**
     * 获取店铺商品分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsCat(){
        $parentId = input('parent_id', 0);

        $goodsCatModel = new GoodsCat();
        $categoryList = $goodsCatModel->getAllCat(false, [
            'site_id'   => $this->siteId,
            'parent_id' => $parentId
        ]);

        $newGoodsCates = [];
        foreach($categoryList as $k => $category){
            $newGoodsCates[$k] = [
                'class_id'   => $category['id'],
                'class_name' => $category['name']
            ];
        }

        return $newGoodsCates;
    }

    /**
     * 显示各控件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fieldsInfo(){
        $aswpId = input('post.field_id', 0);
        $type   = input('post.field_type');

        $this->view->engine->layout(false);
        $templateFile = '';
        if($aswpId > 0) {
            $fieldInfo = (new AreaMallWeiPage())->where('amwp_id',$aswpId)->find();
            $data['field_id'] = $fieldInfo['amwp_id'];
            $data['field_type'] = $fieldInfo['field_type'];
            $data['field_list'] = unserialize($fieldInfo['desc']);

            if ($fieldInfo['field_type'] == 'ProductGroup') {
                $productList = (new Goods())->tableData([
                    'limit'                 => $data['maxProductNum'],
                    'mall_recommend_class'  => $data['class_id']
                ]);
                $data['products_list'] = [];
                if($productList['count'] > 0){
                    foreach ($productList['data'] as $kk => $pInfo) {
                        $imageUrl = '';
                        if ($pInfo['image_id'] > 0) {
                            $imageIns = (new Images())->imageInfo($pInfo['image_id']);
                            $imageUrl = $imageIns['url'];
                        }

                        $data['products_list'][$kk] = [
                            'goodsId' => $pInfo['id'],
                            'goodsName' => $pInfo['name'],
                            'goodsImage' => $imageUrl,
                            'goodsPrice' => $pInfo['price']
                        ];
                    }
                }
            }
            if ($fieldInfo['field_type'] == 'ShopGroup') {
                $siteList = (new AreaMallSite())->tableData([
                    'limit'                 => $data['maxSiteNum'],
                    'recommend_class'       => $data['class_id']
                ]);
                $data['site_list'] = [];
                if($siteList['count'] > 0){
                    foreach ($siteList['data'] as $kk => $pInfo) {
                        $data['site_list'][$kk] = [
                            'siteId'    => $pInfo['site_id'],
                            'siteName'  => $pInfo['site_name'],
                            'siteLogo'  => $pInfo['site_logo'],
                            'siteFans'  => 100
                        ];
                    }
                }
            }

            $this->assign('data', $data);
        }

        //商品分组
        if(in_array($type, ['ProductGroup', 'Swiper'])) {
            $goodsCatModel = new PageGoodsClass();
            $categoryList = $goodsCatModel->getAllLabel([
                'mall_id' => $this->mallId,
            ]);

            $newGoodsCates = [];
            foreach ($categoryList as $k => $category) {
                $newGoodsCates[$k] = [
                    'class_id' => $category['id'],
                    'class_name' => $category['name']
                ];
            }
            $this->assign('class_list', $newGoodsCates);
        }

        //店铺分组
        if(in_array($type, ['ShopGroup'])) {
            $siteCatModel = new PageSiteClass();
            $categoryList = $siteCatModel->getAllLabel([
                'mall_id' => $this->mallId,
            ]);

            $newGoodsCates = [];
            foreach ($categoryList as $k => $category) {
                $newGoodsCates[$k] = [
                    'class_id' => $category['id'],
                    'class_name' => $category['name']
                ];
            }
            $this->assign('class_list', $newGoodsCates);
        }

        if($type == 'ProductGroup'){
            $templateFile = 'productgroup-component';
        }elseif($type == 'Notice'){
            $templateFile = 'notice-component';
        }elseif($type == 'Swiper'){
            $templateFile = 'swiper-component';
        }elseif($type == 'Image'){
            $templateFile = 'image-component';
        }elseif($type == 'ShopGroup'){
             $templateFile = 'shopgroup-component';
        }

        return [
            'status' => true,
            'data' => $this->fetch($templateFile),
            'msg' => ''
        ];
    }
}
