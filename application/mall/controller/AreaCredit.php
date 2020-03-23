<?php
/**
 * Created by PhpStorm.
 * User: Lyunp
 * Date: 2019/5/13
 * Time: 10:12
 */

namespace app\mall\controller;

use app\common\controller\Mall;
use app\common\model\AreaMallCreditList;
use app\common\model\AreaMallCreditPostpone;
use app\common\model\Credit;
use app\common\model\CreditPostpone;
use app\common\taglib\Jshop;
use app\manage\validate\AreaCreditList;
use org\Upload;
use org\upload\driver\Qiniu;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\File;
use think\Request;
use think\Validate;
use think\view\driver\Think;

/**
 * 区域赊账控制器
 * Class AreaCredit
 * @package app\manage\controller
 */
class AreaCredit extends Mall
{

    protected $buyerModel = null;//用户表
    protected $AreaCreditModel = null;//账户表
    protected $AreaCreditListModel =null;//申请表
    protected $AreaCreditFlowModel = null;//流水表
    protected $AreaCreditRootModel = null;//开通赊账权限

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->buyerModel = model('Buyer');
        $this->AreaCreditModel = model('AreaMallCredit');
        $this->AreaCreditListModel = model('AreaMallCreditList');
        $this->AreaCreditFlowModel = model('AreaMallCreditFlow');
        $this->AreaCreditRootModel = model('AreaMallCreditRoot');

    }

    /**
     * 区域账户赊账列表显示
     * @return mixed
     */
    public function creditIndex()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            if($params)
            {
                $where = [];
                if(isset($params['name']))
                {
                    $where[] = ['username','like', '%'.$params['name'].'%'];
                }

                $status = isset($params['status']) ? $params['status'] : 0;
//                dump($status);
                switch ($status)
                {
                    case '1':
                        $where['status'] = ['status','eq',1];
                        break;
                    case '2':
                        $where['status'] = ['status','eq',2];
                        break;
                    case '3':
                        $where['status'] = ['status','eq',3];
                        break;
                    case '4':
                        $where['isdel'] = ['isdel','eq',1];
                        break;
                    default:
                }
                $page = $this->request->get("page", 0);
                $limit = $this->request->get("limit", 0);
                $where['mall_id'] = $areaId;
                list($where,$page,$limit) = [$where,$page,$limit];
                $list = $this->AreaCreditListModel->where($where)->order('id','desc')->page($page,$limit)->select();

                $total = $this->AreaCreditListModel
                    ->where($where)
                    ->count();

                $status = ['0'=>"<a style='color: #6ce26c'>待审核</a>",'1'=>"<a style='color: green'>已申请</a>",'2'=>"<a style='color: #0C0C0C'>未通过</a>",'3'=>"<a style='color: red'>已通过</a>"];
                $schedule = ['1'=>'等待一审','2'=>'一审通过','3'=>'一审失败','4'=>'等待二审','5'=>'二审通过','6'=>'二审失败','7'=>'等待三审','8'=>'三审通过','9'=>'三审失败'];
                foreach ($list as $key=>$val) {
                    $list[$key]['status'] = $status[$val['status']];
                    $list[$key]['status_s'] = $schedule[$val['schedule']];
                }

                $result = array("code"=>0,"count" => $total, "data" => $list);

                return json($result);
            }
            return [];
        }

        //展示状态数据条数
        $sta = [];
        $sta['all'] = $this->AreaCreditListModel->where('mall_id',$areaId)->count();
        $sta['refuse'] = $this->AreaCreditListModel->where(['mall_id'=>$areaId,'status'=>2])->count();
        $sta['pass'] = $this->AreaCreditListModel->where(['mall_id'=>$areaId,'status'=>3])->count();
        $sta['unreviewed'] = $this->AreaCreditListModel->where(['mall_id'=>$areaId,'status'=>1])->count();
        $sta['black'] = $this->AreaCreditListModel->where(['mall_id'=>$areaId,'isdel'=>1])->count();

        $this->assign('status',$sta);
