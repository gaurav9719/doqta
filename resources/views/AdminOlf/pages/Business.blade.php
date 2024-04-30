@extends('Admin.layouts.master')
@section('title',"Business")
@section('main_content')
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<style>
    @import "https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700";

    table.display {
        font-family: 'Muli';
        table-layout: fixed;
    }

    table.dataTable.compact tbody th,
    table.dataTable.compact tbody td,
    table.dataTable.compact tfoot td {
        /*padding: 0px 17px 0px 4px;*/
        padding: 0px 0px 0px 0px;
    }

    /* td.details-control {
        background: url(https://www.datatables.net/examples/resources/details_open.png) no-repeat center center;
        cursor: pointer;
        transition: .5s;
    } */

    /* /* tr.shown td.details-control {
        background: url(https://www.datatables.net/examples/resources/details_close.png) no-repeat center center;
        transition: .5s;
    } */

    /* td.details-control1 {
        background: url(https://www.datatables.net/examples/resources/details_open.png) no-repeat center center;
        cursor: pointer;
        transition: .5s;
    } */

    td.details-control2 {
        background: url(https://www.datatables.net/examples/resources/details_close.png) no-repeat center center;
        transition: .5s;
    }

    /* td.details-control2 {
        background: url(https://www.datatables.net/examples/resources/details_open.png) no-repeat center center;
        cursor: pointer;
        transition: .5s;
    } */

    /* tr.shown td.details-control2 {
        background: url(https://www.datatables.net/examples/resources/details_close.png) no-repeat center center;
        width: 0px transition: .5s;
    } */

    .fee-col {
        text-align: right;
        width: 8%;
    }

    .label-col {
        text-align: left;
        width: 32%;
    }

    .label-col2 {
        text-align: left;
        width: 31%;
    }

    .label-col3 {
        text-align: left;
        width: 30%;
    }

    tr.shown td {
        background-color: lightgrey !important;
        transition: .5s;
        font-weight: 800
    }

    td.invoice-date {
        background-color: rgba(237, 205, 255, .2);
        width: 100px;
    }

    td.invoice-author {
        background-color: rgba(237, 205, 255, .2);
        width: 100px;
    }

    td.invoice-notes {
        background-color: rgba(237, 205, 255, .2);
        white-space: normal !important;
    }

    td.details-control {
        background: url('https://www.datatables.net/examples/resources/details_open.png') no-repeat center center;
        cursor: pointer;
    }

    tr.shown td.details-control {
        background: url('https://www.datatables.net/examples/resources/details_close.png') no-repeat center center;
    }


    td.details-control1 {
        background: url('https://www.datatables.net/examples/resources/details_open.png') no-repeat center center;
        cursor: pointer;
    }

    tr.shown td.details-control1 {
        background: url('https://www.datatables.net/examples/resources/details_close.png') no-repeat center center;
    }
</style>

<section class="section">
    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Business</h4>
                    </div>
                    <div class="card-body">


                        <div class="table-responsive">

                            <table class="table table-striped" id="business-data-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            #
                                        </th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Profile</th>
                                        <th>Bio</th>
                                        <th>Phone No</th>
                                        <th>Status</th>

                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>

                            </table>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vertically Center -->
<div class="modal fade bd-example-modal-lg" id="userBusinessModal" tabindex="-1" role="dialog" aria-labelledby="userBusinessModal" aria-hidden="true" style="width: 100%;">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">


            <div id="userBusinessUser">

            </div>




        </div>
    </div>
</div>




<script type="text/javascript">
    var childEditors = {}; // Globally track created chid editors
    var childTable;
    var childTable2;
    var tableD;

    $(function() {

        // Return table with id generated from row's name field
        // function format(rowData) {
        //     var childTable = '<table  id="cl' + rowData.id + '" class=" table table-striped " style= "border: 1px solid black;" width="100%" cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        //         '<th>#</th >' +'<th>Business name</th >' +'<th>Business name</th >'+'<th>Business name</th >'+'<th>Business name</th >'
        //         '</table>';
        //     return $(childTable).toArray();
        // }


        function format(rowData) {
            var childTable = '<h5>Business</h5><table id="cl' + rowData.id + '" class=" table table-striped ">' +
                '</table>';
            return $(childTable).toArray();
        }

        function format2(rowData) {
            var childTable = '<table id="mt' + rowData.matterID + '" class="display compact nowrap w-100" width="100%">' +
                '<thead style="display:none"></thead >' +
                '</table>';
            return $(childTable).toArray();
        }

        function format3(rowData) {
            var childTable = '<table id="in' + rowData.invoice + '" class="display compact wrap w-100 cell-border" width="100%">' +
                '<thead>#</thead >' + '<thead>Name</thead >'
            '</table>';




            return $(childTable).toArray();
        }














        var table = $('#business-data-table').DataTable({

            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.business') }}",
                dataSrc: 'data',

            },
            columns: [{
                    className: 'details-control',
                    orderable: false,
                    data: null,
                    defaultContent: ''
                },

                //     {
                //         data: "full_name",
                //     "render": function(data, type, row) {
                //         data = '<a href="/client/detail/' + row.id + '?name=' + row.full_name + '">' + data + '</a>';
                //         return data;
                //     }
                // },
                // {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {
                    data: 'full_name',
                    title: 'full_name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'profile_pic',
                    name: 'profile_pic'
                },
                {
                    data: 'bio',
                    name: 'bio'
                },
                {
                    data: 'phone_no',
                    name: 'phone_no'
                },
                {
                    data: 'is_active',
                    name: 'is_active',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'created_at',
                    name: 'created_at'
                },

            ]
        });






        // Add event listener for opening and closing first level childdetails
        $('#business-data-table tbody').on('click', 'td.details-control', function() {

            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var rowData = row.data();

            // console.log(rowData);
            // return false;

            if (row.child.isShown()) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
                // Destroy the Child Datatable
                $('#cl' + rowData.id).DataTable().destroy();
            } else {
                // Open this row
                row.child(format(rowData)).show();
                var id = rowData.id;

                childTable = $('#cl' + id).DataTable({

                    dom: "t",
                    ajax: {
                        url: "{{ route('admin.Userbusiness') }}",
                        dataSrc: 'data',
                        data: {
                            id: id // Single parameter being sent
                        },
                    },
                    columns: [{
                            className: 'details-control2',
                            orderable: false,
                            data: [rowData],
                            defaultContent: ''
                        },

                        {
                            data: 'DT_RowIndex',
                            title: '#'
                        },

                        {
                            data: 'business_name',
                            title: 'business_name'
                        },
                        {
                            data: 'position',
                            title: 'position'
                        },
                        {
                            data: 'about',
                            title: 'about'
                        },
                        {
                            data: 'phone_no',
                            title: 'phone_no'
                        },
                        {
                            data: 'profile_pic',
                            title: 'profile_pic'
                        },
                        {
                            data: 'view_campaign',
                            title: 'view_campaign'
                        },
                        {
                            data: 'created_at',
                            title: 'created_at'
                        },





                    ],
                    // columnDefs: [{
                    //     targets: [2, 3, 4, 5, 6, 7, 8, 9],
                    //     className: "fee-col"
                    // }, {
                    //     targets: [1],
                    //     className: "label-col2"
                    // }],
                    scrollY: '150px',
                    select: true,
                });

                tr.addClass('shown');
            }
        });
    });












    function chageUserStatus(ele) {

        let changeStatus = $(ele).attr("xyz");
        var uid = $(ele).attr("data-src");
        var usd = $(ele).attr("data-div");
        if (changeStatus == 1) {
            var title = "Are you sure to Activate the user?";
            var confirm_m = "Active";
        } else {
            var title = "Are you sure to Inactivate the user?";
            var confirm_m = "Inactive";
        } // publish 
        Swal.fire({
            title: title,
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirm_m
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({

                    type: 'POST',

                    url: "{{ route('admin.activeInactive') }}",

                    data: {

                        'uid': uid,
                        "_token": "{{ csrf_token() }}",
                        'status': changeStatus
                    },

                    dataType: "json",

                    success: function(data) {
                        // console.log(data);

                        if (data.status == 200) {

                            console.log(data.html);

                            // toastr.success(data.message);
                            $('#' + usd).html(data.html);


                        } else {
                            toastr.error(data.message);
                            //     setTimeout(() => {
                            //     window.location.reload();
                            // }, 2000);
                        }
                    },
                    error: function(data) {
                        console.log(data);
                    }
                }); // /ajax
            }
        })
    }




    // business campaign

    function businessCamp(id) {
        
        $.ajax({

            type:   "POST",
            url:    "{{ route('admin.business.campaign') }}",
            data:{
                'id':id,
                '_token':"{{ csrf_token() }}"
            },

            dataType:"json",
            success:function(Response){

                if(Response.status==200){

                    $('#userBusinessUser').html(Response.html);
                    $('#userBusinessModal').modal('show');

                }

            },error:function(error){
                console.log(error);
            }
        });
    }
</script>

@endsection