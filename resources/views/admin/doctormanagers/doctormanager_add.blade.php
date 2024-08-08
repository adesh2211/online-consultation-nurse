@extends('layouts.vertical', ['title' => 'Add '.__('Doctor Manager')])

@section('css')

@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title">Add {{ __('Doctor Manager') }}</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form role="form" action="{{ url('admin/doctormanager/create') }}" method="post">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="_method" value="POST">
        <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
          <div class="form-group">
            <label for="exampleInputEmail1">Email address</label>
            <input type="email" class="form-control" required="" name="email" value="{{ old('email') }}" id="exampleInputEmail1" placeholder="Enter email">
            @if ($errors->has('email'))
                    <span class="text-danger">{{ $errors->first('email') }}</span>
            @endif
          </div>
          <div class="form-group">
            <label for="name"> Name</label>
            <input type="text" name="name" required="" class="form-control" value="{{ old('name') }}" id="name" placeholder="name">
            @if ($errors->has('name'))
                    <span class="text-danger">{{ $errors->first('name') }}</span>
            @endif
          </div>
          <div class="form-group">
            <label for="name"> Phone</label>
            <input type="text" name="phone" required="" class="form-control" value="{{ old('phone') }}" id="phone" placeholder="phone">
            @if ($errors->has('phone'))
                    <span class="text-danger">{{ $errors->first('phone') }}</span>
            @endif
          </div>
          <div class="form-group">
            <label for="page_body">Doctors</label>
            <select multiple class="form-control" name="doctors[]">
                <option value="">--Select Doctor--</option>
                @foreach($consultants as $cat_key=>$consultant)
                <option   value="{{ $consultant->id }}">{{ $consultant->name }}</option>
                @endforeach
              </select>
              @if ($errors->has('doctor'))
                <span class="text-danger">{{ $errors->first('doctor') }}</span>
              @endif
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
    </script>
@endsection