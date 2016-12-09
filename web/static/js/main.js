function showMenuItems() {
    var x = document.getElementById("menu");
    if (x.className === "topnav") {
        x.className += " responsive";
    } else {
        x.className = "topnav";
    }
}

$( document ).ready(function(){

    $('#filter').click(function(e){

        var _filterBlock = $('#filterBlock');
        if( _filterBlock.is(':visible') ){
            _filterBlock.slideUp();
            $(e.currentTarget).text('Показать фильтры');
        }
        else{
            _filterBlock.slideDown();
            $(e.currentTarget).text('Убрать фильтры');
        }
        return false;
    });

    $('.addDriverBtn').click(function(e){
        var dWindow = $('#addDriverWindow'),
            dTable = $('#driversTable'),
            dFilterBtn = $('#filter'),
            dWindowShowBtn = $(e.currentTarget),
            cancelBtn = dWindow.find('#cancelBtn'),
            addDriverVehicleSelect = dWindow.find('#addDriverVehicle');

        dFilterBtn.hide();
        dTable.hide();
        dWindowShowBtn.hide();
        dWindow.show();

        addDriverVehicleSelect.chosen({no_results_text: "Ничего не найдено."});

        cancelBtn.click(function(e){
            dFilterBtn.show();
            dTable.show();

            dWindowShowBtn.show();
            dWindow.hide();
        });
    });
});
