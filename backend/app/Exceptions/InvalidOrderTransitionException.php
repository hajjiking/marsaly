<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderTransitionException extends Exception
{
    public function __construct(public readonly string $from, public readonly string $to)
    {
        parent::__construct(__('api.invalid_transition', ['from' => $from, 'to' => $to]));
    }

    public function render($request)
    {
        return \App\Support\ApiResponse::error(
            $this->getMessage(),
            422,
            ['status' => [__('api.invalid_transition', ['from' => $this->from, 'to' => $this->to])]],
        );
    }
}
