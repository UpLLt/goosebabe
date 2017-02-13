<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 11:47
 */

namespace Appapi\Controller;


use Common\Model\AddressModel;
use Common\Model\AttrModel;
use Common\Model\CartModel;
use Common\Model\CommentModel;
use Common\Model\LogisticsModel;
use Common\Model\MemberModel;
use Common\Model\OptionModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionValueModel;
use Common\Model\ProductSkuModel;

class OrderController extends OrderBaseController
{

    private $order_model;
    private $order_product_model;
    private $pruduct_model;
    private $cart_model;
    private $address_model;
    private $member_model;
    private $product_model;
    private $comment_model;
    private $product_option_value_model;
    private $logistics_model;
    private $attr_model;
    private $option_model;
    private $product_sku_model;

    public function __construct()
    {
        parent::__construct();
        $this->cart_model = new CartModel();
        $this->pruduct_model = new ProductModel();
        $this->order_model = new OrderModel();
        $this->address_model = new AddressModel();
        $this->order_product_model = new OrderProductModel();
        $this->member_model = new MemberModel();
        $this->product_model = new ProductModel();
        $this->comment_model = new CommentModel();
        $this->product_option_value_model = new ProductOptionValueModel();
        $this->logistics_model = new LogisticsModel();
        $this->attr_model = new AttrModel();
        $this->option_model = new OptionModel();
        $this->product_sku_model = new ProductSkuModel();
    }


    /**
     * 购物车->购物车结算
     */
    public function cartSettlementBefore()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $cartids = I('post.cartid');

        $this->checkparam([$mid, $token, $cartids]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $cartid = explode(',', $cartids);
        foreach ($cartid as $k => $v) {
            if (!$this->cart_model->find($v)) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请刷新购物车'));
            }
        }

        $shop_list = [];
        $shop_list['total_price_sum'] = 0;
        $shop_list['total_tax_sum'] = 0;
        $shop_list['total'] = 0;
        $shop_list['carriage'] = 0;
        $shop_list['cartids'] = $cartids;

        //认证查询
        $member_data = $this->member_model->find($mid);
        $shop_list['card_status'] = $member_data['authentication'];
        $shop_list['card_status'] = $shop_list['card_status'] == 1 ? '已认证' : '未认证';
        $shop_list['real_name'] = $member_data['real_name'];
        $shop_list['identity_card'] = $member_data['identity_card'];


        //
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
//        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_option_value as c on a.option = c.product_option_value_id';
//        $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as d on c.option_value_id = d.option_value_id';
        $join4 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_sku as e on e.sku_id = a.option';
        foreach ($cartid as $k => $v) {
            $cart = $this->cart_model
                ->alias('a')
                ->join($join)
//                ->join($join2)
//                ->join($join3)
                ->join($join4)
                ->where(['a.id' => $v])
                ->field('a.*,b.name as product_name,b.tariff as tax,b.inventory,b.smeta,e.attr_option_path,e.price')
                ->find();

            if ($cart) {
                $smeta = json_decode($cart['smeta'], true);
                $smeta = $smeta['thumb'];
                $smeta = $smeta ? $this->geturl($smeta) : "";

                if ($cart['option_price_prefix'] == '+') {
                    $cart['price'] += $cart['option_price'];
                }

                if ($cart['option_price_prefix'] == '-') {
                    $cart['price'] -= $cart['option_price'];
                }

                $data_order_product = [
                    'cartid'            => $v,
                    'product_id'        => $cart['product_id'],
                    'name'              => $cart['product_name'],
                    'quantity'          => $cart['quantity'],
                    'option'            => $cart['option'],
                    'price'             => $cart['price'],
                    'smeta'             => $smeta,
                    'total_price'       => number_format($cart['price'] * $cart['quantity'], 2),
                    'tax'               => 0,
                    'total_tax'         => 0,
                    'option_value_name' => $cart['option_values'],
                ];

                $shop_list['total_price_sum'] += $cart['price'] * $cart['quantity'];
//                $shop_list['total_tax_sum'] += $cart['tax'] * $cart['quantity'];
//                $shop_list['total_price_sum'] = 0;

                $shop_list['total_tax_sum'] = 0;
                $shop_list['total'] = ($shop_list['total_price_sum'] + $shop_list['total_tax_sum']);
            }
            $shop_list['lists'][] = $data_order_product;
        }

        $shop_list['total_price_sum'] = number_format($shop_list['total_price_sum'], 2);
        $shop_list['total_tax_sum'] = number_format($shop_list['total_tax_sum'], 2);
        $shop_list['total'] = number_format($shop_list['total'], 2);
        $shop_list['carriage'] = number_format($carriage, 2);


