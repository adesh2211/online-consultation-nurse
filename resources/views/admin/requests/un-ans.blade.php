@extends('layouts.vertical', ['title' => 'Appointments' ])
@section('css')
<link href="{{asset('assets/libs/datatables/datatables.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
<?php 
$admin = Auth::user()->hasRole('admin');

if(isset($_COOKIE['royo_timZone'])){ $timeZone = $_COOKIE['royo_timZone'];}else{ $timeZone = 'Asia/Calcutta';} ?>
<div class="container-fluid">
		<div class="row">
		    <div class="col-12">
		      <div class="card">
		        <div class="card-header">
		          <h3 class="card-title">Appointments {{ $chats->count() }}</h3>
		        </div>
		        <!-- /.card-header -->
		        <div class="card-body">
		          	<table id="scroll-horizontal-datatable" class="table w-100 nowrap">
		            <thead>
		            <tr >
		            	<th>#</th>
		            	<th>Booking Date</th>
		            	<th>Time</th>
		            	<th>Patient</th>
		            	<th>{{ __('text.Vendor') }} Name</th>
		            	<th>Status</th>
		            </tr>
		            </thead>
		            <tbody>
		             @foreach($chats as $index => $chat)
			            <tr>
			              <td>{{ $index+1 }}</td>
			              <td>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $chat->booking_date)->tz($timeZone)->format('d M Y') }}</td>
			              <td>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $chat->booking_date)->tz($timeZone)->format('h:i A') }}</td>
			              <td>{{ $chat->cus_info->name }}</td>
			              @php $d_detail = $chat->getCustomDoctor($chat->id); @endphp
				            <td>{{ isset($d_detail->first_name)?$d_detail->first_name:$chat->sr_info->name  }}</td>
			              <td> <button class="btn btn-sm btn-success">{{ ($chat->requesthistory)?$chat->requesthistory->status:'NA' }}</button> </td>
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
<script src="{{asset('assets/libs/datatables/datatables.min.js')}}"></script>
<!-- <script src="{{asset('assets/js/pages/datatables.init.js')}}"></script> -->
<script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
<script type="text/javascript">
	var dataTable = $('#scroll-horizontal-datatable').DataTable({
            "scrollX": true,
            "language": {
                "paginate": {
                    "previous": "<i class='mdi mdi-chevron-left'>",
                    "next": "<i class='mdi mdi-chevron-right'>"
                }
            },
            "drawCallback": function () {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            },
        });
	$(".AssignPhysio").click(function(e){
          e.preventDefault();
          var request_id = $(this).attr('data-request_id');
          $("#request_id").val(request_id);
          $('#AssignPhysio').modal('show');
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $('#scroll-horizontal-datatable').on('click', '.AcceptRequest', function(e){
    	_this = $(this);
        e.preventDefault();
        _this.html('Accepting...');
        $(this).attr('disabled',true);
        var request_id = $(this).attr('data-request_id');
        $.ajax({
           type:'POST',
           url:base_url+'/admin/appointment/status',
           data:{'request_id':request_id,'status':'accept'},
           success:function(data){
              Swal.fire(
                'Accepted!',
                'Appointment Accepted',
                'success'
              ).then((result)=>{
              		_this.html('Accepted');
              		window.location.reload();
              });
           }
        });
    });
    $('#scroll-horizontal-datatable').on('click', '.StartRequest', function(e){
        e.preventDefault();
        _this = $(this);
        _this.html('Please Wait...');
        $(this).attr('disabled',true);
        var request_id = $(this).attr('data-request_id');
        $.ajax({
           type:'POST',
           url:base_url+'/admin/appointment/status',
           data:{'request_id':request_id,'status':'in-progress'},
           success:function(data){
              Swal.fire(
                'StartRequest!',
                'Appointment Started',
                'success'
              ).then((result)=>{
              		_this.html('Started');
              		window.location.reload();
              });
           }
        });
    });
    $('#scroll-horizontal-datatable').on('click', '.CancelRequest', function(e){
        e.preventDefault();
        _this = $(this);
        _this.html('Canceled...');
        $(this).attr('disabled',true);
        var request_id = $(this).attr('data-request_id');
        $.ajax({
           type:'POST',
           url:base_url+'/admin/appointment/status',
           data:{'request_id':request_id,'status':'canceled'},
           success:function(data){
              Swal.fire(
                'Canceled!',
                'Appointment Canceled',
                'success'
              ).then((result)=>{
              		_this.html('Canceled');
              		window.location.reload();
              });
           }
        });
    });
    $('#scroll-horizontal-datatable').on('click', '.CompleteRequest', function(e){
        e.preventDefault();
        _this = $(this);
        _this.html('completing...');
        $(this).attr('disabled',true);
        var request_id = $(this).attr('data-request_id');
        $.ajax({
           type:'POST',
           url:base_url+'/admin/appointment/status',
           data:{'request_id':request_id,'status':'completed'},
           success:function(data){
              Swal.fire(
                'Completed!',
                'Appointment Completed',
                'success'
              ).then((result)=>{
              		_this.html('Completed');
              		window.location.reload();

              });
           }
        });
    });
</script>
@endsection