<?php
namespace susuzhao88\Zhongjin\Support;

use Illuminate\Support\Arr;
use susuzhao88\Zhongjin\Exceptions\Exception;

class XML{
  /**
   * @var \DOMDocument
   */
  protected $xml;
  protected $origin;
  public function __construct($data)
  {
    $this->origin = $data;
    $this->buildXml();
    $this->xml->formatOutput = true;
  }

  public function buildXml(){
    $data = $this->origin;
    if(is_string($this->origin)){
      $xml = simplexml_load_string($this->origin);
      if($xml === false){
        $data = json_decode($this->origin, true);
        if(JSON_ERROR_NONE !== json_last_error()){
          throw new Exception("参数类型错误[json string,xml string ,array].");
        }
      }
      $this->xml =  (new \DOMDocument)->loadXML($this->origin);
      return $this;
    }
    // 
    if(is_array($data)){
      // xml
      $this->xml = new \DOMDocument();
      $this->array2xml($data, $this->xml);
      return $this;
    }

    throw new Exception("参数类型错误[json string,xml string ,array].");
  }

  protected function array2xml($array, \DOMNode $parent){
    foreach($array as $k=>$v){
      if(is_array($v)){
        // 关联数组
        if(Arr::isAssoc($v)){
          $node = $this->xml->createElement($k);
          $this->array2xml($v, $node);
        }else{
          // 索引数组
          foreach($v as $i=>$child){
            $this->array2xml([$k=>$child], $parent);
          }
        }
      }else{
        if($v){
          $node = $this->xml->createElement($k);
          $node->appendChild($this->xml->createTextNode($v));
        } 
      }
      $parent->appendChild($node);
    }
    return $this;
  }

  /**
   * @description: 
   * @return \DOMDocument
   */  
  public function getXml(){
    return $this->xml;
  }
  

  public function toString(){
    return $this->xml->saveXML();
  }
}