        exit($this->returnApiSuccess($shop_list));
    }


    /**
     * 购物车数据检查
     * 库存判断
     * order 写入
     * order_product 写入
     */
    public function carttopay()
    {
        if (!IS_POST)
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $cartids = I('post.cartid');
        $addressid = I('post.addressid');
        $mark = I('post.mark');

        $this->checkparam([$mid, $token, $cartids, $addressid]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data_address = $this->address_model->find($addressid);
        if (!$data_address)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请选择收货地址'));

        $cartid = explode(',', $cartids);
        $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $joins1 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_sku as e on e.sku_id = a.option';
        $cart_list = $this->cart_model
            ->alias('a')
            ->join($joins)
            ->join($joins1)
            ->where(['a.id' => ['in', $cartids], 'a.mid' => $mid])
            ->field('a.*,b.name as product_name,b.tariff as tax,b.inventory,e.quantity as inventory,e.price,e.attr_option_path')
            ->select();

        foreach ($cart_list as $k => $v) {
            if ($v['inventory'] < $v['quantity']) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, $v['product_name'] . '库存不足'));
            }
        }
        if (count($cart_list) != count($cartid))
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请刷新购物车'));

        //事物 startTrans
        $ordersn = $this->getOrderNumber();
        $iscommit = true;
        $error = '';
        $this->order_product_model->startTrans();
        $data = [
            'mid'              => $mid,
            'order_sn'         => $ordersn,
            'status'           => OrderModel::ORDER_NOPAY,
            'payment_fullname' => $data_address['fullname'],
            'payment_address'  => $data_address['address'],
            'payment_phone'    => $data_address['shopping_telephone'],
            'mark'             => $mark,
            'create_time'      => time(),
            'update_time'      => time(),
            'date_day'         => date('Ymd', time()),
        ];
        $order_result = $this->order_model->add($data);
        if (!$order_result) {
            $error .= 'order_result';
            $iscommit = false;
        }
        $order_id = $this->order_model->getLastInsID();
        $total_price = 0;
        unset($v);

        foreach ($cart_list as $k => $v) {

            $option_datas = $this->option_model->where(['option_key_id' => ['in', $v['attr_option_path']]])->select();
            $option_value_name = '';
            foreach ($option_datas as $key => $val) {
                $option_value_name .= $val['option_name'];
            }

            $data_order_product = [
                'order_id'      => $order_id,
                'product_id'    => $v['product_id'],
                'name'          => $v['product_name'],
                'quantity'      => $v['quantity'],
                'option'        => $v['option'],
                'option_values' => $option_value_name,
                'price'         => $v['price'],
                'total'         => ($v['price'] + $v['tax']) * $v['quantity'],
                'tax'           => $v['tax'],
            ];

            $total_price += $v['price'] * $v['quantity'];
            $total_price += $v['tax'] * $v['quantity'];

            $order_product_result = $this->order_product_model->add($data_order_product);
            if (!$order_product_result) $iscommit = false;

            //修改库存
            $this->product_sku_model->where(['sku_id' => $v['option']])->save(['quantity' => ['exp', "quantity-" . $v['quantity']]]);

            //删除购物车中对应的商品
            $result = $this->cart_model->delete($v['id']);
            if (!$result) {
                $iscommit = false;
                $error .= '删除购物车';
            }
        }
        //运费
        //$total_price += '';

        $return = [
            'order_sn'    => $ordersn,
            'mid'         => $mid,
            'total_price' => $total_price,
        ];


        if ($iscommit) {
            $this->order_product_model->commit();
            exit($this->returnApiSuccess($return));
        } else {
            $this->order_product_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $error));
        }
    }

    /**
     * 立即购买
     */
    public function nowSettlementBefore()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $quantity = I('post.quantity');
        $option = I('post.option');

//        exit($this->returnApiError(ApibaseController::FATAL_ERROR, '维护中，交易关闭'));

        $this->checkparam([$mid, $token, $product_id, $quantity, $option]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }


//        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_option_value as c on a.option = c.product_option_value_id';
//        $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as d on c.option_value_id = d.option_value_id';


//        $join1 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as b on a.option_value_id = b.option_value_id';
//        $product_option_value = $this->product_option_value_model
//            ->join($join1)
//            ->alias('a')
//            ->where(array('product_option_value_id' => $option))
//            ->field('a.* ,b.name as option_value_name')
//            ->find();

