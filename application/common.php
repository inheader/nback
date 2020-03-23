<?php
// 应用公共文件
use think\Config;
use think\Cookie;
use think\Db;
use think\Debug;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Lang;
use think\Loader;
use think\Log;
use think\Model;
use think\Request;
use think\Response;
use think\Session;
use think\Url;
use think\View;
use think\Container;
use app\common\model\Operation;
use app\common\model\Area;
use app\common\model\Payments;
use app\common\model\Logistics;
use think\facade\Cache;
use Qiniu\Auth as Auth;
use Qiniu\Storage\UploadManager;
//毫秒数
//返回当前的毫秒时间戳
function msectime() {
    list($tmp1, $tmp2) = explode(' ', microtime());
    return sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0,$adv=false) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

//判断前端浏览器类型
function get_client_broswer(){
    $ua = $_SERVER['HTTP_USER_AGENT'];

    //微信内置浏览器
    if (stripos($ua, 'MicroMessenger')) {
        //preg_match('/MicroMessenger\/([\d\.]+)/i', $ua, $match);
        return "weixin";
    }
    //支付宝内置浏览器
    if (stripos($ua, 'AlipayClient')) {
        //preg_match('/AlipayClient\/([\d\.]+)/i', $ua, $match);
        return "alipay";
    }
    return false;
}
//生成编号
function get_sn($type){
    switch ($type)
    {
        case 1:         //订单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 2:         //支付单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 3:         //商品编号
            $str = 'G'.substr(msectime().rand(0,5),1);
            break;
        case 4:         //货品编号
            $str = 'P'.substr(msectime().rand(0,5),1);
            break;
        case 5:         //售后单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 6:         //退款单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 7:         //退货单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        case 8:         //发货单编号
            $str = $type.substr(msectime().rand(0,9),1);
            break;
        default:
            $str = substr(msectime().rand(0,9),1);
    }
    return $str;
}


/**
 * 获取hash值
 * @return string
 */
function get_hash()
{
    $chars   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';
    $random  = $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)] . $chars[mt_rand(0,73)];
    $content = uniqid() . $random;
    return sha1($content);
}

/**
 * @param $filename
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 11:32
 */
function get_file_extension($filename)
{
    $pathinfo = pathinfo($filename);
    return strtolower($pathinfo['extension']);
}

/***
 * 获取HASH目录
 * @param $name
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 15:26
 */
function get_hash_dir($name='default')
{
    $ident = sha1(uniqid('',true) . $name . microtime());
    $dir   = DIRECTORY_SEPARATOR . $ident{0} . $ident{1} . DIRECTORY_SEPARATOR . $ident{2} . $ident{3} . DIRECTORY_SEPARATOR . $ident{4} . $ident{5} . DIRECTORY_SEPARATOR;
    return $dir;
}

/**
 *
 * +--------------------------------------------------------------------
 * Description 递归创建目录
 * +--------------------------------------------------------------------
 * @param  string $dir 需要创新的目录
 * +--------------------------------------------------------------------
 * @return 若目录存在,或创建成功则返回为TRUE
 * +--------------------------------------------------------------------
 * @author gongwen
 * +--------------------------------------------------------------------
 */
function mkdirs($dir,$mode = 0777)
{
    if(is_dir($dir) || mkdir($dir,$mode,true)) return true;
    if(!mkdirs(dirname($dir),$mode)) return false;
    return mkdir($dir,$mode,true);
}


/**
 * 返回图片地址
 * TODO 水印，裁剪，等操作
 * @param $image_id
 * @param $type
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 18:34
 */
function _sImage($image_id,$type='s')
{
    if(!$image_id){
        return config('jshop.default_image');//默认图片
    }
    $image_obj= new \app\common\model\Images();
    $image=$image_obj->where(array(
        'id'=>$image_id
    ))->field('url')->find();
    if($image){
        if(stripos($image['url'],'http')!==false||stripos($image['url'],'https')!==false) {
            return str_replace("\\","/",$image['url']);
        }else{
            return request()->domain().str_replace("\\","/",$image['url']);
        }
    }else{
        return request()->domain().'/'.config('jshop.default_image');//默认图片
    }
}


