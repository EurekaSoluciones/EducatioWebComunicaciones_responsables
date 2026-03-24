<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cartelera {{ $cartelera->nombre }}</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      color: #1f2937;
    }

    .wrap {
      max-width: 960px;
      margin: 0 auto;
      padding: 24px 16px 40px;
    }

    .card {
      background: #fff;
      border: 1px solid #dbe1e7;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
      overflow: hidden;
    }

    .header {
      padding: 20px 24px;
      border-bottom: 1px solid #e5e7eb;
    }

    .header h1 {
      margin: 0;
      font-size: 28px;
      line-height: 1.2;
    }

    .body {
      padding: 24px;
    }

    .content {
      padding: 20px;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      min-height: 240px;
      overflow-wrap: anywhere;
    }

    .content img {
      max-width: 100%;
      height: auto;
    }

    .attachments {
      margin-top: 24px;
    }

    .attachments h2 {
      margin: 0 0 12px;
      font-size: 20px;
    }

    .attachments ul {
      list-style: none;
      padding: 0;
      margin: 0;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      overflow: hidden;
    }

    .attachments li {
      border-bottom: 1px solid #e5e7eb;
    }

    .attachments li:last-child {
      border-bottom: 0;
    }

    .attachments a {
      display: block;
      padding: 14px 16px;
      color: #0f4c81;
      text-decoration: none;
    }

    .attachments a:hover {
      background: #f8fafc;
    }

    .meta {
      margin-top: 20px;
      color: #6b7280;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="header">
        <h1>Cartelera {{ $cartelera->nombre }}</h1>
      </div>

      <div class="body">
        <div class="content">
          {!! $cartelera->cartelera !!}
        </div>

        @if($cartelera->adjuntos->count() > 0)
          <div class="attachments">
            <h2>Adjuntos</h2>

            <ul>
              @foreach($cartelera->adjuntos as $adjunto)
                <li>
                  <a href="{{ url("/storage/$adjunto->filename") }}" download="{{ $adjunto->originalFilename }}" target="_blank">
                    {{ $adjunto->originalFilename }}
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="meta">
          Creada {{ $cartelera->created_at->format('d/m/Y H:i') }} - {{ $cartelera->created_at->diffForHumans() }}
        </div>
      </div>
    </div>
  </div>
</body>
</html>