//        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
//        $join1 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as c on a.option_value_id = c.option_value_id';
//        $product_data = $this->product_option_value_model
//            ->join($join)
//            ->join($join1)
//            ->where(array('a.product_option_value_id' => $option))
//            ->field('a.*,b.name,b.id,b.inventory,b.price as product_price,b.tax,c.name as option_name')
//            ->select();

        $product_data = $this->product_model->find($product_id);
        if (!$product_data)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));

        $product_sku_data = $this->product_sku_model->where(['sku_id' => $option, 'product_key_id' => $product_id])->find();
        if (!$product_sku_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));

        //实名认证查询//
        $member_data = $this->member_model->field('authentication,real_name,identity_card')->find($mid);
        $shop_list['card_status'] = $member_data['authentication'];
        $shop_list['card_status'] = $shop_list['card_status'] == 1 ? '已认证' : '未认证';
        $shop_list['real_name'] = $member_data['real_name'];
        $shop_list['identity_card'] = $member_data['identity_card'];

        //费用
        $shop_list['total_price_sum'] = 0;  //商品总价
        $shop_list['total_tax_sum'] = 0; //税费
        $shop_list['total'] = 0; //总价
        $shop_list['carriage'] = 0; //运费

        //图片
        $smeta = json_decode($product_data['smeta'], true);
        $smeta = $smeta['thumb'];
        $smeta = $smeta ? $this->geturl($smeta) : "";

        $option_datas = $this->option_model->where(['option_key_id' => ['in', $product_sku_data['attr_option_path']]])->select();
        $option_value_name = '';
        foreach ($option_datas as $k => $v) {
            $option_value_name .= $v['option_name'];
        }

        $data_order_product = [
            'product_id'        => $product_data['id'],
            'name'              => $product_data['name'],
            'quantity'          => $quantity,
            'option'            => $product_sku_data['sku_id'],
            'inventory'         => $product_sku_data['quantity'], //库存
            'smeta'             => $smeta,
            'price'             => $product_sku_data['price'],
            'total_price'       => number_format($product_sku_data['price'] * $quantity, 2),
            'tax'               => 0,
            'total_tax'         => 0,
            'option_value_name' => $option_value_name,
        ];
        //商品总价
        $shop_list['total_price_sum'] += $product_sku_data['price'] * $quantity;
        //关税
        $shop_list['total_tax_sum'] = 0;
        //总价
        $shop_list['total'] = $shop_list['total_price_sum'] + $shop_list['total_tax_sum'] + $shop_list['carriage'];

        $shop_list['carriage'] = number_format($shop_list['carriage'], 2);
        $shop_list['total'] = number_format($shop_list['total'], 2);
        $shop_list['lists'][] = $data_order_product;

        exit($this->returnApiSuccess($shop_list));
    }


    public function nowtopay()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $addressid = I('post.addressid');
        $quantity = I('post.quantity'); //数量
        $option = I('post.option'); //可选
        $mark = I('post.mark'); //可选


        $this->checkparam([$mid, $token, $product_id, $addressid, $quantity, $option]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data_address = $this->address_model->where(['id' => $addressid, 'mid' => $mid])->find();
        if (!$data_address) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请选择收货地址'));

        //产品查询
        $product_data = $this->product_model->find($product_id);
        if (!$product_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));

        //sku查询
        $product_sku_data = $this->product_sku_model->where(['sku_id' => $option, 'product_key_id' => $product_id])->find();
        if (!$product_sku_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));


        if ($product_sku_data['quantity'] < $quantity) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '库存不足'));
        }

        $option_datas = $this->option_model->where(['option_key_id' => ['in', $product_sku_data['attr_option_path']]])->select();
        $option_value_name = '';
        foreach ($option_datas as $k => $v) {
            $option_value_name .= $v['option_name'];
        }

        $iscommit = true;
        $this->order_model->startTrans();

