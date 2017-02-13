<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 10:27
 */

namespace Ordercenter\Controller;


use Appapi\Controller\RealnameController;
use Common\Controller\AdminbaseController;
use Common\Model\LogisticsModel;
use Common\Model\MemberModel;
use Common\Model\OptionModel;
use Common\Model\OrderActionModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;

class OrderController extends AdminbaseController
{
    private $order_model;
    private $order_product_model;
    private $member_model;
    private $order_action_log;
    private $logistics_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->member_model = new MemberModel();
        $this->order_action_log = new OrderActionModel();
        $this->logistics_model = new LogisticsModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $fields = [
            'start_time'     => ["field" => "a.create_time", "operator" => ">", 'datatype' => 'time'],
            'end_time'       => ["field" => "a.create_time", "operator" => "<", 'datatype' => 'time'],
            'select_status'  => ["field" => "a.status", "operator" => "=", 'datatype' => 'string'],
            'select_paytype' => ["field" => "a.pay_type", "operator" => "=", 'datatype' => 'string'],
            'keyword'        => ["field" => "a.order_sn", "operator" => "=", 'datatype' => 'string'],
            'username'       => ["field" => "c.username", "operator" => "=", 'datatype' => 'string'],
        ];
        $where_ands = [];
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }
        $where = join(" and ", $where_ands);

        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as c on a.mid = c.id';
        $count = $this->order_model
            ->join($join2)
            ->alias('a')
            ->where($where)
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->order_model
            ->join($join2)
            ->alias('a')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('a.id desc')
            ->field('a.*,c.username')
            ->select();
        $categorys = '';
        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] = '<a href="' . U("Order/info", ["id" => $v['id']]) . '">查看</a>';

            $total = $this->order_product_model->where(['order_id' => $v['id']])->sum('total');
            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['order_sn'] . '</td>
            <td>' . $result[$k]['username'] . '</td>
            <td>' . $total . '</td>
            <td>' . $v['pay_money'] . '</td>
            <td>' . $this->order_model->getStatusValues($result[$k]['status'], $v['comment']) . '</td>
            <td>' . (!empty($v['pay_type']) ? ($v['pay_type'] == 1 ? '微信' : '支付宝') : '') . '</td>
            <td>' . ($v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : "") . '</td>
            <td>' . ($v['update_time'] ? date('Y-m-d H:i:s', $v['update_time']) : "") . '</td>
            <td>' . ($v['payment_time'] ? date('Y-m-d H:i:s', $v['payment_time']) : "") . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        unset($v);
        $status_model = $this->order_model->field('status')->group('status')->select();
        $option = '';
        foreach ($status_model as $k => $v) {
            $stat = '';
            if (isset($_GET['select_status']) && I('get.select_status') == $v['status']) $stat = 'selected="selected"';
            $option .= '<option ' . $stat . '  value="' . $v['status'] . '">' . ($this->order_model->getStatusValues($v['status'], 1)) . '</option>';
        }

        $paytypelist = [
            ['status' => 1,
             'value'  => '微信'],
            ['status' => 2,
             'value'  => '支付宝'],
        ];
        unset($v);
        unset($stat);

        $paytype_model = '';
        foreach ($paytypelist as $k => $v) {
            $stat = '';
            if (isset($_GET['select_paytype']) && I('get.select_paytype') == $v['status']) $stat = 'selected="selected"';
            $paytype_model .= '<option ' . $stat . ' value="' . $v['status'] . '">' . $v['value'] . '</option>';
        }


        $this->assign('paytype_model', $paytype_model);
        $this->assign('status_model', $option);
        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function info()
    {
        $order_id = I('get.id');
        if (empty($order_id)) $this->error('empty');
        $data_order = $this->order_model
            ->find($order_id);

        $data_order['status'] = $this->order_model->getStatusValues($data_order['status'], $data_order['comment']);
        $data_order['payment_time'] = $data_order['payment_time'] ? date('Y-m-d', $data_order['payment_time']) : '';

        $data_member = $this->member_model->find($data_order['mid']);

        $identity_phone = json_decode($data_member['identity_phone'], true);
        foreach ($identity_phone as $k => $v) {
            $identity_phones[$v['key']] = $v['url'];
        }

        $data_order_product = $this->order_product_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'category as c on b.category_id = c.id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'product_sku as d on d.sku_id = a.option')
            ->where(['a.order_id' => $order_id])
            ->order('a.id desc')
            ->field('a.*,c.name as category_name,d.attr_option_path')
            ->select();

        $total_price = 0;
        $total_tax = 0;
        $categorys = '';
        foreach ($data_order_product as $k => $v) {

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $v['name'] . '</td>
            <td>' . $v['category_name'] . '</td>
            <td>官方标配</td>
            <td>￥' . $v['price'] . '</td>
            <td>' . $v['quantity'] . '</td>
            <td>￥' . number_format($v['total'], 2) . '</td>
        </tr>';

            $total_price += $v['total'];
            $total_tax += $v['tax'];
        }

        $toal_sum = $total_price + $total_tax;

        $order_action = $this->order_action_log
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'users as b on a.operator = b.id')
            ->where(['a.order_sn' => $data_order['order_sn']])
            ->field('a.*,b.user_nicename')
            ->select();

        unset($v);

        $manger_str = '';
        foreach ($order_action as $k => $v) {
            $manger_str .= "<tr>
                              <td>" . date('Y-m-d H:i;s', $v['create_time']) . "</td>
                              <td>" . $this->order_model->getStatusValues($v['status'], $data_order['comment']) . "</td>
                              <td>" . $v['user_nicename'] . "</td>
                              <td>" . $v['remarks'] . "</td>
                            </tr>";
        }
        unset($v);
        ////////
