<?php
namespace susuzhao88\Zhongjin\Kernel;

use susuzhao88\Zhongjin\Support\Encrypt\Linux;
use susuzhao88\Zhongjin\Support\Encrypt\Windows;

class Encrypt{

  protected $encrypt;

  protected $config;

  public function __construct(Config $config)
  {
    $this->config = $config;
    $this->build();
  }

  protected function build(){
    $os = $this->getOs();
    switch($os){
      case 'windows':
        $class = Windows::class;
        break;
      case 'linux':
        $class = Linux::class;
        break;
      default:
        $class = Linux::class;
    }

    $this->encrypt = new $class($this->config->toArray());
  }

  protected function getOs(){
    $uname = strtolower(php_uname());
    if (strpos($uname, 'win') !== false) {
      return 'windows';
    } elseif (strpos($uname, 'linux') !== false) {
      return 'linux';
    } else {
      return 'unknown';
    }
  }

  public function __call($name, $arguments)
  {
    return call_user_func_array([$this->encrypt, $name], $arguments);
  }
}
