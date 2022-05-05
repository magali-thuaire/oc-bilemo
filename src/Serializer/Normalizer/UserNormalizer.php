<?php

namespace App\Serializer\Normalizer;

use App\Entity\User;
use App\Service\Attribute\LinkResolver;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class UserNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
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
        if (!$object instanceof User) {
            return [];
        }

        $data = $this->normalizer->normalize($object, $format, $context);

        if (array_key_exists('password', $data)) {
            $data['plainPassword'] = $data['password'];
            unset($data['password']);
        }

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
        return $data instanceof User;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
