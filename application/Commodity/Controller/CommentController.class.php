<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/19
 * Time: 15:55
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\CommentModel;

class CommentController extends AdminbaseController
{

    private $comment_model;

    public function __construct()
    {
        parent::__construct();
        $this->comment_model = new CommentModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }


    private function _lists()
    {
        $count = $this->comment_model->count();
        $page = $this->page($count, 20);

        $result = $this->comment_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach ($result as $k => $v) {
            $status_string = $v['status'] == 1 ? '取消审核' : '审核';
            $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Comment/audit', array('id' => $v['id'])) . '">' . $status_string . '</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Comment/delete', array('id' => $v['id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $v['full_name'] . '</td>
            <td>' . $v['product_id'] . '</td>
            <td>' . $v['content'] . '</td>
            <td>' . ($v['status'] == 1 ? '通过' : '未审核') . '</td>
            <td>' . date('Y-m-d', $v['create_time']) . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    public function audit()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');
        $status = $this->comment_model->where(array('id' => $id))->getField('status');
        $updata_status = $status == 1 ? 0 : 1;
        $result = $this->comment_model->where(array('id' => $id))->save(array('status' => $updata_status));
        if ($result === false) $this->error('error');
        else $this->success('success');
    }

    public function delete()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('error');
        $result = $this->comment_model->where(array('id' => $id))->delete();
        if (!$result) $this->error('error');
        else $this->success('success');
    }
}