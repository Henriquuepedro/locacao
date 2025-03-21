@extends('adminlte::page')

@section('title', 'Configuração')

@section('content_header')
    <h1 class="m-0 text-dark">Configuração</h1>
@stop

@section('css')
    <style>
        .permissions input[name^="user_"],
        .permissions input[name^="newuser_"],
        .permissions label[for^="user_"],
        .permissions label[for^="newuser_"]{
            cursor: pointer;
        }
        #dropdownConfigUser i {
            margin-left: 10px;
        }
        [aria-labelledby="dropdownConfigUser"] .btn{
            border-radius: 0;
        }
        #viewPermission .permissions .card .card-body ,
        #newUserModal .permissions .card .card-body {
            padding: 0.8rem 0.8rem;
        }
        .card-config-company {
            border: 1px solid #000;
            border-radius: 5px;
            padding: 10px 0;
            background: #666;
            color: #fff;
        }
        @media (max-width: 576px) {
            #users-registred .permissions .card .card-body {
                padding: 0.8rem 0.8rem;
            }
            #users-registred .card .card-body .user-avatar{
                width: 100%;
                text-align: center;
            }
            [aria-labelledby="dropdownConfigUser"]{
                top: -163px !important;
                left: 0px !important;;
                right: 40px !important;;
            }
        }
        #integration .card{
            background-color: var(--bs-border-color);
        }
        #integration .card-body p,
        #integration .card-body h4 {
            color: var(--bs-card-title-color);
        }
    </style>
@stop

@section('js')
    <script src="{{ asset('assets/js/shared/file-upload.js') }}" type="application/javascript"></script>
    <script src="{{ asset('assets/js/views/config/form.js') }}" type="application/javascript"></script>
    <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}" type="application/javascript"></script>
@stop

