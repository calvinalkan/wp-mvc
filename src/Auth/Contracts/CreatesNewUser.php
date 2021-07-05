<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Contracts;


    use WPMvc\Http\Psr7\Request;

    interface CreatesNewUser
    {

        /**
         *
         * Validate and create a new WP_User for the given request.
         *
         * @param  Request  $request
         *
         * @return int The new users id.
         */
        public function create(Request $request) :int;


    }