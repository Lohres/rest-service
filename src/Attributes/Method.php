<?php declare(strict_types=1);

namespace Lohres\RestService\Attributes;

use Attribute;

/**
 * Declares the HTTP method for an endpoint handler.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Method {
    /**
     * @param string $method HTTP method (for example GET, POST).
     */
    public function __construct(string $method) {}
}
