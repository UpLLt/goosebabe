<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/27
 * Time: 11:12
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\AttrModel;
use Common\Model\AttrOptionModel;
use Common\Model\CategoryAttrModel;
use Common\Model\OptionDescriptionModel;
use Common\Model\OptionModel;
use Common\Model\OptionsModel;
use Common\Model\OptionValueDescriptionModel;
use Common\Model\OptionValueModel;
use Common\Model\ProductOptionModel;
use Common\Model\ProductOptionValueModel;

class ProductoptionController extends AdminbaseController
{

    private $production_option_model;
    private $production_option_value_model;
    private $option_value_model;
//    private $option_value_description_modle;
    private $option_model;

//    private $option_description_model;

    private $attr_model;
    private $attr_option_model;
    private $category_attr_model;

    private $option_array;

    public function __construct()
    {
        parent::__construct();
        $this->production_option_model = new ProductOptionModel();
        $this->production_option_value_model = new ProductOptionValueModel();

        $this->option_model = new OptionModel();
//        $this->option_value_model = new OptionValueModel();
//        $this->option_value_description_modle = new OptionValueDescriptionModel();
//        $this->option_description_model = new OptionDescriptionModel();

        $this->attr_model = new AttrModel();
        $this->attr_option_model = new AttrOptionModel();
        $this->category_attr_model = new CategoryAttrModel();

        $this->option_array = array(
            array(
                'type' => 'radion',
                'value' => '单选'
            ),
            array(
                'type' => 'checkbox',
                'value' => '多选'
            ),
            array(
                'type' => 'select',
                'value' => '下拉'
            ),
        );
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    public function array_group_by(array $arr, callable $key_selector)
    {
        $result = array();
        foreach ($arr as $i) {
            $key = $i[$key_selector];
            $result[$key][] = $i;
        }
        return $result;
    }


    private function _lists()
    {
        $result = $this->attr_model
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= '<a href="' . U('Productoption/edit', array('id' => $v['option_key_id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Productoption/delete', array('id' => $v['option_key_id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['option_name'] . '</td>
            <td>' . $result[$k]['sort_order'] . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
    }

    public function edit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $data = $this->option_model
            ->where(array('option_key_id' => $id))
            ->find();

        foreach ($this->option_array as $k => $v) {
            if ($v['type'] == $data['type'])
                $option_list .= '<option selected="selected" value="radio">' . $v['value'] . '</option > ';
            else
                $option_list .= '<option value = "radio" >' . $v['value'] . '</option > ';
        }

//        $data_option_value = $this->option_value_model
//            ->where(array('option_key_id' => $id))
//            ->order('sort_order asc')
//            ->select();
//
//
//        foreach ($data_option_value as $k => $v) {
//            $tablebody .= '<tr>
//                        <td><input type="hidden" name="option_value[' . $k . '][option_value_id]" value="' . $v['option_value_id'] . '"><input required name="option_value[' . $k . '][name]" value="' . $v['name'] . '"></td>
//                        <td><input required name="option_value[' . $k . '][sort_order]" value="' . $v['sort_order'] . '"></td>
//                        <td><a class="btn btn-danger btn-small" href="javascript:;" onclick="deletetr(this)"><span>删除</span></a></a></td>
//                        </tr>';
//            $option_value_row = $k + 1;
//        }
//
//        $option_value_row = $option_value_row ? $option_value_row : '0';
//        $this->assign('option_value_row', $option_value_row);
        $this->assign('tablebody', $tablebody);
        $this->assign('option_list', $option_list);
        $this->assign('data', $data);
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {
            $option = I('post.option');
            $option_key_id = $option['option_key_id'];

            $iscommit = true;
            $this->option_model->startTrans();

            $save_option = $this->option_model
                ->where(array(
                    'option_key_id' => $option_key_id
                ))
                ->save(array(
                    'name' => $option['name'],
                    'type' => $option['type'],
                    'sort_order' => $option['sort_order'],
                ));

            //对比移除
            $update_before_option_value_ids = $this->option_value_model
                ->where(array('option_key_id' => $option_key_id))
                ->field('option_value_id')
                ->select();

            foreach ($update_before_option_value_ids as $k => $v) {
                $update_before_option_value_id[] = $v['option_value_id'];
            }
            unset($v);
            $update_option_value_id = array();

            if ($save_option === false) $iscommit = false;

            if (isset($_POST['option_value'])) {
                $option_value = I('post.option_value');
                foreach ($option_value as $k => $v) {
                    if (isset($v['option_value_id']) && $v['option_value_id']) {
                        $where_option_value = array('option_value_id' => $v['option_value_id']);
                        $update_option_value_id[] = $v['option_value_id'];
                        $save_option_value = array(
                            'name' => $v['name'],
                            'sort_order' => $v['sort_order'],
                            'option_key_id' => $option_key_id
                        );
                        if (!$this->option_value_model->create($save_option_value)) {
                            $iscommit = false;
                            continue;
                        }
                        $result_option_value = $this->option_value_model->where($where_option_value)->save($save_option_value);
                        if ($result_option_value === false) $iscommit = false;
                    } else {
                        $add_option_value = array(
                            'name' => $v['name'],
                            'sort_order' => $v['sort_order'],
                            'option_key_id' => $option_key_id
                        );
                        if (!$this->option_value_model->create($add_option_value)) {
                            $iscommit = false;
                            continue;
                        }
                        $result_option_value = $this->option_value_model->add($add_option_value);
                        if ($result_option_value === false) $iscommit = false;
                    }
                }
            }

            unset($v);
            $diffs = array_diff($update_before_option_value_id, $update_option_value_id);
            foreach ($diffs as $k => $v) {
                $delete_option_value = $this->option_value_model->where(array('option_value_id' => $v))->delete();
                if ($delete_option_value === false) $iscommit = false;

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

    public function delete()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $iscommit = true;
        $this->option_value_model->startTrans();
        $delete_option = $this->option_model->where(array('option_key_id' => $id))->delete();
        if ($delete_option === false) $iscommit = false;
        $delete_option_value = $this->option_value_model->where(array('option_key_id' => $id))->delete();
        if ($delete_option_value === false) $iscommit = false;

        if ($iscommit) {
            $this->option_value_model->commit();
            $this->success('success');
        } else {
            $this->option_value_model->rollback();
            $this->error('error');
        }


    }

    public function add()
    {
        foreach ($this->option_array as $k => $v) {
            $option_list .= '<option value = "radio" >' . $v['value'] . '</option > ';
        }
        $this->assign('option_list', $option_list);
        $this->display();
    }

    public function add_post()
    {
        if (IS_POST) {
            $option = I('post.option');
            $option_key_id = $option['id'];

            $iscommit = true;
            $this->option_model->startTrans();

            $add_option = $this->option_model
                ->add(array(
                    'name' => $option['name'],
                    'type' => $option['type'],
                    'sort_order' => $option['sort_order'],
                ));

            if (!$add_option) $iscommit = false;
            $add_option_key_id = $this->option_model->getLastInsID();


            if (isset($_POST['option_value'])) {
                $option_value = I('post.option_value');
                foreach ($option_value as $k => $v) {
                    $add_option_value = array(
                        'name' => $v['name'],
                        'sort_order' => $v['sort_order'],
                        'option_key_id' => $add_option_key_id
                    );
                    if (!$this->option_value_model->create($add_option_value)) {
                        $iscommit = false;
                        continue;
                    }
                    $result_option_value = $this->option_value_model->add($add_option_value);
                    if ($result_option_value === false) $iscommit = false;
                }
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

}