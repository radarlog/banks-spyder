$(function () {
    $(document).on('click', 'A.company-statuses-view', function () {
        $('#modal-company-name').text(this.dataset.name); //header

        $.get(this.dataset.href).done(function (data) {
            $('#modal-company-statuses').find('.modal-body').html(data);
        });
    });
});