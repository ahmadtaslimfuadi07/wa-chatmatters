@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['buttons'=>[
	[
		'name'=>'Back',
		'url'=>route('user.apps.index'),
	]
]])
@endsection
@section('content')
<div>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0 font-weight-bolder">{{__('Integration')}}</h3>
                    </div>
                    <div class="card-body">
                        <div class="">
                            <ul class="nav nav-pills nav-fill" id="myTab1" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="home-tab" data-toggle="tab" data-target="#messages" type="button" role="tab" aria-controls="home" aria-selected="true">Create New Message</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#constact" type="button" role="tab" aria-controls="home" aria-selected="false">Create New Contact</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#chat" type="button" role="tab" aria-controls="home" aria-selected="false">Get Chat List</a>
                                </li>
                            </ul>
                            <div class="tab-content mt-4 mb-4" id="myTabContent1">
                                <div class="tab-pane fade show active" id="messages" role="tabpanel" aria-labelledby="home-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0 font-weight-bolder">{{__('Create New Message')}}</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="">
                                                @php
                                                $url=route('api.create.message');
                                                @endphp
                                                <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link active" id="home-tab" data-toggle="tab" data-target="#curl" type="button" role="tab" aria-controls="home" aria-selected="true">cUrl</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#php" type="button" role="tab" aria-controls="profile" aria-selected="false">Php</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#nodejs" type="button" role="tab" aria-controls="profile" aria-selected="false">NodeJs - Request</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="contact-tab" data-toggle="tab" data-target="#python" type="button" role="tab" aria-controls="contact" aria-selected="false">Python</a>
                                                    </li>
                                                </ul>
                                                <div class="tab-content mt-4 mb-4" id="myTabContent">
                                                    <div class="tab-pane fade show active" id="curl" role="tabpanel" aria-labelledby="home-tab">
                                                        <div class="language-markup">
                                                            <pre class="language-markup" tabindex="0">
                                                                <h3>{{ __('Text Message Only') }}</h3>
                                                                curl --location --request POST '{{ $url }}' \
                                                                --form 'appkey="{{ $key }}"' \
                                                                --form 'authkey="{{ Auth::user()->authkey }}"' \
                                                                --form 'to="RECEIVER_NUMBER"' \
                                                                --form 'message="Example message"' \
                                                            </pre>
                                                        </div>
                                                        <hr>
                                                        <div class="language-markup">
                                                            <pre class="language-markup" tabindex="0">
                                                                <h3>{{ __('Text Message with file') }}</h3>
                                                                curl --location --request POST '{{ $url }}' \
                                                                --form 'appkey="{{ $key }}"' \
                                                                --form 'authkey="{{ Auth::user()->authkey }}"' \
                                                                --form 'to="RECEIVER_NUMBER"' \
                                                                --form 'message="Example message"' \
                                                                --form 'file="https://www.africau.edu/images/default/sample.pdf"'
                                                            </pre>
                                                        </div>
                                                        <hr>
                                                        <div class="language-markup">
                                                            <pre class="language-markup" tabindex="2">
                                                                <code class="language-markup">
                                                                <h3>{{ __('Template Only') }}</h3>
                                                                curl --location --request POST '{{ $url }}' \
                                                                --form 'appkey="{{ $key }}"' \
                                                                --form 'authkey="{{ Auth::user()->authkey }}"' \
                                                                --form 'to="RECEIVER_NUMBER"' \
                                                                --form 'template_id="TEMPLATE_ID"' \
                                                                --form 'variables[{variableKey1}]="jhone"' \
                                                                --form 'variables[{variableKey2}]="replaceable value"'

                                                                </code>
                                                            </pre>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="php" role="tabpanel" aria-labelledby="profile-tab">
                                                        <pre class="language-markup" tabindex="1div">
                                                            <h3>{{ __('Text Message Only') }}</h3>
                                                            $curl = curl_init();

                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => '{{ $url }}',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS => array(
                                                            'appkey' => '{{ $key }}',
                                                            'authkey' => '{{ Auth::user()->authkey }}',
                                                            'to' => 'RECEIVER_NUMBER',
                                                            'message' => 'Example message',
                                                            'sandbox' => 'false'
                                                            ),
                                                            ));

                                                            $response = curl_exec($curl);

                                                            curl_close($curl);
                                                            echo $response;
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="1">
                                                            <h3>{{ __('Text Message with file') }}</h3>
                                                            $curl = curl_init();

                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => '{{ $url }}',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS => array(
                                                            'appkey' => '{{ $key }}',
                                                            'authkey' => '{{ Auth::user()->authkey }}',
                                                            'to' => 'RECEIVER_NUMBER',
                                                            'message' => 'Example message',
                                                            'file' => 'https://www.africau.edu/images/default/sample.pdf',
                                                            'sandbox' => 'false'
                                                            ),
                                                            ));

                                                            $response = curl_exec($curl);

                                                            curl_close($curl);
                                                            echo $response;
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="1">
                                                            <h3>{{ __('Template Message Only') }}</h3>
                                                            $curl = curl_init();

                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => '{{ $url }}',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS => array(
                                                            'appkey' => '{{ $key }}',
                                                            'authkey' => '{{ Auth::user()->authkey }}',
                                                            'to' => 'RECEIVER_NUMBER',
                                                            'template_id' => 'TEMPLATE_ID',
                                                            'variables' => array(
                                                                '{variableKey1}' => 'Jhone',
                                                                '{variableKey2}' => 'replaceable value'
                                                            )
                                                            ),
                                                            ));

                                                            $response = curl_exec($curl);

                                                            curl_close($curl);
                                                            echo $response;
                                                        </pre>
                                                    </div>
                                                    <div class="tab-pane fade" id="nodejs" role="tabpanel" aria-labelledby="contact-tab">
                                                        <pre class="language-markup" tabindex="2">
                                                            <code class="language-markup">
                                                            <h3>{{ __('Text Message Only') }}</h3>
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url }}',
                                                            'headers': {
                                                            },
                                                            formData: {
                                                                'appkey': '{{ $key }}',
                                                                'authkey': '{{ Auth::user()->authkey }}',
                                                                'to': 'RECEIVER_NUMBER',
                                                                'message': 'Example message'
                                                            }
                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });

                                                            </code>
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="2">
                                                            <code class="language-markup">
                                                            <h3>{{ __('Text Message With File') }}</h3>
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url }}',
                                                            'headers': {
                                                            },
                                                            formData: {
                                                                'appkey': '{{ $key }}',
                                                                'authkey': '{{ Auth::user()->authkey }}',
                                                                'to': 'RECEIVER_NUMBER',
                                                                'message': 'Example message',
                                                                'file': 'https://www.africau.edu/images/default/sample.pdf'
                                                            }
                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });

                                                            </code>
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="2">
                                                            <code class="language-markup">
                                                            <h3>{{ __('Template Only') }}</h3>
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url }}',
                                                            'headers': {
                                                            },
                                                            formData: {
                                                                'appkey': '{{ $key }}',
                                                                'authkey': '{{ Auth::user()->authkey }}',
                                                                'to': 'RECEIVER_NUMBER',
                                                                'template_id': 'SELECTED_TEMPLATE_ID',
                                                                'variables': {
                                                                    '{variableKey1}' : 'jhone',
                                                                    '{variableKey2}' : 'replaceable value'
                                                                }
                                                            }
                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });

                                                            </code>
                                                        </pre>
                                                    </div>
                                                    <div class="tab-pane fade" id="python" role="tabpanel" aria-labelledby="contact-tab">
                                                        <pre class="language-markup" tabindex="3">
                                                            <code class="language-markup">
                                                            import requests

                                                            url = "{{ $url }}"

                                                            payload={
                                                            'appkey': '{{ $key }}',
                                                            'authkey': '{{ Auth::user()->authkey }}',
                                                            'to': 'RECEIVER_NUMBER',
                                                            'message': 'Example message',

                                                            }
                                                            files=[

                                                            ]
                                                            headers = {}

                                                            response = requests.request("POST", url, headers=headers, data=payload, files=files)

                                                            print(response.text)
                                                            </code>
                                                        </pre>
                                                    </div>
                                                </div>
                                            </div>
                                            <h3 class="font-weight-bolder">{{__('Successful Json Callback')}}</h3>
                                            <pre>
                                                <code>
                                                {
                                                    "message_status": "Success",
                                                    "data": {
                                                        "from": "SENDER_NUMBER",
                                                        "to": "RECEIVER_NUMBER",
                                                        "status_code": 200
                                                    }
                                                }
                                                </code>
                                            </pre>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0 font-weight-bolder">{{__('Parameters')}}</h3>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-flush">
                                                <thead class="">
                                                <tr>
                                                    <th>{{__('S/N')}}</th>
                                                    <th>{{__('Value')}}</th>
                                                    <th>{{__('Type')}}</th>
                                                    <th>{{__('Required')}}</th>
                                                    <th>{{__('Description')}}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>{{__('1.')}}</td>
                                                    <td>{{__('appkey')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("Used to authorize a transaction for the app") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('2.')}}</td>
                                                    <td>{{__('authkey')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("Used to authorize a transaction for the is valid user") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('3.')}}</td>
                                                    <td>{{__('to')}}</td>
                                                    <td>{{__('number')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("Who will receive the message the Whatsapp number should be full number with country code") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('4.')}}</td>
                                                    <td>{{__('template_id')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('No')}}</td>
                                                    <td>{{ __("Used to authorize a transaction for the template") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('5.')}}</td>
                                                    <td>{{__('message')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('No')}}</td>
                                                    <td>{{ __("The transactional message max:1000 words") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('6.')}}</td>
                                                    <td>{{__('file')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('No')}}</td>
                                                    <td>{{ __("file extension type should be in jpg,jpeg,png,webp,pdf,docx,xlsx,csv,txt") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('7.')}}</td>
                                                    <td>{{__('variables')}}</td>
                                                    <td>{{__('Array')}}</td>
                                                    <td>{{__('No')}}</td>
                                                    <td>{{ __("the first value you list replaces the {1} variable in the template message and the second value you list replaces the {2} variable") }}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade show" id="constact" role="tabpanel" aria-labelledby="profile-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0 font-weight-bolder">{{__('Create New Contact')}}</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="">
                                                @php
                                                $url_create=route('api.create.contact');
                                                $url_update=route('api.update.contact');
                                                @endphp
                                                <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link active" id="home-tab" data-toggle="tab" data-target="#curl1" type="button" role="tab" aria-controls="home" aria-selected="true">cUrl</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#php1" type="button" role="tab" aria-controls="profile" aria-selected="false">Php</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#nodejs1" type="button" role="tab" aria-controls="profile" aria-selected="false">NodeJs - Request</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="contact-tab" data-toggle="tab" data-target="#python1" type="button" role="tab" aria-controls="contact" aria-selected="false">Python</a>
                                                    </li>
                                                </ul>
                                                <div class="tab-content mt-4 mb-4" id="myTabContent">
                                                    <div class="tab-pane fade show active" id="curl1" role="tabpanel" aria-labelledby="home-tab">
                                                        <div class="language-markup">
                                                            <pre class="language-markup" tabindex="0">
                                                                <h3>{{ __('Create Contact') }}</h3>
                                                                curl --location '{{ $url_create }}' \
                                                                --header 'Content-Type: application/json' \
                                                                --data '{
                                                                    "user_id":{{ $info->user_id }},
                                                                    "device_id":{{ $info->device_id }},
                                                                    "phone":"086719242393",
                                                                    "name":"Ilham",
                                                                    "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                                }'
                                                            </pre>
                                                        </div>
                                                        <hr>
                                                        <div class="language-markup">
                                                            <pre class="language-markup" tabindex="0">
                                                                <h3>{{ __('Update Contact') }}</h3>
                                                                curl --location '{{ $url_update }}' \
                                                                --header 'Content-Type: application/json' \
                                                                --data '{
                                                                    "id":ID,
                                                                    "phone":"086719242393",
                                                                    "name":"Ilham",
                                                                    "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                                }'
                                                            </pre>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="php1" role="tabpanel" aria-labelledby="profile-tab">
                                                        <pre class="language-markup" tabindex="1div">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Create New Contact')}}</h3>
                                                            $curl = curl_init();

                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => '{{ $url_create }}',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS =>'{
                                                                "user_id":{{ $info->user_id }},
                                                                "device_id":{{ $info->device_id }},
                                                                "phone":"086719242393",
                                                                "name":"Ilham",
                                                                "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                            }',
                                                            CURLOPT_HTTPHEADER => array(
                                                                'Content-Type: application/json'
                                                            ),
                                                            ));

                                                            $response = curl_exec($curl);

                                                            curl_close($curl);
                                                            echo $response;
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="1">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Update Contact')}}</h3>
                                                            $curl = curl_init();

                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => '{{ $url_update }}',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS =>'{
                                                                "id":ID,
                                                                "phone":"086719242393",
                                                                "name":"Ilham",
                                                                "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                            }',
                                                            CURLOPT_HTTPHEADER => array(
                                                                'Content-Type: application/json'
                                                            ),
                                                            ));

                                                            $response = curl_exec($curl);

                                                            curl_close($curl);
                                                            echo $response;
                                                        </pre>
                                                    </div>
                                                    <div class="tab-pane fade" id="nodejs1" role="tabpanel" aria-labelledby="contact-tab">
                                                        <pre class="language-markup" tabindex="2">
                                                            <code class="language-markup">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Create New Contact')}}</h3>
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url_create }}',
                                                            'headers': {
                                                                'Content-Type': 'application/json'
                                                            },
                                                            body: JSON.stringify({
                                                                "user_id":{{ $info->user_id }},
                                                                "device_id":{{ $info->device_id }},
                                                                "phone": "086719242393",
                                                                "name":"Ilham",
                                                                "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                            })

                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });

                                                            </code>
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="2">
                                                            <code class="language-markup">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Update Contact')}}</h3>
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url_update }}',
                                                            'headers': {
                                                                'Content-Type': 'application/json'
                                                            },
                                                            body: JSON.stringify({
                                                                "id": ID,
                                                                "phone": "086719242393",
                                                                "name":"Ilham",
                                                                "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                            })

                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });

                                                            </code>
                                                        </pre>
                                                    </div>
                                                    <div class="tab-pane fade" id="python1" role="tabpanel" aria-labelledby="contact-tab">
                                                        <pre class="language-markup" tabindex="3">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Create New Contact')}}</h3>
                                                            <code class="language-markup">
                                                            import requests
                                                            import json

                                                            url = "{{ $url_create }}"

                                                            payload = json.dumps({
                                                            "user_id": {{ $info->user_id }},
                                                            "device_id": {{ $info->device_id }},
                                                            "phone": "086719242393",
                                                            "name":"Ilham",
                                                            "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                            })
                                                            headers = {
                                                            'Content-Type': 'application/json'
                                                            }

                                                            response = requests.request("POST", url, headers=headers, data=payload)

                                                            print(response.text)
                                                            </code>
                                                        </pre>
                                                        <hr>
                                                        <pre class="language-markup" tabindex="2">
                                                            <code class="language-markup">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Update Contact')}}</h3>
                                                            import requests
                                                            import json

                                                            url = "{{ $url_update }}"

                                                            payload = json.dumps({
                                                            "id": ID,
                                                            "phone": "086719242393",
                                                            "name":"Ilham",
                                                            "lid":"3EB0EF2C6E29ZZ8FCE88"
                                                            })
                                                            headers = {
                                                            'Content-Type': 'application/json'
                                                            }

                                                            response = requests.request("POST", url, headers=headers, data=payload)

                                                            print(response.text)

                                                            </code>
                                                        </pre>
                                                    </div>
                                                </div>
                                            </div>
                                            <h3 class="font-weight-bolder">{{__('Successful Json Callback')}}</h3>
                                            <pre>
                                                <code>
                                                {
                                                    "code": 202,
                                                    "data": {
                                                        "id": ID,
                                                        "user_id": {{ $info->user_id }},
                                                        "name": "Ilham",
                                                        "phone": "086719242393",
                                                        "lid": "3EB0EF2C6E29ZZ8FCE88",
                                                        "created_at": "2023-11-23T06:37:52.000000Z",
                                                        "updated_at": "2023-11-23T06:37:52.000000Z",
                                                        "device_id": {{ $info->device_id }}
                                                    },
                                                    "message": "Succes"
                                                }
                                                </code>
                                            </pre>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0 font-weight-bolder">{{__('Parameters')}}</h3>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-flush">
                                                <thead class="">
                                                <tr>
                                                    <th>{{__('S/N')}}</th>
                                                    <th>{{__('Value')}}</th>
                                                    <th>{{__('Type')}}</th>
                                                    <th>{{__('Required')}}</th>
                                                    <th>{{__('Description')}}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>{{__('1.')}}</td>
                                                    <td>{{__('user_id')}}</td>
                                                    <td>{{__('number')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('2.')}}</td>
                                                    <td>{{__('device_id')}}</td>
                                                    <td>{{__('number')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('3.')}}</td>
                                                    <td>{{__('phone')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('4.')}}</td>
                                                    <td>{{__('name')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('No')}}</td>
                                                    <td>{{ __("Contact name") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('5.')}}</td>
                                                    <td>{{__('lid')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('No')}}</td>
                                                    <td>{{ __("WhatsApp LID (Locally Identifiable Data)") }}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade show" id="chat" role="tabpanel" aria-labelledby="profile-tab">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0 font-weight-bolder">{{__('Get Chat List')}}</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="">
                                                @php
                                                $url_get=route('api.chat.list');
                                                @endphp
                                                <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link active" id="home-tab" data-toggle="tab" data-target="#curl2" type="button" role="tab" aria-controls="home" aria-selected="true">cUrl</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#php2" type="button" role="tab" aria-controls="profile" aria-selected="false">Php</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#nodejs2" type="button" role="tab" aria-controls="profile" aria-selected="false">NodeJs - Request</a>
                                                    </li>
                                                    <li class="nav-item" role="presentation">
                                                        <a class="nav-link" id="contact-tab" data-toggle="tab" data-target="#python2" type="button" role="tab" aria-controls="contact" aria-selected="false">Python</a>
                                                    </li>
                                                </ul>
                                                <div class="tab-content mt-4 mb-4" id="myTabContent">
                                                    <div class="tab-pane fade show active" id="curl2" role="tabpanel" aria-labelledby="home-tab">
                                                        <div class="language-markup">
                                                            <pre class="language-markup" tabindex="0">
                                                                <h3>{{ __('Get Chat List') }}</h3>
                                                                curl --location '{{ $url_get }}' \
                                                                --header 'Content-Type: application/json' \
                                                                --data '
                                                                {
                                                                    "appkey":"{{ $key }}",
                                                                    "phone":"{{ $phone }}",
                                                                    "start_date":"{{ date('Y-m-d H:i:s') }}",
                                                                    "end_date":"{{ date('Y-m-d H:i:s') }}"
                                                                }'
                                                            </pre>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane fade" id="php2" role="tabpanel" aria-labelledby="profile-tab">
                                                        <pre class="language-markup" tabindex="1div">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Get Chat List')}}</h3>
                                                            $curl = curl_init();

                                                            curl_setopt_array($curl, array(
                                                            CURLOPT_URL => '{{ $url_create }}',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS =>'{
                                                                    "appkey":"{{ $key }}",
                                                                    "phone":"{{ $phone }}",
                                                                    "start_date":"{{ date('Y-m-d H:i:s') }}",
                                                                    "end_date":"{{ date('Y-m-d H:i:s') }}"
                                                                }',
                                                            CURLOPT_HTTPHEADER => array(
                                                                'Content-Type: application/json'
                                                            ),
                                                            ));

                                                            $response = curl_exec($curl);

                                                            curl_close($curl);
                                                            echo $response;
                                                        </pre>
                                                    </div>
                                                    <div class="tab-pane fade" id="nodejs2" role="tabpanel" aria-labelledby="contact-tab">
                                                        <pre class="language-markup" tabindex="2">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Get Chat List')}}</h3>
                                                            <code class="language-markup">
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url_create }}',
                                                            'headers': {
                                                                'Content-Type': 'application/json'
                                                            },
                                                            body: JSON.stringify({
                                                                    "appkey":"{{ $key }}",
                                                                    "phone":"{{ $phone }}",
                                                                    "start_date":"{{ date('Y-m-d H:i:s') }}",
                                                                    "end_date":"{{ date('Y-m-d H:i:s') }}"
                                                                })

                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });

                                                            </code>
                                                        </pre>
                                                    </div>
                                                    <div class="tab-pane fade" id="python2" role="tabpanel" aria-labelledby="contact-tab">
                                                        <pre class="language-markup" tabindex="3">
                                                            <h3 class="mb-0 font-weight-bolder">{{__('Get Chat List')}}</h3>
                                                            <code class="language-markup">
                                                            var request = require('request');
                                                            var options = {
                                                            'method': 'POST',
                                                            'url': '{{ $url_update }}',
                                                            'headers': {
                                                                'Content-Type': 'application/json'
                                                            },
                                                            body: JSON.stringify({
                                                                    "appkey":"{{ $key }}",
                                                                    "phone":"{{ $phone }}",
                                                                    "start_date":"{{ date('Y-m-d H:i:s') }}",
                                                                    "end_date":"{{ date('Y-m-d H:i:s') }}"
                                                                })

                                                            };
                                                            request(options, function (error, response) {
                                                            if (error) throw new Error(error);
                                                            console.log(response.body);
                                                            });
                                                            </code>
                                                        </pre>
                                                    </div>
                                                </div>
                                            </div>
                                            <h3 class="font-weight-bolder">{{__('Successful Json Callback')}}</h3>
                                            <pre>
                                                <code>
                                                {
                                                    "code": 202,
                                                    "data": "data": [
                                                        {
                                                            "phone": "62813XXXXXXXXXX",
                                                            "message": "Sample Chat",
                                                            "fromMe": "true",
                                                            "timestamp": "2024-08-01 08:17:45",
                                                            "file": "https://chatmatters.io/"
                                                        }
                                                    ],
                                                    "message": "Succes"
                                                }
                                                </code>
                                            </pre>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="mb-0 font-weight-bolder">{{__('Parameters')}}</h3>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-flush">
                                                <thead class="">
                                                <tr>
                                                    <th>{{__('S/N')}}</th>
                                                    <th>{{__('Value')}}</th>
                                                    <th>{{__('Type')}}</th>
                                                    <th>{{__('Required')}}</th>
                                                    <th>{{__('Description')}}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>{{__('1.')}}</td>
                                                    <td>{{__('appkey')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('2.')}}</td>
                                                    <td>{{__('phone')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('3.')}}</td>
                                                    <td>{{__('start_date')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("Y-m-d H:i:s (".date('Y-m-d H:i:s').")") }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('3.')}}</td>
                                                    <td>{{__('end_date')}}</td>
                                                    <td>{{__('string')}}</td>
                                                    <td>{{__('Yes')}}</td>
                                                    <td>{{ __("Y-m-d H:i:s (".date('Y-m-d H:i:s').")") }}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
