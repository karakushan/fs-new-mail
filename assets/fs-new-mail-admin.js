(function ($) {
    $(document).on('change input', '[name="fs_nm_from_city"]', function (event) {
        let el = $(this);
        let input = el.val();
        if (input.length < 2) return;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                'action': 'fs_nm_city_code',
                'input': input
            },
            success: function (res) {
                if (res.success) {
                    el.parent().find('select').remove();
                    el.parent().append(res.data.html);
                }
            }
        });
    });

    $(document).on('change', '[name="select-from-nm-city"]', function (event) {
        $('[name="fs_nm_from_city_id"]').val($(this).val());
        $(this).remove();
    });
})(jQuery);