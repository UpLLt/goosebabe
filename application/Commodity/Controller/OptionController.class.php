<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/10/14
 * Time: 9:50
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\AttrModel;
use Common\Model\AttrOptionModel;
use Common\Model\CategoryModel;
use Common\Model\OptionModel;

class OptionController extends AdminbaseController
{

    private $category_model;
    private $attr_model;
    private $attr_option_model;
    private $option_model;

    public function __construct()
    {
        parent::__construct();
        $this->option_model = new OptionModel();
//        $this->category_model = new CategoryModel();
        $this->attr_model = new AttrModel();
        $this->attr_option_model = new AttrOptionModel();
    }


    public function lists()
    {
        $this->_lists();
        $this->display();
    }


    private function _lists()
    {
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'attr_option as b on a.option_key_id = b.option_key_id';
        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'attr as c on b.attr_key_id = c.attr_key_id';
        $count = $this->option_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->option_model
            ->alias('a')
            ->join($join)
            ->join($join2)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,c.attr_name')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= '<a class="" href="' . U('Option/edit', array('id' => $v['option_key_id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Option/delete', array('id' => $v['option_key_id'])) . '">删除</a>';

            $tablebody .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $v['option_name'] . '</td>
            <td>' . $v['attr_name'] . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }


    public function add()
    {
        $result = $this->attr_model->select();
        foreach ($result as $k => $v) {
            $option .= '<option value="' . $v['attr_key_id'] . '">' . $v['attr_name'] . '</option>';
        }
        $this->assign('options', $option);
        $this->display();
    }


    public function add_post()
    {
        if (IS_POST) {
            $post = I('post.');
            if (empty($post['option_name'])) $this->error('empty');
            if (empty($post['attr_key_id'])) $this->error('empty');

            $data_option = array(
                'option_name' => $post['option_name']
            );
            $iscommit = true;
            $this->option_model->startTrans();
            if (!$this->option_model->add($data_option)) {
                $iscommit = false;
            };
            if ($this->option_model->getLastInsID()) {
                $data_attr_option = array(
                    'attr_key_id' => $post['attr_key_id'],
                    'option_key_id' => $this->option_model->getLastInsID(),
                );
                if (!$this->attr_option_model->add($data_attr_option)) {
                    $iscommit = false;
                };
            }

            if ($iscommit) {
                $this->option_model->commit();
                $this->success('success');
            } else {
                $this->option_model->rollback();
                $this->error('error');
            }
        }
    }

    public function edit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');
        $option_data = $this->option_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'attr_option as b on a.option_key_id = b.option_key_id')
            ->where(array('a.option_key_id' => $id))
            ->find();

        $result = $this->attr_model->select();

        $option = '';
        foreach ($result as $k => $v) {
            $selected = '';
            if($option_data['attr_key_id'] == $v['attr_key_id']) $selected = 'selected="selected"';
            $option .= '<option '.$selected.' value="' . $v['attr_key_id'] . '">' . $v['attr_name'] . '</option>';
        }
        $this->assign('options', $option);
        $this->assign('data', $option_data);
        $this->display();
    }


    public function edit_post()
    {
        if (IS_POST) {
            $post = I('post.');
            if (empty($post['option_name'])) $this->error('empty');
            if (empty($post['attr_key_id'])) $this->error('empty');
            if (empty($post['option_key_id'])) $this->error('empty');


            $iscommit = true;
            $this->option_model->startTrans();

            if ($this->option_model->where(array('option_key_id' => $post['option_key_id']))->save(array('option_name' => $post['option_name'])) === false) {
                $iscommit = false;
            };

            if ($this->attr_option_model->where(array('option_key_id' => $post['option_key_id']))->save(array('attr_key_id' => $post['attr_key_id'])) === false) {
                $iscommit = false;
            };

            if ($iscommit) {
                $this->option_model->commit();
                $this->success('success');
            } else {
                $this->option_model->rollback();
                $this->error('error');
            }
        }
    }

    public function delete()
    {
        $option_key_id = intval(I('get.id'));

        if (empty($option_key_id)) $this->error('error');

        $result  = $this->option_model->delete($option_key_id);

        if( $result ){
            $this->success('success');
        }else{
            $this->error('error');
        }
    }
}