/**
 * 返回Products_id
 * TODO 水印，裁剪，等操作
 * @param $image_id
 * @param $type
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-01-09 18:34
 */
function _sProducts($goods_id,$type='s')
{
    if(!$goods_id){
        return config('jshop.default_image');//默认图片
    }
    $produ_obj = new \app\common\model\Products();
    $produ_id = $produ_obj->where('goods_id',$goods_id)->field('id')->find();
    if($produ_id && $produ_id['id']){
        // 产品id
        return $produ_id['id'];
    }
    
}

/**
 * 相对地址转换为绝对地址
 */
function getRealUrl($url='')
{
    if(stripos($url,'http')!==false||stripos($url,'https')!==false) {
        return $url;
    }else{
        if(config('jshop.image_storage.domain')){
            return config('jshop.image_storage.domain').$url;
        }
        return request()->domain().$url;
    }
}


function checkPhoneNumberValidate($mobile){
    //@2017-11-25 14:25:45 https://zhidao.baidu.com/question/1822455991691849548.html
    //中国联通号码：130、131、132、145（无线上网卡）、155、156、185（iPhone5上市后开放）、186、176（4G号段）、175（2015年9月10日正式启用，暂只对北京、上海和广东投放办理）,166,146
    //中国移动号码：134、135、136、137、138、139、147（无线上网卡）、148、150、151、152、157、158、159、178、182、183、184、187、188、198
    //中国电信号码：133、153、180、181、189、177、173、149、199
    $g = "/^1[34578]\d{9}$/";
    $g2 = "/^19[89]\d{8}$/";
    $g3 = "/^166\d{8}$/";
    if(preg_match($g, $mobile)){
        return true;
    }else  if(preg_match($g2, $mobile)){
        return true;
    }else if(preg_match($g3, $mobile)){
        return true;
    }
    return false;

}

/**
 * 格式化数据化手机号码
 */
function format_mobile($mobile)
{
    return substr($mobile,0,5)."****".substr($mobile,9,2);
}

//如果没有登陆的情况下，记录来源url，并跳转到登陆页面
function redirect_url()
{
    if(cookie('?redirect_url')){
        $str = cookie('redirect_url');
        cookie('redirect_url',null);
    }else{
        $str = '/';
    }
    return $str;
}
//返回用户信息
function get_user_info($user_id,$field = 'mobile')
{
    $user = app\common\model\User::get($user_id);
    if($user){
        if($field == 'nickname') {
            $nickname = $user['nickname'];
            if ($nickname == '') {
                $nickname = format_mobile($user['mobile']);
            }
            return $nickname;
        }else{
            return $user->$field;
        }
    }else{
        return "";
    }
}
//返回商品信息
function get_goods_info($goods_id,$field = 'name')
{
    $goodsModel = new \app\common\model\Goods();
    $info = $goodsModel->where(['id'=>$goods_id])->find();
    if($info){
        if($field == 'image_id'){
            return _sImage($info[$field]);
        }else{
            return $info[$field];
        }
    }else{
        return '';
    }
}
//返回用户信息
function get_user_id($mobile)
{
    $userModel = new app\common\model\User();
    $user = $userModel->where(array('mobile'=>$mobile))->find();
    if($user){
        return $user->id;
    }else{
        return false;
    }
}

/**
*todo:zhangjing
 * 根据operation_id 取得链接地址
 * @param $id
 * @return string|Url
 */
function get_operation_url($id)
{
    //由于效率慢，这里加上缓存
    $operationModel = new Operation();

    $actionInfo = $operationModel->getCacheInsForId($id);
    if (!$actionInfo) {
        return "";
    }
    $controllerInfo = $operationModel->getCacheInsForId($actionInfo['parent_id']);
    if (!$controllerInfo) {
        return "";
    }

    return url($controllerInfo['code'] . '/' . $actionInfo['code']);
}

