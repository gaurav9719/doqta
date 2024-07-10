@extends('admin.layouts.app')

@section('content')

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
                    <table id="data_table">
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

    <script src="http://13.237.218.236/service_app//public/js/datatable.min.js"></script>
    <script type="text/javascript">
        $(function(){
            var table=$('#data_table').DataTables({
                processing: true,
                serverSide: true,
                ajax: "{{url('admin/dashboard')}}",
                columns: [
                    {data: 'DT_RowIndex', orderable: false},
                    {data: 'name', name: 'name'},
                    {data: 'name', name: 'name'},
                    {data: 'name', name: 'name'},
                    {data: 'name', name: 'name'},
                ]
            });
        });

        $(function () {
    
    var table = $('#data_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ url('admin/dashboard') }}",
        columns: [
            {data: 'DT_RowIndex' , orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'gender', name: 'gender'},
            
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
  });
        
    </script>
            
@endsection
