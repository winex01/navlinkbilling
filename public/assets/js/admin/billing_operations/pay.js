if (typeof pay != 'function') {
    $("[data-button-type=pay]").unbind('click');

    function pay(button) {
        // ask for confirmation before marking an item as paid
        var button = $(button);
        var route = button.attr('data-route');
    
        swal({
            title: "Warning",
            text: "Are you sure you want to mark this item paid?",
            icon: "warning",
            buttons: {
                cancel: {
                    text: "Cancel",
                    value: null,
                    visible: true,
                    className: "bg-secondary",
                    closeModal: true,
                },
                cash: {
                    text: "Cash",
                    value: 1,
                    visible: true,
                    className: "bg-success",
                },
                gcash: {
                    text: "Gcash",
                    value: 3,
                    visible: true,
                    className: "bg-info",
                },
            },
            dangerMode: true,
        }).then((value) => {
            if (value) {
                $.ajax({
                    url: route,
                    type: 'POST',
                    data: { payment_method: value },
                    success: function(result) {
                        if (result.msg) {
                            if (typeof crud !== 'undefined') {
                                crud.table.ajax.reload();
                            }
                            // Show a success notification bubble
                            new Noty({
                                type: result.type,
                                text: result.msg
                            }).show();
                            // Hide the modal, if any
                            $('.modal').modal('hide');
                        }
                    },
                    error: function(xhr) {
                        // Handle validation errors or other errors
                        if (xhr.status === 422) {
                            // Display validation errors to the user
                            var errors = xhr.responseJSON.errors;
                            errors.forEach(function(errorMsg) {
                                new Noty({
                                    text: errorMsg,
                                    type: 'error'
                                }).show();
                            });
                        } else {
                            // Handle other types of errors
                            swalError('Please contact the administrator.')
                        }
                    }
                });
            }
        });
    }
    
}