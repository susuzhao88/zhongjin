<?php
namespace susuzhao88\Zhongjin\Gateways;

use Illuminate\Support\Arr;

class Trade4600 extends Base{
  protected function getUrl(){

    return $this->sandbox() ? 'https://test.cpcn.com.cn/Gateway4File/InterfaceII' : 'https://www.china-clearing.com/Gateway4File/InterfaceII' ;
  }

  protected function handleParams($params){
    return [
      'Request' =>  [
        'Head'  =>  [
          'TxCode'  =>  '4600',
        ],
        'Body'  =>  [
          'InstitutionID' =>  Arr::get($params, 'InstitutionID', $this->app->config->get('InstitutionID')),
          'TxSN'  =>  Arr::get($params, 'TxSN'),
          'BusinessType'  =>  Arr::get($params, 'BusinessType'),
          'OCRFlag' =>  Arr::get($params, 'OCRFlag'),
          'MainUserID'  =>  Arr::get($params, 'MainUserID'),
          'UserID'  =>  Arr::get($params, 'UserID'),
          'ImageInfo' =>  Arr::get($params, 'ImageInfo'),
        ],
      ]
    ];

  }
}