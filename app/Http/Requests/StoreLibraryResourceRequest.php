<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->role === 'Admin' || $user->hasRole('Admin')
            || $user->role === 'Teacher' || $user->hasRole('Teacher'));
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'nullable|string|max:255',
            'visibility' => 'required|in:general,class',
            'class_id' => 'nullable|required_if:visibility,class|exists:student_classes,id',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,mp4,mp3',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $file = $this->file('file');
            if (!$file) {
                return;
            }

            $ext = strtolower($file->getClientOriginalExtension());
            $maxKb = $ext === 'mp4' ? 102400 : 20480;
            if (($file->getSize() / 1024) > $maxKb) {
                $validator->errors()->add(
                    'file',
                    $ext === 'mp4'
                        ? 'Video files may not be greater than 100MB.'
                        : 'This file may not be greater than 20MB.'
                );
            }
        });
    }
}
