<?php

namespace susuzhao88\Zhongjin\Command;

use COM;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command
{
  protected $output;
  protected $input;

  protected function configure()
  {
    $this
      ->setName('install')
      ->setDescription('安装配置扩展.')
      ->setHelp("安装配置扩展");
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $os = $this->getOs();
    if ($os == 'unknown') {
      $output->writeln('<error>不支持此操作系统.</error>');
      return 1;
    }

    if ($os == 'windows') {
      return $this->winRegisterExtension();
    }

    if($os == 'linux'){
      return $this->linuxRegisterExtension();
    }
  }

  protected function getOs()
  {
    $uname = strtolower(php_uname());
    if (strpos($uname, 'win') !== false) {
      return 'windows';
    } elseif (strpos($uname, 'linux') !== false) {
      return 'linux';
    } else {
      return 'unknown';
    }
  }

  protected function winRegisterExtension()
  {
    $ini_files = php_ini_loaded_file();
    // 安装扩展
    if (!extension_loaded('com_dotnet')) {
      $extensionDir = $this->getPHPExtensionDir();
      if($extensionDir === false) {
        $this->output->writeln('<error>php_com_dotnet 扩展开启失败.</error>');
        return 1;
      } else {
        if(!file_exists($extensionDir.DIRECTORY_SEPARATOR . 'php_com_dotnet.dll')){
          $this->output->writeln('<error>php_com_dotnet 扩展未找到.</error>');
          return 1;
        }
        // 打开扩展
        $fp = fopen($ini_files, 'a');
        $res = fwrite($fp, "\n[COM_DOT_NET]\nextension=php_com_dotnet.dll\n");
        fclose($fp);
        if($res === false){
          $this->output->writeln('<error>php_com_dotnet 扩展开启失败.</error>');
          return 1;
        }
        $this->output->writeln('<fg=green>php_com_dotnet 扩展开启成功.</>');
      }
    }

    // 注册动态文件
    $target_path = getenv('SystemRoot');
    if(!$target_path){
      $this->output->writeln('<error>%systemroot% 不存在.</error>');
    }
    // 当前机器位数
    $machine_bit = $this->getMachineBit();
    if($machine_bit === false) {
      $this->output->writeln('<error>无法确定系统位数.</error>');
      return 1;
    }
    // 文件
    $extension_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension'. DIRECTORY_SEPARATOR . 'windows';
    if($machine_bit == 32){
      $target_path .= DIRECTORY_SEPARATOR . 'System32';
      $extension_path .= DIRECTORY_SEPARATOR . 'x86';
    }

    if($machine_bit == 64){
      $target_path .= DIRECTORY_SEPARATOR . 'SysWOW64';
      $extension_path .= DIRECTORY_SEPARATOR . 'x64';
    }
    $target_exe = $target_path . DIRECTORY_SEPARATOR . 'CryptoKit.Standard.exe';
    $target_dll = $target_path . DIRECTORY_SEPARATOR . 'CryptoKit.Standard.dll';
    $extension_exe = $extension_path . DIRECTORY_SEPARATOR . 'CryptoKit.Standard.exe';
    $extension_dll = $extension_path . DIRECTORY_SEPARATOR . 'CryptoKit.Standard.dll';

    if(!file_exists($target_exe)){
      $res = copy($extension_exe, $target_exe);
      if(!$res) {
        $this->output->writeln('<error>CryptoKit.Standard.exe 注册失败.</error>');
        return 1;
      }
    }

    if(!file_exists($target_dll)){
      $res = copy($extension_dll, $target_dll);
      if(!$res) {
        $this->output->writeln('<error>CryptoKit.Standard.dll 注册失败.</error>');
        return 1;
      }
    }

    // 注册dll
    $com = new COM('WScript.Shell');
    $result = $com->Run("regsvr32 /s " . $target_dll, 0, true);
    if ($result !== 0) {
      $this->output->writeln('<error>CryptoKit.Standard.dll 注册失败.</error>');
      return 1;
    }
    $this->output->writeln('<fg=green>completed.</>');
    return 0;
  }

  protected function linuxRegisterExtension(){
    $ini_files = php_ini_loaded_file();
    $enable_dl = (bool) ini_get('enable_dl');
    // 
    if($ini_files === false){
        $this->output->writeln('<error>未找到php.ini.</error>');
        return 1;
    }
    if(!file_exists($ini_files)){
        $this->output->writeln('<error>文件'.$ini_files.'不存在.</error>');
        return 1;
    }
    if(!is_readable($ini_files)){
        $this->output->writeln('<error>文件'.$ini_files.'没有读取权限.</error>');
        return 1;
    }
    if(!is_writable($ini_files)){
        $this->output->writeln('<error>文件'.$ini_files.'没有写入权限.</error>');
        return 1;
    }
    if (!$enable_dl){
        $fp = fopen($ini_files, 'r+');
        while (($buffer = fgets($fp, 4096)) !== false) {
            $buffer = str_replace(' ', '', $buffer);
            if(stripos($buffer, 'enable_dl=') === 0 || stripos($buffer, ';enable_dl=') === 0){
                fwrite($fp, 'enable_dl = On'.PHP_EOL);
                break;
            }
        }
        fclose($fp);
    }
    
    // 
    if(!extension_loaded('SADKExtension')){
        $so = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'extension'. DIRECTORY_SEPARATOR . 'linux';
        $so .= DIRECTORY_SEPARATOR . 'php' . PHP_MAJOR_VERSION. '.' .PHP_MINOR_VERSION;
        $extensionDir = $this->getPHPExtensionDir();
        if ($extensionDir === false){
            $this->output->writeln('<error>未找到扩展目录.</error>');
            return 1;
        }
        $extension_conf = $so . DIRECTORY_SEPARATOR . 'cfcalog.conf';
        $extension_so = $so . DIRECTORY_SEPARATOR . 'libSADKExtension.so';
        $target_conf = $extensionDir . DIRECTORY_SEPARATOR . 'cfcalog.conf';
        $target_so = $extensionDir . DIRECTORY_SEPARATOR . 'libSADKExtension.so';
        // 
        if(!file_exists($target_so)){
            $res = copy($extension_so, $target_so);
            if(!$res){
                $this->output->writeln('<error>扩展so文件安装失败.</error>');
                return 1;
            }
        }
        
        // if(!file_exists($target_conf)){
        //     $res = copy($extension_conf, $target_conf);
        //     if(!$res){
        //         $this->output->writeln('<error>扩展conf文件安装失败.</error>');
        //         return 1;
        //     }
        //     // 修改权限
        //     $res = chmod($target_conf, 0777);
        //     if(!$res){
        //         $this->output->writeln('<error>扩展conf权限修改失败,请手动修改'.$target_conf.'为777.</error>');
        //     }
        // }
        // 修改配置文件
        $fp = fopen($ini_files,'a');
        $res = fwrite($fp, PHP_EOL."extension = libSADKExtension.so".PHP_EOL);
        fclose($fp);
        if($res === false){
          $this->output->writeln('<error>extension = libSADKExtension.so 写入失败,请手动写入.</error>');
        }
    }
    $this->output->writeln('<fg=green>completed.</>');
    return 0;
  }

  protected function getPHPExtensionDir()
  {
    // 
    $ini_ext_dir = ini_get('extension_dir');
    return $ini_ext_dir;
  }

  protected function getMachineBit(){
    if (PHP_INT_SIZE === 4) {
      return 32;
    } elseif (PHP_INT_SIZE === 8){
      return 64;
    }
    return false;
  }
}
