<?php
namespace app\Manage\controller;

use Request;
use think\Exception;
use think\Db;
use app\common\controller\Manage;
use app\common\model\Buyer;
use app\common\model\BuyerBalance;
use app\common\model\BuyerBalanceLog;
use app\common\model\BuyerPoints;
use app\common\model\BuyerPointsLog;
use app\common\model\GoodsComment;
use app\common\model\SiteMember;
use app\common\model\SiteMemberLevelConfig;
use app\common\model\SiteMemberCate;
use app\common\model\UserLog;
use app\common\model\User as UserModel;
use app\common\model\UserPointLog;
use think\exception\ValidateException;

class User extends Manage
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
            $userModel = new Buyer();
            $count = $userModel->getBuyerCountForParam(input('param.'));
            $list = $userModel->getBuyerListForParam(input('param.'));
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
                'count' => $count
            ];
        }else{
            $this->assign('buyer_id', $buyer_id);
            return $this->fetch('pointLog');
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
                return $this->fetch('balanceLog');
        }


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
        return $this->fetch('editPoint');
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
        $buyerBalanceModel = new BuyerPoints();

        if($this->request->isPost())
        {
            $params = $this->request->param();

            if($params)
            {

                $oldPoint = $buyerBalanceModel->getBuyerPointsForBuyerId($params['buyer_id']);
                $newPoint = $oldPoint + $params['point'];
                //余额判断
                if($newPoint < 0)
                {
                    $return['msg'] = '积分不足';
                    return $return;
                }

                $buyerBalanceModel->checkAndUpdateBuyerBalance($params['buyer_id']);
                //更新数据
                Db::startTrans();
                try{

                    $data = [
                        'mall_id' => $areaId,
                        'buyer_id' => $params['buyer_id'],
                        'site_id' => 0,
                        'type' => 1,
                        'num' => $params['point'],
                        'balance' => $newPoint,
                        'remarks' => '管理员后台操作',
                        'created_at' => date("Y-m-d H:i:s",time()),
                        'updated_at' => date("Y-m-d H:i:s",time()),
                    ];
//                    dump($params);
                    Db::name('buyer_points_log')->insert($data);
                    Db::name('buyer_points')->where('buyer_id',$params['buyer_id'])->update(['balance'=>$newPoint]);

                    Db::commit();
                    return ['status'=>true,'msg'=>'积分修改成功'];
                }catch (\Exception $e) {
                    Db::rollback();
                    return ['status'=>false,'msg'=>'积分修改失败'];
                }


//                $res = $buyerBalanceModel->editBuyerPoint($areaId,$params['buyer_id'],$params['point']);

            }

//            return $res;
        }

        return "";
