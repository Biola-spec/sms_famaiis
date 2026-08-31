@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border d-flex align-items-center justify-content-between py-15">
                            <div>
                                <h3 class="box-title font-weight-700 text-dark mb-0">
                                    <i class="fa fa-picture-o text-primary mr-10"></i>{{ __('ui.class_gallery') }} — {{ $class->name }}
                                </h3>
                                <span class="text-muted d-block mt-5 font-size-12">Complete photo gallery and student roster</span>
                            </div>
                            <a href="{{ route('class.gallery.index') }}" class="btn btn-sm btn-info-light">
                                <i class="fa fa-arrow-left mr-5"></i> Back to Classes
                            </a>
                        </div>
                        <div class="box-body">
                            @if(session('message'))
                                <div class="alert alert-{{ session('alert-type') == 'error' ? 'danger' : (session('alert-type') ?: 'info') }} alert-dismissible fade show" role="alert">
                                    {{ session('message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            <div class="row">
                                @php $validCount = 0; @endphp
                                @foreach($assignments as $assign)
                                    @php
                                        $student = $assign->student;
                                        if (!$student) continue;
                                        $validCount++;

                                        $studentName = trim($student->name);
                                        if (empty($studentName)) {
                                            $studentName = trim(($student->fname ?? '') . ' ' . ($student->surname ?? ''));
                                        }
                                        if (empty($studentName)) {
                                            $studentName = $student->email ?? ('Student #' . $student->id);
                                        }

                                        $regNo = !empty($student->id_no) ? $student->id_no : 'N/A';

                                        $photo = $photos->get($student->id);
                                        if ($photo && !empty($photo->photo_path) && file_exists(public_path($photo->photo_path))) {
                                            $src = url($photo->photo_path);
                                        } elseif (!empty($student->image) && file_exists(public_path('upload/student_images/' . $student->image))) {
                                            $src = url('upload/student_images/' . $student->image);
                                        } else {
                                            $src = url('upload/no_image.jpg');
                                        }
                                    @endphp
                                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-30">
                                        <div class="box box-solid box-bordered h-100 shadow-sm" style="border-radius: 8px; overflow: hidden; background: #ffffff; border: 1px solid #e2e8f0;">
                                            <div class="text-center p-10" style="background: #f8fafc; height: 230px; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #e2e8f0;">
                                                <img src="{{ $src }}" alt="{{ $studentName }}" style="max-width: 100%; max-height: 210px; width: auto; height: auto; object-fit: contain; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                            </div>
                                            <div class="box-body text-center p-15" style="background: #ffffff;">
                                                <h4 class="font-weight-700 mb-5" style="color: #0f172a !important; font-size: 16px; line-height: 1.3;">
                                                    {{ $studentName }}
                                                </h4>
                                                
                                                <div class="mb-10">
                                                    <span class="badge px-12 py-6 font-weight-600" style="background-color: #0284c7 !important; color: #ffffff !important; font-size: 12px; border-radius: 4px;">
                                                        Reg No: {{ $regNo }}
                                                    </span>
                                                </div>

                                                @if(!empty($student->gender))
                                                    <small class="d-block mb-10 font-weight-600" style="color: #64748b !important;">
                                                        Gender: {{ ucfirst($student->gender) }}
                                                    </small>
                                                @endif

                                                @if($canManage)
                                                    <div class="mt-15 pt-15" style="border-top: 1px solid #e2e8f0;">
                                                        <form action="{{ route('class.gallery.store', $class->id) }}" method="post" enctype="multipart/form-data" class="mb-10">
                                                            @csrf
                                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                            <div class="form-group mb-10">
                                                                <label class="font-size-12 mb-5 d-block text-left font-weight-600" style="color: #475569 !important;">Upload / Replace Photo</label>
                                                                <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" required style="background: #f1f5f9; color: #0f172a;">
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-info btn-block font-weight-600">
                                                                <i class="fa fa-upload mr-5"></i> Upload Photo
                                                            </button>
                                                        </form>
                                                        @if($photo)
                                                            <form action="{{ route('class.gallery.destroy', $photo->id) }}" method="post" onsubmit="return confirm('Remove custom gallery photo for {{ addslashes($studentName) }}?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-block mt-5 font-weight-600">
                                                                    <i class="fa fa-trash mr-5"></i> Remove Photo
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($validCount === 0)
                                    <div class="col-12 my-20">
                                        <div class="box box-solid text-center p-40" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px;">
                                            <div class="mb-15">
                                                <i class="fa fa-users text-muted fa-4x"></i>
                                            </div>
                                            <h3 class="font-weight-700 text-dark mb-10">No Active Students in {{ $class->name }}</h3>
                                            <p class="text-muted mb-20">There are currently no active student enrollments assigned to this class for the active academic session.</p>
                                            <a href="{{ route('class.gallery.index') }}" class="btn btn-info font-weight-600">
                                                <i class="fa fa-arrow-left mr-5"></i> Back to Class Gallery List
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
