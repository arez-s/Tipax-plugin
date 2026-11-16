document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.querySelector('[data-tipax-label-select-all]');
    var checkboxes = Array.prototype.slice.call(
        document.querySelectorAll('[data-tipax-label-order]:not(:disabled)')
    );
    var printSelected = document.querySelector('[data-tipax-print-selected-labels]');

    if (!selectAll && !printSelected) {
        return;
    }

    function selectedOrderIds() {
        return checkboxes
            .filter(function (checkbox) {
                return checkbox.checked;
            })
            .map(function (checkbox) {
                return checkbox.value;
            });
    }

    function refreshState() {
        var selected = selectedOrderIds();

        if (printSelected) {
            printSelected.disabled = selected.length === 0;
            printSelected.setAttribute('data-selected-count', String(selected.length));
        }

        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
            refreshState();
        });
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', refreshState);
    });

    if (printSelected) {
        printSelected.addEventListener('click', function () {
            var selected = selectedOrderIds();
            if (!selected.length) {
                window.alert(
                    typeof tipaxLabelAdmin !== 'undefined'
                        ? tipaxLabelAdmin.noSelection
                        : 'حداقل یک سفارش را انتخاب کنید.'
                );
                return;
            }

            if (typeof tipaxLabelAdmin === 'undefined') {
                return;
            }

            var url = new URL(tipaxLabelAdmin.printUrl, window.location.origin);
            url.searchParams.set('action', tipaxLabelAdmin.action);
            url.searchParams.set('order_ids', selected.join(','));
            url.searchParams.set('autoprint', '1');
            url.searchParams.set('_wpnonce', tipaxLabelAdmin.nonce);
            window.open(url.toString(), '_blank', 'noopener');
        });
    }

    refreshState();
});
