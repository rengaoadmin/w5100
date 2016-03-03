<?php
//http://wwww.plclive.com 2016.03.03//
//错误日志
function echo_server_log($log){
	file_put_contents("log.txt", $log, FILE_APPEND);
}
//定义TOKEN
define ( "TOKEN", "YourTokenWithWechat" );
//验证微信公众平台签名
function checkSignature() {
	$signature = $_GET ['signature'];
	$nonce = $_GET ['nonce'];
	$timestamp = $_GET ['timestamp'];
	$tmpArr = array ($nonce, $timestamp, TOKEN );
	sort ( $tmpArr );
	
	$tmpStr = implode ( $tmpArr );
	$tmpStr = sha1 ( $tmpStr );
	if ($tmpStr == $signature) {
		return true;
	}else{
		return false;
	}
}
if(false == checkSignature()) {
	exit(0);
}
//接入时验证接口
$echostr = $_GET ['echostr'];
if($echostr) {
	echo $echostr;
	exit(0);
}
//获取POST数据
function getPostData() {
	$data = $GLOBALS['HTTP_RAW_POST_DATA'];
	return	$data;
}
$PostData = getPostData();
//验错
if(!$PostData){
	echo_server_log("wrong input! PostData is NULL");
	echo "wrong input!";
	exit(0);
}
//装入XML
$xmlObj = simplexml_load_string($PostData, 'SimpleXMLElement', LIBXML_NOCDATA);
//验错
if(!$xmlObj) {
	echo_server_log("wrong input! xmlObj is NULL\n");
	echo "wrong input!";
	exit(0);
}
//准备XML
$fromUserName = $xmlObj->FromUserName;
$toUserName = $xmlObj->ToUserName;
$msgType = $xmlObj->MsgType;

///////////////////////////////////////////////
$Event = $xmlObj->Event; //通过公众测试平台的自定义菜单进行控制
///////////////////////////////////////////////

if($msgType == 'voice') {//判断是否为语音
	$content = $xmlObj->Recognition;
}elseif($msgType == 'text'){
	$content = $xmlObj->Content;
}
/////////////////////////////////////////////
elseif($Event =='CLICK') { 
	$content = $xmlObj->EventKey;  //获得自定义菜单按钮返回的内容
}
/////////////////////////////////////////////

else{
	$retMsg = '只支持文本和语音消息';
}
if (strstr($content, "温度")) {
	$con = mysql_connect('YourSqlServer:SqlPort','YourUserName','YourPassword');  
	mysql_select_db("YourDatabaseName", $con);//修改数据库名

	$result = mysql_query("SELECT * FROM sensor");
	while($arr = mysql_fetch_array($result)){

	  if ($arr['id'] == 1) {
	  	$tempr = $arr['data'];
	  }
	}
	mysql_close($con);

    $retMsg = "梵谷水郡21#910业主："."\n"."房间当前室温为".$tempr."℃。";
	
}else if (strstr($content, "开灯")) {
	$con = mysql_connect('YourSqlServer:SqlPort','YourUserName','YourPassword'); 

	$dati = date("h:i:sa");
	mysql_select_db("YourDatabaseName", $con);//修改数据库名

	$sql ="UPDATE switch SET timestamp='$dati',state = '1'
	WHERE ID = '1'";//修改开关状态值

	if(!mysql_query($sql,$con)){
	    die('Error: ' . mysql_error());
	}else{
		mysql_close($con);
		$retMsg = "已经开灯";
	}
}else if (strstr($content, "关灯")) {
	$con = mysql_connect('YourSqlServer:SqlPort','YourUserName','YourPassword'); 

	$dati = date("h:i:sa");
	mysql_select_db("YourDatabaseName", $con);//修改数据库名

	$sql ="UPDATE switch SET timestamp='$dati',state = '0'
	WHERE ID = '1'";//修改开关状态值

	if(!mysql_query($sql,$con)){
	    die('Error: ' . mysql_error());
	}else{
		mysql_close($con);
		$retMsg = "已经关灯";
	}	
}else{
	$retMsg = "暂时不支持该命令";
}

//装备XML
$retTmp = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[text]]></MsgType>
		<Content><![CDATA[%s]]></Content>
		<FuncFlag>0</FuncFlag>
		</xml>";
$resultStr = sprintf($retTmp, $fromUserName, $toUserName, time(), $retMsg);

//反馈到微信服务器
echo $resultStr;
?>
