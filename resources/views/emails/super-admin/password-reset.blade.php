<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - Super Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Recuperação de Senha</h1>
            <p>Sistema SaaS Automotivo - Super Admin</p>
        </div>

        <div class="content">
            <p>Olá <strong>{{ $userName }}</strong>,</p>

            <p>Recebemos uma solicitação para redefinir sua senha de Super Administrador.</p>

            <p>Para continuar com a redefinição de senha, clique no botão abaixo:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">🔑 Redefinir Senha</a>
            </div>

            <div class="warning">
                <strong>⚠️ Importante:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Este link é válido até: <strong>{{ $expiresAt }}</strong></li>
                    <li>Se você não solicitou esta redefinição, ignore este email</li>
                    <li>Por segurança, o link expira automaticamente</li>
                </ul>
            </div>

            <p>Se o botão não funcionar, copie e cole o link abaixo no seu navegador:</p>
            <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                {{ $resetUrl }}
            </p>
        </div>

        <div class="footer">
            <p>Este é um email automático, não responda a esta mensagem.</p>
            <p>© {{ date('Y') }} Sistema SaaS Automotivo. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
