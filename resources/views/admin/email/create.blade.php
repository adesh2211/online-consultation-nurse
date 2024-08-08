@extends('layouts.vertical', ['title' => 'Email'])

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
                          <li class="breadcrumb-item active">Send Email</li>
                      </ol>
                  </div>
              </div>
          </div>
      </div>  
    <div class="card card-primary">
        <div class="card-header">
          @if(session()->has('message'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
              {{ session()->get('message') }}
          </div>
        @endif
          <h3 class="card-title">Send Email</h3>
        </div>
        <!-- /.card-header -->
        <!-- form start -->
        <form role="form" action="{{ url('admin/send/email')}}" method="post">
          <input type="hidden" name="_token" value="{{ csrf_token() }}">
          <input type="hidden" name="_method" value="post">
            <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
              <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" class="form-control" name="subject" value="{{old('subject')}}" id="subject" placeholder="Enter Subject">
                @if ($errors->has('subject'))
                        <span class="text-danger">{{ $errors->first('subject') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="subject">To</label>
                <input type="email" class="form-control" name="to" value="{{old('to')}}" id="to" placeholder="Enter Email">
                @if ($errors->has('to'))
                        <span class="text-danger">{{ $errors->first('to') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="page_body">Body</label>
                <textarea class="page_body" id="summernote-basic" name="body" value="{{old('body')}}"  placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{{ old('body') }}}</textarea>
                @if ($errors->has('body'))
                        <span class="text-danger">{{ $errors->first('body') }}</span>
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