<?php declare(strict_types=1);

namespace Lohres\RestService\Attributes;

use Attribute;

/**
 * Declares the endpoint path segment mapped to a method.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Url {
    /**
     * @param string $url Path segment appended after class-based prefix.
     */
    public function __construct(string $url) {}
}
