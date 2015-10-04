<?php

    $data = json_decode(file_get_contents('/home/ubuntu/workspace/data.json'),true);
    echo 'hej';
    $my = new mysqli("127.0.0.1", "stumpdk", "", "test");
    if(!$stmt = $my->prepare('insert into images_tags (tag_id, image_id, x, y, value, confidence) values (?, ?, ?, ?, ?, ?)')){
        die ('could not prepare statement:' . $my->error);
    }

    for($j = 0; $j < 100/*count($data)*/; $j++){
        $row = $data[$j];
        if($row['value']['data'] == NULL)
            continue;
            
        $image_id = $row['key'];
        
        $imgHeight = $row['value']['dimensions']['height'];
        $imgWidth = $row['value']['dimensions']['width'];
        
        for($i = 1; $i < count($row['value']['data'])-1; $i++){
            $age = $row['value']['data'][$i][1];
            $confidence = $row['value']['data'][$i][2];
            $first_eye_x = $row['value']['data'][$i][4] / $imgWidth;
            $first_eye_y = $row['value']['data'][$i][5] / $imgHeight;
            
            $tag_id = '1000';
            
            $stmt->bind_param("ssssss", $tag_id, $image_id, $first_eye_x, $first_eye_y, $age, $confidence);
        
            if(!$stmt->execute()){
                die ($my->error() . ' ' . $str);
            }   
        }
        
        
    }