@extends('admin.layouts.app')

@section('content')

<main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5" >
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Users</h2>
                            <div class="card-subtitle">All user details </div>
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
                                <th>Phone</th>
                                <th>Registration Date</th>
                                <th>Email Verification</th>
                                <th>Status</th>
                                <th>Actions</th>

                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
     
                    </div>
            </div>
        </div>
    </main>

    @include('admin.layouts.sections.toast')


<script type="text/javascript">
    $(function () {
        fatchData();
    });

    
    function fatchData(){
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{url('admin/users')}}",
            columns: [
                {data: 'DT_RowIndex' , orderable: false, searchable: false},
                {data: 'logo', name: 'logo'},
                {data: 'name', name: 'name'},
                {data: 'email', name: 'email'},
                {data: 'phone_no', name: 'phone_no'},
                {data: 'registration_date', name: 'registration_date',  searchable: false},
                {data: 'email_verify', name: 'email_verify'},
                {data: 'status', name: 'status'},
                {data: 'edit', name: 'edit'},

            ],
            createdRow: function (row, data, dataIndex) {
                // Check if name is not set
                if (!data.name || data.name.trim() === '') {
                    $('td:eq(2)', row).text('No Name');
                }
                // Check if phone_no is not set
                if (!data.phone_no || data.phone_no.trim() === '') {
                    $('td:eq(4)', row).text('Not available');
                }
                
            }
            

            
        });
        
  };


    function showToast(message, status){
        //success
        if(status == 1){
            var toastItem = document.getElementById('successToast')
            $('#successToastBody').html(message);
            var toast = new bootstrap.Toast(toastItem)
            toast.show()
        }
        //fail
        else if(status == 2){
            var toastItem = document.getElementById('failToast')
            $('#failToastBody').html(message);
            var toast = new bootstrap.Toast(toastItem)
            toast.show()
        }
        else{
            var toastItem = document.getElementById('failToast')
            $('#failToastBody').html(message);
            var toast = new bootstrap.Toast(toastItem)
            toast.show()
        }
    }


    function changeStatus(id){
        $.ajax({
            url : `{{url('admin/users/${id}')}}`,
            method : 'PUT',
            
            data : {"_token": `{{csrf_token()}}`},
            success : function(data){
                if(data.status == 200){
                    console.log(data);
                    $('.data-table').DataTable().destroy(); 
                    fatchData();
                    showToast(data.message, 1);
                }
            }
        })
    }



    function viewProfile(id){
        $.ajax({
            url : `{{url('admin/users/${id}')}}`,
            method : 'GET',
            data : {"_token": `{{csrf_token()}}`, "type": 1},
            success : function(res){
                // console.log(res);
                if(res.status == 200){
                    var user= res.user;
                // $("#viewProfile").modal("toggle");
                if(user.name == null){
                    user.name = "No name";
                }
                if(user.user_name == null){
                    user.user_name = "Not available";
                }
                var html=`<div class="card" style="border-radius: .5rem;">
                            <div class="row g-0" >
                                <div class="col-md-4 gradient-custom text-center text-white"
                                    style="border-top-left-radius: .5rem; border-bottom-left-radius: .5rem;">
                                    <img src="${user.profile}"
                                        alt="Avatar" class="img-fluid my-5" style="width: 80px;" />
                                        <h5 style="color:white;">${user.name}</h5>
                                        <p>${user.email}</p>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body p-4">
                                        <h6>Information</h6>
                                        <hr class="mt-0 mb-4">
                                        <div class="row pt-1">
                                            <div class="col-6 mb-3">
                                                <h6>Registered on</h6>
                                                <p class="text-muted">${user.registration_date}</p>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <h6>Phone</h6>
                                                <p class="text-muted">${user.phone_no}</p>
                                            </div>
                                        </div>
                                        <h6>Additional Information</h6>
                                        <hr class="mt-0 mb-4">
                                        <div class="row pt-1">
                                            <div class="col-6 mb-3">
                                                <h6>Gender</h6>
                                                <p class="text-muted">${user.gender}</p>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <h6>Username</h6>
                                                <p class="text-muted">${user.user_name}</p>
                                            </div>
                                            
                                        </div>
                                        <!--<div class="d-flex justify-content-start">
                                            <a href="#!"><i class="fab fa-facebook-f fa-lg me-3"></i></a>
                                            <a href="#!"><i class="fab fa-twitter fa-lg me-3"></i></a>
                                            <a href="#!"><i class="fab fa-instagram fa-lg"></i></a>
                                        </div>-->
                                    </div>
                                </div>
                            </div>
                        </div>`;
                $("#modelBody").html(html);
                $("#viewProfile").modal('show');
                }
            }
        })
    }


    function closeModel(){
        $("#matchTableBody").html(`<tr><th colspan="4">No Record Found</th></tr>`);
    }
</script>
            



@include('admin.layouts.sections.profile-view-modal')


@endsection
