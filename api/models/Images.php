<?php

use Phalcon\Mvc\Model;

class Images extends Model
{
    public static $SIZE_THUMB = '200';
    public static $SIZE_PREVIEW = '1024';
    
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
        $this->resizedUrl = 'https://hack4dk-2015-stumpdk-1.c9.io/api/img_resize/' . $this->id;
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
            $newExt = '_' . $width . '.php';
            $resized_file = str_replace('http://hack4dk.dr.dk/', '/home/ubuntu/workspace/resized_images/', $image->url);
            $resized_file = str_replace('.jpg',  $newExt, $resized_file);
            
            if(!file_exists($resized_file)){
                //Folder creation (local caching) temporary disabled
             /*   if(!file_exists(dirname($resized_file))){
                    mkdir('./' . dirname($resized_file), '0777', true);
                }*/
                exec('mkdir -p ' . dirname($resized_file));
                //Creating folders recursively
                /*$resized_file2 = str_replace(__DIR__, '', $resized_file);
                $sub_dirs = explode('/', dirname($resized_file2));
                unset($sub_dirs[0]);
                var_dump($sub_dirs);
                $current_dir = '';
                foreach($sub_dirs as $dir){
                    
                    $new_dir = $current_dir . '/' . $dir;
                    echo 'adding: ' . $new_dir;
                    if(!file_exists($new_dir))
                        mkdir($new_dir, '0777');
                    
                    $current_dir .= '/' . $dir;
                }*/
                
                $image = new \Eventviva\ImageResize($image->url);
                $image->resizeToHeight($width);
                $image->save($resized_file);
            }
            
            return $resized_file;
        }        
    }    
}