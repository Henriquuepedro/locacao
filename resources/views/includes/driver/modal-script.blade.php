<script>
    $(() => {
        $('#newDriverModal [name="cpf"]').mask('000.000.000-00');
        $('#newDriverModal [name="phone"]').mask('(00) 000000000');
        $('#newDriverModal [name="rg"], #newDriverModal [name="cnh"]').mask('0#');
        $('#newDriverModal [name="commission"]').maskMoney({thousands: '.', decimal: ',', allowZero: true});
        $('#newDriverModal [name="address_state"], #newDriverModal [name="address_city"]').select2();
        $('#newDriverModal [name="address_zipcode"]').mask('00.000-000');
    });

    $('#newDriverModal').on('shown.bs.modal', function(){
        loadStates($('#newDriverModal [name="address_state"]'));
        loadCities($('#newDriverModal [name="address_city"]'));
        loadSearchZipcode('#formCreateDriverModal [name="address_zipcode"]', $('#formCreateDriverModal'));
    });

    $('#newDriverModal').on('hidden.bs.modal', function(e){
        $("body").addClass("modal-open");
    });

    $('#btnModalNewDriverModal').on('click', function(e){
        $('#newDriverModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        }).modal("show");
    });

    // Validar dados
    $("#formCreateDriverModal").validate({
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
            let getForm = $('#formCreateDriverModal');

            getForm.find('button[type="submit"]').attr('disabled', true);

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: 'POST',
                url: getForm.attr('action'),
                data: getForm.serialize(),
                dataType: 'json',
                success: response => {

                    getForm.find('button[type="submit"]').attr('disabled', false);

                    if (!response.success) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atenção',
                            html: '<ol><li>' + response.message + '</li></ol>'
                        });
                        return false;
                    }

                    Toast.fire({
                        icon: 'success',
                        title: response.message
                    });

                    const el_select_el = $('#newDriverModal').is(':visible') && $('#newVehicleModal').is(':visible') ? null : $('#newDriverModal [name="element_to_load"]').val();

                    $('#newDriverModal').modal('hide');

                    cleanFormDriverModal();
                    checkLabelAnimate();

                    if (el_select_el) {
                        loadDrivers(response.driver_id, `[name='${el_select_el}']`);
                        $('#equipments-selected [id^=collapseEquipment-] [name^="driver_"]').each(function(){
                            if (el_select_el != $(this).prop('name')) {
                                loadDrivers($(this).val(), `[id^="collapseEquipment-"] [name^="driver_"]`);
                            }
                        });

                        $('#equipments-selected [name^=withdrawal_equipment_actual_driver_]').each(function(){
                            if (el_select_el != $(this).prop('name')) {
                                loadDrivers($(this).val(), `[name="${$(this).prop('name')}"]`);
                            }
                        });
                    } else {
                        @if(\Request::route()->getName() == 'rental.create' || \Request::route()->getName() == 'rental.exchange' || \Request::route()->getName() == 'rental.update')
                            loadDrivers($('#newVehicleModal').is(':visible') ? 0 : response.driver_id, "div[id^='collapseEquipment-'].collapse.show [name^='driver_']");
                            loadDrivers($('#newVehicleModal').is(':visible') ? response.driver_id : 0, '#newVehicleModal [name="driver"]');

                            $('#equipments-selected [id^=collapseEquipment-]').each(function () {
                                if ($("div[id^='collapseEquipment-'].collapse.show").attr('id-equipment') !== $(this).attr('id-equipment')) {
                                    loadDrivers($('[name^="driver_"]', this).val(), `#collapseEquipment-${$(this).attr('id-equipment')} [name^="driver_"]`);
                                }
                            });

                            $('#equipments-selected [name^=withdrawal_equipment_actual_driver_]').each(function () {
                                loadDrivers($(this).val(), `[name="${$(this).prop('name')}"]`);
                            });
                        @else
                            loadDrivers(response.driver_id, null);
                        @endif
                    }

                }, error: e => {
                    getForm.find('button[type="submit"]').attr('disabled', false);
                    let arrErrors = [];

                    $.each(e.responseJSON.errors, function( index, value ) {
                        arrErrors.push(value);
                    });

                    if (!arrErrors.length && e.responseJSON.message !== undefined)
                        arrErrors.push('Você não tem permissão para fazer essa operação!');

                    Swal.fire({
                        icon: 'warning',
                        title: 'Atenção',
                        html: '<ol><li>'+arrErrors.join('</li><li>')+'</li></ol>'
                    });
                }
            });
        }
    });

    jQuery.validator.addMethod("cpf", function(value, element) {
        value = jQuery.trim(value);

        return this.optional(element) || validCPF(value);

    }, 'Informe um CPF válido');

    const cleanFormDriverModal = () => {
        $('#newDriverModal [name="name"]').val('');
        $('#newDriverModal [name="email"]').val('');
        $('#newDriverModal [name="phone"]').val('');
        $('#newDriverModal [name="cpf"]').val('');
        $('#newDriverModal [name="rg"]').val('');
        $('#newDriverModal [name="cnh"]').val('');
        $('#newDriverModal [name="cnh_exp"]').val('');
        $('#newDriverModal [name="observation"]').val('');
    }

    const loadDrivers = (driver_id = null, el = null, use_last_id = true) => {

        $(el ?? '.driver-load [name="driver"]').attr('disabled', true).empty().append('<option>Carregando ...</option>');

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'GET',
            url: '{{ route('ajax.driver.get-drivers') }}',
            dataType: 'json',
            success: response => {
                let selected;
                let driver_id_selected = driver_id ?? (use_last_id ? response.lastId : 0);

                $(el ?? '.driver-load [name="driver"]').empty().append('<option value="0">Selecione ...</option>');
                $.each(response.data, function( index, value ) {
                    selected = value.id === parseInt(driver_id_selected) ? 'selected' : '';
                    $(el ?? '.driver-load [name="driver"]').append(`<option value='${value.id}' ${selected}>${value.name}</option>`);
                });

            }, error: e => {
                $.each(e.responseJSON.errors, function( index, value ) {
                    arrErrors.push(value);
                });

                if (!arrErrors.length && e.responseJSON.message !== undefined)
                    arrErrors.push('Você não tem permissão para fazer essa operação!');

                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    html: '<ol><li>'+arrErrors.join('</li><li>')+'</li></ol>'
                });
            },
            complete: () => {
                $(el ?? '.driver-load [name="driver"]').attr('disabled', false);
            }
        });
    }
</script>
