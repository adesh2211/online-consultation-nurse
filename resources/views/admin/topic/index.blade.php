@extends('layouts.vertical', ['title' => __('Topics') ])
@section('css')
<link href="{{asset('assets/libs/datatables/datatables.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('assets/libs/sweetalert2/sweetalert2.min.css')}}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
<div class="container-fluid">
		<div class="row">
		    <div class="col-12">	
		      <div class="card">
		        <div class="card-header">
		          <h3 class="card-title">{{ __('Topics') }}</h3>
		        </div>
		        <!-- /.card-header -->
		        <div class="card-body">
		          	<table id="scroll-horizontal-datatable" class="table w-100 nowrap" >
		            <thead>
		            <tr >
		            	<th>Sr No.</th>
		            	<th>Actions</th>
	            		<th>Sp Name</th>
	            		<th>Title</th>
						<th>Author</th>
	            		<th>Price</th>
	            		<th>Status</th>
		            </tr>
		            </thead>
		            <tbody>
		             @foreach($topics as $index => $profile)
			            <tr class="delete_row">
			              <td>{{ $index+1 }}</td>
			              <td>
			              	<ul style="padding: initial;">
			              		@if($profile->status=='rejected' || $profile->status=='pending')
			              		<li data-toggle="tooltip" title="Approve" style="display:inline-block;">
			              			<a data-profile_id="{{ $profile->id }}" data-consultant_id="{{ $profile->created_by }}" class="btn btn-sm btn-info approve_profile"><i class="fa fa-check-circle" style="cursor: pointer;"></i></a>
			              		</li>
			              		@endif
			              		@if($profile->status!='activate' && $profile->status!='pending')
			              		<li data-toggle="tooltip" title="Activate" style="display:inline-block;">
			              			<a data-profile_id="{{ $profile->id }}" data-consultant_id="{{ $profile->created_by }}" class="btn btn-sm btn-info activate_profile"><i class="fas fa-toggle-on" style="cursor: pointer;"></i></a>
			              		</li>
			              		@endif
			              		@if($profile->status!='deactivate' && $profile->status!='pending')
			              		<li data-toggle="tooltip" title="De Activate" style="display:inline-block;">
			              			<a data-profile_id="{{ $profile->id }}" data-consultant_id="{{ $profile->created_by }}" class="btn btn-sm btn-danger  deactivate_profile"><i class="fa fa-power-off" style="cursor: pointer;"></i></a>
			              		</li>
			              		@endif
			              		@if($profile->status!=='rejected')
			              		<li data-toggle="tooltip" title="Reject" style="display:inline-block;"><button data-profile_id="{{ $profile->id }}" data-consultant_id="{{ $profile->created_by }}" class="btn btn-sm btn-danger reject_profile"><i class="fa fa-ban"></i></button>
			              		</li>
			              		@endif
				              	<li data-toggle="tooltip" title="Delete" style="display:inline-block;"><button data-profile_id="{{ $profile->id }}" data-consultant_id="{{ $profile->created_by }}" class="btn btn-sm btn-danger delete_profile"><i class="fe-delete"></i></button>
			              		</li>
			              	</ul>
			              </td>
			              <td>{{  ($profile->sp_data->name)?$profile->sp_data->name:$profile->sp_data->phone }}</td>
			              <td>{{ $profile->title }}</td>
						  <td>{{ $profile->author }}</td>
			              <td>{{ $profile->price }}</td>
			              <td>{{ $profile->status }}</td>
			            </tr>
			         @endforeach   
		        	</tbody>
		          </table>
				</div>
			</div>
		</div>
	</div>
	<!--modal-->
