<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------
namespace app\Site\controller;

use app\common\controller\Site;
use app\common\model\AreaMallSite;
use app\common\model\AreaMallWeiPage;
use app\common\model\AreaSiteWeiPage;
use app\common\model\GoodsCat;
use app\common\model\Goods;
use app\common\model\Images;
use app\common\model\PageGoodsClass;
use Request;
use app\common\model\Template;
use app\common\model\Setting;




class Wechat extends Site
{

    private $author = [];//小程序授权信息
    private $authorType = 'b2c';//授权类型


    /**
     * 自助绑定小程序
     */
    public function edit()
    {
        $host = \request()->host();
        $this->assign('weixin_host',$host);
        $settingModel = new Setting();
        $data = $settingModel->getAll();
        $this->assign('data', $data);
        $wechat = config('thirdwx.');
        $this->assign('wechat', $wechat);
        return $this->fetch('edit');
    }

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
     *展示授权信息
     */
    public function info()
    {
        $settingModel = new Setting();
        $data = $settingModel->getAll();
        $this->assign('data', $data);
        $wechat = config('thirdwx.');
        $this->assign('wechat', $wechat);

        $host = \request()->host();
        $this->assign('weixin_host',$host);
        return $this->fetch('edit');
    }

    /**
     * 获取模板信息
     */
    public function template()
    {
        $templateModel = new AreaSiteWeiPage();
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
        $templateModel = new AreaSiteWeiPage();

        $res = [];
        if($this->getMyPermissionUserType()==self::PERMISSION_SITE) {
            $data = $templateModel->getSiteWeiPage(['site_id' => $this->siteId]);
            if(count($data) > 0){
                $list = [];
                foreach($data as $k => $info){
                    $list[$k]['field_id']   = $info['aswp_id'];
                    $list[$k]['field_type'] = $info['field_type'];
                    $list[$k]['field_list'] = unserialize($info['desc']);
                    $productCondition = unserialize($info['desc']);
                    if($info['field_type'] == 'ProductGroup'){
//                        $productList = (new Goods())->tableData([
//                            'limit' => $productCondition['maxProductNum'],
//                            'last_cat_id' => $productCondition['class_id'],
//                            'is_recommend' => 1
//                        ]);
                        $productList = (new Goods())->tableData([
                            'limit'                 => $productCondition['maxProductNum'],
                            'site_recommend_class'  => $productCondition['class_id'],
                            'marketable'            =>1
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
                }
            }
            $siteInfo = (new AreaMallSite())->siteInfo($this->siteId);
            $res['list'] = $list;
            $res['siteName'] = $siteInfo['site_name'];
            $res['siteLogo'] = $siteInfo['site_logo'];
        }else{

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
        $aswp_id    = input('param.field_id', 0);
        $sort       = input('param.sort');
        $field_type = input('param.field_type');
        $desc = serialize(input('param.'));

        $weiPageModel = new AreaSiteWeiPage();
        if($aswp_id > 0){
            //编辑
            $res = $weiPageModel->updateData($aswp_id, [
                'sort' => $sort,
                'desc' => $desc
            ]);
        }else{
            //新增
            $res = $weiPageModel->addData([
                'mall_id'    => $this->mallId,
                'site_id'    => $this->siteId,
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

    /**
     * 删除记录
     * @return array
     */
    public function delTemplate(){
        $aswp_id = input('field_id');

        $weiPageModel = new AreaSiteWeiPage();
        $weiPageModel->delData($aswp_id);

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
            $fieldInfo = (new AreaSiteWeiPage())->fieldInfo($aswpId);
            $data['field_id'] = $fieldInfo['aswp_id'];
            $data['field_type'] = $fieldInfo['field_type'];
            $data['field_list'] = unserialize($fieldInfo['desc']);
            if ($fieldInfo['field_type'] == 'ProductGroup') {
//                $productList = (new Goods())->tableData([
//                    'limit' => $data['field_list']['maxProductNum'],
//                    'last_cat_id' =>  $data['field_list']['class_id'],
//                    'is_recommend' => 1
//                ]);
                $productList = (new Goods())->tableData([
                    'limit'                 => $data['maxProductNum'],
                    'site_recommend_class'  => $data['class_id'],
                    'marketable'            =>1
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

            $this->assign('data', $data);
        }

        //所有一级分类供选择
        if(in_array($type, ['ProductGroup', 'Swiper'])) {
            $goodsCatModel = new PageGoodsClass();
            //  PageGoodsClass  GoodsCat

            $categoryList = $goodsCatModel->getAllLabel([
                'site_id' => $this->siteId,
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
        }

        return [
            'status' => true,
            'data' => $this->fetch($templateFile),
            'msg' => ''
        ];
    }
}
