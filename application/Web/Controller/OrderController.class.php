<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;


use Common\Model\AttrModel;
use Common\Model\AttrOptionModel;
use Common\Model\BrowseProductModel;
use Common\Model\CartModel;
use Common\Model\CategoryAttrModel;
use Common\Model\CategoryModel;
use Common\Model\CommentModel;
use Common\Model\OptionModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductSkuModel;
use Think\Controller;
use Think\Log;
use Web\Controller\BaseController;

class OrderController extends BaseController
{
    private $product_model;
    private $comment_model;
    private $browse_product_model;
    private $cart_model;
    private $order_model;
    private $order_product_model;
    private $category_attr_model;
    private $attr_model;
    private $attr_option_model;
    private $product_sku_model;
    private $option_model;
    private $category_model;

    public function __construct()
    {
        parent::__construct();
        $this->product_model = new ProductModel();
        $this->comment_model = new CommentModel();
        $this->browse_product_model = new BrowseProductModel();
        $this->cart_model = new CartModel();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->category_attr_model = new CategoryAttrModel();
        $this->attr_model = new AttrModel();
        $this->attr_option_model = new AttrOptionModel();
        $this->product_sku_model = new ProductSkuModel();
        $this->option_model = new OptionModel();
        $this->category_model = new CategoryModel();
    }

    public function index()
    {

        $id = I('get.id');
        $this->_list($id);
//        $this->attr($id);
        $this->display('detail');
    }


