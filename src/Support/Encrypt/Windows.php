<?php
namespace susuzhao88\Zhongjin\Support\Encrypt;

use COM;
use Exception as GlobalException;
use susuzhao88\Zhongjin\Exceptions\ConfigException;
use susuzhao88\Zhongjin\Exceptions\Exception;
use susuzhao88\Zhongjin\Support\Config;

final class Windows extends Contract{
  protected function sm2Sign($data)
  {
    // 国密证书私钥
    $sm2_path = $this->config->get('sm2_path');
    // 国密证书私钥密码
    $sm2_password = $this->config->get('sm2_password');
    try{
      $encrypt = new COM("CryptoKit.CryptoAgent.Server.Standard.x64.1", NULL, CP_UTF8);
      $sign = $encrypt->SignData_PKCS1("SM2",$data, $sm2_path, $sm2_password, "SHA-1");
      
      $signature_bin=base64_decode($sign);
      $signature_hex=bin2hex($signature_bin);

      return $signature_hex;
    }catch(GlobalException $e){
      $error = $encrypt->GetLastErrorDesc();
      if($error){
        throw new Exception("签名失败:".$error);
      }
      throw new Exception($e->getMessage());
    }
  }

  protected function sm2Verify($data, $sign)
  {
    $sm2_cer_path = $this->config->get('sm2_cer_path');
    if (!$sm2_cer_path || !file_exists($sm2_cer_path)) {
      throw new Exception("sm2_cer_path 不存在");
    }
    $fp = fopen($sm2_cer_path, "r");
    $cert = fread($fp, 8192);
    fclose($fp);
    $cert = str_replace(["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----"], "", $cert);
    try{
      $encrypt = new COM("CryptoKit.CryptoAgent.Server.Standard.x64.1", NULL, CP_UTF8);
      $signature_hex=hex2bin($sign);
      $signature_base64=base64_encode($signature_hex);
      $res = $encrypt->VerifyDataSignature_PKCS1("SM2", $data, trim($cert), "SM3", $signature_base64);
      return !is_null($res);
    }catch(GlobalException $e){
      $error = $encrypt->GetLastErrorDesc();
      if($error){
        throw new Exception("验签失败:".$error);
      }
      throw new Exception($e->getMessage());
    }
  }
}