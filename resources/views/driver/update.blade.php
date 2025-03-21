@extends('adminlte::page')

@section('title', empty($driver) ? 'Cadastrar Motorista' : 'Alterar Motorista')

@section('content_header')
    <h1 class="m-0 text-dark">{{ empty($driver) ? 'Cadastrar Motorista' : 'Alterar Motorista' }}</h1>
@stop

@section('css')
@stop

@section('js')
<script src="{{ asset('assets/js/views/driver/form.js') }}" type="application/javascript"></script>
<script>
    // Validar dados
    $("#formUpdateDriver").validate({
        rules: {
            name: {
                required: true
            },
            email: {
                email: true
            },
            phone: {
                rangelength: [13, 14]
            },
            cpf: {
                cpf: true
            },
            rg: {
                number: true
            },
            cnh: {
                number: true
            },
            cnh_exp: {
                date: true
            }
        },
        messages: {
            name: {
                required: 'Informe um nome para o motorista'
            },
            email: {
                email: 'Informe um endereço de e-mail válido'
            },
            phone: {
                rangelength: "O número de telefone principal está inválido, informe um válido. (99) 999..."
            },
            rg: {
                number: "O número de RG deve conter apenas números"
            },
            cnh: {
                number: "O número da CNH deve conter apenas números"
            },
            cnh_exp: {
                date: "A data de expiração da CNH deve ser uma data válida"
            }
        },
        invalidHandler: function(event, validator) {
            $('html, body').animate({scrollTop:0}, 100);
            let arrErrors = [];
            $.each(validator.errorMap, function (key, val) {
                arrErrors.push(val);
            });
            setTimeout(() => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    html: '<ol><li>'+arrErrors.join('</li><li>')+'</li></ol>'
                });
            }, 150);
        },
        submitHandler: function(form) {
            $('#formCreateDriver [type="submit"]').attr('disabled', true);
            form.submit();
        }
    });
</script>
@stop

@php
    $disabled = in_array('DriverUpdatePost', $permissions) || empty($driver) ? '' : 'disabled';
@endphp

@section('content')
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row flex-grow">
                <div class="col-12">
                    @if ($errors->any())
                    <div class="alert alert-animate alert-warning">
                        <ol>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ol>
                    </div>
                    @endif
                    <form action="{{ route((empty($driver) ? 'driver.insert' : 'driver.update')) }}" method="POST" enctype="multipart/form-data" id="formUpdateDriver">
                        <div class="card">
                            <div class="card-body">
                                <div class="header-card-body">
                                    <h4 class="card-title">Dados do Motorista</h4>
                                    <p class="card-description"> Preencha o formulário abaixo com as informações do novo motorista </p>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-5">
                                        <label for="name">Nome do Motorista <sup>*</sup></label>
                                        <input {{ $disabled }} type="text" class="form-control" id="name" name="name" autocomplete="nope" value="{{ old('name', $driver->name ?? '') }}" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="email">Email</label>
                                        <input {{ $disabled }} type="email" class="form-control" id="email" name="email" autocomplete="nope" value="{{ old('email', $driver->email ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="phone">Telefone</label>
                                        <input {{ $disabled }} type="text" class="form-control" id="phone" name="phone" autocomplete="nope" value="{{ old('phone', $driver->phone ?? '') }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="cpf">CPF</label>
                                        <input {{ $disabled }} type="text" class="form-control" id="cpf" name="cpf" autocomplete="nope" value="{{ old('cpf', $driver->cpf ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="rg">RG</label>
                                        <input {{ $disabled }} type="text" class="form-control" id="rg" name="rg" autocomplete="nope" value="{{ old('rg', $driver->rg ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="cnh">CNH</label>
                                        <input {{ $disabled }} type="text" class="form-control" id="cnh" name="cnh" autocomplete="nope" value="{{ old('cnh', $driver->cnh ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="cnh_exp">Expiração CNH</label>
                                        <input {{ $disabled }} type="date" class="form-control" id="cnh_exp" name="cnh_exp" autocomplete="nope" value="{{ old('cnh_exp', $driver->cnh_exp ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-2">
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>CEP</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="address_zipcode" autocomplete="nope" value="{{ old('address_zipcode', $driver->address_zipcode ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Endereço</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="address_name" autocomplete="nope" value="{{ old('address_name', $driver->address_name ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label>Número</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="address_number" autocomplete="nope" value="{{ old('address_number', $driver->address_number ?? '') }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Complemento</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="address_complement" autocomplete="nope" value="{{ old('address_complement', $driver->address_complement ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Referência</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="address_reference" autocomplete="nope" value="{{ old('address_reference', $driver->address_reference ?? '') }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Bairro</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="address_neigh" autocomplete="nope" value="{{ old('address_neigh', $driver->address_neigh ?? '') }}">
                                    </div>
                                    <div  {{ $disabled }} class="form-group col-md-4">
                                        <label>Estado</label>
                                        <select class="form-control" name="address_state" data-value-state="{{ old('address_state', $driver->address_state ?? '') }}"></select>
                                    </div>
                                    <div  {{ $disabled }} class="form-group col-md-4">
                                        <label>Cidade</label>
                                        <select class="form-control" name="address_city" data-value-city="{{ old('address_city', $driver->address_city ?? '') }}"></select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="observation">Observação</label>
                                        <textarea {{ $disabled }} class="form-control" id="observation" name="observation" rows="3">{{ old('observation', $driver->observation ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-2">
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label>Comissão (%)</label>
                                        <input {{ $disabled }} type="text" class="form-control" name="commission" value="{{ old('commission', formatMoney($driver->commission ?? '')) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-2">
                            <div class="card-body d-flex justify-content-between">
                                <a href="{{ route('driver.index') }}" class="btn btn-secondary col-md-3"><i class="fa fa-arrow-left"></i> Cancelar</a>
                                @if(empty($disabled))<button type="submit" class="btn btn-success col-md-3"><i class="fa fa-sync"></i> {{ empty($driver) ? 'Cadastrar' : 'Atualizar' }}</button>@endif
                            </div>
                        </div>
                        @if(empty($disabled))
                        <input type="hidden" name="driver_id" value="{{ $driver->id ?? '' }}">
                        {{ csrf_field() }}
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
