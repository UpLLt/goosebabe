<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/21
 * Time: 15:32
 */

namespace App\Controller;


use App\Model\DocClassModel;
use App\Model\DocumentsModel;
use Common\Controller\AdminbaseController;

/**
 * 关于我们等文案的配置
 * Class AboutController
 * @package App\Controller
 */
class AboutController extends AdminbaseController
{
    protected $document_model, $doc_class_model;

    public function __construct()
    {
        parent::__construct();

        $this->doc_class_model = new DocClassModel();
        $this->document_model = new DocumentsModel();
    }

    public function index()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['name'] = $keyword;
            $_GET['keyword'] = $keyword;
        }

        $count = $this->document_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->document_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="" href="' . U('About/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('About/delete', array('id' => $v['id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['name'] . '</td>
            <td>' . $result[$k]['doc_class'] . '</td>
            <td>' . $result[$k]['desc'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['update_time']) . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
        $this->display();
    }

    public function add()
    {
        $this->docclasslist();
        $this->display();
    }

    private function docclasslist()
    {
        $result = $this->doc_class_model->select();
        foreach ($result as $v) {
            $doclist .= '<tr>
                            <td width="50%">' . $v['key_name'] . '</td>
                            <td>' . $v['key_desc'] . '</td>
                        </tr>';
        }
        $this->assign('doclist', $doclist);
    }

    public function doclist()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['name'] = $keyword;
            $_GET['keyword'] = $keyword;
        }

        $count = $this->doc_class_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->doc_class_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = '<a class="" href="' . U('About/docedit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('About/doc_delete', array('id' => $v['id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
             <td>' . $result[$k]['key_desc'] . '</td>
            <td>' . $result[$k]['key_name'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['update_time']) . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
        $this->display();
    }

    public function add_post()
    {
        $data = I('post.');
        $data['content'] = htmlspecialchars_decode($data['content']);
        if (!$this->document_model->create($data)) {
            $this->error($this->document_model->getError());
        }
        if (!$this->document_model->add())
            $this->error('失败');
        $this->success('成功');
    }

    public function edit()
    {
        $id = I('id');
        if (empty($id)) $this->error('错误');
        $result = $this->document_model->find($id);
        $this->assign('data', $result);
        $this->docclasslist();
        $this->display();
    }

    public function edit_post()
    {
        $data = I('post.');
        $data['content'] = htmlspecialchars_decode($data['content']);
        if (!$this->document_model->create($data)) {
            $this->error($this->document_model->getError());
        }
        if (!$this->document_model->save())
            $this->error('失败');
        $this->success('成功');
    }

    public function delete()
    {
        $id = I('id');
        if (empty($id)) $this->error('参数错误');
        $result = $this->document_model->delete($id);
        if (!$result) $this->error('操作失败');
        $this->success('操作成功');
    }


    public function docadd()
    {
        $this->display();
    }

    public function docadd_post()
    {
        if (!$this->doc_class_model->create()) {
            $this->error($this->doc_class_model->getError());
        }
        if (!$this->doc_class_model->add())
            $this->error('失败');

        $this->success('成功');
    }


    public function docedit()
    {
        $id = I('id');
        if (empty($id)) $this->error('错误');
        $result = $this->doc_class_model->find($id);
        $this->assign('data', $result);
        $this->display();
    }

    public function docedit_post()
    {
        if (!$this->doc_class_model->create($data)) {
            $this->error($this->doc_class_model->getError());
        }
        if (!$this->doc_class_model->save())
            $this->error('失败');
        $this->success('成功');
    }

}