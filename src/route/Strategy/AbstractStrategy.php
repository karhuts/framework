<?php
declare(strict_types=1);
namespace karthus\route\Strategy;


use Psr\Http\Message\ResponseInterface;

abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * @var array
     */
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
