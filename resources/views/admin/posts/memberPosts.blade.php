@extends('admin.layouts.app')

@section('content')
    <main>
        <!-- Main dashboard content-->
        <div class="container-xl p-5">
            <div class="card card-raised">
                <div class="card-header bg-transparent px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-4">
                            <h2 class="card-title mb-0">Posts</h2>
                            <div class="card-subtitle">All posts details </div>
                        </div>

                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Simple DataTables example-->
                    <table class="table table-bordered data-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>User Name</th>
                                <th>Media</th>
                                <th>Title</th>
                                <th>Link</th>
                                <th>Created At</th>
                                <th>Number Of Comments</th>
                                <th>Details</th>
                                <th>Status</th>
                                <th>Action</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </main>
    <!-- show modal  post start -->
    <div class="modal" id="showPostModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <u>
                        <h5 class="modal-title">Post Details</h5>
                    </u>
                    <button type="button" class="close" data-dismiss="modal" onclick="close_modal()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="modal-body">
                        <h5>Content</h5>
                        <p id="content_data"></p>
                        <hr>
                        <h5>Summarize</h5>
                        <p id="summarize_data"></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- show modal post end -->

    <!-- show modal post status start -->

    <div class="modal fade" id="confirm_status_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status">Are you sure to change
                        the status of
                        the post?</h6>
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

    <!-- show modal post status end -->



    <!-- show modal post status start -->

    <div class="modal fade" id="confirm_status_modal_comment" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h6 class="mb-0" style="text-align: center;width: 100%;" id="message_status_comment">Are you sure to
                        change
                        the status of
                        this comment?</h6>
                    <button type="button" class="btn-close" style="cursor: pointer !important;" aria-label="Close"
                        onclick="close_status_comment()"></button>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" style="cursor: pointer !important;" class="saveBtn base_background_color yes_no"
                        onclick="close_status_comment()">No</button>&nbsp
                    <button type="button" style="cursor: pointer !important;" class="saveBtn base_background_color yes_no"
                        onclick="confirm_status_comment()">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- show modal post status end -->


    <!---------------------- MODAL COMMENT START ------------------------>
    <div class="modal fade" id="comments_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Comments</h5>
                    <button type="button" class="close" onclick="close_comments()" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>


                <div class="modal-body">
                    <table class="table table-bordered data-table-comments">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Profile</th>
                                <th>User Name</th>
                                <th>Email</th>
                                <th>Comment</th>
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
    <!----------------------- MODAL COMMENT END ---------------------------->



    @include('admin.layouts.sections.toast')


    <script type="text/javascript">
        let status_id = '';

        let user_id = `{{ $user_id }}`;

        let group_id = `{{ $group_id }}`;

        let comment_id = '';


        $(function() {
            fetchData();
        });

        function fetchData() {
            // Check if the DataTable is already initialized
            if ($.fn.dataTable.isDataTable('.data-table')) {
                $('.data-table').DataTable().destroy();
            }
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url('admin/posts_data_user') }}",
                    type: 'GET',
                    data: {
                        _token: "{{ csrf_token() }}",
                        user_id: user_id,
                        group_id: group_id
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'post_user',
                        name: 'post_user'
                    },
                    {
                        data: 'media_url',
                        name: 'media_url',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'link',
                        name: 'link'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'comment_count',
                        name: 'comment_count',
                    },
                    {
                        data: 'id',
                        name: 'id',
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
                        data: 'comment',
                        name: 'comment',
                        orderable: false,
                        searchable: false
                    }
                ],
                createdRow: function(row, data, dataIndex) {
                    if (!data.post_user || data.post_user.trim() === '') {
                        $('td:eq(1)', row).text('Not Available');
                    }
                    if (!data.title || data.title.trim() === '') {
                        $('td:eq(3)', row).text('Not Available');
                    }
                    if (!data.link || data.link.trim() === '') {
                        $('td:eq(4)', row).text('Not Available');
                    }
                    if (!data.created_at || data.created_at.trim() === '') {
                        $('td:eq(5)', row).text('Not Available');
                    }

                }



            });

        };

        function showPost(postId) {
            $('#content_data').html('');
            $('#summarize_data').html('');
            $.ajax({
                url: "{{ url('admin/posts') }}/" + postId,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    $('#content_data').html(response.content);
                    $('#summarize_data').html(response.summarize);
                    if (!response.content || response.content.trim() === '') {
                        $('#content_data').html('Not Available');
                    }
                    if (!response.summarize || response.summarize.trim() === '') {
                        $('#summarize_data').html('Not Available');
                    }
                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                }
            });
            $('#showPostModal').modal('show');
        }

        function close_modal() {
            $('#showPostModal').modal('hide');
        }

        function changeStatus(status, is_status) {
            status_id = status;
            if (is_status == 1) {
                $('#message_status').html('Do you really want to deactivate this post?')
            } else {
                $('#message_status').html('Do you really want to activate this post?')
            }
            $('#confirm_status_modal').modal('show');
        }

        function confirm_status() {
            $.ajax({
                url: "{{ url('admin/posts_status_change') }}",
                type: "post",
                data: {
                    'post_id': status_id,
                    "_token": `{{ csrf_token() }}`
                },
                dataType: "json",
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
                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                }
            });
            $('#confirm_status_modal').modal('hide');
        }

        //============ SHOW COMMENT ===================//
        function showComment(id_post) {
            $('#comments_modal').modal('show');

            // Check if the DataTable is already initialized
            if ($.fn.dataTable.isDataTable('.data-table-comments')) {
                $('.data-table-comments').DataTable().destroy();
            }
            $('.data-table-comments').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/comment') }}/" + id_post,
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
                        data: 'comment',
                        name: 'comment'
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
                    // Check if name is not set
                    if (!data.user_name || data.user_name.trim() === '') {
                        $('td:eq(2)', row).text('No Name');
                    }
                }
            });



        }

        function close_comments() {
            $('#comments_modal').modal('hide');
        }

        function changeStatusComment(comment_ids, is_status) {
            $('#comments_modal').modal('hide');

            comment_id = comment_ids;
            if (is_status == 1) {
                $('#message_status_comment').html('Do you really want to deactivate this comment?')
            } else {
                $('#message_status_comment').html('Do you really want to activate this comment?')
            }
            $('#confirm_status_modal_comment').modal('show');

        }

        function confirm_status_comment() {

            $.ajax({
                url: "{{ url('admin/comment_status_change') }}",
                type: "post",
                data: {
                    'comment_id': comment_id,
                    "_token": `{{ csrf_token() }}`
                },
                dataType: "json",
                success: function(response) {

                    if (response.is_active == 1) {
                        $('.status_comment' + response.id).html(
                            '<div class="status_comment' + response.id +
                            '"><span class="badge badge-success text-bg-success " >Active</span></div>');
                        $('.status_btn_comment' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatusComment(' + response
                            .id +
                            ',' + response.is_active +
                            ')" title="Make Inactive"><i class="material-icons">block</i></button></div>');

                    } else {
                        $('.status_comment' + response.id).html(
                            '<div class="status_comment' + response.id +
                            '"><span class="badge badge-danger text-bg-danger" >Inactive</span></div>');
                        $('.status_btn_comment' + response.id).html(
                            '<button class="btn btn-lg btn-icon" onclick="changeStatusComment(' + response
                            .id +
                            ',' + response.is_active +
                            ')" title="Make Active"><i class="material-icons">check_circle</i></button>');
                    }
                },
                error: function(xhr) {
                    console.log('Error:', xhr);
                }
            });
            $('#confirm_status_modal_comment').modal('hide');
            $('#comments_modal').modal('show');

        }

        function close_status_comment() {
            $('#confirm_status_modal_comment').modal('hide');
            $('#comments_modal').modal('show');

        }

       
    </script>

    @include('admin.layouts.sections.profile-view-modal')
@endsection
