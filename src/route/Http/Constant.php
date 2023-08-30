<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  min@bluecity.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route\Http;

class Constant
{
    public const API_CODE_OK = 200;

    public const API_CODE_CREATED = 201;

    public const API_CODE_NO_CONTENT = 204;

    public const API_CODE_BAD_REQUEST = 400;

    public const API_CODE_UNAUTHORIZED = 401;

    public const API_CODE_PAYMENT_REQUIRED = 402;

    public const API_CODE_FORBIDDEN = 403;

    public const API_CODE_NOT_FOUND = 404;

    public const API_CODE_METHOD_NOT_ALLOWED = 405;

    public const API_CODE_GONE = 410;

    public const API_CODE_UNSUPPORTED_MEDIA_TYPE = 415;

    public const API_CODE_UNPROCESSABLE_ENTITY = 422;

    public const API_CODE_TOO_MANY_REQUESTS = 429;

    public const API_CODE_INTERNAL_SERVER_ERROR = 500;

    /**
     * @var array|string[]
     */
    public const MSG = [
        400 => '非法请求',
        401 => '验证失败,请重新登录',
        402 => '需付费',
        403 => '操作被禁止',
        404 => '未找到',
        405 => '请求的方法不支持',
        410 => '操作不被支持',
        415 => '请求错误',
        422 => '参数错误',
        429 => '操作过于频繁,请稍后再试',
        500 => '未知错误,请检查您的网络',
    ];

    /**
     * NOTE: Keep this in sync with the status code list
     * @var array|string[]
     */
    protected static array $statusMessage = [
        100 => 'Continue',            // StatusContinue
        101 => 'Switching Protocols', // StatusSwitchingProtocols
        102 => 'Processing',          // StatusProcessing
        103 => 'Early Hints',         // StatusEarlyHints

        200 => 'OK',                            // StatusOK
        201 => 'Created',                       // StatusCreated
        202 => 'Accepted',                      // StatusAccepted
        203 => 'Non-Authoritative Information', // StatusNonAuthoritativeInformation
        204 => 'No Content',                    // StatusNoContent
        205 => 'Reset Content',                 // StatusResetContent
        206 => 'Partial Content',               // StatusPartialContent
        207 => 'Multi-Status',                  // StatusMultiStatus
        208 => 'Already Reported',              // StatusAlreadyReported
        226 => 'IM Used',                       // StatusIMUsed

        300 => 'Multiple Choices',   // StatusMultipleChoices
        301 => 'Moved Permanently',  // StatusMovedPermanently
        302 => 'Found',              // StatusFound
        303 => 'See Other',          // StatusSeeOther
        304 => 'Not Modified',       // StatusNotModified
        305 => 'Use Proxy',          // StatusUseProxy
        306 => 'Switch Proxy',       // StatusSwitchProxy
        307 => 'Temporary Redirect', // StatusTemporaryRedirect
        308 => 'Permanent Redirect', // StatusPermanentRedirect

        400 => 'Bad Request',                     // StatusBadRequest
        401 => 'Unauthorized',                    // StatusUnauthorized
        402 => 'Payment Required',                // StatusPaymentRequired
        403 => 'Forbidden',                       // StatusForbidden
        404 => 'Not Found',                       // StatusNotFound
        405 => 'Method Not Allowed',              // StatusMethodNotAllowed
        406 => 'Not Acceptable',                  // StatusNotAcceptable
        407 => 'Proxy Authentication Required',   // StatusProxyAuthRequired
        408 => 'Request Timeout',                 // StatusRequestTimeout
        409 => 'Conflict',                        // StatusConflict
        410 => 'Gone',                            // StatusGone
        411 => 'Length Required',                 // StatusLengthRequired
        412 => 'Precondition Failed',             // StatusPreconditionFailed
        413 => 'Request Entity Too Large',        // StatusRequestEntityTooLarge
        414 => 'Request URI Too Long',            // StatusRequestURITooLong
        415 => 'Unsupported Media Type',          // StatusUnsupportedMediaType
        416 => 'Requested Range Not Satisfiable', // StatusRequestedRangeNotSatisfiable
        417 => 'Expectation Failed',              // StatusExpectationFailed
        418 => "I'm a teapot",                    // StatusTeapot
        421 => 'Misdirected Request',             // StatusMisdirectedRequest
        422 => 'Unprocessable Entity',            // StatusUnprocessableEntity
        423 => 'Locked',                          // StatusLocked
        424 => 'Failed Dependency',               // StatusFailedDependency
        425 => 'Too Early',                       // StatusTooEarly
        426 => 'Upgrade Required',                // StatusUpgradeRequired
        428 => 'Precondition Required',           // StatusPreconditionRequired
        429 => 'Too Many Requests',               // StatusTooManyRequests
        431 => 'Request Header Fields Too Large', // StatusRequestHeaderFieldsTooLarge
        451 => 'Unavailable For Legal Reasons',   // StatusUnavailableForLegalReasons

        500 => 'Internal Server Error',           // StatusInternalServerError
        501 => 'Not Implemented',                 // StatusNotImplemented
        502 => 'Bad Gateway',                     // StatusBadGateway
        503 => 'Service Unavailable',             // StatusServiceUnavailable
        504 => 'Gateway Timeout',                 // StatusGatewayTimeout
        505 => 'HTTP Version Not Supported',      // StatusHTTPVersionNotSupported
        506 => 'Variant Also Negotiates',         // StatusVariantAlsoNegotiates
        507 => 'Insufficient Storage',            // StatusInsufficientStorage
        508 => 'Loop Detected',                   // StatusLoopDetected
        510 => 'Not Extended',                    // StatusNotExtended
        511 => 'Network Authentication Required', // StatusNetworkAuthenticationRequired
    ];