//        $tablebody_courier
        $data_logistics = $this->logistics_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'users as b on a.operator_id = b.id')
            ->field('a.*,b.user_nicename')
            ->where(['a.order_id' => $order_id])->select();

        $tablebody_logistics = '';

        foreach ($data_logistics as $k => $v) {
            $tablebody_logistics .= '<tr>
                                        <td>' . $v['logistics_company'] . '</td>
                                        <td>' . $v['logistics_number'] . '</td>
                                        <td>' . $v['logistics_remark'] . '</td>
                                        <td>' . $v['logistics_url'] . '</td>
                                        <td>' . $v['user_nicename'] . '</td>
                                        <td>' . date('Y-m-d H:i:s', $v['create_time']) . '</td>
                                    </tr>';
        }


        $this->assign('tablebody_logistics', $tablebody_logistics);
        $this->assign('manage_str', $manger_str);
        $this->assign('data_order', $data_order);
        $this->assign('data_member', $data_member);
        $this->assign('categorys', $categorys);

        $this->assign('identity_phone', $identity_phones);

        $this->assign('total_price', '￥' . number_format($total_price, 2));
        $this->assign('total_tax', '￥' . number_format($total_tax, 2));
        $this->assign('toal_sum', '￥' . number_format($toal_sum, 2));
        $this->display();
    }

    public function add_order_log()
    {
        if ($this->order_model->where(['order_sn' => I('post.order_sn')])->getField('status') != OrderModel::ORDER_PAY_SUCCESS) {
            $this->ajaxReturnRequest('订单未付款', '999');
            exit;
        }

        $data_order = $this->order_model->where(['order_sn' => I('post.order_sn')])->find();
        $order_id = $data_order['id'];

        $iscommit = true;
        $this->order_model->startTrans();
//        courier_number
        //发货
        if (I('post.status') == OrderModel::ORDER_PRODUCT_SEND) {
            $count = $this->logistics_model->where(['order_id' => $order_id])->count();
            if ($count < 1)
                $this->ajaxReturnRequest('请先添加物流数据', '999');
        }
        $result_ds = $this->order_model->where(['order_sn' => I('post.order_sn')])->save(['status' => I('post.status')]);

        if ($result_ds === false)
            $iscommit = false;

        $data = I('post.');
        $data['create_time'] = time();
        $data['operator'] = sp_get_current_admin_id();

        if (!$this->order_action_log->add($data))
            $iscommit = false;


        $order_action = $this->order_action_log
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'users as b on a.operator = b.id')
            ->where(['a.order_sn' => I('post.order_sn')])
            ->field('a.*,b.user_nicename')
            ->select();

        unset($v);
        $manger_str = '';
        foreach ($order_action as $k => $v) {
            $manger_str .= "<tr>
                              <td>" . date('Y-m-d H:i;s', $v['create_time']) . "</td>
                              <td>" . $this->order_model->getStatusValues($v['status'], $data_order['comment']) . "</td>
                              <td>" . $v['user_nicename'] . "</td>
                              <td>" . $v['remarks'] . "</td>
                            </tr>";
        }


        if ($iscommit) {
            $this->order_model->commit();
            $this->ajaxReturnRequest($manger_str);
        } else {
            $this->order_model->rollback();
            $this->ajaxReturnRequest('error', '999');
        }


    }

    public function add_logistics()
    {
        if (IS_POST) {
            $post = I('post.');
            $post['operator_id'] = sp_get_current_admin_id();
            $post['create_time'] = time();

            if (!$this->logistics_model->create($post)) $this->error($this->logistics_model->getError());

            if ($this->logistics_model->add($post)) {
                $this->success('success');
            } else {
                $this->error('error');
            }
        }
    }
}