(function ($) {
    'use strict';

    // Проверяет входит ли выбраный способ доставки к способам доставки Новой почты
    function checkDelivery(delMethod) {
        if (delMethod == fsNmOptions.pochtomatId || delMethod == fsNmOptions.warehouseId) {
            return true;
        } else {
            return false;
        }
    }

    var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    $(document).on('click', '[name="fs_city"]', function (event) {
        if ($('[data-fs-element="select-delivery-city"]').length > 0) {
            $('[data-fs-element="select-delivery-city"]').fadeIn();
        }
    });

    // получение городов Украины по изменению ввода данных пользователем
    $(document).on('input', '[name="fs_city"]', function () {
        const el = $(this);
        const cityName = $(this).val();
        let delMethod = $("[name='fs_delivery_methods']").val();

        $('[name="fs_delivery_number"]').val('')

        if (cityName.trim().length < 3) return;

        // Если поле является чекбоксом или радиокнопкой
        if ($("[name='fs_delivery_methods']").attr('type') == 'radio' || $("[name='fs_delivery_methods']").attr('type') == 'checkbox') {
            delMethod = $("[name='fs_delivery_methods']:checked").val()
        }

        if (checkDelivery(delMethod)) {
            delay(function () {
                $.ajax({
                    url: fShop.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fs_get_city',
                        'cityName': cityName
                    },
                    beforeSend: function () {
                        $('[name="fs_city"]')
                            .parent()
                            .append('<img src="/wp-content/plugins/fs-new-mail/assets/img/preloader.svg" data-fs-element="select-city-loader" class="loader">')
                    }
                }).done(function (res) {
                    if (res.success) {
                        $('[data-fs-element="select-city-loader"]').remove()
                        el.parent().find('[data-fs-element="select-delivery-city"]').remove();
                        el.parent().find('.errors').remove();
                        let listCitiesHtml = '';
                        if (res.data.cities.length > 0) {
                            res.data.cities.forEach(function (city) {
                                listCitiesHtml += '<li class="select-city-item" data-ref="' + city.ref + '">' + city.name + '</li>';
                            });
                        }
                        if ($('[data-fs-element="select-delivery-city"]').length !== 0) {
                            $('[data-fs-element="select-delivery-city"]').html(listCitiesHtml);
                        } else {
                            let citySelector = $(document).find('#fs_city');
                            citySelector.after('<ul class="nm-city" data-fs-element="select-delivery-city">' +
                                listCitiesHtml +
                                '</ul>');
                        }
                    } else {
                        el.parent().find('[data-fs-element="select-delivery-city"]').remove();
                        el.parent().find('.errors').remove();
                        el.parent().append('<p class="errors text-danger">' + res.data.msg + '</p>');
                        $("[name=\"fs_delivery_number\"]").val("");
                    }

                })
            }, 1000);


        }


    });


    //получение отделений города по клику на дропдовн с городами
    $(document).on('click', '[data-fs-element="select-delivery-city"] li', function () {
        let el = $(this);
        $('[name="fs_city"]').val(el.text());
        $('[data-fs-element="select-warehouse"]').remove();
        $('[data-fs-element="select-delivery-city"]').fadeOut();
        $('[name="fs_delivery_number"]').val('');
        $.ajax({
            type: 'POST',
            url: fShop.ajaxurl,
            beforeSend: function () {
                $(document).trigger('fs_before_get_warehouses', {
                    "ref": el.data("ref"),
                    "cityName": el.data("name")
                });
                $('[name="fs_delivery_number"]')
                    .parent()
                    .append('<img src="/wp-content/plugins/fs-new-mail/assets/img/preloader.svg" data-fs-element="select-warehouse-loader" class="loader">')
            },
            data: {
                "action": "fs_get_warehouses",
                "ref": el.data("ref"),
                "cityName": el.data("name")
            },
            success: function (res) {
                $(document).trigger('fs_after_get_warehouses', {
                    "ref": el.data("ref"),
                    "cityName": el.data("name"),
                    ...res
                });
                $('[data-fs-element="select-warehouse-loader"]').remove();
                let delNumEl = $("[name=\"fs_delivery_number\"]");
                if (res.success) {
                    $('[data-fs-element="select-warehouse"]').remove();
                    delNumEl.parent().find('.errors').remove();

                    let warehousesHtml = '';
                    if (res.data.warehouses.length > 0) {
                        res.data.warehouses.forEach(function (warehouse) {
                            warehousesHtml += '<li class="select-city-item" data-ref="' + warehouse.ref + '">' + warehouse.description + '</li>';
                        });
                    }

                    let warehouseSelector = $('[name="fs_delivery_number"]');
                    if ($('[data-fs-element="select-warehouse"]').length !== 0) {
                        $('[data-fs-element="select-warehouse"]').html(warehousesHtml);
                    } else {
                        warehouseSelector.after('<ul class="nm-city" data-fs-element="select-warehouse">' +
                            warehousesHtml +
                            '</ul>');
                    }

                    if ($('#fs-shipping-fields').length > 0)
                        $('#fs-shipping-fields').append(res.data.html);

                    $("[data-fs-element=\"delivery-cost\"]").html(res.data.deliveryCost);
                    $("[data-fs-element=\"total-amount\"]").html(res.data.totalAmount);
                } else {
                    $('[data-fs-element="select-warehouse"]').remove();
                    delNumEl.parent().find('.errors').remove();
                    delNumEl.parent().append('<p class="errors text-danger">' + res.data.msg + '</p>');
                    delNumEl.val("");
                }
            }
        });
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
            $("#nm-dnum,#nm-city").fadeOut();
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
            console.log(data);
            var delNum = $("[name='fs_delivery_number']").first();
            if (delNum.next().hasClass('nm-dnum')) {
                delNum.next().html(data);
            } else {
                delNum.after("<ul class=\"nm-dnum\" data-fs-element=\"select-delivery-num\">" + data + "</ul>");
            }
            $("#nm-dnum").fadeIn();
        })
    }

    // клик одному элементу списка отделений
    $(document).on('click', '[data-fs-element="select-warehouse"] li', function (event) {
        $("[name='fs_delivery_number']").val($(this).text());
        $(this).parent().fadeOut();
    });

    // показываем список ранее скрытых отделений
    $(document).on('click', '[name="fs_delivery_number"]', function (event) {
        $("[data-fs-element=\"select-warehouse\"]").fadeIn();
    });
})(jQuery);