</div>
@endsection
@section('script')
<script src="{{asset('assets/libs/datatables/datatables.min.js')}}"></script>
<!-- <script src="{{asset('assets/js/pages/datatables.init.js')}}"></script> -->
<script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
<script type="text/javascript">
	$(document).ready(function() {
         var dataTable = $('#scroll-horizontal-datatable').DataTable({
         	"autoFill": false,
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
            initComplete: function() {
                $(this.api().table().container()).find('input[type=search]').parent().wrap('<form>').parent().attr('autocomplete', 'off');
            }
        });
    });



	$(function () {
		var topic = "{{ __('Topic') }}";
		$.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
	    
	    var file_data = null;
	    
		$('#scroll-horizontal-datatable').on('click', '.delete_profile', function(e){
	          e.preventDefault();
	          var _this = $(this);
	          var user_id = $(this).attr('data-consultant_id');
	          let profile_id = _this.attr('data-profile_id');
	          Swal.fire({
	            title: 'Do You Want To Delete This '+topic+' ?',
	            text: "You won't be able to revert this!",
	            showCancelButton: true,
	            confirmButtonColor: '#3085d6',
	            cancelButtonColor: '#d33',
	            confirmButtonText: 'Yes, delete it!'
	          }).then((result) => {
	            if (result.value) {
	                $.ajax({
	                   type:'POST',
	                   url:base_url+'/admin/consultants/delete-topic',
	                   data:{"user_id":user_id,'topic_id':profile_id},
	                   success:function(data){
	          			 _this.parents('tr').remove();
	                      Swal.fire(
	                        'Deleted!',
	                        topic+' has been deleted.',
	                        'success'
	                      ).then((result)=>{
	                        window.location.reload();
	                      });
	                   }
	                });
	              }
	          });
	    });

		$("#scroll-horizontal-datatable").on('click', '.approve_profile',function(e){
		          var __this = $(this);
		          let consultant_id = __this.attr('data-consultant_id');
		          let profile_id = __this.attr('data-profile_id');
		          Swal.fire({
		            title: 'Are you sure?',
		            text: "You want to Approve this Topic",
		            showCancelButton: true,
		            confirmButtonColor: '#3085d6',
		            cancelButtonColor: '#d33',
		            confirmButtonText: 'Yes, Approved!'
		          }).then((result) => {
		            if (result.value) {
		                $.ajax({
		                   type:'PUT',
		                   url:base_url+'/admin/consultants/'+consultant_id,
		                   data:{id:consultant_id,topic_verify_ajax:'true',topic_id:profile_id},
		                   success:function(data){
		                      Swal.fire(
		                        'Approved!',
		                        'Topic has been Approved.',
		                        'success'
		                      ).then((result)=>{
		                      	window.location.reload();
		                      });
		                   }
		                });
		              }
		      });
	    });

	    $("#scroll-horizontal-datatable").on('click', '.reject_profile',function(e){
		          var __this = $(this);
		          let consultant_id = __this.attr('data-consultant_id');
		          let profile_id = __this.attr('data-profile_id');
		          Swal.fire({
		            title: 'Are you sure?',
		            text: "You want to Reject this Topic",
		            showCancelButton: true,
		            confirmButtonColor: '#3085d6',
		            cancelButtonColor: '#d33',
		            confirmButtonText: 'Yes, Rejected!'
		          }).then((result) => {
		            if (result.value) {
		                $.ajax({
		                   type:'PUT',
		                   url:base_url+'/admin/consultants/'+consultant_id,
		                   data:{id:consultant_id,topic_reject_ajax:'true',topic_id:profile_id},
		                   success:function(data){
		                      Swal.fire(
		                        'Rejected!',
		                        'Topic has been Rejected.',
		                        'success'
		                      ).then((result)=>{
		                      	window.location.reload();
		                      });
		                   }
		                });
		              }
		      });
	    });

	    $("#scroll-horizontal-datatable").on('click', '.activate_profile',function(e){
		          var __this = $(this);
		          let consultant_id = __this.attr('data-consultant_id');
		          let profile_id = __this.attr('data-profile_id');
		          Swal.fire({
		            title: 'Are you sure?',
		            text: "You want to Activate this Topic",
		            showCancelButton: true,
		            confirmButtonColor: '#3085d6',
		            cancelButtonColor: '#d33',
		            confirmButtonText: 'Yes, Activate!'
		          }).then((result) => {
		            if (result.value) {
		                $.ajax({
		                   type:'PUT',
		                   url:base_url+'/admin/consultants/'+consultant_id,
		                   data:{id:consultant_id,topic_activate_ajax:'true',topic_id:profile_id},
		                   success:function(data){
		                      Swal.fire(
		                        'Activated!',
		                        'Topic has been Activated.',
		                        'success'
		                      ).then((result)=>{
		                      	window.location.reload();
		                      });
		                   }
		                });
		              }
		      });
	    });
	    $("#scroll-horizontal-datatable").on('click', '.deactivate_profile',function(e){
		          var __this = $(this);
		          let consultant_id = __this.attr('data-consultant_id');
		          let profile_id = __this.attr('data-profile_id');
		          Swal.fire({
		            title: 'Are you sure?',
		            text: "You want to Deactivate this Topic",
		            showCancelButton: true,
		            confirmButtonColor: '#3085d6',
		            cancelButtonColor: '#d33',
		            confirmButtonText: 'Yes, Deactivate!'
		          }).then((result) => {
		            if (result.value) {
		                $.ajax({
		                   type:'PUT',
		                   url:base_url+'/admin/consultants/'+consultant_id,
		                   data:{id:consultant_id,topic_deactivate_ajax:'true',topic_id:profile_id},
		                   success:function(data){
		                      Swal.fire(
		                        'Deactivated!',
		                        'Topic has been Deactivated.',
		                        'success'
		                      ).then((result)=>{
		                      	window.location.reload();
		                      });
		                   }
		                });
		              }
		      });
	    });
	});
</script>
@endsection
	