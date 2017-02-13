<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;

use Appapi\Controller\ApibaseController;
use Common\Model\BannerImageModel;
use Common\Model\CartModel;
use Common\Model\AddressModel;
use Common\Model\MemberModel;
use Common\Model\OptionModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionValueModel;
use Common\Model\ProductSkuModel;
use Think\Controller;

class ShoppingCarController extends BaseController
{

    private $cart_model;
    private $product_model;
    private $address_model;
    private $member_model;
    private $order_product_model;
    private $order_model;
    private $product_option_value_model;
    private $product_sku_model;
    private $option_model;

    public function __construct()
    {
        parent::__construct();
        $this->product_option_value_model = new ProductOptionValueModel();
        $this->cart_model = new CartModel();
        $this->product_model = new ProductModel();
        $this->address_model = new AddressModel();
        $this->member_model  = new MemberModel();
        $this->order_product_model = new OrderProductModel();
        $this->order_model = new OrderModel();
        $this->product_sku_model = new ProductSkuModel();
        $this->option_model = new OptionModel();
    }


    public function index(){
        $mid = session('mid');
        $this->is_login();
        $this->_lists($mid);
        $this->display();
    }

    public function _lists($mid)
    {

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $field = 'b.price ,a.id as cartid,a.mid,a.quantity,b.name as product_name ,b.original_price,b.inventory,ship_address,b.smeta,b.tariff,b.status,b.del';

            $result = $this->cart_model
                ->alias('a')
                ->join($join)
                ->where(['a.mid' => $mid])
                ->order('a.id desc')
                ->field($field)
                ->select();

            foreach ($result as $key => $val) {
                //下架处理，将下架的商品从购物车中清除
                if (!$val['status'] && !$val['del']) {
                    unset($result[$key]);
                    $this->cart_model->delete($val['cartid']);
                    continue;
                }

                $result[$key]['smeta'] = json_decode($val['smeta'], true);
                $result[$key]['smeta'] = $result[$key]['smeta']['thumb'];
                $result[$key]['smeta'] = $this->geturl($result[$key]['smeta']);
                $result[$key]['tital_price'] = $val['quantity'] * $val['price'];
            }



        $this->assign('group',$result);
    }


    /**
     * 修改购物车
     */
    public function editCart()
    {

        $mid = session('mid');
        $this->checkmid($mid);
        $cartid = I('post.cartid');
        $quantity = I('post.quantity');
        $this->checkparam(array($mid, $quantity, $cartid));

        $this->checkisNumber(array($quantity));

        $cart_data = $this->cart_model->find($cartid);
        if (!$cart_data) exit($this->returnApiSuccess(BaseController::FATAL_ERROR, '请刷新购物车'));
        $product_id = $cart_data['product_id'];

        //最大库存
        $inventory = $this->product_model->where(array('id' => $product_id))->getField('inventory');

        if ($quantity > $cart_data['quantity']) {
            if ($quantity > $inventory) {
                exit($this->returnApiError(BaseController::FATAL_ERROR, '数量超过库存'));
            }
        } else {

            if ($quantity > $inventory) {
                $result = $this->cart_model
                    ->where(array('id' => $cartid))
                    ->save(array(
                        'quantity' => $inventory,
                        'create_time' => time(),
                    ));

                if ($result === false) exit($this->returnApiError(BaseController::FATAL_ERROR));
                exit($this->returnApiSuccess());
            }
        }

        $result = $this->cart_model
            ->where(array('id' => $cartid))
            ->save(array(
                'quantity' => $quantity,
                'create_time' => time(),
            ));

        if ($result === false) exit($this->returnApiError(BaseController::FATAL_ERROR));
        exit($this->returnApiSuccess());
    }


