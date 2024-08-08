@extends('layouts.vertical', ['title' => 'Edit Emsat'])

@section('content')
<div class="card card-primary">
    <!-- /.card-header -->
    <!-- form start -->
    <form role="form" action="{{ url('admin/emsat').'/'.$emsat->id}}" method="post" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="_method" value="PUT">
        <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
          <div class="form-group row">
              <div class="col-sm-8">
                <label for="">Title</label><br>
                <input type="text" class="form-control"  name="title"  placeholder="Title" required="" value="{{ old('title')??$emsat->title }}">
                @if ($errors->has('title'))
                        <span class="text-danger">{{ $errors->first('title') }}</span>
                @endif
              </div>
          </div>
          <div class="form-group row">
              <div class="col-sm-6">
                <label for="question">Question</label>
                <input class="form-control"  name="question"  id="question" placeholder="total questions" value="{{old('question')??$emsat->question }}">
                @if ($errors->has('question'))
                        <span class="text-danger">{{ $errors->first('question') }}</span>
                @endif
              </div>
              <div class="col-sm-6">
                <label for="marks">Marks</label>
                <input class="form-control"  name="marks"  id="marks" placeholder="total marks" value="{{old('marks')??$emsat->marks }}">
                @if ($errors->has('marks'))
                        <span class="text-danger">{{ $errors->first('marks') }}</span>
                @endif
              </div>
          </div> 
          <div class="form-group row">
              <div class="col-sm-6">
                <label for="exampleInputFile">Image Web</label>
                <div class="input-group">
                  <div >
                    <input type="file" value="{{old('icon') }}" name="icon" id="ct-img-file">
                    <img src="{{ Storage::disk('spaces')->url('thumbs/'.$emsat->icon) }}" id="profile-img-tag" width="200px" />
                  </div>
                </div>
                 @if ($errors->has('image'))
                                <span class="text-danger">{{ $errors->first('image') }}</span>
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