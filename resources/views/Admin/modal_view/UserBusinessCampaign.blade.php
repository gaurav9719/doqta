<div class="modal-header">

    <h5 class="modal-title" id="exampleModalCenterTitle">Business Campaigns</h5>

    <button type="button" class="close" data-dismiss="modal" aria-label="Close">

        <span aria-hidden="true">&times;</span>

    </button>

</div>

<div class="modal-body">

    <div class="row">
        <div class="col-12">
            <div class="card">
              
                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-striped" id="business-campaigns" style="border: 1px solid black;">
                            <thead>
                                <tr>
                                    <th class="text-center">
                                        #
                                    </th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Post type</th>
                                    <th>Promotor Picked</th>
                                    <th>Post Date</th>
                                    <th>Total Amount</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php
                                $a = 1;
                            @endphp

                            @forelse($campagins as $campagin)
                                <tr>
                                    <td>{{ $a++ }}</td>
                                    <td>{{ $campagin['title'] }}</td>

                                    <td>{{ $campagin['category_name'] }}</td>
                                    <td>{{ $campagin['post_type_name'] }}</td>
                                    <td>{{ $campagin['promotor_picked'] }}</td>
                                    <td>{{ $campagin['post_date'] }}</td>

                                    <td>{{ $campagin['total_amount'] }}</td>
                                </tr>
                               
                            @empty
                                <tr>
                                    <td colspan="3">No data available</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>