//        if ($option) {
//            //修改option库存
//            $update_product_option_value_inventory = $this->product_option_value_model
//                ->where(array('product_option_value_id' => $option))
//                ->save(array("quantity" => array("exp", "quantity-" . $quantity)));
//
//            if (!$update_product_option_value_inventory) $iscommit = false;
//        } else {
//            //修改库存
//            $update_product_inventory = $this->product_model
//                ->where(array('id' => $product_id))
//                ->save(array("inventory" => array("exp", "inventory-" . $quantity)));
//            if (!$update_product_inventory) $iscommit = false;
//        }

        $error = '';

        if ($this->product_sku_model->where(['sku_id' => $option, 'product_key_id' => $product_id])->save(["quantity" => ["exp", "quantity-" . $quantity]]) === false) {
            $iscommit = false;
            $error .= '1';
        }


        $ordersn = $this->getOrderNumber();
        $data_order = [
            'mid'              => $mid,
            'order_sn'         => $ordersn,
            'status'           => OrderModel::ORDER_NOPAY,
            'payment_fullname' => $data_address['fullname'],
            'payment_address'  => $data_address['address'],
            'payment_phone'    => $data_address['shopping_telephone'],
            'mark'             => $mark,
            'create_time'      => time(),
            'update_time'      => time(),
            'date_day'         => date('Ymd', time()),
        ];
        $order_result = $this->order_model->add($data_order);
        if (!$order_result) {
            $iscommit = false;
            $error .= '2';
        }


        $order_id = $this->order_model->getLastInsID();

        $data_order_product = [
            'order_id'      => $order_id,
            'product_id'    => $product_data['id'],
            'name'          => $product_data['name'],
            'quantity'      => $quantity,
            'price'         => $product_sku_data['price'],
            'total'         => $product_sku_data['price'] * $quantity,
            'tax'           => 0,
            'option'        => $option,
            'option_values' => $option_value_name,
        ];

        $order_product_result = $this->order_product_model->add($data_order_product);
        if (!$order_product_result) {
            $iscommit = false;
            $error .= '3';
        }

        $carriage = 0;

        if ($iscommit) {
            $this->order_model->commit();
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单生成失败' . $error));
        }

        $datas = [
            'order_sn'    => $ordersn,
            'mid'         => $mid,
            'total_price' => $data_order_product['total'] + $carriage,
        ];
        exit($this->returnApiSuccess($datas));

    }


    /**
     * 统一支付下单
     */
    public function UnifiedOrder()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $out_trade_no = I('post.order_sn');
        $paytype = I('post.paytype');


