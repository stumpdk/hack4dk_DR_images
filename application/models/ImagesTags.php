<?php

use Phalcon\Mvc\Model;

class ImagesTags extends Model
{
    public function initialize()
    {
        $this->belongsTo("image_id", "Images", "id");
        $this->belongsTo("tag_id", "Tags", "id");
    }
    
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}