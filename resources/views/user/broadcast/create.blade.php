@extends('layouts.main.app')
@section('head')
    @include('layouts.main.headersection', [
        'buttons' => [
            [
                'name' => 'Back',
                'url' => route('user.broadcast.index'),
            ],
        ],
    ])
@endsection
@push('topcss')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/select2/dist/css/select2.min.css') }}">
@endpush
@section('content')
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Create Broadcast') }}</h4>
                </div>
                <div class="card-body">
                    <form method="POST" class="ajaxform_instant_reload" action="{{ route('user.broadcast.store') }}">
                        @csrf
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Select App') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <select class="form-control" name="key" required="">
                                    @foreach ($apps as $app)
                                        <option value="{{ $app['key'] }}">{{ $app['title'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Title') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" name="title" placeholder="Input title" class="form-control"
                                    required="">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Body') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <textarea name="body" placeholder="Input body" class="form-control" rows="8" required></textarea>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Image') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Description/Remark') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="text" name="desc" placeholder="Input a description of this broadcast"
                                    class="form-control">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Start Date Time') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="datetime-local" name="start" class="form-control" required="">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Select Interval Type') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <select name="typeInterval" class="form-control" required="">
                                    <option value="JAM">{{ __('Hour') }}</option>
                                    <option value="MENIT">{{ __('Minute') }}</option>
                                    <option value="DETIK">{{ __('Second') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Interval') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="number" min="1" name="interval" placeholder="Input interval"
                                    class="form-control" required="">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Contact Limit per Interval') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <input type="number" min="1" name="limitContact"
                                    placeholder="Input contact limit per interval" class="form-control" required="">
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label
                                class="col-form-label text-md-right col-12 col-md-3 col-lg-3">{{ __('Select Contacts') }}</label>
                            <div class="col-sm-12 col-md-7">
                                <select class="form-control select2" name="contact[]" multiple="">
                                    @foreach ($contacts as $contact)
                                        <option value="{{ $contact->phone }}|{{ $contact->device_id }}">
                                            {{ $contact->name ? $contact->name . ' (' . $contact->phone . ')' : $contact->phone }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <label class="col-form-label text-md-right col-12 col-md-3 col-lg-3"></label>
                            <div class="col-sm-12 col-md-7">
                                <button type="submit"
                                    class="btn btn-outline-primary submit-btn">{{ __('Create Now') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('topjs')
    <script src="{{ asset('assets/vendor/select2/dist/js/select2.min.js') }}"></script>
@endpush
