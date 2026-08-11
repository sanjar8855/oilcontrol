<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }
        h1 {
            font-size: 16px;
            margin: 0 0 2px 0;
        }
        .meta {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .footer {
            margin-top: 10px;
            font-size: 9px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ $workshopName }} &middot; {{ $generatedAt }} &middot; {{ count($rows) }} {{ trans('export.total_suffix') }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">{{ trans('export.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">OilControl &middot; {{ $generatedAt }}</div>
</body>
</html>
