<!-- jQuery Core -->
<script src="{{ asset('vendor/material-ui/js/plugins/jquery/jquery.min.js') }}"></script>

<!-- jQuery Plugins -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="{{ asset('vendor/material-ui/js/plugins/form/jquery.form.min.js') }}"></script>

<!-- Bootstrap Core -->
<script src="{{ asset('vendor/material-ui/js/core/popper.min.js') }}"></script>
<script src="{{ asset('vendor/material-ui/js/core/bootstrap.min.js') }}"></script>

<!-- Perfect Scrollbar & Smooth Scrollbar -->
<script src="{{ asset('vendor/material-ui/js/plugins/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('vendor/material-ui/js/plugins/smooth-scrollbar.min.js') }}"></script>

<!-- SweetAlert -->
<script src="{{ asset('vendor/material-ui/js/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('vendor/material-ui/js/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>

<!-- Material Dashboard Core -->
<script src="{{ asset('vendor/material-ui/js/material-dashboard.min.js') }}"></script>

<!-- Datatables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- ================= ADD TOASTR CSS/JS HERE ================= -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
<!-- ======================================================== -->

<script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Lain-lain -->
<script src="https://malsup.github.io/jquery.blockUI.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.0/dropzone.js"></script>
<script src="{{ asset('vendor/material-ui/js/plugins/ckeditor/ckeditor.js') }}"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.11.2/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<!-- X-editable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.1/bootstrap-editable/js/bootstrap-editable.min.js"></script>

<!-- FullCalendar -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/bootstrap5@6.1.7/index.global.min.js'></script>

<script>
    if (typeof $ !== 'undefined') {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        console.log('✅ CSRF setup aktif');
    } else {
        console.warn('⚠️ jQuery belum termuat — CSRF setup gagal');
    }
</script>
@stack('scripts')
