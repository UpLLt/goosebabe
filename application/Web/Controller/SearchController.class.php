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
use Common\Model\BrandModel;
use Common\Model\CategoryAttrModel;
use Common\Model\CategoryModel;
use Common\Model\ProductModel;
use Common\Model\ProductSkuModel;
use Think\Controller;

class SearchController extends BaseController
{

    private $attr_model;
    private $category_attr_model;
    private $category_model;
    private $attr_option;
    private $product_model;
    private $product_sku_model;
    private $brand_model;

    public function __construct()
    {
        parent::__construct();
        $this->attr_model = new AttrModel();
        $this->category_model = new CategoryModel();
        $this->category_attr_model = new CategoryAttrModel();
        $this->attr_option = new AttrOptionModel();
        $this->product_model = new ProductModel();
        $this->product_sku_model = new ProductSkuModel();
        $this->brand_model = new BrandModel();
    }


    public function index()
    {
        $category = I('category');
		
        $category  = $this->category($category);
        $this->brand($category);
        $this->search($category);
        $this->_list($category);
        $this->assign('category',$category);
        $this->display();
    }


    public function category($category){
        //判断是否为父级 分类
        $madify_ca = $this->category_model->where(array('id'=>$category))->field('parentid')->find();
        $son_str = "";
        if( $madify_ca['parentid'] == 0 ){
            $son = $this->category_model->where(array('parentid'=>$category))->field('id,name')->select();
            foreach( $son as $k => $v ){
                $son_str .= "<dd option='".$v['id']."'><a href='javascript:void(0);' >".$v['name']."</a></dd>";
            }
            $son_str = "<dd class='select-all selected' ><a href='javascript:void(0);'>全部</a></dd>".$son_str;
            $category =  $category;
			 
        } else{
            $son = $this->category_model->where(array('parentid'=>$madify_ca['parentid']))->field('id,name')->select();
            foreach( $son as $k => $v ){
                $select = $v['id'] == $category ? "class = 'selected'" : "";
                $son_str .= "<dd option='".$v['id']."' ".$select."><a href='javascript:void(0);' >".$v['name']."</a></dd>";
            }
            $son_str = "<dd ><a href='javascript:void(0);'>全部</a></dd>".$son_str;
            //$category = $madify_ca['parentid'];
        }
        $cate_name = $this->category_model->where(array('id'=>$category))->field('name')->find();

        $this->assign('cate_name',$cate_name['name']);
        $this->assign('son_str',$son_str);
        return $category;

    }


    /**
     * @param $category
     * 品牌
     */
    public function brand($category){
        $madify_ca = $this->category_model->where(array('id'=>$category))->field('parentid')->find();
        if( $madify_ca['parentid'] == 0 ){
            $son = $this->category_model->where(array('parentid'=>$category))->field('id')->select();
            $category_list = '';
            foreach( $son as $k => $v ){
                $category_list .= $category_list ? ','.$v['id'] : $v['id'];
            }
            $where['category_id'] = array('in',$category_list);
            $brand =  $this->product_model
                ->where($where)
                ->group('brand_id')
                ->field('brand_id')
                ->select();
        }else{
            $brand =  $this->product_model
                ->where(array('category_id' => $category))
                ->group('brand_id')
                ->field('brand_id')
                ->select();
        }

        $brand_id ='';
        foreach( $brand as $k => $v ){
            $brand_id .= $brand ? ','.$v['brand_id'] : $v['brand_id'];
        }


        $where['id'] = array('in',$brand_id);
        $brand_mo = $this->brand_model->where($where)->field('id,name')->select();

        $brand_str = '';
        foreach( $brand_mo as $key => $value ){
            $brand_str .= "<dd option='".$value['id']."'><a href='javascript:void(0);' >".$value['name']."</a></dd>";
        }



        $brand_str = "<dt>品牌</dt><dd class='select-all selected' ><a href='javascript:void(0);'>全部</a></dd>".$brand_str;



        $this->assign('brand_str' , $brand_str );

     }