    /**
     * 删除商品
     */
    public function delProduct()
    {
        $mid = session('mid');
        $this->checkmid($mid);
        $cartid = I('post.cartid');
        $this->checkparam(array($mid, $cartid));

        $cart = $this->cart_model->find($cartid);
        if( !$cart ) exit($this->returnApiError(BaseController::FATAL_ERROR,'请刷新购物车'));
        $result = $this->cart_model->delete($cartid);
        if ($result)
            exit($this->returnApiSuccess());
        else
            exit($this->returnApiError(BaseController::FATAL_ERROR, '商品不存在，请刷新页面'));
    }



    public function cartSettlementBefore()
    {

        $mid = session('mid');
        $this->is_login();
        $cartids = I('post.cartid');

        if( empty($mid) || empty($cartids) ){
            exit($this->error('请添加商品后结算'));
        }

        $cartid = explode(',', $cartids);


        foreach ($cartid as $k => $v) {
            if (!$this->cart_model->find($v)) {
                exit($this-$this->returnApiError(BaseController::FATAL_ERROR,'请刷新购物车'));

            }
        }

        $shop_list = array();
        $shop_list['total_price_sum'] = 0;
        $shop_list['total_tax_sum'] = 0;
        $shop_list['total'] = 0;
        $shop_list['carriage'] = 0;
        $shop_list['cartids'] = $cartids;


        //认证查询
        $member_data = $this->member_model->find($mid);
        $card_status['card_status'] = $member_data['authentication'];
        $card_status['real_name'] = $member_data['real_name'];
        $card_status['identity_card'] = $member_data['identity_card'];
        $this->assign('card_status',$card_status);


        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';

        foreach ($cartid as $k => $v) {
            $cart = $this->cart_model
                ->alias('a')
                ->join($join)
                ->where(array('a.id' => $v))
                ->field('a.*,b.price,b.name as product_name,b.tariff as tax,b.inventory,b.original_price,b.smeta')
                ->find();

            $cart['price'] =  sprintf('%1.2f',$cart['price']);

            if ($cart) {
                $smeta = json_decode($cart['smeta'], true);
                $smeta = $smeta['thumb'];
                $smeta = $smeta ? $this->geturl($smeta) : "";

                if ($cart['option_price_prefix'] == '+') {
                    $cart['price'] += $cart['price'];
                }

                if ($cart['option_price_prefix'] == '-') {
                    $cart['price'] -= $cart['price'];
                }

                $data_order_product = array(
                    'original_price' => sprintf('%1.2f',$cart['original_price']),
                    'cartid' => $v,
                    'product_id' => $cart['product_id'],
                    'name' => $cart['product_name'],
                    'quantity' => $cart['quantity'],
                    'option' => $cart['option'],
                    'price' => $cart['price'],
                    'smeta' => $smeta,
                    'total_price' => number_format($cart['price'] * $cart['quantity'], 2),
                    'tax' => 0,
                    'total_tax' => 0,
                    'option_value_name' => $cart['option_values'],
                );

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


        $this->assign('shop_list',$shop_list);
        $this->addressList();
        $this->display('order');

    }



    public function addressList()
    {

        $mid = session('mid');
        $this->is_login();
        $this->checkparam(array($mid));
        $result = $this->address_model
            ->where(array('mid' => $mid))
            ->order('id desc')
            ->select();

        if(empty($result)){
            $address = "<div><a href='".U('PersonalCenter/address_list')."'>请添加地址</a></div>";
        }else{
            foreach( $result as $key=>$v ){

                if( $key == 0 ) {
                    $address_ation = "class='address_ation'";
                }else{
                    $address_ation = '';
                }
                $address .= "<li ".$address_ation.">
					<input type='radio' onclick='cli()' name='radio' value='".$v['id']."' /><p>".$v['address']." (".$v['fullname']." 收)<span> ".$v['shopping_telephone']."</span></p></li>";

            }

        }
        $this->assign('addressList',$address);


    }

    public function address(){
        $id = I('post.id');
        $list = $this->address_model->where(array('id'=>$id))->find();

        $address = "	<div class=\"adrs_cart_left\">
								<h2>寄送至：</h2>
							</div><div class=\"adrs_cart_right\">
								<p>".$list['address']." </p>
							</div>
						</div>

						<h4><b>收件人：</b>".$list['fullname']."  ".$list['shopping_telephone'] ."  </h4>";

        $this->ajaxReturn($address);
    }


    /**
     * 购物车数据检查
     * 库存判断
     * order 写入
     * order_product 写入
     */
    public function carttopay()
    {

        $mid = session('mid');
        $this->checkmid($mid);
        $cartids = I('post.cartid');
        $addressid = I('post.addressid');
        $mark = I('post.mark');

        $this->checkparam(array($mid, $cartids, $addressid));


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
        $url = U('Web/Order/order_type',array('order_sn'=>$ordersn,'total'=>$total_price));
        $return = [
            'order_sn'    => $ordersn,
            'mid'         => $mid,
            'total_price' => $total_price,
            'url'         => $url,
        ];


        if ($iscommit) {
            $this->order_product_model->commit();
            exit($this->returnApiSuccess($return));
        } else {
            $this->order_product_model->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, $error));
        }
    }

