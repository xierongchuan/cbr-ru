<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
    </head>
    <body style="font-family: system-ui, sans-serif; min-height: 100vh; margin: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem; color: #333;">Курсы валют ЦБ РФ</h1>
        <p style="color: #666; margin-bottom: 2rem;">Официальные курсы иностранных валют к рублю</p>

        <div style="display: flex; gap: 1rem;">
            <a href="/widget" style="background: #2563eb; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500;">Виджет</a>
            <a href="/settings" style="background: #4b5563; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500;">Настройки</a>
        </div>
    </body>
</html>