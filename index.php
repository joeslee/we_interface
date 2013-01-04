<?php

		/**
		 * wechat php test
		 */
		//error_reporting(E_ALL^E_NOTICE);
		//define your token
		define("TOKEN", "joeslee" );
		$wechatObj = new wechatCallbackapiTest();
		//$wechatObj->valid();
		$wechatObj->responseMsg();


		class wechatCallbackapiTest {
					public function valid() {
								$echoStr = $_GET["echostr"];

								//valid signature , option
								if($this->checkSignature() ) {
											echo $echoStr;
											exit;
								}
					}


					public function responseMsg() {
								// get post data, May be due to the different environments
								$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

								// extract post data
								if(! empty($postStr ) ) {

											$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
											$fromUsername = $postObj->FromUserName;
											$toUsername = $postObj->ToUserName;
											$keyword = trim($postObj->Content );
											$msgtype = $postObj->MsgType;
											$time = time();
											$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";

											if(! empty($msgtype ) ) {
														$msgType = "text";
														$picUrl = $postObj->PicUrl;
														$contentStr = "您的用户名:" . $fromUsername . "\n\n我们:" . $toUsername . "\n\n消息类型:" .
																	$msgtype."\n\n图片地址:".$picUrl;
														$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr );
														echo $resultStr;
											}
											else {
														echo "Input something...";
											}
								}
								else {
											echo "";
											exit();
								}
					}

					private function checkSignature() {
								$signature = $_GET["signature"];
								$timestamp = $_GET["timestamp"];
								$nonce = $_GET["nonce"];

								$token = TOKEN;
								$tmpArr = array(
											$token,
											$timestamp,
											$nonce );
								sort($tmpArr );
								$tmpStr = implode($tmpArr );
								$tmpStr = sha1($tmpStr );

								if($tmpStr == $signature ) {
											return true;
								}
								else {
											return false;
								}
					}
		}

?>



<?php 

$msgtype = new getMsg();

$msgtype->


?>