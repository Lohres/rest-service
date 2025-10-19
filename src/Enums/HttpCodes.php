<?php declare(strict_types=1);

namespace Lohres\RestService\Enums;

use Exception;

enum HttpCodes: int {
    case Continue = 100;
    case SwitchingProtocols = 101;
    case Processing = 102;
    case EarlyHints = 103;
    case OK = 200;
    case Created = 201;
    case Accepted = 202;
    case NonAuthoritativeInformation = 203;
    case NoContent = 204;
    case ResetContent = 205;
    case PartialContent = 206;
    case MultiStatus = 207;
    case AlreadyReported = 208;
    case IMUsed = 226;
    case MultipleChoices = 300;
    case MovedPermanently = 301;
    case Found = 302;
    case SeeOther = 303;
    case NotModified = 304;
    case UseProxy = 305;
    case Unused = 306;
    case TemporaryRedirect = 307;
    case PermanentRedirect = 308;
    case BadRequest = 400;
    case Unauthorized = 401;
    case PaymentRequired = 402;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case NotAcceptable = 406;
    case ProxyAuthenticationRequired = 407;
    case RequestTimeout = 408;
    case Conflict = 409;
    case Gone = 410;
    case LengthRequired = 411;
    case PreconditionFailed = 412;
    case ContentTooLarge = 413;
    case URITooLong = 414;
    case UnsupportedMediaType = 415;
    case RangeNotSatisfiable = 416;
    case ExpectationFailed = 417;
    case ImATeapot = 418;
    case MisdirectedRequest = 421;
    case UnprocessableContent = 422;
    case Locked = 423;
    case FailedDependency = 424;
    case TooEarly = 425;
    case UpgradeRequired = 426;
    case PreconditionRequired = 428;
    case TooManyRequests = 429;
    case RequestHeaderFieldsTooLarge = 431;
    case UnavailableForLegalReasons = 451;
    case InternalServerError = 500;
    case NotImplemented = 501;
    case BadGateway = 502;
    case ServiceUnavailable = 503;
    case GatewayTimeout = 504;
    case HTTPVersionNotSupported = 505;
    case VariantAlsoNegotiates = 506;
    case InsufficientStorage = 507;
    case LoopDetected = 508;
    case NotExtended = 510;
    case NetworkAuthenticationRequired = 511;



    /**
     * @param int $code
     * @return string
     */
    public static function toString(int $code): string
    {
        return match ($code) {
            self::Continue->value => "Continue",
            self::SwitchingProtocols->value => "Switching Protocols",
            self::Processing->value => "Processing",
            self::EarlyHints->value => "Early Hints",
            self::OK->value => "OK",
            self::Created->value => "Created",
            self::Accepted->value => "Accepted",
            self::NonAuthoritativeInformation->value => "Non-Authoritative Information",
            self::NoContent->value => "No Content",
            self::ResetContent->value => "Reset Content",
            self::PartialContent->value => "Partial Content",
            self::MultiStatus->value => "Multi-Status",
            self::AlreadyReported->value => "Already Reported",
            self::IMUsed->value => "IM Used",
            self::MultipleChoices->value => "Multiple Choices",
            self::MovedPermanently->value => "Moved Permanently",
            self::Found->value => "Found",
            self::SeeOther->value => "See Other",
            self::NotModified->value => "Not Modified",
            self::UseProxy->value => "Use Proxy",
            self::Unused->value => "unused",
            self::TemporaryRedirect->value => "Temporary Redirect",
            self::PermanentRedirect->value => "Permanent Redirect",
            self::BadRequest->value => "Bad Request",
            self::Unauthorized->value => "Unauthorized",
            self::PaymentRequired->value => "Payment Required",
            self::Forbidden->value => "Forbidden",
            self::NotFound->value => "Not Found",
            self::MethodNotAllowed->value => "Method Not Allowed",
            self::NotAcceptable->value => "Not Acceptable",
            self::ProxyAuthenticationRequired->value => "Proxy Authentication Required",
            self::RequestTimeout->value => "Request Timeout",
            self::Conflict->value => "Conflict",
            self::Gone->value => "Gone",
            self::LengthRequired->value => "Length Required",
            self::PreconditionFailed->value => "Precondition Failed",
            self::ContentTooLarge->value => "Content Too Large",
            self::URITooLong->value => "URI Too Long",
            self::UnsupportedMediaType->value => "Unsupported Media Type",
            self::RangeNotSatisfiable->value => "Range Not Satisfiable",
            self::ExpectationFailed->value => "Expectation Failed",
            self::ImATeapot->value => "I'm a teapot",
            self::MisdirectedRequest->value => "Misdirected Request",
            self::Locked->value => "Locked",
            self::FailedDependency->value => "Failed Dependency",
            self::TooEarly->value => "Too Early",
            self::UpgradeRequired->value => "Upgrade Required",
            self::PreconditionRequired->value => "Precondition Required",
            self::TooManyRequests->value => "Too Many Requests",
            self::RequestHeaderFieldsTooLarge->value => "Request Header Fields Too Large",
            self::UnavailableForLegalReasons->value => "Unavailable For Legal Reasons",
            self::InternalServerError->value => "Internal Server Error",
            self::NotImplemented->value => "Not Implemented",
            self::BadGateway->value => "Bad Gateway",
            self::ServiceUnavailable->value => "Service Unavailable",
            self::GatewayTimeout->value => "Gateway Timeout",
            self::HTTPVersionNotSupported->value => "HTTP Version Not Supported",
            self::VariantAlsoNegotiates->value => "Variant AlsoNegotiates",
            self::InsufficientStorage->value => "Insufficient Storage",
            self::LoopDetected->value => "Loop Detected",
            self::NotExtended->value => "Not Extended",
            self::NetworkAuthenticationRequired->value => "Network Authentication Required",
            default => "unknown"
        };
    }
}
