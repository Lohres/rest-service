<?php

declare(strict_types=1);

namespace Lohres\RestService;

use DateTimeImmutable;
use InvalidArgumentException;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use JsonException;
use Lcobucci\Clock\FrozenClock;
use Lohres\LogHelper\LogHelper;
use Lohres\RestService\Enums\HttpCodes;
use RuntimeException;
use Throwable;

/**
 * Class JwtHelper
 * Helper class for JWT verification.
 * @package Lohres\RestService
 */
class JwtHelper
{
    /**
     * @param JWS $jws
     * @return array|bool
     * @throws RuntimeException
     */
    public static function checkClaim(JWS $jws): array|bool
    {
        self::checkConfig();
        try {
            $clock = new FrozenClock(now: new DateTimeImmutable(datetime: "now"));
            $claims = json_decode(json: $jws->getPayload(), associative: true, flags: JSON_THROW_ON_ERROR);
            $claimCheckerManager = new ClaimCheckerManager(checkers: [
                new IssuedAtChecker(clock: $clock),
                new NotBeforeChecker(clock: $clock),
                new ExpirationTimeChecker(clock: $clock),
                new AudienceChecker(audience: LOHRES_APP_NAME)
            ]);
            return $claimCheckerManager->check(claims: $claims, mandatoryClaims: ["iss", "sub", "aud"]);
        } catch (Throwable $exception) {
            LogHelper::getLogger(name: "jwt", level: LOHRES_LOG_LEVEL)->error(
                message: $exception->getMessage(),
                context: [$exception->getTrace()]
            );
        }
        return false;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    public static function checkConfig(): void
    {
        if (
            !defined(constant_name: "LOHRES_APP_NAME") ||
            !defined(constant_name: "LOHRES_KEYS_PATH") ||
            !defined(constant_name: "LOHRES_LOG_LEVEL")
        ) {
            throw new RuntimeException(message: "config for jwt invalid!");
        }
    }

    /**
     * @param JWS $jws
     * @return bool
     */
    public static function checkHeader(JWS $jws): bool
    {
        try {
            $headerCheckerManager = new HeaderCheckerManager(
                checkers: [new AlgorithmChecker(supportedAlgorithms: ["HS256"])],
                tokenTypes: [new JWSTokenSupport()]
            );
            $headerCheckerManager->check(jwt: $jws, index: 0);
            return true;
        } catch (Throwable $exception) {
            LogHelper::getLogger(name: "jwt", level: LOHRES_LOG_LEVEL)->error(
                message: $exception->getMessage(),
                context: [$exception->getTrace()]
            );
        }
        return false;
    }

    /**
     * @param string $token
     * @return array
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws RuntimeException
     */
    public static function checkToken(string $token): array
    {
        $jws = self::getTokenData(token: $token);
        if (
            !self::checkHeader(jws: $jws) ||
            !self::verifyToken(jws: $jws) ||
            is_bool(value: self::checkClaim(jws: $jws))
        ) {
            throw new RuntimeException(
                message: HttpCodes::toString(HttpCodes::Forbidden->value),
                code: HttpCodes::Forbidden->value
            );
        }
        return json_decode(json: $jws->getPayload(), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $token
     * @return JWS
     * @throws InvalidArgumentException
     */
    public static function getTokenData(string $token): JWS
    {
        return new JWSSerializerManager(serializers: [new CompactSerializer()])->unserialize(input: $token);
    }

    /**
     * @param JWS $jws
     * @return bool
     * @throws JsonException
     * @throws RuntimeException
     */
    public static function verifyToken(JWS $jws): bool
    {
        self::checkConfig();
        $jwk = JWK::createFromJson(
            json: file_get_contents(filename: LOHRES_KEYS_PATH . "/" . LOHRES_APP_NAME . ".cache")
        );
        return new JWSVerifier(
            signatureAlgorithmManager: new AlgorithmManager(algorithms: [new HS256()])
        )->verifyWithKey(jws: $jws, jwk: $jwk, signature: 0);
    }
}