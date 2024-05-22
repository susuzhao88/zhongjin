<?php
namespace susuzhao88\Zhongjin\Gateways;

use Psr\Container\ContainerInterface;
use susuzhao88\Zhongjin\Exceptions\GatewayException;

abstract class Base{

  protected $app;

  public function __construct(ContainerInterface $app)
  { 
    $this->app = $app;
  }


  protected function execute($params){
    $params = $this->handleParams($params);
    $url = $this->getUrl();
    $client = $this->app->client;
    $response = $client->request($url, $params);
    list($plain, $sign) = explode(',', $response->getBodyContents());
    $plain = trim(base64_decode($plain));
    // 验签
    $verify = $this->app->encrypt->verify($plain, $sign);
    if(!$verify){
      throw new GatewayException("验签失败");
    }
    return $plain;
  }

  protected function handleParams($params){
    return $params;
  }
  abstract protected function getUrl();
  /**
   * @return bool
   */
  protected function sandbox(){
    return (bool) $this->app->config->get('sandbox', false);
  }

  public function __invoke($params)
  {
    return $this->execute($params);    
  }
}