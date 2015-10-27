<?php //var_dump($imageData); ?>
<!DOCTYPE html>
<html>
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Open Graph data -->
    <meta property="og:title" content="Opmærkning af DR's billeder" />
    <meta property="og:url" content="<?php echo $url . '/html/static.php?image_id=' . $imageData['image']['id']; ?>" />
    <meta property="og:image" content="<?php echo $imageData['image']['resizedUrl']; ?>" />
    <meta property="og:description" content="Hjælp os med at gøre DR's historiske billeder tilgængelige!" />

    <title>Opmærkning af DR's billeder</title>
    </head>
    <body>
    	<img src="<?php echo $imageData['image']['resizedUrl']; ?>">
    	<?php foreach($imageData['tags'] as $tag){ ?>
    	<p><?php echo $tag['name']; ?></p>
    	<?php } ?>
    </body>
</html>