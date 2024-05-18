<?php
namespace susuzhao88\Zhongjin;

use susuzhao88\Zhongjin\Exceptions\Exception;
use susuzhao88\Zhongjin\Kernel\Config;
use susuzhao88\Zhongjin\Kernel\Encrypt;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class Application extends ContainerBuilder{

  /**
   * @var array
   */
  protected $userConfig = [];

  public function __construct(array $config = [])
  {
    parent::__construct();
    $this->userConfig = $config;
    $this->registerBase();
  }

  public function getConfig(){
    $default_config = [];

    return array_replace_recursive($default_config, $this->userConfig);
  }

  protected function registerBase(){
    $this->register('app', $this)->setSynthetic(true)->setPublic(true)->setAutowired(true);
    $this->autowire(Config::class,Config::class)->addArgument($this->getConfig())->setPublic(true);
    $this->setAlias('config', Config::class);
    $this->autowire(Encrypt::class, Encrypt::class)->addArgument(new Reference('config'))->setPublic(true);
    $this->setAlias('encrypt', Encrypt::class);
  }

  public function __get($name)
  {
    if($this->has($name)) return $this->get($name);
    throw new Exception("属性不存在");
  }
  
}