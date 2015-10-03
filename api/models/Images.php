<?php

use Phalcon\Mvc\Model;

class Images extends Model
{
    /*public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "ImagesTags",
            "image_id", "tag_id",
            "Tags",
            "id"
        );
    }*/
    public function initialize()
    {
        $this->hasMany("id", "ImagesTags", "image_id");
   /*     
        $this->hasManyToMany(
            "id",
            "ImagesTags",
            "image_id", "tag_id",
            "Tags",
            "id"
        );*/
    }
    
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}