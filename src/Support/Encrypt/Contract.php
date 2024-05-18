<?php
namespace susuzhao88\Zhongjin\Support\Encrypt;

use susuzhao88\Zhongjin\Exceptions\ConfigException;
use susuzhao88\Zhongjin\Exceptions\Exception;
use susuzhao88\Zhongjin\Kernel\Config;

abstract class Contract{
  protected $config;
  /**
   * @description: 
   * @param {array} $config [certificate=>1表示证书使用的国密证书，2为国际证书 , cfcalog_path=>cfcalog.conf文件路径 , sm2_path=>xx.sm2文件路径 , pfx_path=>xx.pfx文件路径, sm2_cer_path=>xx_gm.cer文件路径 , pfx_cer_path=>xxx.cer文件路径 , sign_algo=>国际签名算法 1.OPENSSL_ALGO_SHA1 7.OPENSSL_ALGO_SHA256]
   * @return {*}
   */  
  public function __construct(array $config)
  {
    $this->config = new Config($config);
    $this->checkConfig();
  }

  protected function checkConfig()
  {
    // 1 or 2 (1表示证书使用的国密证书，2为国际证书)
    $certificate = $this->config->get('certificate', 1);
    if ($certificate != 1 && $certificate != 2) throw new ConfigException("certificate 必须是1或2");
    if ($certificate == 1) {
      $strLogCofigFilePath = $this->config->get('cfcalog_path');
      if (!$strLogCofigFilePath || !file_exists($strLogCofigFilePath)) {
        throw new ConfigException("cfcalog_path 不存在");
      }
      if (!is_writable($strLogCofigFilePath) || is_readable($strLogCofigFilePath)) {
        throw new ConfigException("cfcalog_path 文件必须要有读写权限");
      }
      $sm2_path = $this->config->get('sm2_path');
      if (!$sm2_path || !file_exists($sm2_path)) {
        throw new ConfigException("sm2_path 不存在");
      }
    } else {
      $pfx_path = $this->config->get('pfx_path');
      if (!$pfx_path || !file_exists($pfx_path)) {
        throw new ConfigException("pfx_path 不存在");
      }

      $sign_algo = $this->config->get('sign_algo', OPENSSL_ALGO_SHA1);

      if (!in_array($sign_algo, [OPENSSL_ALGO_SHA1, OPENSSL_ALGO_SHA256])) {
        throw new ConfigException("sign_algo 错误,可选 " . OPENSSL_ALGO_SHA1 . "," . OPENSSL_ALGO_SHA256);
      }
    }
  }

  /**
   * @description: 私钥签名
   * @param {*} $data
   * @return {*}
   */
  public function sign($data)
  {
    // 1 or 2 (1表示证书使用的国密证书，2为国际证书)
    $certificate = $this->config->get('certificate', 1);
    // 国密证书
    if ($certificate == 1) {
      return $this->sm2Sign($data);
    }
    return $this->pfxSign($data);
  }

  /**
   * @description: 国密证书私钥签名
   * @param {*} $data
   * @return {string}
   */
  protected abstract function sm2Sign($data);

  /**
   * @description: 国际证书私钥签名
   * @param {*} $data
   * @return {string}
   */
  protected function pfxSign($data)
  {
    // 国际证书私钥
    $pfx_path = $this->config->get('pfx_path');
    // 私钥密码
    $pfx_password = $this->config->get('pfx_password');
    $fp = fopen($pfx_path, 'r');
    $p12buf = fread($fp, filesize($pfx_path));
    fclose($fp);
    openssl_pkcs12_read($p12buf, $p12cert, $pfx_password);

    $pkey_id = $p12cert["pkey"];
    $binary_signature = "";
    // 签名算法
    $sign_algo = $this->config->get('sign_algo', OPENSSL_ALGO_SHA1);

    openssl_sign($data, $binary_signature, $pkey_id, $sign_algo);

    return bin2hex($binary_signature);
  }

  /**
   * @description: 公钥验签
   * @param {string} $data
   * @param {string} $sign
   * @return {bool}
   */
  public function verify($data, $sign)
  {
    // 1 or 2 (1表示证书使用的国密证书，2为国际证书)
    $certificate = $this->config->get('certificate', 1);
    // 国密证书
    if ($certificate == 1) {
      return $this->sm2Verify($data, $sign);
    }
    return $this->pfxVerify($data, $sign);
  }
  /**
   * @description: 国密证书公钥验签
   * @param {*} $data
   * @param {*} $sign
   * @return {bool}
   */
  protected abstract function sm2Verify($data, $sign);

  /**
   * @description: 国际证书公钥验签
   * @param {*} $data
   * @param {*} $sign
   * @return {bool}
   */
  protected function pfxVerify($data, $sign)
  {
    $pfx_cer_path = $this->config->get('pfx_cer_path');
    if (!$pfx_cer_path || !file_exists($pfx_cer_path)) {
      throw new Exception("pfx_cer_path 不存在");
    }
    $fp = fopen($pfx_cer_path, "r");
    $cert = fread($fp, 8192);
    fclose($fp);
    $binary_signature = pack("H" . strlen($sign), $sign);
    // 签名算法
    $sign_algo = $this->config->get('sign_algo', OPENSSL_ALGO_SHA1);
    $mapper = [OPENSSL_ALGO_SHA1  =>  OPENSSL_ALGO_SHA1, OPENSSL_ALGO_SHA256  =>  OPENSSL_ALGO_SHA256];
    return openssl_verify($data, $binary_signature, $cert, $mapper[$sign_algo]);
  }
}