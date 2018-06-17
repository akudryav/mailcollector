$(document).ready(function () {
    // Обработчик выбора json файла
    $('#credential-form input[type="file"]').change(function() {
        $('#credential-form').submit();
        $('#myModal').modal('hide');
    });

    // Подготовка модального окна удаления комментария
    $('#myModal').on('show.bs.modal', function(e) {
        $('#credential-form').attr("action", $(e.relatedTarget).attr("href"));
    });
    // кнопка csv импорта
    $("#csv_button").on("click", function(){
        $("#csvform").toggle();
    });
});


