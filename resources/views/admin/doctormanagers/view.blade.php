@extends('layouts.vertical', ['title' => 'View '.__('Doctor Manager')])

@section('css')
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  <link href="{{asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    <div class="row">
            <div class="col-md-4">

               <div class="card-header p-2">
                  <ul class="nav nav-pills">
                    <li class="nav-item"></li>
                  </ul>
                </div><!-- /.card-header -->

              <!-- Profile Image -->
              <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                  <div class="text-center">
                          <img class="profile-user-img img-fluid img-circle" src="{{ Storage::disk('spaces')->url('thumbs/'.$manager->profile_image) }}" alt="User Image">
              
                  </div>

                  <h3 class="profile-username text-center">{{ ($manager->name)?$manager->name:'unknown' }}</h3>
                  <?php $requests = $manager->getReqAnaliticsByCustomer($manager->id); ?>

                  <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                      <b>Email:</b> {{ $manager->email }}
                    </li>
                    <li class="list-group-item">
                      <b>Phone:</b> {{ $manager->country_code }}-{{ $manager->phone }}
                    </li>
                    <li class="list-group-item">
                      <b>Doctors:</b> {{ $manager->assign_user }}
                    </li>
                   
                  </ul>
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->

              <!-- About Me Box -->
              
              <!-- /.card -->
            </div>
            <!-- /.col -->
            <div class="col-md-8">
              
              <!-- /.nav-tabs-custom -->
            </div>
            <!-- /.col -->
    </div>
@endsection
@section('script')
<script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>