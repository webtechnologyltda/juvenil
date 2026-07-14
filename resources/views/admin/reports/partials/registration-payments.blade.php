@if (count($rows))
    <section class="report-section">
        <table class="report-table report-payment-table">
            <thead>
                <tr>
                    <th>Inscrição</th>
                    <th>Lançamento</th>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Forma</th>
                    <th>Status do pagamento</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            <span class="report-payment-table__type">{{ $row['registration_type'] }}</span>
                            <strong>{{ $row['registration_name'] }}</strong>
                        </td>
                        <td>
                            <strong>{{ $row['launch_name'] }}</strong>
                            <span class="report-payment-table__detail">{{ $row['category'] }}</span>
                        </td>
                        <td>{{ $row['date'] }}</td>
                        <td><strong>{{ $row['amount'] }}</strong></td>
                        <td>{{ $row['payment_method'] }}</td>
                        <td>
                            <span class="report-badge report-badge--{{ $row['status']['color'] }}">
                                @svg($row['status']['icon'], 'report-badge__icon', ['aria-hidden' => 'true'])
                                {{ $row['status']['label'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@else
    <p class="report-empty">Nenhum pagamento de campista ou equipe de trabalho encontrado com os filtros informados.</p>
@endif
