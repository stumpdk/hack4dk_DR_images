<?php

use Phalcon\Mvc\Model;

class Images extends Model
{
    public static $SIZE_THUMB = '200';
    public static $SIZE_PREVIEW = '1024';

    public function initialize()
    {
        $this->hasMany("id", "ImagesTags", "image_id");
       
        $this->hasManyToMany(
            "id",
            "ImagesTags",
            "image_id", "tag_id",
            "Tags",
            "id"
        );
    }
    
    public function afterFetch()
    {
        // Convert the string to an array
        $this->resizedUrl = 'https://hack4dk-2015-stumpdk-1.c9.io/api/img_resize/' . $this->id . '/preview';
        $this->thumbUrl = 'https://hack4dk-2015-stumpdk-1.c9.io/api/img_resize/' . $this->id . '/thumb';;
    }
    
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
    
    public function resize($image, $width){
        //$image = Images::findFirstById($id);
        if(count($image) > 0){
            $newExt = '_' . $width . '.jpg';
            $resized_file = str_replace('http://hack4dk.dr.dk/', '/home/ubuntu/workspace/resized_images/', $image->url);
            $resized_file = str_replace('.jpg',  $newExt, $resized_file);
            
            if(!file_exists($resized_file)){
                
                //Creating the image directories
                exec('mkdir -p ' . dirname($resized_file));
                
                $image = new \Eventviva\ImageResize($image->url);
                
                if($width == Images::$SIZE_THUMB){
                    $image->crop(200, 150);
                }
                else{
                    //$image->resizeToHeight($width);
                    $image->resizeToBestFit(Images::$SIZE_PREVIEW, Images::$SIZE_PREVIEW);
                }
                
                $image->save($resized_file);
            }
            
            return $resized_file;
        }        
    }    
}