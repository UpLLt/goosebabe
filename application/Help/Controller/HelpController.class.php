<?php
namespace Help\Controller;

use Common\Controller\AdminbaseController;
use Common\Model\HelpModel;

class HelpController extends AdminbaseController {
    protected $Help_model;

    public function __construct()
    {
        parent::__construct();
        $this->Help_model = new HelpModel();
    }


    public function lists(){
        $this->_lists();
        $this->display();
    }


    public function _lists(){
        $help = $this->Help_model->select();
        $helps = '';

        foreach( $help as $k => $v){
            if( $v['type'] == HelpModel::SHOPP__PROCESS )   $url = U('Help/shopping');
            if( $v['type'] == HelpModel::COMMON_PROBLEM )   $url = U('Help/index');
            if( $v['type'] == HelpModel::PAY_MONEY )        $url = U('Help/pay');
            if( $v['type'] == HelpModel::FREIGHT )          $url = U('Help/freight');
            if( $v['type'] == HelpModel::CONNECT_CUSTOMER ) $url = U('Help/connect');
            if( $v['type'] == HelpModel::ABOUT_US )         $url = U('Help/about_us');

            $helps .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $v['title'] . '</td>
            <td>' . date('Y-m-d H:i:s',$v['create_time']) . '</td>
            <td><a  href="' . $url . '">编辑</a></td>
        </tr>';

        }


        $this->assign('help',$helps);
    }


    /**
     * 常见问题
     */
    public function index(){


        $platform = $this->Help_model->where(array('type'=>HelpModel::COMMON_PROBLEM))->field('content,id,title')->find();
        $platform['content'] =  html_entity_decode($platform['content']);
        if( empty( $platform['id']) ){

            $add['title']       = $this->Help_model->getHelpTostring(HelpModel::COMMON_PROBLEM);
            $add['type']        = HelpModel::COMMON_PROBLEM;
            $add['create_time'] = time();
            $add['content']     = '';
            $this->Help_model->add($add);
            $platform = $this->Help_model->where(array('type'=>HelpModel::COMMON_PROBLEM))->field('content,id,title')->find();

        }

        $this->assign('list',$platform);
        $this->display('index');
    }






    /**
     * 购物流程
     */
    public function shopping(){


        $platform = $this->Help_model->where(array('type'=>HelpModel::SHOPP__PROCESS))->field('content,id,title')->find();
        $platform['content'] =  html_entity_decode($platform['content']);
        if( empty( $platform['id']) ){

            $add['title']       = $this->Help_model->getHelpTostring(HelpModel::SHOPP__PROCESS);
            $add['type']        = HelpModel::SHOPP__PROCESS;
            $add['create_time'] = time();
            $add['content']     = '';
            $this->Help_model->add($add);
            $platform = $this->Help_model->where(array('type'=>HelpModel::SHOPP__PROCESS))->field('content,id,title')->find();
        }

        $this->assign('list',$platform);
        $this->display('index');
    }

    /**
     * 在线支付
     */
    public function pay(){

        $platform = $this->Help_model->where(array('type'=>HelpModel::PAY_MONEY))->field('content,id,title')->find();
        $platform['content'] =  html_entity_decode($platform['content']);
        if( empty( $platform['id']) ){

            $add['title']       = $this->Help_model->getHelpTostring(HelpModel::PAY_MONEY);
            $add['type']        = HelpModel::PAY_MONEY;
            $add['create_time'] = time();
            $add['content']     = '';
            $this->Help_model->add($add);
            $platform = $this->Help_model->where(array('type'=>HelpModel::PAY_MONEY))->field('content,id,title')->find();
        }

        $this->assign('list',$platform);
        $this->display('index');
    }

    /**
     * 运费及时效
     */
    public function freight(){

        $platform = $this->Help_model->where(array('type'=>HelpModel::FREIGHT))->field('content,id,title')->find();
        $platform['content'] =  html_entity_decode($platform['content']);
        if( empty( $platform['id']) ){

            $add['title']       = $this->Help_model->getHelpTostring(HelpModel::FREIGHT);
            $add['type']        = HelpModel::FREIGHT;
            $add['create_time'] = time();
            $add['content']     = '';
            $this->Help_model->add($add);
            $platform = $this->Help_model->where(array('type'=>HelpModel::FREIGHT))->field('content,id,title')->find();
        }

        $this->assign('list',$platform);
        $this->display('index');
    }

    /**
     * 联系客服
     */
    public function connect(){

        $platform = $this->Help_model->where(array('type'=>HelpModel::CONNECT_CUSTOMER))->field('content,id,title')->find();
        $platform['content'] =  html_entity_decode($platform['content']);
        if( empty( $platform['id']) ){

            $add['title']       = $this->Help_model->getHelpTostring(HelpModel::CONNECT_CUSTOMER);
            $add['type']        = HelpModel::CONNECT_CUSTOMER;
            $add['create_time'] = time();
            $add['content']     = '';
            $this->Help_model->add($add);
            $platform = $this->Help_model->where(array('type'=>HelpModel::CONNECT_CUSTOMER))->field('content,id,title')->find();

       }

        $this->assign('list',$platform);
        $this->display('index');
    }

    /**
     * 关于我们
     */
    public function about_us(){

        $platform = $this->Help_model->where(array('type'=>HelpModel::ABOUT_US))->field('content,id,title')->find();
        $platform['content'] =  html_entity_decode($platform['content']);
        if( empty( $platform['id']) ){

            $add['title']       = $this->Help_model->getHelpTostring(HelpModel::ABOUT_US);
            $add['type']        = HelpModel::ABOUT_US;
            $add['create_time'] = time();
            $add['content']     = '';
            $this->Help_model->add($add);
            $platform = $this->Help_model->where(array('type'=>HelpModel::ABOUT_US))->field('content,id,title')->find();

        }

        $this->assign('list',$platform);
        $this->display('index');
    }


    /**
     * 修改常见问题
     */
    public function edit(){
        $result = $this->Help_model
                        ->where(array('id'=>I('id')))
                        ->save(array(
                            'content' =>I('content'),
                            'create_time'=>time()

                        ));
        if($result) {
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }

}