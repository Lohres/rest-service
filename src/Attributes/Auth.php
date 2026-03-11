<?php declare(strict_types=1);

namespace Lohres\RestService\Attributes;

use Attribute;

/**
 * Marks whether an endpoint requires authentication.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Auth {
    /**
     * @param bool $auth True when endpoint auth check is required.
     */
    public function __construct(bool $auth) {}
}