    public function _list($id)
    {
        $mid = session('mid');

        if ($mid) {
            //浏览记录
            $count = $this->browse_product_model->where(['mid' => $mid, 'product_id' => $id])->count();
            if ($count == 0) {
                $browse_logs = $this->browse_product_model
                    ->add(['mid' => $mid, 'product_id' => $id, 'update_time' => time()]);
            } else {
                $browse_logs = $this->browse_product_model
                    ->where(['mid' => $mid, 'product_id' => $id])
                    ->save(['update_time' => time()]);
            }
        }

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';

        $where['a.id'] = $id;

        $result = $this->product_model
            ->where($where)
            ->join($join)
            ->join($join2)
            ->alias("a")
            ->field('a.*,b.name as category_name,c.name as brand_name')
            ->order('a.id desc')
            ->find();


        $photo = json_decode($result['smeta'], true)['photo'];
        $photo_str = '';
        foreach ($photo as $key => $v) {
            $star = '';
            if ($key == 0) $star = "id='onlickImg'";
            $photo_str .= "<li  " . $star . "><img  src=\"/data/upload/" . $v['url'] . "\" /></li>";
        }
        $result['bigimg'] = "/data/upload/" . $photo['0']['url'];
        $result['photo'] = $photo_str;
//        dump($result);exit;
        $this->browseLogs();
        $this->comment($id);
        $this->popular();
        $this->assign('detail', $result);
    }

//    public function attr($id)
//    {
//
//
//        $product_id = intval($id);
//        if (empty($product_id)) $this->error('empty');
//        $data_product = $this->product_model->find($product_id);
//
//        if (!$data_product) $this->error('error');
//        $parentid = $this->category_model->where(['id' => $data_product['category_id']])->getField('parentid');
//
//        if ($parentid != 0) {
//            $category_id = $parentid;
//        } else {
//            $category_id = $data_product['category_id'];
//        }
//
//        $attr_key_id_array = $this->category_attr_model
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as b on a.attr_key_id = b.attr_key_id')
//            ->alias('a')
//            ->where([
//                'a.category_key_id' => $category_id,
//            ])
//            ->field('a.*,b.attr_name')
//            ->select();
//
//        $attrid = '';
//        foreach ($attr_key_id_array as $value) {
////            $category_options .= '<option name="' . $value['attr_key_id'] . '">' . $value['attr_name'] . '</option>';
//            $attrid .= $attrid ? ',' . $value['attr_key_id'] : $value['attr_key_id'];
//        }
//        if ($attrid) {
//            $where = ['c.attr_key_id' => ['in', $attrid]];
//        }
//
//        unset($value);
//
//        $option = $this->option_model
//            ->alias('a')
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr_option as b on a.option_key_id = b.option_key_id')
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as c on b.attr_key_id = c.attr_key_id')
//            ->field('a.*,c.attr_key_id')
//            ->where($where)
//            ->select();
//
//
//        foreach ($attr_key_id_array as $k => $v) {
//            foreach ($option as $key => $val) {
//                if ($v['attr_key_id'] == $val['attr_key_id']) {
//                    $attr_key_id_array[$k]['option'][] = $val;
//                }
//            }
//        }
//
//
//        unset($v);
//        $product_sku_data = $this->product_sku_model->where(['product_key_id' => $product_id])->select();
//
//        $attr_option_path = '';
//
//        foreach ($product_sku_data as $k => $v) {
//            $attr_option_path .= $attr_option_path ? ',' . $v['attr_option_path'] : $v['attr_option_path'];
//        }
//
//
//        $attr_option_path_array = explode(',', $attr_option_path);
//        $attr_option_path_array = array_unique($attr_option_path_array);
//
//
//        unset($v);
//        $option_ids = '';
//        foreach ($attr_option_path_array as $v) {
//            $option_ids .= $option_ids ? ',' . $v : $v;
//        }
//
//        $result_attroption_leftjoin_attr = $this->attr_option_model
//            ->alias('a')
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as b on a.attr_key_id = b.attr_key_id')
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'option as c on a.option_key_id = c.option_key_id')
//            ->where(['a.option_key_id' => ['in', $option_ids]])
//            ->select();
//
//        $result_attroption_leftjoin_attr_name = $this->attr_option_model
//            ->alias('a')
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as b on a.attr_key_id = b.attr_key_id')
//            ->where(['a.option_key_id' => ['in', $option_ids]])
//            ->field('attr_name')
//            ->select();
//
//        $array2D = $result_attroption_leftjoin_attr_name;
//        $stkeep = false;
//        $ndformat = true;
//        if ($stkeep) $stArr = array_keys($array2D);
//        if ($ndformat) $ndArr = array_keys(end($array2D));
//        foreach ($array2D as $v) {
//            $v = join(",", $v);
//            $temp[] = $v;
//        }
//        $temp = array_unique($temp);
//        foreach ($temp as $k => $v) {
//            if ($stkeep) $k = $stArr[$k];
//            if ($ndformat) {
//                $tempArr = explode(",", $v);
//                foreach ($tempArr as $ndkey => $ndval) $output[$k][$ndArr[$ndkey]] = $ndval;
//            } else $output[$k] = explode(",", $v);
//        }
//
//
//        foreach ($result_attroption_leftjoin_attr as $k => $v) {
//            foreach ($output as $key => $value) {
//                if ($v['attr_name'] == $value['attr_name']) {
//                    $output[$key]['name'][] = $v;
//                }
//            }
//        }
//
//        foreach ($output as $k => $v) {
//            $output[$k]['number'] = $k + 1;
//        }
//
//        $count = count($output);
//
//        $this->assign('attr', $output);
//        $this->assign('count', $count);
//
//
////        $product = $this->product_model->where(array('id'=>$id))->field('category_id')->find();
////        $category = $product['category_id'];
////
////        $where = array('b.category_key_id' => $category);
////        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category_attr as b on a.attr_key_id = b.attr_key_id';
////        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as c on b.category_key_id = c.id';
////        $attr = $this->attr_model
////            ->alias('a')
////            ->join($join)
////            ->join($join2)
////            ->field('a.attr_name,b.attr_key_id')
////            ->where($where)
////            ->select();
////        $count = $this->attr_model
////            ->alias('a')
////            ->join($join)
////            ->join($join2)
////            ->where($where)
////            ->count();
////
////
////        foreach ($attr as $k => $v) {
////
////            $attr[$k]['number'] = $k + 1;
////            $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'attr_option as b on a.attr_key_id = b.attr_key_id';
////            $attr_where = array('a.attr_key_id' => $v['attr_key_id']);
////            $attr_a = $this->category_attr_model
////                ->alias('a')
////                ->join($join3)
////                ->where($attr_where)
////                ->field('b.option_key_id')
////                ->select();
////
////            foreach ($attr_a as $key => $val) {
////                $join4 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option as b on a.option_key_id = b.option_key_id';
////                $option_where = array('a.option_key_id' => $val['option_key_id']);
////                $option = $this->attr_option_model
////                    ->alias('a')
////                    ->where($option_where)
////                    ->join($join4)
////                    ->field('b.option_key_id,b.option_name')
////                    ->find();
////                $attr_a[$key]['option_name'] = $option['option_name'];
////            }
////            $attr[$k]['attr'] = $attr_a;
////
////        }
//
//
//    }


    /**
     * 人气单品
     */



    public function popular()
    {
        $result = $this->product_model->order('sales_volume')->where(array('status'=>'1','del'=>'1'))->limit(5)->select();


        foreach ($result as $v) {
            $a['id'] = $v['id'];
            $a['name'] = $v['name'];
            $a['price'] = $v['price'];
            $a['picture'] = json_decode($v['smeta'], true)['thumb'];
            $data[] = $a;
        }

        $this->assign('popular', $data);
    }


    /**
     * 评论管理
     */

    public function comment($id)
    {
        $list = $this->comment_model->where(['product_id' => $id , 'status'=> 1 ])->field('full_name,content,create_time,property')->select();
        foreach ($list as $key => $v) {
            $list[$key]['create_time'] = date('Y-m-d ', $v['create_time']);
        }
        $this->assign('comment', $list);
    }

    /**
     * 浏览历史
     */

    public function browseLogs()
    {
        $mid = session('mid');
        if (empty($mid)) {
            $result = $this->product_model->where(array('status'=>'1','del'=>'1'))->limit(6)->select();
            foreach ($result as $key => $v) {
                $result[$key]['product_name'] = $v['name'];
                $result[$key]['product_id'] = $v['id'];
            }


        } else {
            $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
            $result = $this->browse_product_model
                ->join($joins)
                ->alias('a')
                ->where(['mid' => $mid,'b.status'=>1,'b.del'=>1])
                ->field('a.* , b.name as product_name , b.smeta ,b.price')
                ->limit(0, 6)
                ->order('a.update_time desc')
                ->select();
        }

        foreach ($result as $k => $v) {
            $smeta = json_decode($v['smeta'], true);
            $smeta = $smeta['thumb'];
            $smeta = $smeta ? $this->geturl($smeta) : '';

            $result[$k]['smeta'] = $smeta;
            $result[$k]['update_time'] = date('Y-m-d', $v['update_time']);
        }
        $this->assign('browseLogs', $result);
    }

    /**
     * 添加购物车
     */
    public function addCart()
    {

        $mid = session('mid');
        $this->checkmid($mid);
        $product_id = I('post.product_id');
        $quantity = I('post.quantity');

        $this->checkisNumber([$quantity]);
        $this->checkparam([$mid]);

        $result = $this->product_model->find($product_id);
        if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR));


        if ($quantity > $result['inventory']) exit($this->returnApiError(BaseController::FATAL_ERROR, '库存不足'));

