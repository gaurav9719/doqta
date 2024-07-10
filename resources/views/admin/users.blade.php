@extends('admin.layouts.app')

@section('content')
    <style>
        .modal {
            --bs-modal-width: 700px;
        }
    </style>

    <main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5">
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
                                <th>Datails</th>
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


    <!-- show modal community status start -->

    <div class="modal fade" id="confirm_status_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status">Are you sure to change
                        the status of
                        the user?</h6>
                    <button type="button" class="btn-close" style="cursor: pointer !important;" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" style="cursor: pointer !important;" class="saveBtn base_background_color yes_no"
                        data-bs-dismiss="modal">No</button>&nbsp
                    <button type="button" style="cursor: pointer !important;" class="saveBtn base_background_color yes_no"
                        onclick="confirm_status()">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- show modal community status end -->


    @include('admin.layouts.sections.toast')


    <script type="text/javascript">
        let id = '';

        $(function() {
            fatchData();
        });


        function fatchData() {
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/users') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'logo',
                        name: 'logo'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'phone_no',
                        name: 'phone_no'
                    },
                    {
                        data: 'registration_date',
                        name: 'registration_date',
                        searchable: false
                    },
                    {
                        data: 'email_verify',
                        name: 'email_verify'
                    },
                    {
                        data: 'details',
                        name: 'details'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'edit',
                        name: 'edit'
                    },

                ],
                createdRow: function(row, data, dataIndex) {
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


        function showToast(message, status) {
            //success
            if (status == 1) {
                var toastItem = document.getElementById('successToast')
                $('#successToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem)
                toast.show()
            }
            //fail
            else if (status == 2) {
                var toastItem = document.getElementById('failToast')
                $('#failToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem)
                toast.show()
            } else {
                var toastItem = document.getElementById('failToast')
                $('#failToastBody').html(message);
                var toast = new bootstrap.Toast(toastItem)
                toast.show()
            }
        }

        //show confirm modal
        function changeStatus(user_id, is_status) {
            id = user_id;
            if (is_status == 1) {
                $('#message_status').html('Do you really want to block this user?')
            } else {
                $('#message_status').html('Do you really want to unblock this user?')
            }
            $('#confirm_status_modal').modal('show');
        }

        //confirm change status
        function confirm_status() {

            $.ajax({
                url: `{{ url('admin/users/${id}') }}`,
                method: 'PUT',
                data: {
                    "_token": `{{ csrf_token() }}`
                },
                success: function(response) {
                    if (response.is_active == 1) {
                        $('.status_' + response.id).html(
                            '<div class="status_' + response.id +
                            '"><span class="badge badge-success text-bg-success " >Active</span></div>');
                        $('.status_btn' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatus(' + response.id +
                            ',' + response.is_active +
                            ')" title="Make Inactive"><i class="material-icons">block</i></button></div>');

                    } else {
                        $('.status_' + response.id).html(
                            '<div class="status_' + response.id +
                            '"><span class="badge badge-danger text-bg-danger" >Inactive</span></div>');
                        $('.status_btn' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatus(' + response.id +
                            ',' + response.is_active +
                            ')" title="Make Active"><i class="material-icons">check_circle</i></button>');
                    }
                    $('#confirm_status_modal').modal('hide');

                }
            })
        }




        function viewProfile(id) {
            $.ajax({
                url: `{{ url('admin/users/${id}') }}`,
                method: 'GET',
                data: {
                    "_token": `{{ csrf_token() }}`,
                    "type": 1
                },
                success: function(res) {
                    if (res.status == 200) {
                        var user = res.user;
                        if (user.name == null) {
                            user.name = "No name";
                        }
                        if (user.user_name == null) {
                            user.user_name = "Not available";
                        }
                        var html = `<div class="card" style="border-radius: .5rem;">
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
                                                <p class="text-muted">
                                        ${(user.phone_no != null && user.phone_no) ? user.phone_no : 'Not Available'}
                                                    </p>
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

                                          <div class="row pt-1">
                                            <div class="col-6 mb-3">
                                                <h6>Roles</h6>
                                                <p class="text-muted">`;
                        if (user.roles.length > 0) {
                        let  roleNames = user.roles.map(role => role.name).join(" "); 
                            html += roleNames;
                        } else {
                            html += 'Not Available';
                        }
                        html += `</p>
                                </div>
                                        </div>
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


        function closeModel() {
            $("#matchTableBody").html(`<tr><th colspan="4">No Record Found</th></tr>`);
        }
    </script>




    @include('admin.layouts.sections.profile-view-modal')
@endsection
