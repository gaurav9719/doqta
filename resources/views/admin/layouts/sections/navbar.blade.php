<div id="layoutDrawer_nav">
    <!-- Drawer navigation-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <nav class="drawer accordion drawer-light bg-white" id="drawerAccordion">
        <div class="drawer-menu">
            <div class="nav">
                <!-- Drawer section heading (Account)-->
                <div class="drawer-menu-heading d-sm-none">Account</div>
                <!-- Drawer link (Notifications)-->
                <a class="nav-link d-sm-none" href="#!">
                    <div class="nav-link-icon"><i class="material-icons">notifications</i></div>
                    Notifications
                </a>
                <!-- Drawer link (Messages)-->
                <a class="nav-link d-sm-none" href="#!">
                    <div class="nav-link-icon"><i class="material-icons">mail</i></div>
                    Messages
                </a>
                <!-- Divider-->
                <div class="drawer-menu-divider d-sm-none"></div>
                <!-- Drawer section heading (Interface)-->
                <div class="drawer-menu-heading">Interface</div>
                <!-- Drawer link (Overview)-->

                <!-- Drawer link (Dashboards)-->
                <a class="nav-link collapsed {{ Request::path() == 'admin/dashboard' ? 'active' : '' }}"
                    href="{{ url('admin/dashboard') }}">
                    <div class="nav-link-icon"><i class="material-icons">dashboard</i></div>
                    Dashboards
                </a>

                <!-- Drawer link (Layouts)-->
                <a class="nav-link collapsed  {{ Request::path() == 'admin/users' ? 'active' : '' }}"
                    href="{{ url('admin/users') }}">
                    <div class="nav-link-icon"><i class="material-icons">person</i></div>
                    Users

                </a>
                <!-- Drawer link (Layouts)-->
                <a class="nav-link collapsed  {{ Request::path() == 'admin/document-verification' ? 'active' : '' }}"
                    href="{{ url('admin/document-verification') }}">
                    <div class="nav-link-icon"><i class="material-icons">pending_actions</i></div>
                    Document Verification
                </a>

                <!-- Community -->
                <a class="nav-link collapsed  {{( Request::path() == 'admin/community' ||Request::path()=='admin/members' ) ? 'active' : '' }}"
                    href="{{ url('admin/community') }}">
                    <div class="nav-link-icon"><i class="material-icons">groups</i></div>
                    Community
                </a>

                <!-- posts -->
                {{-- <a class="nav-link collapsed  {{ Request::path() == 'admin/posts' ? 'active' : '' }}"
                    href="{{ url('admin/posts') }}">
                    <div class="nav-link-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    Posts
                </a> --}}

                <!-- Domains -->
                <a class="nav-link collapsed  {{ Request::path() == 'admin/partners-domain' ? 'active' : '' }}"
                    href="{{ url('admin/partners-domain') }}">
                    <div class="nav-link-icon"><i class="material-icons">language</i></div>
                    Domains
                </a>

            </div>
        </div>
        <!-- Drawer footer  -->
        <div class="drawer-footer border-top">
            <div class="d-flex align-items-center">
                <i class="material-icons text-muted">account_circle</i>
                <div class="ms-3">
                    <div class="caption">Logged in as:</div>
                    <div class="small fw-500">{{ $auth->name }}</div>
                </div>
            </div>
        </div>
    </nav>
</div>
