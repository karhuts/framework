<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\route\Strategy;

use Psr\Http\Message\ResponseInterface;

abstract class AbstractStrategy implements StrategyInterface
{
    protected array $responseDecorators = [];

    public function addResponseDecorator(callable $decorator): StrategyInterface
    {
        $this->responseDecorators[] = $decorator;
        return $this;
    }

    protected function decorateResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->responseDecorators as $decorator) {
            $response = $decorator($response);
        }

        return $response;
    }
}
