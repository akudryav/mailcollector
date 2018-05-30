$(document).ready(function () {
    // селектор типа ящика
    $("#mailbox-server_id").on("change", function(){
       $.get( "/server/data?id="+$(this).val(), function( data ) {
            $( "#mailbox-host" ).val( data.host );
            $( "#mailbox-port" ).val( data.port );
            $( "#mailbox-is_ssl" ).val( data.is_ssl );
          });
    });
    // кнопка csv импорта
    $("#csv_button").on("click", function(){
        $("#csvform").toggle();
    });
});


