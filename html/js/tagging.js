var dataUrl = 'https://hack4dk-2015-stumpdk-1.c9.io/api/';
var receiver = (function(){
    pub = {};
    pub.id = -1;
    pub.url = '';
    pub.getImage = function(){
        return $.ajax(dataUrl + 'images/random', {method: 'GET', dataType: 'json'}).
        success(function(data){
            console.log('fetched random image:', data.resizedUrl);
            pub.id = data.id;
            pub.url = data.resizedUrl;
            return data;
        });
    };
    
    pub.saveImageMetadata = function(data){
        var convertedData = {};
        convertedData.id = pub.id;
        convertedData.tags = data.data;
        for(var i = 0; i < data.data.length; i++){
            data.data[i].name = data.data[i].text;
            data.data[i].category_id = 1;
        }
        return $.ajax(dataUrl + 'image/metadata/' + pub.id, {method: 'post', dataType: 'json', data: convertedData}).
        success(function(data){
            console.log('saved data:', convertedData);
        });        
    };
    
    return pub;
})();

var tagCtrl = (function(){
    var pub = {};
    pub.saveData = function(data){
        //convert data here!
        console.log(receiver);
        console.log(data);
        receiver.saveImageMetadata({data});
    };
    
    pub.loadRandomImage = function(){
        new receiver();
        receiver.getImage().success(function(){
            console.log('data saved!');
        });
    };
    
    return pub;
})();