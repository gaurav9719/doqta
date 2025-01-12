<div class="container-fluid px-4">
    <!-- Drawer toggle button-->
    <button class="btn btn-lg btn-icon order-1 order-lg-0" id="drawerToggle" href="javascript:void(0);"><i class="material-icons">menu</i></button>
    <!-- Navbar brand-->
    <!-- <a class="navbar-brand me-auto" href="index-2.html"><div class="text-uppercase font-monospace">Material Admin Pro</div></a> -->
    <a class="navbar-brand me-auto" href="{{url('/admin/dashboard')}}"><img style="height:40px" src="{{asset('assets/img/logo/logo.svg')}}" style="" alt=""></a>
    <!-- Navbar items-->
    <div class="d-flex align-items-center mx-3 me-lg-0">
        <!-- Navbar-->
        {{-- <ul class="navbar-nav d-none d-lg-flex">
            <li class="nav-item"><a class="nav-link" href="{{url('admin/dashboard')}}">Overview</a></li>
            <!-- <li class="nav-item"><a class="nav-link" href="https://docs.startbootstrap.com/material-admin-pro" target="_blank">Documentation</a></li> -->
        </ul> --}}
        <!-- Navbar buttons-->
        <div class="d-flex">
            <!-- Messages dropdown-->
            {{-- <div class="dropdown dropdown-notifications d-none d-sm-block">
            <button class="btn btn-lg btn-icon me-3" type="button" ><i class="material-icons">mail_outline</i></button>
                <!-- <button class="btn btn-lg btn-icon dropdown-toggle me-3" id="dropdownMenuMessages" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">mail_outline</i></button> -->
                <ul class="dropdown-menu dropdown-menu-end me-3 mt-3 py-0 overflow-hidden" aria-labelledby="dropdownMenuMessages">
                    <li><h6 class="dropdown-header bg-primary text-white fw-500 py-3">Messages</h6></li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item unread" href="#!">
                            <div class="dropdown-item-content">
                                <div class="dropdown-item-content-text"><div class="text-truncate d-inline-block" style="max-width: 18rem">Hi there, I had a question about something, is there any way you can help me out?</div></div>
                                <div class="dropdown-item-content-subtext">Mar 12, 2023 · Juan Babin</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item" href="#!">
                            <div class="dropdown-item-content">
                                <div class="dropdown-item-content-text"><div class="text-truncate d-inline-block" style="max-width: 18rem">Thanks for the assistance the other day, I wanted to follow up with you just to make sure everyting is settled.</div></div>
                                <div class="dropdown-item-content-subtext">Mar 10, 2023 · Christine Hendersen</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item" href="#!">
                            <div class="dropdown-item-content">
                                <div class="dropdown-item-content-text"><div class="text-truncate d-inline-block" style="max-width: 18rem">Welcome to our group! It's good to see new members and I know you will do great!</div></div>
                                <div class="dropdown-item-content-subtext">Mar 8, 2023 · Celia J. Knight</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item py-3" href="#!">
                            <div class="d-flex align-items-center w-100 justify-content-end text-primary">
                                <div class="fst-button small">View all</div>
                                <i class="material-icons icon-sm ms-1">chevron_right</i>
                            </div>
                        </a>
                    </li>
                </ul>
            </div> --}}
            <!-- Notifications and alerts dropdown-->
            {{-- <div class="dropdown dropdown-notifications d-none d-sm-block">
                <button class="btn btn-lg btn-icon me-3"  type="button" ><i class="material-icons">notifications</i></button>
                <!-- <button class="btn btn-lg btn-icon dropdown-toggle me-3" id="dropdownMenuNotifications" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">notifications</i></button> -->
                <ul class="dropdown-menu dropdown-menu-end me-3 mt-3 py-0 overflow-hidden" aria-labelledby="dropdownMenuNotifications">
                    <li><h6 class="dropdown-header bg-primary text-white fw-500 py-3">Alerts</h6></li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item unread" href="#!">
                            <i class="material-icons leading-icon">assessment</i>
                            <div class="dropdown-item-content me-2">
                                <div class="dropdown-item-content-text">Your March performance report is ready to view.</div>
                                <div class="dropdown-item-content-subtext">Mar 12, 2023 · Performance</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item" href="#!">
                            <i class="material-icons leading-icon">check_circle</i>
                            <div class="dropdown-item-content me-2">
                                <div class="dropdown-item-content-text">Tracking codes successfully updated.</div>
                                <div class="dropdown-item-content-subtext">Mar 12, 2023 · Coverage</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item" href="#!">
                            <i class="material-icons leading-icon">warning</i>
                            <div class="dropdown-item-content me-2">
                                <div class="dropdown-item-content-text">Tracking codes have changed and require manual action.</div>
                                <div class="dropdown-item-content-subtext">Mar 8, 2023 · Coverage</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0" /></li>
                    <li>
                        <a class="dropdown-item py-3" href="#!">
                            <div class="d-flex align-items-center w-100 justify-content-end text-primary">
                                <div class="fst-button small">View all</div>
                                <i class="material-icons icon-sm ms-1">chevron_right</i>
                            </div>
                        </a>
                    </li>
                </ul>
            </div> --}}
            <!-- User profile dropdown-->
            <div class="dropdown">
                <button class="btn btn-lg btn-icon dropdown-toggle" id="dropdownMenuProfile" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">person</i></button>
                <ul class="dropdown-menu dropdown-menu-end mt-3" aria-labelledby="dropdownMenuProfile">
                    <li>
                        <a class="dropdown-item " href="{{url('admin/profile')}}">
                            <i class="material-icons leading-icon">account_circle</i>
                            <div class="me-3 fw-500">{{$auth->name}}</div>
                        </a>
                    </li>
                    <!-- <li>
                        <a class="dropdown-item" href="#!">
                            <i class="material-icons leading-icon">settings</i>
                            <div class="me-3">Settings</div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#!">
                            <i class="material-icons leading-icon">help</i>
                            <div class="me-3">Help</div>
                        </a>
                    </li> -->
                    
                    <li><hr class="dropdown-divider" /></li>
                    <li>
                        <a class="dropdown-item" href="{{url('admin/logout')}}">
                            <i class="material-icons leading-icon">logout</i>
                            <div class="me-3">Logout</div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>