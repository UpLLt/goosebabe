<?php
namespace Advertisement\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\BannerImageModel;
use Common\Model\BannerModel;
use Think\Controller;

class IndexController extends AdminbaseController
{
    private $banner_model;
    private $banner_image_model;


    public function __construct()
    {
        parent::__construct();
        $this->banner_model = new BannerModel();
        $this->banner_image_model = new BannerImageModel();
    }

    public function billboard()
    {
        $this->_lists();
        $this->display();
    }

    public function _lists()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['name'] = array('like', "%$keyword%");
            $_GET['keyword'] = $keyword;
        }
        $count = $this->banner_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->banner_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {

            $result[$k]['str_manage'] = '<a href="' . U('Index/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Index/delete', array('id' => $v['id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . ($v['type'] == 1 ? 'PC' : 'APP') . '</td>
            <td>' . $result[$k]['name'] . '</td>
            <td>' . ($v['status'] == 1 ? '启用' : '禁用') . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function add()
    {
        $this->display();
    }

    public function add_post()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/banner/'; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        // 上传文件
        $info = $upload->upload();
        if (!$info) {
            // 上传错误提示错误信息
            $this->error($upload->getError());
        } else {// 上传成功 获取上传文件信息
            foreach ($info as $file) {
                $url = '/data/upload/banner/' . $file['savepath'] . $file['savename'];
                $upload_iamges[] = array('image' => $url);
            }
        }
        $data = I('post.post');
        $banner_image_array = array();
        if (!empty($_POST['title'])) {
            foreach ($_POST['title'] as $key => $val) {
                $image = array(
                    'title' => $val,
                    'image' => $upload_iamges[$key]['image'],
                    'link' => $_POST['link'][$key],
                    'sort_order' => $_POST['sort_order'][$key]
                );
                $banner_image_array[] = $image;
            }
        }

        $iscommit = true;
        $this->banner_model->startTrans();
        if (!$this->banner_model->create($data)) $this->error($this->banner_model->getError());
        $result = $this->banner_model->add($data);
        if (!$result) $iscommit = false;

        $InsID = $this->banner_model->getLastInsID();
        foreach ($banner_image_array as $k => $v) {
            $banner_image_array[$k]['banner_id'] = $InsID;
            $b = $this->banner_image_model->add($banner_image_array[$k]);
            if (!$b) $iscommit = false;
            unset($b);
        }

        if ($iscommit) {
            $this->banner_model->commit();
            $this->success('success');
        } else {
            $this->banner_model->rollback();
            $this->error('error');
        }
    }

    public function delete()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $this->banner_model->delete($id);
        $this->banner_image_model->where(array('banner_id' => $id))->delete();

        $this->success('success');
    }

    public function edit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');

        $result = $this->banner_model
            ->find($id);
        $banner_image = $this->banner_image_model->where(array('banner_id' => $id))->select();

        foreach ($banner_image as $k => $v) {
            $categorys .= '<tr>
                        <td><input required name="title[]" value="' . $v['title'] . '"></td>
                        <td><input name="link[]" value="' . $v['link'] . '"></td>
                        <td><input name="images[]" type="hidden" value="' . $v['image'] . '"><input style="display:none;" type="file" name="image[]" value="' . $v['image'] . '"><img width="100px" src="' . $v['image'] . '"/></td>
                        <td><input name="sort_order[]" value="' . $v['sort_order'] . '"></td>
                        <td><a class="btn btn-danger btn-small"  href="javascript:;" onclick="deletetr(this)"><span>删除</span></a></td>
                        </tr>';
        }
        $this->assign('data', $result);
        $this->assign('categorys', $categorys);
        $this->display();
    }

    public function edit_post()
    {
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3145728;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './data/upload/banner/'; // 设置附件上传根目录
        $upload->savePath = ''; // 设置附件上传（子）目录
        // 上传文件
        $info = $upload->upload();
        if ($info) {
            foreach ($info as $file) {
                $url = '/data/upload/banner/' . $file['savepath'] . $file['savename'];
                $upload_iamges[] = array('image' => $url);
            }
        }
        $data = I('post.post');
        $banner_image_array = array();
        if (!empty($_POST['title'])) {
            foreach ($_POST['title'] as $key => $val) {
                $i = 0;
                $image = array(
                    'title' => $val,
                    'image' => $_POST['images'][$key] ? $_POST['images'][$key] : $upload_iamges[$i]['image'],
                    'link' => $_POST['link'][$key],
                    'sort_order' => $_POST['sort_order'][$key]
                );
                if (!$_POST['images'][$key]) $i++;
                $banner_image_array[] = $image;
            }
        }

        $iscommit = true;
        $this->banner_model->startTrans();
        $banner_id = I('post.id');
        if ($this->banner_model->where(array('id' => $banner_id))->save($data) === false) $iscommit = false;
        if (!$this->banner_image_model->where(array('banner_id' => $banner_id))->delete()) $iscommit = false;


        foreach ($banner_image_array as $k => $v) {
            $banner_image_array[$k]['banner_id'] = $banner_id;
            $b = $this->banner_image_model->add($banner_image_array[$k]);
            if (!$b) $iscommit = false;
            unset($b);
        }

        if ($iscommit) {
            $this->banner_model->commit();
            $this->success('success');
        } else {
            $this->banner_model->rollback();
            $this->error('error');
        }
    }
}