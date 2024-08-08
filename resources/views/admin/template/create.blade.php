@extends('layouts.vertical', ['title' => 'Template'])

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
                          <li class="breadcrumb-item active">Create Template</li>
                      </ol>
                  </div>
              </div>
          </div>
      </div>  
    <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">Create Template</h3>
        </div>
        <!-- /.card-header -->
        <!-- form start -->
        <form role="form" action="{{ url('admin/templates')}}" method="post">
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
          <input type="hidden" name="_method" value="post">
            <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
              <div class="form-group">
                <label for="title">Type</label>
                <select class="form-control" name="type" >
                  <option value="sms">SMS</option>
                  <option value="email">Email</option>
                </select>
                @if ($errors->has('type'))
                        <span class="text-danger">{{ $errors->first('type') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="title">Template Name</label>
                <input class="form-control" name="template_name" >
                 
                @if ($errors->has('template_name'))
                        <span class="text-danger">{{ $errors->first('template_name') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="page_body">Message</label>
                <textarea class="page_body"  name="message" value="{{old('message')}}"  placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{{ old('message') }}}</textarea>
                @if ($errors->has('message'))
                        <span class="text-danger">{{ $errors->first('message') }}</span>
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
@endsection