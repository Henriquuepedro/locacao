<table class='table content'>
    <thead>
        <th class='text-left'>
            Locação
        </th>
        <th class='text-left'>
            Cliente
        </th>
        <th class='text-left'>
            Criado em
        </th>
        <th class='text-left'>
            Valor
        </th>
    </thead>
    <tbody>
        @foreach($rentals as $rental)
            <tr>
                <td class='info' style='width: 10%'>
                    {{ formatCodeIndex($rental->code) }}
                </td>
                <td class='info' style='width: 50%'>
                    {{ $rental->client_name }}
                </td>
                <td class='info' style='width: 20%'>
                    {{ dateInternationalToDateBrazil($rental->created_at, DATETIME_BRAZIL_NO_SECONDS) }}
                </td>
                <td class='info' style='width: 20%'>
                    {{ formatMoney($rental->net_value, 2, 'R$ ') }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
