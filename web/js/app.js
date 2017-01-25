(function ($) {
    $(function ($) {

        // Директория приложения (реализовано частично, нужно размещать в корне домена)
        app_path = '/';

        // Список регионов при запуске
        $.getJSON("regions.json", function (data) {
            $.each(data, function (key, val) {
                $('.region').append('<option value=' + val.region_id + '>' + val.region_name + '</option>');
                //	console.log(val);
            });
        });

        // Список курьеров при запуске
        $.getJSON("couriers.json", function (data) {
            $('.courier').html('<option selected disabled>Выберите курьера</option>');
            $.each(data, function (key, val) {
                $('.add-trip .courier, .trips-list .courier').append('<option value=' + val.courier_id + '>' + val.courier_lastname + ' ' + val.courier_firstname + ' ' + val.courier_middlename + '</option>');
            });
        });

        // Обновление списка свободных курьеров при переключении региона и даты прибытия
        $('.region, .departure').on('change',
            function () {
                UpdateFreeCouriers();
            }
        );

        // Обновление списка свободных курьеров
        function UpdateFreeCouriers() {
            $('.courier').html('<option selected disabled>Выберите курьера</option>');
            region_id = $('.region').val();
            departure = $('.departure').val();
            if (( region_id != null) && ( departure.length > 0)) {
                json_path = app_path + region_id + '/' + departure + '/couriers.json';
                $.getJSON(app_path + region_id + '/' + departure + '/couriers.json', function (data) {
                    $('.courier').html('<option selected disabled>Выберите курьера</option>');
                    $.each(data, function (key, val) {
                        $('.courier').append('<option value=' + val.courier_id + '>' + val.courier_fio + '</option>');
                    });
                });
            }
        }

        // Добавление поездки
        $('.add-trip').on('click', function () {
            region_id = $('#add-trip .region').val();
            courier_id = $('#add-trip .courier').val();
            trip_departure = $('#add-trip .departure').val();

            // Для удобства, если диапазон дат на вкладке списка поездок ещё не выбирали,
            // то подставляем дату поездки
            from = $('#trips .trips-from').val();
            till = $('#trips .trips-till').val();
            if ((from.length < 1) || (till.length < 1)) {
                $('#trips .trips-from').val(trip_departure);
                $('#trips .trips-till').val(trip_departure);
            }

            $.post(app_path + 'record.json', {
                region_id: region_id,
                courier_id: courier_id,
                trip_departure: trip_departure
            })
                .done(function (data) {
                    UpdateTrips();
                    alert('Поездка добавлена');

                });
        });

        // Обновление списка поездок при переключении даты
        $('#trips .trips-from, #trips .trips-till').on('change', function () {
            UpdateTrips();
        });

        //Обновление списка поездок
        function UpdateTrips() {
            from = $('#trips .trips-from').val();
            till = $('#trips .trips-till').val();
            if (from.length > 0 || till.length > 0) {
                $('#trips .trips-wrapper').html('<div class="trip row"><div class="courier-fio tlabel col-md-4 col-sm-4">ФИО курьера</div><div class="region-name tlabel col-md-4 col-sm-4">Регион</div><div class="trip-departure tlabel col-md-4 col-sm-4">Дата прибытия в регион</div></div>');
                if (till.length < 1) {
                    $('#trips .trips-till').val(from);
                    till = from;
                }
                if (from.length < 1) {
                    $('#trips .trips-from').val(till);
                    from = till;
                }
                $.getJSON(app_path + from + '/' + till + '/trips.json', function (data) {
                    $.each(data, function (key, val) {
                        trip_sel = 'trip-' + val.trip_id;
                        $('#trips .trips-wrapper').append('<div class="trip row ' + trip_sel + '"></div>');
                        $('.' + trip_sel).append('<div class="courier-fio col-md-4 col-sm-4">' + val.courier_fio + '</div>');
                        $('.' + trip_sel).append('<div class="region-name col-md-4 col-sm-4">' + val.region_name + '</div>');
                        $('.' + trip_sel).append('<div class="trip-departure col-md-4 col-sm-4">' + val.trip_departure + '</div>');
                    });
                });
            }
        }

        // Вкладки Bootstrap
        $('#myTab a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
})(jQuery);