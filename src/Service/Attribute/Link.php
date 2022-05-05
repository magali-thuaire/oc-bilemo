<?php

namespace App\Service\Attribute;

use Attribute;
use Symfony\Contracts\Service\Attribute\Required;

#[Attribute(Attribute::TARGET_CLASS)]
class Link
{
    #[Required]
    public string $name;

    #[Required]
    public string $route;

    public array $params = [];

    public function __construct(string $name, string $route, array $params = [])
    {
        $this->name = $name;
        $this->route = $route;
        $this->params = $params;
    }

}