<?php declare(strict_types=1);

namespace Tests;

use Lohres\RestService\Enums\HttpCodes;
use Lohres\RestService\Response;
use PHPUnit\Framework\TestCase;

final class ResponseAndHttpCodesTest extends TestCase
{
    public function testResponseStructureCanRepresentErrorPayload(): void
    {
        $response = new Response();
        $response->setSuccess(false);
        $response->setContent([
            'message' => HttpCodes::toString(HttpCodes::Forbidden->value),
            'code' => (string) HttpCodes::Forbidden->value,
        ]);

        self::assertFalse($response->getSuccess());
        self::assertSame('Forbidden', $response->getContent()['message']);
        self::assertSame('403', $response->getContent()['code']);
        self::assertSame(['success', 'content'], $response->getKeys());
    }

    public function testHttpCodesToStringReturnsKnownAndUnknownValues(): void
    {
        self::assertSame('Not Found', HttpCodes::toString(HttpCodes::NotFound->value));
        self::assertSame('Internal Server Error', HttpCodes::toString(HttpCodes::InternalServerError->value));
        self::assertSame('unknown', HttpCodes::toString(999));
    }
}
