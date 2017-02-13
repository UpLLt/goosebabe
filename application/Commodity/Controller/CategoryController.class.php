<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 15:38
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\CategoryModel;

class CategoryController extends AdminbaseController
{
    private $category_model;

    public function __construct()
    {
        parent::__construct();
        $this->category_model = new CategoryModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $result = $this->category_model->order('listorder asc')->select();
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] =  $v['parentid'] == 0 ? '<a href="' . U("Category/add", array("parent" => $v['id'])) . '">添加子类</a> |' : '';
            $result[$k]['str_manage'] .= ' <a href="' . U("Category/edit", array("id" => $v['id'])) . '">编辑</a> ';
            $result[$k]['str_manage'] .= '| <a href="' . U("Category/delete", array("id" => $v['id'])) . '">删除</a> ';
            $result[$k]['images'] = $v['images'] ? $result[$k]['images'] = "<a href='" . sp_get_asset_upload_path($v['images']) . "' target='_blank'>查看</a>" : "";
        }

        $tree->init($result);
        $str = "<tr>
					<td><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input input-order'></td>
					<td>\$id</td>
					<td>\$spacer\$name</td>
					<td>\$images</td>
					<td>\$str_manage</td>
				</tr>";

        $taxonomys = $tree->get_tree(0, $str);
        $this->assign('categorys', $taxonomys);
    }

    public function add()
    {
        $this->_getCategoryTree();
        $this->display();
    }

    private function _getCategoryTree()
    {
        $parentid = intval(I('get.parent'));
        $result = $this->category_model->select();
        foreach ($result as $k => $v) {
            $result[$k]['selected'] = $v['id'] == (!empty($parentid) && $v['id'] == $parentid) ? 'selected' : '';
        }
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);
        $str = "<option value='\$id' \$selected>\$spacer\$name</option>";
        $taxonomys = $tree->get_tree(0, $str);
        $this->assign("category_tree", $taxonomys);
    }


    public function add_post()
    {
        if (IS_POST) {
            if ($this->category_model->create()) {
                if ($this->category_model->add()) {
                    $this->success("添加成功！", U("Category/lists"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->category_model->getError());
            }
        }
    }

    public function listorders()
    {
        $status = parent::_listorders($this->category_model);
        if ($status) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }

    public function edit()
    {
        $id = intval(I("get.id"));
        $data = $this->category_model->find($id);

        $madify_id = $this->category_model->where(array('id' => $id))->field('parentid,id')->find();

        if( $madify_id['parentid'] == 0 ){
            $taxonomys = "<option value=' 0 '>一级父类</option>";
        }else{
            $id =  $madify_id['parentid'];
            $category_opition = $this->category_model->where( array('parentid' => 0 ))->select();
            foreach( $category_opition as $k => $v ){
                $selected = $id == $v['id'] ? 'selected' : '';
                $taxonomys .= "<option /$selected  value='" . $v['id'] . "'>" . $v['name'] . "</option>";
            }
        }





        $this->assign('data', $data);
        $this->assign('category_tree', $taxonomys);
        $this->display();
    }

    public function delete()
    {
        $id = intval(I("get.id"));
        $count = $this->category_model->where(array("parentid" => $id))->count();

        if ($count > 0) {
            $this->error("该菜单下还有子类，无法删除！");
        }

        if ($this->category_model->delete($id) !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    public function edit_post()
    {
        if (IS_POST) {
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['smeta']['photo'][] = array("url" => $photourl, "alt" => $_POST['photos_alt'][$key]);
                }
            }
            $data = I('post.');
            $data['smeta'] = json_encode($data['smeta']);

            if ($this->category_model->create($data)) {
                if ($this->category_model->save() !== false) {
                    $this->success("修改成功！");
                } else {
                    $this->error("修改失败！", U('Category/lists'));
                }
            } else {
                $this->error($this->category_model->getError());
            }
        }
    }
}