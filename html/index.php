<!DOCTYPE html>
<html>
    <head>
    	<meta charset="UTF-8">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
        
        
        <link rel="stylesheet" href="css/normalize.css" />
	      <link rel="stylesheet" href="css/taggd.css" />
	      
	      <style>
		    img {
			    display: block;
			    width: 100%;
		    }
	      </style>
	      <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	      <script src="js/jquery.taggd.js"></script>
	      <script src="js/tagging.js"></script>
	      
	      
    </head>
    <body>
    	<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Crowdsourcing DR history</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="challenge.php">Challenge</a></li>
            <li><a href="about.html">About</a></li>
            <li><a href="search.html">Search</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container">

      <div class="starter-template">
        <h1>&nbsp;</h1>
        <p style="text-align:center"><input type="button" class="btn" id="saveData" value="Gem" /><input type="button" class="btn" id="get_new_image" value="Hent nyt" /><input type="button" class="btn" id="challenge_button" value="Challenge" /></p>
        <div id="image_container"><img src="" class="taggd" id="tagging_image"></img></div>
      </div>
    </div>
    </body>
    
    <script>
	var options = {
		
		align: {
			y: 'top'
		},
		
		offset: {
			top: 15
		},
		
		handlers: {
	      mouseenter: 'show',
	      mouseleave: 'hide',
	    },
    	
    	edit: true
	};
	
	var data = [
	// 	{ x: 0.62, y: 0.7, text: 'Rope'             },
	// 	{ x: 0.51, y: 0.5, text: 'Bird'             },
	// 	{ x: 0.40, y: 0.3, text: 'Water, obviously' }
	];
	
	/*var taggd = $('.taggd').taggd( options, data );
	taggd.on('change', function(e){
		console.log(taggd.data);
	});*/
	var taggd = '';

	$('#saveData').click(function(e){
		tagCtrl.saveData(taggd.data);
	});
	$("#get_new_image").click(function(e){
		updateImage();
	});
	
	var updateImage = function(){
		if(taggd !== ''){
			taggd.dispose();
		}
	
		$("#tagging_image").remove();
		$("#image_container").html('<img src="" class="taggd" id="tagging_image" />');
		receiver.getImage().success(function(newData){
			$('#tagging_image').attr('src', newData.resizedUrl);
			taggd = $('.taggd').taggd( options, data );
		});
	};
	
	
	$(function(){
		updateImage();
	});
	
	
</script>
</html>