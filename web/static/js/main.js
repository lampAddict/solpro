function showMenuItems() {
    var x = document.getElementById("menu");
    if (x.className === "topnav") {
        x.className += " responsive";
    } else {
        x.className = "topnav";
    }
}

$( document ).ready(function(){

    if( window.location.pathname.replace(/\//g,'') == 'auction' ){ //solprosolportalwebapp_dev.phpauction
        //determine user timezone
        $.ajax({
            method: 'POST',
            url:    'timezone',
            data: {
                offset: -( new Date().getTimezoneOffset() / 60 )
            }
        })
            .done(
                function( response ){
                    if( response.result ){
                        location.reload();
                    }
                }
            );
    }
    
    //show/hide filters panel
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

    /* Various masked input */

    //masked input for driver add/edit window
    $driverPhone = $('#appbundle_driver_phone');
    if( $driverPhone ){
        $driverPhone.mask("9 (999) 999-99-99");
    }

    $driverLicense = $('#appbundle_driver_driverLicense');
    if( $driverLicense ){
        $.mask.definitions['~'] = '[а-яА-ЯёЁ0-9]';
        $driverLicense.mask("~~ ~~ 999999");
    }

    $driverBirthDate = $('#appbundle_driver_passport_date_issue');
    if( $driverBirthDate ){
        $driverBirthDate.mask("99.99.9999");
    }

    //masked input for vehicle add/edit window
    $vehicleRegNum = $('#appbundle_transport_regNum');
    if( $vehicleRegNum ){
        $.mask.definitions['~'] = '[авекмнорстухАВЕКМНОРСТУХ]';//А, В, Е, К, М, Н, О, Р, С, Т, У, Х
        $vehicleRegNum.mask("~999~~ 99?9");
    }

    $vehicleTrailerRegNum = $('#appbundle_transport_trailerRegNum');
    if( $vehicleTrailerRegNum ){
        $.mask.definitions['~'] = '[авекмнорстухАВЕКМНОРСТУХ]';//А, В, Е, К, М, Н, О, Р, С, Т, У, Х
        $vehicleTrailerRegNum.mask("~~9999 99?9");
    }

    $lotFilterTimeFrom = $('#lotFilterTimeFrom');
    if( $lotFilterTimeFrom ){
        $lotFilterTimeFrom.mask("99:99 99.99.9999");
    }

    $lotFilterTimeTo = $('#lotFilterTimeTo');
    if( $lotFilterTimeTo ){
        $lotFilterTimeTo.mask("99:99 99.99.9999");
    }

    $('#auctionTable .showLotRouteInfo').click(function(e){
        var $routeInfo = $(this).parent().parent().next().find('.lotInfoWindow');
        if( $routeInfo.is(':visible') ){
            $routeInfo.slideUp(1100);
        }
        else{
            $routeInfo.slideDown(1100);
        }
    });

    $('#auctionTable .btnCloseRouteInfo').click(function(){
        $(this).parent().parent().slideUp(1100);
    });

    $('#routesTable .showLotRouteInfo').click(function(e){
        var $routeInfo = $(this).parent().parent().next().find('.lotInfoWindow');

        var $ri = $routeInfo.clone(true).removeClass('dnone').addClass('mtop60');
        $('#routesPageContainer > .lotInfoWindow').remove();
        $('#routesPageContainer').append($ri);

        $('#routesTable').hide();
        $('#routesFilter').hide();

        var $routeAddDriverWindow = $ri.find('.routeAddDriverWindow');

        $routeAddDriverSelect = $routeAddDriverWindow.find('#routeAddDriverSelect');
        if( $routeAddDriverSelect ){
            $routeAddDriverSelect.addClass("chosen-select");
            $routeAddDriverSelect.chosen({no_results_text: "Ничего не найдено"});
        }

        $routeAddVehicleSelect = $routeAddDriverWindow.find('#routeAddVehicleSelect');
        if( $routeAddVehicleSelect ){
            $routeAddVehicleSelect.addClass("chosen-select");
            $routeAddVehicleSelect.chosen({no_results_text: "Ничего не найдено"});
        }

        $routeAddDriverWindow.data('routeId', $routeAddDriverWindow.parent().attr('data-routeId'));

        $ri.show();
    });

    $('#routesTable .btnCloseRouteInfo').click(function(){
        $(this).parent().parent().hide();
        $('#routesFilter').show();
        $('#routesTable').show();
    });

    $('#appbundle_transport_type').change(function(e){
        $(e.currentTarget).addClass('black');
    });

    //submit attach driver to route data
    $('.routeAddDriverWindow input[type="button"]').click(function(e){
        //Don't send request if none of the drivers selected
        if( $(e.currentTarget).parent().find('#routeAddDriverSelect').val() != 'Выберите водителя' ){
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
        }
    });

    //remove driver from route
    $('.routeDeleteDriver').click(function(e){
        var $this = $(e.currentTarget);
        var routeId = $this.parent().attr('data-routeId');
        $.ajax({
            method: 'POST',
            url: 'removeDriver',
            data: { route: routeId }
        })
        .done(function( response ){
            if( response.result ){
                //delete driver information from route info table
                $this.parent().find('.routeDriverName').html('');
                $this.parent().find('.routeDriverPassportData').html('');
                $this.parent().find('.routeDriverPhoneNumber').html('');

                $this.parent().find('.routeVehicleName').html('');
                $this.parent().find('.routeVehicleRegNum').html('');

                //delete driver's info from routes table
                //document.location.reload(true);

                $('#routesFilter').show();
                $('#routesTable').show();

                $('#routesPageContainer > .lotInfoWindow.mtop60').remove();

                var $routeTr = $('.route-'+routeId);
                //$routeTr.scrollTop();
                $('html, body').animate({
                    scrollTop: $routeTr.offset().top
                }, 800);
                $routeTr.find('.routeAssignedDriver').html('');
                $routeTr.find('.routeAttachedVehicle').html('');

                $routeTr.next().find('.routeDriverName').parent().hide();
                $routeTr.next().find('.routeVehicleName').parent().hide();
                $routeTr.next().find('.routeDeleteDriver').hide();
            }
        });
    });

    var lotAuctionEndQuery = [];

    //time left countdowns
    $.each($('.lotTimeLeft'), function(i, el){
        var $this = $(this);
        var finalDate = $this.html();
        $this.countdown(finalDate, function(event){
            var totalHours = event.offset.totalDays * 24 + event.offset.hours;
            $this.html(event.strftime(totalHours + ':%M:%S'));

            if(    event.type == 'finish'
                && event.elapsed
            ){
                //if it's time to start auction
                //@TODO rewrite code below for the case of multiple auctions start up which now're causes useless multiple page reload
                /*
                if( $this.parent().attr('class') == 'lotDoBid' ){
                    location.reload();
                }
                */
                //if auction has been ended
                //else{
                    //sendLotAuctionEndRequest($this);

                    //disable do bid button
                    $this.parent().siblings('.lotDoBid').find('.doBid').attr('disabled', 'disabled');

                    lotAuctionEndQuery.push( $this );
                //}
            }
        });//.on('finish.countdown', sendLotAuctionEndRequest($this));
    });

    var queryCheckId = setInterval( function(){checkLotAuctionEndQuery()}, 2000 );

    function checkLotAuctionEndQuery(){
        if( lotAuctionEndQuery.length > 0 ){
            $this = lotAuctionEndQuery.pop();
            sendLotAuctionEndRequest($this);
        }
        else{
            //clearInterval(queryCheckId);
        }
    }

    //lot auction end request
    function sendLotAuctionEndRequest($this){
        $.ajax({
             method: 'POST'
            ,url: 'lotAuctionEnd'
            ,data: { lot: $this.parent().siblings('.lotCurrentPrice').attr('id') }
            //,timeout: 2500
        })
        .done(function( response ){
            if( response.result ){
                //delete row with expired lot from auction table
                var $lot_tr = $this.parent().parent();
                $lot_tr.next().remove();
                $lot_tr.remove();
            }
        })
        .fail(function( response ){
            console.log('FAIL');
            console.log(response);
            //setTimeout( function(){sendLotAuctionEndRequest($this)}, 3000 );
        });
    }

    //click handlers for do bet buttons
    var bets = {};
    $('.doBid').each(function(){
        var $this = $(this);
        //submit data handler
        $this.parent().submit(function(e){

                e.preventDefault();

                var formSerialize = $(this).serialize();
                $.post('auction', formSerialize, function (response) {
                    console.log(response);
                    if( response.result ){
                        $lcp = $this.parent().parent().siblings('.lotCurrentPrice');
                        //set new current lot price
                        $lcp.text(response.price);
                        //highlight user's bet
                        if( !$lcp.hasClass('myBet') ){
                            $lcp.addClass('myBet');
                        }
                        //set new bet value
                        $this.parent().find('#appbundle_bet_value').val(response.bet);
                        //enable do bet button
                        $this.removeAttr('disabled');

                        //set current lot price in route full information window
                        $('#lpi_' + $lcp.attr('id')).html(response.price);
                        
                        //update time left timer
                        $tlt = $this.parent().parent().siblings('.lotTimeLeftTimer').find('.lotTimeLeft');
                        if(    response.prolongation
                            && response.prolongation > 0
                        ){
                            //'Y/m/d H:i:s'
                            $tlt.countdown( new Date((parseInt($tlt.attr('data-ts')) + response.prolongation)*1000).toISOString().replace(/\..+/g,'').replace(/-/g,'/').replace(/T/g,' ') );
                        }
                    }
                    else{
                        location.reload();
                    }
                }, 'JSON');
            }
        );
        //click `submit` button handler
        $this.click(function(){
            var _bet = parseInt( $this.siblings('#appbundle_bet').find('#appbundle_bet_value').val() ),
                _lcp = $this.parents('.lotDoBid').siblings('.lotCurrentPrice'),
                _price = _lcp.html(),
                _lotId = _lcp.attr('id'),
                betInArr = false;

            if( bets[_lotId] !== undefined ){
                if( bets[_lotId].indexOf(_bet) > 0 ){
                    betInArr = true;
                }
            }
            else{
                bets[_lotId] = [];
            }

            //in order to place bet it must conform following conditions
            if(    _bet > 0 //positive number
                && !betInArr //new bet
                && _bet <= parseInt(_price) - parseInt($this.attr('data-tradeStep')) //less than current lot price minus step of price reducing
                //lot is in auction
            ){
                $this.attr('disabled', 'disabled');
                bets[_lotId].push( _bet );

                $this.parent().submit();
            }
        });
    });

    //update lots prices routine
    var updateLotPrices = function(){
        if( window.location.pathname.replace(/\//g,'') == 'auction' ){ //solprosolportalwebapp_dev.phpauction
            $.ajax({
                url: 'lotsPrices',
                cache: false
            }).done(function( data ){
                console.log(data);
                var _uid = $('#auctionPageContainer').attr('data-user');
                if( !jQuery.isEmptyObject(data.lots) ){
                    var $lotsPrices = $('.lotCurrentPrice'),
                        lotPricesTableCount = $lotsPrices.length,
                        lotPricesDataCount = Object.keys(data.lots).length,
                        savedNumLotsPrices = $('body').data('numLotsPrices'),
                        pageReload = false;

                    if( savedNumLotsPrices == undefined ){
                        $('body').data('numLotsPrices', lotPricesDataCount);
                    }
                    else{
                        if( lotPricesDataCount != parseInt(savedNumLotsPrices) ){
                            pageReload = true;
                        }
                    }
                    
                    if( lotPricesTableCount != lotPricesDataCount ){
                        //check if some lots should be removed already
                        if( lotPricesTableCount > lotPricesDataCount ){
                            var _id;
                            for( var i=0; i<$lotsPrices.length; i++ ){
                                _id = parseInt($(this).attr('id'));
                                if(    _id
                                    &&
                                    (
                                           data.lots[ _id ] == null
                                        || data.lots[ _id ] == undefined
                                    )
                                ){
                                    $(this).parent().next().remove();
                                    $(this).parent().remove();
                                }
                            }
                        }
                        //reload page if need to show new lot prices data
                        else if(    lotPricesTableCount > 0
                                 && lotPricesDataCount > 0
                                 && lotPricesTableCount < lotPricesDataCount
                                 && pageReload
                        ){
                            location.reload();
                        }
                    }

                    $lotsPrices.each(function(){
                        var _id = parseInt($(this).attr('id'));
                        //highlight users bets
                        if(    data.lots[ _id ] !== null
                            && data.lots[ _id ] !== undefined
                        ){
                            if( _uid == data.lots[ _id ].owner ){
                                if( !$(this).hasClass('myBet') )
                                    $(this).removeClass('notMyBet').addClass('myBet');
                            }
                            else{
                                if( data.lots[ _id ].history.indexOf(_uid) >= 0 ){
                                    if( !$(this).hasClass('notMyBet') ){
                                        $(this).removeClass('myBet').addClass('notMyBet');
                                    }
                                }
                            }

                            //update lot price if needed
                            if( data.lots[ _id ].price != parseInt($(this).html()) ){
                                //main table on auction page
                                $(this).html(data.lots[ _id ].price);

                                //update 'next bet' input value
                                var price = parseInt(data.lots[ _id ].price), tradeStep = parseInt($(this).siblings('.lotDoBid').find('input.doBid').attr('data-tradeStep'));
                                if( price - tradeStep > 0 ){
                                    $(this).siblings('.lotDoBid').find('input#appbundle_bet_value').val((price - tradeStep));
                                }
                                else{
                                    $(this).siblings('.lotDoBid').find('input#appbundle_bet_value').val('');
                                }

                                //full lot information window
                                $('#lpi_' + _id).html(data.lots[ _id ].price + ' &#8381;');
                            }
                        }
                    });
                }
            });
        }
    };

    setInterval( updateLotPrices, 4000 );

    //set auction filters
    $('.btnSetAuctionFilter').click(function(e){

        var vt = [];
        $.each( $('#lotFilterVehicleTypes input'), function(i, el){
            if( $(el).is(':checked') )
                vt.push($( el ).attr('value'));
        });

        $.ajax({
             method: 'POST'
            ,url: 'auctionSetFilter'
            ,data: {
                type: 0,
                params: {
                     'status_active' : ($('#lotFilterTradeActive').is(':checked')?1:0)
                    ,'status_planned' : ($('#lotFilterPlanned').is(':checked')?1:0)
                    ,'region_from' : $('#lotFilterDirFrom').val()
                    ,'region_to' : $('#lotFilterDirTo').val()
                    ,'vehicle_types' : (vt.length > 0 ? vt : '')
                    ,'load_date_from': $('#lotFilterTimeFrom').val()
                    ,'load_date_to': $('#lotFilterTimeTo').val()
                }
            }
        })
        .done(function( response ){
            if( response.result ){
                location.reload();
            }
        })
        .fail(function( response ){
            console.log('FAIL');
            console.log(response);
        });
    });

    //unset auction filters
    $('.btnUnsetAuctionFilter').click(function(e){
        $.ajax({
            method: 'POST',
            url: 'auctionUnsetFilter'
        })
        .done(function( response ){
            if( response.result ){
                location.reload();
            }
        })
        .fail(function( response ){
            console.log('FAIL');
            console.log(response);
        });
    });
});
