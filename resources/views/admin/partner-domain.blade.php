@extends('admin.layouts.app')

@section('content')

<style>
    #add {
    color: #6200ea;
    background-color: #efe6ff;
    outline: 3px solid #d5c3ff;
}
</style>

<main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5" >
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Partner Domains</h2>
                            <div class="card-subtitle">All Domain details </div>
                        </div>
                        <div class="d-flex gap-2 me-n2">
                            <button class="btn btn-lg btn-text-primary btn-icon" type="button" id="add" data-bs-toggle="modal" data-bs-target="#createStats"><i class="material-icons">add</i></button>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Simple DataTables example-->
                    <table class="table table-bordered data-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Name</th>
                                <th>Created at</th>
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
    @include('admin.layouts.sections.view-employee-model')


<script type="text/javascript">
    $(function () {
        fatchData();
    });

    
    function fatchData(){
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{url('admin/partners-domain')}}",
            columns: [
                {data: 'DT_RowIndex' , orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'created_at', name: 'created_at'},
                {data: 'status', name: 'status'},
                {data: 'edit', name: 'edit'},
            ],
            createdRow: function (row, data, dataIndex) {
                // Check if name is not set
                if (!data.name || data.name.trim() === '') {
                    $('td:eq(2)', row).text('No Name');
                }
            }
            

            
        });
        
    };


    function viewEmployees(id){
        
        $.ajax({
            url : `{{url('admin/partners-domain/${id}')}}`,
            method : 'GET',
            data : {"_token": `{{csrf_token()}}` , "type": 2},
            success : function(res){
                // console.log(res);
                if(res.status == 200){
                    var a;
                    var html=` 
                    <table class="table">
                      <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Corporate Email</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                                </thead>
                                <tbody id="matchTableBody">`;
                                    
                        var a=res.data;
                                    
                        for(var i=0; i < a.length; i++){
                            if(a[i]['is_active'] == 1){
                                var status= '<span class="badge badge-success text-bg-success" >Active</span>';
                                var action= `<button class="btn btn-icon" onclick="changeEmloyeeStatus(${a[i]['id']})" title="Block User"><i class="material-icons">block</i></button>`;
                            }
                            else{
                                var status= '<span class="badge badge-danger text-bg-danger">Inactive</span>';
                                var action= `<button class="btn btn-lg btn-icon" onclick="changeEmloyeeStatus(${a[i]['id']})" title="Make Active"><i class="material-icons">check_circle</i></button>`;
                            }
                            html+=`<tr>
                                    <th scope="row">${i+1}</th>
                                    <td>${a[i]['employee_name']}</td>
                                    <td>${a[i]['corporate_email']}</td>
                                    <td>${status}</td>
                                    <td>${action}</td>
                                </tr>`;
                        }
                        
                    html+=`</tbody>
                            </table>`;
                        $("#matchDataBody").html(html);
                        $("#viewMatch").modal('show');
                
                }
                else{
                    $("#viewMatch").modal('show');
                }
            },
            error : function(e){
                console.log(e.responseText);
            }
        })
    }


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

    function editDomain(id){
        // console.log(id);
        $.ajax({
            url : `{{url('admin/partners-domain/${id}')}}`,
            method : 'GET',
            data : {"_token": `{{csrf_token()}}`, "type": 1},
            success : function(data){
                $("#id").val(data.id);
                $("#domain").val(data.name);
                $("#updateStats").modal('show');
            },
            error:function(e){
                console.log(e.responseText);
                }
        });
    }


    $(document).ready(function () {
    //create stats
        $("#createStatsForm").submit(function (event) {
            event.preventDefault();
            // $("#submitBtn").html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"> </span> &nbsp;&nbsp;Loading...`);
            $("#submitBtn").attr("disabled", true);
            
            var form = $(this);
            var formData = form.serialize();

            $.ajax({
                type: "POST",
                url: "{{url('admin/partners-domain')}}",
                headers: {
                    "X-CSRF-TOKEN":"{{csrf_token()}}"
                    },
                data: formData,
                dataType: "json",
                encode: true,
                success : function(data){
                    
                    if(data.status == 200){
                        $( '#createStatsForm' ).each(function(){
                            this.reset();
                        });
                        $("#submitBtn").attr("disabled", false);
                        $("#createStats").modal('hide');
                        $('.data-table').DataTable().destroy(); 
                        fatchData();

                        showToast(data.message, 1);
                    }
                    else if(data.status == 400){
                        $("#submitBtn").attr("disabled", false);
                        showToast(data.message, 2);
                    }
                },
                error : function(e){
                    console.log(e.responseText);
                }
            });
            
        });



        //update stats
        $("#updateStatsForm").submit(function (event) {
            event.preventDefault();
            // $("#submitBtn").html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"> </span> &nbsp;&nbsp;Loading...`);
            $("#submitBtn").attr("disabled", true);
            
            var form = $(this);
            var formData = form.serialize();

            $.ajax({
            type: "PUT",
            url: `{{url('admin/partners-domain/1')}}`,
            headers: {
                "X-CSRF-TOKEN":"{{csrf_token()}}"
                },
            data: formData,
            dataType: "json",
            encode: true,
            success: function (data){
                if(data.status == 200){
                    // console.log(data);  
                    $( '#updateStatsForm' ).each(function(){
                        this.reset();
                    });
                    $("#submitBtn").attr("disabled", false);
                    $("#updateStats").modal('hide');
                    $('.data-table').DataTable().destroy(); 
                    fatchData();

                    showToast(data.message, 1);
                }
                else if(data.status == 400){
                    $("#submitBtn").attr("disabled", false);

                    showToast(data.message, 2);
                }
                else{
                    console.log(data);  
                }
            },
            error:function(e){
                    console.log(e.responseText);
                    }
            });

        });

    });

    function closeModel(){
        $("#matchTableBody").html(`<tr><th colspan="4">No Record Found</th></tr>`);
    }


    function changeStatus(id){
        if(confirm("Are you sure you want to change the domain status?")){
            // console.log(id);
            $.ajax({
                url : `{{url('admin/partners-domain/${id}')}}`,
                method : 'DELETE',
                data : {"_token": `{{csrf_token()}}` , 'type' : '1'},
                success : function(data){
                    if(data.status == 200){
                        $('.data-table').DataTable().destroy(); 
                        fatchData();
                        showToast(data.message, 1);
                    }
                },
                error: function(e){
                    console.log(e.responseText);
                }
            });
        }
        else{
            console.log('Fail')
        }
  }

  function changeEmloyeeStatus(id){
        if(confirm("Are you sure you want to change the employee status?")){
            // console.log(id);
            $.ajax({
                url : `{{url('admin/partners-domain/${id}')}}`,
                method : 'DELETE',
                data : {"_token": `{{csrf_token()}}`, 'type' : '2'},
                success : function(data){
                    if(data.status == 200){
                        viewEmployees(data.id);
                        showToast(data.message, 1);
                        
                    }
                },
                error: function(e){
                    console.log(e.responseText);
                }
            });
        }
        else{
            console.log('Fail')
        }
  }
</script>
            



@include('admin.layouts.sections.create-domain')
@include('admin.layouts.sections.update-domain')
@include('admin.layouts.sections.toast')

@endsection
