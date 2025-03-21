let charts_started = false;
let all_markers = [];
let markers_dashboard;
const element_dashboard = document.getElementById('mapRentals');
let map_rentals_dashboard = L.map(element_dashboard, {
    //fullscreenControl: true,
    fullscreenControl: {
        pseudoFullscreen: false
    }
});
L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map_rentals_dashboard);

$(function () {
    elementForm = $('#viewRental');
    draggableMap = false;
    gestureHandlingMap = true;
    'use strict';
});

const initCharts = () => {
    if (charts_started) {
        return;
    }

    charts_started = true;

    newClientsForMonth();
    rentalsForMonth();
    billsForMonth();
    clientsTopRentals();
    rentalsLate();
    billingOpenLate();
    loadMap();
}

onLocationError = async (e, zoom = 12) => {
    if (parseInt(e.code) === 1) {
        const latLng = await deniedLocation(true);
        if (latLng) {
            const latCenter = latLng.lat;
            const lngCenter = latLng.lng;
            const center    = L.latLng(latCenter, lngCenter);

            map_rentals_dashboard.setView(center, zoom);
        }
    }
}

const onLocationFound = (map, zoom = 12) => {
    map_rentals_dashboard.setView(map.latlng, zoom);
}

const removeAllMarkers = () => {
    // Remove todos os marcadores que estão juntos.
    map_rentals_dashboard.removeLayer(markers_dashboard);
    // Remove todos os marcadores que não estão juntos.
    for (let i = 0; i < all_markers.length; i++) {
        map_rentals_dashboard.removeLayer(all_markers[i]);
    }
}

const loadMap = () => {
    // Remove marcadores.
    if(all_markers.length !== 0) {
        removeAllMarkers();
    }

    all_markers = []; // arcadores em branco.

    markers_dashboard = L.markerClusterGroup({ disableClusteringAtZoom: 17 });
    let bounds = []; // Coordenadas em branco.
    let type_map = 0; // 0 - marcadores não juntos, 1 - marcadores juntos.
    let lat, lng, client_name, address, content_info, target, created_at;

    $.getJSON($('#route_rentals_open').val(), function(data) {
        // Não existemm locações, deve pegar a localização da empresa.
        if (data.length === 0) {
            map_rentals_dashboard.on('locationfound', onLocationFound);
            map_rentals_dashboard.on('locationerror', onLocationError);
            map_rentals_dashboard.locate({setView: true, maxZoom: 12});
            setTimeout(() => {
                map_rentals_dashboard.invalidateSize();
            }, 1000);
            return false;
        }

        // Adiciona marcadores no mapa.
        $(data).each(function(k, value){
            lat         = value.address_lat;
            lng         = value.address_lng;
            client_name = value.client.name;
            address     = `${value.address_name}, ${value.address_number} - ${value.address_zipcode} - ${value.address_neigh} - ${value.address_city}/${value.address_state}` ;
            type_map    = 1;
            created_at  = formatDate(value.created_at, FORMAT_DATETIME_BRAZIL_NO_SECONDS);

            content_info = `
                <div style="width: 450px">
                    <div class="display-flex justify-center">
                        <h4 class="text-center font-weight-bold mb-3">${client_name}</h4>
                    </div>
                    <div class="display-flex justify-center text-center mt-2">
                        <span class="">
                            <b>Endereço:</b> ${address}
                        </span>
                    </div>
                    <div class="display-flex justify-center text-center mt-2">
                        <span class="">
                            <b>Criado em:</b> ${created_at}
                        </span>
                    </div>
                    <div  class="display-flex justify-center text-center">
                        <span class="text-black mt-1">
                            <button class="btn btn-link btnViewRental" data-rental-id="${value.id}">Visualizar Locação #${formatCodeIndex(value.code)}</button>
                        </span>
                    </div>
                </div>
            `;

            target = L.latLng(lat, lng);

            // Usuário visualizar marcadores juntos
            if (type_map === 0) {
                marker = L.marker(target).addTo(map_rentals_dashboard).bindPopup(content_info, {
                    maxWidth: 560
                });
            }
            // Usuário visualizar marcadores separados
            if (type_map === 1) {
                marker = L.marker(target).bindPopup(content_info, {
                    maxWidth: 560
                });
            }

            all_markers.push(marker);
            markers_dashboard.addLayer(marker);
            bounds.push(L.point(lat, lng));
        });

        // Lat e lng inicial.
        let lat_center = 0;
        let lng_center = 0;

        // lat e lng do ponto central dos pontos
        if (bounds.length === 1) {
            lat_center = L.bounds(bounds).max.x;
            lng_center = L.bounds(bounds).max.y;
        }
        if (bounds.length > 1) {
            lat_center = L.bounds(bounds).getCenter().x;
            lng_center = L.bounds(bounds).getCenter().y;
        }

        // Centraliza o mapa.
        const center = L.latLng(lat_center, lng_center);
        map_rentals_dashboard.setView(center, 12);
        // Adicionar marcadores juntos.
        if (type_map === 1) {
            map_rentals_dashboard.addLayer(markers_dashboard);
        }

        // Aguardar um segundo para iniciar.
        setTimeout(() => {
            map_rentals_dashboard.invalidateSize();
        }, 1000);
    });
}

