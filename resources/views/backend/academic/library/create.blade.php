@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Upload library resource</h3>
                        </div>
                        <div class="box-body">
                            <form method="post" action="{{ route('library.store') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                    @error('title')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" name="subject" class="form-control" value="{{ old('subject') }}">
                                </div>
                                <div class="form-group">
                                    <label>Visibility <span class="text-danger">*</span></label>
                                    <select name="visibility" id="visibility" class="form-control" required>
                                        <option value="general" {{ old('visibility') === 'class' ? '' : 'selected' }}>General (all students)</option>
                                        <option value="class" {{ old('visibility') === 'class' ? 'selected' : '' }}>Specific class</option>
                                    </select>
                                </div>
                                <div class="form-group" id="class-wrap">
                                    <label>Class</label>
                                    <select name="class_id" class="form-control">
                                        <option value="">Select class</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ (string) old('class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('class_id')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                                <div class="form-group">
                                    <label>File <span class="text-danger">*</span></label>
                                    <input type="file" name="file" class="form-control" required>
                                    <small class="text-muted">pdf, doc, docx, ppt, pptx, xls, xlsx, jpg, png, mp3 (max 20MB). mp4 max 100MB.</small>
                                    @error('file')<small class="text-danger d-block">{{ $message }}</small>@enderror
                                </div>
                                <button type="submit" class="btn btn-info">Upload</button>
                                <a href="{{ route('library.index') }}" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var vis = document.getElementById('visibility');
    var wrap = document.getElementById('class-wrap');
    function toggle() {
        wrap.style.display = vis.value === 'class' ? 'block' : 'none';
    }
    vis.addEventListener('change', toggle);
    toggle();
});
</script>
@endsection