//        exit($this->returnApiError(ApibaseController::FATAL_ERROR,'维护中，交易关闭'));


        $this->checkparam([$mid, $token, $out_trade_no, $paytype]);

        if ($paytype != 'wxpay' && $paytype != 'alipay') exit($this->returnApiError(ApibaseController::FATAL_ERROR, '支付类型错误'));

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }


        $order = $this->order_model->getValidityOrder($mid, $out_trade_no);
        if (!$order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在，或已支付'));

        $order_product_data = $this->order_product_model->where(['order_id' => $order['id']])->select();
        $price = 0;
        foreach ($order_product_data as $k => $v) {
            $price += ($order_product_data[$k]['price'] + $order_product_data[$k]['tax']) * $order_product_data[$k]['quantity'];
        }


        if ($paytype == 'wxpay') {

            $money_all = $price * 100;
//            $money_all = 1;

            if ($mid == 33) $money_all = 1;

            vendor('WxPayPubHelper.WxPayPubHelper');
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("body", "GOOSEBABE");//商品描述
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");  //商户订单号
            $unifiedOrder->setParameter("total_fee", $money_all);  //总金额
            $unifiedOrder->setParameter("notify_url", \WxPayConf_pub::NOTIFY_URL);  //通知地址
            $unifiedOrder->setParameter("trade_type", "APP");  //交易类型
            $unifiedOrder->setParameter("device_info", "WEB");  //设备号
            $unifiedOrder->setParameter("body", "海外购");  //商品描述

            $appparam = $unifiedOrder->getResultAppApi();
            if ($appparam) {
                $data['Appparam'] = $appparam;

            } else {
                $data['error'] = $unifiedOrder->result;

            }
        }


        if ($paytype == 'alipay') {
            vendor('Alipay.Corefunction');
            vendor('Alipay.Md5function');
            vendor('Alipay.Notify');
            vendor('Alipay.Submit');
            vendor('Alipay.RSAfunction');
            $alipay_res = new \AlipaySubmit(C('ALIPAY_CONFIG'));
            if ($mid == 33) $price = 0.01;
            $para_sort = [
                'service'        => 'mobile.securitypay.pay',
                'partner'        => C('ALIPAY_CONFIG.partner'),
                'seller_id'      => C('ALIPAY_CONFIG.partner'),
                '_input_charset' => 'utf-8',
                'notify_url'     => $this->geturl('/Notify/Alipay/index'),
                'out_trade_no'   => $out_trade_no,
                'subject'        => 'GOOSEBABE海外购',
                'body'           => 'GOOSEBABE海外购',
                'payment_type'   => '1',
                'total_fee'      => $price,
                'it_b_pay'       => '30m',
                'return_url'     => C('ALIPAY_CONFIG.return_url'),
            ];
            $pay_string = $alipay_res->buildRequestParaToString($para_sort);

//            $data['config'] = C('ALIPAY_CONFIG');
            $data['Appparam'] = $para_sort;  //pkcs8支付
            $data['pay_string'] = $pay_string;  //后端签名支付
        }


        exit($this->returnApiSuccess($data));

    }


    public function orderList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $key = I('post.key');

        $this->checkparam([$mid, $token, $key]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $ids = $this->order_model->getStatusByKey($key);
        $where = ['mid' => $mid];
        $where['hidden'] = 1;

        if (is_array($ids)) {
            $where['status'] = ['in', $ids];
        } else {
            $where['status'] = $ids;
            if ($ids == OrderModel::ORDER_COMPLETE) {
                $where['comment'] = 0;
            }
        }


        $result = $this->order_model
            ->where($where)
            ->order('id desc')
            ->field('id,order_sn,create_time,status as status_string,comment')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
//            $joins2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_option_value as c on a.option = c.product_option_value_id';
//            $joins3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as d on c.option_id = d.option_id';
            $joins4 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_sku as c on c.sku_id = a.option';


            $data_order_product = $this->order_product_model
                ->alias('a')
                ->join($joins)
//                ->join($joins2)
//                ->join($joins3)
                ->join($joins4)
                ->where(['a.order_id' => $v['id']])
                ->field('a.name,a.quantity,a.total,a.tax,a.option,b.smeta,a.product_id,c.attr_option_path,c.price')
                ->order('a.order_id desc')
                ->select();

            foreach ($data_order_product as $key => $val) {
                $smeta = json_decode($val['smeta'], true);
                $smeta = $smeta['thumb'];
                $smeta = $smeta ? $this->geturl($smeta) : '';
                $data_order_product[$key]['smeta'] = $smeta;

                $result[$k]['total_quantity'] += $val['quantity'];
                $result[$k]['total_money'] += $val['total'];

                if ($val['attr_option_path']) {
                    $option_names = $this->option_model->where(['option_key_id' => ['in', $val['attr_option_path']]])->field('option_name')->order('option_key_id asc')->select();
                    $optionname = '';
                    foreach ($option_names as $key_id => $values) {
                        $data_order_product[$key]['option_value_name'] .= ' ' . $values['option_name'];
                    }
                }
//                $data_order_product[$key]['option_value_name'] = $val['option_value_name'] ? $val['option_value_name'] : '官方标配';
            }
            $result[$k]['lists'] = $data_order_product;
            $result[$k]['status_string'] = $this->order_model->getStatusValues($v['status_string'], $v['comment']);
        }

        exit($this->returnApiSuccess($result));
    }


    /**
     * 前置条件：订单未付款
     *
     * 判断是否为未付款
     */
    public function userCancelOrder()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_sn = I('post.order_sn');

        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $where = ['order_sn' => $order_sn];
        $data_order = $this->order_model->where($where)->find();

        if (!$data_order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($data_order['status'] != OrderModel::ORDER_NOPAY) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '非法操作'));
        }

        $result = $this->order_model->where($where)->save(['status' => OrderModel::ORDER_CANCEL]);
        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '操作失败'));
        else exit($this->returnApiSuccess());

    }

    /**
     * 前置条件：订单已发货
     *
     * 收货
     */
    public function signinOrder()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_sn = I('post.order_sn');

        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $where = ['order_sn' => $order_sn];
        $data_order = $this->order_model->where($where)->find();

        if (!$data_order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($data_order['status'] != OrderModel::ORDER_PRODUCT_SEND) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '非法操作'));
        }

        $result = $this->order_model->where($where)->save(['status' => OrderModel::ORDER_COMPLETE]);
        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '操作失败'));
        else exit($this->returnApiSuccess());
    }


    /**
     * 评论列表
     */
    public function commentList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');

        $this->checkparam([$mid, $token]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';;
        $result = $this->comment_model
            ->alias('a')
            ->join($joins)
            ->where(['a.mid' => $mid, 'a.status' => 1])
            ->order('a.id desc')
            ->field('a.* , b.name as product_name,b.smeta')
            ->select();

        foreach ($result as $k => $v) {
            $smeta = json_decode($v['smeta'], true);
            $smeta = $smeta['thumb'];
            $smeta = $smeta ? $this->geturl($smeta) : '';
            $result[$k]['smeta'] = $smeta;

            $result[$k]['create_time'] = date('Y-m-d', $v['create_time']);
        }

        exit($this->returnApiSuccess($result));
    }

    /**
     * 去评论
     */
    public function gotoComment()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_sn = I('post.order_sn');

        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data_order = $this->order_model->where(['order_sn' => $order_sn])->find();
        if (!$data_order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '非法参数'));

        if ($data_order['comment'] == 1)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '已评论'));

        $order_id = $data_order['id'];

        $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';;
        $result = $this->order_product_model
            ->alias('a')
            ->join($joins)
            ->where(['a.order_id' => $order_id, 'a.comment_status' => 0])
            ->order('a.id desc')
            ->field('a.id as order_product_id ,a.product_id,b.name as product_name,b.smeta')
            ->select();

        foreach ($result as $k => $v) {
            $smeta = json_decode($v['smeta'], true);
            $smeta = $smeta['thumb'];
            $smeta = $smeta ? $this->geturl($smeta) : '';
            $result[$k]['smeta'] = $smeta;
            $result[$k]['order_sn'] = $order_sn;
        }

        exit($this->returnApiSuccess($result));
    }


    /**
     * 评价
     */
    public function commentOrder()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $content = I('post.content');
        $order_product_id = I('post.order_product_id');

        $this->checkparam([$mid, $token, $content, $order_product_id]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data_member = $this->member_model->find($mid);

        $data_order_product = $this->order_product_model->find($order_product_id);
        if (!$data_order_product) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请求的数据不存在'));

        $order_id = $data_order_product['order_id'];
        $product_id = $data_order_product['product_id'];
        $comment_status = $data_order_product['comment_status'];

        if ($comment_status == 1)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '不能重复评价'));


        $data = [
            'product_id'  => $product_id,
            'content'     => $content,
            'mid'         => $mid,
            'create_time' => time(),
            'full_name'   => $data_member['nickname'],
        ];
        //
        $result_comment = $this->comment_model->add($data);

        $result_order_product = $this->order_product_model
            ->where(['id' => $order_product_id])
            ->save(['comment_status' => 1]);

        $result = $this->order_product_model->where(['order_id' => $order_id, 'comment_status' => 0])->count();
        if ($result == 0) {
            $this->order_model->where(['id' => $order_id])->save(['comment' => 1]);
        }

        exit($this->returnApiSuccess());
    }


    /**
     * 隐藏订单
     */
    public function hideOrder()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_sn = I('post.order_sn');

        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $where = ['order_sn' => $order_sn];
        $data_order = $this->order_model->where($where)->find();

        if (!$data_order) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($data_order['status'] != OrderModel::ORDER_COMPLETE) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '非法操作'));
        }

        $result = $this->order_model->where($where)->save(['hidden' => 0]);
        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '操作失败'));
        else exit($this->returnApiSuccess());
    }


    public function logistics()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_sn = I('post.order_id');

        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $order_id = $this->order_model->where(['order_sn' => $order_sn])->find();
        $order_id = $order_id['id'];

        $data = $this->logistics_model
            ->where(['order_id' => $order_id])
            ->order('id desc')
            ->limit(0, 1)
            ->field('id,order_id,logistics_number,logistics_company,create_time')
            ->select();
        if ($data) {
            $data = $data[0];
            $data['create_time'] = date('Y-m-d H:i:s', $data['create_time']);
        } else {
            $data = [
                'id'                => '',
                'order_id'          => '',
                'logistics_number'  => '',
                'logistics_company' => '',
                'create_time'       => '',
            ];
        }
        exit($this->returnApiSuccess($data));
    }

    public function logistics_2()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $order_sn = I('post.order_sn');

        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $order_id = $this->order_model->where(['order_sn' => $order_sn])->find();
        if (!$order_id) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        $order_id = $order_id['id'];

        $data = $this->logistics_model
            ->where(['order_id' => $order_id])
            ->order('id desc')
            ->field('id,logistics_remark,logistics_url,order_id,logistics_number,logistics_company,create_time')
            ->select();

        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = date('Y-m-d', $v['create_time']);
        }
        if ($data)
            exit($this->returnApiSuccess($data));
        else
            exit($this->returnApiSuccess([]));
    }


    public function reminddispatch()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');

        $order_sn = I('post.order_sn');


        $this->checkparam([$mid, $token, $order_sn]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        if (!$this->order_model->where(['order_sn' => $order_sn])->count())
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单不存在'));

        if ($this->order_model->where(['order_sn' => $order_sn])->save(['remind' => 1])) {
            exit($this->returnApiSuccess('操作成功,请耐心等待'));
        } else {
            exit($this->returnApiSuccess('已提醒,请耐心等待'));
        }
    }




    /**
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     * ///////////////////////    V 1.1.0    ///////////////////////
     *
     *
     * 砍掉 option
     * 时间 2016.11.27
     * V 1.1.0
     *
     */


    /**
     * v 1.1.0
     * 购物车->购物车结算
     */
    public function cartSettlementBeforeV2()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $cartids = I('post.cartid');

        $this->checkparam([$mid, $token, $cartids]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $cartid = explode(',', $cartids);
        foreach ($cartid as $k => $v) {
            if (!$this->cart_model->find($v)) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请刷新购物车'));
            }
        }

        $shop_list = [];
        $shop_list['total_price_sum'] = 0;
        $shop_list['total_tax_sum'] = 0;
        $shop_list['total'] = 0;
        $shop_list['carriage'] = 0;
        $shop_list['cartids'] = $cartids;

        //认证查询
        $member_data = $this->member_model->find($mid);
        $shop_list['card_status'] = $member_data['authentication'];
        $shop_list['card_status'] = $shop_list['card_status'] == 1 ? '已认证' : '未认证';
        $shop_list['real_name'] = $member_data['real_name'];
        $shop_list['identity_card'] = $member_data['identity_card'];


        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        foreach ($cartid as $k => $v) {
            $cart = $this->cart_model
                ->alias('a')
                ->join($join)
                ->where(['a.id' => $v])
                ->field('a.*,b.name as product_name,b.tariff as tax,b.inventory,b.smeta,b.price')
                ->find();

            if ($cart) {

                $smeta = json_decode($cart['smeta'], true);
                $smeta = $smeta['thumb'];
                $smeta = $smeta ? $this->geturl($smeta) : "";

                $data_order_product = [
                    'cartid'      => $v,
                    'product_id'  => $cart['product_id'],
                    'name'        => $cart['product_name'],
                    'quantity'    => $cart['quantity'],
                    'price'       => $cart['price'],
                    'smeta'       => $smeta,
                    'total_price' => number_format($cart['price'] * $cart['quantity'], 2),
                    'tax'         => 0,
                    'total_tax'   => 0,
                ];

                $shop_list['total_price_sum'] += $cart['price'] * $cart['quantity'];
                $shop_list['total_tax_sum'] = 0;

                $shop_list['total'] = ($shop_list['total_price_sum'] + $shop_list['total_tax_sum']);
            }
            $shop_list['lists'][] = $data_order_product;
        }

        $shop_list['total_price_sum'] = number_format($shop_list['total_price_sum'], 2);
        $shop_list['total_tax_sum'] = number_format($shop_list['total_tax_sum'], 2);
        $shop_list['total'] = number_format($shop_list['total'], 2);
