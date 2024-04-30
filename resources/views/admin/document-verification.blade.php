@extends('admin.layouts.app')

@section('content')

<main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5" >
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Document Verification</h2>
                            <div class="card-subtitle">With user details </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Simple DataTables example-->
                    <table class="table table-bordered data-table">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration</th>
                                <th>Documet Status</th>
                                <th>View Documet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $id =  Request::get('page');
                            if(!isset($id)){
                                $id=1;
                            }
                            @endphp
                            @foreach($users as $user)
                            <tr>
                                <td>{{(($id-1)*10) + $loop->iteration }}</td>
                                <td>
                                    @if(isset($user->name))
                                        {{$user->name}}
                                    @else
                                        No Name
                                    @endif
                                </td>
                                <td>
                                    @if(isset($user->email))
                                        {{$user->email}}
                                    @else
                                        Not available
                                    @endif
                                </td>
                                <td>
                                    @if(isset($user->created_at))
                                        {{$user->created_at->format('d-m-Y')}}
                                    @else
                                        Not available
                                    @endif
                                </td>
                                <td>
                                    @if($user->is_document_verify == 1)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                    <span class="badge bg-warning text-dark">Not Verified</span>
                                    @endif
                                </td>
                                <td>
                                <!-- <a href="" style="color:black;" class="text-decoration-none"><div class="nav-link-icon"><i class="material-icons">visibility</i></div></a> -->
                                    <a href="{{url('admin/document-verification/view')}}/{{$user->id}}"><button type="button" class="btn btn-sm btn-primary">&nbsp;<i class="material-icons">visibility</i>&nbsp;</button></a>
                                </td>
                            </tr>
                            @endforeach
                            <tr>
                                <td></td>
                            </tr>
                        </tbody>
                        
                    </table>
                    {{$users->links()}}
                </div>
            </div>
        </div>
    </main>

<script type="text/javascript">
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




</script>
            




@endsection
