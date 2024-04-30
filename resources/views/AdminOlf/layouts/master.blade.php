<!DOCTYPE html>
<html lang="en">

@include('Admin.layouts.partials.header')

<body>

    <div class="loader"></div>

    <div id="app">

        <div class="main-wrapper main-wrapper-1">
            @if(Auth::user())
            @include('Admin.layouts.partials.navbar')

            @include('Admin.layouts.partials.sidebar')
            @endif
            <div class="main-content">

                @yield('main_content')

            </div>

            @include('Admin.layouts.partials.footer')
</body>

</html>