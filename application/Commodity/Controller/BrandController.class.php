<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 17:22
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\BrandModel;

class BrandController extends AdminbaseController
{
    private $brand_model;

    public function __construct()
    {
        parent::__construct();
        $this->brand_model = new BrandModel();
    }


    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    public function add()
    {
        $this->display();
    }

    public function add_post()
    {
        if (IS_POST) {
            if ($this->brand_model->create()) {
                if ($this->brand_model->add()) {
                    $this->success("添加成功！", U("Brand/lists"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->brand_model->getError());
            }
        }
    }

    public function _lists()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['name'] = array('like', "%$keyword%");
            $_GET['keyword'] = $keyword;
        }
        $count = $this->brand_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->brand_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] .= '<a class="jjs-ajax-dialog-btn" href="' . U('Brand/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete"  href="' . U('Brand/delete', array('id' => $v['id'])) . '">删除</a>';


            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['name'] . '</td>
            <td><a href="' . sp_get_asset_upload_path($result[$k]['image']) . '" target=\'_blank\'>     <img style="width: 40px;height: 30px" src="' . $result[$k]['image'] . '"></a></td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function delete()
    {
        $id = intval(I('id'));
        if (empty($id)) $this->error('empty');
        if ($this->brand_model->delete($id)) {
            $this->success('success');
        } else {
            $this->error('error');
        }
    }

    public function edit()
    {
        $list = $this->brand_model->where(array('id' => I('id')))->find();
        if ($list['exhibition'] == 1) {
            $stat = 'selected="selected"';
        } else {
            $stat = '';
        }


        $exhibition = " <option value='0'>不展示</option>
                        <option  " . $stat . "  value='1'>展示</option>";

        $this->assign('list', $list);
        $this->assign('exhibition', $exhibition);
        $this->display('edit');

    }


    public function edit_post()
    {
        if (IS_POST) {
            if ($this->brand_model->create()) {
                if ($this->brand_model->save() == false) {
                    $this->error("修改失败！");
                } else {
                    $this->error("修改成功！", U("Brand/lists"));
                }
            } else {
                $this->error($this->brand_model->getError());
            }
        }
    }

}