    /**
     * MIME types were copied from https://github.com/nginx/nginx/blob/67d2a9541826ecd5db97d604f23460210fd3e517/conf/mime.types with the following updates:
     * - Use "application/xml" instead of "text/xml" as recommended per https://datatracker.ietf.org/doc/html/rfc7303#section-4.1
     * - Use "text/javascript" instead of "application/javascript" as recommended per https://www.rfc-editor.org/rfc/rfc9239#name-text-javascript
     * @var array|string[]
     */
    protected static array $mimeExtensions = [
        'html' => 'text/html',
        'htm' => 'text/html',
        'shtml' => 'text/html',
        'css' => 'text/css',
        'xml' => 'application/xml',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'atom' => 'application/atom+xml',
        'rss' => 'application/rss+xml',
        'mml' => 'text/mathml',
        'txt' => 'text/plain',
        'jad' => 'text/vnd.sun.j2me.app-descriptor',
        'wml' => 'text/vnd.wap.wml',
        'htc' => 'text/x-component',
        'avif' => 'image/avif',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'wbmp' => 'image/vnd.wap.wbmp',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'jng' => 'image/x-jng',
        'bmp' => 'image/x-ms-bmp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'jar' => 'application/java-archive',
        'war' => 'application/java-archive',
        'ear' => 'application/java-archive',
        'json' => 'application/json',
        'hqx' => 'application/mac-binhex40',
        'doc' => 'application/msword',
        'pdf' => 'application/pdf',
        'ps' => 'application/postscript',
        'eps' => 'application/postscript',
        'ai' => 'application/postscript',
        'rtf' => 'application/rtf',
        'm3u8' => 'application/vnd.apple.mpegurl',
        'kml' => 'application/vnd.google-earth.kml+xml',
        'kmz' => 'application/vnd.google-earth.kmz',
        'xls' => 'application/vnd.ms-excel',
        'eot' => 'application/vnd.ms-fontobject',
        'ppt' => 'application/vnd.ms-powerpoint',
        'odg' => 'application/vnd.oasis.opendocument.graphics',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'wmlc' => 'application/vnd.wap.wmlc',
        'wasm' => 'application/wasm',
        '7z' => 'application/x-7z-compressed',
        'cco' => 'application/x-cocoa',
        'jardiff' => 'application/x-java-archive-diff',
        'jnlp' => 'application/x-java-jnlp-file',
        'run' => 'application/x-makeself',
        'pl' => 'application/x-perl',
        'pm' => 'application/x-perl',
        'prc' => 'application/x-pilot',
        'pdb' => 'application/x-pilot',
        'rar' => 'application/x-rar-compressed',
        'rpm' => 'application/x-redhat-package-manager',
        'sea' => 'application/x-sea',
        'swf' => 'application/x-shockwave-flash',
        'sit' => 'application/x-stuffit',
        'tcl' => 'application/x-tcl',
        'tk' => 'application/x-tcl',
        'der' => 'application/x-x509-ca-cert',
        'pem' => 'application/x-x509-ca-cert',
        'crt' => 'application/x-x509-ca-cert',
        'xpi' => 'application/x-xpinstall',
        'xhtml' => 'application/xhtml+xml',
        'xspf' => 'application/xspf+xml',
        'zip' => 'application/zip',
        'bin' => 'application/octet-stream',
        'exe' => 'application/octet-stream',
        'dll' => 'application/octet-stream',
        'deb' => 'application/octet-stream',
        'dmg' => 'application/octet-stream',
        'iso' => 'application/octet-stream',
        'img' => 'application/octet-stream',
        'msi' => 'application/octet-stream',
        'msp' => 'application/octet-stream',
        'msm' => 'application/octet-stream',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'kar' => 'audio/midi',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/x-m4a',
        'ra' => 'audio/x-realaudio',
        '3gpp' => 'video/3gpp',
        '3gp' => 'video/3gpp',
        'ts' => 'video/mp2t',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'webm' => 'video/webm',
        'flv' => 'video/x-flv',
        'm4v' => 'video/x-m4v',
        'mng' => 'video/x-mng',
        'asx' => 'video/x-ms-asf',
        'asf' => 'video/x-ms-asf',
        'wmv' => 'video/x-ms-wmv',
        'avi' => 'video/x-msvideo',
    ];

    /**
     * returns the correct message for the provided HTTP statuscode
     * @param int $status
     * @return string
     */
    public static function statusMessage(int $status): string
    {
        return static::$statusMessage[$status] ?? '';
    }
}
