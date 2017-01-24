(function ($) {
    $(function ($) {
        app_path = '/';
//	$('#trips-list .courier').on('click', function(){console.log($('#trips-list .courier option:selected').val());});
        $.getJSON("regions.json", function (data) {
            $.each(data, function (key, val) {
                $('.region').append('<option value=' + val.region_id + '>' + val.region_name + '</option>');
                //	console.log(val);
            });
        });
        $.getJSON("couriers.json", function (data) {
            $('.courier').html('<option selected disabled>Выберите курьера</option>');
            $.each(data, function (key, val) {
                $('.add-trip .courier, .trips-list .courier').append('<option value=' + val.courier_id + '>' + val.courier_lastname + ' ' + val.courier_firstname + ' ' + val.courier_middlename + '</option>');
                //	console.log(val);
            });
        });
        $('.courier').on('change',
            function () {
                $('.trips tbody').html('');
                courier_id = $('.trips-list .courier option:selected').val();
                json_path = app_path + courier_id + "/trips.json";
                console.log(json_path); //yyyy-mm-dd
                $.getJSON(json_path, function (data) {
                    console.log(data);
                    $.each(data, function (key, val) {
                        $('.trips tbody').append('<tr><td>' + val.trip_id + '</td><td>' + val.region_name + '</td><td>' + val.trip_departure + '</td></tr>');
                        console.log(val);
                    });
                });
            });
        $('.region, .departure').on('change',
            function () {
                $('.courier').html('<option selected disabled>Выберите курьера</option>');
                region_id = $('.region').val();
                departure = $('.departure').val();
                if (( region_id != null) && ( departure != '')) {
                    json_path = app_path + region_id + '/' + departure + '/couriers.json';
                    console.log(json_path);
                }
                $.getJSON(app_path + region_id + '/' + departure + '/couriers.json', function (data) {
                    $('.courier').html('<option selected disabled>Выберите курьера</option>');
                    $.each(data, function (key, val) {
                        $('.courier').append('<option value=' + val.courier_id + '>' + val.courier_fio + '</option>');
                        console.log(val);
                    });
                });
            }
        );
        $('.add-trip').on('click', function () {
            region_id = $('.region').val();
            courier_id = $('.courier').val();
            trip_departure = $('.departure').val();
            alert(trip_departure);
            $.post( app_path + 'record.json', { region_id: region_id, courier_id: courier_id, trip_departure: trip_departure })
                .done(function( data ) {
                    alert( "Data Loaded: " + data );
            });
        });
    });
})(jQuery);