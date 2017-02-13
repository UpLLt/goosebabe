<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 15:36
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\AttrModel;
use Common\Model\AttrOptionModel;
use Common\Model\BrandModel;
use Common\Model\CategoryAttrModel;
use Common\Model\CategoryModel;
use Common\Model\OptionModel;
use Common\Model\ProductModel;
use Common\Model\ProductSkuModel;
use Think\Log;

class GoodsController extends AdminbaseController
{

    private $product_model;
    private $brand_model;
    private $category_model;

    private $option_model;
    private $attr_model;

    private $category_attr_model;
    private $attr_option_model;

    private $product_sku_model;

    public function __construct()
    {
        parent::__construct();
        $this->product_model = new ProductModel();
        $this->brand_model = new BrandModel();
        $this->category_model = new CategoryModel();

        $this->option_model = new OptionModel();

        $this->attr_model = new AttrModel();
        $this->attr_option_model = new AttrOptionModel();
        $this->category_attr_model = new CategoryAttrModel();

        $this->product_sku_model = new ProductSkuModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    public function add()
    {
        $brand_data = $this->brand_model->select();
        $brand_option = '';
        foreach ($brand_data as $k => $v) {
            $brand_option .= '<option value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        unset($v);
        $category_list = $this->_getCategoryTree('', true);
        $this->assign('brandoption', $brand_option);
        $this->assign('categoryoption', $category_list);
        $this->display();
    }

    private function _lists()
    {
        $fields = [
            'keyword'         => ["field" => "a.name", "operator" => "like", 'datatype' => 'string'],
            'select_category' => ["field" => "b.id", "operator" => "=", 'datatype' => 'string'],
        ];


        if (IS_POST) {
            if (I('post.keyword')) {
                $where['a.name'] = ['like', "%" . I('post.keyword') . "%"];
                $_GET['keyword'] = I('post.keyword');
            }

            if (I('post.select_category')) {
                $cat = $this->category_model->find(I('post.select_category'));
                if ($cat['parentid'] == 0) {
                    $ids = $this->category_model->field('id')->where(['parentid' => I('post.select_category')])->select();
                    $id_str = '';
                    foreach ($ids as $v) {
                        $id_str .= $id_str ? ',' . $v['id'] : $v['id'];
                    }
                    $where['b.id'] = ['in', $id_str];
                } else {
                    $where['b.id'] = I('post.select_category');
                }
                $_GET['select_category'] = I('post.select_category');
            }

        } else {
            if (I('get.keyword')) {
                $where['a.name'] = ['like', "%" . I('get.keyword') . "%"];
                $_GET['keyword'] = I('get.keyword');
            }


            if (I('get.select_category')) {
                $cat = $this->category_model->find(I('get.select_category'));
                if ($cat['parentid'] == 0) {
                    $ids = $this->category_model->field('id')->where(['parentid' => I('get.select_category')])->select();
                    $id_str = '';
                    foreach ($ids as $v) {
                        $id_str .= $id_str ? ',' . $v['id'] : $v['id'];
                    }
                    $where['b.id'] = ['in', $id_str];
                } else {
                    $where['b.id'] = I('get.select_category');
                }
                $_GET['select_category'] = I('get.select_category');
            }
        }

        $where['del'] = 1;

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'brand as c on a.brand_id = c.id';

        $count = $this->product_model
            ->where($where)
            ->join($join)
            ->join($join2)
            ->alias("a")
            ->count();

        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->product_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->join($join)
            ->join($join2)
            ->alias("a")
            ->field('a.*,b.name as category_name,c.name as brand_name')
            ->order('a.id desc')
            ->select();


        $tablebody = '';
        foreach ($result as $k => $v) {

//            $result[$k]['inventory'] = $this->product_sku_model->where(['product_key_id' => $v['id']])->sum('quantity');

            $result[$k]['str_manage'] .= '<a href="' . U('Goods/edit', ['id' => $v['id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Goods/delete', ['id' => $v['id']]) . '">删除</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Goods/updateStatus', ['id' => $v['id']]) . '">' . ($v['status'] == 1 ? '下架' : '上架') . '</a>';

            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Goods/hotmanage', ['id' => $v['id']]) . '">' . ($v['hot'] == 1 ? '取消APP热卖' : 'APP热卖') . '</a>';

            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Goods/todaymanage', ['id' => $v['id']]) . '">' . ($v['today_boutique'] == 1 ? '取消APP今日精品推荐' : 'APP今日精品推荐') . '</a>';

            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Goods/boutiquemanage', ['id' => $v['id']]) . '">' . ($v['boutique'] == 1 ? '取消PC精品推荐' : 'PC精品推荐') . '</a>';

//            $result[$k]['str_manage'] .= ' | ';
//            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Goods/topmanage', ['id' => $v['id']]) . '">' . ($v['top'] == 1 ? '取消首页推荐' : '首页推荐') . '</a>';

            $tablebody .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['id'] . '</td>
            <td>' . $result[$k]['name'] . '</td>
            <td>' . $result[$k]['category_name'] . '</td>
            <td>' . $result[$k]['brand_name'] . '</td>
            <td>' . $result[$k]['price'] . '</td>
            <td>' . $result[$k]['original_price'] . '</td>
            <td>' . $result[$k]['sales_volume'] . '</td>
            <td>' . ($v['inventory'] ? $v['inventory'] : 0) . '</td>
            <td>' . $result[$k]['ship_address'] . '</td>
            <td>' . $this->product_model->getStatusTostring($v['status']) . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }


        unset($v);
//        $categorys = $this->category_model->field('id,name')->select();
//        foreach ($categorys as $k => $v) {
//            $stat = '';
//            if (isset($_GET['select_category']) && $v['id'] == I('get.select_category')) $stat = 'selected="selected"';
//            $category_option .= '<option  ' . $stat . '  value="' . $v['id'] . '">' . $v['name'] . '</option>';
//        }


        $result = $this->category_model->select();

        $select_default = true;
        foreach ($result as $k => $v) {
            if (isset($_GET['select_category']) && $v['id'] == I('get.select_category')) {
                $select_default = false;
                $result[$k]['selected'] = 'selected';
            } else {
                $result[$k]['selected'] = '';
            }
        }


        $tree = new \Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);
        $str = "<option value='\$id' \$selected>\$spacer\$name</option>";
        $taxonomys = $tree->get_tree(0, $str);

        $this->assign('category_model', $taxonomys);
        $this->assign('formget', I(''));
        $this->assign('categorys', $tablebody);
        $this->assign("Page", $page->show());
    }


    public function add_post()
    {
        if (IS_POST) {
            $data = I('post.post');
            $data['content'] = htmlspecialchars_decode($data['content']);
            if (!empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['smeta']['photo'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }
            $data['smeta'] = json_encode(I('post.smeta'));
            if ($this->product_model->create($data)) {
                if ($this->product_model->add()) {
                    $this->success('success');
                } else {
                    $this->error('error');
                }
            } else {
                $this->error($this->product_model->getError());
            }
        }
    }


    public function updateStatus()
    {
        $id = intval(I('id'));
        if (empty($id)) $this->error('empty');
        $status = $this->product_model->where(['id' => $id])->getField('status');
        $status = $status == ProductModel::STATUS_PUTAWAY ? ProductModel::STATUS_SOLDOUT : ProductModel::STATUS_PUTAWAY;
        $result = $this->product_model->where(['id' => $id])->save(['status' => $status]);
        if ($result === false) $this->error('error');
        else $this->success('success');
    }

    public function delete()
    {
        $id = intval(I('id'));
        if (empty($id)) $this->error('empty');
        $result = $this->product_model->where(['id' => $id])->save(['del' => 0]);
        if ($result === false) $this->error('error');
        else $this->success('success');
    }

    public function hotmanage()
    {
        $id = intval(I('id'));
        if (empty($id)) $this->error('empty');

        $status = $this->product_model->where(['id' => $id])->getField('hot');
        $status = $status == 1 ? 0 : 1;
        $result = $this->product_model->where(['id' => $id])->save(['hot' => $status, 'updata_time' => time()]);
        if ($result === false) $this->error('error');
        else $this->success('success');
    }

    public function todaymanage()
    {
        $id = intval(I('id'));
        if (empty($id)) $this->error('empty');

        $status = $this->product_model->where(['id' => $id])->getField('today_boutique');
        $status = $status == 1 ? 0 : 1;
        $result = $this->product_model->where(['id' => $id])->save(['today_boutique' => $status, 'updata_time' => time()]);
        if ($result === false) $this->error('error');
        else $this->success('success');
    }

    public function boutiquemanage()
    {
        $id = intval(I('id'));
        if (empty($id)) $this->error('empty');

        $status = $this->product_model->where(['id' => $id])->getField('boutique');
        $status = $status == 1 ? 0 : 1;
        $result = $this->product_model->where(['id' => $id])->save(['boutique' => $status]);
        if ($result === false) $this->error('error');
        else $this->success('success');
    }

//    public function topmanage()
//    {
//        $id = intval(I('id'));
//        if (empty($id)) $this->error('empty');
//
//        $status = $this->product_model->where(['id' => $id])->getField('top');
//        $status = $status == 1 ? 0 : 1;
//        $result = $this->product_model->where(['id' => $id])->save(['top' => $status]);
//        if ($result === false) $this->error('error');
//        else $this->success('success');
//    }


    private function _getCategoryTree($parentid = '', $disabled = false)
    {
        $result = $this->category_model->select();

        foreach ($result as $k => $v) {
            $result[$k]['selected'] = (!empty($parentid) && $parentid == $v['id']) ? 'selected' : '';
            if ($disabled) {
                $result[$k]['disabled'] = '';
                $count = $this->category_model->where(['parentid' => $v['id']])->count();
                if ($v['parentid'] == 0 && $count > 0)
                    $result[$k]['disabled'] = 'disabled="disabled"';
            }
        }
        $tree = new \Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);

        $str = "<option value='\$id' \$selected \$disabled>\$spacer\$name</option>";

        $taxonomys = $tree->get_tree(0, $str);

        return $taxonomys;
    }

    public function edit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('empty');


        $data_product = $this->product_model->find($id);
        if (!$data_product) $this->error('error');

        $brand_data = $this->brand_model->select();
        $category_data = $this->category_model->select();

        foreach ($brand_data as $k => $v) {
            $stat = '';
            if ($v['id'] == $data_product['brand_id']) $stat = 'selected="selected"';
            $brand_option .= '<option ' . $stat . ' value="' . $v['id'] . '">' . $v['name'] . '</option>';
        }
        unset($v);


        $result = $this->category_model->select();
        foreach ($result as $k => $v) {
            if ($v['id'] == $data_product['category_id'])
                $result[$k]['selected'] = 'selected="selected"';
            else
                $result[$k]['selected'] = '';

            if ($v['parentid'] == 0) {
                $result[$k]['disable'] = 'disabled="disabled"';

            } else {
                $result[$k]['disable'] = '';
            }
        }
        $tree = new \Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);
        $str = "<option value='\$id'\$disable \$selected>\$spacer\$name</option>";
        $taxonomys = $tree->get_tree(0, $str);
        $this->assign("categoryoption", $taxonomys);


//        foreach ($category_data as $k => $v) {
//            $stat = '';
//            if ($v['id'] == $data_product['category_id']) $stat = 'selected="selected"';
//            $category_option .= '<option  ' . $stat . '  value="' . $v['id'] . '">' . $v['name'] . '</option>';
//        }

        $smeta = json_decode($data_product['smeta'], true);
        $this->assign("smeta", $smeta);

        $this->assign('brandoption', $brand_option);
//        $this->assign('categoryoption', $category_option);
        $this->assign('data', $data_product);
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {
            $data = I('post.post');
            $data['content'] = htmlspecialchars_decode($data['content']);
            if (!empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['smeta']['photo'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }
            $data['smeta'] = json_encode(I('post.smeta'));
            if ($this->product_model->create($data)) {
                if ($this->product_model->save() === false) {
                    $this->error('error');
                } else {
                    $this->success('success', U('Goods/lists'));
                }
            } else {
                $this->error($this->product_model->getError());
            }
        }
    }


    public function editoption()
    {
        $product_id = intval(I('id'));
        if (empty($product_id)) $this->error('empty');
        $data_product = $this->product_model->find($product_id);
        if (!$data_product) $this->error('error');

        $parentid = $this->category_model->where(['id' => $data_product['category_id']])->getField('parentid');

        if ($parentid != 0) {
            $category_id = $parentid;
        } else {
            $category_id = $data_product['category_id'];
        }


        $attr_key_id_array = $this->category_attr_model
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as b on a.attr_key_id = b.attr_key_id')
            ->alias('a')
            ->where([
                'a.category_key_id' => $category_id,
            ])
            ->field('a.*,b.attr_name')
            ->select();

        $attrid = '';
        foreach ($attr_key_id_array as $value) {
//            $category_options .= '<option name="' . $value['attr_key_id'] . '">' . $value['attr_name'] . '</option>';
            $attrid .= $attrid ? ',' . $value['attr_key_id'] : $value['attr_key_id'];
        }
        if ($attrid) {
            $where = ['c.attr_key_id' => ['in', $attrid]];
        }

        unset($value);

        $option = $this->option_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr_option as b on a.option_key_id = b.option_key_id')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as c on b.attr_key_id = c.attr_key_id')
            ->field('a.*,c.attr_key_id')
            ->where($where)
            ->select();


        foreach ($attr_key_id_array as $k => $v) {
            foreach ($option as $key => $val) {
                if ($v['attr_key_id'] == $val['attr_key_id']) {
                    $attr_key_id_array[$k]['option'][] = $val;
                }
            }
        }
//        dump($attr_key_id_array);
        $this->assign('attr_option', $attr_key_id_array);

        ///修改这里
        unset($v);
        $product_sku_data = $this->product_sku_model->where(['product_key_id' => $product_id])->select();
//        dump($product_sku_data);
        $attr_option_path = '';
        foreach ($product_sku_data as $k => $v) {
            $attr_option_path .= $attr_option_path ? ',' . $v['attr_option_path'] : $v['attr_option_path'];
        }
        $attr_option_path_array = explode(',', $attr_option_path);
        $attr_option_path_array = array_unique($attr_option_path_array);
//        dump($attr_option_path_array);
        $this->assign('option_data', $attr_option_path_array);

        unset($v);
        $option_ids = '';
        foreach ($attr_option_path_array as $v) {
            $option_ids .= $option_ids ? ',' . $v : $v;
        }

        $result_attroption_leftjoin_attr = $this->attr_option_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr as b on a.attr_key_id = b.attr_key_id')
            ->where(['a.option_key_id' => ['in', $option_ids]])
            ->select();

        unset($v);
        foreach ($result_attroption_leftjoin_attr as $k => $v) {
            $option_attr_array[$v['attr_key_id']][$v['option_key_id']] = 1;
        }

        $ajaxdata = $this->Cartesianproduct($option_attr_array, $product_id);


        $this->assign('product_sku_data', $ajaxdata);
//        dump($result_attroption_leftjoin_attr);
//        $this->assign('category_options', $category_options);
        $this->assign('product_id', $product_id);
        $this->display();
    }

    public function editoption_post()
    {

    }

    public function add_option_post()
    {

    }


    public function selectoption()
    {
        $post = I('post.post');
        if (!$post) $this->error('empty');
        $ajaxdata = $this->Cartesianproduct($post);
        $this->ajaxReturn($ajaxdata);
    }


    private function onerowtables(array $post, $product_id)
    {
        if (!$post) return "";
//        $ajaxdata['tbody'][] = json_encode($post);
        $options = '';
        $option = '';
        foreach ($post as $k => $v) {
            $key = array_keys($v);
            $option_key_array[] = $key[0];
        }
        unset($v);
        asort($option_key_array);
        $option_key_array = array_merge($option_key_array);

        foreach ($option_key_array as $k => $v) {
            $options .= $options ? ',' . $v : $v;
            $option .= $v;
        }

        $product_sku_data = $this->product_sku_model->where(['product_key_id' => $product_id, 'attr_option_path' => $options])->find();

        $table_body = '<tbody>';
        $table_body .= '<tr>
                                <td>
                                <input type="hidden" value="' . $options . '" name="post[' . $option . '][attr_symbol_path]">
                                    <input class="input" type="number" name="post[' . $option . '][price]" value="' . $product_sku_data['price'] . '">
                                </td>
                                <td>
                                    <input class="input" type="number" name="post[' . $option . '][count]" value="' . $product_sku_data['quantity'] . '">
                                </td>
                            </tr>';
        $table_body .= '</tbody>';

        $ajaxdata['tbody'] = $table_body;

        $table_header = '<thead><tr>';
        $table_header .= '<th>价格</th>';
        $table_header .= '<th>数量</th>';
        $table_header .= '</tr></thead>';

        $ajaxdata['header'] = $table_header;


        return $ajaxdata;
    }

    /**
     * 笛卡尔积
     *
     * @param array $post
     */
    private function Cartesianproduct(array  $post, $product_id = '')
    {
        //层数大于1  至少有一层数量大于1
        //$check_ceng_count > 1 and  $check_ceng_max_count = true;  使用笛卡尔坐标  否则  使用其它 (当作BUG处理)


        $ceng_count = false;
        foreach ($post as $k => $v) {
            if (count($v) > 1) $ceng_count = true;
        }
        if (!(count($post) > 1 && $ceng_count)) {
            return $this->onerowtables($post, $product_id);
        }
        unset($v);
        ksort($post);
        $check_ceng_count = count($post);

        $post_data = [];
        if ($product_id) {
            $product_sku_data = $this->product_sku_model->where(['product_key_id' => $product_id, 'status' => 1])->select();
        }
        $options = '';
        $attrids = '';
        $check_ceng_max_count = false;
        foreach ($post as $k => $v) {
            $bijiao[] = [
                'attr_key_id' => $k,
                'count'       => count($v),
            ];
            if (count($v) > 1) {
                $check_ceng_max_count = true;
            }
            foreach ($v as $key => $val) {
//                $data[] = $k . '-' . $key;
                $options .= $options ? ',' . $key : $key;
//                $post_data[$k][] = $key;
            }

            $attrids .= $attrids ? ',' . $k : $k;
        }

        unset($v);
        unset($val);

        $attr_data = $this->attr_model->where(['attr_key_id' => ['in', $attrids]])->select();
        $option_data = $this->option_model->where(['option_key_id' => ['in', $options]])->select();
        $attr_list = $this->arraySortByKey($bijiao, 'count');

        foreach ($post as $k => $v) {
            foreach ($v as $key => $val) {
                foreach ($option_data as $value) {
                    if ($value['option_key_id'] == $key) {
                        $post_data[$k][] = $value;
                    }
                }
            }
        }

        foreach ($attr_list as $k => $v) {
            foreach ($attr_data as $value) {
                if ($v['attr_key_id'] == $value['attr_key_id']) {
                    $attr_list[$k]['attr_name'] = $value['attr_name'];
                }
            }
//            unset($attr_list[$k]['count']);
        }


        unset($v);
        unset($value);


        ////table thead 标题部分
        $table_header = '<thead><tr>';
        foreach ($attr_list as $k => $v) {
            $table_header .= '<th>' . $v['attr_name'] . '</th>';
        }
        $table_header .= '<th>价格</th>';
        $table_header .= '<th>数量</th>';

        $table_header .= '</tr></thead>';

        unset($v);

//        if ($attr_list[(count($attr_list) - 1)]['attr_key_id'] == 'count') unset($attr_list[(count($attr_list) - 1)]);
//        if ($attr_list[(count($attr_list) - 2)]['attr_key_id'] == 'price') unset($attr_list[(count($attr_list) - 2)]);

        ////table  tbody 正文

        //每层数据最大值 == 最外层rowspan的值
        $cen_max_count = 1;
        $attr_list_cp = [];

        foreach ($attr_list as $k => $v) {
            $attr_tr[] = [
                'attr_key_id' => $v['attr_key_id'],
                'attr_name'   => $v['attr_name'],
                'child'       => array_keys($post[$v['attr_key_id']]),
            ];
            $descartes[] = array_keys($post[$v['attr_key_id']]);
            if ($k > 0)
                $cen_max_count *= $v['count'];
        }
//        if($descartes)
        $array_descartes = Descartes($descartes);
        unset($v);

        foreach ($array_descartes as $k => $v) {
            if (is_array($v)) {
                $array_descartes[$k]['attr_symbol_path_array'] = $v;
                sort($v);
                foreach ($v as $key => $value) {
                    $array_descartes[$k]['attr_symbol_path'] .= $array_descartes[$k]['attr_symbol_path'] ? ',' . $value : $value;
                    foreach ($option_data as $val) {
                        if ($val['option_key_id'] == $value) {
                            $array_descartes[$k]['attr_symbol_path_name'] .= $val['option_name'];
                        }
                    }

                    $array_descartes[$k]['symbol'] .= $value;
                    unset($array_descartes[$k][$key]);
                }
            }
        }

        unset($v);
        unset($value);

        $table_body = '<tbody>';
        foreach ($array_descartes as $k => $v) {
            $table_body .= '<tr>';
            if (count($attr_list) > 1) {
                foreach ($attr_list as $key => $value) {
                    $n = 1; //rowspan行数
                    //这里需要一个**函数
                    $attr_list_cp = $attr_list;
                    unset($attr_list_cp[0]);
                    $attr_list_cp = array_merge($attr_list_cp);
                    $n = $this->array_shift_fun($key, $n, $attr_list_cp);

                    // k / n     根据倍数读取相应的值？
                    // n / 层数   外层->内层
                    $vv_count = count($post_data[$value['attr_key_id']]);
                    $sign = intval($k / $n);
                    $sign = $sign % $vv_count;
                    $name = $post_data[$value['attr_key_id']][$sign]['option_name'];

                    if ($k == 0 || (($k + 1) % $n == 1)) {
                        if ($n > 1) {
                            $table_body .= '<td rowspan="' . $n . '">' . $name . '</td>';
                        }
                    }
                }
            }

            $bb = $post_data[$attr_list[count($attr_list) - 1]['attr_key_id']];
            $bb_count = count($bb);
            $name_neiceng = $bb[$k % $bb_count]['option_name'];

            //'.$v['attr_symbol_path_name'].'
            if ($product_sku_data) {
                $price = '';
                $count = '';
                foreach ($product_sku_data as $key2 => $value2) {
                    if ($v['attr_symbol_path'] == $value2['attr_option_path']) {
                        $price = $value2['price'];
                        $count = $value2['quantity'];
                    }
                }
            }

            $table_body .= '<td>' . $name_neiceng . '</td>
                                <td>
                                <input type="hidden" value="' . $v['attr_symbol_path'] . '" name="post[' . $v['symbol'] . '][attr_symbol_path]">
                                    <input class="input" type="number" name="post[' . $v['symbol'] . '][price]" value="' . $price . '">
                                </td>
                                <td>
                                    <input class="input" type="number" name="post[' . $v['symbol'] . '][count]" value="' . $count . '">
                                </td>
                            </tr>';
        }
        $table_body .= '</tbody>';

        $ajaxdata['tbody'] = $table_body;
        $ajaxdata['header'] = $table_header;

        return $ajaxdata;
    }


    private function array_shift_fun($k, $n, array  $attr_list)
    {
        if (($k + 1) > count($attr_list)) {
            return $n;
        }
        $n = $n * $attr_list[$k]['count'];
        $k++;
        return $this->array_shift_fun($k, $n, $attr_list);

    }


    public function arraySortByKey(array $array, $key, $asc = true)
    {
        $result = [];
        // 整理出准备排序的数组
        foreach ($array as $k => &$v) {
            $values[$k] = isset($v[$key]) ? $v[$key] : '';
        }
        unset($v);
        // 对需要排序键值进行排序
        $asc ? asort($values) : arsort($values);
        // 重新排列原有数组
        foreach ($values as $k => $v) {
            $result[] = $array[$k];
        }

        return $result;
    }

    public function updataoption()
    {
        $post = I('post.post');
        $product_key_id = I('post.product_id');
        $iscommit = true;
        //插入前判断(如果product_id相同,但是attr_option_path组合不同? 库存和价格有意义吗？)
        //不考虑锁表,不考虑操作冲突
        //整删，整存

        if ($this->product_sku_model->where(['product_key_id' => $product_key_id])->save(['status' => 0]) === false) {
            $this->ajaxReturnRequest('异常', '1111');
        }

        $this->product_sku_model->startTrans();
        //插入数据
        foreach ($post as $k => $v) {
            $data = [
                'attr_option_path' => $v['attr_symbol_path'],
                'product_key_id'   => $product_key_id,
                'price'            => $v['price'],
                'freight'          => 0,
                'quantity'         => $v['count'],
                'status'           => 1,
            ];
            if (!$this->product_sku_model->add($data))
                $iscommit = false;
        }
        if ($iscommit) {
            $this->product_sku_model->commit();
            $this->ajaxReturnRequest('成功', '0000');
        } else {
            $this->product_sku_model->rollback();
            $this->ajaxReturnRequest('失败', '1111');
        }

    }
}
