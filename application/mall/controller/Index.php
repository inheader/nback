<?php
namespace app\Mall\controller;

use app\common\controller\Mall;
use app\common\model\BillAftersales;
use app\common\model\Operation;
use app\common\model\Order;
use app\common\model\OrderCommon;
use app\common\model\OrderReturn;
use think\facade\Cache;
use think\Console;
use app\common\model\WeixinAuthor;
use app\common\model\Goods;
use app\common\model\Brand;


class Index extends Mall
{
    public function index()
    {
        $userWhere = $this->getMyUserWhere();
        
        // 获取权限类别
        $authType = $this->getMyAuthType();

        // 待支付数量
        $orderModel = new OrderCommon();
        $unpaid_count = $orderModel->where($userWhere)->where('order_state', OrderCommon::PENDING_PAYMENT)->count();

        // 待发货数量
        $orderModel = new OrderCommon();
        $unship_count = $orderModel->where($userWhere)->where('order_state', OrderCommon::PENDING_DELIVERY)->count();

        // 待售后数量
        $orderReturnModel = new OrderReturn();
        $afterSales_count = $orderReturnModel->where($userWhere)->where('status','in',[1,6])->count();

        $this->assign('unpaid_count',$unpaid_count);
        $this->assign('unship_count',$unship_count);
        $this->assign('after_sales_count',$afterSales_count);

        // 修改注释
        // $goodsModel = new Goods();
        // $goodsStatics=$goodsModel->staticGoods($userWhere);
        // $this->assign('goods_statics',$goodsStatics);

        //是否关闭弹窗
        $closeauthor = Cache::get("closeauthor",'false');
        $this->assign('closeauthor',$closeauthor);
        $this->assign('authType',$authType);
        hook('adminindex', $this);//后台首页钩子

        return $this->fetch();
    }


    public function welcome(){
        $userWhere = $this->getMyUserWhere();
        
        // 获取权限类别
        $authType = $this->getMyAuthType();

        // 待支付数量
        $orderModel = new OrderCommon();
        $unpaid_count = $orderModel->where($userWhere)->where('order_state', OrderCommon::PENDING_PAYMENT)->count();

        // 待发货数量
        $orderModel = new OrderCommon();
        $unship_count = $orderModel->where($userWhere)->where('order_state', OrderCommon::PENDING_DELIVERY)->count();

        // 待售后数量
        $orderReturnModel = new OrderReturn();
        $afterSales_count = $orderReturnModel->where($userWhere)->where('status','in',[1,6])->count();

        $this->assign('unpaid_count',$unpaid_count);
        $this->assign('unship_count',$unship_count);
        $this->assign('after_sales_count',$afterSales_count);

        $goodsModel   = new Goods();
        $goodsStatics = $goodsModel->staticGoods();
        $this->assign('goods_statics', $goodsStatics);
        hook('adminindex', $this);//后台首页钩子
        return $this->fetch('welcome');
    }

    
    /**
     * 清除整站全部缓存
     * 如果其它地方写了缓存的读写方法，一定要有判断是否有缓存的情况！！！
     */
    public function clearCache()
    {
        Cache::clear();//TODO 如果开启其他缓存，记得这里要配置缓存配置信息
        Console::call('clear', ['--cache', '--dir']);//清除缓存文件
        Console::call('clear', ['--path', ROOT_PATH . '\\runtime\\temp\\']); //清除模板缓存
        $this->success('清除缓存成功', 'index/welcome');
    }


    /**
     * 供tag标签选择品牌的时候使用
     */
    public function tagSelectBrands()
    {
        $this->view->engine->layout(false);
        if(input('param.type') != 'show'){
            $request = input('param.');
            $brandModel = new Brand();
            return $brandModel->tableData($request);
        }else{
            return $this->fetch('tagselectbrands');
        }

    }
    /**
     * 供tag标签选择商品的时候使用
     */
    public function tagSelectGoods()
    {
        $this->view->engine->layout(false);
        if(input('param.type') != 'show'){
            $request = input('param.');
            $goodModel = new Goods();
            $request['marketable'] = $goodModel::MARKETABLE_UP;     //必须是上架的商品
            return $goodModel->tableData($request);

        }else{
            return $this->fetch('tagselectgoods');
        }

    }

}