const newClientsForMonth = () => {
    $.getJSON($('#route_new_clients_for_month').val(), function(response) {
        let labels = [];
        let data = [];
        let max_registers = 0;
        let step_size = 0;
        const rgb_color_primary = '62, 173, 114';

        for (const property in response) {
            labels.push(property);
            data.push(response[property]);

            if (response[property] > max_registers) {
                max_registers = response[property];
            }
        }

        step_size = getStepSizeChart(max_registers);

        let newClientsChartCanvas = $("#newClientsChart").get(0).getContext("2d");
        let saleGradientBg = newClientsChartCanvas.createLinearGradient(5, 0, 5, 100);
        saleGradientBg.addColorStop(0, `rgba(${rgb_color_primary}, 0.25)`);
        saleGradientBg.addColorStop(1, `rgba(${rgb_color_primary}, 0.03)`);

        let lineData = {
            labels,
            datasets: [{
                data,
                label: "Clientes",
                backgroundColor: saleGradientBg,
                borderColor: [
                    `rgb(${rgb_color_primary})`
                ],
                fill: true, // 3: no fill
                borderWidth: 1.5,
                pointBorderWidth: 1,
                pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4],
                pointHoverRadius: [3, 3, 3, 3, 3, 3, 3, 3, 3],
                pointBackgroundColor: [
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`
                ],
                pointBorderColor: ['#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730'],
            }]
        };
        let lineOptions = {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Mês',
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Número de clientes',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        display: true,
                        autoSkip: false,
                        maxRotation: 0,
                        stepSize: step_size,
                        min: 0,
                        max: max_registers
                    }
                }
            },
        }

        new Chart(newClientsChartCanvas, {
            type: 'line',
            data: lineData,
            options: lineOptions
        });
    });
}

const rentalsForMonth = () => {
    $.getJSON($('#route_rentals_for_month').val(), function(response) {
        let labels = [];
        let data = [];
        let max_registers = 0;
        let step_size = 0;
        const rgb_color_primary = '17,65,152';

        for (const property in response) {
            labels.push(property);
            data.push(response[property]);

            if (response[property] > max_registers) {
                max_registers = response[property];
            }
        }

        step_size = getStepSizeChart(max_registers);

        let rentalsDoneChartCanvas = $("#rentalsDoneChart").get(0).getContext("2d");
        let saleGradientBg = rentalsDoneChartCanvas.createLinearGradient(5, 0, 5, 100);
        saleGradientBg.addColorStop(0, `rgba(${rgb_color_primary}, 0.25)`);
        saleGradientBg.addColorStop(1, `rgba(${rgb_color_primary}, 0.03)`);

        let lineData = {
            labels,
            datasets: [{
                data,
                label: "Locações",
                backgroundColor: saleGradientBg,
                borderColor: [
                    `rgb(${rgb_color_primary})`,
                ],
                fill: true, // 3: no fill
                borderWidth: 1.5,
                pointBorderWidth: 1,
                pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4],
                pointHoverRadius: [3, 3, 3, 3, 3, 3, 3, 3, 3],
                pointBackgroundColor: [
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`,
                    `rgb(${rgb_color_primary})`
                ],
                pointBorderColor: ['#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730'],
            }]
        };
        let lineOptions = {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                filler: {
                    propagate: false
                },
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Mês',
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Locações por mês',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        display: true,
                        autoSkip: false,
                        maxRotation: 0,
                        stepSize: step_size,
                        min: 0,
                        max: max_registers
                    }
                }
            }
        }

        new Chart(rentalsDoneChartCanvas, {
            type: 'line',
            data: lineData,
            options: lineOptions
        });
    });
}

