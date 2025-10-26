<?php declare(strict_types=1);

namespace Lohres\RestService\Enums;

enum RequestMethods: string {
    case GET = "GET";
    case HEAD = "HEAD";
    case POST = "POST";
    case PUT = "PUT";
    case DELETE = "DELETE";
    case OPTIONS = "OPTIONS";
    case PATCH = "PATCH";
    case TRACE = "TRACE";
    case CONNECT = "CONNECT";
}
