<?php

/*
 * author : 安德兔
 * link : http://www.andetu.com/code/1861
 */

namespace app\index\model;
use think\Validate;
use think\Log;
class Pay extends \think\Model
{
	private function _weixin_config(){
		define('WXPAY_APPID', "");//微信公众号APPID
		define('WXPAY_MCHID', "");//微信商户号MCHID
		define('WXPAY_KEY', "");//微信商户自定义32位KEY
		define('WXPAY_APPSECRET', "");//微信公众号appsecret
		vendor('wxpay.WxPay_Api');
		vendor('wxpay.WxPay_NativePay');
	}

	public function weixin($data=[])
	{
		$validate = new Validate([
			['body','require','请输入订单描述'],
			['attach','require','请输入订单标题'],
			['out_trade_no','require|alphaNum','订单编号输入错误|订单编号输入错误'],
			['total_fee','require|number|gt:0','金额输入错误|金额输入错误|金额输入错误'],
			['notify_url','require','异步通知地址不为空'],
			['trade_type','require|in:JSAPI,NATIVE,APP','交易类型错误'],
		]);
		if (!$validate->check($data)) {
			return ['code'=>0,'msg'=>$validate->getError()];
		}
		$this->_weixin_config();
		$notify = new \NativePay();
		$input = new \WxPayUnifiedOrder();
		$input->SetBody($data['body']);
		$input->SetAttach($data['attach']);
		$input->SetOut_trade_no($data['out_trade_no']);
		$input->SetTotal_fee($data['total_fee']);
		$input->SetTime_start($data['time_start']);
		$input->SetTime_expire($data['time_expire']);
		$input->SetGoods_tag($data['goods_tag']);
		$input->SetNotify_url($data['notify_url']);
		$input->SetTrade_type($data['trade_type']);
		$input->SetProduct_id($data['product_id']);
		$result = $notify->GetPayUrl($input);
		if($result['return_code'] != 'SUCCESS'){
			return ['code'=>0,'msg'=> $result['return_msg']];
		}
		if($result['result_code'] != 'SUCCESS'){
			return ['code'=>0,'msg'=> $result['err_code_des']];
		}
		return ['code'=>1,'msg'=>$result["code_url"]];
	}

	public function notify_weixin($data='')
	{
		if(!$data){
			return false;
		}
		$this->_weixin_config();
    	$doc = new \DOMDocument();
		$doc->loadXML($data);
		$out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
		$transaction_id = $doc->getElementsByTagName("transaction_id")->item(0)->nodeValue;
		$openid = $doc->getElementsByTagName("openid")->item(0)->nodeValue;
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($input);
		if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
			// 处理支付成功后的逻辑业务
			Log::init([
				'type'  =>  'File',
				'path'  =>  LOG_PATH.'../paylog/'
			]);
			Log::write($result,'log');
			return 'SUCCESS';
		}
		return false;
	}
}
?>