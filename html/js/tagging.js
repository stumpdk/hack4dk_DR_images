var dataUrl = 'https://hack4dk-2015-stumpdk-1.c9.io/api/';

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

var receiver = (function(){
    pub = {};
    pub.id = -1;
    pub.url = '';
    pub.tags = {};
    pub.originalData = {};
    
    pub.getImage = function(image_id){
        if(image_id !== undefined && image_id !== null){
            return $.ajax(dataUrl + 'image/' + image_id, {method: 'GET', dataType: 'json'}).
            success(function(data){
                console.log('fetched image:', data.resizedUrl);
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
            return $.ajax(dataUrl + 'images/random', {method: 'GET', dataType: 'json'}).
            success(function(data){
          //      console.log('fetched random image:', data.resizedUrl);
                pub.id = data.id;
                pub.url = data.resizedUrl;
                return data;
            });
        }
    };
    
    pub.saveImageMetadata = function(data){
        if(data.data.equals(pub.originalData)){
            updateStatus('no changes to save...');
            return;
        }
            
        updateStatus("saving data...");
        var convertedData = {};
        convertedData.id = pub.id;
        convertedData.tags = data.data;
        for(var i = 0; i < data.data.length; i++){
            data.data[i].name = data.data[i].text;
            data.data[i].category_id = 1;
        }
        return $.ajax(dataUrl + 'image/metadata/' + pub.id, {method: 'post', dataType: 'json', data: convertedData}).
        complete(function(data){
            updateStatus("data saved...");
        });        
    };
    
    pub.getLatestTags = function(){
      return $.ajax(dataUrl + 'tags/latest', {method: 'GET', dataType: 'json'});
    };
    
    return pub;
})();

var tagCtrl = (function(){
    var pub = {};
    pub.saveData = function(data){
        //convert data here!
        receiver.saveImageMetadata({data: data});
    };
    
    pub.loadRandomImage = function(){
        new receiver();
        receiver.getImage().success(function(){
            console.log('data saved!');
        });
    };
    
    return pub;
})();

      var searchModule = (function(){
        var pub = {};

        pub.search = function(term){
          $.ajax('/api/images/search?term=' + term, {'dataType' : 'json', 'method': 'get'})
          .success(function(data){
            pub.results = data;
            pub.addResultsToDOM('#search_results');
          });
        };
        
        pub.addResultsToDOM = function(element){
          $(element).html();
          var html = '<div>found ' + pub.results.length + ' results</div>';
          for(var i = 0; i < pub.results.length; i++)
          {
            html = html + '<div class="col-md-4"><a href="/?image_id=' + pub.results[i].id + '"><img src="' + pub.results[i].url + '"/></a></div>';
          }
          $(element).html(html);
        };
        
        pub.gup = function(name) {
      	  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
      	};
        
        return pub;
      })();