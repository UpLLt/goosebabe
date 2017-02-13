<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/9
 * Time: 16:09
 */

namespace Message\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\MemberModel;
use Common\Model\SmslogModel;

class MessageadminController extends AdminbaseController
{
    protected $smslog_model;
    protected $member_model;

    public function __construct()
    {
        parent::__construct();
        $this->smslog_model = new SmslogModel();
        $this->member_model = new MemberModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $keyword = I('keyword');
        if (!empty($keyword)) {
            $where['mobile'] = array('like', "%$keyword%");
            $_GET['keyword'] = $keyword;
        }
        $count = $this->smslog_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->smslog_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['mobile'] . '</td>
            <td>' . $result[$k]['code'] . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td>' . date('Y-m-d H:i:s', $result[$k]['end_time']) . '</td>
            <td>' . (time() > $result[$k]['end_time'] ? '<span class="text-error">已过期</span>' : '<span class="text-success">未过期</span>') . '</td>
        </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }
}