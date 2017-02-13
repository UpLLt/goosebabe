<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:15
 */

namespace Appapi\Controller;


use Common\Model\BannerImageModel;
use Common\Model\BannerModel;
use Common\Model\BrowseProductModel;
use Common\Model\CartModel;
use Common\Model\CategoryModel;
use Common\Model\CommentModel;
use Common\Model\OptionModel;
use Common\Model\OrderModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Common\Model\ProductOptionValueModel;
use Common\Model\ProductSkuModel;

class ProductController extends ApibaseController
{
    private $product_model;
    private $category_model;
    private $banner_model;
    private $banner_image_model;
    private $browse_product_model;
    private $cart_model;
    private $comment_model;

    private $option_model;
    private $product_option_model;
    private $product_option_value_model;

    private $product_sku_model;


    public function __construct()
    {
        parent::__construct();
        $this->category_model = new CategoryModel();
        $this->product_model = new ProductModel();
        $this->banner_model = new BannerModel();
        $this->banner_image_model = new BannerImageModel();
        $this->browse_product_model = new BrowseProductModel();
        $this->comment_model = new CommentModel();
//        $this->cart_model = new CartModel();

        $this->product_option_model = new ProductOptionModel();
        $this->product_option_value_model = new ProductOptionValueModel();

        $this->product_sku_model = new ProductSkuModel();
        $this->option_model = new OptionModel();
    }


    public function homeProduct()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $where = [
            'status' => ProductModel::STATUS_PUTAWAY,
            'del'    => 1,
        ];

        $field = 'id,name,price,original_price,smeta,sales_volume';
        $today = $this->product_model
            ->where($where)
            ->where(['today_boutique' => 1])
            ->field($field)
            ->order('updata_time desc')
            ->select();
        $hot = $this->product_model
            ->where($where)
            ->where(['hot' => 1])
            ->order('updata_time desc')
            ->field($field)
            ->select();

        foreach ($today as $k => $v) {
            $today[$k]['smeta'] = json_decode($v['smeta'], true);
            if (!empty($today[$k]['smeta'])) {
                $today[$k]['smeta']['thumb'] = $this->geturl($today[$k]['smeta']['thumb']);
                $today[$k]['smeta']['advertisement'] = $this->geturl($today[$k]['smeta']['advertisement']);
                unset($today[$k]['smeta']['photo']);
//                $today[$k]['smeta']['photo'] = $this->geturl($today[$k]['smeta']['photo']);
            }

        }

        unset($v);
        foreach ($hot as $k => $v) {
            $hot[$k]['smeta'] = json_decode($v['smeta'], true);
            if (!empty($hot[$k]['smeta'])) {
                $hot[$k]['smeta']['thumb'] = $this->geturl($hot[$k]['smeta']['thumb']);
                $hot[$k]['smeta']['advertisement'] = $this->geturl($hot[$k]['smeta']['advertisement']);
                unset($hot[$k]['smeta']['photo']);
//                $hot[$k]['smeta']['photo'] = $this->geturl($hot[$k]['smeta']['photo']);
            }
        }

        $data['today'] = $today;
        $data['hot'] = $hot;

        exit($this->returnApiSuccess($data));
    }


    public function categoryList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $category = $this->category_model
            ->where(['parentid' => 0])
            ->order('listorder asc')
            ->select();
        foreach ($category as $k => $v) {
            $category[$k]['images'] = $this->geturl($v['images']);
        }
        unset($v);

        $banner_home = $this->banner_model
            ->where(['name' => '首页轮播图'])
            ->find();
        $banner_id = $banner_home['id'];
        $bannerlist = $this->banner_image_model
            ->where(['banner_id' => $banner_id])
            ->order('sort_order asc')
            ->select();
        foreach ($bannerlist as $k => $v) {
            $bannerlist[$k]['image'] = $this->geturl($v['image']);
        }

        $data['category'] = $category;
        $data['banner'] = $bannerlist;
        exit($this->returnApiSuccess($data));
    }


    public function detail()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $this->checkparam([$product_id]);

        $field = 'a.id,a.inventory,a.name as product_name,a.price,a.original_price,a.smeta,b.name as category_name,c.name as brand_name,a.ship_address';
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';
        $data = $this->product_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where(['a.id' => $product_id])
            ->field($field)
            ->find();

        if (!$data) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));
        }
        $data['smeta'] = json_decode($data['smeta'], true);
        $data['smeta']['thumb'] = $this->geturl($data['smeta']['thumb']);
        $data['smeta']['advertisement'] = $this->geturl($data['smeta']['advertisement']);
        foreach ($data['smeta']['photo'] as $k => $v) {
            $data['smeta']['photo'][$k] = $this->geturl('/data/upload/' . $v['url']);
        }
        unset($v);
        $data['url'] = $this->geturl('/Wap/Product/detail/id/' . $product_id);

//        $this->product_option_model = new ProductOptionModel();
//        $this->product_option_value_model = new ProductOptionValueModel();

