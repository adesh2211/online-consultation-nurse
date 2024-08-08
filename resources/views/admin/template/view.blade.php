@extends('layouts.vertical', ['title' => 'Templates'])

@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/datatables/datatables.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />

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
                        <li class="breadcrumb-item active">Templates</li>
                    </ol>
                </div>
            </div>
        </div>
    </div> 
    <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">Templates</h5>
              
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                  <table id="scroll-horizontal-datatable" class="table w-100 nowrap">
                <thead>
                <tr >
                    <th>Sr No.</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                 @foreach($templates as $index => $template)
                    <tr>
                      <td>{{ $index+1 }}</td>
                      <td>{{ $template->type }}</td>
                      <td>{{ $template->message }}</td>
                      
                      <td>
                          <a href="{{ url('admin/templates') .'/'.$template->id.'/edit'}}" class="btn btn-sm btn-info float-left">Edit</a>
                        
                      </td>
                    </tr>
                 @endforeach   
                </tbody>
              </table>
            </div>
<!-- /.card-body -->
</div>
<!-- /.card -->
</div>
<!-- /.col -->
</div>
</div>
@endsection

@section('script')
<!-- Plugins js-->
<script src="{{asset('assets/libs/datatables/datatables.min.js')}}"></script>
<!-- <script src="{{asset('assets/libs/pdfmake/pdfmake.min.js')}}"></script> -->

<!-- Page js-->
<script src="{{asset('assets/js/pages/datatables.init.js')}}"></script>

<script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
<script type="text/javascript">
    $(function () {
         $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
       
    });
</script>
@endsection