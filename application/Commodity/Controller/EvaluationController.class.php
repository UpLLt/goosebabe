<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/7
 * Time: 上午11:38
 */

namespace Commodity\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\CommentModel;

class EvaluationController extends AdminbaseController
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
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id';
        $count = $this->comment_model
            ->alias('a')
            ->join($join)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->comment_model
            ->alias('a')
            ->join($join)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.*,b.name as product_name')
            ->select();

        $tablebody = '';
        foreach ($result as $k => $v) {
            $status_value =  $v['status'] ? '隐藏' : '显示';
            $result[$k]['str_manage'] = '<a class="js-ajax-dialog-btn" href="' . U('Evaluation/check', array('id' => $v['id'])) . '">'.$status_value.'</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Evaluation/delete', array('id' => $v['id'])) . '">删除</a>';

            $tablebody .= '<tr>
                                <td>' . ($k + 1) . '</td>
                                <td>' . $v['full_name'] . '</td>
                                <td>' . $v['product_name'] . '</td>
                                <td>' . ($v['status'] ? '显示' : '隐藏') . '</td>
                                <td>' . $v['content'] . '</td>
                                <td>' . date('Y-m-d',$v['create_time']) . '</td>
                                <td>' . $result[$k]['str_manage'] . '</td>
                           </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }

    public function delete()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('empty');

        if($this->comment_model->delete($id))
            $this->success('success');
        else
            $this->error('error');
    }


    public function check()
    {
        $id = intval(I('get.id'));
        if (empty($id)) $this->error('empty');

        $status = $this->comment_model->where(array('id' => $id))->getField('status');
        $status = $status ? '0' : '1';

        if($this->comment_model->where(array('id' => $id))->save(array('status' => $status)) === false)
            $this->error('error');
        else
            $this->success('success');
    }

    public function allshow(){
        if($this->comment_model->where(array('id' => array('neq','-1')))->save(array('status' => 1)) === false)
            $this->error('error');
        else
            $this->success('success');

    }
}