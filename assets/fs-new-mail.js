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

    // получение городов Украины  по изменению ввода данных пользователем
    $(document).on('keypress keyup click', '[name="fs_city"]', function () {

        const el = $(this);
        const cityName = $(this).val();
        let delMethod = $("[name='fs_delivery_methods']").val();

        // Если поле является чекбоксом или радиокнопкой
        if ($("[name='fs_delivery_methods']").attr('type') == 'radio' || $("[name='fs_delivery_methods']").attr('type') == 'checkbox') {
            delMethod = $("[name='fs_delivery_methods']:checked").val()
        }
        
        console.log(delMethod);

        if (checkDelivery(delMethod)) {
            $.ajax({
                url: fShop.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fs_get_city',
                    'cityName': cityName
                },
                beforeSend: function () {

                }
            }).done(function (res) {
                console.log(res);
                if (res.success) {
                    el.parent().find('[data-fs-element="select-delivery-city"]').remove();
                    el.parent().find('.errors').remove();
                    el.parent().append(res.data.html);
                } else {
                    el.parent().find('[data-fs-element="select-delivery-city"]').remove();
                    el.parent().find('.errors').remove();
                    el.parent().append('<p class="errors text-danger">' + res.data.msg + '</p>');
                    $("[name=\"fs_delivery_number\"]").val("");
                }

            })
        }


    });


    //получение отделений города по клику на дропдовн с городами
    $(document).on('click', '[data-fs-element="select-delivery-city"] li', function () {
        let el = $(this);
        $('[name="fs_city"]').val(el.text());
        $('[data-fs-element="select-delivery-city"]').fadeOut();
        $.ajax({
            type: 'POST',
            url: fShop.ajaxurl,
            data: {
                "action": "fs_get_warehouses",
                "ref": el.data("ref"),
                "cityName": el.data("name")
            },
            success: function (res) {
                console.log(res);
                let delNumEl = $("[name=\"fs_delivery_number\"]");
                if (res.success) {
                    delNumEl.parent().find('[data-fs-element="select-warehouse"]').remove();
                    delNumEl.parent().find('.errors').remove();
                    delNumEl.parent().append(res.data.html);
                    $("[data-fs-element=\"delivery-cost\"]").html(res.data.deliveryCost);
                    $("[data-fs-element=\"total-amount\"]").html(res.data.totalAmount);
                } else {
                    delNumEl.parent().find('[data-fs-element="select-warehouse"]').remove();
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