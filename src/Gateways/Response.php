<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways;

class Response
{
    protected bool $success = false;
    protected array $data = [];
    protected string $checkoutUrl = '';

    protected string $error = '';

    public function __construct(bool $success = false, array $data = [], string $checkoutUrl = '')
    {
        $this->success = $success;
        $this->data = $data;
        $this->checkoutUrl = $checkoutUrl;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function checkoutUrl(): string
    {
        return $this->checkoutUrl;
    }

    public function error(string $errorMessage = '')
    {
        if ($errorMessage !== '') {
            $this->error = $errorMessage;

            return $this;
        }

        return $this->error;
    }
}
