<?php
namespace susuzhao88\Zhongjin\Kernel;

use Psr\Container\ContainerInterface;
use susuzhao88\Zhongjin\Kernel\Http\Request;
use susuzhao88\Zhongjin\Kernel\Http\Response;
use susuzhao88\Zhongjin\Support\XML;

class Client{

  protected $app;

  public function __construct(ContainerInterface $app){
    $this->app = $app;
  }

  public function request(string $url, array $params = [], $method = 'POST') {
    // 转为xml
    $xml = new XML($params);
    $document = $xml->getXml();
    $document->version = '1.0';
    $document->encoding = 'UTF-8';
    /**
     * @var \DOMElement
     */
    $node_request = $document->getElementsByTagName('Request')->item(0);
    if($node_request){
      $node_request->setAttribute('version', '2.0');
    }
    $xmlStr = $xml->toString();
    // 签名
    $sign = $this->app->encrypt->sign($xmlStr);
    $message = base64_encode(trim($xmlStr));
    $request = new Request($url, $method, $this->getOptions());
    $response = $request->send(['message'=>$message, 'signature'=>$sign]);
    return Response::buildPsrResponse($response);
  }

  public function getOptions(){
    /**
     * @var Config
     */
    $config = $this->app->get('config');
    return [
      'allow_redirects' =>  [
        'max' =>  10,
        'strict'          => true,
        'referer'         => true,
      ],
      'connect_timeout' =>  $config->get('http.timeout', 120),
      'timeout' =>  $config->get('http.timeout', 120),
      'verify'  =>  false,
      'headers' =>  [
        'Accept-Encoding' =>  '',
        'User-Agent'  =>  'institution',
        'Expect'  =>  '',
      ],
    ];
  }
}