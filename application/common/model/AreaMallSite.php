<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/13
 * Time: 21:44
 */

namespace app\common\model;
use think\Validate;

class AreaMallSite extends Common
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';
    protected $updateTime = 'utime';

    protected $rule =   [
        'site_name'         =>  'require|max:50',
        'site_logo'         =>  'require',
        'site_back_image'   =>  'require',
    ];

    protected $msg  =   [
        'site_name.require'         =>  '请输入店铺名称',
        'site_name.max'             =>  '店铺名称不超过50个字符',
        'site_logo.require'         =>  '请上传店铺logo',
        'site_back_image.require'   =>  '请上传店铺背景图',
    ];


    public function getMyMallId()       { return isset($this->mall_id) ? $this->mall_id : 0; }
    public function getMySiteId()       { return isset($this->site_id) ? $this->site_id : 0; }
    public function getMySiteName()     { return isset($this->site_name) ? $this->site_name : '';}
    public function getMySiteMobile()   { return isset($this->site_mobile) ? $this->site_mobile : '';}
    public function getMySiteMobile1()   { return isset($this->site_mobile_1) ? $this->site_mobile_1 : '';}
    public function getMySiteType()     { return isset($this->site_type) ? $this->site_type : 1;}
    public function getMySiteLogo()     { return isset($this->site_logo) ? $this->site_logo : self::DEFAULT_SITE_LOG;}
    public function getMySiteBackImage(){ return isset($this->site_back_image) ? $this->site_back_image : '';}
    public function getMySiteDesc()     { return isset($this->site_desc) ? $this->site_desc : self::DEFAULT_SITE_DESC;}//店铺简介
    public function getMyClassId()      { return isset($this->class_id) ? $this->class_id : 0;}
    public function getMyShippingFee()  { return isset($this->shipping_fee) ? $this->shipping_fee : 0;}
    public function getMyShippingTransId(){ return isset($this->shipping_trans_id) ? $this->shipping_trans_id : 0;}
    public function getMyReturnShippingFee()    { return isset($this->return_shipping_fee) ? $this->return_shipping_fee : 0;}
    public function getMyOrderShippingFee()    { return isset($this->order_shipping_fee) ? $this->order_shipping_fee : 0;}
    public function getMyFreeShippingFee()    { return isset($this->free_shipping_fee) ? $this->free_shipping_fee : 0;}
    public function getMySiteShippingFeeType() { return isset($this->site_shipping_fee_type) ? $this->site_shipping_fee_type : 'mall';}//店铺配送方式
    public function getMySiteTrousers() { return isset($this->trousers) ? $this->trousers : 0;}//店铺配送方式


    /**
     * @param $siteId
     * @return AreaMallSite
     */
    public function InstanceBySiteId($siteId){
        return $this->where('site_id', $siteId)->find();
    }

    /**
     * @param $post
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function tableData($post)
    {
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);


        if (isset($post['recommend_class']) && !empty($post['recommend_class'])) {
            $classId = $post['recommend_class'];
            $where[] = " FIND_IN_SET('.$classId.'recommend_class) ";
        }

        $list = $this->field($tableWhere['field'])
            ->where($tableWhere['where'])
            ->order("site_sort",'desc')
            ->paginate($limit);
        //返回的数据格式化，并渲染成table所需要的最终的显示数据类型
//        $data = $this->tableFormat($list->getCollection());
        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $list->getCollection();

        return $re;
    }


    /**
     *  门店添加
     * User:tianyu
     * @param array $data
     * @return array
     */
    public function addData( $data= [] )
    {
        $result = ['status' => true, 'msg' => '保存成功','data' => ''];

        $validate = new Validate($this->rule,$this->msg);

        if ( !$validate->check($data) )
        {
            $result['status'] = false;
            $result['msg'] = $validate->getError();
        } else {
            if (!$this->allowField(true)->save($data)) {
                $result['status'] = false;
                $result['msg'] = '保存失败';
            }
        }

        return $result;
    }


    /**
     * @param array $siteInfo
     * @param $canEditSiteClass //查看是店铺还是区域的编辑 0店铺 1区域
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editData( $siteInfo= [] ,$canEditSiteClass)
    {
        $result = [ 'status' => true, 'msg' => '保存成功', 'data' => $canEditSiteClass];
        $siteId = isset($siteInfo['site_id']) ?$siteInfo['site_id'] : 0;

        $siteIns = $this->where('site_id',$siteId)->find();
        $saveInfo = [
            'site_mobile'       => $siteInfo['site_mobile'],
            'site_mobile_1'       => $siteInfo['site_mobile_1'],
            'site_desc'         => $siteInfo['site_desc'],
            'site_back_image'   => $siteInfo['site_back_image'],
            'return_shipping_fee'   => $siteInfo['return_shipping_fee'],
            'site_noun'             => $siteInfo['site_noun'],
            'free_shipping_fee'   => $siteInfo['free_shipping_fee'],
            'order_shipping_fee'   => $siteInfo['order_shipping_fee'],
            'goods_stocks_warn'   => $siteInfo['goods_stocks_warn'],
            
            ];

        if(!empty($siteInfo['site_name'])){
            $saveInfo['site_name'] = $siteInfo['site_name'];
        }

        // 仓库标识
        if(!empty($siteInfo['trousers'])){
            $saveInfo['trousers'] = $siteInfo['trousers'];
        }

        // 判断是否官方物流
        if(!empty($siteInfo['site_shipping_fee_type'])){
            $saveInfo['site_shipping_fee_type'] = $siteInfo['site_shipping_fee_type'];
        }
        if(!empty($siteInfo['class_id'])){
            $saveInfo['class_id'] = $siteInfo['class_id'];
        }
        // 判断logo
        if(!empty($siteInfo['site_logo'])){
            $saveInfo['site_logo'] = $siteInfo['site_logo'];
        }

        if(!empty($siteIns)){
            $this->where('site_id',$siteId)->update($saveInfo);
        }else{
            $saveInfo['site_type'] = $siteInfo['site_type'];
            $saveInfo['mall_id'] = $siteInfo['mall_id'];
            // 如果是积分商城，则一个区域只能建一个积分商城，需要在这里限制一下
            $siteInfo = $this->getPointSiteForMall($siteInfo['mall_id']);
            // if(!empty($siteInfo)){
            //     return [ 'status' => false, 'msg' => '一个区域积分商城只能建一个', 'data' => $canEditSiteClass];
            // }
            $this->insert($saveInfo);
        }
        return $result;
    }

    /**
     * 获取区域的积分商城
     * @param $mallId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPointSiteForMall($mallId)
    {
        $siteInfo = $this
            ->where('mall_id',$mallId)
            ->where('site_type',0)
            ->find();
        return !empty($siteInfo) ? $siteInfo : [];
    }

    /**
     * 更新店铺状态
     * @param $siteId
     * @param $status
     * @return AreaMallSite
     */
    public function updateStatus($siteId,$status)
    {
        return $this->where('site_id',$siteId)
            ->update([
                'site_status'=>$status
            ]);
    }


    /**
     * 更新店铺数据
     * @param $siteId
     * @param $data
     * @return false|int
     */
    public function updateIns($siteId,$data)
    {
        return $this->save($data,['site_id'=>$siteId]);
    }

    /**
     * 设置店铺排序
     * @param $siteId
     * @param $sort
     * @return AreaMallSite
     */
    public function editSiteSort($siteId,$sort)
    {
        return $this->where('site_id',$siteId)
            ->update([
                'site_sort'=>$sort
            ]);
    }

    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach( $list as $val )
        {
            $val['logo'] = _sImage($val['logo']);
            $val['area'] = get_area($val['area_id']);
            $val['ctime'] = getTime($val['ctime']);
            $val['utime'] = getTime($val['utime']);
        }

        return $list;
    }

    /**
     * 获取指定店铺信息
     * @param $siteId
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function siteInfo($siteId)
    {
        return $this->where([
            'site_id'=>$siteId
        ])->find();
    }

    /**
     * 获取区域下的店铺列表
     * @param $mallId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMallSiteList($mallId)
    {
        $list = $this->where([
            'mall_id'=>$mallId,
        ])->where('site_type','<>',0)
            ->order('site_sort','desc')
            ->select();
        return collect($list)->map(function($info){
            return[
                'site_id'   =>$info['site_id'],
                'site_name' =>$info['site_name']
            ];
        })->all();
    }


}