//        $data_option = $this->product_option_model
//            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'option as b on a.option_id = b.option_id')
//            ->alias('a')
//            ->where(array('a.product_id' => $product_id))
//            ->field('a.product_option_id,b.name as option_name')
//            ->select();

        unset($v);
//        foreach ($data_option as $k => $v) {
//
//            $optionlist = $this->product_option_value_model
//                ->alias('a')
//                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'option_value as b on a.option_value_id = b.option_value_id')
//                ->where(array('a.product_option_id' => $v['product_option_id']))
//                ->field('a.product_option_value_id,a.quantity,a.price,a.price_prefix,b.name as option_value_name')
//                ->select();
//
//            foreach ($optionlist as $key => $val) {
//                if ($val['price_prefix'] == '+')
//                    $optionlist[$key]['price_prefix'] = 'add';
//                if ($val['price_prefix'] == '-')
//                    $optionlist[$key]['price_prefix'] = 'sub';
//            }
//            unset($val);
//            $data_option[$k]['optionlist'] = $optionlist;
//            $data_option[$k]['option_name'] = '规格';
//        }

        $product_options = $this->product_sku_model->where(['product_key_id' => $product_id])->select();
        foreach ($product_options as $k => $v) {
            if ($v['quantity'] < 1) {
                unset($product_options[$k]);
                continue;
            }
            $option_names = $this->option_model->where(['option_key_id' => ['in', $v['attr_option_path']]])->field('option_name')->order('option_key_id asc')->select();
            $optionname = '';
            foreach ($option_names as $key => $val) {
                $optionname .= $val['option_name'];
            }
            $product_options[$k]['optionname'] = $optionname;
        }
        $product_options = array_merge($product_options);
        $data_option = $product_options;

        if (!$data_option) {
            $data_option[] = [
                'sku_id'           => '0',
                'attr_option_path' => '0',
                'product_key_id'   => '0',
                'price'            => '0',
                'freight'          => '0',
                'quantity'         => '0',
                'optionname'       => '库存不足',
            ];
        }

        $data['option'] = $data_option;


        if ($mid && $token) {
            $this->checkparam([$mid, $token]);
            if (!$this->checktoken($mid, $token)) {
                exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
            }
            //浏览记录
            $count = $this->browse_product_model->where(['mid' => $mid, 'product_id' => $product_id])->count();
            if ($count == 0)
                $browse_logs = $this->browse_product_model
                    ->add(['mid' => $mid, 'product_id' => $product_id, 'update_time' => time()]);
            else
                $browse_logs = $this->browse_product_model
                    ->where(['mid' => $mid, 'product_id' => $product_id])
                    ->save(['update_time' => time()]);
        }

        $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $comments = $this->comment_model
            ->where(['product_id' => $product_id, 'status' => 1])
            ->order('a.id desc')
            ->alias('a')
            ->join($join3)
            ->field('a.id as cid,a.content,a.create_time,a.full_name,b.headimg')
            ->select();

        unset($v);

        foreach ($comments as $k => $v) {
            $comments[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            $comments[$k]['headimg'] = $this->geturl($v['headimg']);
        }
        unset($v);
        $data['comments'] = $comments;

        exit($this->returnApiSuccess($data));
    }


    public function productList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $category_id = I('post.category_id');
        $page = I('post.page');
        $pagenum = I('post.pagenum');

        $sort_price = I('post.sort_price');
        $sort_sales = I('post.sort_sales');

        $this->checkparam([$category_id, $page, $pagenum]);
        $order['id'] = 'desc';
        if (!empty($sort_price) || !empty($sort_sales)) {
            $order = [];
        }
        if (!empty($sort_price)) {
            $order['price'] = $sort_price;
        }
        if (!empty($sort_sales)) {
            $order['sales_volume'] = $sort_sales;
        }

        $parent_id = $this->category_model->where(['id' => $category_id])->getField('parentid');

        if ($parent_id == '0') {
            $category_list = $this->category_model->where(['parentid' => $category_id])->getField('id', true);
            $categorys = '';
            foreach ($category_list as $k => $v) {
                $categorys .= $categorys ? ',' . $v : $v;
            }
            $where = [
                'category_id' => ['in', $categorys],
                'status'      => ProductModel::STATUS_PUTAWAY,
                'del'         => 1,
            ];
        } else {
            $where = [
                'category_id' => $category_id,
                'status'      => ProductModel::STATUS_PUTAWAY,
                'del'         => 1,
            ];
        }

        $count = $this->product_model
            ->where($where)
            ->count();

        if ($count > 0) {

            $result = $this->product_model
                ->where($where)
                ->field('id,name,price,original_price,smeta,ship_address,inventory,tariff,sales_volume')
                ->limit(($page - 1) * $pagenum, $pagenum)
                ->order($order)
                ->select();

            foreach ($result as $k => $v) {
                $smeta = json_decode($v['smeta'], true);
                $smeta = $smeta ? $this->geturl($smeta['thumb']) : '';
                $result[$k]['smeta'] = $smeta;
            }
        }

        if ($count > 0) {
            $Totalpage = $count / $pagenum;
            $Totalpage = floor($Totalpage);
            $b = $count % $pagenum;
            if ($b) $Totalpage += 1;

            $data['lists'] = $result;
            $data['page'] = $page;
            $data['Totalpage'] = $Totalpage;

        } else {
            $data['lists'] = [];
            $data['page'] = 0;
            $data['Totalpage'] = 0;
        }

        exit($this->returnApiSuccess($data));
    }


    public function browseLogs()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');

        $this->checkparam([$mid, $token]);

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $joins = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $result = $this->browse_product_model
            ->join($joins)
            ->alias('a')
            ->where(['mid' => $mid])
            ->field('a.* , b.name as product_name , b.smeta ,b.price')
            ->limit(0, 10)
            ->order('a.update_time desc')
            ->select();


        foreach ($result as $k => $v) {
            $smeta = json_decode($v['smeta'], true);
            $smeta = $smeta['thumb'];
            $smeta = $smeta ? $this->geturl($smeta) : '';

            $result[$k]['smeta'] = $smeta;
            $result[$k]['update_time'] = date('Y-m-d', $v['update_time']);
        }


        exit($this->returnApiSuccess($result));
    }

    public function search()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $keyword = I('post.keyword');
        $page = I('post.page');
        $pagenum = I('post.pagenum');

        $this->checkparam([$page, $pagenum]);

        if (!empty($keyword)) {
            $where = [
                'name' => ['like', "%$keyword%"],
            ];
        }

        $count = $this->product_model
            ->where($where)
            ->count();

        if ($count > 0) {
            $result = $this->product_model
                ->where($where)
                ->field('id,name,price,original_price,smeta,ship_address,inventory,tariff,sales_volume')
                ->limit(($page - 1) * $pagenum, $pagenum)
                ->select();

            foreach ($result as $k => $v) {
                $smeta = json_decode($v['smeta'], true);
                $smeta = $smeta ? $this->geturl($smeta['thumb']) : '';
                $result[$k]['smeta'] = $smeta;
            }
        }

        if ($count > 0) {
            $Totalpage = $count / $pagenum;
            $Totalpage = floor($Totalpage);
            $b = $count % $pagenum;
            if ($b) $Totalpage += 1;

            $data['lists'] = $result;
            $data['page'] = $page;
            $data['Totalpage'] = $Totalpage;
        } else {
            $data['lists'] = [];
            $data['page'] = 0;
            $data['Totalpage'] = 0;
        }

        exit($this->returnApiSuccess($data));

    }

    /**
     * 二级分类查询
     */
    public function categorylistsearch()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $category_id = I('post.category_id');
        $result = $this->category_model
            ->where(['parentid' => $category_id])
            ->field('id as category_id,name,parentid')
            ->select();
        exit($this->returnApiSuccess($result));
    }



    /**
     *
     *
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
     * V 1.1.0
     */
    public function detailV2()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $this->checkparam([$product_id]);

        $field = 'a.id,a.inventory,a.name as product_name,a.price,a.original_price,a.smeta,b.name as category_name,c.name as brand_name,a.ship_address';
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';
        $data = $this->product_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->where(['a.id' => $product_id])
            ->field($field)
            ->find();

        if (!$data) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));
        }
        $data['smeta'] = json_decode($data['smeta'], true);
        $data['smeta']['thumb'] = $this->geturl($data['smeta']['thumb']);
        $data['smeta']['advertisement'] = $this->geturl($data['smeta']['advertisement']);
        foreach ($data['smeta']['photo'] as $k => $v) {
            $data['smeta']['photo'][$k] = $this->geturl('/data/upload/' . $v['url']);
        }
        unset($v);
        $data['url'] = $this->geturl('/Wap/Product/detail/id/' . $product_id);


        if ($mid && $token) {
            $this->checkparam([$mid, $token]);
            if (!$this->checktoken($mid, $token)) {
                exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
            }
            //浏览记录
            $count = $this->browse_product_model->where(['mid' => $mid, 'product_id' => $product_id])->count();
            if ($count == 0)
                $browse_logs = $this->browse_product_model
                    ->add(['mid' => $mid, 'product_id' => $product_id, 'update_time' => time()]);
            else
                $browse_logs = $this->browse_product_model
                    ->where(['mid' => $mid, 'product_id' => $product_id])
                    ->save(['update_time' => time()]);
        }

        $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $comments = $this->comment_model
            ->where(['product_id' => $product_id, 'status' => 1])
            ->order('a.id desc')
            ->alias('a')
            ->join($join3)
            ->field('a.id as cid,a.content,a.create_time,a.full_name,b.headimg')
            ->select();

        unset($v);

        foreach ($comments as $k => $v) {
            $comments[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            $comments[$k]['headimg'] = $this->geturl($v['headimg']);
        }
        unset($v);
        $data['comments'] = $comments;

        exit($this->returnApiSuccess($data));
    }
}