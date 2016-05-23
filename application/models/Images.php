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
        }
        else{
            $result['additional_info'] = [];
            $result['additional_info']['fotograf'] = null;
        }
        
        if($result['additional_info']['fotograf'] == null || $result['additional_info']['fotograf'] == '' || $result['additional_info']['fotograf'] == '?')
            $result['additional_info']['fotograf'] = 'DR';
        
        return $result;
    }
}