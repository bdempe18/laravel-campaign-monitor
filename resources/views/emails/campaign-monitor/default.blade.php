<!DOCTYPE html>
<html>
<head>
    <title>Campaign Monitor Email</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .data-container { margin: 20px 0; }
        dl { margin: 0; }
        dt { font-weight: bold; color: #333; margin-top: 10px; }
        dd { margin: 5px 0 15px 20px; color: #666; }
        .no-data { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <h2>Campaign Monitor Email</h2>
    <p>This email is sent via Campaign Monitor smart email.</p>

    <div class="data-container">
        <h3>Data:</h3>

        @php
          $data = get_defined_vars() ?? [];
        @endphp

        @if(isset($data) && is_array($data) && count($data) > 0)
            <dl>
                @foreach($data as $key => $value)
                    <dt>{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                    <dd>
                        @if(is_array($value) || is_object($value))
                            {{ json_encode($value, JSON_PRETTY_PRINT) }}
                        @elseif(is_bool($value))
                            {{ $value ? 'Yes' : 'No' }}
                        @elseif(is_null($value))
                            <em>Not provided</em>
                        @else
                            @if(filter_var($value, FILTER_VALIDATE_URL))
                                <a href="{{ $value }}">{{ $value }}</a>
                            @else
                                {{ $value }}
                            @endif
                        @endif
                    </dd>
                @endforeach
            </dl>
        @else
            <div class="no-data">
                <p><strong>No data available</strong></p>
                <p>There is currently no information to display.</p>
            </div>
        @endif
    </div>

</body>
</html>
