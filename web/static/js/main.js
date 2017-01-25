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

    $vehicleSelect = $('#appbundle_driver_transport_id');
    if( $vehicleSelect ){
        $vehicleSelect.addClass("chosen-select");
        $vehicleSelect.chosen({no_results_text: "Ничего не найдено"});
    }

    //masked input for driver add/edit window
    $driverPhone = $('#appbundle_driver_phone');
    if( $driverPhone ){
        $driverPhone.mask("9 (999) 999-99-99", {placeholder:"_"});
    }

    $driverLicense = $('#appbundle_driver_driverLicense');
    if( $driverLicense ){
        $.mask.definitions['~'] = '[а-яА-ЯёЁ0-9]';
        $driverLicense.mask("~~ ~~ 999999", {placeholder:"_"});
    }

    //masked input for vehicle add/edit window
    $vehicleRegNum = $('#appbundle_transport_regNum');
    if( $vehicleRegNum ){
        $.mask.definitions['~'] = '[авекмнорстухАВЕКМНОРСТУХ]';//А, В, Е, К, М, Н, О, Р, С, Т, У, Х
        $vehicleRegNum.mask("~999~~ 99?9", {placeholder:"_"});
    }

    $vehicleTrailerRegNum = $('#appbundle_transport_trailerRegNum');
    if( $vehicleTrailerRegNum ){
        $.mask.definitions['~'] = '[авекмнорстухАВЕКМНОРСТУХ]';//А, В, Е, К, М, Н, О, Р, С, Т, У, Х
        $vehicleTrailerRegNum.mask("~~9999 99?9", {placeholder:"_"});
    }

    //unlink vehicle from driver routine
    $('#btnUnlinkVehicle').click(function(e){
        var  $vehicleSelect = $('#appbundle_driver_transport_id')
            ,$vehicleSelectLabel = $vehicleSelect.parent().find('label');

        $vehicleSelectLabel.text('Прикреплённое транспортное средство:');
        $vehicleSelect.find('option:selected').removeAttr('selected');
        $vehicleSelect.append('<option val="" selected="selected"> </option>');
    });

    $('.showLotRouteInfo').click(function(e){
        var $routeInfo = $(this).parent().parent().next().find('.lotInfoWindow');
        if( $routeInfo.is(':visible') ){
            $routeInfo.slideUp(1100);
        }
        else{
            $routeInfo.slideDown(1100);
        }
    });

    $('.btnCloseRouteInfo').click(function(){
        $(this).parent().parent().slideUp(1100);
    });

    $('#appbundle_transport_type').change(function (e) {
        $(e.currentTarget).addClass('black');
    });

    $('#routeAssignDriver').click(function(e){
        var $routesTable = $('#routesTable'), $routesFilter = $('#routesFilter'), $routeAddDriverWindow = $('#routeAddDriverWindow');

        $routeAddDriverSelect = $('#routeAddDriverSelect');
        if( $routeAddDriverSelect ){
            $routeAddDriverSelect.addClass("chosen-select");
            $routeAddDriverSelect.chosen({no_results_text: "Ничего не найдено"});
        }

        $routeAddVehicleSelect = $('#routeAddVehicleSelect');
        if( $routeAddVehicleSelect ){
            $routeAddVehicleSelect.addClass("chosen-select");
            $routeAddVehicleSelect.chosen({no_results_text: "Ничего не найдено"});
        }

        $routesTable.hide();
        $routesFilter.hide();

        $routeAddDriverWindow.data('routeId', $('.routeAssignDriver').attr('data-routeId'));
        $routeAddDriverWindow.show();
    });

    //show driver's vehicle while driver is being selected
    //params.selected shows current selected option
    $('#routeAddDriverSelect').on('change', function(e, params){
        $('.routeAddVehicleSelect .driversVehicle').html( $('#routeAddDriverSelect option:selected').attr('data-vehicle') );
    });

    //submit attach driver to route data
    $('#routeAddDriverWindow input[type="button"]').click(function(e){
        $.ajax({
            method: 'POST',
            url: 'attachDriver',
            data: {
                     driver: $(this).parent().find('#routeAddDriverSelect').val()
                    ,vehicle: $(this).parent().find('#routeAddVehicleSelect').val()
                    ,route: $(this).parent().data('routeId')
            }
        })
        .done(function( response ){
            if( response.result ){
                location.reload();
            }
        });
    });

    $('#routeDeleteDriver').click(function(e){
        var $this = $(e.currentTarget);
        $.ajax({
            method: 'POST',
            url: 'removeDriver',
            data: { route: $('.routeAssignDriver').attr('data-routeId') }
        })
        .done(function( response ){
            if( response.result ){
                //delete driver information from route info table
                $this.parent().find('.routeDriverName').html('');
                $this.parent().find('.routeDriverPassportData').html('');
                $this.parent().find('.routeDriverVehicle').html('');

                //delete driver's info from routes table
                location.reload();
            }
        });
    });

    //time left countdowns
    $.each($('.lotTimeLeft'), function(i, el){
        var $this = $(this);
        var finalDate = $this.html();
        $this.countdown(finalDate, function(event){
            var totalHours = event.offset.totalDays * 24 + event.offset.hours;
            $this.html(event.strftime(totalHours + ':%M:%S'));
            if( event.elapsed ){
                $.ajax({
                    method: 'POST',
                    url: 'lotAuctionEnd',
                    data: { lot: $this.parent().siblings('.lotCurrentPrice').attr('id') }
                })
                .done(function( response ){
                    if( response.result ){
                        //delete row with expired lot from auction table
                        $this.parent().parent().remove();
                    }
                });
            }
        });
    });

    //click handlers for do bet buttons
    var bets = [];
    $('.doBid').each(function(){
        var $this = $(this);
        $this.click(function(){
            var _bet = parseInt( $this.siblings('#appbundle_bet').find('#appbundle_bet_value').val() );
            var _price = $this.parents('.lotDoBid').siblings('.lotCurrentPrice').html();
            //in order to place bet it must conform following conditions
            if(    _bet > 0 //positive number
                && bets.indexOf(_bet) < 0 //new bet
                && _bet <= parseInt(_price) - parseInt($this.attr('data-tradeStep')) //less than current lot price minus step of price reducing
                //lot is in auction
            ){
                bets.push( _bet );
                $this.parent().submit();
            }
        });
    });

    var updateLotPrices = function(){
        $.ajax({
            url: 'lotsPrices',
            cache: false
        }).done(function( data ){
            console.log(data);
            if( !jQuery.isEmptyObject(data.lots) ){
                $('.lotCurrentPrice').each(function(){
                    var _id = parseInt($(this).attr('id'));
                    if( data.lots[ _id ].price != parseInt($(this).html()) ){
                        $(this).html(data.lots[ _id ].price);
                    }
                });
            }
        });
    };

    setInterval( updateLotPrices, 2500 );
});
