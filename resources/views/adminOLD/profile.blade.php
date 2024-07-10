@extends('admin.layouts.app')

@section('content')

<main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5">
            <div class="row justify-content-between align-items-center mb-5">
                <div class="col flex-shrink-0 mb-5 mb-md-0">
                    <h1 class="display-4 mb-0">Admin Profile</h1>
                    <div class="text-muted"></div>
                </div>
            </div>
            <!-- Colored status cards-->
            
            <div class="row">
            <div class="col-md-6 h4">
                <div class="card card-raised">
                    <div class="card-header bg-transparent px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="me-4">
                                <h2 class="card-title mb-0">Admin Profile Details</h2>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">       
                        <table class="table">
                            <tr>
                                <td>Name</td>
                                <td>{{$auth->name}}</td>
                            </tr>
                            <tr>
                                <td>Email</td>
                                <td>{{$auth->email}}</td>
                            </tr>
                            <tr>
                                <td>Phone</td>
                                <td>{{$auth->phone_no}}</td>
                            </tr>
                            <tr>
                                <td>Profle</td>
                                <td>Admin</td>
                            </tr>
                        </table>       
                    </div>
                </div>
            </div>
            <div class="col-md-6 h4">
                <div class="card card-raised">
                    <div class="card-header bg-transparent px-4">
                        <div class="card-title">Edit Profie</div>
                    </div>
                    <div class="card-body">
                        <form class="form" action="" method="post">
                            @csrf
                            <input type="hidden" class="form-control" name="type" value="1">
                            <div class="mb-3">
                                <label for="exampleInput1" class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" value="">
                            </div>
                            <div class="mb-3">
                                <label for="exampleInput1">Phone</label>
                                <input type="text" class="form-control" name="phone" value="">
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
            <div class="col-md-6 mt-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Change Password</div>
                    </div> 
                    <div class="card-body">
                        <form action="" method="post">
                            @csrf
                            <input type="hidden" name="type" value="2">
                            <div class="mb-3">
                                <label for="" class="form-lable">Old Password</label>
                                <input type="password" name="old_password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="" class="form-lable">New Password</label>
                                <input type="password" class="form-control" name="password">
                            </div>
                            <div class="mb-3">
                                <label for="" class="form-lable">Confirm New Password</label>
                                <input type="text" class="form-control" name="password_confirmation">
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Change</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</div>
    </main>


    @include('admin.layouts.sections.toast')
    <script type="text/javascript">
        $(function () {
            var error = '{{ $errors->count() > 0 ? $errors->first() : ""}}';
            var success = '{{session('success') ? session('success') : ""}}';
            var fail = '{{session('fail') ? session('fail') : ""}}';
            if(error && error != ""){
                showToast(error, 2);
            }
            if(success && success != ""){
                showToast(success, 1);
            }
            if(fail && fail != ""){
                showToast(fail, 2);
            }
        });

        function showToast(message, status){
            //success
            if(status == 1){
                var toastItem = document.getElementById('successToast')
                $('#successToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem);
                toast.show();
            }
            //fail
            else if(status == 2){
                var toastItem = document.getElementById('failToast')
                $('#failToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem);
                toast.show();
            }
            else{
                var toastItem = document.getElementById('failToast')
                $('#failToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem);
                toast.show();
            }
        }

    </script>
            

@endsection