@section('content')
    <div class="row profile-page">
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
            @if(session('success'))
                <div class="alert alert-animate alert-success"><i class="fa fa-check-circle"></i> {{session('success')}}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-animate alert-danger mt-2">{{session('warning')}}</div>
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="header-card-body">
                        <h4 class="card-title no-border">Configurações da Empresa</h4>
                    </div>
                    <div class="home-tab">
                        <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active ps-0" id="company-tab" data-bs-toggle="tab" href="#company" role="tab" aria-controls="company" aria-selected="true">Empresa</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="users-tab" data-bs-toggle="tab" href="#users" role="tab" aria-controls="users" aria-selected="false">Usuário</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="config-tab" data-bs-toggle="tab" href="#config" role="tab" aria-controls="config" aria-selected="false">Configuração</a>
                                </li>
                                @if (false)
                                <li class="nav-item">
                                    <a class="nav-link" id="integration-tab" data-bs-toggle="tab" href="#integration" role="tab" aria-controls="integration" aria-selected="false">Integração</a>
                                </li>
                                @endif
                            </ul>
                        </div>
                        <div class="tab-content tab-content-basic">
                            <div class="tab-pane fade show active" id="company" role="tabpanel" aria-labelledby="company-tab">
                                <form action="{{ route('config.update.company') }}" method="POST" enctype="multipart/form-data" id="formUpdateCompany">
                                    <div class="row">
                                        <div class="col-md-9 pull-left">
                                            <div class="row">
                                                <div class="form-group {{  $company->type_person == 'pf' ? 'col-md-12' : 'col-md-6'}}">
                                                    <label for="name">{{ $company->type_person == 'pf' ? 'Nome Completo' : 'Razão Social' }}</label>
                                                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name') ?? $company->name }}" required>
                                                </div>
                                                @if ($company->type_person == 'pj')
                                                    <div class="form-group col-md-6">
                                                        <label for="fantasy">Fantasia</label>
                                                        <input type="text" class="form-control" name="fantasy" id="fantasy" value="{{ old('fantasy') ?? $company->fantasy }}">
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-5">
                                                    <label>{{ $company->type_person == 'pf' ? 'CPF' : 'CNPJ' }}</label>
                                                    <input type="tel" class="form-control" id="cpf_cnpj" value="{{ old('cpf_cnpj') ?? $company->cpf_cnpj }}" disabled>
                                                </div>
                                                <div class="form-group col-md-7">
                                                    <label for="email">E-mail Comercial</label>
                                                    <input type="email" class="form-control" name="email" id="email" value="{{ old('email') ?? $company->email }}" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-4">
                                                    <label for="phone_1">Telefone Primário</label>
                                                    <input type="tel" class="form-control" name="phone_1" id="phone_1" value="{{ old('phone_1') ?? $company->phone_1 }}" required>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="phone_2">Telefone Secundário</label>
                                                    <input type="tel" class="form-control" name="phone_2" id="phone_2" value="{{ old('phone_2') ?? $company->phone_2 }}">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="contact">Contato</label>
                                                    <input type="text" class="form-control" name="contact" id="contact" value="{{ old('contact') ?? $company->contact }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex justify-content-center flex-wrap pull-right">
                                            <div class="form-group col-md-10">
                                                <div class="logo-company text-center col-md-12">
                                                    <img src="{{ $company->logo }}" style="max-height:100px; max-width: 100%" id="src-profile-logo">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-10">
                                                <input type="file" name="profile_logo" id="profile-logo" class="file-upload-default">
                                                <div class="input-group col-md-12">
                                                    <input type="text" class="form-control file-upload-info" disabled placeholder="Alterar Logo"/>
                                                    <span class="input-group-append">
                                                                <button class="file-upload-browse btn btn-info" type="button">Alterar</button>
                                                            </span>
                                                </div>
                                                <small class="d-flex justify-content-center">Imagens em JPG, JPEG ou PNG até 2mb. Proporção de 3:1.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <hr class="mb-0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mt-3">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h4>Endereço da Empresa</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-3">
                                                    <label for="cep">CEP</label>
                                                    <input type="tel" class="form-control" name="cep" id="cep" value="{{ old('cep') ?? $company->cep }}">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="address">Endereço</label>
                                                    <input type="text" class="form-control" name="address" id="address" value="{{ old('address') ?? $company->address }}">
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="number">Número</label>
                                                    <input type="text" class="form-control" name="number" id="number" value="{{ old('number') ?? $company->number }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label for="complement">Complemento</label>
                                                    <input type="text" class="form-control" name="complement" id="complement" value="{{ old('complement') ?? $company->complement }}">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="reference">Referência</label>
                                                    <input type="text" class="form-control" name="reference" id="reference" value="{{ old('reference') ?? $company->reference }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-4">
                                                    <label for="neigh">Bairro</label>
                                                    <input type="text" class="form-control" name="neigh" id="neigh" value="{{ old('neigh') ?? $company->neigh }}">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="address_state">Estado</label>
                                                    <select class="form-control" name="state" id="state" data-value-state="{{ old('state', $company->state ?? '') }}"></select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="address_city">Cidade</label>
                                                    <select class="form-control" name="city" id="city" data-value-city="{{ old('city', $company->city ?? '') }}"></select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="form-group col-md-12 text-right pt-3 mt-3 border-top d-flex justify-content-end">
                                                    <button type="submit" class="btn btn-success col-md-3"><i class="fa fa-save"></i> Salvar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{ csrf_field() }}
                                </form>
                            </div>
                            <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                                <div class="row">
                                    <div class="col-md-12 mb-4 mt-2 text-right">
                                        <button type="button" class="btn btn-success col-md-3" id="new-user"><i class="fa fa-user-plus"></i> Criar Usuário</button>
                                    </div>
                                    <div id="users-registred" class="col-md-12 no-padding"></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="config" role="tabpanel" aria-labelledby="config-tab">
                                <form action="{{ route('config.update.config') }}" method="POST" enctype="multipart/form-data" id="formUpdateCompany">
                                    <div class="row">
                                        <div class="form-group col-md-12 text-center mb-2">
                                            <h4>Defina as configurações para seu ambiente.</h4>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-12 d-flex justify-content-center flex-wrap">
                                            @foreach($settings['company_config'] as $config_name => $config_value)
                                                <div class="form-group col-md-3 card-config-company">
                                                    <div class="switch d-flex flex-wrap justify-content-center text-center">
                                                        <input type="checkbox" class="check-style check-md" name="{{ $config_name }}" id="{{ $config_name }}" {{ old() ? old($config_name) ? 'checked': '' : ($config_value ? 'checked' : '') }}>
                                                        <label for="{{ $config_name }}" class="check-style check-md"></label><span class="col-md-12">{{ __("field.$config_name") }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-12 d-flex justify-content-end mt-3">
                                            <button class="btn btn-success"><i class="fa fa-save"></i> Salvar Configurações</button>
                                        </div>
                                    </div>
                                    {{ csrf_field() }}
                                </form>
                            </div>
                            @if (false)
                            <div class="tab-pane fade" id="integration" role="tabpanel" aria-labelledby="integration-tab">
                                <div class="row">
                                    <div class="form-group col-md-12 text-center mb-2">
                                        <h4>Defina as integrações para seu ambiente.</h4>
                                    </div>
                                </div>
                                <div class="row d-flex justify-content-center">
                                    @foreach($integrations as $integration)
                                    <div class="col-md-4 grid-margin stretch-card">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-sm-flex flex-row flex-wrap text-start align-items-center">
                                                    <img src="{{ asset("assets/images/system/integrations/$integration[name].png") }}" class="img-lg rounded" alt="profile image" />
                                                    <div class="ms-sm-3 ms-md-0 ms-xl-3 mt-2 mt-sm-0 mt-md-2 mt-xl-0">
                                                        <h4>{{ $integration['description'] }}</h4>
                                                        <p class="mb-0 fw-bold">Conecte sua conta whatsapp para envios automáticos</p>
                                                        <button type="button" class="btn btn-primary btn-flat btn-sm" data-bs-toggle="modal" data-bs-target="#integration-{{ $integration['name'] }}"><i class="fa fa-plug"></i> Conectar</button>
                                                        <div class="additional-info"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <form action="{{ route('ajax.user.new-user') }}" method="POST" id="formCreateUser">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newUserModalLabel">Cadastro de novo usuário</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-primary" role="alert">
                                            <i class="mdi mdi-alert-circle"></i> Após a criação do usuário, o mesmo deverá acessar o e-mail e realizar uma confirmação para acessar a plataforma.
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-7">
                                        <label>Nome</label>
                                        <input type="text" class="form-control" name="name_modal">
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>Tipo de usuário</label>
                                        <select class="form-control" name="type_user">
                                            <option value="0">Usuário</option>
                                            <option value="1">Administrador</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-7">
                                        <label>E-mail</label>
                                        <input type="email" class="form-control" name="email_modal">
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>Telefone</label>
                                        <input type="tel" class="form-control" name="phone_modal">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>Senha</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="password_modal" id="password_modal">
                                            <div class="input-group-addon input-group-append">
                                                <button type="button" class="btn btn-secondary show-hide-password"><i class="fa fa-eye"></i></button>
                                            </div>
                                        </div>
                                        <small>A senha deve conter no mínimo 8 dígitos.</small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Confirme a Senha</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="password_modal_confirmation">
                                            <div class="input-group-addon input-group-append">
                                                <button type="button" class="btn btn-secondary show-hide-password"><i class="fa fa-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <hr>
                                    </div>
                                </div>
                                <div class="user-permission">
                                    <div class="row">
                                        <div class="col-md-12 mb-3 text-center">
                                            <h4 class="no-margin">Permissões de acesso</h4>
                                            <small>Defina as permissões do usuário</small>
                                            <br>
                                            <div class="d-flex justify-content-center mt-4">
                                                <div class="switch">
                                                    <input type="checkbox" class="switch-input select-all-permission" id="permission_select_all_permission">
                                                    <label for="permission_select_all_permission" class="switch-label"></label>
                                                </div>
                                                Selecionar Tudo
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-center">
                                        {!! $htmlPermissions !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-around">
                        <button type="button" class="btn btn-secondary col-md-3" data-bs-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                        <button type="submit" class="btn btn-success col-md-3"><i class="fa fa-save"></i> Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewPermission" tabindex="-1" role="dialog" aria-labelledby="newViewPermission" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <form action="{{ route('ajax.user.update-permission') }}" method="POST" id="formUpdatePermission">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newViewPermission">Permissões do usuário</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-center mt-4">
                            <div class="switch">
                                <input type="checkbox" class="switch-input select-all-permission" id="permission_select_all_permission_update">
                                <label for="permission_select_all_permission_update" class="switch-label"></label>
                            </div>
                            Selecionar Tudo
                        </div>
                        <div class="d-flex flex-wrap justify-content-center user-permission-update"></div>
                    </div>
                    <div class="modal-footer d-flex justify-content-around">
                        <button type="button" class="btn btn-secondary col-md-3" data-bs-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                        <button type="submit" class="btn btn-success col-md-3"><i class="fa fa-sync-alt"></i> Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="updateUser" tabindex="-1" role="dialog" aria-labelledby="updateUser" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <form action="{{ route('ajax.user.update') }}" method="POST" id="formUpdateUser">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateUser">Atualizar usuário</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label>Nome</label>
                                        <input type="text" name="update_user_name" class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label>E-mail</label>
                                        <input type="text" name="update_user_email" id="update_user_email" class="form-control">
                                        <small>Ao realizar a alteração de e-mail, o usuário deverá verificar o e-mail novamente.</small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Telefone</label>
                                        <input type="text" name="update_user_phone" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-around">
                        <button type="button" class="btn btn-secondary col-md-3" data-bs-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                        <button type="submit" class="btn btn-success col-md-3"><i class="fa fa-sync-alt"></i> Atualizar</button>
                    </div>
                    <input type="hidden" name="update_user_id">
                </form>
            </div>
        </div>
    </div>
    @if (false)
    <div class="modal" id="integration-whatsapp" tabindex="-1" role="dialog" aria-labelledby="newIntegrationWhatsapp" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <form action="{{ route('ajax.integration.create-integration') }}" method="POST" id="formSaveIntegration">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newIntegrationWhatsapp">Integração com Whatsapp</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="form-group col-md-12 d-flex justify-content-center align-items-center flex-nowrap">
                                <h3 class="mr-2">WhatsApp</h3> <img src="{{ asset("assets/images/system/integrations/$integration[name].png") }}" class="img-sm rounded" alt="profile image" />
                            </div>
                        </div>
                        <div class="row" id="content-start-integration-whatsapp">
                            <div class="form-group col-md-12 d-flex justify-content-center">
                                <button type="button" id="btn-start-integration" class="col-md-6 btn btn-primary"><i class="fa fa-circle-play"></i> Iniciar Integração</button>
                            </div>
                        </div>
                        <div class="row" id="content-terminate-integration-whatsapp">
                            <div class="form-group col-md-12 d-flex justify-content-center">
                                <button type="button" id="btn-terminate-integration" class="col-md-6 btn btn-primary"><i class="fa fa-circle-play"></i> Encerrar Integração</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-12 d-flex justify-content-center mt-2 flex-wrap" id="content-integration-whatsapp">

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-right">
                        <button type="button" class="btn btn-secondary col-md-3" data-bs-dismiss="modal"><i class="fa fa-times"></i> Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    <input type="hidden" id="routeGetUserPermission" value="{{ route('ajax.user.get-permission') }}">
    <input type="hidden" id="routeInactiveUser" value="{{ route('ajax.user.inactivate') }}">
    <input type="hidden" id="routeUserChangeType" value="{{ route('ajax.user.change-type') }}">
    <input type="hidden" id="routeDeleteUser" value="{{ route('ajax.user.delete') }}">
    <input type="hidden" id="routeGetUser" value="{{ route('ajax.user.get-user') }}">
    <input type="hidden" id="routeGetUsers" value="{{ route('ajax.user.get-users') }}">
    <input type="hidden" id="routeCheckIntegration" value="{{ route('ajax.integration.check-integration') }}">
    <input type="hidden" id="routeCreateIntegration" value="{{ route('ajax.integration.create-integration') }}">
    <input type="hidden" id="routeTerminateIntegration" value="{{ route('ajax.integration.terminate-integration') }}">
@stop
