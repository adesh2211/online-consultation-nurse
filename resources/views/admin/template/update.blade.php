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
                          <li class="breadcrumb-item active">Edit Template</li>
                      </ol>
                  </div>
              </div>
          </div>
      </div>  
    <div class="card card-primary">
        <div class="card-header">
          <h3 class="card-title">
          <?php
					$template_name = $template->template_name;
					$template_name = str_replace("_"," ",$template_name);
		?>
				 Template
          <small class="pull-right">
          
            <div class="dropdown">
					<button type="button" class="btn btn-outline-primary dropdown-toggle" id="page-header-options-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<i class="fa fa-clone"></i> Variables
					</button>
					<div class="dropdown-menu min-width-600" aria-labelledby="page-header-options-dropdown" id="variables_container">
                 
                        <button class="btn btn-outline-danger" type="button" data-col-name="booking_date" >Booking Date </button>
                        <button class="btn btn-outline-danger" type="button" data-col-name="doctor_name" >Doctor Name </button>
                        <button class="btn btn-outline-danger" type="button" data-col-name="doctor_email" >Doctor Email </button>
                        <button class="btn btn-outline-danger" type="button" data-col-name="user_name" >Patient Name </button>
                        <button class="btn btn-outline-danger" type="button" data-col-name="type" >Service </button>
					</div>
                </div>
				</small> 
                </h3>
        </div>
        <!-- /.card-header -->
        <!-- form start -->
        <form role="form" action="{{ url('admin/templates/update').'/'.$template->id}}" method="post">
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
                <select class="form-control"  name="type" >
                  <option value="sms" <?php echo (old('type') ?? $template->type =='sms')?"selected":'' ?> >SMS</option>
                  <option value="email" <?php echo (old('type') ?? $template->type =='email')?"selected":'' ?> >Email</option>
                </select>
                @if ($errors->has('type'))
                        <span class="text-danger">{{ $errors->first('type') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="title">Template Name</label>
                <input class="form-control" name="template_name" value="{{$template->template_name}}" >
                 
                @if ($errors->has('template_name'))
                       <span class="text-danger">{{ $errors->first('template_name') }}</span>
                @endif
              </div>
              <div class="form-group">
                <label for="page_body">Message</label>
                <!-- <textarea class="message_div"  name="message" placeholder="Place some text here"  style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{ $template->message }}</textarea> -->
                <textarea class="message_div" class="page_body" id="summernote-basic" name="message" value="{{ $template->message }}"  placeholder="Place some text here" style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;">{{{  $template->message  }}}</textarea>
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
    <script>
        $("#variables_container button").click(function(){
          
          var _col_name = $(this).attr("data-col-name");
             // alert(_col_name);
          $(".note-editable").append('%'+_col_name);

          // insertAtCaret('.note-editable', _col_name);
	});
    </script>
@endsection