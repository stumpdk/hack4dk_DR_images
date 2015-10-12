<?php

require('library/S3Helper.php');

use Phalcon\Mvc\Model;

class Images extends Model
{
    const SIZE_THUMB = '200';
    const SIZE_PREVIEW = '1024';

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
        $this->thumbUrl = 'https://hack4dk-2015-stumpdk-1.c9.io/api/img_resize/' . $this->id . '/thumb';
        $this->originalUrl = str_replace('https://hack4dk-2015-stumpdk-1.c9.io','http://hack4dk.dr.dk',$this->url);
    }
    
    public function validation()
    {
        // Check if any messages have been produced
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }
    
    public function getImageFile($id, $width){
        $s3 = new S3Helper();
        return $s3->getFileContents($id . $width);
    }
    
    public function resizeExternalFile($id, $url, $width){
        
        //Old file check, for backward compability
        //Already converted files are loaded from the local storage, otherwise from S3
        $newExt = '_' . $width . '.jpg';
        $resized_file = str_replace('http://hack4dk.dr.dk/', '/home/ubuntu/workspace/resized_images/', $url);
        $resized_file = str_replace('.jpg',  $newExt, $resized_file);
        
        if(file_exists($resized_file)){
            return base64_encode(readfile($resized_file));
        }
        
        //Checking S3 storage for file
        $s3 = new S3Helper();    
        $result = null;
        
        $result = $s3->getFileContents($id . $width);
        //We have a match!
        if($result !== false){
            return $result['Body'];
        }
        
        //Resizing image and saving it in S3 storage
        $image = new \Eventviva\ImageResize($url);
        if($width == Images::SIZE_THUMB){
            $image->crop(200, 150);
        }
        else{
            //$image->resizeToHeight($width);
            $image->resizeToBestFit(Images::SIZE_PREVIEW, Images::SIZE_PREVIEW);
        }
     
        $imageStr = $image->getImageAsString();
        $s3->put($id . $width, $imageStr);

        return $imageStr;
    }    
}