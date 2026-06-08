@if (count($rows))
    <section class="report-section">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Campista</th>
                    <th>Responsável</th>
                    <th>Telefone</th>
                    <th>Endereço</th>
                    <th>Referência</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['responsible'] ?? 'Não informado' }}</td>
                        <td>{{ $row['phone'] ?? 'Não informado' }}</td>
                        <td>{{ $row['address'] ?: 'Não informado' }}</td>
                        <td>{{ $row['reference'] ?? 'Não informado' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@else
    <p class="report-empty">Nenhum contato encontrado com os filtros informados.</p>
@endif
