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

    //show add driver window
    $('.addDriverBtn').click(function(e){
        var dWindow = $('#addDriverWindow'),
            dTable = $('#driversTable'),
            dFilterBtn = $('#filter'),
            dFilterWindow = $('#filterBlock'),
            dWindowShowBtn = $(e.currentTarget),
            cancelBtn = dWindow.find('#cancelBtn'),
            addDriverVehicleSelect = dWindow.find('#addDriverVehicle');

            dFilterBtn.hide();
            dFilterWindow.hide();
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

    //show add vehicle window
    $('.addVehicleBtn').click(function(e){
        var vWindow = $('#addVehicleWindow'),
            vTable = $('#vehicleTable'),
            vFilterBtn = $('#filter'),
            vFilterWindow = $('#filterBlock'),
            vWindowShowBtn = $(e.currentTarget),
            cancelBtn = vWindow.find('#cancelBtn'),
            vehicleTypeSelect = vWindow.find('#vehicleType');

            vFilterBtn.hide();
            vFilterWindow.hide();
            vTable.hide();
            vWindowShowBtn.hide();
            vWindow.show();

            cancelBtn.click(function(e){
                vFilterBtn.show();
                vTable.show();

                vWindowShowBtn.show();
                vWindow.hide();
            });

            vehicleTypeSelect.change(function () {
                vehicleTypeSelect.addClass('black');
            });
    });

});
