@extends('layouts.vertical', ['title' => 'Advertisement'])
@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
<div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title">New Advertisement</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form role="form" action="{{ url('admin/advertisement')}}" method="post" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="_method" value="POST">
        <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
          <div class="form-group">
            <div class="row">
                  <div class="col-sm-4">
                    <label>Date Range:</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text">
                          <i class="far fa-calendar-alt"></i>
                        </span>
                      </div>
                      <input type="text" name="date_range" value="{{ old('date_range') }}" class="form-control float-right" id="range-datepicker">
                    </div>                     
                    @if ($errors->has('date_range'))
                              <span class="text-danger">{{ $errors->first('date_range') }}</span>
                      @endif
                </div>
            </div>    
          </div>
          <div class="form-group">
             <div class="row">
                  <div class="col-sm-4">
                    <label for="exampleInputFile">Image</label>
                    <div class="input-group">
                      <div >
                        <input multiple type="file" value="{{old('image') }}" name="image[]" id="ct-img-file">
                        <img src="" id="profile-img-tag" width="200px" />
                      </div>
                    </div>
                     @if ($errors->has('image'))
                                    <span class="text-danger">{{ $errors->first('image') }}</span>
                      @endif
                  </div>
                  <div class="col-sm-4">
                     <label for="video">Video</label>
                        <div class="input-group">
                          <div >
                            <input multiple type="file" value="{{old('video') }}" name="video[]" id="image_icon">
                            <img src="" id="profile-img-tag-icon" width="200px" />
                          </div>
                        </div>
                         @if ($errors->has('video'))
                                        <span class="text-danger">{{ $errors->first('video') }}</span>
                                @endif
                  </div>
                  @if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live")
                  <div class="col-sm-4">
                      <label >User</label>
                      <select class="form-control" name="user">
                          <option value="">--Select User--</option>
                          @foreach($users as $cat_key=>$user)
                          <option <?php echo (old('user')==$user->id)?"selected":'' ?>  value="{{ $user->id }}">{{ $user->name }}</option>
                          @endforeach
                        </select>
                        @if ($errors->has('user'))
                          <span class="text-danger">{{ $errors->first('user') }}</span>
                        @endif
                </div>
                @endif
              </div>
          </div>
          <div class="form-group">
            <div class="row">
                  <div class="col-sm-4">
                      <label >Category</label>
                      <select class="form-control" name="category">
                          <option value="">--Select Category--</option>
                          @foreach($categories as $cat_key=>$parentCategory)
                          <option <?php echo (old('category')==$parentCategory->id)?"selected":'' ?>  value="{{ $parentCategory->id }}">{{ $parentCategory->name }}</option>
                          @endforeach
                        </select>
                        @if ($errors->has('category'))
                          <span class="text-danger">{{ $errors->first('category') }}</span>
                        @endif
                </div>
                 <div class="col-sm-4">
                      <label >Service Provider</label>
                      <select class="form-control" name="service_provider">
                          <option value="">--Select SP--</option>
                          @foreach($service_providers as $cat_key=>$service_pro)
                          <option <?php echo (old('service_provider')==$service_pro->id)?"selected":'' ?>  value="{{ $service_pro->id }}">{{ $service_pro->name }}</option>
                          @endforeach
                        </select>
                        @if ($errors->has('service_provider'))
                          <span class="text-danger">{{ $errors->first('service_provider') }}</span>
                        @endif
                </div>

                <div class="col-sm-4">
                      <label >Advertisement Type</label>
                      <select class="form-control" name="advertisement_type">
                          <option <?php echo (old('advertisement_type')=='category')?"selected":'' ?>  value="category">Category</option>
                          <option <?php echo (old('advertisement_type')=='service_provider')?"selected":'' ?>  value="service_provider">Service Provider</option>
                          @if(config('client_connected') && Config::get("client_data")->domain_name == "care_connect_live")
                          <option <?php echo (old('advertisement_type')=='user')?"selected":'' ?>  value="user">User</option>
                          @endif
                          
                        </select>
                        @if ($errors->has('advertisement_type'))
                          <span class="text-danger">{{ $errors->first('advertisement_type') }}</span>
                        @endif
                </div>
               
            </div>
           </div>
         
           <div class="form-group">
            <div class="row">
                 
                <input type="hidden" name="position" class="form-control" value="0" id="position" placeholder="Position">
                  <!-- <div class="col-sm-4">
                      <label for="position">Position</label>

                      @if ($errors->has('position'))
                              <span class="text-danger">{{ $errors->first('position') }}</span>
                      @endif
                </div> -->
              
           </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
  </div>
@endsection
@section('script')
    <!-- Plugins js-->
    <script src="{{asset('assets/libs/flatpickr/flatpickr.min.js')}}"></script>
    <script src="{{asset('assets/libs/selectize/selectize.min.js')}}"></script>
    <!-- Page js-->
    <!-- <script src="{{asset('assets/js/pages/form-pickers.init.js')}}"></script> -->
    <link href="{{asset('assets/libs/selectize/selectize.min.css')}}" rel="stylesheet" type="text/css" />
   
    <script type="text/javascript">

    $('select').selectize({
        create: true,
        sortField: 'text'
    });

      !function ($) {
          "use strict";

          var FormPickers = function () { };

          FormPickers.prototype.init = function () {

              $('#range-datepicker').flatpickr({
                  mode: "range"
              });
              
          },
              $.FormPickers = new FormPickers, $.FormPickers.Constructor = FormPickers

      }(window.jQuery),

          //initializing 
          function ($) {
              "use strict";
              $.FormPickers.init()
          }(window.jQuery);

    </script>
@endsection