    public function vafily_login(){
        $product_id = I('post.product_id');
        $quantity   = I('post.quantity');
        $mid = session('mid');
        if(empty( $mid )) exit($this->returnApiError(ApibaseController::FATAL_ERROR,'未登陆,请您登陆'));
        $url = U('ShoppingCar/cnowpaySettlementBefore',array('product_id'=>$product_id,'quantity'=>$quantity));
        exit($this->returnApiSuccess($url));

    }


    /**
     * 立即购买
     */
    public function cnowpaySettlementBefore()
    {

        $mid = session('mid');
        $this->is_login();
        $product_id = I('product_id');
        $quantity   = I('quantity');


        //认证查询
        $member_data = $this->member_model->find($mid);
        $card_status['card_status'] = $member_data['authentication'];
        $card_status['real_name'] = $member_data['real_name'];
        $card_status['identity_card'] = $member_data['identity_card'];
        $this->assign('card_status',$card_status);

        $shop_list = $this->product_model->where('id='.$product_id)->find();

        $shop_list['total_price_sum'] = number_format($shop_list['price'] * $quantity , 2);
        $shop_list['total_tax_sum']   = number_format($shop_list['price'] * $quantity , 2);
        $shop_list['total']           = number_format($shop_list['price'] * $quantity , 2);
        $shop_list['carriage']        = number_format($shop_list, 2);
        $shop_list['number']          = $quantity;
        $smeta = json_decode($shop_list['smeta'], true);
        $shop_list['smeta']   = $smeta['thumb'];




        $this->assign('vo',$shop_list);
        $this->addressList();
        $this->display('now_pay');

    }


    /**
     * v 1.1.0
     *
     * 立即购买 生成订单
     */
    public function nowtopayV2()
    {
        $mid = session('mid');
        $product_id = I('post.product_id');
        $addressid = I('post.addressid');
        $quantity = I('post.quantity'); //数量

        $this->checkmid($mid);


        $this->checkparam([$mid, $product_id, $addressid, $quantity]);


        $data_address = $this->address_model->where(['id' => $addressid, 'mid' => $mid])->find();
        if (!$data_address) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '请选择收货地址'));

        //产品查询
        $product_data = $this->product_model->find($product_id);

        if (!$product_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));


        if ($product_data['inventory'] <= $quantity) {
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
            'update_time'      => time(),
            'create_time'      => time(),
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

        $url = U('Web/Order/order_type',array('order_sn'=>$ordersn,'total'=>$data_order_product['total']));

        $datas = [
            'order_sn'    => $ordersn,
            'mid'         => $mid,
            'total_price' => $data_order_product['total'] + $carriage,
            'url'         => $url,
        ];

        exit($this->returnApiSuccess($datas));

    }


}
