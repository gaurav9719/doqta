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




        <!---------------------- MODAL MEMBERS START ------------------------>
        <div class="modal fade" id="members_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="myLargeModalLabel">Members</h5>
                        <button type="button" class="close" onclick="close_member()" data-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>


                    <div class="modal-body">
                        <table class="table table-bordered data-table-members">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Profile</th>
                                    <th>User Name</th>
                                    <th>Email</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

                    </div>

                </div>
            </div>
        </div>

        <!----------------------- MODAL MEMBERS END ---------------------------->




        <!-- show modal USERS status start -->

        <div class="modal fade" id="confirm_status_modal_users" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header justify-content-center">
                        <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status_comment">Are you sure
                            to
                            change
                            the status of
                            this user?</h6>
                        <button type="button" class="btn-close" style="cursor: pointer !important;" aria-label="Close"
                            onclick="close_status_users()"></button>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" style="cursor: pointer !important;"
                            class="saveBtn base_background_color yes_no" onclick="close_status_users()">No</button>&nbsp
                        <button type="button" style="cursor: pointer !important;"
                            class="saveBtn base_background_color yes_no" onclick="confirm_status_users()">Yes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- show modal USERS status end -->

          <!-- show modal DOMAIN status start -->

          <div class="modal fade" id="confirm_status_modal_domain" tabindex="-1" aria-labelledby="exampleModalLabel"
          aria-hidden="true">
          <div class="modal-dialog">
              <div class="modal-content">
                  <div class="modal-header justify-content-center">
                      <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status_comment">Are you sure
                          to
                          change
                          the status of
                          this domain?</h6>
                      <button type="button" class="btn-close" style="cursor: pointer !important;" aria-label="Close"
                          onclick="close_status_domain()"></button>
                  </div>
                  <div class="modal-footer justify-content-center">
                      <button type="button" style="cursor: pointer !important;"
                          class="saveBtn base_background_color yes_no" onclick="close_status_domain()">No</button>&nbsp
                      <button type="button" style="cursor: pointer !important;"
                          class="saveBtn base_background_color yes_no" onclick="confirm_status_domain()">Yes</button>
                  </div>
              </div>
          </div>
      </div>

      <!-- show modal DOMAIN status end -->


        <!-- Main dashboard content-->
        <div class="container-xl p-5">
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Partner Domains</h2>
                            <div class="card-subtitle">All Domain details </div>
                        </div>
                        <div class="d-flex gap-2 me-n2">
                            <button class="btn btn-lg btn-text-primary btn-icon" type="button" id="add"
                                data-bs-toggle="modal" data-bs-target="#createStats"><i
                                    class="material-icons">add</i></button>
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
                                <th>Number of Users</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Users</th>
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

        let user_id_status = '';


        $(function() {
            fatchData();
        });


        function fatchData() {
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/partners-domain') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'total_users',
                        name: 'total_users',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'users',
                        name: 'users',
                        orderable: false,
                        searchable: false
                    },
                ],
                createdRow: function(row, data, dataIndex) {
                    // Check if name is not set
                    if (!data.name || data.name.trim() === '') {
                        $('td:eq(2)', row).text('No Name');
                    }
                }



            });

        };

        function showToast(message, status) {
            //success
            if (status == 1) {
                var toastItem = document.getElementById('successToast')
                $('#successToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem);
                toast.show();
            }
            //fail
            else if (status == 2) {
                var toastItem = document.getElementById('failToast')
                $('#failToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem);
                toast.show();
            } else {
                var toastItem = document.getElementById('failToast')
                $('#failToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem);
                toast.show();
            }
        }

        function editDomain(id) {
            $.ajax({
                url: `{{ url('admin/partners-domain/${id}') }}`,
                method: 'GET',
                data: {
                    "_token": `{{ csrf_token() }}`,
                    "type": 1
                },
                success: function(data) {
                    $("#id").val(data.id);
                    $("#domain").val(data.name);
                    $("#updateStats").modal('show');
                },
                error: function(e) {
                    console.log(e.responseText);
                }
            });
        }


        $(document).ready(function() {
            //create stats
            $("#createStatsForm").submit(function(event) {
                event.preventDefault();
                // $("#submitBtn").html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"> </span> &nbsp;&nbsp;Loading...`);
                $("#submitBtn").attr("disabled", true);

                var form = $(this);
                var formData = form.serialize();

                $.ajax({
                    type: "POST",
                    url: "{{ url('admin/partners-domain') }}",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    data: formData,
                    dataType: "json",
                    encode: true,
                    success: function(data) {

                        if (data.status == 200) {
                            $('#createStatsForm').each(function() {
                                this.reset();
                            });
                            $("#submitBtn").attr("disabled", false);
                            $("#createStats").modal('hide');
                            $('.data-table').DataTable().destroy();
                            fatchData();

                            showToast(data.message, 1);
                        } else if (data.status == 400) {
                            $("#submitBtn").attr("disabled", false);
                            showToast(data.message, 2);
                        }
                    },
                    error: function(e) {
                        console.log(e.responseText);
                    }
                });

            });



            //update stats
            $("#updateStatsForm").submit(function(event) {
                event.preventDefault();

                $("#submitBtn").attr("disabled", true);

                var form = $(this);
                var formData = form.serialize();

                $.ajax({
                    type: "PUT",
                    url: `{{ url('admin/partners-domain/1') }}`,
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    data: formData,
                    dataType: "json",
                    encode: true,
                    success: function(data) {
                        if (data.status == 200) {
                            $('#updateStatsForm').each(function() {
                                this.reset();
                            });
                            $("#submitBtn").attr("disabled", false);
                            $("#updateStats").modal('hide');
                            $('.data-table').DataTable().destroy();
                            fatchData();

                            showToast(data.message, 1);
                        } else if (data.status == 400) {
                            $("#submitBtn").attr("disabled", false);

                            showToast(data.message, 2);
                        } else {
                            console.log(data);
                        }
                    },
                    error: function(e) {
                        console.log(e.responseText);
                    }
                });

            });

        });

        function closeModel() {
            $("#matchTableBody").html(`<tr><th colspan="4">No Record Found</th></tr>`);
        }


        function changeStatus(id) {
            if (confirm("Are you sure you want to change the domain status?")) {
                $.ajax({
                    url: `{{ url('admin/partners-domain/${id}') }}`,
                    method: 'DELETE',
                    data: {
                        "_token": `{{ csrf_token() }}`,
                        'type': '1'
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            $('.data-table').DataTable().destroy();
                            fatchData();
                            showToast(data.message, 1);
                        }
                    },
                    error: function(e) {
                        console.log(e.responseText);
                    }
                });
            } else {
                console.log('Fail')
            }
        }

        function changeEmloyeeStatus(id) {
            if (confirm("Are you sure you want to change the employee status?")) {
                // console.log(id);
                $.ajax({
                    url: `{{ url('admin/partners-domain/${id}') }}`,
                    method: 'DELETE',
                    data: {
                        "_token": `{{ csrf_token() }}`,
                        'type': '2'
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            viewEmployees(data.id);
                            showToast(data.message, 1);

                        }
                    },
                    error: function(e) {
                        console.log(e.responseText);
                    }
                });
            } else {
                console.log('Fail')
            }
        }



        function viewEmployees(domain_id) {

            $('#members_modal').modal('show');

            // Check if the DataTable is already initialized
            if ($.fn.dataTable.isDataTable('.data-table-members')) {
                $('.data-table-members').DataTable().destroy();
            }

            $('.data-table-members').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url('admin/domain_users') }}",
                    type: 'GET',
                    data: function(d) {
                        d.domain_id = domain_id; // Add domain_id to the request data
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'profile',
                        name: 'profile',
                    },
                    {
                        data: 'user_name',
                        name: 'user_name',
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'gender',
                        name: 'gender',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                createdRow: function(row, data, dataIndex) {
                    // Check NAME
                    if (!data.user_name || data.user_name.trim() === '') {
                        $('td:eq(2)', row).text('No Name');
                    }
                    // Check GENDER
                    if (data.gender == 1) {
                        $('td:eq(4)', row).text('Male');
                    }else if(data.gender == 2){
                        $('td:eq(4)', row).text('Female');
                    }else{
                        $('td:eq(4)', row).text('Other');
                    }
                }
            });

        }

        function close_member() {
            
            $("#members_modal").modal('hide');
        }