/**
 * 获取转换后金额
 * @param int $money
 * @return string
 * User: wjima
 * Email:1457529125@qq.com
 * Date: 2018-02-01 15:32
 */
function getMoney($money = 0)
{
    return sprintf("%.2f", $money);
}

//根据支付方式编码取支付方式名称等
function get_payment_info($payment_code,$field = 'name')
{
    $paymentModel = new Payments();
    $paymentInfo = $paymentModel->where(['code'=>$payment_code])->find();
    if($paymentInfo){
        return $paymentInfo[$field];
    }else{
        return $payment_code;
    }
}

//根据物流编码取物流名称等信息
function get_logi_info($logi_code,$field='logi_name')
{
    $logisticsModel = new Logistics();
    $logiInfo = $logisticsModel->where(['logi_code'=>$logi_code])->find();
    if($logiInfo){
        return $logiInfo[$field];
    }else{
        return $logi_code;
    }
}

/**
 * 根据地区id取省市区的信息
 * @param $area_id
 * @return string
 */
function get_area($area_id)
{
    $areaModel = new Area();
    $data = $areaModel->getArea($area_id);
    $parse = "";
    foreach($data as $v){
        if(isset($v['info'])){
            $parse .= $v['info']['name']." ";
        }
    }
    return $parse;
}
function error_code($code,$mini = false)
{
    $result = [
        'status' => false,
        'data' => 10000,
        'msg' => config('error.10000')
    ];
    if(config('?error.'.$code)){
        $result['data'] = $code;
        $result['msg'] = config('error.'.$code);
    }
    if($mini){
        return $result['msg'];
    }else{
        return $result;
    }
}

/**
 * 删除数组中指定值
 * @param $arr
 * @param $value
 * @return mixed
 */
function unsetByValue($arr, $value){
    $keys = array_keys($arr, $value);
    if(!empty($keys)){
        foreach ($keys as $key) {
            unset($arr[$key]);
        }
    }
    return $arr;
}

/**
 * 删除图片
 * @param $image_id
 * @return bool
 */
function delImage($image_id){
    $image_obj= new \app\common\model\Images();
    $image = $image_obj->where(['id'=>$image_id])->find();
    if($image){
        //删除图片数据
        $res = $image_obj->where(['id'=>$image_id])->delete();
        if($image['type']=='local'){
            $dd = @unlink($image['path']);
        }
        if($res){
            return true;
        }
        //默认本地存储，返回本地域名图片地址
    }else{
        return false;
    }
}

/**
 * 查询标签
 * @param $ids
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getLabel($ids)
{
    if(!$ids){
        return [];
    }
    $label_obj = new \app\common\model\Label();
    $labels = $label_obj->field('name,style')->where('id', 'in', $ids)->select();
    if (!$labels->isEmpty()) {
        return $labels->toArray();
    }
    return [];
}

/**
 * 查询首页推荐
 * @param $ids
 * @param $authType
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getPageClassList($ids,$authType)
{
    if(!$ids){
        return [];
    }
    $label_obj = new \app\common\model\PageGoodsClass();
    $labels = $label_obj->field('name,style')->where('id', 'in', $ids);
    //区域管理员
    if($authType==2){
        $labels = $labels->where('site_id', 0);
    }
    //店铺管理员
    if($authType==3){
        $labels = $labels->where('mall_id', 0);
    }

    $labels = $labels->select();

    if (!$labels->isEmpty()) {
        return $labels->toArray();
    }
    return [];
}

function getLabelStyle($style){
    $label_style='';
    switch ($style) {
        case 'red':
            $label_style = "";
            break;
        case 'green':
            $label_style = "layui-bg-green";
            break;
        case 'orange':
            $label_style = "layui-bg-orange";
            break;
        case 'blue':
            $label_style = "layui-bg-blue";
            break;
        default :
            $label_style = '';
    }
    return $label_style;
}

/* 单位自动转换函数 */
function getRealSize($size)
{
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    if($size < $kb)
    {
        return $size . 'B';
    }
    else if($size < $mb)
    {
        return round($size/$kb, 2) . 'KB';
    }
    else if($size < $gb)
    {
        return round($size/$mb, 2) . 'MB';
    }
    else if($size < $tb)
    {
        return round($size/$gb, 2) . 'GB';
    }
    else
    {
        return round($size/$tb, 2) . 'TB';
    }
}