const billsForMonth = () => {
    $.getJSON($('#route_bills_for_month').val(), function(response) {
        let labels = [];
        let data_receive = [];
        let data_pay = [];
        let max_registers = 0;
        let step_size = 0;

        for (const property in response) {
            labels.push(property);
            data_receive.push(response[property].receive);
            data_pay.push(response[property].pay);

            if (response[property].receive > max_registers) {
                max_registers = response[property].receive;
            }
            if (response[property].pay > max_registers) {
                max_registers = response[property].pay;
            }
        }

        step_size = getStepSizeChart(max_registers);

        let billingChartCanvas = $("#billingChart").get(0).getContext("2d");
        new Chart(billingChartCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Faturamento',
                        data: data_receive,
                        backgroundColor: 'rgba(84, 195, 190, 0.5)',
                        borderColor: 'rgba(84, 195, 190, 1)',
                        borderWidth: 1.5,
                        fill: false
                    },
                    {
                        label: 'Despesas',
                        data: data_pay,
                        backgroundColor: 'rgba(239, 114, 111, 0.5)',
                        borderColor: 'rgba(239, 114, 111, 1)',
                        borderWidth: 1.5,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: tooltipItems => {
                                return `${tooltipItems.dataset.label}: ${numberToReal(tooltipItems.raw, 'R$ ')}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Mês',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Financeiro por mês',
                            font: {
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            display: true,
                            autoSkip: false,
                            maxRotation: 0,
                            stepSize: step_size,
                            min: 0,
                            max: max_registers,
                            callback: function(val, index) {
                                // Hide every 2nd tick label
                                return numberToReal(val, 'R$ ');
                            }
                        }
                    }
                }
            }
        });
    });
}

const clientsTopRentals = () => {
    $.getJSON($('#route_clients_top_rentals').val(), function(response) {
        $('#top_clients_rental').empty();
        $(response).each(function (key, value) {
            $('#top_clients_rental').append(
                `<li>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex">
                        <div>
                            <h6 class="mb-0"><a href="${$('#route_update_client').val()}/${value.client_id}">${value.name}</a></h6>
                            <small class="text-muted">${value.email ?? '&nbsp;'}</small>
                        </div>
                    </div>
                    <div>
                        <small class="d-block mb-0">${value.total}</small>
                    </div>
                </div>
            </li>`);
        });
    });
}

const rentalsLate = () => {
    $.getJSON($('#route_rentals_late_by_type').val(), function(response) {
        let data = [
            {...{x: "Para entregar atrasado", type: "to_delivery"}, ...{total: response.to_delivery ?? 0}},
            {...{x: "Para retirar atrasado", type: "to_withdraw"}, ...{total: response.to_withdraw ?? 0}},
            {...{x: "Sem data de retirada", type: "no_date_to_withdraw"}, ...{total: response.no_date_to_withdraw ?? 0}},
        ];
        let max_registers = Math.max.apply(null, data);
        let step_size = getStepSizeChart(max_registers);

        let lineData = {
            labels: ["Para entregar atrasado", "Para retirar atrasado", "Sem data de retirada"],
            datasets: [{
                data,
                backgroundColor: ["rgba(93, 98, 180, 0.5)", "rgba(84, 195, 190, 0.5)", "rgba(249, 196, 70, 0.5)"],
                borderColor: ["rgba(93, 98, 180, 1)", "rgba(84, 195, 190, 1)", "rgba(249, 196, 70, 1)"],
                label: "Locações atrasadas",
                parsing: {
                    yAxisKey: 'total'
                },
                borderWidth: 2,
                fill: false
            }]
        };
        let lineOptions = {
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: tooltipItems => {
                            const rentals = tooltipItems.raw.total;
                            let complement_rental = rentals <= 1 ? 'locação' : 'locações';

                            return `${rentals} ${complement_rental}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: false,
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Quantidade de locações',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        display: true,
                        autoSkip: false,
                        maxRotation: 0,
                        stepSize: step_size,
                        min: 0,
                        max: max_registers
                    }
                }
            },
            onClick: (evt, item) => {
                if (evt.chart.boxes[0].legendItems.length) {
                    const type = item[0].element['$context'].raw.type;
                    let url_redirect = '';

                    if (type === 'to_delivery') {
                        url_redirect = $('#route_list_table_rental_to_delivery_late').val();
                    } else if (type === 'to_withdraw') {
                        url_redirect = $('#route_list_table_rental_to_withdraw_late').val();
                    } else if (type === 'no_date_to_withdraw') {
                        url_redirect = $('#route_list_table_rental_no_date_to_withdraw_late').val();
                    }

                    window.location.href = url_redirect;
                }
            },
            onHover: event => {
                event.native.target.style.cursor = 'pointer';
            }
        }
        let rentalsLateChartCanvas = $("#rentalsLateChart").get(0).getContext("2d");
        new Chart(rentalsLateChartCanvas, {
            type: 'bar',
            data: lineData,
            options: lineOptions
        });
    });
}

