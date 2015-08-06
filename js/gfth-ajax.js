/**
 * Created by jrogers on 30/07/15.
 */
jQuery(document).ready(function ($) {
    $('#form_add_product').submit(function () {

        $('#gfth_loading').show();
        $('#gfth_loading_text').show();
        $('#product_submit').attr('disabled', true);

        var form_data = new FormData();
        form_data.append('csv', $('input[type=file]')[1].files[0]);
        form_data.append('test', $("input:radio[name ='test']:checked").val());
        form_data.append('do', 'upload_products');
        form_data.append('action', 'gfth_get_results');
        form_data.append('gfth_nonce', gfth_vars.gfth_nonce);



        $.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                $('#importresults').html(response);
                $('#gfth_loading').hide();
                $('#gfth_loading_text').hide();
                $('#product_submit').attr('disabled', false);
            }});

        return false;
    });

    $('#form_download_prices').submit(function (event) {
        $('#gfth_loading_download').show();
        $('#gfth_loading_download_text').show();
        $('#price_upload').attr('disabled', true);
        $('#price_download').attr('disabled', true);

        var form_data = new FormData();
        form_data.append('do', 'price_download');
        form_data.append('action', 'gfth_get_results');
        form_data.append('gfth_nonce', gfth_vars.gfth_nonce);

        event.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                $('#gfth_loading_download').hide();
                $('#gfth_loading_download_text').hide();
                $('#price_upload').attr('disabled', false);
                $('#price_download').attr('disabled', false);
                //var w = window.open('http://giftsfromtheheart.staging.wpengine.com/wp-content/uploads/csv-downloads/product-prices.csv', 'CSVDownload');
                var w = window.open(response, 'CSVDownload');
            }});

        return false;

    });

    $('#form_misc').submit(function (event) {

        if (!confirm('Are you SURE you want to delete ALL the drafts?')) {
            return false;
        }
        $('#gfth_loading_delete').show();
        $('#gfth_loading_delete_text').show();
        $('#delete_drafts').attr('disabled', true);

        var form_data = new FormData();
        form_data.append('do', 'delete_drafts');
        form_data.append('action', 'gfth_get_results');
        form_data.append('gfth_nonce', gfth_vars.gfth_nonce);

        event.preventDefault();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                $('#gfth_loading_delete').hide();
                $('#gfth_loading_delete_text').hide();
                $('#delete_drafts').attr('disabled', false);
                alert(response);
            }});

        return false;

    });

    $('#form_upload_prices').submit(function () {

        $('#gfth_loading_upload').show();
        $('#gfth_loading_upload_text').show();
        $('#price_upload').attr('disabled', true);

        var form_data = new FormData();
        form_data.append('csv', $('input[type=file]')[0].files[0]);
        form_data.append('do', 'update_prices');
        form_data.append('action', 'gfth_get_results');
        form_data.append('gfth_nonce', gfth_vars.gfth_nonce);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response) {
                $('#update_prices').html(response);
                $('#gfth_loading_upload').hide();
                $('#gfth_loading_upload_text').hide();
                $('#price_upload').attr('disabled', false);
            }});

        return false;
    });
});

