<?php
namespace susuzhao88\Zhongjin\Kernel\Http;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use susuzhao88\Zhongjin\Exceptions\RequestException;

class Request{

  protected $params;

  protected $client;

  protected $method;

  protected $options;

  protected $url;

  public function __construct($url = '',$method = 'POST', $options = [])
  {
    $this->client = new Client();
    $this->setUrl($url);
    $this->setMethod($method);
    $this->setOptions($options);
  }

  public function setUrl($url){
    $this->url = $url;
    return $this;
  }

  public function getUrl(){
    return $this->url;
  }

  public function setMethod($method){
    $this->method = strtoupper($method);
    return $this;
  }

  public function getMethod(){
    return $this->method;
  }

  public function setOptions($options){
    $this->options = $options;
    return $this;
  }
  public function getOptions(){
    return $this->options;
  }

  public function getParams(){
    return $this->params;
  }

  /**
   * @description: 发送http请求
   * @param {array} $params 参数
   * @param {bool} $json 是否发送json
   * @return {ResponseInterface}
   */
  
  public function send(array $params = [], bool $json = false){
    if($params) $this->params = $params;
    switch($this->method){
      case 'DELETE':
      case 'PUT':
      case 'POST':
        $key = $json ? 'json' : 'form_params';
        $this->options = array_merge($this->options, [
          $key =>  $this->params,
        ]);
        break;
      case 'GET':
        $this->options = array_merge($this->options, [
          'query' =>  $this->params,
        ]);
        break;
      default:
        throw new RequestException("不支持请求方法[".$this->getMethod()."].");
    }
    try{
      return $this->client->request($this->getMethod(), $this->getUrl(), $this->getOptions());
    }catch(\Exception $e){
      throw new RequestException($e->getMessage(), $e->getCode(), $e->getPrevious());
    }
  }

}