	@extends('layouts.vertical', ['title' => 'Advertisements'])

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
                            <li class="breadcrumb-item active">Advertisements</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div> 
		<div class="row">
		    <div class="col-12">
		      <div class="card">
		        <div class="card-header">
		          <h5 class="card-title">Advertisement Listing</h5>
		           <a href="{{ url('admin/advertisement/create')}}" class="btn btn-sm btn-info float-right">Add New Advertisement</a>
		        </div>
		        <!-- /.card-header -->
		        <div class="card-body">
		          	<table id="scroll-horizontal-datatable" class="table w-100 nowrap">
		            <thead>
		            <tr >
		            	<th>Sr No.</th>
		            	<th>Advertisement Type</th>
		            	<th>SP Name</th>
		            	<th>Category</th>
		            	<th>Class</th>
		            	<!-- <th>Position</th> -->
		            	<th>StartDate EndDate</th>
		            	<th>Image</th>
		            	<th>Video</th>
		            	<th>Advertisement Status</th>
		            	<th>Action</th>
		            </tr>
		            </thead>
		            <tbody>
		             @foreach($advertisements as $index => $advertisement)
			            <tr>
			              <td>{{ $index+1 }}</td>
			              <td>{{ $advertisement->banner_type }}</td>
			              <td>{{ ($advertisement->sp_data)?$advertisement->sp_data->name:'NA' }}</td>
			              <td>{{ ($advertisement->category)?$advertisement->category->name:'NA' }}</td>
			              <td>{{ ($advertisement->class)?$advertisement->class->name:'NA' }}</td>
			              <!-- <td>{{ $advertisement->position }}</td> -->
			              <td>{{ $advertisement->start_date }} <br>{{ $advertisement->end_date }}</td>
			              <td> @php
						  		$adv_image = null;
								if($advertisement->image != null){
									$adv_image = json_decode($advertisement->image);
								}
								$adv_video = null;
								if($advertisement->video != null){
									$adv_video = json_decode($advertisement->video);
								}
								  @endphp
								@if($adv_image != null)
								@foreach($adv_image as $image)
								<img height="50px" width="50px" src="{{ Storage::disk('spaces')->url('thumbs/'.$image) }}"> 
								@endforeach
								@endif
						  </td>
			              <td>
						  		@if($adv_video != null)
									@foreach($adv_video as $video)
										<a class="btn btn-primary" href="{{ Storage::disk('spaces')->url('video/'.$video) }}" target="blank">View Video</a>
									@endforeach
								@endif
						  </td>
			              <td>{{ $advertisement->enable?'Enable':'Disable' }}</td>
			              <td>
			              	<a href="{{ url('admin/advertisement') .'/'.$advertisement->id.'/edit'}}" class="btn btn-sm btn-info float-left">Edit</a>

			              	<a class="btn btn-danger btn-sm delete-advertisement" data-advertisement_id="{{ $advertisement->id }}" href="javascript:void(0)">
                              <i class="fas fa-trash">
                              </i>
                              Delete
                            </a>
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
    		$(".delete-advertisement").click(function(e){
			          e.preventDefault();
			          var advertisement_id = $(this).attr('data-advertisement_id');
			          Swal.fire({
			            title: 'Are you sure?',
			            text: "You won't be able to revert this!",
			            showCancelButton: true,
			            confirmButtonColor: '#3085d6',
			            cancelButtonColor: '#d33',
			            confirmButtonText: 'Yes, delete it!'
			          }).then((result) => {
			            if (result.value) {
			                $.ajax({
			                   type:'DELETE',
			                   url:base_url+'/admin/advertisement/'+advertisement_id,
			                   data:{id:advertisement_id},
			                   success:function(data){
			                      Swal.fire(
			                        'Deleted!',
			                        'Advertisement has been deleted.',
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