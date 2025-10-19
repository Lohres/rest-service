<?php declare(strict_types=1);

namespace Lohres\RestService\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Method {
    public function __construct(string $method) {}
}

