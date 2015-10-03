<?php

use Phalcon\Mvc\Model;

class Tags extends Model
{
    public function initialize()
    {
        $this->hasMany("id", "ImagesTags", "tag_id");
    }
    
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}