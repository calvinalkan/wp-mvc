<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Support\Arr;

    class Request implements ServerRequestInterface
    {
        use ImplementsPsr7Request;
        use InspectsRequest;

        public function __construct(ServerRequestInterface $psr_request)
        {

            $this->psr_request = $psr_request;

        }

        public function path() : string
        {
            return $this->getUri()->getPath();
        }

        public function url( bool $trailing_slash = false ) : string
        {
            $url = trim(preg_replace('/\?.*/', '', $this->getUri()), '/');

            if ( $trailing_slash ) {

                $url = $url . '/';

            }

            return $url;

        }

        public function fullUrl(bool $trailing_slash = false) : string
        {

            $full_url =  trim($this->getUri()->__toString());

            return  ($trailing_slash) ? $full_url . '/' : $full_url;

        }

        public function withType ( string $type ) {

            return $this->withAttribute('type', $type);

        }

        public function getType() : string
        {

            return $this->getAttribute('type', '');

        }

        public function query(string $name = null , $default = null )
        {

            if ( ! $name ) {

                return $this->getQueryParams() ?? [];

            }

            return Arr::get($this->getQueryParams(), $name, $default);

        }

        public function parsedBody(string $name = null , $default = null )
        {

            if ( ! $name ) {

                return $this->getParsedBody() ?? [];

            }

            return Arr::get($this->getParsedBody(), $name, $default);

        }


    }