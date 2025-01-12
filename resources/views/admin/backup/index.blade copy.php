<!DOCTYPE html>
<html lang="en">
    
<!-- Mirrored from material-admin-pro.startbootstrap.com/app-dashboard-minimal.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 10 Apr 2024 05:20:04 GMT -->
<head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>{{env('APP_NAME')}}</title>
        <!-- Load Favicon-->
        <link href="{{asset('assets/img/favicon.ico')}}" rel="shortcut icon" type="image/x-icon" />
        <!-- Load Material Icons from Google Fonts-->
        <link href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Two+Tone|Material+Icons+Round|Material+Icons+Sharp" rel="stylesheet" />
        <!-- Load Simple DataTables Stylesheet-->
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <!-- Roboto and Roboto Mono fonts from Google Fonts-->
        <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Mono:400,500" rel="stylesheet" />
        <!-- Load main stylesheet-->
        <link href="{{asset('assets/css/styles.css')}}" rel="stylesheet" />
    </head>
    <body class="nav-fixed bg-light">
        <!-- Top app bar navigation menu-->
        <nav class="top-app-bar navbar navbar-expand navbar-dark bg-dark">
            <div class="container-fluid px-4">
                <!-- Drawer toggle button-->
                <button class="btn btn-lg btn-icon order-1 order-lg-0" id="drawerToggle" href="javascript:void(0);"><i class="material-icons">menu</i></button>
                <!-- Navbar brand-->
                <a class="navbar-brand me-auto" href="index-2.html"><div class="text-uppercase font-monospace">Material Admin Pro</div></a>
                <!-- Navbar items-->
                <div class="d-flex align-items-center mx-3 me-lg-0">
                    <!-- Navbar-->
                    <ul class="navbar-nav d-none d-lg-flex">
                        <li class="nav-item"><a class="nav-link" href="index-2.html">Overview</a></li>
                        <li class="nav-item"><a class="nav-link" href="https://docs.startbootstrap.com/material-admin-pro" target="_blank">Documentation</a></li>
                    </ul>
                    <!-- Navbar buttons-->
                    <div class="d-flex">
                        <!-- Messages dropdown-->
                        <div class="dropdown dropdown-notifications d-none d-sm-block">
                            <button class="btn btn-lg btn-icon dropdown-toggle me-3" id="dropdownMenuMessages" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">mail_outline</i></button>
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
                        </div>
                        <!-- Notifications and alerts dropdown-->
                        <div class="dropdown dropdown-notifications d-none d-sm-block">
                            <button class="btn btn-lg btn-icon dropdown-toggle me-3" id="dropdownMenuNotifications" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">notifications</i></button>
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
                        </div>
                        <!-- User profile dropdown-->
                        <div class="dropdown">
                            <button class="btn btn-lg btn-icon dropdown-toggle" id="dropdownMenuProfile" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">person</i></button>
                            <ul class="dropdown-menu dropdown-menu-end mt-3" aria-labelledby="dropdownMenuProfile">
                                <li>
                                    <a class="dropdown-item" href="#!">
                                        <i class="material-icons leading-icon">person</i>
                                        <div class="me-3">Profile</div>
                                    </a>
                                </li>
                                <li>
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
                                </li>
                                <li><hr class="dropdown-divider" /></li>
                                <li>
                                    <a class="dropdown-item" href="#!">
                                        <i class="material-icons leading-icon">logout</i>
                                        <div class="me-3">Logout</div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Layout wrapper-->
        <div id="layoutDrawer">
            <!-- Layout navigation-->
            <div id="layoutDrawer_nav">
                <!-- Drawer navigation-->
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
                            <a class="nav-link" href="index-2.html">
                                <div class="nav-link-icon"><i class="material-icons">language</i></div>
                                Overview
                            </a>
                            <!-- Drawer link (Dashboards)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseDashboards" aria-expanded="false" aria-controls="collapseDashboards">
                                <div class="nav-link-icon"><i class="material-icons">dashboard</i></div>
                                Dashboards
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Dashboards)-->
                            <div class="collapse" id="collapseDashboards" aria-labelledby="headingOne" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav">
                                    <a class="nav-link" href="app-dashboard-default.html">Default</a>
                                    <a class="nav-link" href="app-dashboard-minimal.html">Minimal</a>
                                    <a class="nav-link" href="app-dashboard-analytics.html">Analytics</a>
                                    <a class="nav-link" href="app-dashboard-accounting.html">Accounting</a>
                                    <a class="nav-link" href="app-dashboard-orders.html">Orders</a>
                                    <a class="nav-link" href="app-dashboard-projects.html">Projects</a>
                                </nav>
                            </div>
                            <!-- Drawer link (Layouts)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="nav-link-icon"><i class="material-icons">view_compact</i></div>
                                Layouts
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Layouts)-->
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav">
                                    <a class="nav-link" href="layout-dark.html">Dark Theme</a>
                                    <a class="nav-link" href="layout-light.html">Light Theme</a>
                                    <a class="nav-link" href="layout-static.html">Static Navigation</a>
                                </nav>
                            </div>
                            <!-- Drawer link (Pages)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                <div class="nav-link-icon"><i class="material-icons">layers</i></div>
                                Pages
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Pages)-->
                            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav accordion" id="drawerAccordionPages">
                                    <!-- Drawer link (Pages -> Account)-->
                                    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#pagesCollapseAccount" aria-expanded="false" aria-controls="pagesCollapseAccount">
                                        Account
                                        <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                                    </a>
                                    <!-- Nested drawer nav (Pages -> Account)-->
                                    <div class="collapse" id="pagesCollapseAccount" aria-labelledby="headingOne" data-bs-parent="#drawerAccordionPages">
                                        <nav class="drawer-menu-nested nav">
                                            <a class="nav-link" href="app-account-billing.html">Billing</a>
                                            <a class="nav-link" href="app-account-notifications.html">Notifications</a>
                                            <a class="nav-link" href="app-account-profile.html">Profile</a>
                                            <a class="nav-link" href="app-account-security.html">Security</a>
                                        </nav>
                                    </div>
                                    <!-- Drawer link (Pages -> Authentication)-->
                                    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#pagesCollapseAuth" aria-expanded="false" aria-controls="pagesCollapseAuth">
                                        Authentication
                                        <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                                    </a>
                                    <!-- Nested drawer nav (Pages -> Authentication)-->
                                    <div class="collapse" id="pagesCollapseAuth" aria-labelledby="headingOne" data-bs-parent="#drawerAccordionPages">
                                        <nav class="drawer-menu-nested nav">
                                            <a class="nav-link" href="app-auth-login-basic.html">Login 1</a>
                                            <a class="nav-link" href="app-auth-login-styled-1.html">Login 2</a>
                                            <a class="nav-link" href="app-auth-login-styled-2.html">Login 3</a>
                                            <a class="nav-link" href="app-auth-register-basic.html">Register</a>
                                            <a class="nav-link" href="app-auth-password-basic.html">Forgot Password</a>
                                        </nav>
                                    </div>
                                    <!-- Drawer link (Pages -> Blank Pages)-->
                                    <a class="nav-link" href="app-blank-page.html">Blank Page</a>
                                    <!-- Drawer link (Pages -> Error)-->
                                    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#pagesCollapseError" aria-expanded="false" aria-controls="pagesCollapseError">
                                        Error
                                        <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                                    </a>
                                    <!-- Nested drawer nav (Pages -> Error)-->
                                    <div class="collapse" id="pagesCollapseError" aria-labelledby="headingOne" data-bs-parent="#drawerAccordionPages">
                                        <nav class="drawer-menu-nested nav">
                                            <a class="nav-link" href="app-error-400.html">400 Error Page</a>
                                            <a class="nav-link" href="app-error-401.html">401 Error Page</a>
                                            <a class="nav-link" href="app-error-403.html">403 Error Page</a>
                                            <a class="nav-link" href="app-error-404.html">404 Error Page</a>
                                            <a class="nav-link" href="app-error-429.html">429 Error Page</a>
                                            <a class="nav-link" href="app-error-500.html">500 Error Page</a>
                                            <a class="nav-link" href="app-error-503.html">503 Error Page</a>
                                            <a class="nav-link" href="app-error-504.html">504 Error Page</a>
                                        </nav>
                                    </div>
                                    <!-- Drawer link (Pages -> Pricing)-->
                                    <a class="nav-link" href="app-invoice.html">Invoice</a>
                                    <!-- Drawer link (Pages -> Knowledgebase)-->
                                    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#pagesCollapseKnowledgebase" aria-expanded="false" aria-controls="pagesCollapseKnowledgebase">
                                        Knowledgebase
                                        <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                                    </a>
                                    <!-- Nested drawer nav (Pages -> Knowledgebase)-->
                                    <div class="collapse" id="pagesCollapseKnowledgebase" aria-labelledby="headingOne" data-bs-parent="#drawerAccordionPages">
                                        <nav class="drawer-menu-nested nav">
                                            <a class="nav-link" href="app-knowledgebase-home.html">Home</a>
                                            <a class="nav-link" href="app-knowledgebase-categories.html">Categories</a>
                                            <a class="nav-link" href="app-knowledgebase-article.html">Article</a>
                                        </nav>
                                    </div>
                                    <!-- Drawer link (Pages -> Pricing)-->
                                    <a class="nav-link" href="app-pricing.html">Pricing</a>
                                </nav>
                            </div>
                            <!-- Divider-->
                            <div class="drawer-menu-divider"></div>
                            <!-- Drawer section heading (UI Toolkit)-->
                            <div class="drawer-menu-heading">UI Toolkit</div>
                            <!-- Drawer link (Components)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseComponents" aria-expanded="false" aria-controls="collapseComponents">
                                <div class="nav-link-icon"><i class="material-icons">widgets</i></div>
                                Components
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Components)-->
                            <div class="collapse" id="collapseComponents" aria-labelledby="headingOne" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav">
                                    <a class="nav-link" href="components-alerts.html">Alerts</a>
                                    <a class="nav-link" href="components-badges.html">Badges</a>
                                    <a class="nav-link" href="components-buttons.html">Buttons</a>
                                    <a class="nav-link" href="components-cards.html">Cards</a>
                                    <a class="nav-link" href="components-chips.html">Chips</a>
                                    <a class="nav-link" href="components-dropdowns.html">Dropdowns</a>
                                    <a class="nav-link" href="components-icon-buttons.html">Icon Buttons</a>
                                    <a class="nav-link" href="components-modals.html">Modals</a>
                                    <a class="nav-link" href="components-navigation.html">Navigation</a>
                                    <a class="nav-link" href="components-progress.html">Progress</a>
                                    <a class="nav-link" href="components-spinners.html">Spinners</a>
                                    <a class="nav-link" href="components-tooltips.html">Tooltips</a>
                                </nav>
                            </div>
                            <!-- Drawer link (Content)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">
                                <div class="nav-link-icon"><i class="material-icons">amp_stories</i></div>
                                Content
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Content)-->
                            <div class="collapse" id="collapseContent" aria-labelledby="headingOne" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav">
                                    <a class="nav-link" href="content-icons.html">Icons</a>
                                    <a class="nav-link" href="content-tables.html">Tables</a>
                                    <a class="nav-link" href="content-typography.html">Typography</a>
                                </nav>
                            </div>
                            <!-- Drawer link (Forms)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseForms" aria-expanded="false" aria-controls="collapseForms">
                                <div class="nav-link-icon"><i class="material-icons">description</i></div>
                                Forms
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Forms)-->
                            <div class="collapse" id="collapseForms" aria-labelledby="headingOne" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav">
                                    <a class="nav-link" href="forms-inputs.html">Inputs</a>
                                    <a class="nav-link" href="forms-checks-and-radios.html">Checks &amp; Radio</a>
                                    <a class="nav-link" href="forms-input-groups.html">Input Groups</a>
                                    <a class="nav-link" href="forms-range.html">Range</a>
                                    <a class="nav-link" href="forms-select.html">Select</a>
                                </nav>
                            </div>
                            <!-- Drawer link (Utilities)-->
                            <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseUtilities" aria-expanded="false" aria-controls="collapseUtilities">
                                <div class="nav-link-icon"><i class="material-icons">build</i></div>
                                Utilities
                                <div class="drawer-collapse-arrow"><i class="material-icons">expand_more</i></div>
                            </a>
                            <!-- Nested drawer nav (Utilities)-->
                            <div class="collapse" id="collapseUtilities" aria-labelledby="headingOne" data-bs-parent="#drawerAccordion">
                                <nav class="drawer-menu-nested nav">
                                    <a class="nav-link" href="utilities-background.html">Background</a>
                                    <a class="nav-link" href="utilities-borders.html">Borders</a>
                                    <a class="nav-link" href="utilities-ripples.html">Ripples</a>
                                    <a class="nav-link" href="utilities-shadows.html">Shadows</a>
                                    <a class="nav-link" href="utilities-text.html">Text</a>
                                    <a class="nav-link" href="utilities-transforms.html">Transforms</a>
                                </nav>
                            </div>
                            <!-- Divider-->
                            <div class="drawer-menu-divider"></div>
                            <!-- Drawer section heading (Plugins)-->
                            <div class="drawer-menu-heading">Plugins</div>
                            <!-- Drawer link (Charts)-->
                            <a class="nav-link" href="plugins-charts.html">
                                <div class="nav-link-icon"><i class="material-icons">bar_chart</i></div>
                                Charts
                            </a>
                            <!-- Drawer link (Code Blocks)-->
                            <a class="nav-link" href="plugins-code-blocks.html">
                                <div class="nav-link-icon"><i class="material-icons">code</i></div>
                                Code Blocks
                            </a>
                            <!-- Drawer link (Data Tables)-->
                            <a class="nav-link" href="plugins-data-tables.html">
                                <div class="nav-link-icon"><i class="material-icons">filter_alt</i></div>
                                Data Tables
                            </a>
                            <!-- Drawer link (Date Picker)-->
                            <a class="nav-link" href="plugins-date-picker.html">
                                <div class="nav-link-icon"><i class="material-icons">date_range</i></div>
                                Date Picker
                            </a>
                        </div>
                    </div>
                    <!-- Drawer footer        -->
                    <div class="drawer-footer border-top">
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-muted">account_circle</i>
                            <div class="ms-3">
                                <div class="caption">Logged in as:</div>
                                <div class="small fw-500">Start Bootstrap</div>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            <!-- Layout content-->
            <div id="layoutDrawer_content">
                <!-- Main page content-->
                <main>
                    <!-- Main dashboard content-->
                    <div class="container-xl p-5">
                        <div class="row justify-content-between align-items-center mb-5">
                            <div class="col flex-shrink-0 mb-5 mb-md-0">
                                <h1 class="display-4 mb-0">Dashboard</h1>
                                <div class="text-muted">Sales overview &amp; summary</div>
                            </div>
                            <div class="col-12 col-md-auto">
                                <div class="d-flex flex-column flex-sm-row gap-3">
                                    <mwc-select class="mw-50 mb-2 mb-md-0" outlined="" label="View by">
                                        <mwc-list-item selected="" value="0">Order type</mwc-list-item>
                                        <mwc-list-item value="1">Segment</mwc-list-item>
                                        <mwc-list-item value="2">Customer</mwc-list-item>
                                    </mwc-select>
                                    <mwc-select class="mw-50" outlined="" label="Sales from">
                                        <mwc-list-item value="0">Last 7 days</mwc-list-item>
                                        <mwc-list-item value="1">Last 30 days</mwc-list-item>
                                        <mwc-list-item value="2">Last month</mwc-list-item>
                                        <mwc-list-item selected="" value="3">Last year</mwc-list-item>
                                    </mwc-select>
                                </div>
                            </div>
                        </div>
                        <!-- Colored status cards-->
                        <div class="row gx-5">
                            <div class="col-xxl-3 col-md-6 mb-5">
                                <div class="card card-raised border-start border-primary border-4">
                                    <div class="card-body px-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="me-2">
                                                <div class="display-5">101.1K</div>
                                                <div class="card-text">Downloads</div>
                                            </div>
                                            <div class="icon-circle bg-primary text-white"><i class="material-icons">download</i></div>
                                        </div>
                                        <div class="card-text">
                                            <div class="d-inline-flex align-items-center">
                                                <i class="material-icons icon-xs text-success">arrow_upward</i>
                                                <div class="caption text-success fw-500 me-2">3%</div>
                                                <div class="caption">from last month</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6 mb-5">
                                <div class="card card-raised border-start border-warning border-4">
                                    <div class="card-body px-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="me-2">
                                                <div class="display-5">12.2K</div>
                                                <div class="card-text">Purchases</div>
                                            </div>
                                            <div class="icon-circle bg-warning text-white"><i class="material-icons">storefront</i></div>
                                        </div>
                                        <div class="card-text">
                                            <div class="d-inline-flex align-items-center">
                                                <i class="material-icons icon-xs text-success">arrow_upward</i>
                                                <div class="caption text-success fw-500 me-2">3%</div>
                                                <div class="caption">from last month</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6 mb-5">
                                <div class="card card-raised border-start border-secondary border-4">
                                    <div class="card-body px-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="me-2">
                                                <div class="display-5">5.3K</div>
                                                <div class="card-text">Customers</div>
                                            </div>
                                            <div class="icon-circle bg-secondary text-white"><i class="material-icons">people</i></div>
                                        </div>
                                        <div class="card-text">
                                            <div class="d-inline-flex align-items-center">
                                                <i class="material-icons icon-xs text-success">arrow_upward</i>
                                                <div class="caption text-success fw-500 me-2">3%</div>
                                                <div class="caption">from last month</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-md-6 mb-5">
                                <div class="card card-raised border-start border-info border-4">
                                    <div class="card-body px-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="me-2">
                                                <div class="display-5">7</div>
                                                <div class="card-text">Channels</div>
                                            </div>
                                            <div class="icon-circle bg-info text-white"><i class="material-icons">devices</i></div>
                                        </div>
                                        <div class="card-text">
                                            <div class="d-inline-flex align-items-center">
                                                <i class="material-icons icon-xs text-success">arrow_upward</i>
                                                <div class="caption text-success fw-500 me-2">3%</div>
                                                <div class="caption">from last month</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row gx-5">
                            <!-- Revenue breakdown chart example-->
                            <div class="col-lg-8 mb-5">
                                <div class="card card-raised h-100">
                                    <div class="card-header bg-transparent px-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-4">
                                                <h2 class="card-title mb-0">Revenue Breakdown</h2>
                                                <div class="card-subtitle">Compared to previous year</div>
                                            </div>
                                            <div class="d-flex gap-2 me-n2">
                                                <button class="btn btn-lg btn-text-primary btn-icon" type="button"><i class="material-icons">download</i></button>
                                                <button class="btn btn-lg btn-text-primary btn-icon" type="button"><i class="material-icons">print</i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row gx-4">
                                            <div class="col-12 col-xxl-2">
                                                <div class="d-flex flex-column flex-md-row flex-xxl-column align-items-center align-items-xl-start justify-content-between">
                                                    <div class="mb-4 text-center text-md-start">
                                                        <div class="text-xs font-monospace text-muted mb-1">Actual Revenue</div>
                                                        <div class="display-5 fw-500">$59,482</div>
                                                    </div>
                                                    <div class="mb-4 text-center text-md-start">
                                                        <div class="text-xs font-monospace text-muted mb-1">Revenue Target</div>
                                                        <div class="display-5 fw-500">$50,000</div>
                                                    </div>
                                                    <div class="mb-4 text-center text-md-start">
                                                        <div class="text-xs font-monospace text-muted mb-1">Goal</div>
                                                        <div class="display-5 fw-500 text-success">119%</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-xxl-10"><canvas id="dashboardBarChart"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent position-relative ripple-gray">
                                        <a class="d-flex align-items-center justify-content-end text-decoration-none stretched-link text-primary" href="#!">
                                            <div class="fst-button">Open Report</div>
                                            <i class="material-icons icon-sm ms-1">chevron_right</i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Segments pie chart example-->
                            <div class="col-lg-4 mb-5">
                                <div class="card card-raised h-100">
                                    <div class="card-header bg-transparent px-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-4">
                                                <h2 class="card-title mb-0">Segments</h2>
                                                <div class="card-subtitle">Revenue sources</div>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-lg btn-text-gray btn-icon me-n2 dropdown-toggle" id="segmentsDropdownButton" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="material-icons">more_vert</i></button>
                                                <ul class="dropdown-menu" aria-labelledby="segmentsDropdownButton">
                                                    <li><a class="dropdown-item" href="#!">Action</a></li>
                                                    <li><a class="dropdown-item" href="#!">Another action</a></li>
                                                    <li><a class="dropdown-item" href="#!">Something else here</a></li>
                                                    <li><hr class="dropdown-divider" /></li>
                                                    <li><a class="dropdown-item" href="#!">Separated link</a></li>
                                                    <li><a class="dropdown-item" href="#!">Separated link</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="d-flex h-100 w-100 align-items-center justify-content-center">
                                            <div class="w-100" style="max-width: 20rem"><canvas id="myPieChart"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent position-relative ripple-gray">
                                        <a class="d-flex align-items-center justify-content-end text-decoration-none stretched-link text-primary" href="#!">
                                            <div class="fst-button">Open Report</div>
                                            <i class="material-icons icon-sm ms-1">chevron_right</i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row gx-5">
                            <!-- Privacy suggestions illustrated card-->
                            <div class="col-xl-6 mb-5">
                                <div class="card card-raised h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-4">
                                                <h2 class="card-title mb-0">Privacy Suggestions</h2>
                                                <p class="card-text">Take our privacy checkup to choose which settings are right for you.</p>
                                            </div>
                                            <img src="assets/img/illustrations/security.svg" alt="..." style="height: 6rem" />
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent position-relative ripple-gray px-4"><a class="stretched-link text-decoration-none" href="#!">Review suggestions (4)</a></div>
                                </div>
                            </div>
                            <!-- Account storage illustrated card-->
                            <div class="col-xl-6 mb-5">
                                <div class="card card-raised h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between">
                                            <div class="me-4">
                                                <h2 class="card-title mb-0">Account Storage</h2>
                                                <p class="card-text">Your account storage is shared across all devices.</p>
                                                <div class="progress mb-2" style="height: 0.25rem"><div class="progress-bar" role="progressbar" style="width: 33%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="30"></div></div>
                                                <div class="card-text">10 GB of 30 GB used</div>
                                            </div>
                                            <img src="assets/img/illustrations/cloud.svg" alt="..." style="height: 6rem" />
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent position-relative ripple-gray px-4"><a class="stretched-link text-decoration-none" href="#!">Manage storage</a></div>
                                </div>
                            </div>
                        </div>
                        <div class="card card-raised">
                            <div class="card-header bg-transparent px-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="me-4">
                                        <h2 class="card-title mb-0">Orders</h2>
                                        <div class="card-subtitle">Details and history</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-lg btn-text-primary btn-icon" type="button"><i class="material-icons">download</i></button>
                                        <button class="btn btn-lg btn-text-primary btn-icon" type="button"><i class="material-icons">print</i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <!-- Simple DataTables example-->
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Ext.</th>
                                            <th>City</th>
                                            <th data-type="date" data-format="YYYY/MM/DD">Start Date</th>
                                            <th>Completion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Unity Pugh</td>
                                            <td>9958</td>
                                            <td>Curicó</td>
                                            <td>2005/02/11</td>
                                            <td>37%</td>
                                        </tr>
                                        <tr>
                                            <td>Theodore Duran</td>
                                            <td>8971</td>
                                            <td>Dhanbad</td>
                                            <td>1999/04/07</td>
                                            <td>97%</td>
                                        </tr>
                                        <tr>
                                            <td>Kylie Bishop</td>
                                            <td>3147</td>
                                            <td>Norman</td>
                                            <td>2005/09/08</td>
                                            <td>63%</td>
                                        </tr>
                                        <tr>
                                            <td>Willow Gilliam</td>
                                            <td>3497</td>
                                            <td>Amqui</td>
                                            <td>2009/29/11</td>
                                            <td>30%</td>
                                        </tr>
                                        <tr>
                                            <td>Blossom Dickerson</td>
                                            <td>5018</td>
                                            <td>Kempten</td>
                                            <td>2006/11/09</td>
                                            <td>17%</td>
                                        </tr>
                                        <tr>
                                            <td>Elliott Snyder</td>
                                            <td>3925</td>
                                            <td>Enines</td>
                                            <td>2006/03/08</td>
                                            <td>57%</td>
                                        </tr>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <!-- Footer-->
                <!-- Min-height is set inline to match the height of the drawer footer-->
                <footer class="py-4 mt-auto border-top" style="min-height: 74px">
                    <div class="container-xl px-5">
                        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-sm-between small">
                            <div class="me-sm-2">Copyright © Your Website 2023</div>
                            <div class="d-flex ms-sm-2">
                                <a class="text-decoration-none" href="#!">Privacy Policy</a>
                                <div class="mx-1">·</div>
                                <a class="text-decoration-none" href="#!">Terms &amp; Conditions</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
        <!-- Load Bootstrap JS bundle-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <!-- Load global scripts-->
        <script type="module" src="{{asset('assets/js/material.js')}}"></script>
        <script src="{{asset('assets/js/scripts.js')}}"></script>
        <!--  Load Chart.js via CDN-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.0.2/chart.min.js" crossorigin="anonymous"></script>
        <!--  Load Chart.js customized defaults-->
        <script src="{{asset('assets/js/charts/chart-defaults.js')}}"></script>
        <!--  Load chart demos for this page-->
        <script src="{{asset('assets/js/charts/demos/chart-pie-demo.js')}}"></script>
        <script src="{{asset('assets/js/charts/demos/dashboard-chart-bar-grouped-demo.js')}}"></script>
        <!-- Load Simple DataTables Scripts-->
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="{{asset('assets/js/datatables/datatables-simple-demo.js')}}"></script>

        <!-- <script src="{{asset('assets/js/sb-customizer.js')}}"></script> -->
        <sb-customizer project="material-admin-pro"></sb-customizer>
</body>

<!-- Mirrored from material-admin-pro.startbootstrap.com/app-dashboard-minimal.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 10 Apr 2024 05:20:04 GMT -->
</html>
