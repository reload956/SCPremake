<?php


namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class notEnoughProduct extends Constraint
{
    public $message = 'Not enough product in stock.';
}