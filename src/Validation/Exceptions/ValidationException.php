<?php


    declare(strict_types = 1);


    namespace WPMvc\Validation\Exceptions;


    use Illuminate\Support\MessageBag;
    use Throwable;
    use WPMvc\ExceptionHandling\Exceptions\HttpException;

    class ValidationException extends HttpException
    {

        /**
         * @var MessageBag
         */
        private $messages;

        /**
         * @var array
         */
        private $errors;

        /**
         * @var string
         */
        private $message_bag_name;

        public function __construct(array $errors, ?string $message_for_humans = 'We could not process your request.', int $status = 400, Throwable $previous = null, ?int $code = 0)
       {

           parent::__construct($status, $message_for_humans, $previous, $code);

           $this->errors = $errors;

       }

        public static function withMessages(array $messages, string $message_for_humans = 'We could not process your request.' , int $status = 400) : ValidationException
        {
            $bag = new MessageBag($messages);
            $e = new static($messages,$message_for_humans, $status);
            $e->setMessageBag($bag);

            return $e;

        }

        public function setMessageBagName(string $name = 'default') {
            $this->message_bag_name = $name;
            return $this;
        }

        public function setMessageBag(MessageBag $message_bag, string $name = 'default') {

            $this->messages = $message_bag;
            $this->message_bag_name = $name;
            return $this;

       }

        public function errorsAsArray() : array
        {
            return $this->errors;
        }

        public function messages() : MessageBag
        {
            return $this->messages;
        }

        public function namedBag () {
            return $this->message_bag_name;
        }

        public function setJsonMessage(string $message)
        {
            $this->message = $message;
            return $this;
        }


    }