    private function search($category)
    {

        $where = array('b.category_key_id' => $category);

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category_attr as b on a.attr_key_id = b.attr_key_id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as c on b.category_key_id = c.id';
        $attr = $this->attr_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->field('a.attr_name,b.attr_key_id')
            ->where($where)
            ->select();

        foreach ($attr as $k => $v) {
            $attr[$k]['number'] = $k + 1;
            $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'attr_option as b on a.attr_key_id = b.attr_key_id';
            $attr_where = array('a.attr_key_id' => $v['attr_key_id']);
            $attr_a = $this->category_attr_model
                ->alias('a')
                ->join($join3)
                ->where($attr_where)
                ->field('b.option_key_id')
                ->select();

            foreach ($attr_a as $key => $val) {
                $join4 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option as b on a.option_key_id = b.option_key_id';
                $option_where = array('a.option_key_id' => $val['option_key_id']);
                $option = $this->attr_option
                    ->alias('a')
                    ->where($option_where)
                    ->join($join4)
                    ->field('b.option_key_id,b.option_name')
                    ->find();
                $attr_a[$key]['option_name'] = $option['option_name'];
            }
            $attr[$k]['attr'] = $attr_a;

        }
		
        $this->assign('select_list', $attr);

    }

    public function _list($category)
    {		
		
	    $where['status'] = 1;
        $where['del'] = 1;
        $order = 'id desc';
        $_GET['category'] = $category;
		
		
        $parent_id = $this->category_model->where(array('id'=>$category))->field('parentid')->find();
		
        if( $parent_id['parentid'] == 0 ) {
             $all = $this->category_model->where(array('parentid'=>$category))->field('id')->select();
			 
                $cate = array();
                foreach( $all as $k => $v ){
                    $cate[] = array('eq',$v['id']);
                }
                    $cate[] = array('eq',$category);
                    $cate[] = 'or';
            $where['category_id'] = $cate;
        }else{
            $where['category_id'] = $category;
        }
		
        $count = $this->product_model
            ->where($where)
            ->count();
			
			
        $page  = $this->page($count,8);
        $product = $this->product_model
            ->where($where)
            ->order($order)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $bread_list = '';
        foreach ($product as $k => $v) {
            $bread_list .= "	<li>
                                <a href='".U('Order/index',array('id'=>$v['id']))."'><img src='".json_decode($v['smeta'], true)['thumb']."' /></a>

                                <h1><span>￥".$v['price']."</span><del>￥".$v['original_price']."</del><b>已售".$v['sales_volume']."件</b></h1>

                                <p><a href='".U('Order/index',array('id'=>$v['id']))."'>".$v['name']."</a></p>
				         	</li>";
        }



        $this->assign('page',$page->show('Admin'));
        $this->assign('bread_list',$bread_list);
        $this->assign('count', $count);
        $this->assign('product', $product);
    }

	 public function ajax_search(){

        $category = I('category');
        $order = I('order');
        $option_path = I('option_path');
        $brand = I('brand');
        $classify = I('classify');


        
         if(empty($classify) || $classify== 'undefined')  {
             $areasd = $this->category_model->where('id='.$category)->find();
             if( empty($areasd['parentid'])){
                 $classify  = $category;
             }else{
                 $classify  = $areasd['parentid'];
             }

         }


        if( $brand )    $where['brand_id'] =  $brand;
         $parent_id = $this->category_model->where('id='.$classify)->find();



        if( $parent_id['parentid'] == 0 ) {
            $all = $this->category_model->where(array('parentid'=>$classify))->field('id')->select();
            $cate = array();
            foreach( $all as $k => $v ){
                $cate[] = array('eq',$v['id']);
            }
            $cate[] = array('eq',$classify);
            $cate[] = 'or';

            $where['category_id'] = $cate;
        }else{
            $where['category_id'] = $classify;


        }




        $order = empty ( $order ) ? 'id desc' : $order . ' desc';
        if ($option_path) {

            //按照重小到大排序
            $option_path = explode(',',$option_path);
            asort($option_path);
            $option_path = implode(",",$option_path);


            //筛选查询
            $where_sku['attr_option_path'] = array('like','%'.$option_path.'%');
            $sku = $this->product_sku_model
                ->where($where_sku)
                ->field('product_key_id')
                ->select();

            $pro_key = '';
            foreach( $sku as $k => $v ){
                $pro_key .= $pro_key ? ','.$v['product_key_id'] : $v['product_key_id'];
            }

            $where = array(
                'id' => array('in',$pro_key),

				'category_id'=>$where['category_id'],
            );
        }
				
        $page = I('post.page');
        if(empty($page)) $page = 1;
         $where['status'] = 1;
         $where['del'] = 1;
        $count = $this->product_model
            ->where($where)
            ->count();

        $star = ($page - 1) * 8;
        $Allpage = ceil($count /8);

        $product = $this->product_model
            ->where($where)
            ->limit($star,8)
            ->order($order)
            ->select();


        $pro = $page - 1;
        if ($pro == 0) $pro = 1;
        $next = $page + 1;
        if ($next > $Allpage) $next = $page;



        $str1 = '<a href="javascript:void(0)"  onclick="page(this)" name="1" >首页</a><a href="javascript:void(0)"  onclick="page(this)" name="' . $pro . '">上一页</a>';

        $str2 = '';
        if( $Allpage > 15 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
            $page_end =  $page + 5;
            $page_start   =  $page - 5;
        }
        for( $i=1 ; $i<=$Allpage ;$i++ ){

            if( $Allpage > 15 ){

                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else if( $i >= $page_start && $i <= $page_end){
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }

            }else{
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else{
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }

            }


        }

         if( $Allpage > 20 ) {
             $str2 .= '<a  href="javascript:void(0);"   >...</a>';
         }

        $str3 = '<a href="javascript:void(0)"  onclick="page(this)" name="' . $next . '">下一页</a>';

        $page = $str1.$str2.$str3;

        $bread_list = '';
        foreach ($product as $k => $v) {
            $bread_list .= "	<li>
                                <a href='".U('Order/index',array('id'=>$v['id']))."'><img src='".json_decode($v['smeta'], true)['thumb']."' /></a>

                                <h1><span>￥".$v['price']."</span><del>￥".$v['original_price']."</del><b>已售".$v['sales_volume']."件</b></h1>

                                <p><a href='".U('Order/index',array('id'=>$v['id']))."'>".$v['name']."</a></p>
				         	</li>";
        }

            if( $Allpage == 1 ) $page = '';

            $data['brand_str'] = $this->ajax_brand($classify);
            $data['bread_list'] = $bread_list;
            $data['count'] = $count;
            $data['page']  = $page;


            exit($this->returnApiSuccess($data));

    }

