<?php
/*
======================================================================================
��������: ����� ��� �������������� � ����������� CURL
�����	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
 
 
��������� ������������� ������ CURL
$params = array(
		'url'=>'', //������������� ������ HTTP �����
		'host'=>'', //��� �����
		'header'=>'', //��������� �������
		'method'=>'', //����� ������� (GET, POST)
		'referer'=>'', //
		'cookie'=>'', //Cookie ������, ������������ � �������
		'post_fields'=>'', //��������� �������, ������������ ������� POST
		'proxy'=>'', //����� ������, ����� ������� ��������� ���������� ����������(��������� ������)
		'login'=>'', //����� ��� HTTP �������������� �� ��������� �������
		'password'=>'', //������ ��� HTTP �������������� �� ��������� �������
		'timeout'=>30 //������� �������� ������ �� ���������� �������
	);

 ������:
 -----------------------------------------
<?php
require("curl.class.php");
$request = new _curl();
$request->init(array(
		"url"=>"http://yandex.ru",
		"host"=>"yandex.ru",
		"header"=>"",
		"method"=>"GET",
		"referer"=>"",
		"cookie"=>"",
		"proxy"=>"192.168.3.1:3128",
		"timeout"=>20
	));
	$data = $req->exec();
	echo $data["body"];
?>

======================================================================================
*/


#������� ���������� ������, ����������� GZIP
if (!function_exists('gzdecode')) {
	function gzdecode ($data) {
		$flags = ord(substr($data, 3, 1));
		$headerlen = 10;
		$extralen = 0;
		$filenamelen = 0;
		if ($flags & 4) {
			$extralen = unpack('v' ,substr($data, 10, 2));
			$extralen = $extralen[1];
			$headerlen += 2 + $extralen;
		}
		if ($flags & 8)  $headerlen = strpos($data, chr(0), $headerlen) + 1; // ��� �����
		if ($flags & 16) $headerlen = strpos($data, chr(0), $headerlen) + 1; // �����������
		if ($flags & 2)  $headerlen += 2; // CRC �� ��������� ������
		$unpacked = gzinflate(substr($data, $headerlen));
		if ($unpacked === FALSE) $unpacked = $data;
		return $unpacked;
	}
}

class Curl{

	private $ch; #������������� ����������

	public function __construct(){
		$this->ch = curl_init();
	}

	public function __destruct(){
		if($this->ch) curl_close($this->ch);
	}


	/*������������� CURL*/
	public function init($params){
		$user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9';
		$header = array(
		"Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
		"Accept-Language: ru-ru,ru;q=0.7,en-us;q=0.5,en;q=0.3",
		"Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7",
		"Keep-Alive: 300");
		if (isset($params['host']) && $params['host']) $header[]="Host: ".$params['host'];
		if (isset($params['header']) && $params['header']) $header[]=$params['header'];
		
		#��������� ���������� ����� PROXY
		if (isset($params['proxy']) && $params['proxy']){
			@curl_setopt ($this->ch, CURLOPT_PROXY, $params['proxy']); 
			@curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		}
		
		#��������� CURL
		@curl_setopt ( $this->ch , CURLOPT_RETURNTRANSFER , 1 );
		@curl_setopt ( $this->ch , CURLOPT_VERBOSE , 0 );
		@curl_setopt ( $this->ch , CURLOPT_HEADER , 1 );
		@curl_setopt ( $this->ch, CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt ( $this->ch , CURLOPT_HTTPHEADER, $header );
		if ($params['referer'])    @curl_setopt ($this->ch , CURLOPT_REFERER, $params['referer'] );
		@curl_setopt ( $this->ch , CURLOPT_USERAGENT, $user_agent);
		if ($params['cookie'])    @curl_setopt ($this->ch , CURLOPT_COOKIE, $params['cookie']);
		if ($params['cookie_jar']) 	 @curl_setopt ($this->ch, CURLOPT_COOKIEJAR, $params['cookie_jar']);
		if ($params['cookie_jar'])	 @curl_setopt($this->ch, CURLOPT_COOKIEFILE, $params['cookie_jar']);

		#����� HEAD
		if ($params['method'] == "HEAD") @curl_setopt($this->ch,CURLOPT_NOBODY,1);

		#����� POST
		if ( $params['method'] == "POST" ){
			curl_setopt( $this->ch, CURLOPT_POST, true );
			curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params['post_fields'] );
		}
		
		@curl_setopt( $this->ch, CURLOPT_URL, $params['url']);
		@curl_setopt ( $this->ch , CURLOPT_SSL_VERIFYPEER, 0 );
		@curl_setopt ( $this->ch , CURLOPT_SSL_VERIFYHOST, 0 );
		
		#����� � ������ ��� HTTP ��������������
		if (isset($params['login']) && isset($params['password']))
			@curl_setopt($this->ch , CURLOPT_USERPWD,$params['login'].':'.$params['password']);
		@curl_setopt ( $this->ch , CURLOPT_TIMEOUT, $params['timeout']);
	}

	/*���������� ������� � �������
	������������ ������ ��������� 
		'header', //��������� ������
		'body', //���� ������
		'curl_error', //�������� ������ CURL
		'curl_errno', //��� ������ CURL
		'http_code', //��� ������ ���������� �������
		'last_url' //�t������ URL, �� �������� ��� ������� ����� (����� ���������� � URL �������)
	*/
	public function exec(){
		$response = curl_exec($this->ch);
		$errorno = curl_errno($this->ch);
		$result = array('header'=>'', 
						'body'=>'', 
						'curl_errno'=>0, 
						'curl_error'=>'', 
						'http_code'=>'',
						'last_url'=>'');
		if($errorno > 0){
			$result['curl_errno'] = $errorno;
			$result['curl_error'] = curl_error($this->ch);
			return $result;
		}

		$header_size = curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);
		$result['header'] = substr($response, 0, $header_size);
		$result['body'] = substr( $response, $header_size );
		if(strpos($result['header'],'Content-Encoding: gzip')!==false) $result['body']=gzdecode($result['body']);
		$result['http_code'] = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
		$result['last_url'] = curl_getinfo($this->ch,CURLINFO_EFFECTIVE_URL);
		//curl_close($this->ch);
		return $result;
	}
}
?>