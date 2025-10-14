<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Invitation - {{ $appName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f7fa;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.95;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 20px;
        }
        
        .message {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 25px;
        }
        
        .invitation-card {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .invitation-card .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #718096;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .invitation-card .value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .invitation-card .value:last-child {
            margin-bottom: 0;
        }
        
        .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        .info-box {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .info-box h3 {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        
        .info-box li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
            color: #4a5568;
            font-size: 14px;
        }
        
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #48bb78;
            font-weight: bold;
            font-size: 16px;
        }
        
        .security-note {
            background-color: #fef5e7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        
        .security-note p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .security-note strong {
            color: #78350f;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }
        
        .footer {
            background-color: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            color: #718096;
            font-size: 14px;
            margin: 8px 0;
        }
        
        .footer-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        .copyright {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #a0aec0;
            font-size: 12px;
        }
        
        .help-text {
            text-align: center;
            margin-top: 25px;
            padding: 15px;
            background-color: #edf2f7;
            border-radius: 6px;
        }
        
        .help-text p {
            font-size: 13px;
            color: #4a5568;
            margin: 5px 0;
        }
        
        .help-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .cta-button {
                padding: 14px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🎉 You're Invited!</h1>
            <p>Welcome to {{ $appName }}</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">Hello there!</div>
            
            <div class="message">
                <p style="margin-bottom: 15px;">
                    Great news! <strong>{{ $inviterName }}</strong> has invited you to join <strong>{{ $appName }}</strong> as a valued team member.
                </p>
                <p>
                    We're excited to have you on board. To get started with your new account, simply click the button below to complete your registration.
                </p>
            </div>
            
            <!-- Invitation Details Card -->
            <div class="invitation-card">
                <div class="label">Your Email</div>
                <div class="value">{{ $email }}</div>
                
                <div class="label">Assigned Role</div>
                <div class="value">
                    <span class="role-badge">{{ $roleDisplay }}</span>
                </div>
                
                <div class="label">Invited By</div>
                <div class="value">{{ $inviterName }}</div>
            </div>
            
            <!-- Call to Action -->
            <div class="button-container">
                <a href="{{ $url }}" class="cta-button">Create Your Account Now</a>
            </div>
            
            <!-- Important Information -->
            <div class="info-box">
                <h3>📋 What You Need to Know</h3>
                <ul>
                    <li>This invitation expires <strong>{{ $expiresIn }}</strong> ({{ $expiresAt }})</li>
                    <li>The invitation link can only be used once for security</li>
                    <li>You'll have <strong>{{ $roleDisplay }}</strong> access privileges</li>
                    <li>Your email address is already registered in our system</li>
                    <li>After registration, you'll be automatically logged in</li>
                </ul>
            </div>
            
            <!-- Security Notice -->
            <div class="security-note">
                <p>
                    <strong>🔒 Security Notice:</strong> This invitation was sent to {{ $email }}. If you did not expect this invitation or believe it was sent in error, please disregard this email and contact our support team.
                </p>
            </div>
            
            <div class="divider"></div>
            
            <!-- Help Section -->
            <div class="help-text">
                <p><strong>Button not working?</strong></p>
                <p>
                    <a href="{{ $url }}" style="word-break: break-all;">Click here if you're not automatically redirected</a>
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>
                This is an automated message from <strong>{{ $appName }}</strong>.
            </p>
            <p>
                If you have any questions, please contact our support team.
            </p>
            
            <div class="copyright">
                <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
