<?php
/**
 * Created by PhpStorm.
 * User: inheader
 * Date: 2018/11/20
 * Time: 22:57
 */
namespace app\common\model;

use think\Db;

/**
 * 店铺会员表
 * Class SiteMember
 * @package app\common\model
 */
class SiteMember extends Common
{
    const DISPLAY_SHOW = 1;
    const DISPLAY_HIDE = 2;

    protected $autoWriteTimestamp = true;
    protected $createTime = 'ctime';


    public function getMySiteId()       { return isset($this->site_id) ? $this->site_id : 0; }
    public function getMyWxOpenId()     { return isset($this->wx_open_id) ? $this->wx_open_id : ''; }
    public function getMyBuyerId()      { return isset($this->buyer_id) ? $this->buyer_id : ''; }
    public function getMyBuyerName()    { return isset($this->buyer_name) ? $this->buyer_name : ''; }
    public function getMyBuyerTel()     { return isset($this->buyer_tel) ? $this->buyer_tel : ''; }
    public function getMyBuyerLevel()   { return isset($this->buyer_level) ? $this->buyer_level : 0; }

    /**
     * 获取用户信息
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne('User','id','user_id');
    }



    /**
     * @param $siteId
     * @param $buyerId
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null|SiteMember
     */
    public function getInstanceByBuyerAndSite($siteId,$buyerId){
        return $this->where([
            'site_id'=>$siteId,
            'buyer_id'=>$buyerId,
        ])->find();
    }



       /**
     * 获取会员在店铺中的等级及折扣
     * @param $siteId
     * @param $buyerId
     * @return mixed
     */
    public function getCacheBuyerSiteLevel($siteId,$buyerId){
     
        $defaultDis = [
            'level'     => 0,
            'discount'  => 1,
        ];
        $siteMemberIns = $this->getInstanceByBuyerAndSite($siteId,$buyerId);

        //为空说明不是这个店铺的会员
        if(empty($siteMemberIns) || !in_array($siteMemberIns->getMyBuyerLevel(),[1,2,3,4,5,6])){
            return $defaultDis;
        }

        //不为空则获取这个会员的会员等级
        $levelConfig = (new SiteMemberLevelConfig())->getInstanceBySiteAndLevel($siteId,$siteMemberIns->getMyBuyerLevel());

        return[
            'level'     => $siteMemberIns->getMyBuyerLevel(),
            'discount'  => !empty($levelConfig) ? $levelConfig->getMyLevelDiscount() : 1
        ];

    }



    /**
     * 获取店铺会员列表
     * @param $siteId
     * @param $name
     * @param $mobile
     * @param $openId
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($siteId,$name,$mobile,$openId,$page = 1, $limit = 10)
    {
        $where[] = ['site_id', 'eq', $siteId];

        if(!empty($mobile)){
            $where[] = ['buyer_tel', 'eq', $mobile];
        }
        if(!empty($openId)){
            $where[] = ['wx_open_id', 'eq', $openId];
        }
        if(!empty($name)){
            $where[] = ['buyer_name', 'like', '%'.$name.'%'];
        }

        $res = $this->where($where)
            ->order('updated_at desc')
            ->page($page, $limit)
            ->select();


        $count = $this->where($where)
            ->count();

        if($res !== false)
        {
            $data = [
                'status' => true,
                'msg' => '获取成功',
                'data' => [
                    'list' => $res,
                    'count' => $count,
                    'page' => $page,
                    'limit' => $limit
                ]
            ];
        }
        else
        {
            $data = [
                'status' => false,
                'msg' => '获取失败',
                'data' => []
            ];
        }
        return $data;
    }

    /**
     * 在这个店铺中,不在会员列表内,且不是普通会员的
     * 这个方法主要用在删除会员等级的时候，防止把已经设置了会员等级的会员等级删除掉
     * @param $siteId
     * @param array $level
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getListWithOutSmlcIds($siteId, $level=[])
    {
        //在这个店铺中，且不是普通会员的
        return $this->where([
            'site_id' => $siteId
        ])
            ->where('buyer_level','<>',0)
            ->whereNotIn('buyer_level',$level)->find();
    }



    /**
     * @param $siteId
     * @param $buyerId
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInsForBuyerId($siteId,$buyerId)
    {
        return $this->where([
            'site_id'   => $siteId,
            'buyer_id'  => $buyerId
        ])->find();
    }

    /**
     * 添加店铺会员数据
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addData($data)
    {
        $result = [ 'status' => true, 'msg' => '保存成功', 'data' => ''];

        $siteMemberIns = $this->getInsForBuyerId($data['site_id'],$data['buyer_id']);
        if(!empty($siteMemberIns)){
            return [ 'status' => false, 'msg' => '此用户已被添加', 'data' => ''];
        }
        $saveInfo = [
            'buyer_id'      => $data['buyer_id'],
            'wx_open_id'    => $data['wx_open_id'],
            'buyer_name'    => $data['buyer_name'],
            'site_id'       => $data['site_id'],
            'buyer_tel'     => $data['buyer_tel'],
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->insert($saveInfo);

        return $result;
    }

    /**
     * 编辑店铺会员数据
     * @param $smId
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editData($smId,$data)
    {
        $result = [ 'status' => true, 'msg' => '保存成功', 'data' => ''];
        $siteMemberIns = $this->where('sm_id',$smId)->find();
        if(empty($siteMemberIns)){
            return [ 'status' => false, 'msg' => '参数错误', 'data' => ''];
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->where('sm_id',$smId)->update($data);

        return $result;
    }

}