const billingOpenLate = () => {
    $.getJSON($('#route_dashboard_get_billing_open_late').val(), function(response) {
        let data = [
            {...{x: "Receber atrasado", type: "receive"}, ...response.receive},
            {...{x: "Pagar atrasado", type: "pay"}, ...response.pay}
        ];
        let max_registers = Math.max.apply(null, [data[0].total_value, data[1].total_value]);
        let step_size = getStepSizeChart(max_registers);

        let lineData = {
            labels: ["Receber atrasado", "Pagar atrasado"],
            datasets: [{
                data,
                backgroundColor: ["rgba(84, 195, 190, 0.5)", "rgba(239, 114, 111, 0.5)"],
                borderColor: ["rgba(84, 195, 190, 1)", "rgba(239, 114, 111, 1)"],
                borderWidth: 2,
                label: "Pagamentos atrasadas",
                parsing: {
                    yAxisKey: 'total_value'
                }
            }]
        };
        let lineOptions = {
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: tooltipItems => {
                            const payments = tooltipItems.raw;
                            let complement_payment = payments.total_count <= 1 ? 'pagamento' : 'pagamentos';

                            return `${numberToReal(payments.total_value, 'R$ ')} de ${payments.total_count} ${complement_payment}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: false,
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Valores de pagamentos',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        display: true,
                        autoSkip: false,
                        maxRotation: 0,
                        stepSize: step_size,
                        min: 0,
                        max: max_registers,
                        callback: function(val, index) {
                            // Hide every 2nd tick label
                            return numberToReal(val, 'R$ ');
                        }
                    }
                }
            },
            onClick: (evt, item) => {
                if (evt.chart.boxes[0].legendItems.length) {
                    const type = item[0].element['$context'].raw.type;
                    let url_redirect = '';
                    if (type === 'receive') {
                        url_redirect = $('#route_list_table_bill_to_receive_late').val();
                    } else if (type === 'pay') {
                        url_redirect = $('#route_list_table_bill_to_pay_late').val();
                    }
                    window.location.href = url_redirect;
                }
            },
            onHover: event => {
                event.native.target.style.cursor = 'pointer';
            }
        }
        let billingOpenLateChartCanvas = $("#billingOpenLateChart").get(0).getContext("2d");
        new Chart(billingOpenLateChartCanvas, {
            type: 'bar',
                data: lineData,
            options: lineOptions
        });
    });
}

const getStepSizeChart = max_registers => {
    if (max_registers <= 10) {
        return 1;
    }
    if (max_registers <= 20) {
        return 2;
    }
    if (max_registers <= 50) {
        return 5;
    }
    if (max_registers <= 100) {
        return 10;
    }
    if (max_registers <= 200) {
        return 20;
    }
    if (max_registers <= 500) {
        return 50;
    }
    if (max_registers <= 1000) {
        return 100;
    }
    if (max_registers <= 2000) {
        return 200;
    }
    if (max_registers <= 5000) {
        return 500;
    }
    if (max_registers <= 10000) {
        return 1000;
    }
    if (max_registers <= 20000) {
        return 2000;
    }
    if (max_registers <= 50000) {
        return 5000;
    }
    if (max_registers <= 100000) {
        return 10000;
    }

    return Math.ceil(max_registers/10);
}
