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
            <li><a href="search.html">Search</a></li>
            <li><a href="about.html">About</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="container">

      <div class="starter-template">
        <h1>&nbsp;</h1>
        <p style="text-align:center"><input type="button" class="btn" id="saveData" value="Save tags" /><input type="button" class="btn" id="get_new_image" value="Get new" /></p>
        <p class="text-center" id="status"></p>
        <div id="image_container"><img src="" class="taggd" id="tagging_image"></img></div>
      </div>
    </div>
    </body>
    
    <script>
	var options = {
		
	  align: {
	    y: 'bottom'
	  },
	
	  offset: {
	    top: -35
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
	
	var updateStatus = function(status){
		if(status == "")
			status = "&nbsp;";
			
		$("#status").html(status);
	};
	
	var updateImage = function(image_id){
		updateStatus("loading image...");
		if(taggd !== ''){
			taggd.dispose();
		}
	
		$("#tagging_image").remove();
		$("#image_container").html('<img src="" class="taggd" id="tagging_image" />');
		receiver.getImage(image_id).success(function(newData){
			var img = new Image();
			img.onload = function () {
			   updateStatus("click on the picture to start tagging");
			};
			img.src = newData.resizedUrl;
			$('#tagging_image').attr('src', newData.resizedUrl);
			taggd = $('.taggd').taggd( options, [] );
			taggd.clear();
			if(newData.tags){
			/*	for(i = 0; i < newData.tag.length; i++){
					
				}*/
				taggd.addData(newData.newTags);
			}
			
			taggd.toggle();
		});
	};
	
	
	$(function(){
		console.log(gup('image_id'));
		updateImage(gup('image_id'));
	});


	var gup = function(name) {
	  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
	};
	
</script>
</html>