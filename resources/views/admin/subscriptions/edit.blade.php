@extends('layouts.vertical', ['title' => 'Subscription'])

@section('content')
<div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title">Edit Subscription</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form role="form" action="{{ url('admin/subscriptions').'/'.$packagePlan->id}}" method="post" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="_method" value="PUT">
        <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
          <div class="form-group">
             <div class="row">
                  <div class="col-sm-6">
                    <label for="title">Title</label>
                    <div class="input-group">
                        <input class="form-control" type="text" value="{{old('title')??$packagePlan->title }}" placeholder="title" name="title" id="title">
                    </div>
                     @if ($errors->has('title'))
                                    <span class="text-danger">{{ $errors->first('title') }}</span>
                      @endif
                  </div>
                  <div class="col-sm-6">
                     <label for="description">Description</label>
                        <div class="input-group">
                            <textarea rows="5" class="form-control" placeholder="description" name="description" id="description">{{old('description')??$packagePlan->description }}</textarea>
                        </div>
                       @if ($errors->has('description'))
                                      <span class="text-danger">{{ $errors->first('description') }}</span>
                              @endif
                  </div>
              </div>
          </div>
          <div class="form-group">
             <div class="row">
                  <div class="col-sm-6">
                      <label for="total_session">Total Sessions/Monthly</label>
                      <input type="number" name="total_session" class="form-control" value="{{ old('total_session')??$packagePlan->total_session }}" id="total_session" placeholder="Total Sessions/Requests">
                      @if ($errors->has('total_session'))
                              <span class="text-danger">{{ $errors->first('total_session') }}</span>
                      @endif
                </div>
                  
                  <div class="col-sm-6">
                     <label for="price">Price</label>
                        <div class="input-group">
                            <input class="form-control" type="number" placeholder="Price" value="{{old('price')??$packagePlan->price }}" name="price" id="price">
                        </div>
                         @if ($errors->has('price'))
                              <span class="text-danger">{{ $errors->first('price') }}</span>
                      @endif
                  </div>
              </div>
          </div>
          <div class="form-group">
            <div class="row">
                  <div class="col-sm-6">
                      <label >Plan Type</label>
                       <div class="input-group">
                            <input class="form-control" type="text" placeholder="type" value="{{$packagePlan->type }}" name="type" id="package_type" disabled="">
                        </div>
                        @if ($errors->has('type'))
                          <span class="text-danger">{{ $errors->first('type') }}</span>
                        @endif
                </div>
            </div>
           </div>
           <div class="form-group">
            <div class="row">
                  <div class="col-sm-6">
                    <label for="exampleInputFile">Image</label>
                    <div class="input-group">
                      <div >
                        <input type="file" value="{{old('image') }}" name="image" id="ct-img-file">
                        <img src="{{ Storage::disk('spaces')->url('thumbs/'.$packagePlan->image) }}" id="profile-img-tag" width="200px" />
                      </div>
                    </div>
                     @if ($errors->has('image'))
                                    <span class="text-danger">{{ $errors->first('image') }}</span>
                      @endif
                  </div>
           </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
  </div>
@endsection