<?php
declare(strict_types=1);

namespace Karthus\Contract\Able;

interface Sendable {
    /**
     * Send the response.
     */
    public function send();
}
