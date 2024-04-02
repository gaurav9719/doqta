<footer class="main-footer">
    <div class="footer-left">
        <a href="templateshub.net">Templateshub</a></a>
    </div>
    <div class="footer-right">
    </div>
</footer>
</div>




</div>
<!-- General JS Scripts -->
<script src="{{ asset('admin_asset/js/app.min.js') }}"></script>
<!-- JS Libraies -->
<script src="{{ asset('admin_asset/bundles/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('admin_asset/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }} "></script>
<script src="{{ asset('admin_asset/bundles/jquery-ui/jquery-ui.min.js')}}"></script>
<!-- Page Specific JS File -->
<script src="{{ asset('admin_asset/js/page/datatables.js')}}"></script>
<!-- Template JS File -->
<script src="{{ asset('admin_asset/bundles/apexcharts/apexcharts.min.js') }}"></script>
<!-- Page Specific JS File -->
<script src="{{ asset('admin_asset/js/page/index.js') }}"></script>
<!-- Template JS File -->
<script src="{{ asset('admin_asset/js/scripts.js') }}"></script>
<!-- Custom JS File -->
<script src="{{ asset('admin_asset/js/custom.js') }}"></script>
<script src="{{ asset('admin_asset/js/sweetalert.min.js') }}"></script>

<script>
     function showLogoutConfirmation() {
        var isConfirmed = confirm("Are you sure you want to log out?");
        
        if (isConfirmed) {
            // If the user confirms, trigger the actual logout
            document.getElementById('logout-form').submit();
        }
    }
</script>