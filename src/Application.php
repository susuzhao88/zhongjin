<?php
namespace susuzhao88\Zhongjin;

use susuzhao88\Zhongjin\Exceptions\Exception;
use susuzhao88\Zhongjin\Kernel\Client;
use susuzhao88\Zhongjin\Kernel\Config;
use susuzhao88\Zhongjin\Kernel\Encrypt;
use susuzhao88\Zhongjin\Kernel\Gateway;
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
    $this->setAlias('app', 'service_container');
    $this->autowire(Config::class,Config::class)->addArgument($this->getConfig());
    $this->setAlias('config', Config::class);
    $this->autowire(Encrypt::class, Encrypt::class)->addArgument(new Reference('config'));
    $this->setAlias('encrypt', Encrypt::class);
    $this->autowire(Client::class, Client::class)->addArgument(new Reference('app'));
    $this->setAlias('client', Client::class);
    $this->autowire('gateway', Gateway::class)->addArgument(new Reference('app'));
  }

  public function __get($name)
  {
    if($this->has($name)) return $this->get($name);
    throw new Exception("属性不存在");
  }
  
}