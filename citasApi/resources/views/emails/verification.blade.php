<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación - Citas App</title>
    <style>
        /* Reset y base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        /* Header con branding */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        /* Contenido principal */
        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        /* Código de verificación */
        .code-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .code-label {
            color: white;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .verification-code {
            font-size: 36px;
            font-weight: bold;
            color: white;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Información de seguridad */
        .security-info {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }

        .security-title {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .security-list {
            list-style: none;
            padding: 0;
        }

        .security-list li {
            color: #4a5568;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }

        .security-list li:before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Call to action */
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.2s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background-color: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .footer-content {
            max-width: 400px;
            margin: 0 auto;
        }

        .footer-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .footer-text {
            font-size: 14px;
            opacity: 0.8;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .contact-info {
            font-size: 12px;
            opacity: 0.6;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .greeting {
                font-size: 20px;
            }

            .verification-code {
                font-size: 28px;
                letter-spacing: 4px;
            }

            .code-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">Citas App</div>
            <div class="subtitle">Sistema de Gestión de Citas Médicas</div>
        </div>

        <!-- Contenido principal -->
        <div class="content">
            <h1 class="greeting">¡Hola!</h1>

            <p class="message">
                Has solicitado un código de verificación para acceder a tu cuenta en Citas App.
                Utiliza el código de abajo para completar el proceso de verificación.
            </p>

            <!-- Código de verificación -->
            <div class="code-container">
                <div class="code-label">Tu código de verificación</div>
                <div class="verification-code">{{ $code }}</div>
            </div>

            <!-- Información de seguridad -->
            <div class="security-info">
                <h3 class="security-title">Información de seguridad</h3>
                <ul class="security-list">
                    <li>Este código es válido por 10 minutos</li>
                    <li>No compartas este código con nadie</li>
                    <li>Si no solicitaste este código, ignóralo</li>
                    <li>Por tu seguridad, este código solo puede usarse una vez</li>
                </ul>
            </div>

            <p style="text-align: center; color: #718096; font-size: 14px; margin-top: 20px;">
                ¿Necesitas ayuda? Contacta a nuestro equipo de soporte.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <div class="footer-title">Citas App</div>
                <div class="footer-text">
                    Tu compañero confiable para gestionar citas médicas de manera eficiente y segura.
                </div>
                <div class="contact-info">
                    Soporte: soporte@citasapp.com<br>
                    Teléfono: +57 123 456 7890
                </div>
            </div>
        </div>
    </div>
</body>
</html>