//        $product_sku_data = $this->product_sku_model->where(array('attr_option_path' => $option, 'product_key_id' => $product_id))->find();
//
//
//
//        if (!$product_sku_data) exit($this->returnApiError(BaseController::FATAL_ERROR, '产品不存在'));
//
//
//
//        $option = $product_sku_data['sku_id'];
//        unset($result);

        $has = $this->cart_model
            ->where([ 'mid' => $mid ,'product_id'=>$product_id])
            ->select();


        if ($has) {
            $data = [
                'quantity'    => ["exp", "quantity+" . $quantity],
                'create_time' => time(),
            ];

            $result = $this->cart_model->where(['product_id' => $product_id, 'mid' => $mid])->save($data);
            if ($result === false) exit($this->returnApiError(BaseController::FATAL_ERROR));
        } else {
            $data = [
                'mid'           => $mid,
                'product_id'    => $product_id,
                'quantity'      => $quantity,
                'create_time'   => time(),
                'date_day'      => date('Ymd', time()),
            ];
            $result = $this->cart_model->add($data);
            if (!$result) exit($this->returnApiError(BaseController::FATAL_ERROR));
        }
        exit($this->returnApiSuccess());
    }


    public function order_type()
    {
        $mid = session('mid');
        $out_trade_no = I('order_sn');
        $order = $this->order_model->getValidityOrder($mid, $out_trade_no);
        if (!$order) exit($this->error('订单不存在，或已支付'));

        $order_product_data = $this->order_product_model->where(['order_id' => $order['id']])->select();
        $price = 0;
        foreach ($order_product_data as $k => $v) {
            $price += ($order_product_data[$k]['price'] + $order_product_data[$k]['tax']) * $order_product_data[$k]['quantity'];
        }
        $this->assign('order_sun', $out_trade_no);
        $this->assign('total_price',$price);
        $this->display();
    }




    public function pay()
    {

        $mid = session('mid');
        $this->checkmid($mid);

        $out_trade_no = I('get.order_sn');
        $paytype = I('get.paytype');


        $this->checkparam([$mid, $out_trade_no, $paytype]);

        if ($paytype != 'wxpay' && $paytype != 'alipay') exit($this->error('支付类型错误'));

        $order = $this->order_model->getValidityOrder($mid, $out_trade_no);
        if (!$order) exit($this->returnApiError($this->error('订单不存在，货已支付')));

        $order_product_data = $this->order_product_model->where(['order_id' => $order['id']])->select();
        $price = 0;
        foreach ($order_product_data as $k => $v) {
            $price += ($order_product_data[$k]['price'] + $order_product_data[$k]['tax']) * $order_product_data[$k]['quantity'];
        }

//        $test = C('TEST_ID');
//        $domain = is_string($test);
//        if(  $domain === true ) $price = 1;

        if ($paytype == 'wxpay') {
            $money_all = $price * 100;

            $order = [
                'body'         => '海外购',
                'total_fee'    => $money_all,
                'out_trade_no' => $out_trade_no,
                'product_id'   => 1,
            ];

            weixinpay($order);
        }
    }


    /**
     * notify_url接收页面
     */
    public function notify()
    {
        // 导入微信支付sdk
        Vendor('Weixinpay.Weixinpay');
        $wxpay = new \Weixinpay();
        $result = $wxpay->notify();

        $test['content'] = '12312';
        D('test')->add($test);

        if ($result) {
            //微信返回参数
            $order_sn = $result['out_trade_no']; //订单号
            $order_price = $result['total_fee'] / 100; //微信返回的是分，换算成元


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
            if ($this->product_model->where(['id' => $v['product_id']])->save(['sales_volume' => ['exp', 'sales_volume+' . $v['quantity']],'inventory'=> ['exp', 'inventory-' . $v['quantity']] ]) === false)
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
    public function order_status()
    {
        $status = $this->order_model->where(['order_sn' => I('id')])->field('status')->find();
        if ($status['status'] == 4) {
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(2);
        }
    }

    public function pay_success()
    {
        $this->display('pay_success');
    }


}
