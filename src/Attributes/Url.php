<?php declare(strict_types=1);

namespace Lohres\RestService\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Url {
    public function __construct(string $url) {}
}
