@extends('Admin.layouts.master')
@section('title',"Admin Login")
@section('main_content')
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif


<section class="section">
    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Influencers</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table  class="table table-striped" id="influncer-data-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            #
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Profile</th>
                                        <th>Bio</th>
                                        <th>Phone No</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  
                                </tbody>
                            </table>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>




<script type="text/javascript">
  $(function () {
    var table = $('#influncer-data-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.influencer') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'full_name', name: 'full_name'},
            {data: 'email', name: 'email'},
            {data: 'profile_pic', name: 'profile_pic'},
            {data: 'bio', name: 'bio'},
            {data: 'phone_no', name: 'phone_no'},
            {data: 'is_active', name: 'is_active', orderable: false, searchable: false},
            {data: 'created_at', name: 'created_at'},

        ]
    });
  });

  function chageUserStatus(ele) {
  
    let changeStatus       =   $(ele).attr("xyz");
    var uid                =   $(ele).attr("data-src");
    var usd                =   $(ele).attr("data-div");
        if (changeStatus == 1) {
            var title     = "Are you sure to Activate the user?";
            var confirm_m = "Active";
        } else {
            var title = "Are you sure to Inactivate the user?";
            var confirm_m = "Inactive";
        } // publish 
        Swal.fire({
            title: title,
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirm_m
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    type: 'POST',

                    url: "{{ route('admin.activeInactive') }}",

                    data: {

                        'uid': uid,
                        "_token": "{{ csrf_token() }}",
                        'status':changeStatus
                    },

                    dataType: "json",

                    success: function(data) {
                        // console.log(data);
                        
                        if (data.status == 200) {

                          console.log(data.html);

                            // toastr.success(data.message);
                            $('#'+usd).html(data.html);
                            

                        } else {
                            toastr.error(data.message);
                            //     setTimeout(() => {
                            //     window.location.reload();
                            // }, 2000);
                        }
                    },
                    error: function(data) {
                        console.log(data);
                    }
                    }); // /ajax
                                    


                                }



                            })
                        }












  
</script>

@endsection