<?php

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
      /*  $this->resizedUrl = UrlHelper::getUrl($this->getDI()) . '/api/img_resize/' . $this->id . '/preview';
        $this->thumbUrl = UrlHelper::getUrl($this->getDI()) . '/api/img_resize/' . $this->id . '/thumb';
        $this->originalUrl = str_replace(UrlHelper::getUrl($this->getDI()),'http://hack4dk.dr.dk',$this->url);
        $this->imagePageUrl = UrlHelper::getUrl($this->getDI()) . '/?image_id=' . $this->id;
        
        if($this->s3_thumb == 1)
        {
            $this->thumbUrl = 'https://s3-eu-west-1.amazonaws.com/crowdsourcing-dr-images/' . $this->id . Images::SIZE_THUMB;
        }
        if($this->s3_preview == 1){
            $this->resizedUrl = 'https://s3-eu-west-1.amazonaws.com/crowdsourcing-dr-images/' . $this->id . Images::SIZE_PREVIEW;
        }*/
        
        $this->resizedUrl = 'https://s3-eu-west-1.amazonaws.com/drbilleder/' . $this->id . '_1024.jpg';
        $this->thumbUrl = 'https://s3-eu-west-1.amazonaws.com/drbilleder/' . $this->id . '_thumb.jpg';
        
        $this->originalUrl = str_replace(UrlHelper::getUrl($this->getDI()),'http://hack4dk.dr.dk',$this->url);
        
        $this->imagePageUrl = UrlHelper::getUrl($this->getDI()) . '?image_id=' . $this->id;
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
    
    public function loadFileContent($id, $url){
        //Old file check, for backward compability
        //Already converted files are loaded from the local storage, otherwise from S3
    /*    $newExt = '_' . $width . '.jpg';
        $resized_file = str_replace('http://hack4dk.dr.dk/', '/home/ubuntu/workspace/resized_images/', $url);
        $resized_file = str_replace('.jpg',  $newExt, $resized_file);
        
        if(file_exists($resized_file)){
            return base64_encode(readfile($resized_file));
        }
      */  
        //Checking S3 storage for file
        $s3 = new S3Helper();    
        $result = false;
        
        $result = $s3->getFileContents($id);
        
        if($result !== false){
            //We have a match!
            return $result['Body'];
        }
        
        return false;
    }
    
    public function resizeExternalFile($url, $width){
        //Resizing image and saving it in S3 storage
        $image = new \Eventviva\ImageResize($url);
        if($width == Images::SIZE_THUMB){
            $image->crop(200, 150);
        }
        else{
            //$image->resizeToHeight($width);
            $image->resizeToBestFit(Images::SIZE_PREVIEW, Images::SIZE_PREVIEW);
        }
     
        return $image->getImageAsString();
    }    
    
    public function saveFileContent($id, $fileData){
        $s3 = new S3Helper();
        $s3->put($id, $fileData);
    }
    
    public function getImageInfo($id){
        $image = Images::findFirst($id);
        
        if(!$image){
            die('image not found!');
        }
        
        $sql = 'select x, y, value, name as text FROM images_tags left join tags on images_tags.tag_id = tags.id WHERE confidence is null AND images_tags.image_id = ' . $id;
        $resultSet = $this->getDI()->get('db')->query($sql);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);

        $tags = $image->getTags()->toArray();
        $imageTags = $image->getImagesTags()->toArray();
        $result = [];
        $result['image'] = $image;
        
        $result['tags'] = $resultSet->fetchAll();
        
        $additionalDataSql = 'select * from additional_image_info a WHERE a.filename = \'' . $image->filename . '\' LIMIT 1';
        $resultSet2 = $this->getDI()->get('db')->query($additionalDataSql);
        $resultSet2->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        
        $addData = $resultSet2->fetchAll();
        
        if(isset($addData[0])){
            $result['additional_info'] = $addData[0];
        
            if($result['additional_info']['fotograf'] == null || $result['additional_info']['fotograf'] == ´´ || $result['additional_info']['fotograf'] == '?')
                $result['additional_info']['fotograf'] = 'DR';
        }
        
        return $result;
    }
}