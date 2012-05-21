function update_objects()
{
   //retrives information from a php-generated xml
   var url = '../ajax/objects_xml.php?mg_id='+document.getElementById('family_name').value;
              
   var hreq=null;
   if(window.XMLHttpRequest){//firefox, chrome,...
      hreq=new XMLHttpRequest();
   }else{
      hreq=new ActiveXObject("Microsoft.XMLHTTP");//IE
   }
      hreq.onreadystatechange = function(){changeObjectsList(hreq); };
      hreq.open("GET", url, true); //true=asynchronous
      hreq.send(null);
}

function changeObjectsList(hreq)
{
  var text="<select name='model_name'>";
	
  if(hreq.readyState==4) //checks that the request is finished       
  { 
    var objects=hreq.responseXML.getElementsByTagName("object");
	 
    for(i=0; i<objects.length; i++) 
    {
      var object=objects[i];
      var id=object.getElementsByTagName("id")[0].childNodes[0].nodeValue;
      var name=object.getElementsByTagName("name")[0].childNodes[0].nodeValue;
	 
      text+="<option value='"+id+"'>"+name+"</option>\n";
    }
  }
  
  text+="</select>";
  
  document.getElementById('form_objects').innerHTML =text;
}