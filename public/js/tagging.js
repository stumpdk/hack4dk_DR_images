
    Array.prototype.equals = function (array) {
        // if the other array is a falsy value, return
        if (!array)
            return false;
    
        // compare lengths - can save a lot of time 
        if (this.length != array.length)
            return false;
    
        for (var i = 0, l=this.length; i < l; i++) {
            // Check if we have nested arrays
            if (this[i] instanceof Array && array[i] instanceof Array) {
                // recurse into the nested arrays
                if (!this[i].equals(array[i]))
                    return false;       
            }           
            else if (this[i] != array[i]) { 
                // Warning - two different object instances will never be equal: {x:20} != {x:20}
                return false;   
            }           
        }       
        return true;
    }

    /**
     * The Receiver module loads images (specific or random), saves tags 
     * and loads stats and latest tags 
     */ 
    var receiver = (function(){
        var pub = {};
        pub.id = -1;
        pub.url = '';
        pub.tags = {};
        pub.originalData = {};
        
        pub.getImage = function(image_id){
            if(image_id !== undefined && image_id !== null){
                return $.ajax(dataUrl + 'image/' + image_id, {method: 'GET', dataType: 'json', cache: false}).
                success(function(data){
                    //console.log('fetched image:', data.resizedUrl);
                    pub.id = data.image.id;
                    pub.url = data.image.resizedUrl;
                    pub.tags = data.tags;
                    var newTags = [];
                    for(i = 0; i < pub.tags.length; i++){
                        var text = "";
                        if(pub.tags[i].value){
                            text = 'calculated age: ' + pub.tags[i].value;
                        }
                        else{
                            text = pub.tags[i].text;
                        }
                        //var text = pub.tags[i].value || pub.tags[i].text;
                        newTags.push({x : pub.tags[i].x, y : pub.tags[i].y,text: text});
                    }
                    data.newTags = newTags;
                    data.resizedUrl = data.image.resizedUrl;
                    pub.originalData = newTags;
                    return data;
                });
            }
            else{
                return $.ajax(dataUrl + 'images/random', {method: 'GET', dataType: 'json', cache: false}).
                success(function(data){
              //      console.log('fetched random image:', data.resizedUrl);
                    pub.id = data.image.id;
                    pub.url = data.image.resizedUrl;
                    return data;
                });
            }
        };
        
        pub.saveImageMetadata = function(data){
            if(data.data.equals(pub.originalData) || data.data.length == 0){
                Helper.updateStatus('ingen ændringer at gemme...');
                return;
            }
                
            Helper.updateStatus("gemmer tags...");
            var convertedData = {};
            convertedData.id = pub.id;
            convertedData.tags = data.data;
            for(var i = 0; i < data.data.length; i++){
                data.data[i].name = data.data[i].text;
                data.data[i].category_id = 1;
            }
            var request = $.ajax(dataUrl + 'image/metadata/' + pub.id, {
                method: 'post', 
                dataType: 'json', 
                cache: false, data: convertedData,
                success: function(data){
                    Helper.updateStatus("dine tags blev gemt...");
                },
                error: function(){
                    Helper.updateStatus('kunne ikke gemme! Prøv igen...');
                }
            });
            
            return request;
        };
        
        pub.getLatestTags = function(){
          return $.ajax(dataUrl + 'tags/latest', {method: 'GET', dataType: 'json', cache: false});
        };
        
        pub.getStats = function(){
          return $.ajax(dataUrl + 'stats', {method: 'GET', dataType: 'json', cache: false});  
        };
        
        return pub;
    })();
    
    /**
     * The TagController controls the saving of image tags
     * and the loading of random images
     */ 
    var tagCtrl = (function(){
        var pub = {};
        pub.saveData = function(data){
            //convert data here!
            receiver.saveImageMetadata({data: data});
        };
        
        pub.loadRandomImage = function(){
            new receiver();
            receiver.getImage().success(function(){
           //     console.log('data saved!');
            });
        };
        
        return pub;
    })();

    /**
     * The Search module searches from images with specific tags
     * and adds results to the DOM
     */ 
    var searchModule = (function(){
    var pub = {};
    
    pub.search = function(term){
        $.ajax( Helper.getUrl() + '/api/images/search?term=' + encodeURI(term), {'dataType' : 'json', 'method': 'get', cache: false})
        .success(function(data){
            pub.results = data;
            pub.addResultsToDOM('#search_results');
        });
    };
    
    pub.addResultsToDOM = function(element){
      $(element).html();
      var html = '<div>fandt ' + pub.results.length + ' resultater</div><div class="row">';
      var url = Helper.getUrl();
      for(var i = 0; i < pub.results.length; i++)
      {
        html = html + '<div class="col-lg-3 col-md-4 col-xs-6 thumb"><a class="thumbnail" href="' + url + '?image_id=' + pub.results[i].id + '"><img class="img-responsive" src="' + pub.results[i].url + '"/></a></div>';
      }
      html = html + '</div>';
      $(element).html(html);
    };
    
    return pub;
    })();
    
    /**
     * The LatestTaggedImages module loads the latest tagged images
     * and adds them to the DOM
     */ 
    var LatestTaggedImagesModule = (function(){
    var pub = {};
    
    pub.get = function(term){
        $.ajax( Helper.getUrl() + '/api/images/latest', {'dataType' : 'json', 'method': 'get', cache: 'false'})
        .success(function(data){
            pub.results = data;
            pub.addResultsToDOM('#latest_tagged_images');
        });
    };
    
    pub.addResultsToDOM = function(element){
      $(element).html();
      var html = '<div class="row">';
      var url = Helper.getUrl();
      for(var i = 0; i < pub.results.length; i++)
      {
        html = html + '<div class="col-lg-3 col-md-4 col-xs-6 thumb"><a class="thumbnail" href="' + url + '?image_id=' + pub.results[i].id + '"><img class="img-responsive" src="' + pub.results[i].url + '"/></a></div>';
      }
      html = html + '</div>';
      $(element).html(html);
    };
    
    return pub;
    })();

    /**
     * The Helper module performs various tasks:
     * Gets the URL and its query parameters
     * Updates the status (a common div in the HTML pages)
     * Gets the current URL
     */ 
    var Helper = (function(){
    var pub = {};
    
    pub.gup = function(name) {
        return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
      };
      
	
	pub.updateStatus = function(status, element){
		if(status == "")
			status = "&nbsp;";
		
		var elm = element || '#status';
			
		$(elm).html(status);
	};
	
	pub.getUrl = function(){
	//    var http = location.protocol;
    //    var slashes = http.concat("//");
        var host = window.location.href;
        
   //     host = ;
/*        if(host.indexOf('/html') !== -1){
            host = host.substr(0, host.indexOf('/html'));
        }*/
        host = host.substring(0, host.lastIndexOf('/')) + '/';
        return host;
	};
    
    return pub;
    })();    
    
    /**
     * The Facebook module is used to handle interaction with the Facebook API
     * It includes:
     * Init: Sets the app ID and other options
     * getLoginStatus: Checks whether or not a user is logged in
     * Subscription for login and logout: Redirects the user to the server
     * side login and logout pages, when login or logout occurs
     */ 
    var FacebookModule = (function(){
  window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '976309079106997',                        // App ID from the app dashboard
  //    channelUrl : '//WWW.YOUR_DOMAIN.COM/channel.html', // Channel file for x-domain comms
      status     : true,                                 // Check Facebook Login status
      xfbml      : true,                                  // Look for social plugins on the page
      cookie     : true,
      version    : '2.4'
    });

    // Additional initialization code such as adding Event Listeners goes here
    FB.getLoginStatus(function(response) {
      if (response.status === 'connected') {
        console.log('Logged in.');
      }
      else {
        console.log('Not logged in.');
      }
    });
    
    FB.Event.subscribe('auth.login', function(response){
      if(response.status != 'connected')
        return;
        
      console.log(response);
      var oldLocation = encodeURI(window.location.href);
      console.log('logged in. redirecting...');
      document.location.href = 'login.php?redirect=' + oldLocation;
    });
    
    FB.Event.subscribe('auth.logout', function(response){
      console.log(response);
      var oldLocation = encodeURI(window.location.href);
      console.log('logged out. redirecting...');
      document.location.href = 'logout.php?redirect=' + oldLocation;
    });
  };
    })();
    
        var dataUrl = Helper.getUrl() + 'api/';//'https://hack4dk-2015-stumpdk-1.c9users.io/api/';
    