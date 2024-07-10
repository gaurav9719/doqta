<!DOCTYPE html>
<html lang="en">
@include('admin.layouts.sections.head')

<body class="nav-fixed" style="background-color: rgb(241, 233, 255);">
        <!-- Top app bar navigation menu-->
        <nav class="top-app-bar navbar navbar-expand navbar-dark bg-dark">
                @include('admin.layouts.sections.header')
        </nav>
        <!-- Layout wrapper-->
        <div id="layoutDrawer">
                <!-- Layout navigation-->
                @include('admin.layouts.sections.navbar')
                <!-- Layout content-->
                <div id="layoutDrawer_content">
                        <!-- Main page content-->
                        @yield('content')
                        <!-- Footer-->
                        <!-- Min-height is set inline to match the height of the drawer footer-->
                        @include('admin.layouts.sections.footer')
                </div>
        </div>
        @include('admin.layouts.sections.scripts')
</body>

</html>