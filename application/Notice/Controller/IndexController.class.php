<?php
namespace Notice\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\NoticeModel;
use Think\Controller;

class IndexController extends AdminbaseController
{

    private $notice_model;

    public function __construct()
    {
        parent::__construct();
        $this->notice_model = new NoticeModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    public function _lists()
    {
        $fields = array(
            'start_time' => array("field" => "create_time", "operator" => ">", 'datatype' => 'time'),
            'end_time' => array("field" => "create_time", "operator" => "<", 'datatype' => 'time'),
        );
        $where_ands = array();
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }
        $where = join(" and ", $where_ands);
        $count = $this->notice_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->notice_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        foreach ($result as $k => $v) {
            if(!$v['status']){
                $result[$k]['str_manage'] .= '<a class="js-ajax-dialog-btn" href="' . U('Index/show_to_home', array('id' => $v['id'])) . '">推到首页</a>';
                $result[$k]['str_manage'] .= ' | ';
            }
            $result[$k]['str_manage'] .= '<a class="" href="' . U('Index/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete"  href="' . U('Index/delete', array('id' => $v['id'])) . '">删除</a>';


            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $result[$k]['title'] . '</td>
            <td><a href="' . sp_get_asset_upload_path($result[$k]['smeta']) . '" target=\'_blank\'>     <img style="width: 40px;height: 30px" src="' . sp_get_asset_upload_path($result[$k]['smeta']) . '"></a></td>
            <td>' . date('Y-m-d H:i:s',$v['create_time']). '</td>
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
        if (IS_POST) {
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);

            $article = I("post.post");
            $article['smeta'] = $_POST['smeta']['thumb'];
            $article['create_time'] = time();

            $article['content'] = htmlspecialchars_decode($article['content']);

            if ($this->notice_model->create($article)) {
                if ($this->notice_model->add()) {
                    $this->success('success');
                } else {
                    $this->error('error');
                }
            } else {
                $this->error($this->notice_model->getError());
            }
        }
    }


    public function show_to_home()
    {
        $id = intval(I('get.id'));
        if(empty($id)) $this->error('empty');
        $this->notice_model->startTrans();
        $iscommit = true;

        if($this->notice_model->where(array('id' => array('neq','-1')))->save(array('status' => 0)) === false) $iscommit = false;
        if($this->notice_model->where(array('id'=>$id))->save(array('status' => 1)) === false) $iscommit = false;

        if($iscommit){
            $this->notice_model->commit();
            $this->success('success');
        }else{
            $this->notice_model->rollback();
            $this->error('error');
        }

    }

    public function delete()
    {
        $id = intval(I('get.id'));
        if(empty($id)) $this->error('empty');
        if($this->notice_model->where(array('id'=>$id))->getField('status') == 1) $this->error('请先指定首页公告，再删除本公告.');
        if($this->notice_model->delete($id))
            $this->success('success');
        else $this->error('error');
    }

    public function edit()
    {
        $id = intval(I('get.id'));
        if(empty($id)) $this->error('empty');
        $result = $this->notice_model->find($id);
        $result['smeta'] = sp_get_asset_upload_path($result['smeta']);
        $this->assign('data',$result);
        $this->display();
    }

    public function edit_post()
    {
        if (IS_POST) {
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $article = I("post.post");
            $article['smeta'] = $_POST['smeta']['thumb'];
            $article['create_time'] = time();
            $article['content'] = htmlspecialchars_decode($article['content']);

            if ($this->notice_model->create($article)) {
                if ($this->notice_model->save() === false) {
                    $this->error('error');
                } else {
                    $this->success('success');
                }
            } else {
                $this->error($this->notice_model->getError());
            }
        }
    }
}