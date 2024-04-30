@extends('admin.layouts.app')

@section('content')
                <main>
                    <!-- Page header-->
                    <header class="bg-dark">
                        <div class="container-xl px-5"><h1 class="text-white py-3 mb-0 display-6">User: {{$user->name ? $user->name : "No Name"}}</h1></div>
                    </header>
                    <!-- Account billing page content-->
                    <div class="container-xl p-5">
                        
                        <!-- Payment methods-->
                        <h2 class="font-monospace text-expanded text-uppercase fs-6 my-4">User Identity</h2>
                        <div class="row gx-5">
                            @if(isset($document['identity']))
                                @foreach($document['identity'] as $identity)
                                
                                <div class="col-md-6 mb-5">
                                    <!-- Payment method card 1-->
                                    <div class="card ">

                                        <img src="{{ asset('storage/'.$identity['document'] ) }}" alt="Mastercard Icon" onclick="viewProfile('{{$identity['document']}}')" style="height: auto; border:2px solid black; border-radius: 5px; margin: 20px;" />
                                        @if($identity['verified_status'] == 1)
                                        <div class="card-actions p-2 justify-content-center bg-success" >
                                            <h4 class="text-white">Verified</h4>
                                        </div>
                                        @elseif($identity['verified_status'] == 2)
                                        <div class="card-actions p-2 justify-content-center bg-danger" >
                                            <h4 class="text-white">Rejected</h4>
                                        </div>
                                        @else
                                        <div class="card-actions p-3 justify-content-end">
                                            <div class="card-action-buttons">
                                                <form class="form-inline" action="{{url('admin/document-verification/view')}}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="type" value="1">
                                                    <input type="hidden" name="id" value="{{$identity['id']}}">
                                                    <button class="btn btn-danger " type="submit" name="reject" onclick="return confirm('Are you sure, you want to reject the document?')" value="1">Reject</button>
                                                    <button class="btn btn-success" type="submit" name="verify" onclick="return confirm('Are you sure, you want to verify the document?')" value="1">Verify</button>
                                                </form>
                                                
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            @else
                            <div class="col-md-6 mb-5">     
                                Not Available
                            </div>
                            
                            @endif
                            <!-- <div class="col-md-6 mb-5"> -->
                                <!-- Payment method card 2-->
                                <!-- <div class="card ">
                                    <img src="{{asset('assets/img/paper/back.jpg')}}" alt="Mastercard Icon" style="height: auto; border:2px solid black; border-radius: 5px; margin: 20px;" />
                                       
                                    <div class="card-actions p-2 justify-content-center bg-success" >
                                        <h4 class="text-white">Verified</h4>  -->
                                        <!-- <div class="card-action-buttons">
                                            <button class="btn btn-text-primary" type="button">Reject</button>
                                            <button class="btn btn-text-primary" type="button">Verify</button>
                                        </div> -->
                                    <!-- </div>
                                </div>
                            </div> -->
                            
                        </div>
                            <h2 class="font-monospace text-expanded text-uppercase fs-6 my-4">User Certificate</h2>
                        <div class="row gx-5">
                            @if(isset($document['certificate']))
                                @foreach($document['certificate'] as $certificate)
                                <div class="col-md-6 mb-5">
                                    <!-- Payment method card 2-->
                                    <div class="card h-100">
                                        <img src="{{ asset('storage/'.$certificate['medicial_document'] ) }}" alt="Mastercard Icon" onclick="viewProfile('{{$certificate['medicial_document']}}')" style="height: auto; border:2px solid black; border-radius: 5px; margin: 20px;" />
                                        @if($certificate['verified_status'] == 1)
                                        <div class="card-actions p-2 justify-content-center bg-success" >
                                            <h4 class="text-white">Verified</h4>
                                        </div>
                                        @elseif($certificate['verified_status'] == 2)
                                        <div class="card-actions p-2 justify-content-center bg-danger" >
                                            <h4 class="text-white">Rejected</h4>
                                        </div>
                                        @else
                                            <div class="card-actions p-3 justify-content-end">
                                                <div class="card-action-buttons">
                                                <form class="form-inline" action="{{url('admin/document-verification/view')}}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="type" value="2">
                                                    <input type="hidden" name="id" value="{{$certificate['id']}}">
                                                    <button class="btn btn-danger " type="submit" name="reject" onclick="return confirm('Are you sure, you want to reject the document?')" value="1">Reject</button>
                                                    <button class="btn btn-success" type="submit" name="verify" onclick="return confirm('Are you sure, you want to verify the document?')" value="1">Verify</button>
                                                </form>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            @else
                            <div class="col-md-6 mb-5">     
                                Not Available
                            </div>
                            @endif

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

        function viewProfile(path){
            var fullPath= `storage/${path}`;
            // console.log(fullPath);
            var html=`<img src='{{asset('${fullPath}')}}' alt="Mastercard Icon" style="border:2px solid black; border-radius: 5px; width:800px; height:auto;" />`;
            $("#modelBody").html(html);
            $("#viewProfile").modal('show');
        }
    </script>
                @include('admin.layouts.sections.view-image-modal')
@endsection