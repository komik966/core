<?php

declare(strict_types=1);

namespace Swagger\DTO;


interface OperationInterface
{
    public function getPath(): string;

    public function getMethod(): string;
}
