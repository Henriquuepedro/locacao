@extends('adminlte::page')

@section('title', 'Listagem de Orçamentos')

@section('content_header')
    <h1 class="m-0 text-dark">Listagem de Orçamentos</h1>
@stop

@section('css')
@stop

@section('js')
    <script>
        let tableBudget;
        $(function () {
            tableBudget = getTable(false);
        });

        const getTable = (stateSave = true) => {
            const clients = $('[name="clients"]').val();
            const situation = $('[name="situation"]').val();

            return $("#tableBudgets").DataTable({
                "responsive": true,
                "processing": true,
                "autoWidth": false,
                "serverSide": true,
                "sortable": true,
                "searching": true,
                "stateSave": stateSave,
                "serverMethod": "post",
                "order": [[ 0, 'desc' ]],
                "ajax": {
                    url: '{{ route('ajax.budget.fetch') }}',
                    pages: 2,
                    type: 'POST',
                    data: {
                        clients,
                        situation,
                        "_token": $('meta[name="csrf-token"]').attr('content')
                    },
                    error: function(jqXHR, ajaxOptions, thrownError) {
                        console.log(jqXHR, ajaxOptions, thrownError);
                    }
                },
                "initComplete": function( settings, json ) {
                    $('[data-bs-toggle="tooltip"]').tooltip();
                },
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json"
                }
            });
        }

        $(document).on('click', '.btnRemoveBudget', function (){
            const budget_id = $(this).attr('budget-id');
            const budget_name = $(this).closest('tr').find('td:eq(1)').html();

            Swal.fire({
                title: 'Exclusão de Veículo',
                html: "Você está prestes a excluir definitivamente o orçamento <br><strong>"+budget_name+"</strong><br>Deseja continuar?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#bbb',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: 'POST',
                        url: "{{ route('ajax.budget.delete') }}",
                        data: { budget_id },
                        dataType: 'json',
                        success: response => {
                            $('[data-bs-toggle="tooltip"]').tooltip('dispose')
                            tableBudget.destroy();
                            $("#tableBudgets tbody").empty();
                            tableBudget = getTable();
                            Toast.fire({
                                icon: response.success ? 'success' : 'error',
                                title: response.message
                            })
                        }, error: e => {
                            console.log(e);
                        },
                        complete: function(xhr) {
                            if (xhr.status === 403) {
                                Toast.fire({
                                    icon: 'error',
                                    title: 'Você não tem permissão para fazer essa operação!'
                                });
                                $(`button[budget-id="${budget_id}"]`).trigger('blur');
                            }
                        }
                    });
                }
            })
        });

        $(document).on('click', '.btnApproveBudget', function (){
            $('#confirmBudget').modal('show');
        });

        $(document).on('click', '.btnApproveBudget', function (){
            const budget_id = $(this).attr('budget-id');
            const budget_name = $(this).closest('tr').find('td:eq(1)').html();

            Swal.fire({
                title: 'Aprovar orçamento',
                html: "Você está prestes a confirmar o orçamento <br><strong>"+budget_name+"</strong><br>Deseja continuar?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2196f3',
                cancelButtonColor: '#bbb',
                confirmButtonText: 'Sim, aprovar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: 'POST',
                        url: "{{ route('ajax.budget.confirm') }}",
                        data: { budget_id },
                        dataType: 'json',
                        success: response => {
                            $('[data-bs-toggle="tooltip"]').tooltip('dispose')
                            tableBudget.destroy();
                            $("#tableBudgets tbody").empty();
                            tableBudget = getTable();

                            if (!response.success) {
                                Toast.fire({
                                    icon: 'error',
                                    title: response.message
                                })
                            } else {

                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    html: `<a href="${$('[name="rental_url"]').val()}" class="btn btn-primary col-md-12"> Abrir listagem de locações</a>`
                                });
                            }
                        }, error: e => {
                            console.log(e);
                        },
                        complete: function(xhr) {
                            if (xhr.status === 403) {
                                Toast.fire({
                                    icon: 'error',
                                    title: 'Você não tem permissão para fazer essa operação!'
                                });
                                $(`button[budget-id="${budget_id}"]`).trigger('blur');
                            }
                        }
                    });
                }
            })
        });

        $('[name="situation"], [name="clients"]').change(function(){
            $('[data-bs-toggle="tooltip"]').tooltip('dispose')
            tableBudget.destroy();
            $("#tableBudgets tbody").empty();
            tableBudget = getTable();
        });
    </script>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12 grid-margin">
            @if(session('success'))
                <div class="alert alert-animate alert-success"><i class="fa fa-check-circle"></i> {{session('success')}}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-animate alert-danger mt-2">{{session('warning')}}</div>
            @endif
            <div class="card">
                <div class="card-body">
                    <div class="header-card-body justify-content-between flex-wrap">
                        <h4 class="card-title no-border">Orçamentos Realizados</h4>
                        @if(in_array('BudgetCreatePost', $permissions))
                        <a href="{{ route('budget.create') }}" class="mb-3 btn btn-primary col-md-3 btn-rounded btn-fw"><i class="fas fa-plus"></i> Novo Orçamento</a>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8 form-group">
                            <label>Cliente</label>
                            <select class="form-control select2" name="clients">
                                <option value="0">Todos</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Situação</label>
                            <select class="form-control" id="situation" name="situation">
                                <option value="1">Não expirados</option>
                                <option value="0">Expirados</option>
                                <option value="all">Todos</option>
                            </select>
                        </div>
                    </div>
                    <table id="tableBudgets" class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente/Endereço</th>
                                <th>Criado Em</th>
                                <th>Expira Em</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th>Código</th>
                                <th>Cliente/Endereço</th>
                                <th>Criado Em</th>
                                <th>Expira Em</th>
                                <th>Ação</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="rental_url" value="{{ route('rental.index') }}">
@stop
