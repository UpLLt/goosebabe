<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 11:52
 */

namespace Common\Model;


class OrderModel extends CommonModel
{
    //失效订单
    const ORDER_LOSE_EFFICACY = 1;
    //未付款 OR 待付款
    const ORDER_NOPAY = 3;
    //已支付 OR 待发货
    const ORDER_PAY_SUCCESS = 4;
    //已发货 OR 待接收
    const ORDER_PRODUCT_SEND = 5;
    //已完成 OR 待评价
    const ORDER_COMPLETE = 6;
    //用户取消
    const ORDER_CANCEL = 7;


    const PAY_TYPE_WXPAY = '1';
    const PAY_TYPE_ALIPAY = '2';

    public function getStatusValues($status, $comments)
    {
        switch ($status) {
            case self::ORDER_LOSE_EFFICACY:
                return '失效';
                break;
            case self::ORDER_NOPAY:
                return '未付款';
                break;
            case self::ORDER_PAY_SUCCESS:
                return '已付款';
                break;
            case self::ORDER_PRODUCT_SEND:
                return '已发货';
                break;
            case self::ORDER_COMPLETE:
                return ($comments ? '已完成' : '待评价');
                break;
            case self::ORDER_CANCEL:
                return '用户取消';
                break;
            default :
                return 'status undefined';
                break;
        }
    }

    /**
     * 获取
     * @param $key
     * @return int
     */
    public function getStatusByKey($key)
    {
        switch ($key) {
            case 'nopay':
                return self::ORDER_NOPAY;
                break;
            case 'paysuccess':
                return self::ORDER_PAY_SUCCESS;
                break;
            case 'reception':
                return self::ORDER_PRODUCT_SEND;
                break;
            case 'comment':
                return self::ORDER_COMPLETE;
                break;
            case 'all':
                return array(self::ORDER_LOSE_EFFICACY, self::ORDER_NOPAY, self::ORDER_PAY_SUCCESS, self::ORDER_PRODUCT_SEND, self::ORDER_COMPLETE);
                break;
            default :
                return 'key undefined';
                break;
        }
    }


    /**
     * 查询有效订单
     * @param $mid
     * @param $order_sn
     * @return mixed
     */
    public function getValidityOrder($mid, $order_sn)
    {
        return $this
            ->where(array(
                'mid' => $mid,
                'order_sn' => $order_sn,
                'status' => self::ORDER_NOPAY
            ))->find();
    }



}