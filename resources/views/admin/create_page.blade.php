@extends('layouts.vertical', ['title' => 'Page'])

@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/summernote/summernote.min.css')}}" rel="stylesheet" type="text/css" />

@endsection

@section('content')
 <!-- Start Content-->
  <div class="container-fluid">
      
      <!-- start page title -->
      <div class="row">
          <div class="col-12">
              <div class="page-title-box">
                  <div class="page-title-right">
                      <ol class="breadcrumb m-0">
                          <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                          <li class="breadcrumb-item active">Create Page</li>
                      </ol>
                  </div>
              </div>
          </div>
      </div>  
    <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Create Page</h3>
        </div>
        <!-- /.card-header -->
        <!-- form start -->
        <form role="form" action="{{ url('admin/pages')}}" method="post">
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
          <input type="hidden" name="_method" value="post">
            <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
              <div class="form-group">
                <div class="row">
                <div class="col-sm-6">
                  <!-- select -->
                  <div class="form-group">
                      <label for="slug">Slug Type* (choose other then slug will create from title)</label>
                      <select class="form-control" id="slug" name="slug" required="">
                          <option value="privacy-policy">Privacy Policy</option>
                          <option value="term-conditions">Term And Conditions</option>
                          <option value="help-and-support">Help and Support</option>
                          <option value="about">About</option>
                          <option value="sales-agreement">Sales Agreement</option>
                          <option value="other">Other</option>
                        </select>
                        @if ($errors->has('slug'))
                          <span class="text-danger">{{ $errors->first('slug') }}</span>
                        @endif
                     </div>
                </div>
                </div>
              </div>
              <div class="form-group">
                <label for="title">Title</label>
                <input type="text" class="form-control" name="title" value="{{old('title')}}" id="title" placeholder="Enter Title">
                @if ($errors->has('title'))
                        <span class="text-danger">{{ $errors->first('title') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="page_body">Body</label>
                <textarea class="page_body" id="summernote-basic" name="body" value="{{old('body')}}"  placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{{ old('body') }}}</textarea>
                @if ($errors->has('body'))
                        <span class="text-danger">{{ $errors->first('body') }}</span>
                @endif
              </div>
              <div class="row">
                <div class="col-sm-4">
                  <!-- select -->
                  <div class="form-group">
                      <label for="page_body">Status</label>
                      <select class="form-control" name="status">
                          <option value="">--Select Status--</option>
                          <option value="publish">Publish</option>
                          <option value="draft">Draft</option>
                        </select>
                        @if ($errors->has('status'))
                          <span class="text-danger">{{ $errors->first('status') }}</span>
                        @endif
                     </div>
                </div>
                <div class="col-sm-6">
                      <label>Show On App Type</label>
                      <select  class="form-control" name="app_type">
                        <option value="both" <?php echo (old('app_type')=='both')?"selected":'' ?>>Both</option>
                        <option value="customer" <?php echo (old('app_type')=='customer')?"selected":'' ?>>User</option>
                        <option value="service_provider" <?php echo (old('app_type')=='service_provider')?"selected":'' ?>>Expert</option>
                      </select>
                      @if ($errors->has('app_type'))
                        <span class="text-danger">{{ $errors->first('app_type') }}</span>
                      @endif
              </div>
              
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
      </div>
    </div>
@endsection

@section('script')
    <!-- Plugins js-->
    <script src="{{asset('assets/libs/summernote/summernote.min.js')}}"></script>

    <!-- Page js-->
    <script src="{{asset('assets/js/pages/form-summernote.init.js')}}"></script>
    <script type="text/javascript">
      // $(document).on("change","#slug", function(e){
      //     if($("#input_type").val()=='textbox'){
      //         $("#data_type").css('display','block');
      //         $("#option_value").css('display','none');
      //     }else{
      //         $("#option_value").css('display','block');
      //         $("#data_type").css('display','none');
      //     }
      // });
    </script>
@endsection