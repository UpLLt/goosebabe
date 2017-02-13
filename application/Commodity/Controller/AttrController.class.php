<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/10/13
 * Time: 17:23
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\AttrModel;
use Common\Model\CategoryAttrModel;
use Common\Model\CategoryModel;

class AttrController extends AdminbaseController
{

    private $attr_model;
    private $category_attr_model;
    private $category_model;

    public function __construct()
    {
        parent::__construct();
        $this->attr_model = new AttrModel();
        $this->category_model = new CategoryModel();

        $this->category_attr_model = new CategoryAttrModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category_attr as b on a.attr_key_id = b.attr_key_id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as c on b.category_key_id = c.id';
        $count = $this->attr_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->attr_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,c.name as category_name')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= '<a class="" href="' . U('Attr/edit', array('id' => $v['attr_key_id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Attr/delete', array('id' => $v['attr_key_id'])) . '">删除</a>';

            $tablebody .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $v['attr_name'] . '</td>
            <td>' . $v['category_name'] . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }

    public function add()
    {
        $result = $this->category_model->select();
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);
        $str = "<option value='\$id'>\$spacer\$name</option>";
        $taxonomys = $tree->get_tree(0, $str);
        $this->assign("category_tree", $taxonomys);
        $this->display();
    }

    public function add_post()
    {
        if (IS_POST) {
            $attr_data = array(
                'attr_name' => I('post.attr_name')
            );

            if (!$this->attr_model->create($attr_data))
                $this->error($this->attr_model->getError());

            $iscommit = true;
            $this->attr_model->startTrans();

            if (!$this->attr_model->add($attr_data))
                $iscommit = false;

            $category_attr_data = array(
                'category_key_id' => I('post.category_key_id'),
                'attr_key_id' => $this->category_attr_model->getLastInsID()
            );
            if (!$this->category_attr_model->add($category_attr_data))
                $iscommit = false;

            if ($iscommit) {
                $this->attr_model->commit();
                $this->success('success');
            } else {
                $this->attr_model->rollback();
                $this->error('error');
            }
        }
    }

    public function delete()
    {
        $attr_key_id = intval(I('get.id'));
        if (empty($attr_key_id)) $this->error('empty');

        $where = array(
            'attr_key_id' => $attr_key_id
        );

        $iscommit = true;
        $this->attr_model->startTrans();
        if (!$this->attr_model->where($where)->delete()) $iscommit = false;
        if (!$this->category_attr_model->where($where)->delete()) $iscommit = false;

        if ($iscommit) {
            $this->attr_model->commit();
            $this->success('success');
        } else {
            $this->attr_model->rollback();
            $this->error('error');
        }
    }


    public function edit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $attr_result = $this->attr_model
                        ->alias('a')
                        ->join('LEFT JOIN ' . C('DB_PREFIX') . 'category_attr as b on a.attr_key_id = b.attr_key_id')
                        ->where(array('a.attr_key_id' => $id))
                        ->find();

        $result = $this->category_model->select();
        foreach ($result as $k => $v){
            $result[$k]['selected'] = '';
            if($v['id'] == $attr_result['category_key_id'])  $result[$k]['selected'] = 'selected="selected"';
        }
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);
        $str = "<option \$selected value='\$id'>\$spacer\$name</option>";
        $taxonomys = $tree->get_tree(0, $str);
        $this->assign("category_tree", $taxonomys);

        $this->assign('data',$attr_result);
        $this->display();
    }

    public function edit_post()
    {

        if (IS_POST) {

           $attr = $this->attr_model->where(array('attr_key_id'=>I('attr_key_id')))->save(array('attr_name'=>I('attr_name')));
           $cate_attr = $this->category_attr_model->where(array('attr_key_id'=>I('attr_key_id')))->save(array('category_key_id'=>I('category_key_id')));

           if( $attr != false || $cate_attr != false ) {
               $this->success('修改成功');
           }else{
               $this->error('修改失败');
           }

        }
    }
}