<?php

use Phalcon\Mvc\Model;

class Tags extends Model
{
    public function initialize()
    {
        $this->hasMany("id", "ImagesTags", "tag_id");
        
        $this->hasManyToMany(
            "id",
            "ImagesTags",
            "tag_id", "image_id",
            "Images",
            "id"
        );
    }
    
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}