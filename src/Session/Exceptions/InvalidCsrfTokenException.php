<?php


    declare(strict_types = 1);


    namespace WPMvc\Session\Exceptions;

    use Throwable;
    use WPMvc\ExceptionHandling\Exceptions\HttpException;

    class InvalidCsrfTokenException extends HttpException
    {

        public function __construct(?string $message_for_humans = 'The Link you followed expired.', Throwable $previous = null, ?int $code = 0)
        {

            parent::__construct(419, $message_for_humans, $previous, $code);

        }

    }