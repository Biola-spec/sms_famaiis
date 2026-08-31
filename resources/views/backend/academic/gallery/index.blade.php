@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title font-weight-700 text-dark mb-0">
                                <i class="fa fa-picture-o text-primary mr-10"></i>{{ __('ui.class_gallery') }}
                            </h3>
                            <span class="text-muted d-block font-size-12 mt-5">Select a class to view or manage student photo galleries.</span>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                @forelse($classes as $class)
                                    <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-20">
                                        <div class="box box-solid box-bordered shadow-sm" style="border-radius: 8px; background: #ffffff;">
                                            <div class="box-body p-20">
                                                <div class="d-flex align-items-center mb-15">
                                                    <div class="bg-primary-light text-primary rounded-circle p-15 text-center mr-15" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fa fa-graduation-cap fa-lg"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-weight-700 text-dark mb-2">{{ $class->name }}</h4>
                                                        <span class="text-muted font-size-12 d-block">Class Photo Gallery</span>
                                                    </div>
                                                </div>
                                                <a href="{{ route('class.gallery.show', $class->id) }}" class="btn btn-info btn-block">
                                                    <i class="fa fa-folder-open mr-5"></i> View Gallery
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="box box-solid text-center p-40" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px;">
                                            <i class="fa fa-folder-o text-muted fa-4x mb-15"></i>
                                            <h4 class="font-weight-700 text-dark">No Classes Available</h4>
                                            <p class="text-muted mb-0">No classes assigned or available for your account.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
