/**
 * Submit the given form
 * @param formId form id to send
 * @param checkURL action URL
 * @param successURLPrefix success URL prefix (TODO user pattern instead)
 * @returns {Boolean} true if success, false otherwise
 */
function ajaxSubmit(formId, checkURL, successURLPrefix) {
    if (!("FormData" in window)) {
        // FormData is not supported; use default submission process
        return false;
    }
    
    // Prepare form
    var fd = new FormData();
    $('input[type="file"]').each(function() {
        var name = $(this)[0].name;
        jQuery.each($(this)[0].files, function(i, file) {
            fd.append(name, file);
        });
    });

    var other_data = $('#'+formId).serializeArray();
    $.each(other_data,function(key,input){
        fd.append(input.name,input.value);
    });
    
    waitingDialog();
    
    // Send form
    $.ajax({
        url: checkURL,
        data: fd,
        async: false,
        processData: false,
        contentType: false,
        type: "POST",
        timeout: 10000
    }).done(function(xml) {
        
        var errors = $(xml).find("error");

        if (errors.length > 0) {
            text = "<ul>";
            errors.each(function(){
                var errorText=$(this).text();
                text+="<li>"+errorText+"</li>";
            });
            text += "</ul>";

            $( "#submit-dialog-errors" ).html(text);
            $( "#submit-dialog" ).dialog( "open" );
        } else {
            var requestId = $(xml).find("requestId").text();
            window.location = successURLPrefix+requestId;
        }
    }).fail(function(jqXHR, textStatus){
        // If Ajax fails, use normal submit
        closeWaitingDialog();
        return false;
    });
    
    closeWaitingDialog();
    
    return true;
}


$(document).ready(function() {
    // create the loading window and set autoOpen to false
    $("#loadingScreen").dialog({
        autoOpen: false,    // set this to false so we can manually open it
        dialogClass: "loadingScreenWindow",
        closeOnEscape: false,
        draggable: false,
        width: 460,
        minHeight: 50,
        modal: true,
        buttons: {},
        resizable: false,
        open: function() {
            // scrollbar fix for IE
            $('body').css('overflow','hidden');
        },
        close: function() {
            // reset overflow
            $('body').css('overflow','auto');
        }
    });
});
function waitingDialog() {
    $("#loadingScreen").html('Please wait...');
    $("#loadingScreen").dialog('option', 'title', 'Loading');
    $("#loadingScreen").dialog('open');
}
function closeWaitingDialog() {
    $("#loadingScreen").dialog('close');
}