/**
 * url参数转换为数组
 * @param $query
 * @return array
 */
function convertUrlQuery($query)
{
    $queryParts = explode('&', $query);
    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
    return $params;
}

/**
 * bool型转义
 * @param string $value
 * @return mixed
 */
function getBool($value='1'){
    $bool = ['1'=>'是','2'=>'否'];
    return $bool[$value];
}

/**
 * 时间格式化
 * @param int $time
 * @return false|string
 */
function getTime($time = 0){
    return date('Y-m-d H:i:s',$time);
}

/**
 * 标签转换
 * @param array $labels
 * @return string
 */
function getExportLabel($labels = []){
    $labelString = '';
    foreach((array)$labels as $v){
        $labelString = $v['name'].',';
    }
    return substr($labelString,0,-1);
}

/**
 * 上下架状态转换
 * @param string $status
 * @return string
 */
function getMarketable($marketable='1'){
    $status = ['1'=>'上架','2'=>'下架'];
    return $status[$marketable];
}



/**
 * 数组转xml
 * @param $arr
 * @param string $root
 * @return string
 */
function arrayToXml($arr, $root = "xml")
{
    $xml = "<" . $root . ">";
    foreach ($arr as $key => $val) {
        if (is_array($val)) {
            $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        }
    }
    $xml .= "</" . $root . ">";
    return $xml;
}

/**
 * 在模板中，有时候，新增的时候，要设置默认值
 * @param $val
 * @param $default
 * @return mixed
 */
function setDefault($val,$default)
{
    return $val?$val:$default;
}


/**
 * xml转数组
 * @param $xml
 * @return mixed
 */
