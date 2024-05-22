<?php
namespace susuzhao88\Zhongjin\Kernel\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;

class Response extends Psr7Response{

  /**
   * @return string
   */
  public function getBodyContents()
  {
      $this->getBody()->rewind();
      $contents = $this->getBody()->getContents();
      $this->getBody()->rewind();

      return $contents;
  }

  /**
   * @description: 
   * @param ResponseInterface $response
   * @return \susuzhao88\Zhongjin\Kernel\Http\Response
   */  
  public static function buildPsrResponse(ResponseInterface $response){
    return new static(
      $response->getStatusCode(),
      $response->getHeaders(),
      $response->getBody(),
      $response->getProtocolVersion(),
      $response->getReasonPhrase()
    );
  }
}