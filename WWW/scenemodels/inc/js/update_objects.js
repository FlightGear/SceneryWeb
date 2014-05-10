function update_objects(modelFilename)
{
    //retrives information from a php-generated xml
    var url = '/inc/objects_xml.php?mg_id='+document.getElementById('family_name').value;

    var hreq = null;
    if(window.XMLHttpRequest){//firefox, chrome,...
       hreq = new XMLHttpRequest();
    } else {
       hreq = new ActiveXObject("Microsoft.XMLHTTP");//IE
    }

    hreq.onreadystatechange = function(){changeObjectsList(hreq, modelFilename); };
    hreq.open("GET", url, true); //true=asynchronous
    hreq.send(null);
}

function changeObjectsList(hreq, modelFilename)
{
    var text="<select name='model_name' id='model_name' onchange='change_thumb();update_model_info();'>";

    // If the request is finished
    if(hreq.readyState === 4)
    {
        var models=hreq.responseXML.getElementsByTagName("model");

        for(i=0; i<models.length; i++)
        {
            var object=models[i];
            var id=object.getElementsByTagName("id")[0].childNodes[0].nodeValue;
            var name=object.getElementsByTagName("name")[0].childNodes[0].nodeValue;
            text+="<option value='"+id+"'";
            if(modelFilename == name) {
                text+= " selected=\"selected\"";
            }
            text+=">"+name+"</option>";
        }
    }

    text+="</select>";

    document.getElementById('form_objects').innerHTML = text;
    change_thumb();
}

function update_model_info(path)
{
    //retrives information from a php-generated xml
    var url = '/inc/model_info_xml.php?mo_id='+document.getElementById('model_name').value;

    var hreq = null;
    if(window.XMLHttpRequest){//firefox, chrome,...
       hreq = new XMLHttpRequest();
    } else {
       hreq = new ActiveXObject("Microsoft.XMLHTTP");//IE
    }

    hreq.onreadystatechange = function(){changeModelInfo(hreq,path); };
    hreq.open("GET", url, true); //true=asynchronous
    hreq.send(null);
}

function changeModelInfo(hreq, path)
{
    if(hreq.readyState === 4) //checks that the request is finished
    {
        var object=hreq.responseXML;
        var name=object.getElementsByTagName("name")[0].childNodes[0].nodeValue;
        var notesNode = object.getElementsByTagName("notes")[0].childNodes;
        var notes;
        if (notesNode.length>0) {
            notes=object.getElementsByTagName("notes")[0].childNodes[0].nodeValue;
        } else {
            notes = "";
        }
        var au_id=object.getElementsByTagName("author")[0].childNodes[0].nodeValue;
        
        document.getElementById('mo_name').value = name;
        document.getElementById('notes').value = notes;
        document.getElementById('mo_author').value = au_id;
    }
}

function change_thumb() {
    document.getElementById('form_objects_thumb').src = "../../modelthumb.php?id="+document.getElementById('model_name').value;  
}

function update_map(long_id, lat_id) {
    var longitude = document.getElementById(long_id).value;
    var latitude = document.getElementById(lat_id).value;

    if(longitude!=="" && latitude!=="")
        document.getElementById('map').data = "http://mapserver.flightgear.org/popmap/?zoom=13&lat="+latitude+"&lon="+longitude;
}


function update_country() {
    var longitude = document.getElementById('longitude').value;
    var latitude = document.getElementById('latitude').value;
    
    if (longitude!=="" && latitude!=="") {
        //retrieves information from a php-generated xml
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
