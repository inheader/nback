<?php
// +----------------------------------------------------------------------
// | PxShop [ 佩祥小程序商城 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2019 https://pxjiancai.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: mark <tahsre@qq.com>
// +----------------------------------------------------------------------

namespace app\common\model;
use think\Db;
use think\model\concern\SoftDelete;
use app\common\model\GoodsImages;
use app\common\model\UserToken;
use app\common\model\GoodsCollection;
use app\common\model\Products;
use app\common\model\GoodsCat;
use app\common\model\Setting;

/**
 * 商品类型
 * Class GoodsType
 * @package app\common\model
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 20:09
 */
class Goods extends Common
{
    use SoftDelete;
    protected $deleteTime = 'isdel';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    const MARKETABLE_UP = 1; // 上架
    const MARKETABLE_DOWN = 2; // 下架
    const VIRTUAL_YES = 2; // 虚拟商品
    const VIRTUAL_NO = 1; // 普通商品


    const PRODUCT_DEFAULT_IMAGE         = 'https://ssl.picture.qingger.com/FlDu31n-lMko1GMah3LNo7X_F9fK';


    public function getMySpcStatus() {return isset($this->marketable) ? $this->marketable : 2;}
    public function getMyMallId()      { return isset($this->mall_id) ? $this->mall_id : '';}
    public function getMySiteId()      { return isset($this->site_id) ? $this->site_id : '';}
    public function getMyGoodsCode()   { return isset($this->bn) ? $this->bn : '';}
    public function getMyGoodsName() { return isset($this->name) ? $this->name : '';}
    public function getMyTiaoLou(){return isset($this->tiaolou) ? $this->tiaolou : 0;}
    public function getMyHandingFee(){return isset($this->handing_fee) ? $this->handing_fee : 0;}
    public function getMyTransportFee(){return isset($this->transport_fee) ? $this->transport_fee : 0;}



    /**
     * @param $goodsId
     * @return GoodsCommon
     */
    public function InstanceByGoodsId($goodsId) {
        return $this->where('id',$goodsId)->find();
    }



    public function getMyMainImage() {
        if(isset($this->image_id) && !empty($this->image_id)){
            $imageIns = (new Images())->InstanceByImageId($this->image_id);
            if($imageIns) return $imageIns->getMyImageUrl();
            else return self::PRODUCT_DEFAULT_IMAGE;
        }else{
            return self::PRODUCT_DEFAULT_IMAGE;
        }
    }


    public function tableData($post,$isPage=true,$authType=0)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }

        $tableWhere = $this->tableWhere($post);

        //为了效率，将商品的品牌和分类在列表不显示
        $query = $this::with('defaultImage,brand,goodsCat,goodsType')
            ->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->whereOr($tableWhere['whereOr'])
            ->where($tableWhere['wheres'])
            ->order($tableWhere['order']);

