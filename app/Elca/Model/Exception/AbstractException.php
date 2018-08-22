<?php
namespace Elca\Model\Exception;

abstract class AbstractException extends \Exception
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $messageTemplate;

    /**
     * AbstractException constructor.
     *
     * @param string         $messageTemplate
     * @param array          $parameters
     * @param int            $code
     * @param \Exception|null $previous
     */
    public function __construct($messageTemplate, array $parameters = [], $code = 0, \Exception $previous = null)
    {
        $this->messageTemplate = $messageTemplate;
        $this->parameters = $parameters;

        parent::__construct($this->message(), $code, $previous);
    }

    /**
     * @return array
     */
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function messageTemplate()
    {
        return $this->messageTemplate;
    }

    /**
     * @return string
     */
    public function message()
    {
        return strtr($this->messageTemplate, $this->parameters);
    }
}
