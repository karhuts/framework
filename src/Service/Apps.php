<?php
declare(strict_types=1);
namespace Karthus\Service;

use Karthus\Logger\Logger;

/**
 * Class Apps
 *
 * @package Service
 */
abstract class Apps implements AppsBasic {
    /**
     * @var array 参数
     */
    protected $params = [];

    /**
     * @var Logger;
     */
    protected $logger = null;

    /**
     * @var Request
     */
    protected $request= null;
    protected $body   = '';

    /***
     * 初始化.
     */
    public function init() {
        $this->request      = Request::initRequest();
        $this->params       = $this->request->getParams();
        $this->body         = $this->request->getBody();
        $this->logger       = new Logger($this->request, $this->request->getLoggerDir());
        $this->api          = new Api($this->request, $this->logger);
    }

    /***
     * @param int        $code
     * @param string     $message
     * @param array|null $data
     * @return array
     */
    public function packagingHttpResponse(int $code = 200, string $message = '', ?array $data = []): array{
        return [
            'code'      => $code,
            'message'   => $message,
            'data'      => $data,
        ];
    }

    public function packagingHttpContent(int $code = 200, string $contents = ''): array{}

    /**
     * 鉴权
     * 如果接口需要进行权限验证，就从这里开始好了，Service\Apps已经抽象出一个 authentication
     *
     * @return mixed
     */
    abstract function authentication();

    /***
     * Class 执行完毕之后必须调用的方法
     * 默认已实现，各个接口如需要，请自己继承后实现
     *
     * @return mixed
     */
    public function done() :void {
        $endTime= microtime(true);
        $spend  = $endTime - $this->request->getRequestTime();
        $spend  = round($spend, 2);
        $this->logger->success("Run Ok[{$spend}s]!!!");
    }

    /***
     * 必须要有一个默认的方法
     * 默认执行的ACTION，Service\Apps中已抽象出一个 execute 所有继承该类的都需要实现execute
     *
     * @return mixed
     */
    abstract function execute();
}
