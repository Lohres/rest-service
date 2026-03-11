<?php declare(strict_types=1);

namespace Lohres\RestService;

use JsonSerializable;

/**
 * Class Response
 * Template class for structured json response
 * @package Lohres\RestService
 */
class Response implements JsonSerializable
{
    /**
     * @var array{success: bool, debug?: string, content?: mixed}
     */
    private array $return;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->setSuccess(value: false);
    }

    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->return["success"];
    }

    /**
     * @param bool $value
     * @return void
     */
    public function setSuccess(bool $value): void
    {
        $this->return["success"] = $value;
    }

    /**
     * @return string
     */
    public function getDebug(): string
    {
        return $this->return["debug"];
    }

    /**
     * @param string $message
     * @return void
     */
    public function setDebug(string $message): void
    {
        $this->return["debug"] = $message;
    }

    /**
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->return["content"];
    }

    /**
     * @param mixed $content
     * @return void
     */
    public function setContent(mixed $content): void
    {
        $this->return["content"] = $content;
    }

    /**
     * @return list<string>
     */
    public function getKeys(): array
    {
        return array_keys(array: $this->return);
    }

    /**
     * @return array{success: bool, debug?: string, content?: mixed}
     */
    public function jsonSerialize(): array
    {
        return $this->return;
    }
}
