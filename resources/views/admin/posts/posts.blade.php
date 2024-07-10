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
                                <th>Details</th>
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







    @include('admin.layouts.sections.toast')


    <script type="text/javascript">
        $(function() {
            fetchData();
        });

        function fetchData() {
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('admin/posts_data') }}",
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
                        name: 'media_url'
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
                        name: 'created_at',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id',
                        name: 'id',
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




    </script>

    @include('admin.layouts.sections.profile-view-modal')
@endsection
