<html>
    <head>
        
    </head>
    <body>
<?php
    // A simple web site in Cloud9 that runs through Apache
    // Press the 'Run' button on the top to start the web server,
    // then click the URL that is emitted to the Output tab of the console

    $sum = 0;
    for($i = 1; $i < 9; $i++){
        $str = 'http://hack4dk.dr.dk/Batch0' . $i . '/DR-ubehandlet/';
        $html = file_get_contents($str);
        
        preg_match_all('/"\/(\S{1,})"/', $html, $matches, PREG_SET_ORDER);
        
        $my = new mysqli("127.0.0.1", "stumpdk", "", "test");
        
        //var_dump($matches);
        $j = 0;
        foreach($matches as $m){
          //  var_dump($m);
         //   echo '<a href="http://hack4dk.dr.dk/' . $m[1] . '">test</a><br>';
            //$str = mysqli_escape_string($link, "insert into dr_images (path) values \'" . $m[1] . "\'");
            if(!$stmt = $my->prepare('insert into images (url, batch, type) values (?, ?, ?)')){
                die ('could not prepare statement');
            }
            $branchName = 'batch' . $i;
            $type = 'ubehandlet';
            $stmt->bind_param("sss", $m[1], $branchName, $type);
            if(!$stmt->execute()){
                die ($my->error() . ' ' . $str);
            }
            $j++;
        }
        
        echo 'Done getting batch. Count: ' . $j . '<br>';
        $sum = $sum + $j;
    }
    echo 'All images added: ' . $sum;
?>       
    </body>
</html>

