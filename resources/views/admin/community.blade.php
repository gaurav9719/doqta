@extends('admin.layouts.app')

@section('content')

<main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5" >
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Community</h2>
                            <div class="card-subtitle">All community details </div>
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
                                <th>Number of Members</th>
                                <th>Created By</th>
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


<script type="text/javascript">
    $(function () {
        fatchData();
    });

    
    function fatchData(){
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{url('admin/community')}}",
            columns: [
                {data: 'DT_RowIndex' , orderable: false, searchable: false},
                {data: 'logo', name: 'logo' , orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'member_count', name: 'member_count' , orderable: false, searchable: false},
                {data: 'created_by', name: 'created_by'},
                {data: 'created_at', name: 'created_at'},
                {data: 'status', name: 'status' , orderable: false, searchable: false},
                {data: 'edit', name: 'edit' , orderable: false, searchable: false},

            ],
            createdRow: function (row, data, dataIndex) {
                // Check if name is not set
                if (!data.name || data.name.trim() === '') {
                    $('td:eq(2)', row).text('No Name');
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
            url : `{{url('admin/community/${id}')}}`,
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


    function closeModel(){
        $("#matchTableBody").html(`<tr><th colspan="4">No Record Found</th></tr>`);
    }
</script>
            




@endsection
