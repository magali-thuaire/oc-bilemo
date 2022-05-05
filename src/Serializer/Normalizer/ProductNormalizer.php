<?php

namespace App\Serializer\Normalizer;

use App\Entity\Product;
use App\Api\Attribute\LinkResolver;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ProductNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private ObjectNormalizer $normalizer;
    private LinkResolver $linkResolver;

    public function __construct(
        ObjectNormalizer $normalizer,
        LinkResolver $linkResolver
    )
    {
        $this->normalizer = $normalizer;
        $this->linkResolver = $linkResolver;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        if (!$object instanceof Product) {
            return [];
        }

        $data = $this->normalizer->normalize($object, $format, $context);

        if ($object->getId()) {
            $data['_links'] = $this->linkResolver->resolve($object);
        }

        return array_filter($data, function ($property) {
            if (!$property) {
                return null;
            }
            return $property;
        });
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Product;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
