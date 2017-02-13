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
 * 支付宝
 * Class AlipayController
 * @package Notify\Controller
 */
class AlipayController extends NotifybaseController
{
    public $order_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        vendor('Alipay.RSAfunction');
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');
    }

    public function index()
    {
        $alipay_config = C('ALIPAY_CONFIG');

        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify = $alipayNotify->verifyNotify();

        if (!$verify) {
            Log::record('支付宝回调校验失败' . json_encode($alipay_config), Log::WARN);
            Log::record('支付宝回调校验失败' . json_encode(I('')), Log::WARN);
            echo 'fail';
            exit;
        }

        if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
            $data = I('post.');
            if (empty($data))
                exit('empty');

            //微信返回参数
            $order_sn = $data['out_trade_no'];
            $order_price = $data['total_fee'];

            $data = [
                'status'       => OrderModel::ORDER_PAY_SUCCESS,
                'pay_type'     => OrderModel::PAY_TYPE_ALIPAY,
                'payment_time' => time(),
                'pay_money'    => $order_price,
            ];
            $result = $this->order_model->where(['order_sn' => $order_sn])->save($data);
            $this->product_change($order_sn);
            echo 'success';
        }

    }
}