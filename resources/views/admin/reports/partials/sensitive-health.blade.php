@if (count($rows))
    <section class="report-section">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Campista</th>
                    <th>Tribo</th>
                    <th>Idade</th>
                    <th>Remédio</th>
                    <th>Recomendação</th>
                    <th>Responsável</th>
                    <th>Telefone</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>
                            <span class="report-table-tribe" style="--report-accent: {{ $row['tribe_accent'] }};">
                                <span class="report-table-tribe__swatch" aria-hidden="true"></span>
                                {{ $row['tribe'] }}
                            </span>
                        </td>
                        <td>{{ $row['age'] ?? '-' }}</td>
                        <td>{{ $row['medicine'] }}</td>
                        <td>{{ $row['recommendation'] }}</td>
                        <td>{{ $row['responsible'] ?? 'Não informado' }}</td>
                        <td>{{ $row['phone'] ?? 'Não informado' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@else
    <p class="report-empty">Nenhum campista com sinalização médica nos filtros informados.</p>
@endif
