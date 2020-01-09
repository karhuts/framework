<?php
namespace Karthus\Service;

use Karthus\Logger\Logger;

class Api {
    protected $request    = null;
    protected $logger     = null;

    /***
     * Api constructor.
     *
     * @param Request|null $request
     * @param Logger|null  $logger
     */
    public function __construct(Request $request = null, Logger $logger = null) {
        $this->request = $request;
        $this->logger  = $logger;
    }
}