//
//        $buyer_id = input('buyer_id');
//        $point = input('point');


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
        return $this->fetch('editBalance');
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
        $buyerBalanceModel = new BuyerBalance();
        $res = $buyerBalanceModel->editBuyerBalance($areaId,$buyer_id,$balance);
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


        if(Request::isAjax()){

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

            //获取会员分类
            $memberCate = new SiteMemberCate();
            $cateList = $memberCate->getMemberCateForSiteId($siteId);

            $list = collect($res['data']['list'])->map(function($info) use($levelList,$cateList){
                $levelName = '';
                $userCate = '';
                if(empty($info['buyer_level'])){
                    $levelName = '普通会员';
                }else{
                    $level = collect($levelList)->where('level',$info['buyer_level'])->first();
                    $levelName = !empty($level) ? $level['level_name'] : '未知会员';
                }

                if(empty($info['site_cat_id'])){
                    $levelName = '未知分类';
                }else{
                    $cate = collect($cateList)->where('id',$info['site_cat_id'])->first();
                    $cateName = !empty($cate) ? $cate['name'] : '未知会员';
                }

                $info['cate_name'] = $cateName;
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
                'site_cat_id'   => input('site_cat_id'),
            ];

            //添加店铺的用户的数据
            return $siteMemberModel->editData($smId,$data);
        }
        $data = $siteMemberModel->where('sm_id',$smId)->find();
        //获取会员等级信息
        $siteMemberLevelConfigModel = new SiteMemberLevelConfig();
        $levelList =$siteMemberLevelConfigModel->getMemberConfigForSiteId($data['site_id']);
        $this->assign('levelList', $levelList);

        // 获取会员分类信息
        $memberCate = new SiteMemberCate();
        $cateList =$memberCate->getMemberCateForSiteId($data['site_id']);
        $this->assign('cateList', $cateList);

        if (!$data) {
            return error_code(10002);
        }
        return $this->fetch('editSiteMember',['data' => $data]);
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

        // $siteMemberModel->editData($siteId,$buyerId);
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

        return $this->fetch('addSiteMember');
    }

    /**
     * 店铺操作赊账权限
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function creditSiteMember(){

        // 获取店铺mall_id和用户site_id
        $auth_id = session('manage.id');
        $auths = Db::name('manage')->where(['id'=>$auth_id])->find();

        $this->view->engine->layout(false);
        $sm_id = input('sm_id');
        $siteMemberModel = new SiteMember();
        $data = $siteMemberModel->where('sm_id',$sm_id)->find();

        $data = [
            'sm_id' => $sm_id,
            'site_id' => $data['site_id'],
            'buyer_id' => $data['buyer_id']
        ];

        $result = Db::name('credit_perm')->where(['sm_id'=>$sm_id,'site_id'=>$auths['site_id']])->find();
        $ajax = input('_ajax');
        if ($ajax==1) {
            $result = Db::name('credit_perm')->where(['sm_id'=>$sm_id,'site_id'=>$auths['site_id']])->find();
            return [
                'status' => true,
                'msg' => '获取成功',
                'result' => $result
            ];
        }else{
            $this->assign('data', $data);
            $this->assign('result', $result);
            return $this->fetch('creditsitemember');
        }
    }


    // 修改权限
    public function doBalanceCredit(){
        if (Request::isAjax()){
            $data = input('post.');
            if(!empty($data['is_credit']) ){
                $data = ['site_id' => $data['site_id'], 'buyer_id' => $data['buyer_id'] , 'sm_id' => $data['sm_id'], 'created_at' => date("Y-m-d H:i:s",time())];
                $result = Db::name('credit_perm')->insert($data);
                $back = Db::name('site_member')->where(['site_id' => $data['site_id'], 'sm_id' => $data['sm_id']])->update(['is_credit'=>2]);
                if($result && $back){
                    return [
                        'status' => true,
                        'msg' => '操作成功',
                        'data' => $result,
                    ];
                }
            }
        }
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
        //添加用户信息
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






        /**
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cateMember()
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

        $memberCate = new SiteMemberCate();
        $info = $memberCate->getMemberCateForSiteId($siteId);
        return $this->fetch('catemember',[ 'info' => $info ]);

    }


    /**
     * 会员等级配置折扣修改
     * @return array|bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editCateMember()
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
        $memberCate = new SiteMemberCate();

        //获取分类名称
        $name = isset($param['name']) ? $param['name'] : [];

        //验证会员等级的唯一性
        $returnData = $this->checkCateName($name,$returnData);
        if(!$returnData['status']){
            return $returnData;
        }

        // //  验证是否能删除被删除的会员
        // $memberInfo = $memberModel->getListWithOutSmlcIds($siteId,$name);

        // //如果不为空，说明删除了不应该删除的
        // if(!empty($memberInfo)){
        //     return [
        //         'status' => false,
        //         'msg'    => '将要删除的等级已被用户使用,不能删除. '.$memberInfo['buyer_tel'],
        //     ];
        // }

        try {

            $smlcList = [];
            collect($name)->each(function($v,$k) use($name,$siteId,&$smlcList){
                $smlcList[$k]['site_id']        = $siteId;
                $smlcList[$k]['name']          = $v;
                $smlcList[$k]['name']     = $name[$k];
            });

            //删除已经删掉的店铺地址
            $memberCate->deleteWithOutSmlcIds($siteId,$name);

            //更新会员等级
            collect($smlcList)->each(function($info)  use($memberCate){
                $memberCate->updateMemberCate($info['name'],$info['site_id'],$info);
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
     * 验证会员等级名称
     * @param $levelName
     * @param $returnData
     * @return array
     */
    public function checkCateName($name,$returnData){
        if(empty($name) || !is_array($name)){
            return [
                'status' => false,
                'msg'    => '验证会员等级名称有误',
            ];
        }
        if(collect($name)->count()!=collect($name)->unique()->count()){
            return [
                'status' => false,
                'msg'    => '会员等级名称必须唯一',
            ];
        }
        return $returnData;
    }

    // 用户导入
    public function export(){

        // 会员数据
        $data = Db::table('ims_ewei_shop_member')->where('1=1')->select();

        // 遍历数据
        $i = 0;
        $list = [];
        foreach($data as $key =>$value){
            // 地区数据
            $address = Db::table('ims_ewei_shop_member_address')->where('openid',$value['openid'])->whereOr('openid',$value['openid_wa'])->find();
            // 判断
            if($address){
                $i++;
                // $value['ad_realname']  =  $address['realname'];
                // $value['ad_mobile']  =  $address['mobile'];
                // $value['ad_province']  =  $address['province'];
                // $value['ad_city']  =  $address['city'];
                // $value['ad_area']  =  $address['area'];
                // $value['ad_address']  =  $address['address'];

                // Db::table('ims_diff')->insert(['uid'=>$value['id'],'openid'=>$value['openid'],'nickname'=>$value['nickname'],'nickname_wechat'=>$value['nickname_wechat'],'realname'=>$address['realname'] ,'mobile'=>$address['mobile'] ,'province'=>$address['province'] ,'city'=>$address['city'] ,'area'=>$address['area'] ,'address'=>$address['address']]);
            }else{
                array_push($list,$value['openid']);
            }
        }

        var_dump($list);
    }
}
