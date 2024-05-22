<?php

namespace susuzhao88\Zhongjin\Kernel;

use Psr\Container\ContainerInterface;
use susuzhao88\Zhongjin\Exceptions\GatewayException;
use susuzhao88\Zhongjin\Kernel\Http\Response;

class Gateway
{

  protected $app;

  public function __construct(ContainerInterface $app)
  {
    $this->app = $app;
  }


  /**
   * @description: 
   * @param string $txCode 交易编号 eg.4600
   * @param array $params 交易参数 eg. xml body [TxSN=>...,BusinessType=>...,ImageInfo=>[[ItemNo=>...,ImageType=>...,ImageContent=>...],...],...]
   * @return Response
   */  
  public function trade(string $txCode, array $params) 
  {
    $class = "\\susuzhao88\\Zhongjin\\Gateways\\Trade".$txCode;
    if(!class_exists($class)){
      throw new GatewayException("网关错误.");
    }
    return (new $class($this->app))($params);
  }
}
