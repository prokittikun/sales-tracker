    </div><!-- /.container-fluid -->
</main>

<!-- ============================================================
     Bootstrap 5 Bundle (includes Popper)
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ============================================================
     jQuery - Required by Select2
     ============================================================ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- ============================================================
     Select2 - Searchable dropdowns
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- ============================================================
     Auto-dismiss flash alerts after 5 seconds
     ============================================================ -->
<script>
(function () {
    'use strict';

    // Auto-dismiss alerts
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // Confirm before delete
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var msg = el.getAttribute('data-confirm') || 'คุณแน่ใจหรือไม่?';
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // Confirm delete forms (forms with class .form-delete)
    document.querySelectorAll('form.form-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var msg = form.getAttribute('data-confirm') || 'คุณต้องการลบรายการนี้ใช่หรือไม่?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Tooltip initialization
    var tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Popover initialization
    var popoverEls = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverEls.forEach(function (el) {
        new bootstrap.Popover(el);
    });

    // Select2 initialization for searchable dropdowns
    setTimeout(function() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select-searchable').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%'
            });
        } else {
            console.warn('Select2 not loaded yet, retrying...');
            // Retry after 500ms if Select2 not ready
            setTimeout(arguments.callee, 500);
        }
    }, 200);
})();
</script>

<!-- ============================================================
     Initialize Select2 again at document end to ensure it loads
     ============================================================ -->
<script>
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSelect2);
} else {
    initSelect2();
}

function initSelect2() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        jQuery(function($) {
            $('.select-searchable').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%'
            });
            console.log('Select2 initialized successfully');
        });
    } else {
        console.error('jQuery or Select2 not available');
    }
}
</script>

<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>

</body>
</html>
