<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/25
 * Time: 14:05
 */

namespace Notify\Controller;

use Common\Model\OrderModel;
use Think\Log;


/**
 * 微信支付
 * Class WxpayController
 * @package Notify\Controller
 */
class WxpayController extends NotifybaseController
{
    public function __construct()
    {
        parent::__construct();
        header("Content-type:text/html;charset=utf-8");
        ini_set('date.timezone', 'Asia/Shanghai');
        vendor('WxPayPubHelper.WxPayPubHelper');
    }

    public function index()
    {
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //回调错误
        if (!$xml){
            Log::record('微信支付回调校验失败:' . json_encode(I('')), Log::WARN);
            return false;
        }

        $notify->saveData($xml);
        //签名状态
        $checkSign = true;
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");//返回状态码
            $notify->setReturnParameter("return_msg", "签名失败");//返回信息
            $checkSign = false;
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();


        if (!$checkSign) {
            Log::record('微信支付回调校验失败:' . json_encode(I('')), Log::WARN);
            exit;
        }

        //通知微信，成功获取到相应的异步通知
        echo $returnXml;

        //微信返回参数
        $back_data = $notify->getData();
        $order_sn = $back_data['out_trade_no']; //订单号
        $order_price = $back_data['total_fee'] / 100; //微信返回的是分，换算成元

        $data = [
            'status'       => OrderModel::ORDER_PAY_SUCCESS,
            'pay_type'     => OrderModel::PAY_TYPE_WXPAY,
            'payment_time' => time(),
            'pay_money'    => $order_price,
        ];
        $result = $this->order_model->where(['order_sn' => $order_sn])->save($data);

        $this->product_change($order_sn);


    }
}