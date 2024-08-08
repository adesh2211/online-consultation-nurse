@extends('layouts.vertical', ['title' => 'Categories'])

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
                            <li class="breadcrumb-item active">Categories</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Categories</h4>
                </div>
            </div>
        </div> 

    <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted font-13 mb-4">
                            Category Listing
                        </p>
                        <a href="{{ url('admin/categories/create')}}" class="btn btn-sm btn-info float-right">Add Main category</a>
                        <table id="scroll-horizontal-datatable" class="table w-100 nowrap">
                                <thead>
                                    <tr >
                                        <th>Sr No.</th>
                                        <th>Name</th>
                                        <!-- <th>Color Code</th> -->
                                        <th>Child Cat.</th>
                                        <th>Enable On Front-End</th>
                                        <th>Enable Service Type</th>
                                        @if(Config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live")
                                        <th>Percentage</th>
                                        <th>Enable Percentage</th>
                                        @endif
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                 @foreach($parentCategories as $index => $category)
                                    <tr class="{{ $category->enable=='1'?'': 'table-danger'}}">
                                      <td>{{ $index+1 }}</td>
                                      <td>{{ $category->name }}</td>
                                      <!-- <td>{{ $category->color_code }}</td> -->
                                      <td>{{ $category->subcategory->count() }}</td>
                                      <td>{{ ($category->enable=='1'?'Yes':'No') }}</td>
                                      <td>{{ ($category->enable_service_type=='1'?'Yes':'No') }}</td>
                                      @if(Config('client_connected') && Config::get("client_data")->domain_name=="care_connect_live")
                                      <td>{{ $category->percentage }}</td>
                                      <td>{{ ($category->enable_percentage=='1'?'Yes':'No') }}</td>
                                      @endif
                                      <td><a href="{{ url('admin/categories') .'/'.$category->id.'/edit'}}" class="btn btn-sm btn-info float-left">Edit</a>

                                        <li  title="Disable" style="display:inline-block;">
                                            <button data-user_id="{{ $category->id }}" class="btn btn-sm btn-danger {{ $category->enable=='1'?'disableCategory': 'enableCategory'}} "><i class="{{ $category->enable=='1'?'fe-trash': 'fa fa-toggle-on'}}"></i>
                                            </button>
                                </li>
                                      </td>
                                    </tr>
                                 @endforeach   
                                </tbody>
                        </table>

                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->
        </div>
</div>
@endsection

@section('script')
    <!-- Plugins js-->
    <script src="{{asset('assets/libs/datatables/datatables.min.js')}}"></script>
    <script src="{{asset('assets/libs/pdfmake/pdfmake.min.js')}}"></script>

    <!-- Page js-->
    <script src="{{asset('assets/js/pages/datatables.init.js')}}"></script>
    <script src="{{asset('assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        $('#scroll-horizontal-datatable').on('click', '.disableCategory', function(e){
              e.preventDefault();
              var _this = $(this);
              var category_id = $(this).attr('data-user_id');
              Swal.fire({
                title: 'Do You Want To Disable Category ?',
                text: "You won't be able to see on Front-End",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Disable it!'
              }).then((result) => {
                if (result.value) {
                    $.ajax({
                       type:'POST',
                       url:base_url+'/admin/categories/disable',
                       data:{"category_id":category_id,'disable':true},
                       success:function(data){
                          Swal.fire(
                            'Disabled!',
                            'This Category has been Disabled',
                            'success'
                          ).then((result)=>{
                            window.location.reload();
                          });
                       }
                    });
                  }
              });
        });

        $('#scroll-horizontal-datatable').on('click', '.enableCategory', function(e){
              e.preventDefault();
              var _this = $(this);
              var category_id = $(this).attr('data-user_id');
              Swal.fire({
                title: 'Do You Want To Enable this Category ?',
                text: "You will be able to see on Front-End",
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Enable it!'
              }).then((result) => {
                if (result.value) {
                    $.ajax({
                       type:'POST',
                       url:base_url+'/admin/categories/disable',
                       data:{"category_id":category_id,'disable':false},
                       success:function(data){
                          Swal.fire(
                            'Enabled!',
                            'This Category has been Enabled',
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