<?php

namespace susuzhao88\Zhongjin\Support\Encrypt;

use susuzhao88\Zhongjin\Exceptions\ConfigException;
use susuzhao88\Zhongjin\Exceptions\Exception;
use susuzhao88\Zhongjin\Support\Config;

final class Linux extends Contract
{
  /**
   * @description: 国密证书私钥签名
   * @param {*} $data
   * @return {string}
   */
  protected function sm2Sign($data)
  {
    $nResult = 0;
    $strLogCofigFilePath = $this->config->get('cfcalog_path');
    $nResult = cfca_initialize($strLogCofigFilePath);
    if (0 != $nResult) {
      throw new Exception("cfca_Initialize error:" . $nResult);
    }
    // 国密证书私钥
    $sm2_path = $this->config->get('sm2_path');
    // 国密证书私钥密码
    $sm2_password = $this->config->get('sm2_password');
    try {
      $strMsgPKCS7DetachedSignature = "";
      cfca_signData_PKCS1('SM2', $data, $sm2_path, $sm2_password, 'SM3', $strMsgPKCS7DetachedSignature);
      if (0 != $nResult) {
        throw new Exception("cfca_signData_PKCS1 error:" . $nResult);
      }
      $signature_bin = base64_decode($strMsgPKCS7DetachedSignature);
      $signature_hex = bin2hex($signature_bin);
      /* 
          调用本函数库中其它函数之后调用cfca_uninitialize()。
          如果需要在多线程环境下调用此函数库中的函数，cfca_uninitialize()需要在多线程结束之后调用。
          此函数只需要调用一次。
          */
      $nResult = cfca_uninitialize();
      if (0 != $nResult) {
        throw new Exception("cfca_uninitialize error:" . $nResult);
      }
    } catch (Exception $e) {
      cfca_uninitialize();
      throw new Exception("签名失败:" . $e->getMessage(), $e->getCode());
    }
    return $signature_hex;
  }

  /**
   * @description: 国密证书公钥验签
   * @param {*} $data
   * @param {*} $sign
   * @return {bool}
   */
  protected function sm2Verify($data, $sign)
  {
    $sm2_cer_path = $this->config->get('sm2_cer_path');
    if (!$sm2_cer_path || !file_exists($sm2_cer_path)) {
      throw new Exception("sm2_cer_path 不存在");
    }
    $nResult = 0;
    $strLogCofigFilePath = $this->config->get('cfcalog_path');
    $nResult = cfca_initialize($strLogCofigFilePath);
    if (0 != $nResult) {
      throw new Exception("cfca_Initialize error:" . $nResult);
    }
    $signature_hex = hex2bin($sign);
    $signature_base64 = base64_encode($signature_hex);
    $fp = fopen($sm2_cer_path, "r");
    $cert = fread($fp, 8192);
    fclose($fp);
    $cert = str_replace(["-----BEGIN CERTIFICATE-----", "-----END CERTIFICATE-----"], "", $cert);
    try {
      $result = cfca_verifyDataSignature_PKCS1("SM2", $data, $cert, "SM3", $signature_base64);
      if (0 != $result) {
        throw new Exception("cfca_verifyDataSignature_PKCS1 error:" . $result);
      }
      $nResult = cfca_uninitialize();
      if (0 != $nResult) {
        throw new Exception("\n cfca_uninitialize error:" . $nResult);
      }
    } catch (Exception $e) {
      cfca_uninitialize();
      throw new Exception("验签失败:" . $e->getMessage(), $e->getCode());
    }
    return $result == 0;
  }
}
