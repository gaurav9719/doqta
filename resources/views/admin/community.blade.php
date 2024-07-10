@extends('admin.layouts.app')

@section('content')
    <style>
        .position-relative {
            position: relative;
        }

        .cover_pics {
            display: block;
            width: 100px;
            height: 100px;
        }


        .upload-label {
            width: 25px;
            height: 25px;
            position: absolute;
            top: 0%;
            left: 62%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            font-size: 1rem;
            padding: 1px;
            border-radius: 42%;
            cursor: pointer;
        }

        .upload-icon {
            display: inline-block;
        }

        .upload-input {
            display: none;
        }

        .modal-content {
            width: 121%;
        }
    </style>


    <main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5">
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Community</h2>
                            <div class="card-subtitle">All community details </div>
                        </div>
                        <div class="d-flex gap-2 me-n2">
                            <button class="btn btn-lg btn-text-primary btn-icon" type="button" id="add"
                                onclick="add_community()"><i class="material-icons">add</i></button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Simple DataTables example-->
                    <table class="table table-bordered data-table data-table-groups">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Cover Picture</th>
                                <th>Name</th>
                                <th>Number of Admin</th>
                                <th>Number of Members</th>
                                <th>Created By</th>
                                <th>Created at</th>
                                <th>Status</th>
                                <th>Actions</th>
                                <th>Datails</th>
                                <th>Edit</th>
                                <th>Members</th>


                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </main>

    <!----------------- show modal community status start --------------------->

    <div class="modal fade" id="confirm_status_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status">Are you sure to change
                        the status of
                        the community?</h6>
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

    <!----------------------- MODAL END ------------------------------------>


    <!------------------- ADD COMMUNITY MODAL START --------------------->
    <div class="modal fade" id="addCommunity" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="add_community_form" action="javascript:void(0)" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add Community</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <center>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <label for="cover_pic_input" class="upload-label">
                                    <span class="upload-icon">+</span>
                                </label>
                                <img class="cover_pics" src="{{ asset('/assets/img/user/community2.jpg') }}" id="cover_pic"
                                    name="cover_pic">
                                <input type="file" name="cover_pic_input" id="cover_pic_input" class="upload-input">
                            </div>
                        </center>

                        <div class="mb-3">
                            <label for="name" class="col-form-label">Name:</label>
                            <input type="text" class="form-control" id="name" name="name">
                            <label id="name-error" class="error" for="name"></label>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="col-form-label">Description:</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> &nbsp;
                        <button type="submit" id="add_submit_btn" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!----------------- ADD COMMUNITY MODAL END --------------------->

    <!-- show modal  post start -->
    <div class="modal" id="showCommunityModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <u>
                        <h5 class="modal-title">Community Details</h5>
                    </u>
                    <button type="button" class="close" data-dismiss="modal" onclick="close_modal_details()"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-body">
                        <h5>Description</h5>
                        <p id="description_data"></p>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- show modal post end -->


    <!------------------- EDIT COMMUNITY MODAL START --------------------->
    <form id="edit_community_form" enctype="multipart/form-data">
        <div class="modal fade" id="editCommunity" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Edit Community</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <center>
                            <div class="col-12 col-sm-6 col-lg-3 ">

                                <label for="edit_cover_pic_input" class="upload-label">
                                    <span class="upload-icon">+</span>
                                </label>

                                <img class="cover_pics" src="{{ asset('/assets/img/user/community2.jpg') }}"
                                    id="edit_cover_pic" name="edit_cover_pic">

                                <input type="file" name="edit_cover_pic_input" id="edit_cover_pic_input"
                                    class="upload-input">
                            </div>
                        </center>



                        <div class="mb-3">
                            <label for="recipient-name" class="col-form-label">Name:</label>
                            <input type="text" class="form-control" id="edit_name" name="edit_name">

                            <label id="edit_name-error" class="error" for="edit_name"></label>
                        </div>

                        <div class="mb-3">
                            <label for="message-text" class="col-form-label">Description:</label>
                            <textarea class="form-control" id="edit_description" name="edit_description"></textarea>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button> &nbsp;
                        <button type="submit" class="btn btn-primary">EDIT</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!----------------- EDIT COMMUNITY MODAL END --------------------->




    <!------------------- CHANGE ROLE MODAL START --------------------->
    <form id="change_role_form" enctype="multipart/form-data">
        <div class="modal fade" id="change_role_modal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Member</h5>
                        <button type="button" class="btn-close" onclick="close_change_modal()"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="role" class="col-form-label">Change Role</label>
                            <select id="role" class="form-select" name="role">
                                <option value="owner">Owner</option>
                                <option value="admin">Admin</option>
                                <option value="moderator">Moderator</option>
                                <option value="member">Member</option>
                            </select>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="close_change_modal()">Cancel</button>
                        &nbsp;
                        <button type="submit" class="btn btn-primary">Change</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!----------------- CHANGE ROLE MODAL END --------------------->



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
                    <table class="table table-bordered data-table data-table-members">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Profile</th>
                                <th>User Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Number of Posts</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Assign Role</th>
                                <th>Posts</th>
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



    <!-- show modal post status start -->

    <div class="modal fade" id="confirm_status_modal_users" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status_comment">Are you sure
                        to
                        change
                        the status of
                        this comment?</h6>
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

    <!-- show modal post status end -->



    @include('admin.layouts.sections.toast')


    <script type="text/javascript">
        let edit_id = '';

        let status_id = '';

        let group_id = '';

        let base_url = "{{ asset('storage') }}/";

        let member_id = '';

        let member_role = '';

        let user_id_status = '';


        const add_edit_url = "{{ url('admin/communities') }}";

        $(function() {
            fatchData();
        });


        function fatchData() {
            var table = $('.data-table-groups').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/community') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'logo',
                        name: 'logo',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'total_admin',
                        name: 'total_admin',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'member_count',
                        name: 'member_count',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_by',
                        name: 'created_by'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'edit',
                        name: 'edit',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'details',
                        name: 'details',
                        orderable: false,
                        searchable: false

                    },
                    {
                        data: 'edit_community',
                        name: 'edit_community',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false
                    },
                ],
                createdRow: function(row, data, dataIndex) {

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



        function changeStatus(status, is_status) {
            status_id = status;
            if (is_status == 1) {
                $('#message_status').html('Do you really want to deactivate this community?')
            } else {
                $('#message_status').html('Do you really want to activate this community?')
            }
            $('#confirm_status_modal').modal('show');
        }

        function confirm_status() {

            $.ajax({
                url: `{{ url('admin/community/${status_id}') }}`,
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
                }
            })

            $('#confirm_status_modal').modal('hide');

        }



        function closeModel() {
            $("#matchTableBody").html(`<tr><th colspan="4">No Record Found</th></tr>`);
        }

        function showMember(group_ids) {
            $('#members_modal').modal('show');
            group_id = group_ids;
            // Check if the DataTable is already initialized
            if ($.fn.dataTable.isDataTable('.data-table-members')) {
                $('.data-table-members').DataTable().destroy();
            }
            $('.data-table-members').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/members') }}/" + group_id,
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
                        data: 'role',
                        name: 'role',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_posts',
                        name: 'total_posts',
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
                        data: 'assign',
                        name: 'assign',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false
                    }


                ],
                createdRow: function(row, data, dataIndex) {
                    // Check if name is not set
                    if (!data.user_name || data.user_name.trim() === '') {
                        $('td:eq(2)', row).text('No Name');
                    }
                }
            });

        }

        function close_member() {
            $("#members_modal").modal('hide');
        }

        function showPosts(user_id) {
            window.location.href = `{{ url('admin/members') }}?group_id=${group_id}&user_id=${user_id}`;
        }

        function add_community() {
            document.getElementById("edit_community_form").reset();
            document.getElementById("add_community_form").reset();
            $("#cover_pic").attr("src", "{{ asset('/assets/img/user/community2.jpg') }}");
            $('#addCommunity').modal('show');
        }

        //============================   ADD COMMUNITY TO VALIDATE DATA START      ===============================//
        $('#add_community_form').validate({ // initialize the plugin
            rules: {
                name: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Please enter the name"
                }
            },
            submitHandler: function(form) {
                var formData1 = new FormData(form);
                $.ajax({
                    url: add_edit_url,
                    type: "POST",
                    dataType: "json",
                    data: formData1,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        $('#edit_name-error').show();
                        if (data == 403) {
                            $('#name-error').html('The community name is already in use');
                            $('#name-error').show();
                            return;
                        } else
                        if (data == 400) {
                            $('#name-error').html('Please enter the name ');
                            $('#name-error').show();
                            return;
                        }
                        // $('#addCommunity').modal('hide');
                        Swal.fire({
                            title: 'Success!',
                            text: 'Community added successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function() {
                            window.location.reload();
                        });
                    }
                });

            } //submit handler

        }); //validator 


        //==========================    ADD COMMUNITY TO  VALIDATE DATA  END     ============================//


        //============================   EDIT COMMUNITY TO VALIDATE DATA START      ===============================//
        $('#edit_community_form').validate({ // initialize the plugin
            rules: {
                edit_name: {
                    required: true,
                }
            },
            messages: {
                edit_name: {
                    required: "Please enter the name"
                }
            },
            submitHandler: function(form) {
                var formData2 = new FormData(form);
                formData2.append('id', edit_id);
                $.ajax({
                    url: add_edit_url,
                    type: "POST",
                    dataType: "json",
                    data: formData2,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data == 403) {
                            $('#edit_name-error').html('The community name is already in use');
                            $('#edit_name-error').show();
                            return;
                        } else
                        if (data == 400) {
                            $('#edit_name-error').html('Please enter the name ');
                            $('#edit_name-error').show();
                        }
                        if (data.cover_photo) {
                            $('#image_' + data.id + ' img').attr('src', base_url + data
                                .cover_photo);
                        }
                        $('#name_' + data.id).html(data.name);
                        $('#editCommunity').modal('hide');
                        Swal.fire({
                            title: 'Success!',
                            text: 'Community edited successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(function() {

                        });

                    }
                });

            } //submit handler

        }); //validator 

        //==========================    EDIT COMMUNITY TO  VALIDATE DATA  END     ============================//




        $(document).ready(function() {
            $('#cover_pic_input').change(function() {
                var input = this;
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#cover_pic').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            });

            $('#edit_cover_pic_input').change(function() {
                var input_edit = this;
                if (input_edit.files && input_edit.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#edit_cover_pic').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(input_edit.files[0]);
                }
            });
        });


        function editCommunity(id) {
            $('#edit_name-error').hide();
            document.getElementById("add_community_form").reset();
            document.getElementById("edit_community_form").reset();
            edit_id = id;
            $.ajax({
                url: `{{ url('admin/communities/${edit_id}') }}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#edit_name').val(response.name);
                    $('#edit_description').val(response.description);
                    if (response.cover_photo) {
                        $("#edit_cover_pic").attr("src", "{{ asset('/storage') }}/" + response.cover_photo);
                    } else {
                        $("#edit_cover_pic").attr("src", "{{ asset('/assets/img/user/community2.jpg') }}");
                    }
                    $('#editCommunity').modal('show');
                }

            });

        }


        function show_community(id) {
            $('#showCommunityModal').modal('show');
            $.ajax({
                url: `{{ url('admin/communities/${id}') }}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.description) {
                        $('#description_data').html(response.description);
                    } else {
                        $('#description_data').html('Not Available');

                    }
                }

            });
        }


        function close_modal_details() {
            $('#showCommunityModal').modal('hide');
        }


        function changeRole(id, role) {
            $("#members_modal").modal('hide');

            member_id = id;

            member_role = role;

            $("#role").val(member_role);

            $("#change_role_modal").modal('show');

        }

        function close_change_modal() {
            $("#change_role_modal").modal('hide');
            $("#members_modal").modal('show');
        }

        $('#change_role_form').on('submit', function(e) {
            e.preventDefault();
            let role_new = $('#role').val();

            $.ajax({
                url: `{{ url('admin/members/${member_id}') }}`,
                method: 'PUT',
                data: {
                    "_token": `{{ csrf_token() }}`,
                    "role": role_new
                },
                success: function(response) {
                    $('#role_member_span_' + member_id).html(role_new);
                    $("#change_role_modal").modal('hide');
                    $("#members_modal").modal('show');
                    $('#role_member_div_' + member_id).html(
                        '<button class="btn btn-lg btn-icon" onclick="changeRole(' + member_id +
                        ', \'' + role_new +
                        '\')" title="Make Active"><i class="fa-solid fa-user-pen"></i></button>');
                }
            })


        });



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

    </script>
@endsection
