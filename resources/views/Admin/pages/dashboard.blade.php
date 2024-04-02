@extends('Admin.layouts.master')
@section('title',"Admin Login")
@section('main_content')
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<section class="section">

    <div class="row ">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
            <div class="card">
                <div class="card-statistic-4">
                    <div class="align-items-center justify-content-between">
                        <div class="row ">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                <div class="card-content">
                                    <h5 class="font-15">Influencers</h5>
                                    <h2 class="mb-3 font-18">{{ $influencers}}</h2>
                                    <!-- <p class="mb-0"><span class="col-green">10%</span> Increase</p> -->
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                <div class="banner-img">
                                    <img src="assets/img/banner/1.png" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
            <div class="card">
                <div class="card-statistic-4">
                    <div class="align-items-center justify-content-between">
                        <div class="row ">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                <div class="card-content">
                                    <h5 class="font-15"> Business</h5>
                                    <h2 class="mb-3 font-18">{{ $business}}</h2>
                                    <!-- <p class="mb-0"><span class="col-orange">09%</span> Decrease</p> -->
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                <div class="banner-img">
                                    <img src="assets/img/banner/2.png" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
            <div class="card">
                <div class="card-statistic-4">
                    <div class="align-items-center justify-content-between">
                        <div class="row ">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                <div class="card-content">
                                    <h5 class="font-15">Campagians</h5>
                                    <h2 class="mb-3 font-18">{{ $campaigns}}</h2>
                                    <!-- <p class="mb-0"><span class="col-green">18%</span> Increase -->
                                    </p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                <div class="banner-img">
                                    <img src="assets/img/banner/3.png" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
            <div class="card">
                <div class="card-statistic-4">
                    <div class="align-items-center justify-content-between">
                        <div class="row ">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                <div class="card-content">
                                    <h5 class="font-15">Revenue</h5>
                                    <h2 class="mb-3 font-18">$0</h2>
                                    <!-- <p class="mb-0"><span class="col-green">42%</span> Increase</p> -->
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                <div class="banner-img">
                                    <img src="assets/img/banner/4.png" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

















<script>
    $(document).ready(function() {
        // Automatically hide flash messages after 5 seconds (adjust as needed)
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>

@endsection