    public function ajax_brand($category){

        $madify_ca = $this->category_model->where(array('id'=>$category))->field('parentid')->find();

        if( $madify_ca['parentid'] == 0 ){
            $son = $this->category_model->where(array('parentid'=>$category))->field('id')->select();
            $category_list = '';
            foreach( $son as $k => $v ){
                $category_list .= $category_list ? ','.$v['id'] : $v['id'];
            }
            $where['category_id'] = array('in',$category_list);
            $brand =  $this->product_model
                ->where($where)
                ->group('brand_id')
                ->field('brand_id')
                ->select();
        }else{
            $brand =  $this->product_model
                ->where(array('category_id' => $category))
                ->group('brand_id')
                ->field('brand_id')
                ->select();
        }

        $brand_id ='';
        foreach( $brand as $k => $v ){
            $brand_id .= $brand ? ','.$v['brand_id'] : $v['brand_id'];
        }

        $where['id'] = array('in',$brand_id);
        $brand_mo = $this->brand_model->where($where)->field('id,name')->select();

        $brand_str = '';
        foreach( $brand_mo as $key => $value ){
            $brand_str .= "<dd option='".$value['id']."'><a href='javascript:void(0);' >".$value['name']."</a></dd>";
        }


        $brand_str = "<dt>品牌</dt><dd class='select-all selected' ><a href='javascript:void(0);'>全部</a></dd>".$brand_str;
        return $brand_str;
    }
	
   
    /**
     * 搜索
     */

    public function all_search(){
        $key = I('key');
        $_GET['key'] = $key;
        $where['name'] = array('like','%'.$key.'%');
        $where['status'] = 1;
        $where['del'] = 1;
        $count = $this->product_model
            ->where($where)
            ->count();
        $page  = $this->page($count,8);
        $product = $this->product_model
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $bread_list = '';

        foreach ($product as $k => $v) {
            $bread_list .= "<li>
                                <a href='".U('Order/index',array('id'=>$v['id']))."'><img src='".json_decode($v['smeta'], true)['thumb']."' /></a>
                                <h1><span>￥".$v['price']."</span><del>￥".$v['original_price']."</del><b>已售".$v['sales_volume']."件</b></h1>
                                <p><a href='".U('Order/index',array('id'=>$v['id']))."'>".$v['name']."</a></p>
				         	</li>";
        }


        $this->assign('count',$count);
        $this->assign('bread_list',$bread_list);
        $this->assign('page',$page->show('Admin'));
        $this->display();
    }





}