function xmlToArray($xml)
{
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 * 判断url是否内网ip
 * @param string $url
 * @return bool
 */
function isIntranet($url = '')
{
    $params = parse_url($url);
    $host = gethostbynamel($params['host']);
    if (is_array($host)) {
        foreach ($host as $key => $val) {
            if (!filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * 获取微信操作对象（单例模式）
 * @staticvar array $wechat 静态对象缓存对象
 * @param type $type 接口名称 ( Card|Custom|Device|Extend|Media|Oauth|Pay|Receive|Script|User )
 * @return \Wehcat\WechatReceive 返回接口对接
 */
function & load_wechat($type = '',$appid = '') {
    if(!$appid){
        return false;
    }

    if($appid){
        static $wechat = array();
        $index = md5(strtolower($type));
        if (!isset($wechat[$index])) {
            // 从数据库获取配置信息
            $options = array(
                'token'           => $thirdwx['token'], // 填写你设定的key
                'appid'           => $appInfo['appid'], // 填写高级调用功能的app id, 请在微信开发模式后台查询
                'appsecret'       => $appInfo['appsecret'], // 填写高级调用功能的密钥
                'encodingaeskey'  => $thirdwx['encrypt_key'], // 填写加密用的EncodingAESKey（可选，接口传输选择加密时必需）
                'access_token'    => isset($appInfo['authorizer_access_token'])?$appInfo['authorizer_access_token']:'', // 第三方授权
                'mch_id'          => isset($appInfo['mch_id'])?$appInfo['mch_id']:'', // 微信支付，商户ID（可选）
                'partnerkey'      => isset($appInfo['partnerkey'])?$appInfo['partnerkey']:'', // 微信支付，密钥（可选）
                'ssl_cer'         => isset($appInfo['ssl_cer'])?$appInfo['ssl_cer']:'', // 微信支付，双向证书（可选，操作退款或打款时必需）
                'ssl_key'         => isset($appInfo['ssl_key'])?$appInfo['ssl_key']:'', // 微信支付，双向证书（可选，操作退款或打款时必需）
                'cachepath'       => isset($appInfo['cachepath'])?$appInfo['cachepath']:'', // 设置SDK缓存目录（可选，默认位置在Wechat/Cache下，请保证写权限）
            );
            \Wechat\Loader::config($options);
            $wechat[$index] = \Wechat\Loader::get($type);
        }
        return $wechat[$index];
    }
    else{
        return false;
    }
}

/**
 * 获取最近天数的日期和数据
 * @param $day
 * @param $data
 * @return array
 */
function get_lately_days($day, $data)
{
    $day = $day-1;
    $days = [];
    $d = [];
    for($i = $day; $i >= 0; $i --)
    {
        $d[] = date('d', strtotime('-'.$i.' day')).'日';
        $days[date('Y-m-d', strtotime('-'.$i.' day'))] = 0;
    }
    foreach($data as $v)
    {
        $days[$v['day']] = $v['nums'];
    }
    $new = [];
    foreach ($days as $v)
    {
        $new[] = $v;
    }
    return ['day' => $d, 'data' => $new];
}

/**
 * 判断用户积分是否满足
 * @param $user_id
 * @param $seller_id
 * @param $num //正整数
 * @return array
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function is_user_point($user_id, $seller_id, $num)
{
    $sellerUser = new \app\common\model\SellerUser();
    $res = $sellerUser->isPointBalance($user_id, $seller_id, $num);
    return $res;
}

/**
 * 商家发送信息助手
 * @param $seller_id
 * @param $user_id
 * @param $code
 * @param $params
 * @return array
 */
function sendMessage($user_id, $code, $params)
{
    $messageCenter = new \app\common\model\MessageCenter();
    return $messageCenter->sendMessage($user_id, $code, $params);
}


/**
 * 根据商户id和用户id获取openid
 * @param $seller_id
 * @param $user_id
 * @return bool|array
 */
function getUserWxInfo($user_id)
{
    $wxModel = new \app\common\model\UserWx();
    $filter[] = ['user_id','eq',$user_id];
    $wxInfo = $wxModel->field('id,user_id,openid,unionid,avatar,nickname')->where($filter)->find();
    if($wxInfo){
        return $wxInfo->toArray();
    }else{
        return false;
    }
}



/**
 * 判断用户是否有新消息，用于前端显示小红点
 * @param $user_id
 * @param int $seller_id
 */
function hasNewMessage($user_id)
{
    $messageModel = new \app\common\model\Message();
    $re = $messageModel->hasNew($user_id);
    return $re;
}

//格式化银行卡号，前四位和最后显示原样的，其他隐藏
function bankCardNoFormat($cardNo){
    $n = strlen($cardNo);
    //判断尾部几位显示原型
    if($n%4 == 0){
        $j = 4;
    }else{
        $j = $n%4;
    }
    $str = "";
    for($i=0;$i<$n;$i++){
        if($i <4 || $i> $n-$j-1){
            $str .= $cardNo[$i];
        }else{
            $str .= "*";
        }
        if($i%4 == 3){
            $str .=" ";
        }
    }
    return $str;
}

/**
 * 获取系统设置
 * @param string $key
 * @return array
 */
function getSetting($key = ''){
    $systemSettingModel = new \app\common\model\Setting();
    return $systemSettingModel->getValue($key);
}

/***
 * 获取插件配置信息
 * @param string $name 插件名称
 * @return array
 */
function getAddonsConfig($name = ''){
    if(!$name){
        return [];
    }
    $addonModel = new \app\common\model\Addons();
    return $addonModel->getSetting($name);
}
//货品上的多规格信息，自动拆分成二维数组
function getProductSpesDesc($str_spes_desc){
    if($str_spes_desc == ""){
        return [];
    }
    $spes = explode(',',$str_spes_desc);
    $re = [];
    foreach($spes as $v){
        $val = explode(':',$v);
        $re[$val[0]] = $val[1];
    }
    return $re;
}

//返回用户信息
function get_manage_info($manage_id,$field = 'username')
{
    $user = app\common\model\Manage::get($manage_id);
    if($user){
        if($field == 'nickname') {
            $nickname = $user['nickname'];
            if ($nickname == '') {
                $nickname = format_mobile($user['mobile']);
            }
            return $nickname;
        }else{
            return $user->$field;
        }
    }else{
        return "";
    }
}

/**
 * 数组倒排序，取新的键
 * @param array $array
 * @return array
 */
function _krsort($array = [])
{
    krsort($array);
    if (is_array($array)) {
        $i          = 0;
        $temp_array = [];
        foreach ($array as $val) {
            $temp_array[$i] = $val;
            $i++;
        }
        return $temp_array;
    } else {
        return $array;
    }
}

/**
 * 判断钩子是否有插件
 * @param string $hookname
 * @return bool
 */
function checkAddons($hookname = '')
{
    $hooksModel = new \app\common\model\Hooks();
    $addons     = $hooksModel->where(['name' => $hookname])->field('addons')->find();
    if (isset($addons['addons']) && !empty($addons['addons'])) {
        return true;
    } else {
        return false;
    }
}

    //生成pdf
    function pdf($html='',$name='',$host='',$footer='',$type='')
    {

        require_once('../vendor/TCPDF-master/tcpdf.php');

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION,PDF_UNIT,PDF_PAGE_FORMAT,true,'UTF-8',false);
        //设置打印模式
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('常州佩祥');
        $pdf->SetSubject('常州佩祥');
        $pdf->SetKeywords('常州佩祥电子商务有限公司');
        // 是否显示页眉
        $pdf->setPrintHeader(false);
        // 设置页眉显示的内容
        $pdf->SetHeaderData('', 60, $host, $footer, array(0,64,255), array(0,64,128));
        // 设置页眉字体
        $pdf->setHeaderFont(Array('dejavusans', '', '12'));
        // 页眉距离顶部的距离
        $pdf->SetHeaderMargin('5');
        // 是否显示页脚
        $pdf->setPrintFooter(true);
        // 设置页脚显示的内容
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        // 设置页脚的字体
        $pdf->setFooterFont(Array('dejavusans', '', '10'));
        // 设置页脚距离底部的距离
        $pdf->SetFooterMargin('10');
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // 设置行高
        $pdf->setCellHeightRatio(1);
        // 设置左、上、右的间距
        $pdf->SetMargins('10', '10', '10');
        // 设置是否自动分页  距离底部多少距离时分页
        $pdf->SetAutoPageBreak(TRUE, '15');
        // 设置图像比例因子
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->setFontSubsetting(true);
        $pdf->AddPage();
        // 设置字体
        $pdf->SetFont('stsongstdlight', '', 14, '', true);
//        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->lastPage();

        $pdf->Output($name,$type);

    }

    /**
     * 七牛文件上传
     * @param $key
     * @param $filePath
     * @return array
     * @throws Exception
     */
    function uploadPdf($key,$filePath)
    {
        require_once('../vendor/php-sdk-master/autoload.php');

        $config = config('jshop.image_storage');
        $auth = new Auth($config['accessKey'], $config['secretKey']);
        $token = $auth->uploadToken($config['bucket']);

        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            return ["err"=>1,"msg"=>$err,"data"=>""];
        } else {
            //返回图片的完整URL
            return ["err"=>0,"msg"=>"上传完成","data"=>getRealUrl($config['domain'] . $ret['key'])];
        }
    }


    //金额转汉字大写
    function cny($ns){
        static $cnums = array("零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖");
        $cnyunits = array("圆", "角", "分");
        $grees = array("拾", "佰", "仟", "万", "拾", "佰", "仟", "亿");

        $_cny_map_unit = function ($list, $units){
            $ul = count($units);
            $xs = array();
            foreach (array_reverse($list) as $x) {
                $l = count($xs);
                if ($x != "0" || !($l % 4)) $n = ($x == '0' ? '' : $x) . ($units[($l - 1) % $ul]);
                else $n = is_numeric($xs[0][0]) ? $x : '';
                array_unshift($xs, $n);
            }
            return $xs;
        };

        list($ns1, $ns2) = explode(".", $ns, 2);
        $ns2 = array_filter(array($ns2[1], $ns2[0]));
        $ret = array_merge($ns2, array(implode("", $_cny_map_unit(str_split($ns1), $grees)), ""));
        $ret = implode("", array_reverse($_cny_map_unit($ret, $cnyunits)));
        return str_replace(array_keys($cnums), $cnums, $ret);
    }




    /**
     * Array中指定的键进行排序
     *  http://php.net/manual/en/function.sort.php
     * @param $array
     * @param $on
     * @param int $order
     * @return array
     */
    function array_key_sort($array, $on, $order = SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }



    /**
     *@param $path文件夹绝对路径 $file_type待删除文件的后缀名
     *return void
     */
    function clearn_file($path, $file_type = 'bak')
    {
        //判断要清除的文件类型是否合格
        if (!preg_match('/^[a-zA-Z]{2,}$/', $file_type)) {
            return false;
        }
        //当前路径是否为文件夹或可读的文件
        if (!is_dir($path) || !is_readable($path)) {
            return false;
        }
        //遍历当前目录下所有文件
        $all_files = scandir($path);
        foreach ($all_files as $filename) {
            //跳过当前目录和上一级目录
            if (in_array($filename, array(".", ".."))) {
                continue;
            }
            //进入到$filename文件夹下
            $full_name = $path . '/' . $filename;
            //判断当前路径是否是一个文件夹，是则递归调用函数
            //否则判断文件类型，匹配则删除
            if (is_dir($full_name)) {
                clearn_file($full_name, $file_type);
            } else {
                preg_match("/(.*)\.$file_type/", $filename, $match);
                if (!empty($match[0][0])) {
//                    echo $full_name;
//                    echo '<br>';
                    unlink($full_name);
                }
            }
        }
    }

/**
 * @param $url
 * @param $xml
 * @param $extras
 * @return array
 */
    function CurlPostSsl($url,$xml,$extras=[]){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch,CURLOPT_TIMEOUT,60);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        if(!empty($extras))
        {
            curl_setopt($ch,CURLOPT_SSLCERT,$extras['CURLOPT_SSLCERT']);
            curl_setopt($ch,CURLOPT_SSLKEY,$extras['CURLOPT_SSLKEY']);
        }
//        curl_setopt($ch,CURLOPT_CAINFO,$extras['CURLOPT_CAINFO']);
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }


    function random($length, $numeric = FALSE) {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        if ($numeric) {
            $hash = '';
        } else {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }



/**
 * 签名连接
 * @param array $param
 * @return string
 */
    function stringSign($param = [])
    {
        unset($param['sign']);
        ksort($param);
        $signstr = '';
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                if ($value == '') {
                    continue;
                }
                $signstr .= $key . '=' . $value . '&';
            }
            $signstr = rtrim($signstr, '&');
        }
        return $signstr;
    }

function array2xml($arr, $level = 1) {
    $s = $level == 1 ? "<xml>" : '';
    foreach ($arr as $tagname => $value) {
        if (is_numeric($tagname)) {
            $tagname = $value['TagName'];
            unset($value['TagName']);
        }
        if (!is_array($value)) {
            $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
        } else {
            $s .= "<{$tagname}>" . array2xml($value, $level + 1) . "</{$tagname}>";
        }
    }
    $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
    return $level == 1 ? $s . "</xml>" : $s;
}
