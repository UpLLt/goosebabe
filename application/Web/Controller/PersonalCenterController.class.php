<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;




use Common\Model\AddressModel;
use Common\Model\LogisticsModel;
use Common\Model\MemberModel;

use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Think\Controller;
use Web\Controller\BaseController;

class PersonalCenterController extends BaseController
{
    private $member_model;
    private $address_model;
    private $order_model;
    private $order_product_model;
    private $logistics_model;


    public function __construct()
    {
        parent::__construct();
        $this->member_model = new MemberModel();
        $this->address_model= new AddressModel();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->logistics_model = new LogisticsModel();
    }



    public function index(){

        $mid = session('mid');
        $this->is_login();
        $this->_list($mid);
        $this->display('index');

    }

    public function _list($mid){
        $list = $this->member_model->where(array('id'=>$mid))->find();
        $list['sex'] = $this->member_model->getSexTostring($list['sex']);
        $list['photo'] = json_decode($list['identity_phone'],true);
        $list['identity_card'] = empty( $list['identity_card'] ) ? '未填写，请补全' : substr( $list['identity_card'],0,3).'xxxxxxxxxxx'.substr( $list['identity_card'] ,-4 );
        $list['nickname'] = empty( $list['nickname'] ) ? '未填写，请补全' : $list['nickname'];
        $list['real_name'] = empty( $list['real_name'] ) ? '未填写，请补全' : $list['real_name'];


        $list['photo'] = json_decode($list['identity_phone'],true);

        $this->assign('list',$list);
    }

    public function infomation(){
        $mid = session('mid');
        $this->is_login();
        $list = $this->member_model->where(array('id'=>$mid))->find();
        $photo = json_decode($list['identity_phone'],true);

//        $list['identity_card'] = empty( $list['identity_card'] ) ? '' : substr( $list['identity_card'],0,3).'xxxxxxxxxxx'.substr( $list['identity_card'] ,-4 );

//        $list['identity_card'] = substr( $list['identity_card'],0,3).'xxxxxxxxxxx'.substr( $list['identity_card'] ,-4 );

        foreach( $photo as $v){
            if($v['key'] == 'front' ){
                $list['front']  = $v['url'];
            }else{
                $list['back']   = $v['url'];
            }
        }

        $this->assign('member',$list);
        $this->display('modify_info');
    }


    public function madify_info()
    {

        $mid = session('mid');
        $this->is_login();
        $real_name = I('post.real_name');
        $identity_card = I('post.identity_card');
        $sex = I('post.sex');
        $nickname = I('post.nickname');
        if( empty($real_name) || empty($identity_card) || empty($sex) || empty($nickname) ){
            $this->error('请补全表单');
        }
        if (!$this->isIdCard($identity_card)) exit($this->error( '身份证号码不合法'));

        $authentication = $this->member_model->where(array('id' => $mid))->getField('authentication');



        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/identity/'; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        // 上传文件
        $info = $upload->upload();


        if (!$info) {
            // 上传错误提示错误信息
            exit($this->error($upload->getError()));
        } else {// 上传成功 获取上传文件信息
            foreach ($info as $file) {
                $url = '/data/upload/identity/' . $file['savepath'] . $file['savename'];
                $upload_iamges[] = array(
                    'key' => $file['key'],
                    'url' => $url
                );
            }
        }

        $data = array(
            'identity_card' => $identity_card,
            'real_name' => $real_name,
            'authentication' => 1,
            'sex' => $sex,
            'nickname' => $nickname,
            'identity_phone' => json_encode($upload_iamges)
        );

        $result = $this->member_model->where(array('id' => $mid))->save($data);
        if ($result === false) exit($this->error());


        exit($this->success('修改成功'));

    }


    public function password(){
        $mid = session('mid');
        $this->is_login();
        $username = $this->member_model->where(array('id'=>$mid))->field('username')->find();
        $this->assign('username',$username['username']);
        $this->display('modifypasswd');
    }

