<!DOCTYPE html>
<html lang="en">
    
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Admin Login | {{env('APP_NAME')}}</title>
        <!-- Load Favicon-->
        <link rel="shortcut icon" type="image/jpg" href="{{asset('assets/img/logo/favicon.png')}}"/>
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
                            <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-8">
                                <div class="card card-raised shadow-10 mt-5 mt-xl-10 mb-4">
                                    <div class="card-body p-5" style="background-color: #e5e5e5;">

                                        <div class="text-center">
                                          <img src="{{asset('assets/img/logo/favicon.png')}}" style="height: 70px;" alt="logo"><br>
                                          <img src="{{ asset('assets/img/logo/logo.svg') }}" style="height: 60px;" alt="logo">
                                          <h4 class="mt-4 mb-4 pb-1">Admin Login</h4>
                                        </div>
                                        @if ($errors->any())
                                            @foreach ($errors->all() as $error)
                                                <div class="alert alert-danger" role="alert" >
                                                        {{ $error }}
                                                </div>
                                            @endforeach
                                        @endif

                                        @if (session('message'))
                                            <div class="alert alert-danger" role="alert">
                                                    {{ session('message') }}
                                            </div>
                                        @endif


                                        <form action="" method="post">
                                            @csrf
                                          <p>Please login to Access Admin Panel</p>
                        
                                          <div  class="form-outline mb-4">
                                              <label class="form-label" for="form2Example11">Email</label>
                                            <input type="email" name="email" id="form2Example11" class="form-control"
                                              placeholder="Enter email address" value="{{old('email')}}" />
                                          </div>
                        
                                          <div  class="mb-4">
                                              <label class="form-label" for="form2Example22">Password</label>
                                              <div class="input-group mb-3">
                                                <input class="form-control" type="password" id="inputPassword" name="password" placeholder="Password" value="">
                                                <span class="input-group-text clickable" type="visible" id="eyeIcon" onclick="change()"><i class="material-icons" >visibility</i></span>
                                            </div>   
                                          <div class="text-center pt-1 mb-5 pb-1">
                                            <button class="btn btn-primary btn-block fa-lg gradient-custom-2 mb-3" type="submit">Log in</button>
                                          </div>
                        
                                          
                        
                                        </form>
                        
                                      </div>
                                </div>
                                <!-- Auth card message-->
                                <!-- <div class="text-center mb-5"><a class="small fw-500 text-decoration-none link-white" href="app-auth-register-basic.html">Need an account? Sign up!</a></div> -->
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            <!-- Layout footer-->
            <div id="layoutAuthentication_footer">
                <!-- Auth footer-->
                <!-- <footer class="p-4">
                    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between small">
                        <div class="me-sm-3 mb-2 mb-sm-0"><div class="fw-500 text-white">Copyright © Your Website 2023</div></div>
                        <div class="ms-sm-3">
                            <a class="fw-500 text-decoration-none link-white" href="#!">Privacy</a>
                            <a class="fw-500 text-decoration-none link-white mx-4" href="#!">Terms</a>
                            <a class="fw-500 text-decoration-none link-white" href="#!">Help</a>
                        </div>
                    </div>
                </footer> -->
            </div>
        </div>
        <!-- Load Bootstrap JS bundle-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <!-- Load global scripts-->
        <script type="module" src="{{ asset('assets/js/material.js')}}"></script>
        <script src="{{asset('assets/js/scripts.js')}}"></script>
        <script src="https://assets.startbootstrap.com/js/sb-customizer.js"></script>


        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>  
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.js"></script>


        <script>
            function change(){
                
                
                html1=`<i class="material-icons" >visibility</i>`;
                html2=`<i class="material-icons" >visibility_off</i>`;
                var icon = document.getElementById('eyeIcon')
                var passwordInput = document.getElementById('inputPassword');
                console.log(passwordInput.type);
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.innerHTML=html1;
                } else {
                    passwordInput.type = 'password';
                    icon.innerHTML=html2;
                    
                }
                // document.getElementById('eyeIcon').innerHTML = html1;
            }
        </script>

</body>

</html>