//        dump($query);
        if($isPage){
            $list = $query->paginate($limit);


            $data = $this->tableFormatIds($list->getCollection(),$authType);         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = $list->total();
        }else{
            $list = $query->select();

            $data = $this->tableFormatIds($list,$authType);         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = count($list);
        }
        $re['code'] = 0;
        $re['msg'] = '';
        $re['data'] = $data;
        return $re;
    }

    /**
     * @param $post
     * @param bool $isPage
     * @param int $authType
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableDataA($post,$isPage=true,$authType=0)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }

        $tableWhere = $this->tableWhereA($post);

        //为了效率，将商品的品牌和分类在列表不显示
        $query = $this::with('defaultImage,brand,goodsCat,goodsType')
            ->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->whereOr($tableWhere['whereOr'])
            ->where($tableWhere['wheres'])
            ->order($tableWhere['order']);

//        dump($query);
        if($isPage){
            $list = $query->paginate($limit);
            $data = $this->tableFormatIds($list->getCollection(),$authType);         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = $list->total();
        }else{
            $list = $query->select();

            $data = $this->tableFormatIds($list,$authType);         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = count($list);
        }
        $re['code'] = 0;
        $re['msg'] = '';
        $re['data'] = $data;
        return $re;
    }


    /**
     * @param $post
     * @param bool $isPage
     * @param int $authType
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableDataB($post,$isPage=true,$authType=0)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }

        $tableWhere = $this->tableWhereB($post);

        //为了效率，将商品的品牌和分类在列表不显示
        $query = $this::with('defaultImage,brand,goodsCat,goodsType')
            ->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->whereOr($tableWhere['whereOr'])
            ->where($tableWhere['wheres'])
            ->order($tableWhere['order']);

//        dump($query);
        if($isPage){
            $list = $query->paginate($limit);
            $data = $this->tableFormatIds($list->getCollection(),$authType);         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = $list->total();
        }else{
            $list = $query->select();

            $data = $this->tableFormatIds($list,$authType);         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
            $re['count'] = count($list);
        }
        $re['code'] = 0;
        $re['msg'] = '';
        $re['data'] = $data;
        return $re;
    }


    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableWhereB($post)
    {
        $where = $whereOr = [];
        $marketable = 1;
        $where[] = ['marketable', 'eq', $marketable];
     
        if (isset($post['name']) && $post['name'] != "") {
            $where[] = ['name', 'like', '%' . $post['name'] . '%'];
        }
        if (isset($post['id']) && $post['id'] != "") {
            $where[] = ['id', 'in', $post['id']];
        }
        if (isset($post['notId']) && $post['notId'] != "") {
            $where[] = ['id', 'not in', $post['notId']];
        }
        if(isset($post['is_recommend']) && $post['is_recommend'] > 0){
            $where[] = ['is_recommend', 'eq', $post['is_recommend']];
        }
        if (isset($post['warn']) && $post['warn'] == "true") {
            //店铺警戒库存，只有当权限为店铺的时候才会有数据
            $siteId = isset($post['site_id']) ? $post['site_id'] : 0;
            if(!empty($siteId)){
                $siteInfo = (new AreaMallSite())->siteInfo($siteId);
                $goods_stocks_warn = isset($siteInfo['goods_stocks_warn']) ? $siteInfo['goods_stocks_warn'] : 0;
                if(!empty($goods_stocks_warn)){
                    $where[] = ['stock', 'elt', $goods_stocks_warn];
                }else{
                    //这里说明没有设置库存报警，直接不返回数据
                    $where[] = ['site_id', 'eq', 0];
                }
            }
        }
        if(isset($post['goods_type_id'])&& $post['goods_type_id'] != ""){
            $where[] = ['goods_type_id', 'eq', $post['goods_type_id']];
        }
        if(isset($post['brand_id'])&& $post['brand_id'] != ""){
            $where[] = ['brand_id', 'eq', $post['brand_id']];
        }
        if(isset($post['bn'])&& $post['bn'] != ""){
            $where[] = ['bn', 'like', '%'.$post['bn'].'%'];
        }

        if(isset($post['last_cat_id'])&& $post['last_cat_id'] != ""){
            $where[] = ['goods_cat_id', 'eq', $post['last_cat_id']];
        }

        if (isset($post['mall_recommend_class']) && !empty($post['mall_recommend_class'])) {

            $classId = $post['mall_recommend_class'];
            //$wheres =  ['',"EXP","FIND_IN_SET(".$classId.",mall_recommend_class)"];
            $wheres =  "FIND_IN_SET($classId,mall_recommend_class)";
        }

        if (isset($post['site_recommend_class']) && !empty($post['site_recommend_class'])) {
            $classId = $post['site_recommend_class'];
            //$wheres =  ['',"EXP","FIND_IN_SET(".$classId.",site_recommend_class)"];
            $wheres =  "FIND_IN_SET($classId,site_recommend_class)";
        }

        //用户是区域权限
        if(isset($post['mall_id']) && $post['mall_id'] != ""){

            $where[] = ['mall_id', 'eq', $post['mall_id']];
            //用户是区域权限的时候，商品列表需要过滤掉积分商城的商品
            //查询区域下面非积分商城的店铺
            $siteList = (new AreaMallSite())->getMallSiteList($post['mall_id']);
            $sites = collect($siteList)->pluck('site_id')->all();
            $where[] = ['site_id', 'in', $sites];
        }

        if(isset($post['goods_cat_id'])&& $post['goods_cat_id'] != ""&&!$post['last_cat_id']){

            if($post['goods_cat_id']){
                $goodsCatModel = new GoodsCat();
                $catIds=[];
                $childCats = $goodsCatModel->field('id')->where(['parent_id'=>$post['goods_cat_id']])->select();
                if(!$childCats->isEmpty()) {
                    $catIds = array_column($childCats->toArray(), 'id');
                }

                $catIds[] = $post['goods_cat_id'];
                $whereOr[] = ['goods_cat_id', 'in', $catIds];
            }
        }

        $result['where'] = $where;
        $result['wheres'] = $wheres;
        $result['whereOr'] = $whereOr;

        $result['field'] = "*";
        $result['order'] = ['id' => 'desc'];
        return $result;

    }




    /**
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableWhereA($post)
    {
        $where = $whereOr = [];
        if (isset($post['marketable']) && $post['marketable'] != "") {
            $where[] = ['marketable', 'eq', $post['marketable']];
        }
        if (isset($post['name']) && $post['name'] != "") {
            $where[] = ['name', 'like', '%' . $post['name'] . '%'];
        }
        if (isset($post['id']) && $post['id'] != "") {
            $where[] = ['id', 'in', $post['id']];
        }
        if (isset($post['notId']) && $post['notId'] != "") {
            $where[] = ['id', 'not in', $post['notId']];
        }
        if(isset($post['is_recommend']) && $post['is_recommend'] > 0){
            $where[] = ['is_recommend', 'eq', $post['is_recommend']];
        }
        if (isset($post['warn']) && $post['warn'] == "true") {
            //店铺警戒库存，只有当权限为店铺的时候才会有数据
            $siteId = isset($post['site_id']) ? $post['site_id'] : 0;
            if(!empty($siteId)){
                $siteInfo = (new AreaMallSite())->siteInfo($siteId);
                $goods_stocks_warn = isset($siteInfo['goods_stocks_warn']) ? $siteInfo['goods_stocks_warn'] : 0;
                if(!empty($goods_stocks_warn)){
                    $where[] = ['stock', 'elt', $goods_stocks_warn];
                }else{
                    //这里说明没有设置库存报警，直接不返回数据
                    $where[] = ['site_id', 'eq', 0];
                }
            }
        }
        if(isset($post['goods_type_id'])&& $post['goods_type_id'] != ""){
            $where[] = ['goods_type_id', 'eq', $post['goods_type_id']];
        }
        if(isset($post['brand_id'])&& $post['brand_id'] != ""){
            $where[] = ['brand_id', 'eq', $post['brand_id']];
        }
        if(isset($post['bn'])&& $post['bn'] != ""){
            $where[] = ['bn', 'like', '%'.$post['bn'].'%'];
        }

        if(isset($post['last_cat_id'])&& $post['last_cat_id'] != ""){
            $where[] = ['goods_cat_id', 'eq', $post['last_cat_id']];
        }

        if (isset($post['mall_recommend_class']) && !empty($post['mall_recommend_class'])) {

            $classId = $post['mall_recommend_class'];
            //$wheres =  ['',"EXP","FIND_IN_SET(".$classId.",mall_recommend_class)"];
            $wheres =  "FIND_IN_SET($classId,mall_recommend_class)";
        }

        if (isset($post['site_recommend_class']) && !empty($post['site_recommend_class'])) {
            $classId = $post['site_recommend_class'];
            //$wheres =  ['',"EXP","FIND_IN_SET(".$classId.",site_recommend_class)"];
            $wheres =  "FIND_IN_SET($classId,site_recommend_class)";
        }

        //用户是区域权限
        if(isset($post['mall_id']) && $post['mall_id'] != ""){

            $where[] = ['mall_id', 'eq', $post['mall_id']];
            //用户是区域权限的时候，商品列表需要过滤掉积分商城的商品
            //查询区域下面非积分商城的店铺
            $siteList = (new AreaMallSite())->getMallSiteList($post['mall_id']);
            $sites = collect($siteList)->pluck('site_id')->all();
            $where[] = ['site_id', 'in', $sites];
        }else{
            if(isset($post['site_id']) && $post['site_id'] != "")
            {
                $where[] = ['site_id', 'eq', $post['site_id']];
            }
        }

        if(isset($post['goods_cat_id'])&& $post['goods_cat_id'] != ""&&!$post['last_cat_id']){

            if($post['goods_cat_id']){
                $goodsCatModel = new GoodsCat();
                $catIds=[];
                $childCats = $goodsCatModel->field('id')->where(['parent_id'=>$post['goods_cat_id']])->select();
                if(!$childCats->isEmpty()) {
                    $catIds = array_column($childCats->toArray(), 'id');
                }

                $catIds[] = $post['goods_cat_id'];
                $whereOr[] = ['goods_cat_id', 'in', $catIds];
            }
        }

        $result['where'] = $where;
        $result['wheres'] = $wheres;
        $result['whereOr'] = $whereOr;

        $result['field'] = "*";
        $result['order'] = ['id' => 'desc'];
        return $result;

    }

    /**
     * @param $post
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function tableWhere($post)
    {
        $where = $whereOr = [];



        if (isset($post['marketable']) && $post['marketable'] != "") {
            $where[] = ['marketable', 'eq', $post['marketable']];
        }
        if (isset($post['name']) && $post['name'] != "") {
            $where[] = ['name', 'like', '%' . $post['name'] . '%'];
        }
        if (isset($post['id']) && $post['id'] != "") {
            $where[] = ['id', 'in', $post['id']];
        }
        if (isset($post['notId']) && $post['notId'] != "") {
            $where[] = ['id', 'not in', $post['notId']];
        }
        if (isset($post['notInSite']) && $post['notInSite'] != "") {
            $where[] = ['site_id', 'not in', $post['notInSite']];
        }
        if (isset($post['inSite']) && $post['inSite'] != "") {
            $where[] = ['site_id', 'in', $post['inSite']];
        }
        if(isset($post['is_recommend']) && $post['is_recommend'] > 0){
            $where[] = ['is_recommend', 'eq', $post['is_recommend']];
        }
        if (isset($post['warn']) && $post['warn'] == "true") {
            //店铺警戒库存，只有当权限为店铺的时候才会有数据
            $siteId = isset($post['site_id']) ? $post['site_id'] : 0;
            if(!empty($siteId)){
                $siteInfo = (new AreaMallSite())->siteInfo($siteId);
                $goods_stocks_warn = isset($siteInfo['goods_stocks_warn']) ? $siteInfo['goods_stocks_warn'] : 0;
                if(!empty($goods_stocks_warn)){
                    $where[] = ['stock', 'elt', $goods_stocks_warn];
                }else{
                    //这里说明没有设置库存报警，直接不返回数据
                    $where[] = ['site_id', 'eq', 0];
                }
            }
        }
        if(isset($post['goods_type_id'])&& $post['goods_type_id'] != ""){
            $where[] = ['goods_type_id', 'eq', $post['goods_type_id']];
        }
        if(isset($post['brand_id'])&& $post['brand_id'] != ""){
            $where[] = ['brand_id', 'eq', $post['brand_id']];
        }
        if(isset($post['bn'])&& $post['bn'] != ""){
            $where[] = ['bn', 'like', '%'.$post['bn'].'%'];
        }

        if(isset($post['last_cat_id'])&& $post['last_cat_id'] != ""){
            $where[] = ['goods_cat_id', 'eq', $post['last_cat_id']];
        }

        if (isset($post['mall_recommend_class']) && !empty($post['mall_recommend_class'])) {

            $classId = $post['mall_recommend_class'];
            //$wheres =  ['',"EXP","FIND_IN_SET(".$classId.",mall_recommend_class)"];
            $wheres =  "FIND_IN_SET($classId,mall_recommend_class)";
        }

        if (isset($post['site_recommend_class']) && !empty($post['site_recommend_class'])) {
            $classId = $post['site_recommend_class'];
            //$wheres =  ['',"EXP","FIND_IN_SET(".$classId.",site_recommend_class)"];
            $wheres =  "FIND_IN_SET($classId,site_recommend_class)";
        }

        //用户是店铺权限
        if(isset($post['site_id']) && $post['site_id'] != ""){

            $where[] = ['site_id', 'eq', $post['site_id']];
        }
        //用户是区域权限
        if(isset($post['mall_id']) && $post['mall_id'] != ""){

            $where[] = ['mall_id', 'eq', $post['mall_id']];
            //用户是区域权限的时候，商品列表需要过滤掉积分商城的商品
            //查询区域下面非积分商城的店铺
            $siteList = (new AreaMallSite())->getMallSiteList($post['mall_id']);
            $sites = collect($siteList)->pluck('site_id')->all();
            $where[] = ['site_id', 'in', $sites];
        }

        if(isset($post['goods_cat_id'])&& $post['goods_cat_id'] != ""&&!$post['last_cat_id']){

            if($post['goods_cat_id']){
                $goodsCatModel = new GoodsCat();
                $catIds=[];
                $childCats = $goodsCatModel->field('id')->where(['parent_id'=>$post['goods_cat_id']])->select();
                if(!$childCats->isEmpty()) {
                    $catIds = array_column($childCats->toArray(), 'id');
                }

                $catIds[] = $post['goods_cat_id'];
                $whereOr[] = ['goods_cat_id', 'in', $catIds];
            }
        }

        $result['where'] = $where;
        $result['wheres'] = $wheres;
        $result['whereOr'] = $whereOr;

        $result['field'] = "*";
        $result['order'] = ['id' => 'desc'];
        return $result;
    }

    /**
     * 保存商品
     * User:wjima
     * Email:1457529125@qq.com
     * @param array $data
     * @return mixed
     */
    public function doAdd($data = [])
    {
        $result=$this->insert($data);
        if($result)
        {
            return $this->getLastInsID();
        }
        return $result;
    }

    protected function tableFormatIds($list,$authType)
    {
        foreach($list as $key => $val){
            $list[$key]['image'] = _sImage($val['image_id']);
            $list[$key]['products_id'] = _sProducts($val['id']);
            
            if($val['label_ids']){
                $list[$key]['label_ids'] = getLabel($val['label_ids']);
            }

            //区域管理员权限
            if($authType==2){
                if($val['mall_recommend_class']){
                    $list[$key]['recommend_class_ids'] = getPageClassList($val['mall_recommend_class'],$authType);
                }
            }
            //店铺管理员权限
            if($authType==3){
                if($val['site_recommend_class']){
                    $list[$key]['recommend_class_ids'] = getPageClassList($val['site_recommend_class'],$authType);
                }
            }

        }
        return $list;
    }

    /**
     * 更新商品信息
     * @param       $goods_id
     * @param array $data
     * @return false|int
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-23 19:37
     */
    public function updateGoods($goods_id,$data=[])
    {
        return $this->save($data,['id'=>$goods_id]);
    }

    /**
     * 查询商品列表信息
     * @param string $fields 查询字段
     * @param array  $where 查询条件
     * @param string $order 查询排序
     * @param int    $page 当前页码
     * @param int    $limit 每页数量
     * @return array
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-29 16:33
     */
    public function getList($fields='*',$where=[],$order='id desc',$page=1,$limit=10)
    {
        $result = [
            'status' => true,
            'data'   => [ ],
            'msg'    => ''
        ];

        if($fields != '*')
        {
            $tmpData = explode(',',$fields);
            if(in_array('products',$tmpData))
            {
                $key        = array_search('products',$tmpData);
                unset($tmpData[$key]);
            }
            $fields = implode(',',$tmpData);
        }
        $list = $this
            ->field($fields)
            ->where($where)
            ->order($order)
            ->page($page,$limit)
            ->select();
        $total = $this
            ->field($fields)
            ->where($where)
            ->count();

        if(!$list->isEmpty()){
            foreach($list as $key=>$value)
            {
                $image_url = _sImage($value['image_id']);
                $list[$key]['image_url'] = $image_url;
                $list[$key]['label_ids'] = getLabel($value['label_ids']);
                //$list[$key]['products'] = $this->products($value['id']);
            }
            $result['data'] = $list->hidden(['products'=>['isdel'],'isdel']);
        }
        $result['total'] = ceil($total/$limit);
        return $result;
    }


    /**
     * 获取商品详情
     * @param $gid
     * @param string $fields
     * @param string $token
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodsDetial($gid,$fields = '*',$token = '')
    {

        $result = [
            'status' => true,
            'data'   => [ ],
            'msg'    => ''
        ];
        $productsModel = new Products();
        $preModel = '';
        if($fields=='*'){
            $preModel = 'brand,goodsCat';
        } else {

            if (stripos($fields, 'brand_id') !== false) {
                $preModel .= 'brand,';
            }

            if (stripos($fields, 'goods_cat_id') !== false) {
                $preModel .= 'goodsCat,';
            }
            $preModel = substr($preModel, 0, -1);
        }
        $list = $this::with($preModel)->field($fields)->where([ 'id' => $gid ])->find();
        if($list) {
            //$list = $list->toArray();
            //$list['products'] = $this->products($list['id']);

            if(isset($list['image_id'])){
                $image_url         = _sImage($list['image_id']);
                $list['image_url'] = $image_url;
            }
//            if($list['products']){
//                $list['default']   = $list['products'][0];
//            }
            if(isset($list['label_ids'])){
                $list['label_ids'] = getLabel($list['label_ids']);
            }else{
                $list['label_ids'] = [];
            }
            //取默认货品
            $default_product = $productsModel->where('goods_id', $gid)->find();
            if(!$default_product){
                return error_code(10000);
            }
            $product_info = $productsModel->getProductInfo($default_product['id']);
            if(!$product_info['status']){
                return $product_info;
            }
            $list['product'] = $product_info['data'];

            if($list['spes_desc']) {
                $list['spes_desc'] = unserialize($list['spes_desc']);
            }
            //取出图片集
            $imagesModel = new GoodsImages();
            $images = $imagesModel->where(['goods_id'=>$list['id']])->select();
            $album=[];
            if(!$images->isEmpty()){
                foreach($images as $v){
                    $album[] = _sImage($v['image_id']);
                }
            }
            if(isset($list['image_url'])){
                $album[] = $list['image_url'];
            }
            sort($album);
            $list['album']=$album;

            //获取当前登录是否收藏
            $list['isfav']=$this->getFav($list['id'],$token);
            $result['data'] = $list;
        }
        return $result;
    }

    /***
     * 获取默认规格
     * @param $specDefault 默认规格
     * @param $specKey 当前规格名称
     * @param $specValue 当前规格值
     * @return string
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-31 11:32
     */
    private function getDefaultSpec($specDefault,$specKey,$specValue)
    {
        $isDefault = '2';
        foreach((array)$specDefault as $key => $val) {
            if($val['sku_name'] == $specKey && $val['sku_value'] == $specValue) {
                $isDefault = '1';
            }
        }
        return $isDefault;
    }

    /**
     * 获取商品下面所有货品
     */
    public function products($goods_id,$isPromotion=true)
    {
        $productModel = new Products();
        $pids         = $productModel->field('id')->where(['goods_id' => $goods_id])->select();
        $products     = [];

        if (!$pids->isEmpty()) {
            foreach ($pids as $key => $val) {
                $productInfo = $productModel->getProductInfo($val['id'],$isPromotion);
                if ($productInfo['status']) {
                    $products[$key] = $productInfo['data'];
                } else {
                    $products[$key] = [];
                }
            }

        }
        return $products;
    }

    /**
     * 获取goods表图片对应图片地址
     * @return $this
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-29 16:26
     */
    public function defaultImage()
    {
        return $this->hasOne('Images','id','image_id')->field('id,url')->bind([ 'image_url' => 'url' ]);
    }

    /**
     * 获取品牌信息
     * @return $this
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-31 11:43
     */
    public function brand()
    {
        return $this->hasOne('Brand','id','brand_id')->field('id,name,logo')->bind([ 'brand_name' => 'name' ]);
    }


    /**
     * 获取分类名称
     * @return $this
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-01-31 11:46
     */
    public function goodsCat()
    {
        return $this->hasOne('GoodsCat','id','goods_cat_id')->field('id,name')->bind([ 'cat_name' => 'name' ]);
    }

    /**
     * 获取类型名称
     * @return $this
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-03 8:55
     */
    public function goodsType()
    {
        return $this->hasOne('GoodsType','id','goods_type_id')->field('id,name')->bind([ 'type_name' => 'name' ]);
    }
    /**
     * 获取销售价
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-02 10:26
     */
    public function getPrice($product)
    {
        return $product['price'];
    }

    /**
     * 获取可用库存。库存机制：商品下单 总库存不变，冻结库存加1， 商品发货：冻结库存减1，总库存减1，   商品退款：总库存不变，冻结库存减1, 商品退款：总库存加1，冻结库存不变, 可销售库存：总库存-冻结库存
     * @param $product
     * @return mixed
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-02 10:30
     */
    public function getStock($product)
    {
        return $product['stock']-$product['freeze_stock'];
    }

    /**
     * 库存改变机制。库存机制：商品下单 总库存不变，冻结库存加1， 商品发货：冻结库存减1，总库存减1，   商品退款&取消订单：总库存不变，冻结库存减1, 商品退货：总库存加1，冻结库存不变, 可销售库存：总库存-冻结库存
     * @param        $product_id
     * @param string $type
     * @param int $num
     * @return array
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-02 10:34
     */
    public function changeStock($product_id, $type = 'order', $num = 0)
    {
        $result = [
            'status' => false,
            'data'   => [ ],
            'msg'    => '库存更新失败'
        ];
        if($product_id === ''){
            $result['msg'] = '货品ID不能为空';
            return $result;
        }
        $productModel = new Products();
        $where = [];
        $where[] = ['id','eq' ,$product_id];
        $where[] = ['stock','>' ,0];
        switch ($type)
        {
            case 'order': //下单
                $res=$productModel->where($where)->setInc('freeze_stock',$num);
                break;
            case 'send': //发货
                $res=$productModel->where($where)->setDec('stock',$num);
                if($res!==false){
                    $res=$productModel->where($where)->setDec('freeze_stock',$num);
                }else{
                    $result['msg'] = '库存更新失败';
                    return $result;
                }
                break;
            case 'refund': //退款
                $res=$productModel->where($where)->setDec('freeze_stock',$num);
                break;
            case 'return': //退货
                $res=$productModel->where($where)->setInc('stock',$num);
                break;
            case 'cancel': //取消订单
                $res=$productModel->where($where)->setDec('freeze_stock',$num);
                break;
            default:
                $res=$productModel->where($where)->setInc('freeze_stock',$num);
                break;
        }
        if($res!==false){
            $result['msg'] = '库存更新成功';
            $result['status'] = true;
            return $result;
        }
        return $result;
    }

    /*
     * 无数据转换
     * */
    public function getOne($goods_id,$fields='*')
    {
        $result = [
            'status' => false,
            'data'   => [ ],
            'msg'    => '商品不存在'
        ];
        $data   = $this->where([ 'id' => $goods_id ])->field($fields)->find();
        if($data) {
            $goodsImagesModel = new goodsImages();
            $images           = $goodsImagesModel->getAllImages($data->id);
            $tmp_image = [];
            if($images['status']) {
                foreach((array)$images['data'] as $key => $val) {
                    $images['data'][$key]['image_path'] = _sImage($val['image_id']);
                }
                $tmp_image[] = [
                    'goods_id' => $data['id'],
                    'image_id' => $data['image_id'],
                    'image_path' => _sImage($data['image_id']),
                ];
                $images['data'] = array_merge((array)$images['data'], (array)$tmp_image);
                $images['data'] = array_reverse($images['data']);
            }else{
                //单图
                $tmp_image[]   = [
                    'goods_id'   => $data['id'],
                    'image_id'   => $data['image_id'],
                    'image_path' => _sImage($data['image_id']),
                ];
                $images['data'] = $tmp_image;
            }
            $data['products'] = $this->products($goods_id,false);

            $data['images']   = $images['data'];
            $result['data']   = $data;
            $result['msg']    = '查询成功';
            $result['status'] = true;
        }
        return $result;
    }

    /**
     * 判断是否收藏过
     * @param int    $goods_id
     * @param string $token
     * @return string
     * User: wjima
     * Email:1457529125@qq.com
     * Date: 2018-02-03 8:36
     */
    public function getFav($goods_id = 0,$token = '')
    {
        $favRes = 'false';
        if($token) {
            $userTokenModel = new UserToken();
            $return_token   = $userTokenModel->checkToken($token);
            if($return_token['status'] == false) {
                return $favRes;
            }
            $tokenData            = $return_token['data'];
            $goodsCollectionModel = new GoodsCollection();
            $isfav                = $goodsCollectionModel->check($tokenData['user_id'],$goods_id);
            if($isfav) {
                $favRes = 'true';
            }
        }
        return $favRes;
    }

    /**
     * 删除商品
     * @param int $goods_id
     * @return array
     */
    public function delGoods($goods_id = 0)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '商品不存在'
        ];
        $goods = $this::get($goods_id);
        if (!$goods) {
            return $result;
        }

        $this->startTrans();

        $res = self::destroy($goods_id);
        if (!$res) {
            $this->rollback();
            $result['msg'] = '商品删除失败';
            return $result;
        }
        $productsModel = new Products();
        $delProduct = $productsModel->where(['goods_id' => $goods_id])->delete(true);
        if (!$delProduct) {
            $this->rollback();
            $result['msg'] = '货品删除失败';
            return $result;
        }
        //删除关联图片
        $goodsImagesModel = new GoodsImages();
        $delImages = $goodsImagesModel->delImages($goods_id);
        if (!$delImages['status']) {
            $this->rollback();
            $result['msg'] = '图片删除失败';
            return $result;
        }
        delImage($goods['image_id']);
        $this->commit();

        hook('deletegoodsafter', $goods);//删除商品后增加钩子

        $result['status'] = true;
        $result['msg'] = '删除成功';
        return $result;
    }

    /**
     * 批量上下架
     * @param $ids
     * @param string $type
     * @return static
     */
    public function batchMarketable($ids,$type='up'){

        if($type=='up'){
            $marketable = self::MARKETABLE_UP;
        }elseif($type=='down'){
            $marketable = self::MARKETABLE_DOWN;
        }
        
        $data = [
            'marketable' => $marketable,
            $type.'time' => time(),
        ];

        if($type == 'down'){
            $data['bn'] = time();
        }

        // product 表数据
        $prod = [
            'marketable' => $marketable,
            'sn' => time(),
        ];

        $productsModel = new Products();
        $productsModel->where('goods_id','in',$ids)->update($prod);

        return $this::where('id','in',$ids)->update($data);
    }

    /**
     * 获取csv数据
     * @param $post
     * @return array
     */
    public function getCsvData($post)
    {
        $result = [
            'status' => false,
            'data' => [],
            'msg' => '无可导出商品'
        ];
        $header = $this->csvHeader();
        $goodsData = $this->tableData($post, false);
        if ($goodsData['count'] > 0) {
            $tempBody = $goodsData['data'];
            $body = [];
            $i = 0;
            foreach ($tempBody as $key => $val) {
                //$product = $val->products;
                $product = $this->products($val['id'],false);
                if($val['spes_desc']){ //规格数据处理
                    $tempSpec = unserialize($val['spes_desc']);
                    $spes_desc = '';
                    foreach($tempSpec as $tsKey=>$tsVal){
                        $spes_desc = $spes_desc.'|'.$tsKey.':';
                        if(is_array($tsVal)){
                            foreach($tsVal as $sk=>$sv){
                                $spes_desc=$spes_desc.$sv.',';
                            }
                            $spes_desc = substr($spes_desc,0,-1);
                        }else{
                            $spes_desc=$spes_desc.$sv;
                        }
                    }
                    $spes_desc = substr($spes_desc,1);
                    $val['spes_desc'] = $spes_desc;
                }
                if (count($product) > 1) {//多规格

                    foreach ($product as $productKey => $productVal) {
                        $i++;
                        if ($productKey != 0) {
                            unset($val);
                        }
                        $val['sn'] = $productVal['sn'];
                        $val['price'] = $productVal['price'];
                        $val['price1'] = $productVal['price1'];
                        $val['price2'] = $productVal['price2'];
                        $val['price3'] = $productVal['price3'];
                        $val['price4'] = $productVal['price4'];
                        $val['price5'] = $productVal['price5'];
                        $val['price6'] = $productVal['price6'];
                        $val['stock'] = $productVal['stock'];
                        $val['product_spes_desc'] = $productVal['spes_desc'];
                        $val['is_defalut'] = $productVal['is_defalut'];
                        foreach ($header as $hk => $hv) {
                            if ($val[$hv['id']] && isset($hv['modify'])) {
                                if (function_exists($hv['modify'])) {
                                    $body[$i][$hk] = $hv['modify']($val[$hv['id']]);
                                }
                            } elseif ($val[$hv['id']]) {
                                $body[$i][$hk] = $val[$hv['id']];
                            } else {
                                $body[$i][$hk] = '';
                            }
                        }

                    }
                } else {//单规格
                    $i++;
                    $val['sn'] = $product[0]['sn'];
                    $val['price'] = $product[0]['price'];
                    $val['price1'] = $product[0]['price1'];
                    $val['price2'] = $product[0]['price2'];
                    $val['price3'] = $product[0]['price3'];
                    $val['price4'] = $product[0]['price4'];
                    $val['price5'] = $product[0]['price5'];
                    $val['price6'] = $product[0]['price6'];
                    $val['stock'] = $product[0]['stock'];
                    $val['product_spes_desc'] = $product[0]['spes_desc'];
                    $val['is_defalut'] = $product[0]['is_defalut'];
                    foreach ($header as $hk => $hv) {
                        if ($val[$hv['id']] && isset($hv['modify'])) {
                            if (function_exists($hv['modify'])) {
                                $body[$i][$hk] = $hv['modify']($val[$hv['id']]);
                            }
                        } elseif ($val[$hv['id']]) {
                            $body[$i][$hk] = $val[$hv['id']];
                        } else {
                            $body[$i][$hk] = '';
                        }
                    }
                }
            }
            $result['status'] = true;
            $result['msg'] = '导出成功';
            $result['data'] = $body;
            return $result;
        } else {
            //失败，导出失败
            return $result;
        }
    }
    /**
     * 设置csv header
     * @return array
     */
    public function csvHeader()
    {
        return [
            [
                'id' => 'name',
                'desc' => '商品名称',
            ],
            [
                'id' => 'bn',
                'desc' => '商品编号',
            ],
            [
                'id' => 'brief',
                'desc' => '商品简介',
            ],
            [
                'id' => 'image_url',
                'desc' => '商品主图',
            ],
            [
                'id' => 'cat_name',
                'desc' => '商品分类',
            ],
            [
                'id' => 'type_name',
                'desc' => '商品类型',
            ],
            [
                'id' => 'brand_name',
                'desc' => '品牌名称',
            ],
            [
                'id' => 'is_nomal_virtual',
                'desc' => '是否实物',
                'modify'=>'getBool'
            ],
            [
                'id' => 'marketable',
                'desc' => '是否上架',
                'modify'=>'getMarketable',
            ],
            [
                'id' => 'weight',
                'desc' => '商品重量',
            ],
            [
                'id' => 'unit',
                'desc' => '商品单位',
            ],
            [
                'id' => 'intro',
                'desc' => '商品详情',
            ],
            [
                'id' => 'spes_desc',
                'desc' => '商品规格',
            ],
            [
                'id' => 'params',
                'desc' => '商品参数',
                //'modify'=>'getParams', //todo 格式化商品参数

            ],
            [
                'id' => 'sort',
                'desc' => '商品排序',
            ],
            [
                'id' => 'is_recommend',
                'desc' => '是否上新',
                'modify'=>'getBool'
            ],
            [
                'id' => 'is_hot',
                'desc' => '是否热门',
                'modify'=>'getBool'

            ],
            [
                'id' => 'label_ids',
                'desc' => '商品标签',
                'modify'=>'getExportLabel'
            ],
            [
                'id' => 'ctime',
                'desc' => '创建时间',
                'modify'=>'getTime'
            ],
            [
                'id' => 'utime',
                'desc' => '更新时间',
                'modify'=>'getTime'
            ],
            [
                'id' => 'product_spes_desc',
                'desc' => '货品规格',
            ],
            [
                'id' => 'sn',
                'desc' => '货品编码',
            ],
            [
                'id' => 'price',
                'desc' => '货品价格',
            ],
            [
                'id' => 'price',
                'desc' => '一级价格',
            ],
            [
                'id' => 'price',
                'desc' => '二级价格',
            ],
            [
                'id' => 'price',
                'desc' => '三级价格',
            ],
            [
                'id' => 'price',
                'desc' => '四级价格',
            ],
            [
                'id' => 'price',
                'desc' => '五级价格',
            ],
            [
                'id' => 'price',
                'desc' => '六级价格',
            ],
            [
                'id' => 'stock',
                'desc' => '货品库存',
            ],
            [
                'id' => 'is_defalut',
                'desc' => '是否默认货品',
                'modify'=>'getBool'
            ]
        ];
    }

    /**
     * 商品列表页统计商品相关
     * @param array $baseFilter
     * @param array $permissionInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function staticGoods(array $baseFilter=[],$permissionInfo=[]){

        if(empty($baseFilter)) $baseFilter = [];
        $mallId = isset($baseFilter['mall_id']) ? $baseFilter['mall_id'] : 0;

        if(!empty($mallId)){
            // 用户是区域权限的时候，商品列表需要过滤掉积分商城的商品
            // 查询区域下面非积分商城的店铺
            $siteList = (new AreaMallSite())->getMallSiteList($baseFilter['mall_id']);

            $sites = collect($siteList)->pluck('site_id')->all();

            $baseFilters[] = ['site_id', 'in', $sites];
        }
        $total = $this->where($baseFilters)->where('site_id',$baseFilter['site_id'])->count('id');

        //上架
        $baseFilter['marketable']=self::MARKETABLE_UP;

        $totalMarketableUp = $this->where($baseFilter)->count('id');
        $baseFilter['marketable']=self::MARKETABLE_DOWN;
        $totalMarketableDown = $this->where($baseFilter)->count('id');

        $ret = [
            'totalGoods'            =>$total,
            'totalMarketableUp'     =>$totalMarketableUp,
            'totalMarketableDown'   =>$totalMarketableDown,
            'totalWarn'             =>0,
        ];

        //店铺警戒库存，只有当权限为店铺的时候才会有数据
        $siteId = isset($baseFilter['site_id']) ? $baseFilter['site_id'] : 0;
        if(!empty($siteId)){

            $siteInfo = (new AreaMallSite())->siteInfo($siteId);
            $goods_stocks_warn = isset($siteInfo['goods_stocks_warn']) ? $siteInfo['goods_stocks_warn'] : 0;

            if(!empty($goods_stocks_warn)){
                unset($baseFilter['marketable']);
                $baseFilters[] = ['stock','<=',$goods_stocks_warn];
                $ret['totalWarn'] = $this->where($baseFilter)->where($baseFilters)->count('id');
            }

        }


        return $ret;
    }



    /**
     * 获取重量
     * @param $product_id
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWeight($product_id)
    {
        $where[] = ['id', 'eq', $product_id];
        $goods = model('common/Products')->field('goods_id')
            ->where($where)
            ->find();
        if($goods['goods_id'] != 0){
            $wh[] = ['id', 'eq', $goods['goods_id']];

            $weight = $this->field('weight')
                ->where($wh)
                ->find();
        }
        else
        {
            $weight['weight'] = 0;
        }
        return $weight['weight']?$weight['weight']:0;
    }

}