<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/16
 * Time: 16:15
 */

namespace Web\Controller;


use Common\Model\BannerImageModel;
use Common\Model\BannerModel;
use Common\Model\BrandModel;
use Common\Model\CategoryModel;
use Common\Model\ProductModel;
use Think\Controller;

class IndexController extends BaseController
{
    protected $banner_model;
    protected $banner_image_model;
    protected $brand_model;
    private   $product_model;
    private   $category_model;
    public function __construct()
    {
        parent::__construct();
        $this->banner_model = new BannerModel();
        $this->banner_image_model = new BannerImageModel();
        $this->brand_model  = new BrandModel();
        $this->product_model = new ProductModel();
        $this->category_model = new CategoryModel();
    }


    public function index(){
        $this->category();
        $this->banner();
        $this->hot_shop();
        $this->brand();
        $this->today_boutique();
        $this->boutique();
        $this->display();
    }

    public function category(){
        $list = $this->category_model->order('listorder')->where(array('parentid' => 0))->field('name,id,images')->select();
        foreach($list as $k => $v ){
            $categ = $this->category_model->order('listorder')->where(array('parentid' => $v['id']))->field('name,id')->select();
            $son['parent_name'] = $v['name'];
            $son['parent_id'] = $v['id'];
            $son['son'] =  $categ;
            $result[] = $son;
        }
        $this->assign('cart_two',$result);
        $this->assign('category',$list);
    }

    public function banner(){
        $banner = $this->banner_model->where(array('type'=>1))->field('id')->find();
        $list   = $this->banner_image_model->where(array('banner_id'=>$banner['id']))->field('image,link')->select();
        $this->assign('banner',$list);
    }

    public function brand(){
        $list  = $this->brand_model->where(array('exhibition'=>1))->limit(6)->order('id desc')->field('image,link')->select();
        $this->assign('brand',$list);
    }

    public function hot_shop(){

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';

        $where['a.hot']    = 1;
        $where['a.status'] = 1;
        $where['del'] = 1;

        $result = $this->product_model
            ->where($where)
            ->join($join)
            ->join($join2)
            ->alias("a")
            ->field('a.*,b.name as category_name,c.name as brand_name')
            ->order('a.id desc')
            ->limit(9)
            ->select();

        foreach($result as $v){
            $a['sales_volume'] = $v['sales_volume'];
            $a['id'] = $v['id'];
            $a['name'] = $v['name'];
            $a['price'] = $v['price'];
            $a['original_price'] = $v['original_price'];
            $a['category_name'] = $v['category_name'];
            $a['picture'] = json_decode($v['smeta'],true)['thumb'];
            $data[] = $a;
        }

        $this->assign('hot_shop',$data);

    }

    public function today_boutique(){

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';

        $where['a.today_boutique'] = 1;

        $where['a.status'] = 1;
        $where['del'] = 1;



        $result = $this->product_model
            ->where($where)
            ->join($join)
            ->join($join2)
            ->alias("a")
            ->field('a.*,b.name as category_name,c.name as brand_name')
            ->order('a.id desc')
            ->limit(4)
            ->select();

        foreach($result as $v){
            $a['id'] = $v['id'];
            $a['name'] = $v['name'];
            $a['price'] = $v['price'];
            $a['original_price'] = $v['original_price'];
            $a['category_name'] = $v['category_name'];
            $a['picture'] = json_decode($v['smeta'],true)['thumb_pc'];
            $data[] = $a;

        }

        $this->assign('today_boutique',$data);

    }

    public function boutique(){

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';

        $where['a.boutique'] = 1;
        $where['a.status'] = 1;
        $where['del'] = 1;

        $result = $this->product_model
            ->where($where)
            ->join($join)
            ->join($join2)
            ->alias("a")
            ->field('a.*,b.name as category_name,c.name as brand_name')
            ->order('a.id desc')
            ->limit(3)
            ->select();

        foreach($result as $v){

            $whe['category_id'] = $v['category_id'];
            $whe['status'] = 1;
            $whe['del'] = 1;
            $res = $this->product_model
                        ->where($whe)
                        ->limit(3)
                        ->order('id desc')
                        ->field('id,name,price,original_price,smeta')
                        ->select();
                    $product =  '';
                foreach($res as $d){


                    $b['id'] = $d['id'];
                    $b['name'] = $d['name'];
                    $b['price'] = $d['price'];
                    $b['original_price'] = $d['original_price'];
                    $b['category_name']  = $d['category_name'];
                    $b['picture'] = json_decode($d['smeta'],true)['thumb'];
                    $product[] = $b;
                }

            $a['product'] = $product;
            $a['id'] = $v['id'];
            $a['name'] = $v['name'];
            $a['price'] = $v['price'];
            $a['original_price'] = $v['original_price'];
            $a['category_name']  = $v['category_name'];
            $a['picture'] = json_decode($v['smeta'],true)['boutique'];
            $data[] = $a;

        }

        $this->assign('boutique',$data);

    }


}