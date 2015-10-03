<?php

use Phalcon\Mvc\Model;

class Images extends Model
{
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}