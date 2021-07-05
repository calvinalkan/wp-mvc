<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Events;

    use BetterWpHooks\Traits\IsAction;
    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Session\Session;
    use WPMvc\Session\Contracts\SessionDriver;

    class Logout extends ApplicationEvent
    {

        use IsAction;

        /**
         * @var Session
         */
        public $session;

        /**
         * @var int
         */
        public $user_id;

        public function __construct(Session $session, int $user_id)
        {

            $this->session = $session;
            $this->user_id = $user_id;

            /**
             * Fires after a user is logged out.
             *
             * @since 1.5.0
             * @since 5.5.0 Added the `$user_id` parameter.
             *
             * @param int $user_id ID of the user that was logged out.
             */
            do_action( 'wp_logout', $this->session->userId() );


        }

    }