@forelse ($tribes as $tribe)
    <section class="report-section">
        <div class="report-page__top report-page__top--tribe" style="--report-accent: {{ $tribe['accent'] }};">
            <h2>
                <span class="report-tribe-swatch" aria-hidden="true"></span>
                {{ $tribe['tribe'] }}
            </h2>
            <span class="report-badge">{{ $tribe['count'] }} {{ $tribe['count'] === 1 ? 'campista' : 'campistas' }}</span>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Campista</th>
                    <th>Idade</th>
                    <th>Status</th>
                    <th>Presença</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tribe['records'] as $record)
                    <tr>
                        <td>{{ $record['name'] }}</td>
                        <td>{{ $record['age'] ?? '-' }}</td>
                        <td>{{ $record['status'] }}</td>
                        <td>{{ $record['presence'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@empty
    <p class="report-empty">Nenhuma inscrição encontrada para o quadrante.</p>
@endforelse
