<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;




use Common\Model\CommentModel;
use Common\Model\MemberModel;
use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Think\Controller;

class CommentController extends BaseController
{
    private $order_model;
    private $order_product_model;
    private $product_model;
    private $member_model;
    private $comment_model;

    public function __construct()
    {
        parent::__construct();
        $this->is_login();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->product_model = new ProductModel();
        $this->member_model  = new MemberModel();
        $this->comment_model = new CommentModel();
    }

    public function index(){
        $order_product_id = I('order_product_id');
        $product_id = $this->order_product_model->where(array('id'=>$order_product_id))->field('product_id')->find();

        $data = $this->product_model->where(array('id'=>$product_id['product_id']))->find();
        $data['picture'] = json_decode($data['smeta'],true)['thumb'];
        $data['order_product_id'] = $order_product_id;
        $this->assign( 'list',$data );

        $this->display();

    }

    /**
     * 评价
     */
    public function commentOrder()
    {

        $mid = session('mid');
        $this->checkmid($mid);
        $content = I('post.content');

        $order_product_id = I('post.order_product_id');

        $this->checkparam(array($mid, $content, $order_product_id));

        $data_member = $this->member_model->find($mid);

        $data_order_product = $this->order_product_model->find($order_product_id);
        if (!$data_order_product) exit($this->returnApiError(BaseController::FATAL_ERROR, '请求的数据不存在'));

        $order_id = $data_order_product['order_id'];
        $product_id = $data_order_product['product_id'];
        $comment_status = $data_order_product['comment_status'];

        if ($comment_status == 1)
            exit($this->returnApiError(BaseController::FATAL_ERROR, '不能重复评价'));


        $data = array(
            'product_id' => $product_id,
            'content' => $content,
            'mid' => $mid,
            'create_time' => time(),
            'full_name' => $data_member['nickname'],
        );

        $result_comment = $this->comment_model->add($data);

        $result_order_product = $this->order_product_model
            ->where(array('id' => $order_product_id))
            ->save(array('comment_status' => 1));

        $result = $this->order_product_model->where(array('order_id' => $order_id, 'comment_status' => 0))->count();
        if ($result == 0) {
            $this->order_model->where(array('id' => $order_id))->save(array('comment' => 1));
        }

        exit($this->returnApiSuccess());
    }
}
