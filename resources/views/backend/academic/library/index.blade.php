@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">{{ __('ui.elibrary') }}</h3>
                            @if($canUpload)
                                <a href="{{ route('library.create') }}" class="btn btn-success float-right">Upload resource</a>
                            @endif
                        </div>
                        <div class="box-body">
                            <form method="get" class="mb-20">
                                <div class="input-group" style="max-width:420px;">
                                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search title, subject, type...">
                                    <div class="input-group-append">
                                        <button class="btn btn-info" type="submit">Search</button>
                                    </div>
                                </div>
                            </form>
                            <div class="row">
                                @forelse($resources as $resource)
                                    <div class="col-md-4 mb-20">
                                        <div class="box box-solid">
                                            <div class="box-body">
                                                <h4>{{ $resource->title }}</h4>
                                                <p class="mb-5"><strong>Subject:</strong> {{ $resource->subject ?: '-' }}</p>
                                                <p class="mb-5"><strong>Type:</strong> {{ strtoupper($resource->file_type) }}</p>
                                                <p class="mb-5"><strong>Uploader:</strong> {{ optional($resource->teacher)->name }}</p>
                                                <p class="mb-10"><small>{{ $resource->created_at->format('M d, Y') }}
                                                    @if($resource->visibility === 'class')
                                                        · {{ optional($resource->studentClass)->name }}
                                                    @else
                                                        · General
                                                    @endif
                                                </small></p>
                                                @if($resource->description)
                                                    <p>{{ \Illuminate\Support\Str::limit($resource->description, 120) }}</p>
                                                @endif
                                                <a href="{{ route('library.download', $resource->id) }}" class="btn btn-sm btn-primary">Download</a>
                                                @if($canUpload && (Auth::user()->role == 'Admin' || Auth::user()->hasRole('Admin') || $resource->teacher_id == Auth::id()))
                                                    <form action="{{ route('library.destroy', $resource->id) }}" method="post" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="alert alert-info">No resources found.</div>
                                    </div>
                                @endforelse
                            </div>
                            {{ $resources->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
