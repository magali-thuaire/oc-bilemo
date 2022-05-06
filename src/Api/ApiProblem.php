<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Api;

use http\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * A wrapper for holding data to be used for an application/problem+json response
 */
class ApiProblem
{
    public const TYPE_VALIDATION_ERROR = 'validation_errors';
    public const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';
    public const TYPE_UNPROCESSABLE_ENTITY = 'unique_entity_error';

    public static array $titles = [
        self::TYPE_VALIDATION_ERROR => 'There was validation errors',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
        self::TYPE_UNPROCESSABLE_ENTITY => 'This entity already exists in the application',
    ];

    private int $statusCode;

    private string $type;

    private string $title;

    private array $extraData = [];

    public function __construct(int $statusCode, ?string $type = null)
    {
        $this->statusCode = $statusCode;

        if (null === $type) {
            $type = 'about:blank';
            $title = Response::$statusTexts[$statusCode] ?? 'Unknown status code :(' ;
        } else {
            if (!isset(self::$titles[$type])) {
                throw new InvalidArgumentException('No title for type' . $type);
            }
            $title = self::$titles[$type];
        }

        $this->type = $type;
        $this->title = $title;
    }

    public function toArray(): array
    {
        return array_merge(
            $this->extraData,
            array(
                'status' => $this->statusCode,
                'type' => $this->type,
                'title' => $this->title,
            )
        );
    }

    public function set($name, $value): void
    {
        $this->extraData[$name] = $value;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
