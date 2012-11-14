function update_objects(path)
{
    //retrives information from a php-generated xml
    var url = '/inc/objects_xml.php?mg_id='+document.getElementById('family_name').value;

    var hreq = null;
    if(window.XMLHttpRequest){//firefox, chrome,...
       hreq = new XMLHttpRequest();
    } else {
       hreq = new ActiveXObject("Microsoft.XMLHTTP");//IE
    }

    hreq.onreadystatechange = function(){changeObjectsList(hreq,path); };
    hreq.open("GET", url, true); //true=asynchronous
    hreq.send(null);
}

function changeObjectsList(hreq, path)
{
    var text="<select name='model_name' id='model_name' onchange='change_thumb()'>";

    if(hreq.readyState == 4) //checks that the request is finished
    {
        var objects=hreq.responseXML.getElementsByTagName("object");

        for(i=0; i<objects.length; i++)
        {
            var object=objects[i];
            var id=object.getElementsByTagName("id")[0].childNodes[0].nodeValue;
            var name=object.getElementsByTagName("name")[0].childNodes[0].nodeValue;
            text+="<option value='"+id+"'";
            if(path == name) {
                text+= " selected=\"selected\"";
            }
            text+=">"+name+"</option>\n";
        }
    }

    text+="</select>";

    document.getElementById('form_objects').innerHTML = text;
    change_thumb();
}

function change_thumb()
{
    document.getElementById('form_objects_thumb').src = "../../modelthumb.php?id="+document.getElementById('model_name').value;  
}

function update_map(long_id, lat_id)
{
    var longitude = document.getElementById(long_id).value;
    var latitude = document.getElementById(lat_id).value;

    if(longitude!="" && latitude!="")
        document.getElementById('map').data = "http://mapserver.flightgear.org/popmap/?zoom=13&lat="+latitude+"&lon="+longitude;
}


function update_country()
{
    var longitude = document.getElementById('longitude').value;
    var latitude = document.getElementById('latitude').value;
    
    if (longitude!="" && latitude!="")
    {
        //retrives information from a php-generated xml
        var url = '/inc/country_xml.php?lg='+longitude+"&lt="+latitude;

        var hreq = null;
        if(window.XMLHttpRequest){//firefox, chrome,...
           hreq = new XMLHttpRequest();
        } else {
           hreq = new ActiveXObject("Microsoft.XMLHTTP");//IE
        }

        hreq.onreadystatechange = function(){update_country_aux(hreq); };
        hreq.open("GET", url, true); //true=asynchronous
        hreq.send(null);
    }
}

function update_country_aux(hreq)
{
    if (hreq.readyState == 4) //checks that the request is finished       
    {
        var country=hreq.responseXML.getElementsByTagName("country")[0].childNodes[0].nodeValue;

        var ddl = document.getElementById('ob_country');
        
        for (var i = 0; i < ddl.options.length; i++)
        {
            if (ddl.options[i].value == country)
            {
                if (ddl.selectedIndex != i) {
                    ddl.selectedIndex = i;
                    ddl.onchange();
                }
                break;
            }
        }
    }
}
