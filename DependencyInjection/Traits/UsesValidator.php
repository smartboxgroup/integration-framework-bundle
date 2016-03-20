<?php
namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;


use Symfony\Component\Validator\Validator\ValidatorInterface;

trait UsesValidator
{

    /** @var  ValidatorInterface */
    protected $validator;

    /**
     * @return ValidatorInterface
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param mixed $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }
}