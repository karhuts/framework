<?php
declare(strict_types=1);
namespace Karthus\Http\Client;

class ContentType {
    // 常用POST提交请求头
    public const TEXT_XML               = 'text/xml';
    public const TEXT_JSON              = 'text/json';
    public const FORM_DATA              = 'multipart/form-data';
    public const APPLICATION_XML        = 'application/xml';
    public const APPLICATION_JSON       = 'application/json';
    public const X_WWW_FORM_URLENCODED  = 'application/x-www-form-urlencoded';
}
