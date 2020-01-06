<?php
namespace Karthus\Service;

use Karthus\Logger\Logger;

class Api {
    protected $request    = null;
    protected $logger     = null;

    /**
     * Api constructor.
     *
     * @param Request $request
     * @param Logger  $logger
     */
    public function __construct(Request $request, Logger $logger) {
        $this->request = $request;
        $this->logger  = $logger;
    }
}
