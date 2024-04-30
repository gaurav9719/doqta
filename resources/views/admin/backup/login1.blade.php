<!DOCTYPE html>
<html lang="en">
    
<!-- Mirrored from material-admin-pro.startbootstrap.com/app-auth-login-styled-1.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 10 Apr 2024 05:20:13 GMT -->
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Admin Login | {{env('APP_NAME')}}</title>
        <!-- Load Favicon-->
        <link rel="shortcut icon" type="image/jpg" href="{{asset('assets/img/logo/favicon3.png')}}"/>
        <!-- Load Material Icons from Google Fonts-->
        <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet" />
        <!-- Roboto and Roboto Mono fonts from Google Fonts-->
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Mono:400,500" rel="stylesheet" />
        <!-- Load main stylesheet-->
        <link href="{{asset('assets/css/styles.css')}}" rel="stylesheet" />
    </head>
    <body class="bg-pattern-waihou">
        <!-- Layout wrapper-->
        <div id="layoutAuthentication">
            <!-- Layout content-->
            <div id="layoutAuthentication_content">
                <!-- Main page content-->
                <main>
                    <!-- Main content container-->
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-10 col-10 col-12">
                                <div class="card card-raised shadow-10 mt-5 mt-xl-10 mb-4">
                                    <div class="row g-0">
                                        <div class="col-md12 col-md-12">
                                            <div class="card-body p-5">
                                                <!-- Auth header with logo image-->
                                                <div class="text-center">
                                                    <img class="mb-3" src="{{asset('assets/img/icons/background.svg')}}" alt="..." style="height: 48px" />
                                                    <h1 class="display-5 mb-0">Login</h1>
                                                    <div class="subheading-1 mb-5">to continue to app</div>
                                                </div>
                                                <!-- Login submission form-->
                                                <form class="mb-5">
                                                    <div class="mb-4">
                                                        <label for="Email">Email</label>
                                                        <input class="w-100" label="Username" name="email" outlined=""></input></div>
                                                    <div class="mb-4"><mwc-textfield class="w-100" label="Password" outlined="" icontrailing="visibility_off" type="password"></mwc-textfield></div>
                                                    <div class="d-flex align-items-center">
                                                        <mwc-formfield label="Remember password"><mwc-checkbox></mwc-checkbox></mwc-formfield>
                                                    </div>
                                                    <div class="form-group d-flex align-items-center justify-content-between mt-4 mb-0">
                                                        <a class="small fw-500 text-decoration-none" href="app-auth-password-basic.html">Forgot Password?</a>
                                                        <a class="btn btn-primary" href="index-2.html">Login</a>
                                                    </div>
                                                </form>
                                                <!-- Auth card message-->
                                                <div class="text-center"><a class="small fw-500 text-decoration-none" href="app-auth-register-basic.html">New User? Create an account!</a></div>
                                            </div>
                                        </div>
                                        <!-- Background image column using inline CSS-->
                                        <!-- <div class="col-lg-7 col-md-6 d-none d-md-block" style="background-image: url('#'); background-size: cover; background-repeat: no-repeat; background-position: center"></div> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            <!-- Layout footer-->
            <div id="layoutAuthentication_footer"></div>
        </div>
        <!-- Load Bootstrap JS bundle-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <!-- Load global scripts-->
        <script type="module" src="{{ asset('assets/js/material.js')}}"></script>
        <script src="{{asset('assets/js/scripts.js')}}"></script>

        <script src="https://assets.startbootstrap.com/js/sb-customizer.js"></script>
        <!-- <sb-customizer project="material-admin-pro"></sb-customizer> -->
    <!-- <script>(function(){if (!document.body) return;var js = "window['__CF$cv$params']={r:'872047a24d3244b4',t:'MTcxMjcyNjM0NC4zMTkwMDA='};_cpo=document.createElement('script');_cpo.nonce='',_cpo.src='cdn-cgi/challenge-platform/h/b/scripts/jsd/bcc5fb0a8815/main.js',document.getElementsByTagName('head')[0].appendChild(_cpo);";var _0xh = document.createElement('iframe');_0xh.height = 1;_0xh.width = 1;_0xh.style.position = 'absolute';_0xh.style.top = 0;_0xh.style.left = 0;_0xh.style.border = 'none';_0xh.style.visibility = 'hidden';document.body.appendChild(_0xh);function handler() {var _0xi = _0xh.contentDocument || _0xh.contentWindow.document;if (_0xi) {var _0xj = _0xi.createElement('script');_0xj.innerHTML = js;_0xi.getElementsByTagName('head')[0].appendChild(_0xj);}}if (document.readyState !== 'loading') {handler();} else if (window.addEventListener) {document.addEventListener('DOMContentLoaded', handler);} else {var prev = document.onreadystatechange || function () {};document.onreadystatechange = function (e) {prev(e);if (document.readyState !== 'loading') {document.onreadystatechange = prev;handler();}};}})();</script><script defer src="https://static.cloudflareinsights.com/beacon.min.js/v84a3a4012de94ce1a686ba8c167c359c1696973893317" integrity="sha512-euoFGowhlaLqXsPWQ48qSkBSCFs3DPRyiwVu3FjR96cMPx+Fr+gpWRhIafcHwqwCqWS42RZhIudOvEI+Ckf6MA==" data-cf-beacon='{"rayId":"872047a24d3244b4","version":"2024.3.0","token":"6e2c2575ac8f44ed824cef7899ba8463"}' crossorigin="anonymous"></script> -->
</body>

<!-- Mirrored from material-admin-pro.startbootstrap.com/app-auth-login-styled-1.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 10 Apr 2024 05:20:13 GMT -->
</html>
