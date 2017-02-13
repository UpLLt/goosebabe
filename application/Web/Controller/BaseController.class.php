<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/6
 * Time: 15:12
 */

namespace Web\Controller;


use Think\Controller;
use Think\Model;

/**
 * 接口基类
 * Class ApibaseController
 * @package Appapi\Controller
 */
class BaseController extends Controller
{
    const SYSTEM_BUSY = 100; //系统繁忙，请求超时
    const REQUEST_SUCCESS = 200; //请求成功
    const FATAL_ERROR = 210; //存在逻辑错误(mark:描述问题)
    const TOKEN_ERROR = 220; //token无效
    const MISS_PARAM = 300; //缺少参数（mark:描述问题）
    const REQUEST_NO_POWER = 403; //权限不足
    const INVALID_INTERFACE = 404; //无效接口
    const SERVER_INTERNAL_ERROR = 404; //服务器内部错误



    public function __construct()
    {
        parent::__construct();
        $this->lo_str();
    }

    private function requeryModel()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            return 'IOS';
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
            return 'Android';
        } else {
            return 'Other';
        }
    }

    /**
     * 检查token
     * @param $token
     * @return bool
     */
    public function checktoken($mid, $token)
    {
        $result = D('member')
            ->field('id,token,token_end_time')
            ->find($mid);
        if (!$result) return false;
        $m_token = $result['token'];
        if ($m_token != $token) return false;
        if (time() > $result['token_end_time']) return false;
        return true;
    }

    public  function checkmid($mid){
        if(!$mid){
            exit($this->returnApiError(self::TOKEN_ERROR));
        }else{
            return true;
        }

    }

    /**
     * 返回token
     */
    public function createtoken()
    {
        return md5(md5(time() . "goosebabe" . rand(10, 99)));
    }


    /**
     * 返回服务器接口信息
     * @param array $data
     * @param string $token
     * @param string $code
     * $code说明
     * @return json
     */
    public function returnApiSuccess($data = array(), $token = "")
    {
        if (!count($data)) {
            $result['code'] = self::REQUEST_SUCCESS;
            $result['datas'] = array();
            return json_encode($result, true);
        }

        $data = self::recursionArrayChangeNullToNullString($data);
        $result['code'] = self::REQUEST_SUCCESS;
        if ($token) $result['token'] = $token;

        $result['datas'] = $data;
        return json_encode($result, true);
    }


    /**
     * 返回错误的信息
     * @param $code 错误代码
     * $code说明
     *      100 - 系统繁忙，请求超时
     *      200 - 请求成功
     *      210 - 存在逻辑错误(mark:描述问题)
     *      220 - token无效
     *      300 - 缺少参数（mark:描述问题）
     *      403 - 权限不足
     *      404 - 无效接口
     *      500 - 服务器内部错误
     * @return json
     */
    public function returnApiError($code, $errormsg = "", $urlencode = false)
    {
        $result['code'] = $code;
        if (!empty($errormsg)) {
            $result['mark'] = $urlencode ? urlencode($errormsg) : $errormsg;
        }
        return json_encode($result, true);
    }

    /**
     * 拼接url
     * @param $param
     * 注意格式
     * array('/Home/doc',$id)
     * @return string
     */
    public function geturl($param, $root = true)
    {
        $http_host = "http://" . $_SERVER['HTTP_HOST'];
        if ($root) $http_host .= __ROOT__;
        if (is_string($param))
            return $http_host . $param;
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (!empty($v)) {
                    if ($k != (count($param) - 1))
                        $url .= $v . '/';
                    else
                        $url .= $v;
                }
            }
            return $http_host . $url;
        }
    }


    /**
     * 拼接data/upload/目录
     * /20160707/577dbdd561ca7.png
     * @param $img
     * @return string
     */
    public function setuploadpath($img)
    {
        return '/' . C('UPLOADPATH') . $img;
    }


    /**
     * 检查参数
     * @param $param
     */
    public function checkparam(array $param, $backname = self::MISS_PARAM)
    {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (empty($v)) exit($this->returnApiError($backname));
            }
        }
    }


    /**
     * 检查参数类型必须为数字
     * @param array $param
     */
    public function checkisNumber(array $param)
    {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (!is_numeric($v)) exit($this->returnApiError(self::FATAL_ERROR, '参数类型必须为数字'));
            }
        }
    }




    /**
     * 递归数组，将value=null改变为value=""
     * @param $array
     * @return array|string
     */
    function recursionArrayChangeNullToNullString($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                if (is_array($array[$k])) {
                    $array[$k] = self::recursionArrayChangeNullToNullString($array[$k]);
                } else {
                    if ($array[$k] === null) $array[$k] = "";
                }
            }
        } else {
            if ($array === null) $array = "";
        }
        return $array;
    }


    /**
     * 返回模型操作错误
     * @param $object_db
     * @param bool $result
     * @param string $mark
     * @return array
     */
    public function getDbErrorInfo($object_db, $result = false, $mark = '失败')
    {
        return array(
            'result' => $result,
            'error' => $mark,
            'getError' => $object_db->getError(),
            'sql' => $object_db->getLastSql(),
            'getDbError' => M()->getDbError(),
        );

    }


    /**
     * 获取验证码
     * @param int $length
     * @param int $mode
     * @return string
     */
    public function get_code($length = 32, $mode = 0)//获取随机验证码函数
    {
        switch ($mode) {
            case '1':
                $str = '123456789';
                break;
            case '2':
                $str = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case '3':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case '4':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case '5':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                break;
            case '6':
                $str = 'abcdefghijklmnopqrstuvwxyz1234567890';
                break;
            default:
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
                break;
        }
        $checkstr = '';
        $len = strlen($str) - 1;
        for ($i = 0; $i < $length; $i++) {
            //$num=rand(0,$len);//产生一个0到$len之间的随机数
            $num = mt_rand(0, $len);//产生一个0到$len之间的随机数
            $checkstr .= $str[$num];
        }
        return $checkstr;
    }

    public function isIdCard($number)
    {
        $sigma = '';
        //加权因子
        $wi = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码串
        $ai = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        //按顺序循环处理前17位
        for ($i = 0; $i < 17; $i++) {
            //提取前17位的其中一位，并将变量类型转为实数
            $b = (int)$number{$i};
            //提取相应的加权因子
            $w = $wi[$i];
            //把从身份证号码中提取的一位数字和加权因子相乘，并累加 得到身份证前17位的乘机的和
            $sigma += $b * $w;
        }
        //echo $sigma;die;
        //计算序号  用得到的乘机模11 取余数
        $snumber = $sigma % 11;
        //按照序号从校验码串中提取相应的余数来验证最后一位。
        $check_number = $ai[$snumber];
        if ($number{17} == $check_number) {
            return true;
        } else {
            return false;
        }
    }

    public function getOrderNumber()
    {
        $num = $this->get_code(12, 1);
        if (D('order')->where(array('order_sn' => $num))->count() > 0) {
            return self::getOrderNumber();
        }
        return $num;
    }

    /**
     * 分页显示
     *
     */
    protected function page($total_size = 1, $page_size = 0, $current_page = 1, $listRows = 6, $pageParam = '', $pageLink = '', $static = FALSE)
    {
        if ($page_size == 0) {
            $page_size = C("PAGE_LISTROWS");
        }

        if (empty($pageParam)) {
            $pageParam = C("VAR_PAGE");
        }

        $Page = new \Page($total_size, $page_size, $current_page, $listRows, $pageParam, $pageLink, $static);
        $Page->SetPager('Admin', '{first}{prev}&nbsp;{liststart}{list}{listend}&nbsp;{next}{last}', array("listlong" => "9", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        return $Page;
    }

   private function lo_str(){
       $mid = session('mid');
       if($mid){
           $username = D('member')->where(array('id'=>$mid))->field('username')->find();

           $str = "<li><p>欢迎光临 <span>".$username['username']."</span>,<a href='".U('Web/Base/out')."'><span>退出</span></a></p></li>";
       }else{
           $str = "<li><p>欢迎光临 <span>Goosebabe</span>,请 <a href='".U('Web/Login/index')."'>登录</a>\<a href='".U('Web/Register/index')."'><span>注册</span></a></p></li>";
       }
       $this->assign('login',$str);
   }


    public function is_login(){
        $mid = session('mid');
        $result = D('member')->where(array('id'=>$mid))->field('username')->find();
        if( !$result ){
            $this->redirect('Web/Login/index',array('key'=>1));
        }
    }


    public function out(){
       session('mid',null);
       $this->redirect('Login/index', '', 0, '');
   }

}