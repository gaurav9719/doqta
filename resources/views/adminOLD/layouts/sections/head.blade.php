<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin Panel | {{env('APP_NAME')}}</title>
    <!-- Load Favicon-->
    <link rel="shortcut icon" type="image/jpg" href="{{asset('assets/img/logo/favicon.png')}}"/>

    <!-- Load Material Icons from Google Fonts-->
    <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet" />
    <!-- Load Simple DataTables Stylesheet-->
    <!-- <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" /> -->
    <!-- Roboto and Roboto Mono fonts from Google Fonts-->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Mono:400,500" rel="stylesheet" />
    <!-- Load main stylesheet-->
    <link href="{{asset('assets/css/styles.css')}}" rel="stylesheet" />


    <!-- Data Table -->
    <link href="https://cdn.datatables.net/1.11.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>  
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap5.min.js"></script>
    
    
    
    <!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.1/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script> -->


    <style>
        .badge {
            --bs-badge-padding-x: 0.65em;
            --bs-badge-padding-y: 0.35em;
            --bs-badge-font-size: 0.75em;
            --bs-badge-font-weight: 700;
            
            
            display: inline-block;
            padding: 5px 5px 2px 5px;
            font-size: var(--bs-badge-font-size);
            font-weight: var(--bs-badge-font-weight);
            line-height: 1;
            color: var(--bs-badge-color);
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
        }

        .text-bg-success {
            color: #fff!important;
            background-color: RGBA(25, 135, 84)!important;
            min-width: 50px;
        }

        .text-bg-warning {
            color: #000 !important;
            background-color: RGBA(255, 193, 7)!important;
        }

        .text-bg-danger {
            color: #fff!important;
            background-color: RGBA(220, 53, 69)!important;
        }

        .text-bg-info {
            color: #fff !important;
            background-color: #2aa700 !important;
            width: 69px;
        }

        .gradient-custom {
            /* background: linear-gradient(to right bottom, rgba(246, 211, 101, 1), rgba(253, 160, 133, 1)); */
            /* background: linear-gradient(180deg, rgba(15,15,149,1) 45%, rgba(0,0,0,1) 100%); */
            background: linear-gradient(180deg, rgba(15,15,149,1) 13%, rgba(0,0,0,1) 100%);
        }

        #profileClose {
            position: absolute;
            top: -5%;
            left: 103%;
            transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            border: none;
        }
    </style>

</head>