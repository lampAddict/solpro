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
        $('#lotInfoWindow').show();
        $('#auctionPageContainer').hide();
    });

    //show add driver window
    /*
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
    */
    $('#appbundle_transport_type').change(function (e) {
        $(e.currentTarget).addClass('black');
    });

    //show add vehicle window
    /*
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
    */

    $('#routeAssignDriver').click(function (e) {
        var $lotInfoWindow = $('#lotInfoWindow'), $routeAddDriverWindow = $('#routeAddDriverWindow');

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

        $lotInfoWindow.hide();
        $routeAddDriverWindow.show();
    });

    //time left countdowns
    $.each($('.lotTimeLeft'), function(i, el){
        var $this = $(this);
        var finalDate = $this.html();
        $this.countdown(finalDate, function(event){
            var totalHours = event.offset.totalDays * 24 + event.offset.hours;
            $this.html(event.strftime(totalHours + ':%M:%S'));
            if( event.elapsed ){
                console.log('elapsed');
                $.ajax({
                    method: 'POST',
                    url: '/solpro/solportal/web/app_dev.php/lotAuctionEnd',
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
            url: '/solpro/solportal/web/app_dev.php/lotsPrices',
            cache: false
        }).done(function( data ){
            console.log(data);
            if( !jQuery.isEmptyObject(data.lots) ){
                $('.lotCurrentPrice').each(function(){
                    var _id = parseInt($(this).attr('id'));
                    if( data.lots[ _id ] != parseInt($(this).html()) ){
                        $(this).html(data.lots[ _id ]);
                    }
                });
            }
        });
    };

    setInterval( updateLotPrices, 3000 );
});