//        $shop_list['carriage'] = number_format($carriage, 2);  //运费

        exit($this->returnApiSuccess($shop_list));
    }

    /**
     *  v 1.1.0
     * 购物车数据检查
     * 库存判断
     * order 写入
     * order_product 写入
     */
    public function carttopayV2()
    {
        if (!IS_POST)
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $cartids = I('post.cartid');
        $addressid = I('post.addressid');
        $mark = I('post.mark');

        $this->checkparam([$mid, $token, $cartids, $addressid]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data_address = $this->address_model->find($addressid);
        if (!$data_address)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请选择收货地址'));

        $cartid = explode(',', $cartids);
        $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $cart_list = $this->cart_model
            ->alias('a')
            ->join($joins)
            ->where(['a.id' => ['in', $cartids], 'a.mid' => $mid])
            ->field('a.*,b.name as product_name,b.tariff as tax,b.inventory,b.price,b.smeta')
            ->select();

        if (count($cart_list) != count($cartid))
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请刷新购物车'));

        foreach ($cart_list as $k => $v) {
            if ($v['inventory'] < $v['quantity']) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, $v['product_name'] . '库存不足'));
            }
        }

        //事物 startTrans
        $ordersn = $this->getOrderNumber();
        $iscommit = true;
        $error = '';
        $this->order_product_model->startTrans();
        $data = [
            'mid'              => $mid,
            'order_sn'         => $ordersn,
            'status'           => OrderModel::ORDER_NOPAY,
            'payment_fullname' => $data_address['fullname'],
            'payment_address'  => $data_address['address'],
            'payment_phone'    => $data_address['shopping_telephone'],
            'mark'             => $mark,
            'create_time'      => time(),
            'update_time'      => time(),
            'date_day'         => date('Ymd', time()),
        ];
        $order_result = $this->order_model->add($data);

        if (!$order_result) {
            $error .= 'order_result';
            $iscommit = false;
        }
        $order_id = $this->order_model->getLastInsID();
        $total_price = 0;
        unset($v);

        foreach ($cart_list as $k => $v) {

            $data_order_product = [
                'order_id'   => $order_id,
                'product_id' => $v['product_id'],
                'name'       => $v['product_name'],
                'quantity'   => $v['quantity'],
                'smeta'      => $v['smeta'],
                'price'      => $v['price'],
                'total'      => ($v['price'] + $v['tax']) * $v['quantity'],
                'tax'        => $v['tax'],
            ];

            $total_price += $v['price'] * $v['quantity'];
            $total_price += $v['tax'] * $v['quantity'];

            $order_product_result = $this->order_product_model->add($data_order_product);
            if (!$order_product_result) $iscommit = false;

            //修改库存
            $this->product_model->where(['id' => $v['product_id']])->save(['inventory' => ['exp', 'inventory-' . $v['quantity']]]);

            //删除购物车中对应的商品
            $result = $this->cart_model->delete($v['id']);
            if (!$result) {
                $iscommit = false;
                $error .= '删除购物车';
            }
        }
        //运费
        //$total_price += '';

        $return = [
            'order_sn'    => $ordersn,
            'mid'         => $mid,
            'total_price' => $total_price,
        ];


        if ($iscommit) {
            $this->order_product_model->commit();
            exit($this->returnApiSuccess($return));
        } else {
            $this->order_product_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $error));
        }
    }


    /**
     *
     * v 1.1.0
     *
     * 立即购买
     */
    public function nowSettlementBeforeV2()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $quantity = I('post.quantity');


        $this->checkparam([$mid, $token, $product_id, $quantity]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $product_data = $this->product_model->find($product_id);
        if (!$product_data)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));


        //实名认证查询//
        $member_data = $this->member_model->field('authentication,real_name,identity_card')->find($mid);
        $shop_list['card_status'] = $member_data['authentication'];
        $shop_list['card_status'] = $shop_list['card_status'] == 1 ? '已认证' : '未认证';
        $shop_list['real_name'] = $member_data['real_name'];
        $shop_list['identity_card'] = $member_data['identity_card'];

        //费用
        $shop_list['total_price_sum'] = 0;  //商品总价
        $shop_list['total_tax_sum'] = 0; //税费
        $shop_list['total'] = 0; //总价
        $shop_list['carriage'] = 0; //运费

        //图片
        $smeta = json_decode($product_data['smeta'], true);
        $smeta = $smeta['thumb'];
        $smeta = $smeta ? $this->geturl($smeta) : "";


        $data_order_product = [
            'product_id'  => $product_data['id'],
            'name'        => $product_data['name'],
            'quantity'    => $quantity,
            'inventory'   => $product_data['inventory'], //库存
            'smeta'       => $smeta,
            'price'       => $product_data['price'],
            'total_price' => number_format($product_data['price'] * $quantity, 2),
            'tax'         => 0,
            'total_tax'   => 0,
        ];
        //商品总价
        $shop_list['total_price_sum'] += $product_data['price'] * $quantity;
        //关税
        $shop_list['total_tax_sum'] = 0;
        //总价
        $shop_list['total'] = $shop_list['total_price_sum'] + $shop_list['total_tax_sum'] + $shop_list['carriage'];

        $shop_list['carriage'] = number_format($shop_list['carriage'], 2); //运费
        $shop_list['total'] = number_format($shop_list['total'], 2);
        $shop_list['lists'][] = $data_order_product;

        exit($this->returnApiSuccess($shop_list));
    }


    /**
     * v 1.1.0
     *
     * 立即购买 生成订单
     */
    public function nowtopayV2()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $addressid = I('post.addressid');
        $quantity = I('post.quantity'); //数量
        $mark = I('post.mark'); //可选


        $this->checkparam([$mid, $token, $product_id, $addressid, $quantity]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data_address = $this->address_model->where(['id' => $addressid, 'mid' => $mid])->find();
        if (!$data_address) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请选择收货地址'));

        //产品查询
        $product_data = $this->product_model->find($product_id);
        if (!$product_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));


        if ($product_data['inventory'] < $quantity) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '库存不足'));
        }


        $iscommit = true;
        $this->order_model->startTrans();

        $error = '';
        $ordersn = $this->getOrderNumber();
        $data_order = [
            'mid'              => $mid,
            'order_sn'         => $ordersn,
            'status'           => OrderModel::ORDER_NOPAY,
            'payment_fullname' => $data_address['fullname'],
            'payment_address'  => $data_address['address'],
            'payment_phone'    => $data_address['shopping_telephone'],
            'mark'             => $mark,
            'create_time'      => time(),
            'update_time'      => time(),
            'date_day'         => date('Ymd', time()),
        ];
        $order_result = $this->order_model->add($data_order);
        if (!$order_result) {
            $iscommit = false;
            $error .= '2';
        }


        $order_id = $this->order_model->getLastInsID();

        $data_order_product = [
            'order_id'   => $order_id,
            'product_id' => $product_data['id'],
            'name'       => $product_data['name'],
            'quantity'   => $quantity,
            'price'      => $product_data['price'],
            'total'      => $product_data['price'] * $quantity,
            'tax'        => 0,
        ];

        $order_product_result = $this->order_product_model->add($data_order_product);

        if (!$order_product_result) {
            $iscommit = false;
            $error .= '3';
        }

        //运费
        $carriage = 0;
        if ($iscommit) {
            $this->order_model->commit();
        } else {
            $this->order_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '订单生成失败' . $error));
        }

        $datas = [
            'order_sn'    => $ordersn,
            'mid'         => $mid,
            'total_price' => $data_order_product['total'] + $carriage,
        ];
        exit($this->returnApiSuccess($datas));

    }


}