////=======================    FOR CHANGE STATUS USERS  ===============================//

        function changeStatusUser(user_ids, is_status) {
            $('#members_modal').modal('hide');

            user_id_status = user_ids;

            if (is_status == 1) {
                $('#message_status_comment').html('Do you really want to deactivate this user?')
            } else {
                $('#message_status_comment').html('Do you really want to activate this user?')
            }
            $('#confirm_status_modal_users').modal('show');

        }

        function confirm_status_users() {

            $.ajax({
                url: `{{ url('admin/users/${user_id_status}') }}`,
                method: 'PUT',
                data: {
                    "_token": `{{ csrf_token() }}`
                },
                success: function(response) {

                    if (response.is_active == 1) {
                        $('.status_users' + response.id).html(
                            '<span class="badge badge-success text-bg-success " >Active</span>');
                        $('.status_btn_users' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatusUser(' + response
                            .id +
                            ',' + response.is_active +
                            ')" title="Make Inactive"><i class="material-icons">block</i></button></div>');

                    } else {
                        $('.status_users' + response.id).html(
                            '<span class="badge badge-danger text-bg-danger" >Inactive</span>');
                        $('.status_btn_users' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatusUser(' + response
                            .id +
                            ',' + response.is_active +
                            ')" title="Make Active"><i class="material-icons">check_circle</i></button>');
                    }

                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                }
            });
            $('#confirm_status_modal_users').modal('hide');
            $('#members_modal').modal('show');

        }

        function close_status_users() {

            $('#confirm_status_modal_users').modal('hide');
            $('#members_modal').modal('show');

        }

////=======================     FOR CHANGE STATUS DOMAIN   ===============================//
function changeStatusDomain(user_ids, is_status) {

            $('#members_modal').modal('hide');

            $('#confirm_status_modal_domain').modal('show');

            user_id_status = user_ids;

            if (is_status == 1) {
                $('#message_status_comment').html('Do you really want to deactivate this user?')
            } else {
                $('#message_status_comment').html('Do you really want to activate this user?')
            }
            $('#confirm_status_modal_users').modal('show');

        }

        function confirm_status_domain() {

            $.ajax({
                url: `{{ url('admin/users/${user_id_status}') }}`,
                method: 'PUT',
                data: {
                    "_token": `{{ csrf_token() }}`
                },
                success: function(response) {

                    if (response.is_active == 1) {
                        $('.status_users' + response.id).html(
                            '<span class="badge badge-success text-bg-success " >Active</span>');
                        $('.status_btn_users' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatusUser(' + response
                            .id +
                            ',' + response.is_active +
                            ')" title="Make Inactive"><i class="material-icons">block</i></button></div>');

                    } else {
                        $('.status_users' + response.id).html(
                            '<span class="badge badge-danger text-bg-danger" >Inactive</span>');
                        $('.status_btn_users' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatusUser(' + response
                            .id +
                            ',' + response.is_active +
                            ')" title="Make Active"><i class="material-icons">check_circle</i></button>');
                    }

                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                }
            });
            $('#confirm_status_modal_domain').modal('hide');
            $('#members_modal').modal('hide');
        }

        function close_status_domain() {

            $('#confirm_status_modal_domain').modal('hide');
            $('#members_modal').modal('hide');

        }




    </script>


    @include('admin.layouts.sections.create-domain')
    @include('admin.layouts.sections.update-domain')
    @include('admin.layouts.sections.toast')
@endsection
