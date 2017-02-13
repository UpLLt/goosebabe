<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>跳转提示</title>
<style type="text/css">
* {
    padding: 0;
    margin: 0;
}
body {
    background: #fff;
    font-family: '微软雅黑';
    color: #333;
    font-size: 16px;
}
.system-message {
    position:absolute;
    top:50%;
    left:50%;
    margin:-150px 0 0 -150px;
    width:300px;
    height:300px;
    text-align:center;
}

.system-message h1 {
    font-size: 120px;
    font-weight: normal;
}
.system-message .jump {
    padding-top: 10px
}
.system-message .jump a {
    color: #09C;
    text-decoration:none
}
.system-message .success, .system-message .error {
    line-height: 1.8em;
    font-size: 36px
}
.system-message .detail {
    font-size: 12px;
    line-height: 20px;
    margin-top: 12px;
    display:none
}
    .sss{
        width: 100%;
        float: left;
        text-align: center;
        position: absolute;
        top:35%;
    }
    .sss .error{
        color:#ff8d44 ;
        font-size: 36px;
    }
    .jump{
        margin: 50px 0px 0px;
        line-height: 50px;
        color:#999;;
    }
    .jump span{
        width: 100%;
        float: left;
        font-size: 24px;
        text-align: center;
    }
.jump strong{
    width: 100%;
    float: left;
    font-size: 14px;
    text-align: center;
}
    .jump a{
        margin: 0px 40px 0px 0px;
        color: blue;
    }
</style>
</head>
<body>
<div class="sss">
    <div class="" />
    <img src="/public/images/ero.png" />
</div>
  <?php if(isset($message)) {?>
  <h1>√</h1>
  <p class="success"><?php echo($message); ?></p>
  <style>
  .system-message{border:3px solid #09C;}
  .system-message h1{ color: #09C;}
  </style>
  <?php }else{?>

  <p class="error"><?php echo($error); ?></p>
   <style>
  .system-message{border:3px solid #F33;}
  .system-message h1{ color: #F33;}
  </style>
  <?php }?>

  <p class="detail"></p>

  <p class="jump"> 页面自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait"> 2</b> </p>
</div>
<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
    var time = --wait.innerHTML;
    if(time <= 0) {
        location.href = href;
        clearInterval(interval);
    };
}, 1000);
})();
</script>
</body>
</html>