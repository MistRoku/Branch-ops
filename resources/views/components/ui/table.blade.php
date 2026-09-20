{{-- Table Component - Flat design --}}
@props(['columns' => [], 'data' => [], 'emptyMessage' => 'No data available'])

<div class="table-container">
    <table class="table">
        @if(count($columns) > 0)
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center py-8 text-brand-500">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
