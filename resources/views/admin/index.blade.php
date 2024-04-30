@extends('admin.layouts.app')

@section('content')

<main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5">
            <div class="row justify-content-between align-items-center mb-5">
                <div class="col flex-shrink-0 mb-5 mb-md-0">
                    <h1 class="display-4 mb-0">Dashboard</h1>
                    <div class="text-muted">Business Growth Analytics</div>
                </div>
                
            </div>
            <!-- Colored status cards-->
            <div class="row gx-5">
                <div class="col-xxl-4 col-md-6 mb-5">
                    <div class="card card-raised border-start border-primary border-4">
                        <div class="card-body px-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="me-2">
                                    <div class="display-5">{{$data1['user']}}</div>
                                    <div class="card-text">Registration</div>
                                </div>
                                <div class="icon-circle bg-primary text-white"><i class="material-icons">person</i></div>
                            </div>
                            <div class="card-text">
                                <div class="d-inline-flex align-items-center">
                                    <i class="material-icons icon-xs text-success">arrow_upward</i>
                                    <div class="caption text-success fw-500 me-2"></div>
                                    <div class="caption">from this month</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-md-6 mb-5">
                    <div class="card card-raised border-start border-warning border-4">
                        <div class="card-body px-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="me-2">
                                    <div class="display-5">{{$data1['community']}}</div>
                                    <div class="card-text">Community</div>
                                </div>
                                <div class="icon-circle bg-warning text-white"><i class="material-icons">groups</i></div>
                            </div>
                            <div class="card-text">
                                <div class="d-inline-flex align-items-center">
                                    <i class="material-icons icon-xs text-success">arrow_upward</i>
                                    <div class="caption text-success fw-500 me-2"></div>
                                    <div class="caption">from this month</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-md-6 mb-5">
                    <div class="card card-raised border-start border-secondary border-4">
                        <div class="card-body px-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="me-2">
                                    <div class="display-5">{{$data1['post']}}</div>
                                    <div class="card-text">Post</div>
                                </div>
                                <div class="icon-circle bg-secondary text-white"><i class="material-icons">view_comfy_alt</i></div>
                            </div>
                            <div class="card-text">
                                <div class="d-inline-flex align-items-center">
                                    <i class="material-icons icon-xs text-success">arrow_upward</i>
                                    <div class="caption text-success fw-500 me-2"></div>
                                    <div class="caption">from this month</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- <div class="col-xxl-3 col-md-6 mb-5">
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
                </div> -->
            </div>
            
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Registrations</h2>
                            <div class="card-subtitle">Recent registerd user details</div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Simple DataTables example-->
                    <table class="table table-bordered data-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Gender</th>
                                <th>Email Verification</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
     
                    </div>
            </div>
        </div>
    </main>


<script type="text/javascript">
  $(function () {
      
    var table = $('.data-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{url('admin/dashboard')}}",

        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            
            {data: 'logo', name: 'logo'},
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'gender', name: 'gender', searchable: true},
            {data: 'email_verify', name: 'email_verify'},
            {data: 'registration_date', name: 'registration_date', orderable: false, searchable: false},
        ],  
        createdRow: function (row, data, dataIndex) {
        // Check if name is not set
        if (!data.name || data.name.trim() === '') {
            $('td:eq(2)', row).text('No name');
        }
    }
        
    });
      
  });
</script>
            
            
@endsection
