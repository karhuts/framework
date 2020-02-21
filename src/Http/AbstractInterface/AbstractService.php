<?php
declare(strict_types=1);

namespace Karthus\Http\AbstractInterface;

use Karthus\Component\Singleton;
use Karthus\Http\Request;
use Karthus\Http\Response;

abstract class AbstractService {
    use Singleton;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    public function __construct(Request $request, Response $response) {
        $this->request  = $request;
        $this->response = $response;
    }

}
