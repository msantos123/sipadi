<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #4F46E5;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .content h2 {
            color: #333333;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .content p {
            color: #666666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .code-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            text-align: center;
            border-radius: 8px;
            margin: 25px 0;
        }
        .code-text {
            color: #ffffff;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            margin: 0;
            font-family: 'Courier New', monospace;
        }
        .info-box {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #92400E;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>SIPADI - Sistema de Información</h1>
        </div>
        
        <div class="content">
            <h2>¡Bienvenido/a {{ $user->nombres }} {{ $user->apellido_paterno }}!</h2>
            
            <p>Tu cuenta ha sido creada exitosamente en el sistema SIPADI.</p>
            
            <p>Esta es tu <strong>contraseña temporal</strong> para acceder al sistema:</p>
            
            <div class="code-box">
                <p class="code-text">{{ $verificationCode }}</p>
            </div>
            
            <div class="info-box">
                <p><strong>⚠️ Importante:</strong> Por seguridad, te recomendamos cambiar esta contraseña después de tu primer inicio de sesión.</p>
            </div>
            
            <p><strong>Datos de tu cuenta:</strong></p>
            <ul style="color: #666666; line-height: 1.8;">
                <li><strong>Nombre completo:</strong> {{ $user->nombres }} {{ $user->apellido_paterno }} {{ $user->apellido_materno }}</li>
                <li><strong>CI:</strong> {{ $user->ci }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                @if($user->celular)
                <li><strong>Celular:</strong> {{ $user->celular }}</li>
                @endif
            </ul>
            
            <p style="margin-top: 25px;">Si no solicitaste la creación de esta cuenta, por favor ignora este correo o contacta al administrador del sistema.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} SIPADI. Todos los derechos reservados.</p>
            <p>Este es un correo automático, por favor no responder.</p>
        </div>
    </div>
</body>
</html>
