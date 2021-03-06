<?php


namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class lessorEqualZero extends Constraint
{
    public $message = 'quantity cant be equal zero';
}