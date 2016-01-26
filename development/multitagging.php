<?php

    $tag = "this is the tag2";
    $image_id_start = 342938;
    $image_id_end = 342940;
    $user_id = null;
    
    $my = new mysqli("127.0.0.1", "stumpdk", "", "drs_historiske_billeder");
    
    //Create tag if it does not exist. Get id.
    if(!$stmt_find = $my->prepare('select id from tags where name = ?')){
        die("could not prepare statement:". $my->error);    
    }
    
    $stmt_find->bind_param("s", $tag);
    $stmt_find->execute();
    $stmt_find->bind_result($id);
    $stmt_find->fetch();
    $stmt_find = null;
    
    if(is_null($id))
    {
        if(!$stmt_insert = $my->prepare('insert into tags (name) VALUES (?)')){
            die ('could not prepare statement:' . $my->error);
        }
       $stmt_insert->bind_param("s", $tag);
       $stmt_insert->execute();
       $id = $my->insert_id;
       $stmt_insert = null;
       echo 'created new tag' . "\r\n";;
    }
    
    echo 'id: ' . $id . "\r\n";
    
    //Insert the tag id for all images from image start to image end
    if(!$stmt_new = $my->prepare('insert into images_tags (user_id, tag_id, image_id, x, y) values (?, ?, ?, ?, ?)')){
        die ('could not prepare statement:' . $my->error);
    }
    
    for($image_id = $image_id_start; $image_id <= $image_id_end; $image_id++)
    {
        $x = 0.1;
        $y = 0.1;
        $stmt_new->bind_param("sssss",$user_id,$id,$image_id,$x, $y);
        $stmt_new->execute();
        echo 'added image_tag: ' . $image_id . "\r\n";
    }