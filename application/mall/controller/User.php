<?php
namespace app\Mall\controller;

use app\common\controller\Mall;
use app\common\model\AreaMallBuyerCoupon;
use app\common\model\AreaMallBuyerCouponCategory;
use app\common\model\AreaMallBuyerCouponList;
use app\common\model\Buyer;
use app\common\model\BuyerBalance;
use app\common\model\BuyerBalanceLog;
use app\common\model\BuyerPoints;
use app\common\model\BuyerPointsLog;
use app\common\model\AreaMallCredit;
use app\common\model\AreaMallCreditFlow;
use app\common\model\GoodsComment;
use app\common\model\SiteMember;
use app\common\model\SiteMemberLevelConfig;
use app\common\model\UserLog;
use app\common\model\User as UserModel;
use app\common\model\UserPointLog;
use app\common\model\AreaMallBuyerRelation;
use app\common\model\AreaMall;
use org\AliSms;
use Request;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

class User extends Mall
{
    public function index()
    {
        if(Request::isAjax()){
            //获取权限的筛选条件
            $userWhere = $this->getMyUserWhere();
            //这里只有区域管理员可以设置
            $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
            if(empty($areaId)){
                return [
                    'code' => false,
                    'msg' => '只有区域管理员才有权限打开此界面',
                    'count' => 0,
                    'data' => [],
                ];
            }

            $filter = array_merge(['mall_id'=>$areaId],input('param.'));
            $userModel = new Buyer();
            $count = $userModel->getBuyerCountForParam($filter);
            $list = $userModel->getBuyerListForParam($filter);
            return [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];

        }else{
            return $this->fetch('index');
        }
    }

    /**
     *
     */
    public function editUserNick()
    {
        if($this->request->isAjax())
        {
            $params = $this->request->param();

            if($params)
            {
                $userModel = new Buyer();
                $userModel->where('buyer_id',$params['buyer_id'])->update(['buyer_nickname'=>$params['buyer_nickname']]);
                return [
                    'code' => 0,
                    'msg' => '修改成功',
                    'data' => [],
                ];
            }
            return ['code'=>1,'msg'=>'参数错误'];
        }
        return [];
    }

    /**
     * 获取积分记录
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pointLog()
    {
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $ajax = input('_ajax');
        if ($ajax==1) {
            $page = input('page',1);
            $limit = input('limit',10);
            $buyerPointLogModel = new BuyerPointsLog();
            $list = $buyerPointLogModel->getBuyerPointsLog(0,$buyer_id,$page,$limit);
            $count = $buyerPointLogModel->getBuyerPointsLog(1,$buyer_id,$page,$limit);
            return [
                'status' => true,
                'msg' => '获取成功',
                'data' => $list,
                'count' => $count,
            ];
        }else{
            $this->assign('buyer_id', $buyer_id);
            return $this->fetch('pointlog');
        }


    }



    /**
     * 余额记录
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function balanceLog()
    {
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $ajax = input('_ajax');
        if ($ajax==1) {
            $page = input('page',1);
            $limit = input('limit',10);
            $buyerBalanceLogModel = new BuyerBalanceLog();

            $list = $buyerBalanceLogModel->getBuyerBalanceLog(0,$buyer_id,$page,$limit);
            $count = $buyerBalanceLogModel->getBuyerBalanceLog(1,$buyer_id,$page,$limit);

            return [
                'status' => true,
                'msg' => '获取成功',
                'data' => $list,
                'count' => $count
            ];
        }else{
                $this->assign('buyer_id', $buyer_id);
                return $this->fetch('balancelog');
        }
    }

    /**
     * 发送指定卷
     * @return mixed
     */
    public function sendCoupon()
    {
        $this->view->engine->layout(false);

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return  [
                'status' => false,
                'msg' => '只有区域管理员才有权限保存'
            ];
        }

