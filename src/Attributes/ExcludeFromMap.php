<?php declare(strict_types=1);

namespace Lohres\RestService\Attributes;

use Attribute;

/**
 * Excludes a public method from route map generation.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ExcludeFromMap {
    /**
     * @param string $excludeFromMap Marker argument; value is not used by runtime logic.
     */
    public function __construct(string $excludeFromMap = "excluded") {}
}
