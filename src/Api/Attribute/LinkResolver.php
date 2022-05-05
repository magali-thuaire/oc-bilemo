<?php

namespace App\Api\Attribute;

use ReflectionClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LinkResolver
{
    private array $links = [];
    private UrlGeneratorInterface $urlGenerator;
    private ExpressionLanguage $expressionLanguage;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function resolve(object $object): array
    {
        $reflexionClass = new ReflectionClass($object);
        $attributes = $reflexionClass->getAttributes(Link::class);

        foreach ($attributes as $attribute) {
            $attribute = $attribute->getArguments();
            $uri = $this->urlGenerator->generate(
                $attribute['route'],
                $this->resolveParams($attribute['params'], $object)
            );

            $this->links[$attribute['name']] = $uri;
        }

        return $this->links;
    }

    private function resolveParams(array $params, $object): array
    {
        foreach ($params as $key => $param) {
            $params[$key] = $this->expressionLanguage->evaluate($param, [
                'object' => $object
            ]);
        }

        return $params;
    }
}