        $buyerId = $this->request->param('buyer_id');
        if($buyerId)
            $buyerInfo = Buyer::get(['buyer_id'=>$buyerId]);
        //获取优惠劵固定分类
        $couponCategory = (new AreaMallBuyerCouponCategory())->where(['mall_id'=>$areaId,'status'=>1])->whereIn('type',[1,3,5,6,7])->select();
        $this->assign('category',$couponCategory);
        $this->assign('buyer',$buyerInfo);
        return $this->fetch();
    }

    /**
     * 获取劵
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCoupon()
    {
        if($this->request->isAjax())
        {
            $id = $this->request->param('id');
            $where['category_id'] = $id;
            $where['status'] = 1;
            //根据分类ID查询优惠劵
            $couponList = (new AreaMallBuyerCoupon())
                ->where($where)
                ->whereTime('end_time', '>', time())
                ->select();

            return ['count'=>count($couponList),'dataList'=>$couponList];
        }
    }

    /**
     * 指定发劵
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendCouponPost()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return  [
                'status' => false,
                'msg' => '只有区域管理员才有权限保存'
            ];
        }
        if($this->request->isPost())
        {
            $params = $this->request->param();

            if($params)
            {

                $buyer = Buyer::get(['buyer_id'=>$params['buyerId']]);
                if(!$buyer)
                {
                    return ['status'=>false,'msg'=>'无效用户'];
                }
                Db::startTrans();
                try{

                    //查询分类
                    $category = (new AreaMallBuyerCouponCategory())->where('id',$params['categoryId'])->find();
                    $coupon = [];
                    //检测需要发送的劵
                    if($category['type'] == 1) //满减
                    {
                        $coupon = (new AreaMallBuyerCoupon())->where(['mall_id'=>$areaId,'cp_id'=>$params['couponId'],'cp_type'=>1,'status'=>1])->whereIn('use_type',[1,3])->find();
                    }elseif($category['type'] == 3) //折扣
                    {
                        $coupon = (new AreaMallBuyerCoupon())->where(['mall_id'=>$areaId,'cp_id'=>$params['couponId'],'cp_type'=>2,'status'=>1])->whereIn('use_type',[1,3])->find();
                    }elseif($category['type'] == 5) //服务劵
                    {
                        //查询垃圾劵
                        $coupon = (new AreaMallBuyerCoupon())->where(['mall_id'=>$areaId,'cp_id'=>$params['couponId'],'cp_type'=>4,'status'=>1])->whereIn('use_type',[1,3])->find();
                    }elseif ($category['type'] == 6) //特快劵
                    {
                        //查询特快劵
                        $coupon = (new AreaMallBuyerCoupon())->where(['mall_id'=>$areaId,'cp_id'=>$params['couponId'],'cp_type'=>5,'status'=>1])->whereIn('use_type',[1,3])->find();
                    }elseif ($category['type'] == 7) //物流劵
                    {
                        //查询物流劵
                        $coupon = (new AreaMallBuyerCoupon())->where(['mall_id'=>$areaId,'cp_id'=>$params['couponId'],'cp_type'=>6,'status'=>1])->whereIn('use_type',[1,3])->find();
                    }
                    if(!empty($coupon))
                    {
                        //没有过期的优惠劵
                        if(time() < $coupon['end_time'])
                        {
                            if($params['couponNum'] > 0)
                            {
                                for ($i=1;$i<=$params['couponNum'];$i++){

                                    (new AreaMallBuyerCouponList())->insert([
                                        'mall_id' => $coupon['mall_id'],
                                        'site_id' => $coupon['site_id'],
                                        'buyer_id' => $buyer['buyer_id'],
                                        'cp_id' => $coupon['cp_id'],
                                        'coupon_title' => $coupon['cp_title'],
                                        'coupon_sn' => rand(10000, 99999) . rand(10000, 99999),
                                        'coupon_state' => 1,
                                        'coupon_type' => $coupon['cp_type'],
                                        'coupon_class_type' => $coupon['class_type'],
                                        'coupon_num_limit' => $coupon['cp_num_limit'],
                                        'coupon_price_discount' => $coupon['cp_price_discount'],
                                        'coupon_money_limit' => $coupon['cp_money_limit'],
                                        'coupon_money_discount' => $coupon['cp_money_discount'],
                                        'is_type' => 0,
                                        'remark' => $coupon['cp_desc'],
                                        'push_time' => time(),
                                        'begin_time' => $coupon['start_time'],
                                        'end_time' => $coupon['end_time'],
                                    ]);
                                }
                                //推送短信通知
                                $name = "[".$buyer['buyer_name']."]";
                                $content = "【".$coupon['cp_title']."】"; //编辑优惠劵名称
                                $this->send_coupon_sms($buyer['buyer_tel'],$name,$content);
//                                dump($a);
                            }
                        }
                    }
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }
        }
        return [];
    }

    /**
     * 发送用户优惠劵获取通知
     * @param $mobile
     * @param $name
     * @param $content
     * @return string
     */
    public function send_coupon_sms($mobile,$name,$content)
    {
        $config = [
            'accessKeyId'   => 'LTAID6FqAEXdF0Uo',
            'accessKeySecret'   => 'GS2v2CA7mE9A49WJOpnQK0nSDDNIk9',
            'signName'   => '佩祥',
            'templateCode'   => 'SMS_182405104',
        ];
        $sms = new AliSms($config);
        $msg = $sms->send_massage($mobile,$name,$content,'https://dysmsapi.aliyuncs.com/');
        if($msg) return 'SUCCESS';
        return 'ERROR';
    }

    /**
     * 积分编辑界面
     * @return array|bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editPoint()
    {
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $this->assign('buyer_id', $buyer_id);
        $buyerPointsModel = new BuyerPoints();
        $point = $buyerPointsModel->getBuyerPointsForBuyerId($buyer_id);
        $this->assign('point', $point);
        return $this->fetch('editpoint');
    }

    /**
     * 修改积分余额
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function savePoint()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return  [
                'status' => false,
                'msg' => '只有区域管理员才有权限保存'
            ];
        }
        $buyer_id = input('buyer_id');
        $point = input('point');
        $remark = input('remark');
        $buyerBalanceModel = new BuyerPoints();
        $res = $buyerBalanceModel->editBuyerPoint($areaId,$buyer_id,$point,$remark);
        return $res;
    }


    /**
     * 余额编辑界面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editBalance()
    {
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $this->assign('buyer_id', $buyer_id);
        $buyerBalanceModel = new BuyerBalance();
        $balance = $buyerBalanceModel->getBuyerBalanceForBuyerId($buyer_id);
        $this->assign('balance', $balance);
        return $this->fetch('editbalance');
    }


    /**
     * 赊账编辑界面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editCredit(){
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $this->assign('buyer_id', $buyer_id);
        $buyerCreditModel = new AreaMallCredit();
        $balance = $buyerCreditModel->getBuyerCreditForBuyerId($buyer_id);
        $this->assign('balance', $balance);
        return $this->fetch('editcredit');
    }



        /**
     * 保存用户余额
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveCredit()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return  [
                'status' => false,
                'msg' => '只有区域管理员才有权限保存'
            ];
        }
        $buyer_id = input('buyer_id');
        $balance = input('balance');
        $remark  = input('remark');
        $buyerCreditModel = new AreaMallCredit();
        $res = $buyerCreditModel->editBuyerCredit($areaId,$buyer_id,$balance,$remark);
        return $res;
    }


    /**
     * 赊账记录
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function CreditLog()
    {
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $ajax = input('_ajax');
        if ($ajax==1) {
            $page = input('page',1);
            $limit = input('limit',10);
            $buyerCreditFlowModel = new AreaMallCreditFlow();
            $list = $buyerCreditFlowModel->getBuyerCreditLog(0,$buyer_id,$page,$limit);
            $count = $buyerCreditFlowModel->getBuyerCreditLog(1,$buyer_id,$page,$limit);

            return [
                'status' => true,
                'msg' => '获取成功',
                'data' => $list,
                'count' => $count
            ];
        }else{
            $this->assign('buyer_id', $buyer_id);
            return $this->fetch('creditlog');
        }
    }

    

    /**
     * 保存用户余额
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveBalance()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        //这里只有区域管理员可以设置
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return  [
                'status' => false,
                'msg' => '只有区域管理员才有权限保存'
            ];
        }
        $buyer_id = input('buyer_id');
        $balance = input('balance');
        $remark  = input('remark');
        $buyerBalanceModel = new BuyerBalance();
        $res = $buyerBalanceModel->editBuyerBalance($areaId,$buyer_id,$balance,$remark);
        return $res;
    }


    //取当前店铺的所有用户的登陆退出消息,现在是绑定死一个用户，以后可能有多个用户
    public function userLogList()
    {
        $userLogModel = new UserLog();

        return $userLogModel->getList($this->getUserId());
    }
    //用户统计
    public function statistics()
    {
        $userLogModel = new Buyer();

        //新增用户
        $list_login = $userLogModel->statistics(7, 3);

        //活跃用户
        $list_reg = $userLogModel->statistics(7, 4);

        $data = [
            'legend' => [
                'data' => ['新增记录', '活跃记录']
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'data' => $list_login['day']
                ]
            ],
            'series' => [
                [
                    'name' => '新增记录',
                    'type' => 'line',
                    'data' => $list_reg['data']
                ],
                [
                    'name' => '活跃记录',
                    'type' => 'line',
                    'data' => $list_login['data']
                ]
            ]
        ];
        return $data;
    }


    /**
     * 评价列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment()
    {
        if(Request::isPost())
        {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $order_id = input('order_id', '');
            $evaluate = input('evaluate', 'all');
            $mobile = input('mobile', false);
            $goodsCommentModel = new GoodsComment();
            $res = $goodsCommentModel->getListComments($page, $limit, $order_id, $evaluate, 'all', $mobile);
            if($res['status'])
            {
                $return = [
                    'status' => true,
                    'msg' => '获取成功',
                    'data' => $res['data']['list'],
                    'count' => $res['data']['count']
                ];
            }
            else
            {
                $return = [
                    'status' => false,
                    'msg' => '获取失败',
                    'data' => $res['data']['list'],
                    'count' => $res['data']['count']
                ];
            }
            return $return;
        }
        else
        {
            return $this->fetch('comment');
        }
    }

    /**
     *店铺会员管理
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function siteMember()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺才有权限打开此界面'
            ];
        }


        if(Request::isAjax())
        {

            $page = input('page', 1);
            $limit = input('limit', 20);

            $buyer_name = input('buyer_name', '');
            $buyer_tel = input('buyer_tel', '');
            $wxOpenId = input('wx_open_id', '');


            $siteMemberModel = new SiteMember();
            $res = $siteMemberModel->getList($siteId, $buyer_name, $buyer_tel,$wxOpenId, $page, $limit);

            //获取会员等级信息
            $siteMemberLevelConfigModel = new SiteMemberLevelConfig();
            $levelList =$siteMemberLevelConfigModel->getMemberConfigForSiteId($siteId);

            $list = collect($res['data']['list'])->map(function($info) use($levelList){
                $levelName = '';
                if(empty($info['buyer_level'])){
                    $levelName = '普通会员';
                }else{
                    $level = collect($levelList)->where('level',$info['buyer_level'])->first();
                    $levelName = !empty($level) ? $level['level_name'] : '未知会员';
                }
                $info['level_name'] = $levelName;
                return $info;
            })->all();

            if($res['status'])
            {
                $return = [
                    'code' => 0,
                    'msg' => '获取成功',
                    'data' => $list,
                    'count' => $res['data']['count']
                ];
            }
            else
            {
                $return = [
                    'code' => 500,
                    'msg' => '获取失败',
                    'data' => $list,
                    'count' => $res['data']['count']
                ];
            }
            return $return;
        }
        else
        {
            return $this->fetch('sitemember');
        }

    }

    /**
     * 店铺会员编辑
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editSiteMember()
    {
        $this->view->engine->layout(false);
        $siteMemberModel = new SiteMember();
        $smId = input('sm_id');
        if(Request::isPost())
        {
            //修改会员信息
            $data = [
                'buyer_name'    => input('buyer_name'),
                'buyer_level'   => input('buyer_level'),
            ];
            //添加店铺的用户的数据
            return $siteMemberModel->editData($smId,$data);
        }
        $data = $siteMemberModel->where('sm_id',$smId)->find();
        //获取会员等级信息
        $siteMemberLevelConfigModel = new SiteMemberLevelConfig();
        $levelList =$siteMemberLevelConfigModel->getMemberConfigForSiteId($data['site_id']);
        $this->assign('levelList', $levelList);
        if (!$data) {
            return error_code(10002);
        }
        return $this->fetch('editsitemember',['data' => $data]);
    }

    /**
     * 添加用户信息
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addSiteMember()
    {
        $this->view->engine->layout(false);

        if(Request::isPost())
        {
            //获取权限的筛选条件
            $userWhere = $this->getMyUserWhere();

            $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
            if(empty($siteId)){
                return [
                    'status' => false,
                    'msg' => '只有店铺才有权限编辑'
                ];
            }

            $buyerId =  input('buyer_id');


            $buyerModel = new Buyer();
            $buyerInfo = $buyerModel->getBuyerInfoForId($buyerId);
            if(empty($buyerInfo)){
                return  [
                    'status' => false,
                    'msg' => '没有找到该用户信息',
                    'data' => $buyerInfo,
                ];
            }
            //添加用户信息
            $siteMemberModel = new SiteMember();
            $siteMemberIns = $siteMemberModel->getInsForBuyerId($siteId,$buyerId);
            if(!empty($siteMemberIns)){
                return  [
                    'status' => false,
                    'msg' => '此用户已添加',
                    'data' => $buyerInfo,
                ];
            }
//            $siteMemberModel->editData($siteId,$buyerId);
        $data = [
            'buyer_id'      => $buyerInfo['buyer_id'],
            'wx_open_id'    => $buyerInfo['wx_open_id'],
            'buyer_name'    => $buyerInfo['buyer_name'],
            'site_id'       => $siteId,
            'buyer_tel'     => $buyerInfo['buyer_tel'],
        ];
        //添加店铺的用户的数据
        return $siteMemberModel->addData($data);

        }

        return $this->fetch('addsitemember');
    }

    /**
     * 根据手机号码获取用户信息
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchSiteMember()
    {
        $phone =  input('buyer_tel','');

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺才有权限编辑'
            ];
        }
        $buyerModel = new Buyer();
        //根据手机号码获取用户信息
        $buyerInfo = $buyerModel->getBuyerInfoForTel($phone);

        if(empty($buyerInfo)){
            return  [
                'status' => false,
                'msg' => '没有找到该用户信息',
                'data' => $buyerInfo,
            ];
        }

        //查看这个用户在这个店铺是否被增加了
        $buyerMemberModel = new SiteMember();
        $buyerIns = $buyerMemberModel->getInsForBuyerId($siteId,$buyerInfo['buyer_id']);
        if(!empty($buyerIns)){
            return  [
                'status' => false,
                'msg' => '此用户已添加',
                'data' => $buyerInfo,
            ];
        }


        return  [
            'status' => true,
            'msg' => '获取成功',
            'data' => $buyerInfo,
        ];
    }


    /**
     * 添加用户信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveSiteMember()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺才有权限编辑'
            ];
        }

        $buyerId =  input('buyer_id');
        $buyerModel = new Buyer();
        $buyerInfo = $buyerModel->getBuyerInfoForId($buyerId);
        if(empty($buyerInfo)){
            return  [
                'status' => false,
                'msg' => '没有找到该用户信息',
                'data' => $buyerInfo,
            ];
        }
        //添加用户信息
        $siteMemberModel = new SiteMember();
        $siteMemberIns = $siteMemberModel->getInsForBuyerId($siteId,$buyerId);
        if(!empty($siteMemberIns)){
            return  [
                'status' => false,
                'msg' => '此用户已经添加',
                'data' => $buyerInfo,
            ];
        }

//        $data = [
//            'buyer_id'      => $buyerInfo['buyer_id'],
//            'wx_open_id'    => $buyerInfo['wx_open_id'],
//            'buyer_name'    => $buyerInfo['buyer_name'],
//            'site_id'       => $siteId,
//            'buyer_tel'     => $buyerInfo['buyer_tel'],
//        ];
//        //添加店铺的用户的数据
//        return $siteMemberModel->addData($data);
        return true;
    }

    /**
     * 店铺会员删除
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delSiteMember()
    {
        $smId = input('sm_id');
        // 添加用户信息
        $siteMemberModel = new SiteMember();
        $siteMemberIns = $siteMemberModel->where('sm_id',$smId)->find();

        if(empty($siteMemberIns)){
            return  [
                'status' => true,
                'msg' => '会员不存在',
            ];
        }
        $siteMemberModel->where('sm_id',$smId)->delete();

        return  [
            'status' => true,
            'msg' => '删除成功',
        ];
    }


    /**
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function levelMember()
    {
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            return [
                'status' => false,
                'msg' => '只有店铺才有权限打开此界面'
            ];
        }

        $memberConfig = new SiteMemberLevelConfig();

        $info = $memberConfig->getMemberConfigForSiteId($siteId);


        return $this->fetch('levelmember',[ 'info' => $info ]);

    }


    /**
     * 会员等级配置折扣修改
     * @return array|bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editLevelMember()
    {
        $returnData = [
            'status' => true,
            'msg'    => '保存成功',
        ];

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();

        $siteId = isset($userWhere[self::PERMISSION_SITE]) ? $userWhere[self::PERMISSION_SITE] : 0;
        if(empty($siteId)){
            $returnData = [
                'status' => false,
                'msg'    => '只有店铺才有权限打开此界面',
            ];
        }

        $param = input('param.');

        $memberModel = new SiteMember();
        $memberConfig = new SiteMemberLevelConfig();

        //获取会员等级
//        $smlc_ids = isset($param['smlc_id']) ? $param['smlc_id'] : [];
        $level = isset($param['level']) ? $param['level'] : [];
        $level_name = isset($param['level_name']) ? $param['level_name'] : [];
        $level_discount = isset($param['level_discount']) ? $param['level_discount'] : [];

        //验证会员等级的唯一性
        $returnData = $this->checkLevels($level,$returnData);
        if(!$returnData['status']){
            return $returnData;
        }
        $returnData = $this->checkLevelsName($level_name,$returnData);
        if(!$returnData['status']){
            return $returnData;
        }

        //验证是否能删除被删除的会员
        $memberInfo = $memberModel->getListWithOutSmlcIds($siteId,$level);
        //如果不为空，说明删除了不应该删除的
        if(!empty($memberInfo)){
            return [
                'status' => false,
                'msg'    => '将要删除的等级已被用户使用,不能删除. '.$memberInfo['buyer_tel'],
            ];
        }

        try {

            $smlcList = [];
            collect($level)->each(function($v,$k) use($level_name,$level_discount,$siteId,&$smlcList){
//                $smlcList[$k]['smlc_id']        = $v;
                $smlcList[$k]['site_id']        = $siteId;
                $smlcList[$k]['level']          = $v;
                $smlcList[$k]['level_name']     = $level_name[$k];
                //会员的折扣不能为0，否则则为1
                $smlcList[$k]['level_discount'] = !empty($level_discount[$k]) ? $level_discount[$k] : 1;
            });


            //删除已经删掉的店铺地址
            $memberConfig->deleteWithOutSmlcIds($siteId,$level);


            //更新会员等级
            collect($smlcList)->each(function($info)  use($memberConfig){
                $memberConfig->updateMemberConfig($info['level'],$info['site_id'],$info);
            });

        } catch (ValidateException $e) {
            return [
                'status' => false,
                'msg'    => '配置会员等级失败'.$e,
            ];
        }

        return $returnData;
    }


    /**
     * 验证会员等级
     * @param array $level
     * @param $returnData
     * @return array
     */
    public function checkLevels($level,$returnData){
        if(empty($level) || !is_array($level)){
            return [
                'status' => false,
                'msg'    => '提交会员等级有误',
            ];
        }
        if(collect($level)->count()!=collect($level)->unique()->count()){
            return [
                'status' => false,
                'msg'    => '会员等级必须唯一',
            ];
        }

        $isError = 0;
        collect($level)->each(function($id) use(&$isError){
            if(!in_array($id,[1,2,3,4,5,6])){
                $isError = $isError+1;
            }
        });
        if(!empty($isError)){
            return [
                'status' => false,
                'msg'    => '会员等级只能在1到6',
            ];
        }
        return $returnData;
    }

    /**
     * 验证会员等级名称
     * @param $levelName
     * @param $returnData
     * @return array
     */
    public function checkLevelsName($levelName,$returnData){
        if(empty($levelName) || !is_array($levelName)){
            return [
                'status' => false,
                'msg'    => '验证会员等级名称有误',
            ];
        }
        if(collect($levelName)->count()!=collect($levelName)->unique()->count()){
            return [
                'status' => false,
                'msg'    => '会员等级名称必须唯一',
            ];
        }
        return $returnData;
    }


    /*********************PLUS会员*************************/

    /**
     * 添加plus页面
     * @return mixed
     */
    public function addPlus()
    {
        $this->view->engine->layout(false);//移除模板样式

        if($this->request->isAjax())
        {
            $id = $this->request->get('buyer_id');

            $info = (new Buyer())->where('buyer_id',$id)->find();
            if($info)
            {
                if($info['is_plus'] == 1)
                {
                    (new Buyer())->where('buyer_id',$id)->update(['is_plus'=>0]);
                    return ['code'=>0,'msg'=>'取消成功'];
                }else{
                    (new Buyer())->where('buyer_id',$id)->update(['is_plus'=>1]);
                    return ['code'=>0,'msg'=>'加入成功'];
                }
            }


        }
    }

    /**
     * 添加处理
     */
    public function addPlusPost()
    {
        if($this->request->isPost())
        {
            $params = $this->request->param();

        }
    }

    /**
     * 添加区域权限
     * @return mixed
     */
    public function addAuthority()
    {
        // 移除模板样式
        $this->view->engine->layout(false);

        if($this->request->isAjax())
        {
            $id = $this->request->get('buyer_id');

            $info = (new Buyer())->where('buyer_id',$id)->find();
            if($info)
            {
                if($info['is_mall'] == 1)
                {
                    (new Buyer())->where('buyer_id',$id)->update(['is_mall'=>0]);
                    return ['code'=>0,'msg'=>'取消成功'];
                }else{
                    (new Buyer())->where('buyer_id',$id)->update(['is_mall'=>1]);
                    return ['code'=>0,'msg'=>'加入成功'];
                }
            }
        }
    }


    /**
     * 添加区域权限
     * @return mixed
     */
    public function setArea()
    {
        $this->view->engine->layout(false);
        $buyer_id = input('buyer_id');
        $ajax = input('_ajax');
        if ($ajax==1) {

            // 查询已开地区
            $AreaMall = new AreaMall();
            $list = $AreaMall->areaMallList();

            // 查询区域权限表
            $AreaMallBuyerRelation = new AreaMallBuyerRelation();

            $listData = [];
            foreach ($list as $key => $value) {
                $relation = $AreaMallBuyerRelation->MallBuyerRelation($buyer_id,$value['mall_id']);
                if($relation){
                    $value['switch'] = true; 
                }else{
                    $value['switch'] = false;
                }
                $listData[] = $value;
            }

            return [
                'status' => true,
                'msg' => '获取成功',
                'data' => $listData,
                'count' => $count
            ];
        }else{
            $this->assign('buyer_id', $buyer_id);
            return $this->fetch('setArea');
        }
    }


    /**
     * 积分种植记录
     */
    public function doSetArea(){

        if(Request::isAjax()){

            $mall_id = input('mall_id');
            $buyer_id = input('buyer_id');

            if($mall_id && $buyer_id){
                // 查询区域权限表
                $AreaMallBuyerRelation = new AreaMallBuyerRelation();
                $relation = $AreaMallBuyerRelation->MallBuyerRelation($buyer_id,$mall_id);
                // 判断新增活删除
                $results ='';
                if($relation){
                    // 删除
                    $results = $AreaMallBuyerRelation->MallBuyerRelationDelete($buyer_id,$mall_id);
                }else{
                    // 新增
                    $results = $AreaMallBuyerRelation->MallBuyerRelationInset($buyer_id,$mall_id);
                }

                if($results){
                    return [
                        'msg' => '操作成功',
                        'data' => $results,
                    ];
                }else{
                    return [
                        'msg' => '操作失败',
                        'data' => $results,
                    ];
                }
                

            }
        }

    }

    /**
     * 积分种植记录
     */
    public function balancetop(){

        if(Request::isAjax()){
            //获取权限的筛选条件
            $userWhere = $this->getMyUserWhere();
            $buyerModel = new Buyer();
            $BuyerBalanceLog = new BuyerBalanceLog();

            $page = input('page',1);
            $limit = input('limit',20);
            
            $data = $BuyerBalanceLog->where('remark',['like','%现金充值%'])->order('id','desc')->page($page,$limit)->select();
    
            $balanceData = [];
            // 遍历信息
            foreach($data as $value){
                // 查询用户信息
                $userinfo = $buyerModel->where('buyer_id',$value['buyer_id'])->find();
                $value['buyer_name'] = $userinfo['buyer_name'];
                $value['buyer_tel'] = $userinfo['buyer_tel'];
                $value['buyer_header'] = $userinfo['buyer_header'];
            }

            $count = count($data);
            return [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $data,
            ];

        }else{
            return $this->fetch('balancetop');
        }

    }


}
