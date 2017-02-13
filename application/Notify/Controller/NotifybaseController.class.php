<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/25
 * Time: 14:04
 */

namespace Notify\Controller;


use Common\Model\MemberModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Think\Controller;
use Think\Log;

/**
 * 回调订单处理业务逻辑
 * Class NotifybaseController
 * @package Notify\Controller
 */
class NotifybaseController extends Controller
{
    public $order_model;
    public $order_product_model;
    public $product_model;
    public $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->product_model = new ProductModel();
        $this->member_model = new MemberModel();
    }


    /**
     * 商品
     * 库存变更
     * 销量变更
     */
    public function product_change($order_sn)
    {
        if (!$order_sn) return false;
        $order_data = $this->order_model->where(['order_sn' => $order_sn])->find();
        $order_product_data = $this->order_product_model->where(['order_id' => $order_data['id']])->select();
        $iscommit = true;
        $this->order_model->startTrans();

        foreach ($order_product_data as $k => $v) {
            //销量+quantity
            if ($this->product_model->where(['id' => $v['product_id']])->save(['sales_volume' => ['exp', 'sales_volume+' . $v['quantity']], 'inventory' => ['exp', 'inventory-' . $v['quantity']]]) === false)
                $iscommit = false;
        }

        if ($iscommit) {
            $this->order_model->commit();
            return true;
        } else {
            $this->order_model->rollback();
            return false;
        }
    }

}