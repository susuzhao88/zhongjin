<?php
namespace susuzhao88\Zhongjin\Kernel;

use Psr\Container\ContainerInterface;

class Client{

  protected $app;

  public function __construct(ContainerInterface $app){
    $this->app = $app;
  }

  public function request(string $url, $method){

  }
}