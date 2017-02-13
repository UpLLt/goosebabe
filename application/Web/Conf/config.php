<?php
return array(


	//微信配置参数
	'WEIXINPAY_CONFIG'       => array(
		'APPID'              => 'wxb7eacd0d5aff8dc6', // 微信支付APPID
		'MCHID'              => '1390903802', // 微信支付MCHID 商户收款账号
		'KEY'                => 'a665ad4138ed95e0d7dfa3cd854e6562', // 微信支付KEY
		'APPSECRET'          => '6b990b2e7914f73a0c74c2c31c073222', // 公众帐号secert (公众号支付专用)
		'NOTIFY_URL'         => 'http://www.goosebabe.com/Web/Order/notify', // 接收支付状态的连接
	),


	//支付宝配置参数
	'alipay_config'=>array(
		'partner' =>'2088421964434755',   //这里是你在成功申请支付宝接口后获取到的PID；
		'seller_id' => '2088421964434755',
		'key'=>'5dxihgq6iys6oy717do1d2968ukhq34b',//这里是你在成功申请支付宝接口后获取到的Key
		'sign_type'=>strtoupper('MD5'),
		'input_charset'=> strtolower('utf-8'),
		'cacert'=> getcwd().'\\cacert.pem',
		'transport'=> 'http',
	),
	//以上配置项，是从接口包中alipay.config.php 文件中复制过来，进行配置；

	'alipay'   =>array(
		//这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
		'seller_email'=>'ouwenyisen@126.com',
		//这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
		'notify_url'=>'http://www.goosebabe.com/Web/Alipay/notifyurl',
		//这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
		'return_url'=>'http://www.goosebabe.com/Web/Alipay/returnurl',
		//支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
		'successpage'=>'http://www.goosebabe.com/Web/Order/pay_success',
		//支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
		'errorpage'=>'http://www.goosebabe.com/Web/Index/index',
	),

	'DEFAULT_THEME'=> '',
//	'URL_MODEL'   => 2,

	'TEST_ID'=> '44,33',

);