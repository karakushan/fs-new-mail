(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
	 *
	 * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
	 *
	 * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    function checkDelivery() {
        var delMethod = $("[name='fs_delivery_methods']:checked").val();
        if (delMethod == fsNmOptions.pochtomatId || delMethod == fsNmOptions.warehouseId) {
            return true;
        } else {
            return false;
        }
    }

    if (checkDelivery()){
        $("[name=\"fs_adress\"]").fadeOut();
        $("[name=\"fs_delivery_number\"],[name=\"fs_city\"]").fadeIn();
    }

    //получение городов области Украины
    $(document).on('keypress keyup click', '[name="fs_city"]', function () {

        var el = $(this);
        var id = $(this).val();
        if (id.length > 1 && checkDelivery()) {
            $.ajax({
                url: FastShopData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fs_get_city',
                    'id': id
                },
                beforeSend: function () {

                }
            }).done(function (data) {
                if (data.length) {
                    $("#nm-city").fadeIn();
                    if (el.next().hasClass('nm-city')) {
                        el.next().html(data);
                    }

                    if (el.next().hasClass('nm-city') == false) {
                        el.after("<ul class=\"nm-city\" id=\"nm-city\">" + data + "</ul>");
                    }

                }
                if (data.length == 0) {
                    if (el.next().hasClass('nm-city')) {
                        el.next().html("<li>не знайдено відділень</li>");
                    } else {
                        el.after("<ul class=\"nm-city\" id=\"nm-city\"><li>не знайдено відділень</li></ul>");
                    }
                }

            })
        }


    });


    //получение отделений города по клику на дропдовн с городами
    $(document).on('click', '#nm-city li', function () {
        var el = $(this);
        var delMethod = $("[name='fs_delivery_methods']:checked").val();
        var city = el.data('value');
        $('[name="fs_city"]').val(city)
        $('#nm-city').fadeOut();
        getWarehouses(city, delMethod);
    });

    // показываем отделения по измененнию способа доставки
    $(document).on('change', '[name="fs_delivery_methods"]', function (event) {
        var el = $(this);
        if (checkDelivery()) {
            $("[name=\"fs_adress\"]").fadeOut();
            $("[name=\"fs_delivery_number\"],[name=\"fs_city\"]").fadeIn();
            var city = $("[name=\"fs_city\"]").val();
            if (city != '') {
                getWarehouses(city, el.val());
            }
        } else {
            $("[name=\"fs_adress\"]").fadeIn();
            $("#nm-dnum,#nm-city,[name=\"fs_delivery_number\"]").fadeOut();
        }
    });

    function getWarehouses(city, delMethod) {
        $.ajax({
            url: FastShopData.ajaxurl,
            type: 'POST',
            data: {
                action: 'fs_get_warehouses',
                'id': city,
                'type': delMethod
            },
            beforeSend: function () {
            }
        }).done(function (data) {
            var delNum = $("[name='fs_delivery_number']").first();
            if (delNum.next().hasClass('nm-dnum')) {
                delNum.next().html(data);
            } else {
                delNum.after("<ul class=\"nm-dnum\" id=\"nm-dnum\">" + data + "</ul>");
            }
            $("#nm-dnum").fadeIn();


        })
    }

    $(document).on('click', '#nm-dnum li', function (event) {
        $("[name='fs_delivery_number']").val($(this).data('value'));
        $(this).parent().fadeOut();
    });
    $(document).on('click', '[name="fs_delivery_number"]', function (event) {
        if ($(this).next().hasClass('nm-dnum')) {
            $(this).next().fadeIn();
        }

    });


})(jQuery);