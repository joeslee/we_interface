<?php
/**
 *	微信 公众平台消息接口 SDK
 *  @author xhxu xh_xu@qq.com/QQ:7844577
 *  @version 1.0.20130103
 */
class Weixin
{
	public $token = '';
	public $debug =  false;
	public $flag = false;
	public $msgtype = 'text';	//('text','image','location')
	Public $msg = array();
	Public $version = '1.0.20130103';

	
	public function __construct($token,$debug)
	{
		$this->token = $token;
		$this->debug = $debug;
	}
	public function getMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if ($this->debug) {
			file_put_contents('./log.txt', $postStr."\n",FILE_APPEND);
		}
		if (!empty($postStr)) {
			$this->msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$this->msgtype = strtolower($this->msg['MsgType']);
		}
	}
	public function makeText($text='')
	{
		$CreateTime = time();
		$FuncFlag = $this->flag ? 1 : 0;
		$textTpl = "<xml>
			<ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
			<FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<FuncFlag>%s</FuncFlag>
			</xml>";
		return sprintf($textTpl,$text,$FuncFlag);
	}
	public function makeNews($newsData=array())
	{
		$CreateTime = time();
		$FuncFlag = $this->flag ? 1 : 0;
		$newTplHeader = "<xml>
			<ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
			<FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<ArticleCount>%s</ArticleCount><Articles>";
		$newTplItem = "<item>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<PicUrl><![CDATA[%s]]></PicUrl>
			<Url><![CDATA[%s]]></Url>
			</item>";
		$newTplFoot = "</Articles>
			<FuncFlag>%s</FuncFlag>
			</xml>";
		$Content = '';
		$itemsCount = count($newsData['items']);
		$itemsCount = $itemsCount < 10 ? $itemsCount : 10;
		if ($itemsCount) {
			foreach ($newsData['items'] as $key => $item) {
				if ($key<=9) {
					$Content .= sprintf($newTplItem,$item['title'],$item['description'],$item['picurl'],$item['url']);
				}
			}
		}
		$header = sprintf($newTplHeader,$newsData['content'],$itemsCount);
		$footer = sprintf($newTplFoot,$FuncFlag);
		return $header . $Content . $footer;
	}
	public function reply($data)
	{
		if ($this->debug) {
			file_put_contents('./reply.txt', $data."\n",FILE_APPEND);
		}
		echo $data;
	}
	public function valid()
	{
		if ($this->checkSignature()) {
			if( $_SERVER['REQUEST_METHOD']=='GET' )
			{
				echo $_GET['echostr'];
				exit;
			}
		}else{
			file_put_contents('./log.txt', 'valid fild'."\n",FILE_APPEND);
			exit;
		}
	}
	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$tmpArr = array($this->token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 下载远程图片
	 * @param string $url 图片的绝对url
	 * @param string $filepath 文件的完整路径（包括目录，不包括后缀名,例如/www/images/test） ，此函数会自动根据图片url和http头信息确定图片的后缀名
	 * @return mixed 下载成功返回一个描述图片信息的数组，下载失败则返回false
	 */
/*		public function grabImage($url, $filepath) {
		//服务器返回的头信息
		$responseHeaders = array();
		//原始图片名
		$originalfilename = '';
		//图片的后缀名
		$ext = '';
		$ch = curl_init($url);
		//设置curl_exec返回的值包含Http头
		curl_setopt($ch, CURLOPT_HEADER, 1);
		//设置curl_exec返回的值包含Http内容
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//设置抓取跳转（http 301，302）后的页面
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		//设置最多的HTTP重定向的数量
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	
		//服务器返回的数据（包括http头信息和内容）
		$html = curl_exec($ch);
		//获取此次抓取的相关信息
		$httpinfo = curl_getinfo($ch);
		curl_close($ch);
		if ($html !== false) {
			//分离response的header和body，由于服务器可能使用了302跳转，所以此处需要将字符串分离为 2+跳转次数 个子串
			$httpArr = explode("\r\n\r\n", $html, 2 + $httpinfo['redirect_count']);
			//倒数第二段是服务器最后一次response的http头
			$header = $httpArr[count($httpArr) - 2];
			//倒数第一段是服务器最后一次response的内容
			$body = $httpArr[count($httpArr) - 1];
			$header.="\r\n";
	
			//获取最后一次response的header信息
			preg_match_all('/([a-z0-9-_]+):\s*([^\r\n]+)\r\n/i', $header, $matches);
			if (!empty($matches) && count($matches) == 3 && !empty($matches[1]) && !empty($matches[1])) {
				for ($i = 0; $i < count($matches[1]); $i++) {
					if (array_key_exists($i, $matches[2])) {
						$responseHeaders[$matches[1][$i]] = $matches[2][$i];
					}
				}
			}
			//获取图片后缀名
			if (0 < preg_match('{(?:[^\/\\\\]+)\.(jpg|jpeg|gif|png|bmp)$}i', $url, $matches)) {
				$originalfilename = $matches[0];
				$ext = $matches[1];
			} else {
				if (array_key_exists('Content-Type', $responseHeaders)) {
					if (0 < preg_match('{image/(\w+)}i', $responseHeaders['Content-Type'], $extmatches)) {
						$ext = $extmatches[1];
					}
				}
			}
			//保存文件
			if (!empty($ext)) {
				$filepath .= ".$ext";
				//如果目录不存在，则先要创建目录
				CFiles::createDirectory(dirname($filepath));
				$local_file = fopen($filepath, 'w');
				if (false !== $local_file) {
					if (false !== fwrite($local_file, $body)) {
						fclose($local_file);
						$sizeinfo = getimagesize($filepath);
						return array('filepath' => realpath($filepath), 'width' => $sizeinfo[0], 'height' => $sizeinfo[1], 'orginalfilename' => $originalfilename, 'filename' => pathinfo($filepath, PATHINFO_BASENAME));
					}
				}
			}
		}
		return false;
	}*/
}
