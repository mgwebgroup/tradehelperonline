<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Expression extends Constraint
{
    public $message = 'The formula "{{ formula }}" is not valid.';
    public $name = 'The formula with name "{{ name }}" already exists';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