//        return [
//            'total'       =>$all,
//            'refuse'     =>$refuse,
//            'pass'   =>$pass,
//            'unreviewed'   =>$unreviewed,
//        ];

        return $this->fetch();
    }

    /**
     * 创建区域赊账用户
     * @return mixed
     */
    public function creditAdd()
    {
        $this->view->engine->layout(false);

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                //规则验证
                $rule = [
                    'phone' => 'unique:area_credit_list',
                    'card_id' => ['unique'=>'area_credit_list','regex'=>'/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/'],
                ];
                $msg = [
                    'phone.unique' => '手机号码已存在',
                    'card_id.unique' => '身份证号码已存在',
                    'card_id.regex' => '身份证号码不合法',
                ];
                $data = [
                    'phone' => $params['phone'],
                    'card_id'  => $params['card_id']
                ];
                $validate = Validate::make($rule,$msg);
                $result = $validate->check($data);
                if(!$result)
                {
                    return ['status'=>false,'msg'=>$validate->getError()];
                }
                Db::startTrans();
                try{

                    //添加赊账申请人
                    $params['business'] = $params['image']['business'];
                    $params['mall_id'] = $areaId;
                    $params['status'] = 1;
                    $params['created_at'] = date("Y-m-d H:i:s");
                    $this->AreaCreditListModel->insert($params);
                    Db::commit();
                    return ['status'=>true,'msg'=>'添加成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'添加失败'];
                }
            }
        }

        //初始用户
        $buyer = $this->getBuyerList();
        $this->assign('buyer',$buyer);

        return $this->fetch();
    }


    /**
     * 赊账用户申请过审
     * @param string $id
     * @return mixed
     */
    public function creditEdit($id='')
    {
        $this->view->engine->layout(false);

        $id = isset($id) ? $id : $this->request->get('id');

        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限操作'
            ];
        }

        if($this->request->isPost())
        {
            $params = $this->request->post();

            //更新一审状态
            if(!empty($params['buyer_id']) && !empty($params['mall_id']))
            {
                Db::startTrans();
                try{
                    //根据提交状态完成更新
                    $data = [
                        'schedule' => $params['schedule'],
                        'updated_at' => date("Y-m-d H:i:s"),
                    ];

                    if($params['schedule'] == 3)
                    {
                        $data['status'] = 2;
                    }

                    $this->AreaCreditListModel->allowField(true)->isUpdate(true)->save($data,['mall_id'=>$params['mall_id'],'buyer_id'=>$params['buyer_id']]);
                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }

//            if($params['status'] == 2){
//                //更新申请列表
//                $datas = [
//                    'status' => $params['status'],
//                    'updated_at' => date("Y-m-d H:i:s"),
//                ];
//                $this->AreaCreditListModel->allowField(true)->isUpdate(true)->save($datas,['mall_id'=>$params['mall_id'],'buyer_id'=>$params['buyer_id']]);
//                return ['status'=>true,'msg'=>'操作成功'];
//            }
//
//            if($params['actual_money'] <= 0) return ['status'=>false,'msg'=>'金额不能小于或者等于0'];

//            if(!empty($params['buyer_id']) && !empty($params['mall_id']))
//            {
//                Db::startTrans();
//                try{
//                    //更新申请列表
//                    $datas = [
//                        'status' => $params['status'],
//                        'updated_at' => date("Y-m-d H:i:s"),
//                    ];
//
//                    $this->AreaCreditListModel->allowField(true)->isUpdate(true)->save($datas,['mall_id'=>$params['mall_id'],'buyer_id'=>$params['buyer_id']]);
//
//                    //写入用户赊账余额
//                    $credit = [
//                        'mall_id' => $params['mall_id'],
//                        'buyer_id' => $params['buyer_id'],
//                        'balance' => $params['actual_money'],
//                        'remark' => '审批赊账申请',
//                        'created_at' => date("Y-m-d H:i:s"),
//
//                    ];
//                    $this->AreaCreditModel->insert($credit,true);
//                    //写入申请记录流水
//                    $data = [
//                        'mall_id'   => $params['mall_id'],
//                        'buyer_id'  => $params['buyer_id'],
//                        'type'      => 1,
//                        'price'     => $params['actual_money'],
//                        'balance'   => $params['actual_money'],
//                        'remark'    => '审批赊账',
//                        'created_at' => date("Y-m-d H:i:s")
//                    ];
//                    $this->AreaCreditFlowModel->insert($data);
//                    Db::commit();
//                    return ['status'=>true,'msg'=>'操作成功'];
//                }catch (\Exception $e){
//                    Db::rollback();
//                    return ['status'=>false,'msg'=>'操作失败'];
//                }
//            }

        }
        $find = $this->AreaCreditListModel->where('id',$id)->find();
        if(empty($find)) return ['status'=>false,'msg'=>'没有可查询的赊账信息'];
        $this->assign('find',$find);
        return $this->fetch();
    }

    /**
     * 二审操作
     * @param $id
     * @return array|mixed
     */
    public function creditEditStep1($id='')
    {
        $this->view->engine->layout(false);
        $id = isset($id) ? $id : $this->request->get('id');
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限操作'
            ];
        }

        //审核操作
        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {
                Db::startTrans();
                try{
                    //根据提交状态完成更新
                    $data = [
                        'schedule' => $params['schedule'],
                        'remark' => $params['remark'],
                        'updated_at' => date("Y-m-d H:i:s"),
                    ];

                    if($params['schedule'] == 6)
                    {
                        $data['status'] = 2;
                    }
                    $this->AreaCreditListModel->allowField(true)->isUpdate(true)->save($data,['mall_id'=>$params['mall_id'],'buyer_id'=>$params['buyer_id']]);

                    //申请信息
                    $infoFind = $this->AreaCreditListModel->where(['mall_id'=>$params['mall_id'],'buyer_id'=>$params['buyer_id']])->find();


                    //二审操作同意的话生成协议合同pdf并上传七牛云
                    $pdf = $this->createPdf($infoFind);

                    Db::commit();
                    return ['status'=>true,'msg'=>'操作成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'操作失败'];
                }
            }
        }

        $find = $this->AreaCreditListModel->where('id',$id)->find();
        if(empty($find)) return ['status'=>false,'msg'=>'没有可查询的赊账信息'];
        $this->assign('find',$find);
        return $this->fetch();

    }


    /**
     * 三审操作
     * @param string $id
     * @return array|mixed
     */
    public function creditEditStep2($id='')
    {
        $this->view->engine->layout(false);
        $id = isset($id) ? $id : $this->request->get('id');
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限操作'
            ];
        }

        if($this->request->isPost())
        {
            $params = $this->request->param();
            if($params)
            {

                if($params['actual_money'] <= 0) return ['status'=>false,'msg'=>'金额不能小于或者等于0'];


                $flow = [];

                if(!empty($params['buyer_id']) && !empty($params['mall_id']))
                {
                    Db::startTrans();
                    try{

                        //根据提交状态完成更新
                        $data = [
                            'schedule' => $params['schedule'],
                            'actual_money' => $params['actual_money'],
                            'remark' => $params['remark'],
                            'updated_at' => date("Y-m-d H:i:s"),
                        ];

                        if($params['schedule'] == 9)
                        {
                            $data['status'] = 2;
                        }else{
                            $data['status'] = 3;
                        }

                        $this->AreaCreditListModel->allowField(true)->isUpdate(true)->save($data,['mall_id'=>$params['mall_id'],'buyer_id'=>$params['buyer_id']]);

                        //写入用户赊账余额
                        $credit = [
                            'mall_id' => $params['mall_id'],
                            'buyer_id' => $params['buyer_id'],
                            'balance' => $params['actual_money'],
                            'remark' => '审批赊账申请',
                            'created_at' => date("Y-m-d H:i:s"),

                        ];
                        $this->AreaCreditModel->insert($credit);
                        //写入流水
                        $flow = [
                            'mall_id' => $params['mall_id'],
                            'buyer_id' => $params['buyer_id'],
                            'type' => 1,
                            'price' => isset($params['actual_money']) ? $params['actual_money'] : 0.00,
                            'balance' => isset($params['actual_money']) ? $params['actual_money'] : 0.00,
                            'remark' => '审批赊账',
                            'created_at' => date("Y-m-d H:i:s"),
                        ];
                        $this->AreaCreditFlowModel->insert($flow,true);
                        Db::commit();
                        return ['status'=>true,'msg'=>'操作成功'];
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                }

            }
        }

        $find = $this->AreaCreditListModel->where('id',$id)->find();
        if(empty($find)) return ['status'=>false,'msg'=>'没有可查询的赊账信息'];
        $this->assign('find',$find);
        return $this->fetch();
    }

    /**
     * 创建PDF
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function createPdf($data)
    {
        //金额转大写
        $money = cny($data['apply_money']);
        //搭建生成模型
        $html =<<<EOD
<style>
    .a{
        text-align: center;
    }
</style>
<div>
<h1 class="a">信用赊购合同</h1>
<p>赊购单位：<u>　    {$data['company']}　  </u>(以下简称甲方)</p>
<p>法定代表人：<u>　    {$data['username']}　  </u></p>
<p>通讯地址：<u>　    {$data['address']}　  </u></p>
<p><b>佩祥平台方</b>：<u> 常州佩祥电子商务有限公司 </u>(以下简称乙方)</p>
<p>代理人：______________</p>
<p>通讯地址：_______________________________________</p>
<p>   为了建立甲乙双方长期合作关系和明确责任，经甲乙双方充分协商一致，特订立本合同，以便双方共同遵守。</p>
<p><b>第 1 条：佩祥平台</b></p>
<p>1.1：佩祥平台：指乙方（中华人民共和国增值电信业务经营许可证，经营许可证编码号：苏B2-20190272）基于微信小程序“佩祥”的网上商城业务平台，以下简称平台。</p>
<p>1.2：甲方在下单平台上指定以下账号（微信号 <u>　    {$data['wx_code']}　  </u> 关联手机号 <u>　    {$data['phone']}　  </u> ）为唯一的下单、结算和其他操作账号。 </p>
<p>1.3：甲方对平台的各项规定、注意事项和提示已经完全理解并认可，通过甲方账号在平台的各项下单、结算、对账、接受电子文书和其他操作均予以认可。</p>
<p><b>第 2 条：下单</b></p>
<p>2.1：甲方按照平台的下单须知、商品详情、服务政策下单。</p>
<p>2.2：甲方在平台上下单的订单号为该笔交易的唯一编号。</p>
<p><b>第 3 条：订单服务和验收 </b></p>
<p>3.1：根据甲方订单的相关信息及要求，乙方按时完成订单服务后平台自动更新甲方订单状态。</p>
<p>3.2：甲方应在订单状态更新后7个自然日内，在平台的订单中点击“确认收货”。</p>
<p>3.3：如甲方对订单服务有异议，应在3.2规定的7个自然日内通过平台提出异议。上述时间内甲方未向乙方提出异议，则视为甲方对此笔订单无异议，平台自动更新此笔订单“已完成”。</p>
<p><b>第 4 条：订单的赊购结算 </b></p>
<p>4.1：甲方在平台商誉赊账，本合同签署生效后7个工作内乙方通过平台为甲方账号预充值赊购额度  <u>{$data['apply_money']}</u>  万元，大写          <u>{$money}</u>             。</p>
<p>4.2：对账单确认：每月10日前平台自动在甲方账号对账单入口生成上月对账单，甲方按以下流程在每月15之前完成对账单确认：在平台对账单入口下载对账单--核对确认（若有异议在平台提出）---打印---盖章签字---拍照上传至平台----邮寄至乙方---乙方收件后在平台确认完成。甲方逾期对账视为无异议，乙方有权停止平台服务。</p>
<p>4.3：还款：甲方每月20日前将上月对账单确认的赊购金额汇入乙方指定银行账号。乙方收到甲方还款后，根据还款金额恢复甲方账号在平台的相应赊购额度。如甲方未能按期足额还款，乙方有权停止提供平台服务，未还款部分甲方自愿按日利率1‰（千分之一）计算逾期利息。逾期还款超过30日的，乙方有权自行解除本合同。解除合同的通知发送至1.2条甲方指定账号即生效，逾期利息计算至甲方实际结清欠款之日止。</p>
<p><b>第 5 条：连带担保责任 </b></p>
<p>5.1：担保人：    <u>{$data['surety_name']}</u>   身份证号：   <u>{$data['surety_card']}</u>                                  手机号：  <u>{$data['surety_phone']}</u>     </p>
<p>5.2：：担保人个人自愿承担本合同项下甲方欠款及利息、违约金、损害赔偿金和实现债权费用的连带担保责任，担保期限为主债务履行期届满之日起三年。</p>
<p><b>第 6 条：合同生效 </b></p>
<p>6.1：本合同经双方签字并盖章以及担保人签字按手印后生效。</p>
<p>6.2：本合同一式三份，甲乙双方各执一份，担保人一份。甲方应提供公司营业执照复印件盖章，代理人、 法定代表人、担保人身份证复印件。</p>
<p><b>第 7 条：合同变更和解除 </b></p>
<p>7.1：若甲方需对合同相关事宜变更调整，甲方通过平台合同变更入口进行变更申请，乙方确认同意后，变更约定作为本合同附件执行。</p>
<p>7.2：乙方在合同履行中有权提前解除本合同，解除合同的通知提前30日发送至1.2条甲方指定账号即生效。</p>
<p>7.3：甲方应当于合同解除后5个自然日内结清赊购商品的货款，预充值初始赊购额度未使用金额由乙方清零。逾期还款的，甲方自愿按日利率1‰（千分之一）计算逾期利息，逾期利息计算至甲方实际结清欠款之日止。</p>
<p><b>第 8 条：其他 </b></p>
<p>8.1：<b>本合同和附件条款是双方逐条讨论后商定，均深刻了解其内容和含义。</b></p>
<p>8.2：如因履行本合同发生纠纷，双方协商不成的，任何一方均可直接向常州市钟楼区人民法院提起诉讼。诉讼费、律师费、保全费、差旅费、公证费、评估费、鉴定费、公告费等实现债权的费用均由违约方承担。</p>
<p>8.3：乙方账户：常州佩祥电子商务有限公司 <br>　　开 户 行：江苏江南农村商业银行股份有限公司常州市新闸支行 <br>　　账号：8980 1133 0120 1000 0002 882
</p>
<div style="height:8px"></div>
<div>
<p>甲方（公章）：</p>
<p>法人（代理人）：</p>
<p>联系方式：</p>
</div>
<div>
<p>乙方（公章）：</p>
<p>法人（代理人）：</p>
<p>联系方式：</p>
</div>
<div>
<p>担保人：</p>
<p>右手拇指：</p>
<p>联系方式：</p>
</div>
<p>日期：&nbsp;&nbsp;&nbsp;&nbsp;年&nbsp;&nbsp;&nbsp;&nbsp;月&nbsp;&nbsp;&nbsp;&nbsp;日</p>
</div>
</div>
EOD;

        $name = '平台赊账签押合同'.date("Ymds").'_'.$data['username'].'.pdf';
        $filename  = ROOT_PATH . 'public'.DS.$name;
//https://images.pxjiancai.com http://www.pxjiancai.com
        $pdf = pdf($html, $filename, 'https://images.pxjiancai.com', '常州佩祥电子商务有限公司', 'F');

        $_FILES['pdf']['id'] = fileowner($pdf);
        $_FILES['pdf']['name'] = $pdf;
        $_FILES['pdf']['error'] = 0;
        $_FILES['pdf']['tmp_name'] = 'C:\Users\Lyunp\AppData\Local\Temp\phpEF7D.tmp';
        $_FILES['pdf']['type'] = 'image/png';
        $_FILES['pdf']['lastModifiedDate'] = date('r', filemtime($pdf));
        $_FILES['pdf']['size'] = filesize($pdf);

        $filePath = $name;
        $info = uploadPdf($name,$filePath);
        if($info)
        {
            //删除之前创建好的pdf文件，已不用
            clearn_file(ROOT_PATH . 'public'.DS,'pdf');
            if($info['data'] != '')
            {
                //将返回的数据更新到数据库
                $this->AreaCreditListModel->allowField(true)->isUpdate(true)->save(['buyer_pdf_url'=>$info['data']],['mall_id'=>$data['mall_id'],'buyer_id'=>$data['buyer_id']]);

            }

            return $info;
        }

        //删除之前创建好的pdf文件，已不用
        clearn_file(ROOT_PATH . 'public'.DS,'pdf');

        return [];
    }


    /**
     * 详情查看
     * @param string $id
     * @return array|mixed
     */
    public function creditDetail($id='')
    {
        $this->view->engine->layout(false);

        $id = isset($id) ? $id : $this->request->get('id');

        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限操作'
            ];
        }

        $find = $this->AreaCreditListModel->where('id',$id)->find();

        $this->assign('find',$find);
        return $this->fetch();
    }


    /**
     * 删除
     * @param string $id
     * @return array
     */
    public function del($id='')
    {
        $id = isset($id) ? $id : $this->request->get('id');
        if($id)
        {
            $find = $this->AreaCreditListModel->where('id',$id)->find();
            if(!empty($find))
            {
                Db::startTrans();
                try{
                    $this->AreaCreditListModel->where('id',$id)->delete();

                    $this->AreaCreditModel->where(['buyer_id'=>$find['buyer_id'],'mall_id'=>$find['mall_id']])->delete();

                    $this->AreaCreditFlowModel->where(['buyer_id'=>$find['buyer_id'],'mall_id'=>$find['mall_id']])->delete();

                    Db::commit();
                    return ['status'=>true,'msg'=>'删除成功'];
                }catch (PDOException $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'删除失败'];
                }
            }

        }
        return [];
    }

    /**
     * 批量审核
     * @param string $ids
     */
    public function creditPassMulti($ids='')
    {
        $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');
        if($ids)
        {
            $count = 0;
            $credit =[];
            $flow = [];
            Db::startTrans();
            try {
                $list = $this->AreaCreditListModel->where($this->AreaCreditListModel->getPk(), 'in', $ids)->select();
                foreach ($list as $index => $item) {
                    if($item['status'] == '3')
                    {
                        return ['status'=>false,'msg'=>"其中包含审核通过的，请从新选择"];
                    }
                    $count += $item->allowField(true)->isUpdate(true)->save(['status'=>3]);
                    //写入账户
                    $credit = [
                        'buyer_id'=>$item['buyer_id'],
                        'mall_id'=>$item['mall_id'],
                        'balance'=> $item['apply_money'],
                        'remark'=> '审批赊账申请',
                        'created_at'=> date("Y-m-d H:i:s"),
                    ];
                    $this->AreaCreditModel->insert($credit,true);
                    //写入流水
                    $flow = [
                        'mall_id' => $item['mall_id'],
                        'buyer_id' => $item['buyer_id'],
                        'type' => 1,
                        'price' => $item['apply_money'],
                        'balance' => $item['apply_money'],
                        'remark' => '审批赊账',
                        'created_at' => date("Y-m-d H:i:s"),
                    ];
                    $this->AreaCreditFlowModel->insert($flow,true);
                }

                Db::commit();
                return ['status'=>true,'msg'=>'审核成功'];
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

        }
        return [];
    }

    public function creditBlackMulti($ids='')
    {
        $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');
        if($ids)
        {
            $count = 0;
            Db::startTrans();
            try {
                $list = $this->AreaCreditListModel->where($this->AreaCreditListModel->getPk(), 'in', $ids)->select();

                foreach ($list as $index => $item) {
                    $count += $item->allowField(true)->isUpdate(true)->save(['isdel'=>1]);
                }
                Db::commit();
                return ['status'=>true,'msg'=>'拉黑成功'];
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

        }
        return [];
    }

    /**
     * 取消拉黑
     * @param string $ids
     */
    public function creditUpBlackMulti($ids='')
    {
        $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');
        if($ids)
        {
            $count = 0;
            Db::startTrans();
            try {
                $list = $this->AreaCreditListModel->where($this->AreaCreditListModel->getPk(), 'in', $ids)->select();

                foreach ($list as $index => $item) {
                    $count += $item->allowField(true)->isUpdate(true)->save(['isdel'=>0]);
                }
                Db::commit();
                return ['status'=>true,'msg'=>'取消成功'];
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

        }
        return [];
    }

    public function creditDelMulti($ids='')
    {
        $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');
        if($ids)
        {
            $count = 0;
            Db::startTrans();
            try {
                $list = $this->AreaCreditListModel->where($this->AreaCreditListModel->getPk(), 'in', $ids)->select();

                foreach ($list as $index => $item) {
                    $count += $item->allowField(true)->isUpdate(true)->destroy($ids);
                    //删除账户
                    $this->AreaCreditModel->where(['buyer_id'=>$item['buyer_id'],'mall_id'=>$item['mall_id']])->delete();
                    //删除流水
                    $this->AreaCreditFlowModel->where(['buyer_id'=>$item['buyer_id'],'mall_id'=>$item['mall_id']])->delete();
                }
                //删除流水
                Db::commit();
                return ['status'=>true,'msg'=>'删除成功'];
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

        }
    }
    
    //----------------------------------------------------------------------------

    /**
     * 获取用户列表
     * @return mixed
     */
    public function getBuyerList()
    {
        return $this->buyerModel->select();
    }


    /**
     * 赊账授权用户列表
     * @return mixed|\think\response\Json
     */
    public function creditRootIndex()
    {

        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }

        if($this->request->isAjax())
        {
            $params = $this->request->param();
            $page = $this->request->get("page", 0);
            $limit = $this->request->get("limit", 0);

            list($page,$limit) = [$page,$limit];
            $list = $this->AreaCreditRootModel
//                ->where()
                ->order('id asc')
                ->page($page,$limit)
                ->select();
            $total = $this->AreaCreditRootModel
//                ->where()
                ->order('id asc')
                ->count();

            $status = ['0'=>"<a style='color: #6ce26c'>未授权</a>",'1'=>"<a style='color: red'>已授权</a>",'2'=>"<a style='color: #0C0C0C'>已停权</a>"];
            foreach ($list as $key=>$val) {
                $list[$key]['status'] = $status[$val['status']];
                $list[$key]['buyerName'] = $this->getBuyerFind($val['buyer_id'])['buyer_name']."({$val['buyer_id']})";
            }
            
            $result = array("code"=>0,"count" => $total, "data" => $list);

            return json($result);

        }
        return $this->fetch();
    }

    /**
     * 开通授权
     * @return array|mixed
     */
    public function creditRootAdd()
    {
        $this->view->engine->layout(false);

        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限操作'
            ];
        }
        //post提交
        if($this->request->isPost())
        {
            $params = $this->request->post();
            if($params)
            {
                Db::startTrans();
                try{
                    $data = [
                        'buyer_id' => $params['buyer_id'],
                        'mall_id' => $userWhere['mall_id'],
                        'status' => $params['status'],
                        'created_at' => date("Y-m-d H:i:s"),
                    ];
                    $this->AreaCreditRootModel->insert($data);
                    Db::commit();
                    return ['status'=>true,'msg'=>'授权成功'];
                }catch (\Exception $e){
                    Db::rollback();
                    return ['status'=>false,'msg'=>'授权失败'];
                }
            }
            return [];
        }

        //初始用户
        $buyer = $this->getBuyerList();
        $this->assign('buyer',$buyer);

        return $this->fetch();
    }


    /**
     * 授权用户编辑
     * @return mixed
     */
    public function creditRootEdit($id="")
    {
        $this->view->engine->layout(false);

        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限操作'
            ];
        }

        $id = $id ? $id : $this->request->param('id');

        if($this->request->isPost())
        {
            $params = $this->request->post();
            Db::startTrans();
            try{
                $data = [
                    'status' => $params['status'],
                    'updated_at' => date("Y-m-d H:i:s"),
                ];
                $this->AreaCreditRootModel->allowField(true)->isUpdate(true)->save($data,['buyer_id'=>$params['buyer_id']]);
                Db::commit();
                return ['status'=>true,'msg'=>'授权成功'];
            }catch (\Exception $e){
                Db::rollback();
                return ['status'=>false,'msg'=>'授权失败'];
            }
        }

        if($id)
        {
            $find = $this->AreaCreditRootModel->where('id',$id)->find();
            $find['name'] = $this->getBuyerFind($find['buyer_id'])['buyer_name'];
            $this->assign('find',$find);
        }

        return $this->fetch();
    }

    /**
     * 根据用户ID获取用户信息
     * @param $buyerId
     * @return mixed
     */
    public function getBuyerFind($buyerId)
    {
        return $this->buyerModel->where('buyer_id',$buyerId)->find();
    }

    /**
     * 授权删除
     * @return array
     */
    public function delRoot()
    {
        if($this->request->isGet())
        {
            $id = $this->request->get('id');
            if($id)
            {
                $row = $this->AreaCreditRootModel->where('id',$id)->delete();
                if($row)
                {
                    return ['status'=>true,'msg'=>'删除成功'];
                }else{
                    return ['status'=>false,'msg'=>'删除失败'];
                }
            }
        }

        return [];
    }
    
    /**
     * 赊账授权批量删除
     */
    public function creditRootDelMulti($ids="")
    {
        $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');
        if($ids)
        {
            $count = 0;
            Db::startTrans();
            try {
                $list = $this->AreaCreditRootModel->where($this->AreaCreditRootModel->getPk(), 'in', $ids)->select();

                foreach ($list as $index => $item) {
                    $count += $item->allowField(true)->isUpdate(true)->destroy($ids);
                }
                Db::commit();
                return ['status'=>true,'msg'=>'删除成功'];
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

        }

    }

    /**
     * 赊账授权批量拉黑
     */
    public function creditRootBlockMulti($ids="")
    {
        $ids = $ids ? rtrim($ids,',') : rtrim($this->request->param("ids"),',');
        if($ids)
        {
            $count = 0;
            Db::startTrans();
            try {
                $list = $this->AreaCreditRootModel->where($this->AreaCreditRootModel->getPk(), 'in', $ids)->select();

                foreach ($list as $index => $item) {
                    $count += $item->allowField(true)->isUpdate(true)->save(['status'=>2]);
                }
                Db::commit();
                return ['status'=>true,'msg'=>'拉黑成功'];
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }

        }
    }


    /*********************************延期审核**********************************/

    /**
     * 延期申请列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function creditPostpone()
    {
		 $siteCount = count((new CreditPostpone())
		       ->select());
		 $this->assign('siteCount',$siteCount);
		 $mallCount = count((new AreaMallCreditPostpone())
		       ->select());
		 $this->assign('mallCount',$mallCount);
        //获取权限的筛选条件
        $userWhere = $this->getMyUserWhere();
        $areaId = isset($userWhere[self::PERMISSION_MALL]) ? $userWhere[self::PERMISSION_MALL] : 0;
        if(empty($areaId)){
            return [
                'status' => false,
                'msg' => '只有区域管理员才有权限打开此界面'
            ];
        }
        if($this->request->isAjax())
        {
            $page = $this->request->get("page", 0);
            $limit = $this->request->get("limit", 0);
            $creditType = $this->request->get("creditType", 'mall');

            $type = ['1'=>'延期还款','2'=>'部分还款','3'=>'全部还款'];
            $status = ['1'=>'等待审核','2'=>'通过审核','3'=>'审核拒绝'];
            //默认店铺为首
            if($creditType == 'mall')
            {
                $list = (new AreaMallCreditPostpone())
                    ->where(['mall_id'=>$areaId])
                    ->page($page,$limit)
                    ->select();
                $total = (new AreaMallCreditPostpone())
                    ->where(['mall_id'=>$areaId])
                    ->count();
                foreach ($list as $k=>$item) {
                    $credit = $this->AreaCreditListModel->where(['mall_id'=>$item['mall_id'],'buyer_id'=>$item['buyer_id']])->field('username')->find();
                    $list[$k]['buyer_name'] = $credit['username'];
                    $list[$k]['type']   = $type[$item['type']];
                    $list[$k]['status'] = $status[$item['status']];
                }
                $result = array("code"=>0,"count" => $total, "data" => $list);
            }

            if($creditType == 'site')
            {
                $list = (new CreditPostpone())
                    ->where(['mall_id'=>$areaId])
                    ->page($page,$limit)
                    ->select();
                $total = (new CreditPostpone())
                    ->where(['mall_id'=>$areaId])
                    ->count();

                foreach ($list as $k=>$item) {
                    $credit = (new Credit())->where(['site_id'=>$item['site_id'],'buyer_id'=>$item['buyer_id']])->field('username')->find();
                    $list[$k]['buyer_name'] = $credit['username'];
                    $list[$k]['type']   = $type[$item['type']];
                    $list[$k]['status'] = $status[$item['status']];
                }
			
                $result = array("code"=>0,"count" => $total, "data" => $list);
            }

            return json($result);
        }
		
        return $this->fetch();
    }
  
  
    public function creditPostponeEdit($ids=null,$creditType=null)
    {
        $this->view->engine->layout(false);
        $id = isset($ids) ? $ids : $this->request->get('id');
        $creditType = isset($creditType) ? $creditType : 'mall';
        if($creditType == 'mall') //平台
        {  
            if($this->request->isPost()){
              
                $param = $this->request->param();
				$userMoney = Db::table('ev_confirm_statem')->where('buyer_id',$param['buyer_id'])->find();//查询用户总的赊账

				if($param['schedule'] == 2){
					if($param['number'] == $param['portion_price'] && !empty($param['desc'])){
						 
					    if($param['type'] == 1){ //延期还款
						    if($userMoney['total'] >= $param['number']){
							
								$countMoney = $userMoney['total']-$param['number'];
								$list = (new AreaMallCreditPostpone())::get($param['id']);
								$list->postpone_year_month_date = $param['postpone_day_date'];
								$list->status = $param['schedule'];
								$list->save();//修改延迟还款时间
								
								$data = Db::name('area_mall_credit_list')->where('buyer_id',$param['buyer_id'])->update(['exp_time' =>$param['postpone_day_date']]);
								
								$Money = Db::name('confirm_statem')->where('buyer_id',$param['buyer_id'])->update(['total' =>  $countMoney,'end_time'=>$param['postpone_day_date']]);	
													
								$log = $this->credit_postpone_log($param['id'],$param['desc']);						
							    return 	["status"=>true,"msg" => "延期还款审核通过"];
							}else{
								$list = (new AreaMallCreditPostpone())::get($param['id']);
								$list->status = 3;
								$list->save();//修改延迟还款时间
							
								return 	["status"=>true,"msg" => "延期还款未审核通过"];
							}
							
						   
					    }else if($param['type'] == 2){ //部分支付
	
					 	    if($param['number'] == $userMoney['total']){//还款金额与赊账金额相等
							
							    $countMoney =$userMoney['total']-$param['number'];
							    $list = (new AreaMallCreditPostpone())::get($param['id']);
							    $list->portion_apply_price = $param['number'];
								$list->status = $param['schedule'];
							    $list->save();//确认部分还款审核金额
							    
								$data = Db::name('confirm_statem')->where('buyer_id',$param['buyer_id'])->update(['total' =>  $countMoney]);
							    
								$log = $this->credit_postpone_log($param['id'],$param['desc']);
							    return 	["status"=>true,"msg" => "全部还款成功"];
						    }
						    if($param['number'] < $userMoney['total']){
							    $countMoney =$userMoney['total']-$param['number'];
							    $list = (new AreaMallCreditPostpone())::get($param['id']);
							    $list->portion_apply_price = $param['number'];
							    $list->status = $param['schedule'];
							    $list->save();//确认部分还款审核金额
								
							    $data = Db::name('area_mall_credit_list')->where('buyer_id',$param['buyer_id'])->update(['exp_time' =>$param['postpone_day_date']]);
							    $data = Db::name('confirm_statem')->where('buyer_id',$param['buyer_id'])->update(['total' =>  $countMoney,'end_time'=>$param['postpone_day_date']]);
									
							    $log = $this->credit_postpone_log($param['id'],$param['desc']);
							    return 	["status"=>true,"msg" => "还款成功"];
							}
							if($param['number'] > $userMoney['total']){

							   return 	["status"=>true,"msg" => "还款金额有误"];
							}
							
					    }else if($param['type'] == 3){ //全部还款
					        if($param['number'] == $userMoney['total']){
								$countMoney =$userMoney['total']-$param['number'];
								$list = (new AreaMallCreditPostpone())::get($param['id']);
								$list->portion_apply_price = $param['number'];
								$list->status = $param['schedule'];
								$list->save();//确认还款金额
								
								 
								$Money = Db::name('confirm_statem')->where('buyer_id',$param['buyer_id'])->update(['total' =>  $countMoney]);
									
								$log = $this->credit_postpone_log($param['id'],$param['desc']);	
								return 	["status"=>true,"msg" => "全部还款"];
						    }else{
								return 	["status"=>true,"msg" => "还款金额有误"];
							}
					    }
					 
				    }else{
						 return ["status"=>true,"msg" => "审核内容请填写完整"];
					}
				}
				if($param['schedule'] == 3){
				    $list = (new AreaMallCreditPostpone())::get($param['id']);
				   	$list->status = $param['schedule'];
				   	$list->save();//修改通过一审状态
					
					$log = $this->credit_postpone_log($param['id'],$param['desc']);
				   return 	["status"=>true,"msg" => "拒绝还款项目"];
				}else{
					  return 	["status"=>true,"msg" => "请选择进度"];
				}
			  
             
            }
				$credit = (new AreaMallCreditPostpone())::get($id);
				$this->assign('credit',$credit); 

        }else{
            if($this->request->isPost()){
          
                $params = $this->request->param();
				// var_dump($params);die;
				$userMoney = Db::table('ev_confirm_statem')->where('buyer_id',$params['buyer_id'])->find();//查询用户总的赊账
				
				if($params['schedule'] == 2){//审核进度状态
				
					if($params['number'] == $params['portion_price']){//还款金额是否相等
						 
					   if($params['type'] == 1){ //延期还款
					        if($userMoney['total'] >= $params['number']){
								echo 1;die;
							    $list = (new CreditPostpone())::get($params['id']);
								$list->postpone_year_month_date = $params['postpone_day_date'];
								$list->status = $params['schedule'];
								$list->save();//修改延迟还款时间
								  
								$data = Db::name('credit_list') ->where('buyer_id',$params['buyer_id'])->update(['exp_time' => $time]);
								 				
								$Money = Db::name('confirm_statem')->where('buyer_id',$params['buyer_id'])->update(['total' =>  $countMoney]);
									
								$log = $this->credit_postpone_log($params['id'],$params['desc']);					 
								return 	["status"=>true,"msg" => "延期还款审核通过"];
							}
					 	  
						   
					 }else if($params['type'] == 2){ //部分支付

					 	    if($params['number'] == $userMoney['total']){//判断还款金额与赊账金额相等
							    $countMoney =$userMoney['total']-$params['number'];
							    $list = (new CreditPostpone())::get($params['id']);
							    $list->portion_apply_price = $params['number'];
								$list->status = $params['schedule'];
							    $list->save();//修改还款
							    
								$data = Db::name('confirm_statem')->where('buyer_id',$params['buyer_id'])->update(['total' =>  $countMoney]);
								
								$log = $this->credit_postpone_log($params['id'],$params['desc']);
							    return 	["status"=>true,"msg" => "全部还款成功"];
							}
							if($params['number'] < $userMoney['total']){ 
							    $countMoney =$userMoney['total']-$params['number'];
							    $list = (new CreditPostpone())::get($params['id']);
							    $list->portion_apply_price = $params['number'];
							    $list->status = $params['schedule'];
							    $list->save();//确认部分还款审核金额
							   
							    $data = Db::name('credit_list') ->where('buyer_id',$params['buyer_id'])->update(['exp_time' => $time]);
							   
							    $data = Db::name('confirm_statem')->where('buyer_id',$params['buyer_id'])->update(['total' =>  $countMoney]);
								   
							    $log = $this->credit_postpone_log($params['id'],$params['desc']);
							   return 	["status"=>true,"msg" => "还款成功"];
							}
							if($params['number'] > $userMoney['total']){
							    $list = (new CreditPostpone())::get($params['id']);
							    $list->status = 3;
							    $list->save();//默认拒绝审核
								
							    return 	["status"=>true,"msg" => "还款金额有误"];
							}
							
					 }else if($params['type'] == 3){ //全部还款
					    	if($params['number'] == $userMoney['total']){
								$countMoney =$userMoney['total']-$params['number'];
								$list = (new CreditPostpone())::get($params['id']);
								$list->portion_apply_price = $params['number'];
								$list->status = $params['schedule'];
								$list->save();//确认部分还款审核金额
								 
								$data = Db::name('confirm_statem')->where('buyer_id',$params['buyer_id'])->update(['total' =>  $countMoney]);
								
								$log = $this->credit_postpone_log($params['id'],$params['desc']);
								return 	["status"=>true,"msg" => "全部还款"];
							}else{
								return  ["status"=>true,"msg" => "还款金额有误"];
							}
					    }
				       }else{
						
						return ["status"=>true,"msg" => "申请金额和还款金额不一致"];
					   }
				}
				if($params['schedule'] == 3){
					    $list = (new CreditPostpone())::get($params['id']);
						$list->status = $params['schedule'];
						$list->save();//修改通过一审状态
						
                        $log = $this->credit_postpone_log($params['id'],$params['desc']);
					    return  ["status"=>true,"msg" => "拒绝申请还款"];
			    }
				else{
					    return  ["status"=>true,"msg" => "请选择进度"];
				}
            }  
                $credit = (new CreditPostpone())::get($id);
                $this->assign('credit',$credit);
        }
                $this->assign('creditType',$creditType);
                return $this->fetch();
         

    }
	
	//赊账延期或部分支付日志
	public function   credit_postpone_log($credit_id,$credit_desc){
		$log_id  = Db::table('ev_area_mall_credit_postpone_log')//查询日记表
			->order('id','desc') // 不会传入后面的查询
			->find();//查询用户总的赊账
	    $credit_at = date("Y-m-d H:i:s");//当前时间
			if(empty($log_id['id'])){
				$data = ['id'=>1,'postpone_id'=>$credit_id,'desc'=>$credit_desc,'created_at'=>$credit_at,'updated_at'=>$credit_at];
				$code = Db::name('area_mall_credit_postpone_log')->data($data)->insert();
				
				return $code;
			}else{
				$id = $log_id['id']+1;
				$data = ['id'=>$id,'postpone_id'=>$credit_id,'desc'=>$credit_desc,'created_at'=>$credit_at,'updated_at'=>$credit_at];
				$code = Db::name('area_mall_credit_postpone_log')->data($data)->insert();
				
				return $code;
			}
		
	}


    /**
     * @param $buyerId
     * @param $changeAmount
     * @return mixed
     */
    public function changeMyBalance($buyerId,$changeAmount){

        $buyerBalanceIns = $this->where('buyer_id',$buyerId)->find();
        $buyerBalanceIns->balance += $changeAmount;
        $buyerBalanceIns->save();

        return $buyerBalanceIns;
    }

}