    /**
     * 修改密码
     */
    public function modifypasswd()
    {

            $username = I('post.username');
            $oldpwd = I('post.oldpwd');
            $newpwd = I('post.newpwd');

            $this->checkparam(array($username, $newpwd, $oldpwd));
            $where = array('username' => $username);
            $member = $this->member_model->where($where)->find();
            if (!$member) exit($this->returnApiError(BaseController::FATAL_ERROR, '用户不存在'));

            if (!sp_compare_password($oldpwd, $member['password'])) exit($this->returnApiError(BaseController::FATAL_ERROR, '旧密码错误'));

            $resutl = $this->member_model->where($where)->save(array('password' => sp_password($newpwd)));
            if ($resutl === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '密码修改失败'));
            exit($this->returnApiSuccess());
        }

    public function address_list(){

        $mid = session('mid');
        $this->is_login();
        $result = $this->address_model
            ->where(array('mid' => $mid))
            ->limit(6)
            ->order('id desc')
            ->select();

        $this->assign('address',$result);
        $this->display('address');
    }

    public function addressAdd()
    {

        $mid = session('mid');
        $this->checkmid($mid);

        $fullname = I('post.fullname');
        $shopping_telephone = I('post.shopping_telephone');
        $address = I('post.address');

        $this->checkparam(array($mid,  $fullname, $shopping_telephone, $address));


        $data = array(
            'mid' => $mid,
            'fullname' => $fullname,
            'shopping_telephone' => $shopping_telephone,
            'address' => $address,
        );

        $result = $this->address_model->add($data);
        if ($result) exit($this->returnApiSuccess());
        else exit($this->returnApiError(BaseController::FATAL_ERROR));
    }

    public function addressDelete()
    {

        $addressid = I('get.id');


        $result = $this->address_model
            ->where(array('id' => $addressid))
            ->delete();



        redirect('/index.php?g=Web&m=PersonalCenter&a=address_list');
    }


    /**
     * 全部订单
     */
    public function orderList()
    {

        $mid = session('mid');
        $this->is_login();
        $key = I('key');
        $ids = $this->order_model->getStatusByKey($key);


        $where = array('mid' => $mid);
        $where['a.hidden'] = 1;

        if (is_array($ids)) {
            $where['a.status'] = array('in', $ids);
        } else {
            $where['a.status'] = $ids;
            if ($ids == OrderModel::ORDER_COMPLETE) {
                $where['a.comment'] = 0;
            }
        }

        $count = $this->order_model
            ->where($where)
            ->join('as a left join bb_logistics as b on a.id=b.order_id')
            ->count();
        $page  = $this->page($count,2);
        $result = $this->order_model
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->join('as a left join bb_logistics as b on a.id=b.order_id')
            ->order('id desc')
            ->field('a.id,a.order_sn,a.create_time,a.status as status_string,a.comment,b.logistics_number,b.logistics_company')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
            $joins2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_option_value as c on a.option = c.product_option_value_id';
            $joins3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as d on c.option_id = d.option_id';
            $data_order_product = $this->order_product_model
                ->alias('a')
                ->join($joins)
                ->join($joins2)
                ->join($joins3)
                ->where(array('a.order_id' => $v['id']))
                ->field('a.id as order_product_id,a.name,a.quantity,a.total,a.price,a.tax,a.option,b.smeta,a.product_id,d.name as option_value_name,b.original_price')
                ->order('a.order_id desc')
                ->select();

            foreach ($data_order_product as $key => $val) {
                $smeta = json_decode($val['smeta'], true);
                $smeta = $smeta['thumb'];
                $smeta = $smeta ? $this->geturl($smeta) : '';
                $data_order_product[$key]['smeta'] = $smeta;

                $result[$k]['total_quantity'] += $val['quantity'];
                $result[$k]['total_money'] += $val['total'];
                $data_order_product[$key]['option_value_name'] = $val['option_value_name'] ? $val['option_value_name'] : '官方标配';
            }

            $result[$k]['total_money'] = number_format( $result[$k]['total_money'] ,2);


            foreach ($data_order_product as $ke => $va) {

                if( $v['status_string'] == OrderModel::ORDER_NOPAY ){
                    $data_order_product[$ke]['url'] = "<li class=\"payment\"><a href='".U('Order/order_type',array('order_sn'=>$v['order_sn'],'total'=>$result[$k]['total_money']))."' >立即支付</a><a href=\"javascript:;\" class='".$v['order_sn']."' onclick='can(this)' id=\"cancel\">取消订单</a></li>";
                }elseif( $v['status_string'] == OrderModel::ORDER_COMPLETE && $v['comment'] == 0){
                    $data_order_product[$ke]['url'] = "<li ><a href='".U('Comment/index',array('order_product_id'=>$va['order_product_id']))."' >去评价</a></li>";
                }elseif( $v['status_string'] == OrderModel::ORDER_PAY_SUCCESS  || $v['status_string'] == OrderModel::ORDER_PRODUCT_SEND  ){
                    $data_order_product[$ke]['url'] = "<li ><a href=\"javascript:;\" class='".$v['order_sn']."' onclick='sig(this)' >确认收货</a></li>";
                }else{
                    $data_order_product[$ke]['url'] = "<li ><a href=\"\" >已评价</a></li>";
                }

            }
            $result[$k]['lists'] = $data_order_product;
            $result[$k]['status_string'] = $this->order_model->getStatusValues($v['status_string'], $v['comment']);
            if( $v['status_string'] == OrderModel::ORDER_PRODUCT_SEND ) {
                $result[$k]['string'] = "<li><span>卖家已发货</span><span><a href=\"#\" class='".$v['id']."' onclick='logi(this)'  id='wuliuxinx'>查看物流</a></span></li>";
            }else{
                $result[$k]['string'] = "<li><h5>".$result[$k]['status_string']."</h5></li>";
            }
        }

        if( I('key') == 'all'){
            $class =   "<li  class=\"order_hover\" ><a href='".U('PersonalCenter/orderList',array('key'=>'all'))."'>所有订单</a><img src=\"/public/Web/images/shadow.png\" /></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'nopay'))."'> 待付款</a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'paysuccess'))."'>    待发货 </a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'reception'))."'>待收货 </a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'comment'))."'>待评价 </a></li>";
        }elseif( I('key') == "nopay"){
            $class =   "<li><a href='".U('PersonalCenter/orderList',array('key'=>'all'))."'>所有订单</a></li>
						<li class=\"order_hover\"><a href='".U('PersonalCenter/orderList',array('key'=>'nopay'))."'> 待付款</a><img src=\"/public/Web/images/shadow.png\" /></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'paysuccess'))."'>    待发货 </a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'reception'))."'>待收货 </a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'comment'))."'>待评价 </a></li>";
        }elseif( I('key') == 'paysuccess' ){
            $class =   "<li><a href='".U('PersonalCenter/orderList',array('key'=>'all'))."'>所有订单</a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'nopay'))."'> 待付款</a></li>
						<li class=\"order_hover\"><a href='".U('PersonalCenter/orderList',array('key'=>'paysuccess'))."'>待发货 </a><img src=\"/public/Web/images/shadow.png\" /></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'reception'))."'>待收货 </a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'comment'))."'>待评价 </a></li>";
        }elseif( I('key') == 'reception' ){
            $class =   "<li><a href='".U('PersonalCenter/orderList',array('key'=>'all'))."'>所有订单</a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'nopay'))."'> 待付款</a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'paysuccess'))."'>    待发货 </a></li>
						<li class=\"order_hover\"><a href='".U('PersonalCenter/orderList',array('key'=>'reception'))."'>待收货 </a><img src=\"/public/Web/images/shadow.png\" /></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'comment'))."'>待评价 </a></li>";
        }elseif( I('key') == 'comment'){
            $class =   "<li><a href='".U('PersonalCenter/orderList',array('key'=>'all'))."'>所有订单</a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'nopay'))."'> 待付款</a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'paysuccess'))."'>    待发货 </a></li>
						<li><a href='".U('PersonalCenter/orderList',array('key'=>'reception'))."'>待收货 </a></li>
						<li class=\"order_hover\"><a href='".U('PersonalCenter/orderList',array('key'=>'comment'))."'>待评价 </a><img src=\"/public/Web/images/shadow.png\" /></li>";
        }


        $this->assign('class',$class);
        $this->assign('list',$result);
        $this->assign('page',$page->show('Admin'));
        $this->display('order');

    }

    /**
     * 前置条件：订单未付款
     *
     * 判断是否为未付款
     */
    public function userCancelOrder()
    {

        $mid = session('mid');
        $this->checkmid($mid);
        $order_sn = I('post.order_sn');

        $this->checkparam(array($mid, $order_sn));

        $where = array('order_sn' => $order_sn);
        $data_order = $this->order_model->where($where)->find();

        if (!$data_order) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));

        if ($data_order['status'] != OrderModel::ORDER_NOPAY) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '非法操作'));
        }

        $result = $this->order_model->where($where)->save(array('status' => OrderModel::ORDER_CANCEL));
        if ($result === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '操作失败'));
        else exit($this->returnApiSuccess());

    }

    /**
     * 前置条件：订单已发货
     *
     * 收货
     */
    public function signinOrder()
    {

        $mid = session('mid');
        $this->checkmid($mid);
        $order_sn = I('post.order_sn');
        $this->checkparam(array($mid, $order_sn));

        $where = array('order_sn' => $order_sn);
        $data_order = $this->order_model->where($where)->find();

        if (!$data_order) exit($this->returnApiError(BaseController::FATAL_ERROR, '订单不存在'));

        if ($data_order['status'] != OrderModel::ORDER_PRODUCT_SEND) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '该订单还未发货，请确认已收到货'));
        }

        $result = $this->order_model->where($where)->save(array('status' => OrderModel::ORDER_COMPLETE));
        if ($result === false) exit($this->returnApiError(BaseController::FATAL_ERROR, '操作失败'));
        else exit($this->returnApiSuccess());
    }

    public function get_logis(){
        $order_id = I('post.order_id');
        $result = $this->logistics_model->where('order_id='.$order_id)->find();
        $this->ajaxReturn($result);
    }
}
